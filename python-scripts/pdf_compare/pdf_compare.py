import sys, os, re
import json
import time
import html
import hashlib
import logging
import difflib
import unicodedata
from pathlib import Path
from concurrent.futures import ProcessPoolExecutor, as_completed

import fitz
import cv2
import numpy as np


logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)


def write_status(status_file, **data):
    """Atomically update the job status JSON."""
    status_file = Path(status_file)

    payload = {
        "updated_at": time.time(),
        **data
    }

    temp_file = Path(str(status_file) + ".tmp")

    try:
        with open(temp_file, "w", encoding="utf-8") as f:
            json.dump(payload, f)
            print(json.dumps(payload))
    except OSError:
        temp_file = None

    # On Windows os.replace raises PermissionError while the status page
    # (PHP) has status.json open, so retry and finally fall back to a
    # direct write instead of failing the whole comparison.
    if temp_file is not None:
        for attempt in range(10):
            try:
                os.replace(temp_file, status_file)
                return
            except PermissionError:
                time.sleep(0.05 * (attempt + 1))

    for attempt in range(10):
        try:
            with open(status_file, "w", encoding="utf-8") as f:
                json.dump(payload, f)
            break
        except PermissionError:
            if attempt == 9:
                logging.warning("Could not update %s", status_file)
            else:
                time.sleep(0.05 * (attempt + 1))

    if temp_file is not None:
        try:
            os.remove(temp_file)
        except OSError:
            pass


#MARK: Constants
ZERO_WIDTH = {"\u200b", "\u200c", "\u200d", "\u2060", "\ufeff", "\u00ad"}

LIGATURES = ("ffi", "ffl", "ff", "fi", "fl", "Th")
LIG_RE = re.compile("|".join(LIGATURES))

PAIR_MIN_RATIO = 0.5       # min similarity to treat two words as "same word, edited"
PAIRING_LIMIT = 40000      # max n*m for word pairing inside a replaced block
MOVE_RADIUS = 80.0         # pt, max distance for "shape moved" matching
MAX_RECTS = 400            # max highlight rectangles stored per finding
MAX_DRAWINGS = 6000        # above this, vector objects are not compared one by one

FONT_SIZE_TOL = 0.1        # pt: font size differences up to this are ignored
COLOR_TOL = 8              # per RGB channel (0-255): colour differences up to this are ignored

HIGHLIGHT_ALPHA = 0.40
MIN_HIGHLIGHT_PX = 6       # thin lines / zero-height shapes are drawn at least this thick

# PyMuPDF span flag bits that matter for formatting
FLAG_ITALIC = 2
FLAG_BOLD = 16

# BGR colours
RED = (0, 0, 255)
GREEN = (0, 160, 0)
YELLOW = (0, 255, 255)
BLUE = (255, 0, 0)
ORANGE = (0, 165, 255)

STRUCT_COLORS = {
    "shifted": YELLOW,          # same text / image / shape at another position
    "image_moved": YELLOW,
    "shapes_moved": YELLOW,
    "image_removed": RED,       # missed content
    "image_added": RED,
    "shapes_removed": RED,
    "shapes_added": RED,
    "image_changed": BLUE,      # image replaced
    "format_changed": ORANGE,   # same text, different font / size / bold / italic / colour
}


#MARK: Helpers
def normalise_char(c):
    if not c or c in ZERO_WIDTH:
        return ""
    if 0xFB00 <= ord(c) <= 0xFB06:
        return unicodedata.normalize("NFKC", c)
    return c


def normalise_text(text):
    return "".join(normalise_char(c) for c in text)


def canon(text):
    return LIG_RE.sub("", text)


def canon_map(text):
    chars, idx = [], []
    pos = 0

    for m in LIG_RE.finditer(text):
        for k in range(pos, m.start()):
            chars.append(text[k])
            idx.append(k)
        pos = m.end()

    for k in range(pos, len(text)):
        chars.append(text[k])
        idx.append(k)

    return "".join(chars), idx


def rect_union(rects):
    return (
        min(r[0] for r in rects),
        min(r[1] for r in rects),
        max(r[2] for r in rects),
        max(r[3] for r in rects),
    )


def merge_rects(rects, gap=6.0):
    merged = []

    for r in rects:
        r = tuple(r)

        for k, m in enumerate(merged):
            h = min(m[3] - m[1], r[3] - r[1])
            v = min(m[3], r[3]) - max(m[1], r[1])

            if (
                h > 0
                and v >= 0.5 * h
                and r[0] - m[2] <= gap
                and m[0] - r[2] <= gap
            ):
                merged[k] = (
                    min(m[0], r[0]),
                    min(m[1], r[1]),
                    max(m[2], r[2]),
                    max(m[3], r[3]),
                )
                break
        else:
            merged.append(r)

    return merged


def iou(a, b):
    ix = max(0.0, min(a[2], b[2]) - max(a[0], b[0]))
    iy = max(0.0, min(a[3], b[3]) - max(a[1], b[1]))
    inter = ix * iy
    area_a = max(0.0, (a[2] - a[0]) * (a[3] - a[1]))
    area_b = max(0.0, (b[2] - b[0]) * (b[3] - b[1]))
    union = area_a + area_b - inter
    return inter / union if union > 0 else 0.0


def run_groups(items, same=None):
    if same is None:
        def same(k1, k2):
            return k1 == k2

    groups = []
    cur = None

    for key, payload in items:
        if key is None:
            cur = None
            continue

        if cur is not None and same(cur["key"], key):
            cur["items"].append(payload)
        else:
            cur = {"key": key, "items": [payload]}
            groups.append(cur)

    return groups


def page_rotation(page):
    # matrix from unrotated page space to the page as displayed (None if /Rotate is 0)
    return page.rotation_matrix if page.rotation else None


def rotate_rect(r, rm):
    # map a rectangle from unrotated page space to displayed space
    if rm is None:
        return tuple(r)

    q = fitz.Rect(r) * rm
    q.normalize()
    return (q.x0, q.y0, q.x1, q.y1)


def make_finding(ftype, detail="", rects_a=None, rects_b=None):
    def clean(rects):
        return [
            tuple(round(float(v), 2) for v in r)
            for r in (rects or [])
        ][:MAX_RECTS]

    return {
        "type": ftype,
        "detail": detail,
        "rects_a": clean(rects_a),
        "rects_b": clean(rects_b),
    }


#MARK: Text style helpers
def _font_base(name):
    
    name = (name or "").split("+")[-1]
    name = re.sub(r"(bold|italic|oblique|regular)", "", name, flags=re.I)
    name = re.sub(r"[-,_ ]+", "", name)
    return name.lower()


def _is_bold(style):
    return bool(style[2] & FLAG_BOLD) or "bold" in (style[0] or "").lower()


def _is_italic(style):
    low = (style[0] or "").lower()
    return bool(style[2] & FLAG_ITALIC) or "italic" in low or "oblique" in low


def _rgb(c):
    c = int(c or 0)
    return ((c >> 16) & 255, (c >> 8) & 255, c & 255)


def _hex(c):
    return "#%02x%02x%02x" % _rgb(c)


def style_difference(sa, sb):
    diffs = []

    if _font_base(sa[0]) != _font_base(sb[0]):
        diffs.append(
            f"font {sa[0].split('+')[-1] or '?'} → {sb[0].split('+')[-1] or '?'}"
        )

    ba, bb = _is_bold(sa), _is_bold(sb)

    if ba != bb:
        diffs.append("bold → regular" if ba else "regular → bold")

    ia, ib = _is_italic(sa), _is_italic(sb)

    if ia != ib:
        diffs.append("italic → upright" if ia else "upright → italic")

    if abs(sa[1] - sb[1]) > FONT_SIZE_TOL:
        diffs.append(f"size {sa[1]:g} → {sb[1]:g} pt")

    if max(abs(x - y) for x, y in zip(_rgb(sa[3]), _rgb(sb[3]))) > COLOR_TOL:
        diffs.append(f"colour {_hex(sa[3])} → {_hex(sb[3])}")

    return tuple(diffs) or None


#MARK: Extraction
def _make_word(chars, boxes, styles, direction):
    return {
        "text": "".join(chars),
        "chars": list(boxes),          # one bbox per character of "text"
        "bbox": rect_union(boxes),
        "dir": direction,              # reading direction as displayed, e.g. (1, 0)
        "style": max(set(styles), key=styles.count),
        "eol": False,
    }


def _merge_hyphenated(words):
    
    out = []
    i = 0

    while i < len(words):
        w = words[i]

        if (
            w["eol"]
            and len(w["text"]) > 1
            and w["text"].endswith("-")
            and i + 1 < len(words)
            and words[i + 1]["text"][:1].islower()
        ):
            nxt = words[i + 1]
            merged = dict(w)
            merged["text"] = w["text"][:-1] + nxt["text"]
            merged["chars"] = w["chars"][:-1] + nxt["chars"]
            merged["eol"] = nxt["eol"]
            merged["style"] = w["style"]
            out.append(merged)
            i += 2
        else:
            out.append(w)
            i += 1

    return out


def extract_words(page, sort_reading_order=False):
    lines_out = []

    # coordinates and directions are converted to the page as displayed, so
    # /Rotate pages (landscape tables) line up with the rendered image
    rm = page_rotation(page)

    data = page.get_text("rawdict")

    for block in data["blocks"]:
        if block.get("type") != 0:
            continue

        for line in block["lines"]:
            dx, dy = line["dir"]

            if rm is not None:
                dx, dy = dx * rm.a + dy * rm.c, dx * rm.b + dy * rm.d

            if abs(dx) > 0.01 and abs(dy) > 0.01:
                continue

            direction = (round(dx), round(dy))

            line_words = []
            cur_t, cur_b, cur_s = [], [], []

            for span in line["spans"]:
                style = (
                    span.get("font", "") or "",
                    round(float(span.get("size", 0) or 0), 1),
                    int(span.get("flags", 0) or 0) & (FLAG_BOLD | FLAG_ITALIC),
                    int(span.get("color", 0) or 0),
                )

                for ch in span["chars"]:
                    c = normalise_char(ch["c"])

                    if c == "":
                        continue

                    if c.isspace():
                        if cur_t:
                            line_words.append(
                                _make_word(cur_t, cur_b, cur_s, direction)
                            )
                            cur_t, cur_b, cur_s = [], [], []
                        continue

                    box = rotate_rect(ch["bbox"], rm)

                    for piece in c:   # ligatures expand to several characters
                        cur_t.append(piece)
                        cur_b.append(box)
                        cur_s.append(style)

            if cur_t:
                line_words.append(_make_word(cur_t, cur_b, cur_s, direction))

            if line_words:
                line_words[-1]["eol"] = True
                lb = rotate_rect(line["bbox"], rm)
                lines_out.append((
                    (round(((lb[1] + lb[3]) / 2) / 4.0), lb[0]),
                    line_words
                ))

    if sort_reading_order:
        lines_out.sort(key=lambda item: item[0])

    words = []

    for _, line_words in lines_out:
        words.extend(line_words)

    return _merge_hyphenated(words)


def comparable_words(words):
    out = []

    for w in words:
        key = canon(w["text"])

        if key:
            w["key"] = key
            out.append(w)

    return out


def extract_images(page):
    out = []
    rm = page_rotation(page)

    try:
        infos = page.get_image_info(hashes=True)
    except Exception:
        return out

    for im in infos:
        bbox = rotate_rect(im["bbox"], rm)

        if bbox[2] - bbox[0] < 2 or bbox[3] - bbox[1] < 2:
            continue

        digest = im.get("digest") or b""

        if isinstance(digest, (bytes, bytearray)):
            digest = digest.hex()

        out.append({"bbox": bbox, "digest": str(digest)})

    return out


def _round_color(c):
    if c is None:
        return None
    return tuple(round(float(v), 2) for v in c)


def extract_drawings(page):
    out = []
    rm = page_rotation(page)

    try:
        items = page.get_drawings()
    except Exception:
        return out

    for d in items:
        r = d.get("rect")

        if r is None:
            continue

        out.append({
            "rect": rotate_rect((r.x0, r.y0, r.x1, r.y1), rm),
            "style": (
                _round_color(d.get("color")),
                _round_color(d.get("fill")),
                round(float(d.get("width") or 0), 1),
                d.get("type"),
            ),
        })

    return out


def render_page(page, dpi):
    pix = page.get_pixmap(dpi=dpi, alpha=False)

    img = np.frombuffer(
        pix.samples,
        dtype=np.uint8
    ).reshape(pix.h, pix.w, pix.n)

    if pix.n == 4:
        return cv2.cvtColor(img, cv2.COLOR_RGBA2BGR)

    return cv2.cvtColor(img, cv2.COLOR_RGB2BGR)


#MARK: Word diff (text differences + the matching that the structure check needs)
def _boundary_rect(word, i):
    
    chars = word["chars"]
    dx, dy = word.get("dir", (1, 0))
    bb = word["bbox"]
    n = len(chars)

    if abs(dx) >= abs(dy):                      # horizontal text
        if dx >= 0:
            x = chars[i][0] if i < n else chars[-1][2]
        else:
            x = chars[i][2] if i < n else chars[-1][0]
        return (x - 0.6, bb[1], x + 0.6, bb[3])

    if dy >= 0:                                 # vertical text, reads downwards
        y = chars[i][1] if i < n else chars[-1][3]
    else:                                       # vertical text, reads upwards
        y = chars[i][3] if i < n else chars[-1][1]

    return (bb[0], y - 0.6, bb[2], y + 0.6)


def _char_level_rects(a, b):
    """
    Rectangles of only the changed characters of two similar words.
    Ligature sequences are removed before diffing, so they never produce a
    highlight, but rectangles are taken from the original glyph boxes.
    """
    ca, ma = canon_map(a["text"])
    cb, mb = canon_map(b["text"])

    sm = difflib.SequenceMatcher(None, ca, cb, autojunk=False)

    rects_a, rects_b = [], []

    for op, i0, i1, j0, j1 in sm.get_opcodes():
        if op == "equal":
            continue

        if i1 > i0:
            rects_a.append(rect_union([a["chars"][ma[k]] for k in range(i0, i1)]))
        else:
            pos = ma[i0] if i0 < len(ma) else len(a["chars"])
            rects_a.append(_boundary_rect(a, pos))

        if j1 > j0:
            rects_b.append(rect_union([b["chars"][mb[k]] for k in range(j0, j1)]))
        else:
            pos = mb[j0] if j0 < len(mb) else len(b["chars"])
            rects_b.append(_boundary_rect(b, pos))

    return rects_a, rects_b


def _pair_words(ia, ib, ta, tb):
    """
    Pair up words inside a replaced block.
    Returns ordered ops: ("pair", i, j, ratio) / ("del", i) / ("ins", j).
    """
    n, m = len(ia), len(ib)

    def ratio(x, y):
        return difflib.SequenceMatcher(None, x, y).ratio()

    if n == m:
        return [
            ("pair", i, j, ratio(ta[i], tb[j]))
            for i, j in zip(ia, ib)
        ]

    if n == 0 or m == 0 or n * m > PAIRING_LIMIT:
        return (
            [("del", i) for i in ia]
            + [("ins", j) for j in ib]
        )

    ops = []
    jp = 0

    for i in ia:
        best_k = -1
        best = PAIR_MIN_RATIO - 1e-6

        for k in range(jp, m):
            r = ratio(ta[i], tb[ib[k]])

            if r > best:
                best = r
                best_k = k

        if best_k == -1:
            ops.append(("del", i))
        else:
            ops.extend(("ins", ib[x]) for x in range(jp, best_k))
            ops.append(("pair", i, ib[best_k], best))
            jp = best_k + 1

    ops.extend(("ins", ib[x]) for x in range(jp, m))

    return ops


def diff_words(wa, wb):
    """
    Returns (text_findings, equal_pairs, moved_findings, changed_a, changed_b).

      text_findings   {"rects_a": [...], "rects_b": [...]} real text differences
      equal_pairs     (i, j) of words that are the same in both files
      moved_findings  same text found at a different place ("shifted content")
      changed_a/b     indices of words involved in a text difference
    """
    ta = [w["key"] for w in wa]
    tb = [w["key"] for w in wb]

    sm = difflib.SequenceMatcher(None, ta, tb, autojunk=False)
    opcodes = sm.get_opcodes()

    # moved text: a pure delete and a pure insert with identical words
    ins_by_text = {}

    for op, a0, a1, b0, b1 in opcodes:
        if op == "insert":
            key = " ".join(tb[b0:b1])
            ins_by_text.setdefault(key, []).append((b0, b1))

    moved_a, moved_b = {}, {}

    for op, a0, a1, b0, b1 in opcodes:
        if op != "delete":
            continue

        key = " ".join(ta[a0:a1])

        if len(key) >= 3 and ins_by_text.get(key):
            seg_b = ins_by_text[key].pop(0)
            moved_a[(a0, a1)] = seg_b
            moved_b[seg_b] = (a0, a1)

    moved_findings = []

    for (a0, a1), (b0, b1) in moved_a.items():
        a_first = wa[a0]["bbox"]
        b_first = wb[b0]["bbox"]

        moved_findings.append(make_finding(
            "shifted",
            (
                f"Same text moved: dx={b_first[0] - a_first[0]:+.1f} pt, "
                f"dy={b_first[1] - a_first[1]:+.1f} pt"
            ),
            merge_rects([wa[i]["bbox"] for i in range(a0, a1)]),
            merge_rects([wb[j]["bbox"] for j in range(b0, b1)]),
        ))

    equal_pairs = []
    findings = []
    changed_a, changed_b = set(), set()
    pend_d, pend_i = [], []

    def flush():
        if not pend_d and not pend_i:
            return

        findings.append({
            "rects_a": merge_rects([wa[i]["bbox"] for i in pend_d]),
            "rects_b": merge_rects([wb[j]["bbox"] for j in pend_i]),
        })

        changed_a.update(pend_d)
        changed_b.update(pend_i)
        pend_d.clear()
        pend_i.clear()

    for op, a0, a1, b0, b1 in opcodes:

        if op == "equal":
            equal_pairs.extend(zip(range(a0, a1), range(b0, b1)))
            continue

        if op == "delete" and (a0, a1) in moved_a:
            continue

        if op == "insert" and (b0, b1) in moved_b:
            continue

        ia = list(range(a0, a1))
        ib = list(range(b0, b1))

        if op == "replace":
            ops = _pair_words(ia, ib, ta, tb)
        elif op == "delete":
            ops = [("del", i) for i in ia]
        else:
            ops = [("ins", j) for j in ib]

        for o in ops:
            if o[0] == "del":
                pend_d.append(o[1])

            elif o[0] == "ins":
                pend_i.append(o[1])

            else:
                _, i, j, r = o

                if r >= PAIR_MIN_RATIO:
                    flush()

                    rects_a, rects_b = _char_level_rects(wa[i], wb[j])

                    if rects_a or rects_b:
                        findings.append({"rects_a": rects_a, "rects_b": rects_b})

                    changed_a.add(i)
                    changed_b.add(j)
                else:
                    pend_d.append(i)
                    pend_i.append(j)

        flush()

    return findings, equal_pairs, moved_findings, changed_a, changed_b


#MARK: Visual comparison
def compare_images(ia, ib, tol, same_size):
    findings = []

    by_digest = {}

    for j, im in enumerate(ib):
        by_digest.setdefault(im["digest"], []).append(j)

    rem_a = []

    for i, im in enumerate(ia):
        bucket = by_digest.get(im["digest"]) if im["digest"] else None

        if bucket:
            j = bucket.pop(0)

            if same_size:
                diff = max(abs(x - y) for x, y in zip(im["bbox"], ib[j]["bbox"]))

                if diff > tol:
                    findings.append(make_finding(
                        "image_moved",
                        f"Image moved / resized (max edge change {diff:.1f} pt)",
                        [im["bbox"]],
                        [ib[j]["bbox"]],
                    ))
        else:
            rem_a.append(i)

    rem_b = sorted(j for lst in by_digest.values() for j in lst)

    # same place, different content -> replaced
    for i in rem_a[:]:
        best_j, best_iou = None, 0.5

        for j in rem_b:
            v = iou(ia[i]["bbox"], ib[j]["bbox"])

            if v > best_iou:
                best_j, best_iou = j, v

        if best_j is not None:
            rem_a.remove(i)
            rem_b.remove(best_j)

            findings.append(make_finding(
                "image_changed",
                "Image replaced",
                [ia[i]["bbox"]],
                [ib[best_j]["bbox"]],
            ))

    for i in rem_a:
        findings.append(make_finding(
            "image_removed", "Image missing in second PDF", [ia[i]["bbox"]], []
        ))

    for j in rem_b:
        findings.append(make_finding(
            "image_added", "Image missing in first PDF", [], [ib[j]["bbox"]]
        ))

    return findings


def compare_drawings(da, db, tol):
    
    if len(da) + len(db) > MAX_DRAWINGS:
        return [make_finding(
            "shapes_skipped",
            (
                f"{len(da)} vs {len(db)} vector objects: too many to compare "
                "one by one, lines / shapes were not checked on this page."
            ),
        )]

    buckets = {}

    for i, d in enumerate(da):
        buckets.setdefault(d["style"], ([], []))[0].append(i)

    for j, d in enumerate(db):
        buckets.setdefault(d["style"], ([], []))[1].append(j)

    moved_a, moved_b, removed, added = [], [], [], []

    for style, (ia, ib) in buckets.items():
        free_b = list(ib)
        left_a = []

        for i in ia:
            ra = da[i]["rect"]
            hit = None

            for j in free_b:
                rb = db[j]["rect"]

                if max(abs(x - y) for x, y in zip(ra, rb)) <= tol:
                    hit = j
                    break

            if hit is None:
                left_a.append(i)
            else:
                free_b.remove(hit)

        for i in left_a:
            ra = da[i]["rect"]
            cax, cay = (ra[0] + ra[2]) / 2, (ra[1] + ra[3]) / 2

            best, best_d = None, MOVE_RADIUS

            for j in free_b:
                rb = db[j]["rect"]
                cbx, cby = (rb[0] + rb[2]) / 2, (rb[1] + rb[3]) / 2
                dist = ((cax - cbx) ** 2 + (cay - cby) ** 2) ** 0.5

                if dist <= best_d:
                    best, best_d = j, dist

            if best is None:
                removed.append(i)
            else:
                free_b.remove(best)
                moved_a.append(i)
                moved_b.append(best)

        added.extend(free_b)

    findings = []

    if moved_a:
        findings.append(make_finding(
            "shapes_moved",
            f"{len(moved_a)} line(s) / shape(s) shifted or resized",
            [da[i]["rect"] for i in moved_a],
            [db[j]["rect"] for j in moved_b],
        ))

    if removed:
        findings.append(make_finding(
            "shapes_removed",
            f"{len(removed)} line(s) / shape(s) missing in second PDF",
            [da[i]["rect"] for i in removed],
            [],
        ))

    if added:
        findings.append(make_finding(
            "shapes_added",
            f"{len(added)} line(s) / shape(s) missing in first PDF",
            [],
            [db[j]["rect"] for j in added],
        ))

    return findings


def structure_findings(data_a, data_b, wa, wb, equal_pairs, changed_a, changed_b, cfg):
    tol = cfg["pos_tol"]

    findings = []

    wa_pt, ha_pt = data_a["size"]
    wb_pt, hb_pt = data_b["size"]

    same_size = abs(wa_pt - wb_pt) <= tol and abs(ha_pt - hb_pt) <= tol

    if not same_size:
        findings.append(make_finding(
            "page_size",
            (
                f"Page size {wa_pt:.0f}×{ha_pt:.0f} pt → "
                f"{wb_pt:.0f}×{hb_pt:.0f} pt (position checks skipped)"
            ),
        ))

    # Lines that contain a text edit: later words on that line shift
    # horizontally just because the text got longer / shorter. Not a layout change.
    def yc(w):
        return (w["bbox"][1] + w["bbox"][3]) / 2

    ys_a = [yc(wa[i]) for i in changed_a]
    ys_b = [yc(wb[j]) for j in changed_b]

    def near(y, ys):
        return any(abs(y - v) <= 3.0 for v in ys)

    shift_items = []
    style_items = []

    for i, j in equal_pairs:
        a, b = wa[i], wb[j]

        # ---- formatting (font / bold / italic / size / colour) ----
        diff = style_difference(a["style"], b["style"])
        style_items.append((diff, (i, j)))

        # ---- position ----
        key = None

        # A word whose formatting changed is reported as "format changed";
        # its shift (wider glyphs, different wrapping) is only a side effect.
        if same_size and diff is None:
            dx = b["bbox"][0] - a["bbox"][0]
            dy = b["bbox"][1] - a["bbox"][1]

            if abs(dx) > tol or abs(dy) > tol:
                edited_line = (
                    abs(dy) <= tol
                    and (near(yc(a), ys_a) or near(yc(b), ys_b))
                )

                if not edited_line:
                    key = (dx, dy)

        shift_items.append((key, (i, j)))

    # formatting changes (consecutive words with the same difference are one box)
    for g in run_groups(style_items):
        pairs = g["items"]

        findings.append(make_finding(
            "format_changed",
            "Formatting changed: " + ", ".join(g["key"]) + f" ({len(pairs)} word(s))",
            merge_rects([wa[i]["bbox"] for i, _ in pairs]),
            merge_rects([wb[j]["bbox"] for _, j in pairs]),
        ))

    # shifted text (grouped by similar offset)
    for g in run_groups(
        shift_items,
        same=lambda k1, k2: abs(k1[0] - k2[0]) <= tol and abs(k1[1] - k2[1]) <= tol
    ):
        pairs = g["items"]
        dx, dy = g["key"]

        findings.append(make_finding(
            "shifted",
            f"Shifted dx={dx:+.1f} pt, dy={dy:+.1f} pt ({len(pairs)} word(s))",
            merge_rects([wa[i]["bbox"] for i, _ in pairs]),
            merge_rects([wb[j]["bbox"] for _, j in pairs]),
        ))

    findings.extend(compare_images(
        data_a["images"], data_b["images"], tol, same_size
    ))

    if same_size:
        findings.extend(compare_drawings(
            data_a["drawings"], data_b["drawings"], tol
        ))

    return findings


#MARK: Page alignment
def page_tokens(page):
    tokens = [
        t for t in (canon(t) for t in normalise_text(page.get_text("text")).split())
        if t
    ]

    if not tokens:
        # scanned / image-only page: use image hashes as the signature
        try:
            for im in page.get_image_info(hashes=True):
                d = im.get("digest") or b""
                tokens.append("IMG:" + (d.hex() if isinstance(d, (bytes, bytearray)) else str(d)))
        except Exception:
            pass

    return tokens


def pair_pages(ia, ib, toks_a, toks_b):
    n, m = len(ia), len(ib)

    if n == m:
        return list(zip(ia, ib))

    pairs = []
    jp = 0

    for i in ia:
        best_k, best = -1, PAIR_MIN_RATIO - 1e-6

        for k in range(jp, m):
            r = difflib.SequenceMatcher(
                None, toks_a[i], toks_b[ib[k]], autojunk=False
            ).ratio()

            if r > best:
                best, best_k = r, k

        if best_k == -1:
            pairs.append((i, None))
        else:
            pairs.extend((None, ib[x]) for x in range(jp, best_k))
            pairs.append((i, ib[best_k]))
            jp = best_k + 1

    pairs.extend((None, ib[x]) for x in range(jp, m))

    return pairs


def align_pages(path_a, path_b):
    
    doc_a = fitz.open(path_a)
    doc_b = fitz.open(path_b)

    try:
        toks_a = [page_tokens(p) for p in doc_a]
        toks_b = [page_tokens(p) for p in doc_b]
    finally:
        doc_a.close()
        doc_b.close()

    def key(tokens):
        return hashlib.md5(" ".join(tokens).encode("utf-8")).hexdigest()

    keys_a = [key(t) for t in toks_a]
    keys_b = [key(t) for t in toks_b]

    sm = difflib.SequenceMatcher(None, keys_a, keys_b, autojunk=False)

    pairs = []

    for op, a0, a1, b0, b1 in sm.get_opcodes():
        if op == "equal":
            pairs.extend(zip(range(a0, a1), range(b0, b1)))
        elif op == "delete":
            pairs.extend((i, None) for i in range(a0, a1))
        elif op == "insert":
            pairs.extend((None, j) for j in range(b0, b1))
        else:
            pairs.extend(pair_pages(
                list(range(a0, a1)),
                list(range(b0, b1)),
                toks_a,
                toks_b
            ))

    return pairs


#MARK: Overlay
def build_overlay(img_a, img_b, findings, scale, color_for):
    ha, wa = img_a.shape[:2]
    hb, wb = img_b.shape[:2]

    canvas = np.full((max(ha, hb), wa + wb, 3), 255, np.uint8)
    canvas[:ha, :wa] = img_a
    canvas[:hb, wa:] = img_b

    cv2.line(canvas, (wa, 0), (wa, canvas.shape[0]), (120, 120, 120), 2)

    pad = 2
    boxes = []

    def to_px(r, x_off):
        x0 = int(r[0] * scale) - pad
        y0 = int(r[1] * scale) - pad
        x1 = int(r[2] * scale) + pad
        y1 = int(r[3] * scale) + pad

        # make thin lines / zero-height shapes visible
        if x1 - x0 < MIN_HIGHLIGHT_PX:
            cx = (x0 + x1) // 2
            x0, x1 = cx - MIN_HIGHLIGHT_PX // 2, cx + MIN_HIGHLIGHT_PX // 2

        if y1 - y0 < MIN_HIGHLIGHT_PX:
            cy = (y0 + y1) // 2
            y0, y1 = cy - MIN_HIGHLIGHT_PX // 2, cy + MIN_HIGHLIGHT_PX // 2

        return (max(0, x0) + x_off, max(0, y0), x1 + x_off, y1)

    for f in findings:
        col_a, col_b = color_for(f)

        # images get a solid frame in the same colour, so the colour stays
        # recognisable even on top of a coloured picture
        frame = f.get("type", "").startswith("image_")

        for r in f["rects_a"]:
            boxes.append((to_px(r, 0), col_a, frame))

        for r in f["rects_b"]:
            boxes.append((to_px(r, wa), col_b, frame))

    if not boxes:
        return canvas

    tint = canvas.copy()

    for (x0, y0, x1, y1), color, _ in boxes:
        cv2.rectangle(tint, (x0, y0), (x1, y1), color, -1)

    canvas = cv2.addWeighted(tint, HIGHLIGHT_ALPHA, canvas, 1.0 - HIGHLIGHT_ALPHA, 0)

    for (x0, y0, x1, y1), color, frame in boxes:
        if frame:
            cv2.rectangle(canvas, (x0, y0), (x1, y1), color, 4)

    return canvas


def text_colors(_finding):
    return RED, GREEN


def struct_colors(finding):
    color = STRUCT_COLORS.get(finding["type"], YELLOW)
    return color, color


#MARK: Per page worker
def _compare_page_pair(pa, pb, path_a, path_b, cfg):
    img_dir = Path(cfg["output_dir"]) / "Images"
    img_dir.mkdir(parents=True, exist_ok=True)

    dpi = cfg["dpi"]
    scale = dpi / 72.0

    label = f"{pa + 1 if pa is not None else 'x'}_{pb + 1 if pb is not None else 'x'}"

    result = {
        "page_a": pa,
        "page_b": pb,
        "text_image": None,
        "struct_image": None,
        "text_count": 0,
        "struct_count": 0,
        "notes": [],
        "error": None,
    }

    doc_a = fitz.open(path_a)
    doc_b = fitz.open(path_b)

    try:
        if pa is None or pb is None:
            doc = doc_b if pa is None else doc_a
            page = doc.load_page(pb if pa is None else pa)

            r = page.rect
            full = [(r.x0, r.y0, r.x1, r.y1)]

            img = render_page(page, dpi)
            blank = np.full_like(img, 255)

            if pa is None:
                findings = [{"rects_a": [], "rects_b": full}]
                canvas = build_overlay(blank, img, findings, scale, text_colors)
            else:
                findings = [{"rects_a": full, "rects_b": []}]
                canvas = build_overlay(img, blank, findings, scale, text_colors)

            path = img_dir / f"text_page_{label}.png"
            cv2.imwrite(str(path), canvas)

            result["text_image"] = f"Images/{path.name}"
            result["text_count"] = 1
            return result

        # ---- normal page pair ----
        page_a = doc_a.load_page(pa)
        page_b = doc_b.load_page(pb)

        data_a = {
            "size": (page_a.rect.width, page_a.rect.height),
            "images": extract_images(page_a),
            "drawings": extract_drawings(page_a),
        }

        data_b = {
            "size": (page_b.rect.width, page_b.rect.height),
            "images": extract_images(page_b),
            "drawings": extract_drawings(page_b),
        }

        wa = comparable_words(extract_words(page_a, cfg["sort_reading_order"]))
        wb = comparable_words(extract_words(page_b, cfg["sort_reading_order"]))

        text_found, equal_pairs, moved_found, ch_a, ch_b = diff_words(wa, wb)

        struct_found = moved_found + structure_findings(
            data_a, data_b, wa, wb, equal_pairs, ch_a, ch_b, cfg
        )

        if not text_found and not struct_found:
            return result

        img_a = render_page(page_a, dpi)
        img_b = render_page(page_b, dpi)

        if text_found:
            canvas = build_overlay(img_a, img_b, text_found, scale, text_colors)
            path = img_dir / f"text_page_{label}.png"
            cv2.imwrite(str(path), canvas)

            result["text_image"] = f"Images/{path.name}"
            result["text_count"] = len(text_found)

        if struct_found:
            canvas = build_overlay(img_a, img_b, struct_found, scale, struct_colors)
            path = img_dir / f"structure_page_{label}.png"
            cv2.imwrite(str(path), canvas)

            result["struct_image"] = f"Images/{path.name}"
            result["struct_count"] = len(struct_found)

            # formatting changes are listed as notes (what changed, e.g. regular → bold);
            # differences that cannot be highlighted are shown as a note as well
            # notes = []

            # for f in struct_found:
            #     if not f["detail"]:
            #         continue

            #     if f["type"] == "format_changed" or (
            #         not f["rects_a"] and not f["rects_b"]
            #     ):
            #         if f["detail"] not in notes:
            #             notes.append(f["detail"])

            # result["notes"] = notes

        return result

    finally:
        doc_a.close()
        doc_b.close()


def compare_page_pair(pair, path_a, path_b, cfg):
    pa, pb = pair

    try:
        return _compare_page_pair(pa, pb, path_a, path_b, cfg)
    except Exception as exc:
        logging.exception("Failed to compare page pair %s", pair)

        raise


def page_title(r):
    pa, pb = r["page_a"], r["page_b"]

    if pa is None:
        return f"Page {pb + 1} (only in second PDF)"

    if pb is None:
        return f"Page {pa + 1} (only in first PDF)"

    if pa == pb:
        return f"Page {pa + 1}"

    return f"Page {pa + 1} (first PDF) ↔ page {pb + 1} (second PDF)"


#MARK: Comparator
class PDFComparator:

    def __init__(
        self,
        dpi=150,
        output_dir="mismatched_pages",
        status_file=None,
        pos_tolerance=2.0,         # pt: shifts up to this are ignored
        sort_reading_order=False   # sort lines by position if content-stream order differs
    ):
        self.dpi = dpi
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.status_file = Path(status_file) if status_file else None
        self.results = []

        self.cfg = {
            "dpi": dpi,
            "output_dir": str(self.output_dir),
            "pos_tol": pos_tolerance,
            "sort_reading_order": sort_reading_order,
        }

    def update_status(self, **data):
        if self.status_file:
            write_status(self.status_file, **data)

    def compare(self, path_a, path_b):

        pairs = align_pages(path_a, path_b)
        page_count = len(pairs)

        self.update_status(
            status="processing",
            progress=0,
            current_page=0,
            total_pages=page_count,
            message="Starting PDF comparison..."
        )

        self.results = []

        if page_count == 0:
            return

        completed = 0

        max_workers = min(
            os.cpu_count() or 2,
            page_count,
            4
        )

        with ProcessPoolExecutor(
            max_workers=max_workers
        ) as executor:

            futures = [
                executor.submit(
                    compare_page_pair, pair, path_a, path_b, self.cfg
                )
                for pair in pairs
            ]

            for future in as_completed(futures):

                result = future.result()

                for key in ("text_image", "struct_image"):
                    if result[key]:
                        logging.info(str(self.output_dir / result[key]))

                self.results.append(result)

                completed += 1

                progress = int(
                    completed / page_count * 100
                )

                self.update_status(
                    status="processing",
                    progress=progress,
                    current_page=completed,
                    total_pages=page_count,
                    message=(
                        f"Comparing page "
                        f"{completed} of "
                        f"{page_count}"
                    )
                )

        self.results.sort(key=lambda r: (
            r["page_a"] if r["page_a"] is not None else r["page_b"] + 0.5
        ))

    def generate_html_gallery(
        self,
        pdf_path_a,
        pdf_path_b
    ):
        html_file = (
            self.output_dir
            / "compare_report.html"
        )

        mismatched = [
            r for r in self.results
            if r["text_image"] or r["struct_image"]
        ]

        failed = [r for r in self.results if r["error"]]

        text_pages = sum(1 for r in mismatched if r["text_image"])
        struct_pages = sum(1 for r in mismatched if r["struct_image"])

        name_a = html.escape(Path(pdf_path_a).name)
        name_b = html.escape(Path(pdf_path_b).name)

        if not mismatched and not failed:

            result_message = """
            <div class="success">
                ✓ No differences detected.
                The compared pages are identical based
                on the configured comparison settings.
            </div>
            """

        else:

            result_message = f"""
            <div class="warning">
                ⚠ {len(mismatched)}
                mismatched page(s) detected.
            </div>
            """

        for r in failed:
            result_message += (
                '<div class="warning">'
                f"{html.escape(page_title(r))} could not be compared: "
                f"{html.escape(r['error'])}"
                "</div>"
            )

        html_content = f"""
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<title>PDF Comparison Report</title>

<style>

body {{
    background: #fff;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    color: #222;
}}

header {{
    text-align: center;
    padding: 10px 20px 25px;
}}

.summary {{
    display: inline-block;
    padding: 12px 20px;
    border-radius: 8px;
    background: #f5f5f5;
    margin: 4px;
}}

.success {{
    margin: 15px auto;
    padding: 14px 20px;
    max-width: 900px;
    border-radius: 7px;
    background: #e8f7ed;
    color: #18733b;
    font-weight: bold;
}}

.warning {{
    margin: 15px auto;
    padding: 14px 20px;
    max-width: 900px;
    border-radius: 7px;
    background: #fff4dc;
    color: #875900;
    font-weight: bold;
}}

.gallery {{
    display: flex;
    flex-direction: column;
    width: 95%;
    margin: auto;
    gap: 50px;
    padding-bottom: 50px;
}}

.container {{
    text-align: center;
    color: #000;
    width: 100%;
}}

.container h3 {{
    border-bottom: 2px solid #ddd;
    padding-bottom: 6px;
}}

.container h4 {{
    margin: 18px 0 8px;
    color: #444;
}}

.container img {{
    width: 100%;
    height: auto;
    border: 2px solid #ccc;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}}

.note {{
    color: #875900;
    font-size: 14px;
    margin: 4px 0;
}}

.legend {{
    color: #444;
    font-size: 14px;
    line-height: 2;
}}

</style>
</head>

<body>

<header>

<h1>PDF Comparison Report</h1>

<p>
<strong>{name_a}</strong>
vs
<strong>{name_b}</strong>
</p>

<div class="summary">
Mismatched Pages:
<strong>{len(mismatched)}</strong>
</div>

<div class="summary">
Pages With Text Differences:
<strong>{text_pages}</strong>
</div>

<div class="summary">
Pages With Structural Differences:
<strong>{struct_pages}</strong>
</div>

{result_message}

<div class="legend">

<strong>Text differences:</strong>

<span style="
background-color:rgba(255,0,0,0.3);
padding:2px 5px;
font-weight:bold;">
Red
</span>:
Removed / Changed Text (Left)

|

<span style="
background-color:rgba(0,160,0,0.3);
padding:2px 5px;
font-weight:bold;">
Green
</span>:
Added / Changed Text (Right)

<br>

<strong>Structural differences:</strong>

<span style="
background-color:rgba(255,0,0,0.3);
padding:2px 5px;
font-weight:bold;">
Red
</span>:
Missed Content

|

<span style="
background-color:rgba(255,255,0,0.5);
padding:2px 5px;
font-weight:bold;">
Yellow
</span>:
Shifted Content

|

<span style="
background-color:rgba(255,165,0,0.4);
padding:2px 5px;
font-weight:bold;">
Orange
</span>:
Formatting Changed

|

<span style="
background-color:rgba(0,0,255,0.3);
padding:2px 5px;
font-weight:bold;">
Blue
</span>:
Image Replaced

<br>

Ligature differences (Th, ff, fl, ffi, ffl, fi) are ignored.

</div>

</header>

<div class="gallery">
"""

        for r in mismatched:

            title = html.escape(page_title(r))

            html_content += f"""
<div class="container">

<h3>{title}</h3>
"""

            if r["text_image"]:
                html_content += f"""
<h4>Text Differences</h4>

<img
    src="{r['text_image']}"
    alt="Text differences on {title}"
>
"""

            if r["struct_image"]:

                notes = "".join(
                    f'<p class="note">{html.escape(n)}</p>'
                    for n in r["notes"]
                )

                html_content += f"""
<h4>Structural Differences</h4>

<img
    src="{r['struct_image']}"
    alt="Structural differences on {title}"
>
"""

            html_content += """
</div>
"""

        html_content += """
</div>
</body>
</html>
"""

        with open(
            html_file,
            "w",
            encoding="utf-8"
        ) as f:
            f.write(html_content)

        return html_file

#MARK: Main
def main(pdf_1, pdf_2, job_dir):

    job_dir = Path(job_dir)

    job_dir.mkdir(
        parents=True,
        exist_ok=True
    )

    status_file = (
        job_dir
        / "status.json"
    )

    comparator = PDFComparator(
        output_dir=job_dir,
        status_file=status_file
    )

    try:

        write_status(
            status_file,
            status="processing",
            progress=0,
            current_page=0,
            total_pages=0,
            message="Preparing PDF comparison..."
        )

        comparator.compare(
            pdf_1,
            pdf_2
        )

        write_status(
            status_file,
            status="processing",
            progress=95,
            current_page=0,
            total_pages=0,
            message="Generating HTML comparison report..."
        )

        report_file = (
            comparator.generate_html_gallery(
                pdf_1,
                pdf_2
            )
        )

        write_status(
            status_file,
            status="completed",
            progress=100,
            current_page=0,
            total_pages=0,
            message="PDF comparison completed.",
            report=str(report_file)
        )

        print(
            f"REPORT_PATH={report_file}",
            flush=True
        )

    except Exception as exc:

        logging.exception(
            "PDF comparison failed"
        )

        write_status(
            status_file,
            status="error",
            progress=0,
            current_page=0,
            total_pages=0,
            message=str(exc)
        )

        raise


if __name__ == "__main__":

    if len(sys.argv) < 4:

        print(
            "Usage: python pdf_compare.py "
            "<pdf_1> <pdf_2> <job_dir>"
        )

        sys.exit(1)

    main(
        sys.argv[1],
        sys.argv[2],
        sys.argv[3]
    )

import sys
import os
import json
import time
import logging
import difflib
from pathlib import Path
from concurrent.futures import ProcessPoolExecutor, as_completed
from functools import partial
from collections import Counter

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

    with open(temp_file, "w", encoding="utf-8") as f:
        json.dump(payload, f)

    os.replace(temp_file, status_file)


class PDFComparator:

    def __init__(
        self,
        dpi=300,
        threshold=30,
        output_dir="mismatched_pages",
        status_file=None
    ):
        self.dpi = dpi
        self.threshold = threshold
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.status_file = Path(status_file) if status_file else None

    def update_status(self, **data):
        if self.status_file:
            write_status(self.status_file, **data)

    def render_page(self, pdf_path, page_num):
        doc = fitz.open(pdf_path)

        try:
            page = doc.load_page(page_num)
            pix = page.get_pixmap(dpi=self.dpi, alpha=False)

            img = np.frombuffer(
                pix.samples,
                dtype=np.uint8
            ).reshape(pix.h, pix.w, pix.n)

            if pix.n == 4:
                return cv2.cvtColor(img, cv2.COLOR_RGBA2BGR)

            return cv2.cvtColor(img, cv2.COLOR_RGB2BGR)

        finally:
            doc.close()

    def get_structural_blocks(self, img):
        """Groups nearby visual elements into structural layout boxes."""
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        _, thresh = cv2.threshold(
            gray,
            240,
            255,
            cv2.THRESH_BINARY_INV
        )

        kernel = cv2.getStructuringElement(
            cv2.MORPH_RECT,
            (50, 20)
        )

        dilated = cv2.dilate(
            thresh,
            kernel,
            iterations=2
        )

        contours, _ = cv2.findContours(
            dilated,
            cv2.RETR_EXTERNAL,
            cv2.CHAIN_APPROX_SIMPLE
        )

        blocks = []

        for cnt in contours:
            x, y, w, h = cv2.boundingRect(cnt)

            if w > 40 and h > 20:
                blocks.append((x, y, w, h))

        return blocks

    def get_text_structures(self, pdf_path, page_num, img_shape):
        """Extract text spans and scale their coordinates to image space."""
        doc = fitz.open(pdf_path)

        try:
            page = doc.load_page(page_num)

            pdf_w = page.rect.width
            pdf_h = page.rect.height

            img_h, img_w, _ = img_shape

            scale_x = img_w / pdf_w
            scale_y = img_h / pdf_h

            text_instances = []

            blocks = page.get_text("dict")["blocks"]

            for block in blocks:
                if "lines" not in block:
                    continue

                for line in block["lines"]:
                    for span in line["spans"]:

                        text = span["text"].strip()

                        if not text:
                            continue

                        x0, y0, x1, y1 = span["bbox"]

                        ix0 = int(x0 * scale_x)
                        iy0 = int(y0 * scale_y)
                        ix1 = int(x1 * scale_x)
                        iy1 = int(y1 * scale_y)

                        text_instances.append({
                            "text": text,
                            "bbox": (
                                ix0,
                                iy0,
                                ix1 - ix0,
                                iy1 - iy0
                            )
                        })

            return text_instances

        finally:
            doc.close()

    def save_hybrid_diff(
        self,
        img_a,
        img_b,
        path_a,
        path_b,
        page_num
    ):
        h, w, c = img_a.shape

        # Base side-by-side display canvas.
        canvas = np.zeros(
            (h, w * 2, c),
            dtype=np.uint8
        )

        canvas[:, :w] = img_a.copy()
        canvas[:, w:] = img_b.copy()

        # Raw visual pixel difference.
        b1 = cv2.GaussianBlur(
            img_a,
            (5, 5),
            0
        )

        b2 = cv2.GaussianBlur(
            img_b,
            (5, 5),
            0
        )

        diff = cv2.absdiff(b1, b2)

        gray_diff = cv2.cvtColor(
            diff,
            cv2.COLOR_BGR2GRAY
        )

        _, raw_mask = cv2.threshold(
            gray_diff,
            self.threshold,
            255,
            cv2.THRESH_BINARY
        )

        mismatch_detected = bool(np.any(raw_mask))

        # Red / green background layers.
        kernel_blob = cv2.getStructuringElement(
            cv2.MORPH_RECT,
            (15, 5)
        )

        mask_dilated = cv2.dilate(
            raw_mask,
            kernel_blob,
            iterations=1
        )

        red_layer = np.zeros_like(canvas)

        red_layer[:, :w] = np.where(
            mask_dilated[:, :, None] == 255,
            (0, 0, 255),
            0
        )

        green_layer = np.zeros_like(canvas)

        green_layer[:, w:] = np.where(
            mask_dilated[:, :, None] == 255,
            (0, 255, 0),
            0
        )

        canvas = cv2.addWeighted(
            canvas,
            1.0,
            red_layer,
            0.15,
            0
        )

        canvas = cv2.addWeighted(
            canvas,
            1.0,
            green_layer,
            0.15,
            0
        )

        # Structural boxes.
        blocks_a = self.get_structural_blocks(img_a)
        blocks_b = self.get_structural_blocks(img_b)

        matched_b_indices = set()

        for xa, ya, wa, ha in blocks_a:

            crop_a = img_a[
                ya:ya + ha,
                xa:xa + wa
            ]

            best_match_idx = -1
            best_score = 0.0

            for idx, (xb, yb, wb, hb) in enumerate(blocks_b):

                if idx in matched_b_indices:
                    continue

                if abs(wa - wb) < 30 and abs(ha - hb) < 30:

                    crop_b_resized = cv2.resize(
                        img_b[
                            yb:yb + hb,
                            xb:xb + wb
                        ],
                        (wa, ha)
                    )

                    res = cv2.matchTemplate(
                        crop_a,
                        crop_b_resized,
                        cv2.TM_CCOEFF_NORMED
                    )

                    _, max_val, _, _ = cv2.minMaxLoc(res)

                    if max_val > best_score:
                        best_score = max_val
                        best_match_idx = idx

            if best_score > 0.85:

                xb, yb, wb, hb = blocks_b[best_match_idx]

                matched_b_indices.add(best_match_idx)

                pos_shifted = (
                    abs(xa - xb) > 15
                    or abs(ya - yb) > 15
                )

                if pos_shifted:

                    cv2.rectangle(
                        canvas,
                        (xa, ya),
                        (xa + wa, ya + ha),
                        (255, 120, 0),
                        3
                    )

                    cv2.rectangle(
                        canvas,
                        (w + xb, yb),
                        (w + xb + wb, yb + hb),
                        (255, 120, 0),
                        3
                    )

            else:

                fallback_idx = -1

                for idx, (xb, yb, wb, hb) in enumerate(blocks_b):

                    if idx in matched_b_indices:
                        continue

                    if (
                        max(ya, yb) < min(ya + ha, yb + hb)
                        or abs(ya - yb) < 150
                    ):
                        fallback_idx = idx
                        break

                if fallback_idx != -1:

                    xb, yb, wb, hb = blocks_b[fallback_idx]

                    matched_b_indices.add(fallback_idx)

                    cv2.rectangle(
                        canvas,
                        (xa, ya),
                        (xa + wa, ya + ha),
                        (0, 165, 255),
                        3
                    )

                    cv2.rectangle(
                        canvas,
                        (w + xb, yb),
                        (w + xb + wb, yb + hb),
                        (0, 165, 255),
                        3
                    )

                else:

                    cv2.rectangle(
                        canvas,
                        (xa, ya),
                        (xa + wa, ya + ha),
                        (0, 0, 255),
                        3
                    )

        for idx, (xb, yb, wb, hb) in enumerate(blocks_b):

            if idx not in matched_b_indices:

                cv2.rectangle(
                    canvas,
                    (w + xb, yb),
                    (w + xb + wb, yb + hb),
                    (0, 200, 0),
                    3
                )

        # Text token comparison.
        text_a = self.get_text_structures(
            path_a,
            page_num,
            img_a.shape
        )

        text_b = self.get_text_structures(
            path_b,
            page_num,
            img_b.shape
        )

        str_a = [item["text"] for item in text_a]
        str_b = [item["text"] for item in text_b]

        freq_a = Counter(str_a)
        freq_b = Counter(str_b)

        def is_repositioned(token, source_freq, target_freq):
            return (
                source_freq.get(token, 0)
                == target_freq.get(token, 0)
            )

        matcher = difflib.SequenceMatcher(
            None,
            str_a,
            str_b,
            autojunk=False
        )

        for opcode, a_start, a_end, b_start, b_end in matcher.get_opcodes():

            if opcode == "equal":
                continue

            if opcode in ("replace", "delete"):

                for i in range(a_start, a_end):

                    token = str_a[i]

                    if is_repositioned(
                        token,
                        freq_a,
                        freq_b
                    ):
                        continue

                    x, y, bw, bh = text_a[i]["bbox"]

                    underline_y = min(
                        y + bh + 4,
                        h - 1
                    )

                    cv2.line(
                        canvas,
                        (x, underline_y),
                        (x + bw, underline_y),
                        (0, 0, 255),
                        3
                    )

            if opcode in ("replace", "insert"):

                for i in range(b_start, b_end):

                    token = str_b[i]

                    if is_repositioned(
                        token,
                        freq_a,
                        freq_b
                    ):
                        continue

                    x, y, bw, bh = text_b[i]["bbox"]

                    underline_y = min(
                        y + bh + 4,
                        h - 1
                    )

                    cv2.line(
                        canvas,
                        (w + x, underline_y),
                        (w + x + bw, underline_y),
                        (0, 200, 0),
                        3
                    )

        if mismatch_detected:

            img_folder = self.output_dir / "Images"

            img_folder.mkdir(
                parents=True,
                exist_ok=True
            )

            file_path = (
                img_folder
                / f"comparison_page_{page_num + 1}.png"
            )

            cv2.imwrite(
                str(file_path),
                canvas
            )

            return str(file_path)

        return None

    def compare_single_page(
        self,
        page_num,
        path_a,
        path_b
    ):
        img_a = self.render_page(
            path_a,
            page_num
        )

        img_b = self.render_page(
            path_b,
            page_num
        )

        if img_a.shape != img_b.shape:

            img_b = cv2.resize(
                img_b,
                (
                    img_a.shape[1],
                    img_a.shape[0]
                )
            )

        return self.save_hybrid_diff(
            img_a,
            img_b,
            path_a,
            path_b,
            page_num
        )

    def compare(self, path_a, path_b):

        doc_a = fitz.open(path_a)
        doc_b = fitz.open(path_b)

        try:
            page_count = min(
                len(doc_a),
                len(doc_b)
            )
        finally:
            doc_a.close()
            doc_b.close()

        self.update_status(
            status="processing",
            progress=0,
            current_page=0,
            total_pages=page_count,
            message="Starting PDF comparison..."
        )

        if page_count == 0:
            return

        func = partial(
            self.compare_single_page,
            path_a=path_a,
            path_b=path_b
        )

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
                executor.submit(func, page_num)
                for page_num in range(page_count)
            ]

            for future in as_completed(futures):

                result = future.result()

                if result:
                    logging.info(result)

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

    def generate_html_gallery(
        self,
        pdf_path_a,
        pdf_path_b
    ):
        html_file = (
            self.output_dir
            / "compare_report.html"
        )

        image_folder = (
            self.output_dir
            / "Images"
        )

        if image_folder.exists():

            images = sorted(
                image_folder.glob(
                    "comparison_page_*.png"
                ),
                key=lambda f: int(
                    f.stem.split("_")[-1]
                )
            )

        else:
            images = []

        total_mismatches = len(images)

        name_a = Path(pdf_path_a).name
        name_b = Path(pdf_path_b).name

        if total_mismatches == 0:

            result_message = """
            <div class="success">
                ✓ No visual differences detected.
                The compared pages are identical based
                on the configured comparison threshold.
            </div>
            """

        else:

            result_message = f"""
            <div class="warning">
                ⚠ {total_mismatches}
                mismatched page(s) detected.
            </div>
            """

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
    gap: 40px;
    padding-bottom: 50px;
}}

.container {{
    text-align: center;
    color: #000;
    width: 100%;
}}

.container img {{
    width: 100%;
    height: auto;
    border: 2px solid #ccc;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}}

.legend {{
    color: #444;
    font-size: 14px;
    line-height: 1.7;
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
Mismatched Pages Found:
<strong>{total_mismatches}</strong>
</div>

{result_message}

<div class="legend">

<span style="
background-color:rgba(255,0,0,0.2);
padding:2px 5px;
font-weight:bold;">
Red Tint/underlined
</span>:
Removed/Changed Data Area (Left)

|

<span style="
background-color:rgba(0,255,0,0.2);
padding:2px 5px;
font-weight:bold;">
Green Tint/underlined
</span>:
Added/Changed Data Area (Right)

<br>

<span style="
color:orange;
font-weight:bold;">
Orange/Blue Box
</span>:
Repositioned Table/Block

</div>

</header>

<div class="gallery">
"""

        for img_path in images:

            page_num = img_path.stem.split("_")[-1]

            relative_img_path = (
                f"Images/{img_path.name}"
            )

            html_content += f"""
<div class="container">

<h3>Page {page_num}</h3>

<img
    src="{relative_img_path}"
    alt="Mismatch on page {page_num}"
>

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


def main(
    pdf_1,
    pdf_2,
    job_dir
):

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

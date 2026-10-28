<?php
/* Move Files add/edit pages: file-naming rules per extension.
   Each "Files to move" cell gets a one-line summary of its rules (chips); clicking it opens a small dialog with one pattern per extension
   and a live "try a file name" check.  Values are kept in hidden inputs rules[stage][dept][ext] = pattern, which the pages turn into
   inw_move_files_dept_wise.move_name_rules (see includes/move_naming.php).
   $mfn_existing (edit page only): array(stage => array(dept => json)) of the saved rules. */
$mfn_init = array();
if (!empty($mfn_existing) && is_array($mfn_existing)) {
    require_once __DIR__ . '/move_naming.php';
    foreach ($mfn_existing as $st => $depts) {
        foreach ($depts as $dp => $json) {
            $r = mfn_parse_rules($json);
            if ($r) { $mfn_init[$st][$dp] = $r; }
        }
    }
}
?>
<style>
.mf-chips{display:flex;flex-wrap:wrap;align-items:center;gap:4px;margin-top:5px;font-size:11px;line-height:1.2}
.mf-chip{display:inline-flex;gap:4px;align-items:center;max-width:100%;padding:2px 8px;border-radius:10px;background:#eaf0f9;color:#2c5f9e;cursor:pointer;border:0}
.mf-chip b{font-weight:700}
.mf-chip span{color:#14304a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px}
.mf-chip.mf-chip-none{background:#f3f4f6;color:#8a97ab}
.mf-chip.mf-chip-none span{color:#8a97ab}
.mf-chips .mf-edit{border:0;background:none;color:#2c5f9e;font-weight:600;padding:0 2px;cursor:pointer;text-decoration:underline}
.mf-rules-key{margin:8px 0 14px;padding:8px 12px;border-radius:10px;background:#f3f6fb;border:1px solid #e3e9f3;font-size:12px;color:#445}
.mf-rules-key code{background:#fff;padding:0 4px;border-radius:4px}
.mfr-ov{position:fixed;inset:0;background:rgba(20,35,60,.5);z-index:3000;display:flex;align-items:center;justify-content:center;padding:12px}
.mfr-box{background:#fff;border-radius:16px;width:560px;max-width:100%;max-height:92vh;overflow:auto;box-shadow:0 20px 60px rgba(20,35,60,.35);font-size:13px;color:#223}
.mfr-h{background:linear-gradient(90deg,#e8863a,#de7a2a);color:#fff;padding:14px 20px;border-radius:16px 16px 0 0;font-weight:700;font-size:15px}
.mfr-h small{display:block;font-weight:400;opacity:.9;font-size:12px}
.mfr-b{padding:16px 20px;text-align:left}
.mfr-try{display:flex;gap:10px;flex-wrap:wrap;background:#f3f6fb;border-radius:10px;padding:10px 12px;margin-bottom:12px}
.mfr-try label{flex:1 1 200px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#5b6b7b;margin:0}
.mfr-try input,.mfr-row input{width:100%;height:32px;font-size:13px;border:1px solid #cfd8e6;border-radius:8px;padding:2px 10px;margin-top:3px;background:#fff;color:#14304a;box-sizing:border-box}
.mfr-row{display:grid;grid-template-columns:62px 1fr 26px;gap:8px;align-items:center;margin-bottom:8px}
.mfr-row .mfr-ext{font-weight:700;color:#2c5f9e;background:#eaf0f9;border-radius:8px;padding:5px 0;text-align:center}
.mfr-row input{margin:0}
.mfr-row input.mfr-bad{border-color:#c0392b;background:#fdf1ef}
.mfr-res{font-size:16px;text-align:center}
.mfr-ok{color:#2e8b57}.mfr-no{color:#c0392b}.mfr-na{color:#b4bfcc}
.mfr-help{margin:6px 0 0;color:#667;font-size:12px;line-height:1.6}
.mfr-multi{margin:10px 0 0;padding:9px 12px;border-radius:10px;background:#fff7e8;border:1px solid #f2dcae;color:#4a3a14;font-size:12px;line-height:1.6}
.mfr-multi code{background:#fff;padding:0 4px;border-radius:4px}
.mfr-help code{background:#f3f6fb;padding:0 4px;border-radius:4px}
.mfr-f{padding:12px 20px;border-top:1px solid #eef1f6;display:flex;justify-content:flex-end;gap:8px}
.mfr-f button{border-radius:10px;padding:8px 22px;font-weight:700;border:1px solid #cfd8e6;background:#fff;color:#1d3557}
.mfr-f button.mfr-apply{background:#1d3557;border-color:#1d3557;color:#fff}
</style>
<script>
(function () {
  var INIT = <?php echo json_encode((object) $mfn_init); ?>;
  function stageOf(name) { var m = /^files\[([^\]]+)\]\[(\d+)\]$/.exec(name); return m ? { stage: m[1], dept: m[2] } : null; }
  function exts(v) {
    var seen = {}, out = [];
    $.each(String(v || '').split('|'), function (i, e) { e = $.trim(e).replace(/^\.+/, '').toLowerCase(); if (e && /^[a-z0-9_\-]+$/.test(e) && !seen[e]) { seen[e] = 1; out.push(e); } });
    return out;
  }
  function esc(s) { return String(s).replace(/[.*+?^${}()|[\]\\\/\-]/g, '\\$&'); }

  /* same pattern language as includes/move_naming.php (the server validates with PCRE on save) */
  function toRegex1(pat, pid) {
    try {
      if (pat.length > 2 && pat.charAt(0) === '~' && pat.slice(-1) === '~') { return new RegExp(pat.slice(1, -1).split('{pubid}').join(esc(pid)), 'i'); }
      var re = '';
      $.each(pat.split(/(\{pubid\}|\[[^\]]*\]|\*|\?)/), function (i, p) {
        if (p === '') { return; }
        if (p === '{pubid}') { re += esc(pid); } else if (p === '*') { re += '.*'; } else if (p === '?') { re += '.'; }
        else if (p.charAt(0) === '[' && p.slice(-1) === ']' && p.length > 2) { re += '[' + esc(p.slice(1, -1)) + ']'; } else { re += esc(p); }
      });
      return new RegExp('^' + re + '$', 'i');
    } catch (e) { return null; }
  }
  /* a rule may list several names/patterns separated by commas; returns a tester, or null when any of them is invalid */
  function toRegex(pat, pid) {
    pat = $.trim(pat); if (!pat) { return null; }
    var alts = (pat.length > 2 && pat.charAt(0) === '~' && pat.slice(-1) === '~') ? [pat] : $.grep($.map(pat.split(','), $.trim), function (a) { return a !== ''; });
    var res = [], ok = alts.length > 0;
    $.each(alts, function (i, a) { var r = toRegex1(a, pid); if (!r) { ok = false; } else { res.push(r); } });
    return ok ? { test: function (s) { for (var i = 0; i < res.length; i++) { if (res[i].test(s)) { return true; } } return false; } } : null;
  }

  /* hidden inputs are the source of truth for a Files cell */
  function hiddenFor($files) { return $files.parent().find('input.mf-rule-h'); }
  function rulesOf($files) { var o = {}; hiddenFor($files).each(function () { o[$(this).data('ext')] = this.value; }); return o; }
  function setRules($files, rules) {
    var k = stageOf($files.attr('name')); if (!k) { return; }
    var $col = $files.parent();
    $col.find('input.mf-rule-h').remove();
    $.each(exts($files.val()), function (i, e) {
      $('<input type="hidden" class="mf-rule-h">').attr('name', 'rules[' + k.stage + '][' + k.dept + '][' + e + ']').data('ext', e).val(rules[e] || '').prop('disabled', $files.prop('disabled')).appendTo($col);
    });
    chips($files);
  }
  function chips($files) {
    var $col = $files.parent(), $c = $col.children('.mf-chips'), list = exts($files.val()), r = rulesOf($files);
    if (!$c.length) { $c = $('<div class="mf-chips"></div>').insertAfter($files); }
    $c.empty();
    if (!list.length) { return; }
    $.each(list, function (i, e) {
      var has = $.trim(r[e] || '') !== '';
      var $b = $('<button type="button" class="mf-chip"><b></b><span></span></button>').toggleClass('mf-chip-none', !has).attr('title', has ? 'File name rule for .' + e : 'No file name rule: every .' + e + ' file moves');
      $b.find('b').text('.' + e); $b.find('span').text(has ? r[e] : 'any file');
      $c.append($b);
    });
    $c.append('<button type="button" class="mf-edit">Naming rules</button>');
  }
  function sync($files) {
    var cur = rulesOf($files), keep = {};
    $.each(exts($files.val()), function (i, e) { if (cur[e]) { keep[e] = cur[e]; } });
    setRules($files, keep);
  }

  /* the dialog */
  function openDialog($files) {
    var k = stageOf($files.attr('name')), list = exts($files.val()); if (!k || !list.length) { return; }
    var $lab = $files.closest('.d-md-flex').children('label').first().clone(); $lab.find('.mf-self-tag').remove();
    var rules = rulesOf($files);
    var $ov = $('<div class="mfr-ov"><div class="mfr-box"><div class="mfr-h">File naming rules<small></small></div><div class="mfr-b"></div><div class="mfr-f"><button type="button" class="mfr-cancel">Cancel</button><button type="button" class="mfr-apply">Apply</button></div></div></div>');
    $ov.find('small').text($.trim($lab.text()).replace(/:$/, '') + ' · stage ' + k.stage + ' · only matching files move to this department');
    var $b = $ov.find('.mfr-b');
    $b.append('<div class="mfr-try"><label>Sample pub id<input type="text" class="mfr-pid" value="ABC-123_x" autocomplete="off"></label><label>Try a file name<input type="text" class="mfr-file" placeholder="e.g. ABC-123_x_final.docx" autocomplete="off"></label></div>');
    $.each(list, function (i, e) {
      var $r = $('<div class="mfr-row"><span class="mfr-ext"></span><input type="text" maxlength="200" class="mfr-pat" placeholder="empty = every file of this type moves" autocomplete="off"><span class="mfr-res"></span></div>');
      $r.find('.mfr-ext').text('.' + e); $r.find('.mfr-pat').data('ext', e).val(rules[e] || ''); $b.append($r);
    });
    $b.append('<div class="mfr-multi"><b>Need more than one file name for the same type?</b> Write them in the same box, separated by a comma. A file moves if its name matches <u>any one</u> of them.<br>e.g. <code>{pubid}_2, {pubid}_note</code> moves <code>ABC-1_2.txt</code> and <code>ABC-1_note.txt</code>, and nothing else of that type.<br>Use a comma only here. Use <code>|</code> only in the Files box, to separate file types (<code>pdf|txt</code>).</div>');
    $b.append('<div class="mfr-help"><b>Fixed names</b> work as they are: <code>cover</code> or <code>main.tex</code> moves only that file.<br><code>{pubid}</code> the article pub id &nbsp; <code>*</code> anything &nbsp; <code>?</code> one character &nbsp; <code>[-_]</code> one of these<br>Examples: <code>{pubid}</code> &nbsp; <code>{pubid}_*</code> &nbsp; <code>{pubid}[-_]rev?</code><br>Full regex between tildes: <code>~^{pubid}(_rev\\d+)?$~</code></div>');
    function check() {
      var pid = $.trim($ov.find('.mfr-pid').val()), file = $.trim($ov.find('.mfr-file').val()), m = /^(.*)\.([^.]+)$/.exec(file);
      $ov.find('.mfr-row').each(function () {
        var $in = $(this).find('.mfr-pat'), e = $in.data('ext'), pat = $.trim($in.val()), $res = $(this).find('.mfr-res'), re = pat ? toRegex(pat, pid) : null;
        $in.toggleClass('mfr-bad', !!pat && !re);
        if (!file || !m || m[2].toLowerCase() !== e) { $res.attr('class', 'mfr-res mfr-na').text('·').attr('title', ''); return; }
        var ok = !pat || (re && (re.test(m[1]) || re.test(file)));
        $res.attr('class', 'mfr-res ' + (ok ? 'mfr-ok' : 'mfr-no')).text(ok ? '✓' : '✗').attr('title', ok ? (pat ? 'matches: this file would move' : 'no rule: this file would move') : 'does not match: this file would NOT move');
      });
    }
    $ov.on('input', 'input', check);
    function close() { $(document).off('keydown.mfr'); $ov.remove(); }
    $ov.on('click', '.mfr-cancel', close).on('click', function (ev) { if (ev.target === this) { close(); } });
    $(document).on('keydown.mfr', function (ev) { if (ev.key === 'Escape') { close(); } });
    $ov.on('click', '.mfr-apply', function () {
      var out = {}, bad = false;
      $ov.find('.mfr-pat').each(function () {
        var p = $.trim(this.value); if (!p) { return; }
        if (!toRegex(p, 'X-1')) { bad = true; $(this).addClass('mfr-bad'); return; }
        out[$(this).data('ext')] = p;
      });
      if (bad) { return; }
      setRules($files, out); close();
    });
    $('body').append($ov); check(); $ov.find('.mfr-pat').first().focus();
  }

  $(function () {
    $('input[name^="files["]').each(function () {
      var $f = $(this), k = stageOf($f.attr('name')), saved = (k && INIT[k.stage] && INIT[k.stage][k.dept]) || {};
      setRules($f, saved);
    });
    $(document).on('input change', 'input[name^="files["]', function () { sync($(this)); });
    $(document).on('click', '.mf-chip, .mf-edit', function () { openDialog($(this).closest('.mf-chips').parent().children('input[name^="files["]')); });
    /* the stage-depts script disables the row of the source department: keep the hidden rule inputs in step */
    setInterval(function () { $('input[name^="files["]').each(function () { $(this).parent().find('input.mf-rule-h').prop('disabled', $(this).prop('disabled')); }); }, 800);
    var $anchor = $('#accordion').first();
    if ($anchor.length && !$('.mf-rules-key').length) {
      $anchor.before('<div class="mf-rules-key"><b>File naming rules:</b> after typing the extensions (e.g. <code>xml|docx</code>) click <b>Naming rules</b> under the box. An extension with a rule moves only files whose name matches it; without a rule every file of that type moves.</div>');
    }
  });
})();
</script>

<?php include "includes/includes.php";
include "includes/modern-ui.php";
/* ============================================================================
 * Manuscript File Flow - new version                     /workflow-new/departmentfileflow
 * "When did each department pick up this manuscript, and where is it now?"
 * Every number comes from api/fileflow.php. The previous version stays at /departmentfileflow-old.
 * ========================================================================== */

/* old links (?date_wise=m&mfrom_dt=06-2026&cust_id=42&j_id=..&stage=FIN&dp_platform=LATEX&filter=1) open the same view here */
if (isset($_GET['filter']) || isset($_GET['date_wise']) || isset($_GET['wgetaction'])) {
    $q = array();
    $c = isset($_GET['cust_id']) ? (string)$_GET['cust_id'] : '';
    if ($c === 'all' || ctype_digit($c)) { $q['cust'] = $c; }
    $jj = isset($_GET['j_id']) ? (string)$_GET['j_id'] : '';
    if (ctype_digit($jj)) { $q['j'] = $jj; }
    $st = isset($_GET['stage']) ? strtoupper((string)$_GET['stage']) : '';
    if (in_array($st, array('FP', 'REV', 'PMC', 'FIN', 'JSTAGE'), true)) { $q['stage'] = $st; }
    $pl = isset($_GET['dp_platform']) ? (string)$_GET['dp_platform'] : '';
    if ($pl !== '' && $pl !== 'all' && preg_match('/^[A-Za-z0-9 _-]{1,20}$/', $pl)) { $q['platform'] = $pl; }
    $dw = isset($_GET['date_wise']) ? (string)$_GET['date_wise'] : '';
    if ($dw === 'm' && preg_match('/^(\d{2})-(\d{4})$/', isset($_GET['mfrom_dt']) ? (string)$_GET['mfrom_dt'] : '', $m)) {
        $q['month'] = $m[2] . '-' . $m[1];
    } elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', isset($_GET['from_dt']) ? (string)$_GET['from_dt'] : '', $a)
           && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', isset($_GET['to_dt']) ? (string)$_GET['to_dt'] : '', $b)
           && checkdate((int)$a[2], (int)$a[1], (int)$a[3]) && checkdate((int)$b[2], (int)$b[1], (int)$b[3])) {
        $q['from'] = $a[3] . '-' . $a[2] . '-' . $a[1];
        $q['to']   = $b[3] . '-' . $b[2] . '-' . $b[1];
    }
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: departmentfileflow' . ($q ? '?' . http_build_query($q) : ''));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <link rel="stylesheet" href="css/report-ui.css?v=1">
    <style>
/* ---------- File Flow specifics ---------- */
.ifx-stat.on{box-shadow:0 0 0 2px var(--c),0 12px 24px rgba(20,48,74,.15);transform:translateY(-3px);}
#ffFocus.on i{color:#b87800;} #ffFocus button{margin-left:auto;border:0;background:transparent;color:#7a4f00;font-weight:700;cursor:pointer;text-decoration:underline;}
.ff-seg{display:inline-flex;padding:4px;border-radius:10px;background:#eef2f6;}
.ff-seg button{border:0;background:transparent;padding:8px 16px;border-radius:7px;font:inherit;font-weight:600;color:var(--mute);cursor:pointer;transition:all .2s;white-space:nowrap;}
.ff-seg button.on{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(20,48,74,.15);}
.ff-check{display:flex;align-items:center;gap:8px;height:40px;font-weight:600;color:var(--ink);cursor:pointer;}
.ff-check{text-transform:none !important;letter-spacing:0 !important;font-size:13px !important;}
.ff-check input{width:17px;height:17px;accent-color:#1b6ca8;}
.ifx-filters .mu-field select{min-width:150px;}
.ff-hint{margin:-2px 0 14px;color:var(--mute);font-size:12.5px;}
.ff-hint b{color:var(--navy);}
.ff-tools{display:flex;flex-wrap:wrap;align-items:center;gap:10px 12px;margin-bottom:14px;}
.ff-tools .mu-search{flex:1;min-width:220px;max-width:360px;}
.ff-tools select{height:38px;padding:0 10px;border:1px solid #d5dde6;border-radius:9px;background:#fff;}
.ff-tools .sp{flex:1;}
.ff-wrap{overflow:auto;border:1px solid var(--line);border-radius:12px;max-width:100%;}
.ff-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;}
.ff-table th{position:sticky;top:0;z-index:2;background:#f4f7fa;color:var(--mute);font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;text-align:left;padding:10px 12px;white-space:nowrap;user-select:none;}
.ff-table th[data-sort]{cursor:pointer;} .ff-table th[data-sort]:hover{color:var(--navy);background:#e9eef4;}
.ff-table th.asc:after{content:" \25B2";color:var(--accent);font-size:9px;} .ff-table th.desc:after{content:" \25BC";color:var(--accent);font-size:9px;}
.ff-table th.dp{text-align:center;background:#eef3f8;color:var(--navy);}
.ff-table td{padding:10px 12px;border-top:1px solid var(--line);background:#fff;vertical-align:middle;white-space:nowrap;}
.ff-table tbody tr{cursor:pointer;} .ff-table tbody tr:hover td{background:#f4f9fd;}
.ff-table tbody tr:first-child td{border-top:0;}
.ff-table td.n{color:var(--mute);font-weight:700;width:44px;}
.ff-table td.ref{position:sticky;left:0;z-index:1;box-shadow:2px 0 0 var(--line);min-width:190px;max-width:260px;}
.ff-table th.ref{position:sticky;left:0;z-index:3;box-shadow:2px 0 0 var(--line);}
.ff-table td.ref b{color:var(--navy);} .ff-table td small{display:block;color:var(--mute);font-size:11.5px;margin-top:1px;max-width:240px;overflow:hidden;text-overflow:ellipsis;}
.ff-table td.c{text-align:center;}
.ff-table td.dp{text-align:center;min-width:104px;border-left:1px dashed #e6ebf1;}
.ff-table td.dp .t{font-weight:700;color:var(--navy);font-variant-numeric:tabular-nums;}
.ff-table td.dp .t i{font-style:normal;color:var(--mute);font-weight:600;font-size:11.5px;margin-right:3px;}
.ff-table td.dp .s{font-size:11.5px;color:var(--mute);margin-top:1px;}
.ff-open{display:inline-block;margin-top:2px;padding:1px 8px;border-radius:20px;background:#fdf1d9;color:#b87800;font-size:11px;font-weight:700;}
.ff-now small{margin-top:3px;}
.ff-flag.el{background:#ece8fd;color:#5b3fd1;}
.ff-flag.nr{background:#eef1f4;color:#5c6b7a;}
.ff-flag{display:inline-block;padding:2px 9px;border-radius:20px;background:#fbe5e5;color:#d64545;font-size:11.5px;font-weight:700;}
.ff-go{color:#b7c2ce;transition:color .15s,transform .15s;} .ff-table tbody tr:hover .ff-go{color:var(--blue);transform:translateX(3px);}
.ifx-st.PMC{background:#fdf1d9;color:#b87800;} .ifx-st.JSTAGE{background:#fdf1dc;color:#b8730f;}
.ff-skel td{padding:14px 12px;}
/* journey drawer */
.ff-trh{padding:16px 20px 6px;}
.ff-trh h3{margin:0 0 4px;font-size:15px;color:var(--navy);font-weight:700;line-height:1.35;}
.ff-chips{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0 4px;}
.ff-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:20px;background:#eef3f8;color:var(--mute);font-size:12px;font-weight:600;}
.ff-chip b{color:var(--navy);}
.ff-stg{margin:14px 20px 0;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff;}
.ff-stg.focus{border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.12);}
.ff-stg>header{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;padding:10px 14px;background:#f4f7fa;}
.ff-stg>header .m{color:var(--mute);font-size:12px;} .ff-stg>header .m b{color:var(--navy);}
.ff-stg>header .r{margin-left:auto;}
.ff-steps{list-style:none;margin:0;padding:6px 14px 10px 30px;position:relative;}
.ff-steps:before{content:"";position:absolute;left:19px;top:14px;bottom:18px;width:2px;background:#e1e8ef;}
.ff-steps li{position:relative;padding:8px 0 8px 14px;}
.ff-steps li:before{content:"";position:absolute;left:-16px;top:14px;width:10px;height:10px;border-radius:50%;background:#fff;border:2px solid #1b6ca8;}
.ff-steps li.open:before{border-color:#e8a30a;background:#fdf1d9;animation:muPulse 1.6s infinite;}
.ff-steps .a{display:flex;flex-wrap:wrap;align-items:center;gap:8px;}
.ff-steps .a b{color:var(--navy);} .ff-steps .u{color:var(--mute);font-size:12.5px;}
.ff-steps .b{margin-top:2px;color:var(--mute);font-size:12.5px;font-variant-numeric:tabular-nums;}
.ff-steps .b em{font-style:normal;color:#b87800;font-weight:600;}
.ff-steps .cm{margin-top:3px;padding:6px 10px;border-radius:8px;background:#f8fafc;color:#4a5865;font-size:12px;white-space:normal;}
.ff-drawer-links{display:flex;gap:8px;margin-top:10px;}
@media(max-width:700px){.ff-seg button{padding:8px 10px;}.ff-trh,.ff-stg{margin-left:14px;margin-right:14px;padding-left:0;padding-right:0;}.ff-trh{padding-left:0;}}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php"); ?>

      <div class="mu-root ifx" id="ifx">
        <div class="mu-hero">
          <div>
            <small>Reports</small>
            <h1>Manuscript File Flow</h1>
            <p>When each department picked up a manuscript, and where it is now - for every manuscript received in the period</p>
          </div>
          <div class="mu-hero-right">
            <span class="ifx-live" id="ifxLive">LIVE DATA</span>
            <div class="mu-chip"><small>Calculated</small><b id="ifxCalc">-</b></div>
            <a class="ifx-hero-link" href="departmentfileflow-old">Previous version</a>
          </div>
        </div>

        <section class="mu-panel mu-filter-panel">
          <div class="ifx-filters">
            <div class="mu-field"><label>Stage</label>
              <div class="ff-seg" id="ffStage" role="tablist">
                <?php $ff_first = true; foreach (array('FP' => 'First proof', 'REV' => 'Revisions', 'PMC' => 'PMC', 'FIN' => 'Finals', 'JSTAGE' => 'JSTAGE') as $ff_k => $ff_n) { if (!rs_row_enabled($ff_k)) { continue; } ?><button type="button" data-s="<?php echo $ff_k; ?>"<?php echo $ff_first ? ' class="on"' : ''; ?>><?php echo $ff_n; ?></button><?php $ff_first = false; } ?>
              </div></div>
            <div class="mu-field"><label>Received by</label>
              <div class="mu-seg" id="ifxMode">
                <label><input type="radio" name="ifxmode" value="days" checked><span>Days</span></label>
                <label><input type="radio" name="ifxmode" value="month"><span>Month</span></label>
              </div></div>
            <div class="mu-field" id="ifxDaysField" style="min-width:250px;"><label>Received between</label>
              <div class="dvr" id="ifxRange" data-from-name="from" data-to-name="to"></div></div>
            <div class="mu-field" id="ifxMonthField" style="display:none;"><label>Month</label>
              <div class="ifx-month"><select id="ifxMM" aria-label="Month"></select><select id="ifxYY" aria-label="Year"></select></div></div>
            <div class="mu-field" style="min-width:200px;"><label for="ifxCust">Customer</label>
              <select id="ifxCust"><option>Loading...</option></select></div>
            <div class="mu-field" style="min-width:200px;"><label for="ffJ">Journal</label>
              <select id="ffJ"><option value="all">All journals</option></select></div>
            <div class="mu-field" style="min-width:150px;"><label for="ffPlat">Platform</label>
              <select id="ffPlat"><option value="all">All platforms</option></select></div>
            <div class="mu-field"><label>&nbsp;</label>
              <label class="ff-check" title="The previous report only listed manuscripts that are still in the chosen stage today"><input type="checkbox" id="ffCur"> Only those still in this stage</label></div>
            <div class="ifx-actions">
              <button type="button" class="mu-btn" id="ifxRefresh" title="Reload the numbers"><i class="fa fa-refresh"></i> Refresh</button>
              <button type="button" class="mu-btn" id="ifxLink" title="Copy a link to this exact view"><i class="fa fa-link"></i> Copy link</button>
            </div>
          </div>
        </section>

        <div class="ifx-note" id="ffFocus" style="background:#fdf1d9;border-color:#f1dcab;color:#7a4f00;"></div>
        <div class="ifx-note" id="ffNote"><i class="fa fa-info-circle"></i><span id="ffNoteTxt"></span><button type="button" id="ffNoteBtn"></button></div>
        <div class="ifx-stats" id="ifxStats"></div>

        <section class="mu-panel">
          <h3><i class="fa fa-random"></i> <span id="ffTitle">Manuscripts</span> <span class="mu-right" id="ffCount"></span></h3>
          <p class="ff-hint" id="ffHint"></p>
          <div class="ff-tools">
            <input type="search" class="mu-search" id="ifxQ" placeholder="Search manuscript, title or journal" aria-label="Search">
            <select id="ifxPer" aria-label="Rows per page"><option value="25">25 rows</option><option value="50" selected>50 rows</option><option value="100">100 rows</option><option value="200">200 rows</option></select>
            <span class="sp"></span>
            <button type="button" class="mu-btn" id="ifxExport"><i class="fa fa-file-excel-o"></i> Export CSV</button>
          </div>
          <div id="ffTable"><div class="ifx-skel-grid"><i></i><i></i><i></i><i></i><i></i></div></div>
          <div class="ifx-dfoot" style="border:0;padding:14px 0 0;"><span class="info" id="ffInfo"></span><div class="ifx-pg" id="ifxPg"></div></div>
        </section>
      </div>

      <!-- journey drawer -->
      <div class="ifx-scrim" id="ifxScrim"></div>
      <aside class="ifx-drawer" id="ifxDrawer" role="dialog" aria-modal="true" aria-labelledby="ifxDTitle" aria-hidden="true">
        <div class="ifx-dhead">
          <div class="grow"><h2 id="ifxDTitle">Journey</h2><div class="sub" id="ifxDSub"></div></div>
          <span class="cnt" id="ifxDCnt">0 steps</span>
          <button type="button" class="ifx-dbtn" id="ifxClose" title="Close (Esc)" aria-label="Close"><i class="fa fa-times"></i></button>
        </div>
        <div class="ifx-dbody" id="ifxDBody" style="padding:0 0 18px;"></div>
      </aside>
      <div class="ifx-toast" id="ifxToast" role="status"></div>

      <?php echo footer(); ?>
    </div>

    <?php echo scripts(); ?>
    <script src="dv-rangepicker.js?v=2"></script>
    <script>
(function () {
  'use strict';
  var API = 'api/fileflow.php';
  var STAGE_NAME = { FP: 'First proof', REV: 'Revisions', PMC: 'PMC', FIN: 'Finals', JSTAGE: 'JSTAGE' };
  var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
  var parse = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
  var parseDT = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) : null; };
  var addDays = function (d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; };
  var today = new Date(); today.setHours(0, 0, 0, 0);
  var fmtShort = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) : ''; };
  var fmtLong = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear() : ''; };
  var fmtDate = function (s) { return (!s || s === '0000-00-00') ? '-' : fmtLong(s); };
  var fmtTime = function (s) { var d = parseDT(s); return d ? pad(d.getHours()) + ':' + pad(d.getMinutes()) : ''; };
  var fmtDT = function (s) { var d = parseDT(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear() + ', ' + fmtTime(s) : '-'; };
  var nf = function (n) { return Number(n || 0).toLocaleString(); };
  function dur(min) {                                   /* 95 -> "1h 35m", 3000 -> "2d 2h" */
    if (min == null) { return '-'; }
    min = Math.round(min); if (min < 1) { return '<1m'; }
    var d = Math.floor(min / 1440), h = Math.floor((min % 1440) / 60), m = min % 60;
    if (d) { return d + 'd ' + h + 'h'; } if (h) { return h + 'h ' + (m ? m + 'm' : ''); } return m + 'm';
  }

  /* ------------------------------------------------------------ state */
  var RS_ON = <?php echo json_encode(rs_row_map()); ?>;   /* which stages the reports show: $report_stage_types (includes/report_stages.php) */
  var FF_FIRST = ['FP', 'REV', 'PMC', 'FIN', 'JSTAGE'].filter(function (k) { return RS_ON[k]; })[0] || 'FP';
  var S = { stage: FF_FIRST, mode: 'days', from: iso(addDays(today, -29)), to: iso(today), my: today.getFullYear(), mm: today.getMonth() + 1,
            cust: 'all', j: 'all', platform: 'all', current: false, focus: '', q: '', sort: '', dir: 'desc', page: 1, per: 50,
            meta: null, list: null, summary: null, columns: [], trace: null };
  var ctl = { list: null, summary: null, trace: null };
  var opener = null, journeyFocus = null;

  function range() {
    if (S.mode === 'month') { return { from: iso(new Date(S.my, S.mm - 1, 1)), to: iso(new Date(S.my, S.mm, 0)) }; }
    return { from: S.from, to: S.to };
  }
  function custName() {
    if (S.cust === 'all') { return 'All customers'; }
    var c = (S.meta ? S.meta.customers : []).filter(function (x) { return String(x.id) === String(S.cust); })[0];
    return c ? c.name : 'Customer ' + S.cust;
  }
  function baseParams(extra) {
    var r = range(), p = { stage: S.stage, from: r.from, to: r.to, cust_id: S.cust };
    if (S.j !== 'all') { p.j_id = S.j; }
    if (S.platform !== 'all') { p.platform = S.platform; }
    if (S.current) { p.current_only = 1; }
    for (var k in extra) { p[k] = extra[k]; }
    return p;
  }

  /* ------------------------------------------------------------ url */
  function readUrl() {
    var p = new URLSearchParams(location.search);
    if (p.get('cust')) { S.cust = p.get('cust'); } if (p.get('j')) { S.j = p.get('j'); } if (p.get('platform')) { S.platform = p.get('platform'); }
    if (/^(FP|REV|PMC|FIN|JSTAGE)$/.test(p.get('stage') || '') && RS_ON[p.get('stage')]) { S.stage = p.get('stage'); }
    S.current = p.get('current') === '1';
    if (/^(waiting|elsewhere|progress|wait|flow)$/.test(p.get('focus') || '')) { S.focus = p.get('focus'); }
    var mo = /^(\d{4})-(\d{2})$/.exec(p.get('month') || '');
    if (mo) { S.mode = 'month'; S.my = +mo[1]; S.mm = +mo[2]; }
    else if (parse(p.get('from')) && parse(p.get('to'))) { S.from = p.get('from'); S.to = p.get('to'); }
    var h = /^#m(\d+)(?::([A-Z]+\d*))?$/.exec(location.hash || '');
    return h ? { id: +h[1], stage: h[2] || null } : null;
  }
  function writeUrl() {
    var p = new URLSearchParams();
    p.set('stage', S.stage); p.set('cust', S.cust); if (S.j !== 'all') { p.set('j', S.j); } if (S.platform !== 'all') { p.set('platform', S.platform); } if (S.current) { p.set('current', '1'); } if (S.focus) { p.set('focus', S.focus); }
    if (S.mode === 'month') { p.set('month', S.my + '-' + pad(S.mm)); } else { p.set('from', S.from); p.set('to', S.to); }
    var h = S.trace ? '#m' + S.trace + (journeyFocus ? ':' + journeyFocus : '') : '';
    try { history.replaceState(null, '', location.pathname + '?' + p.toString() + h); } catch (e) {}
    try { localStorage.setItem('ffCust', S.cust); } catch (e) {}
  }

  /* ------------------------------------------------------------ api */
  function api(params, key) {
    if (ctl[key]) { ctl[key].abort(); }
    var c = ctl[key] = new AbortController();
    return fetch(API + '?' + new URLSearchParams(params).toString(), { credentials: 'same-origin', signal: c.signal }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Unexpected response from the server.' }; }).then(function (j) { j._status = r.status; return j; });
    });
  }
  function apiPlain(params) { return fetch(API + '?' + new URLSearchParams(params).toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); }); }
  function toast(msg) { var t = $('#ifxToast'); t.textContent = msg; t.classList.add('on'); clearTimeout(toast._t); toast._t = setTimeout(function () { t.classList.remove('on'); }, 2200); }
  function failBox(j, retry) {
    var m = j && j.error ? esc(j.error) : 'Something went wrong.';
    if (j && j._status === 401) { m += ' <a href="index">Sign in</a>'; }
    return '<div class="ifx-err"><i class="fa fa-exclamation-triangle"></i><span>' + m + '</span>' + (retry ? '<button type="button" class="mu-btn" data-retry>Try again</button>' : '') + '</div>';
  }
  function statusPill(s) {
    var v = String(s || ''), cls = 'label-default';
    if (/deliver|complet|approved/i.test(v)) { cls = 'label-success'; } else if (/query|hold|reject/i.test(v)) { cls = 'label-warning'; } else if (/review|progress|assign|process/i.test(v)) { cls = 'label-info'; }
    return v ? '<span class="label ' + cls + '">' + esc(v) + '</span>' : '';
  }
  function stClass(stage) { var s = String(stage || ''); return /^FP/.test(s) ? 'FP' : /^REV/.test(s) ? 'REV' : /^FIN/.test(s) ? 'FIN' : /^PMC/.test(s) ? 'PMC' : /^JSTAGE/.test(s) ? 'JSTAGE' : 'ISS'; }

  /* ------------------------------------------------------------ load */
  function loadAll() {
    $('#ifxLive').textContent = 'UPDATING';
    var r = range();
    $('#ffTitle').textContent = STAGE_NAME[S.stage] + ' received';
    $('#ffHint').innerHTML = S.current
      ? 'Showing <b>only manuscripts still in ' + STAGE_NAME[S.stage].toLowerCase() + ' today</b> (the view of the previous report). Each department cell is the first time it picked the file up.'
      : 'Every manuscript <b>received between ' + fmtLong(r.from) + ' and ' + fmtLong(r.to) + '</b>, wherever it is now. Each department cell is the first time it picked this round up; a dash means that department did not work on it.';
    $('#ifxStats').innerHTML = [1, 2, 3, 4, 5].map(function () { return '<div class="ifx-stat skel"><div class="top">&nbsp;</div><b>0</b><div class="delta">&nbsp;</div></div>'; }).join('');
    loadSummary(); loadList();
  }
  function loadSummary() {
    api(baseParams({ action: 'summary' }), 'summary').then(function (j) {
      if (!j.ok) { $('#ifxStats').innerHTML = ''; return; }
      S.summary = j; renderStats(); renderNote();
    }).catch(function () {});
  }
  function loadList() {
    if (!S.list) { $('#ffTable').innerHTML = '<div class="ifx-skel-grid"><i></i><i></i><i></i><i></i><i></i></div>'; }
    var extra = { action: 'list', page: S.page, per_page: S.per };
    if (S.sort) { extra.sort = S.sort; extra.dir = S.dir; } if (S.q) { extra.q = S.q; } if (S.focus) { extra.focus = S.focus; }
    api(baseParams(extra), 'list').then(function (j) {
      $('#ifxLive').textContent = 'LIVE DATA';
      if (!j.ok) { $('#ffTable').innerHTML = failBox(j, true); $('#ifxPg').innerHTML = ''; $('#ffInfo').textContent = ''; return; }
      S.list = j; S.columns = j.columns;
      $('#ifxCalc').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' · ' + (j.ms / 1000).toFixed(2) + 's';
      renderTable(); renderPager();
    }).catch(function (e) { if (e && e.name === 'AbortError') { return; } $('#ifxLive').textContent = 'LIVE DATA'; $('#ffTable').innerHTML = failBox({ error: 'Could not reach the server.' }, true); });
  }

  function countUp(el, to) {
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.textContent = nf(to);
    if (reduce || to === 0 || document.visibilityState !== 'visible') { return; }
    var t0 = null, d = 650, done = false; el.textContent = nf(0);
    setTimeout(function () { done = true; el.textContent = nf(to); }, d + 250);
    (function step(ts) { if (done) { return; } if (t0 === null) { t0 = ts; } var p = Math.min(1, (ts - t0) / d), e = 1 - Math.pow(1 - p, 3); el.textContent = nf(Math.round(to * e)); if (p < 1) { requestAnimationFrame(step); } })(performance.now());
  }
  var FOCUS_TXT = {
    waiting:  { title: 'Waiting for pickup', note: 'manuscripts no department has started yet' },
    elsewhere:{ title: 'Worked in another stage', note: 'no timing in this stage - the work was recorded under another stage (see the journey)' },
    progress: { title: 'In progress now',    note: 'manuscripts a department is working on right now' },
    wait:     { title: 'Longest wait before pickup', note: 'picked-up manuscripts, the ones that waited longest first' },
    flow:     { title: 'Longest time in flow',       note: 'finished manuscripts, the slowest from first pickup to last finish first' }
  };
  function stateFlag(r) {
    if (r.state === 'elsewhere') { return '<span class="ff-flag el" title="No timing in this stage; open the journey to see it">worked in ' + esc((r.other_stages || []).join(', ')) + '</span>'; }
    if (r.state === 'norecord') { return '<span class="ff-flag nr" title="Nothing was recorded for this manuscript in any stage">no work recorded</span>'; }
    return '<span class="ff-flag">not picked up</span>';
  }
  function renderStats() {
    var s = S.summary, cards = [
      { f: '',         i: 'fa-files-o',         c: '#1b6ca8', t: STAGE_NAME[S.stage], v: s.total, d: s.archived ? nf(s.archived) + ' from the archive' : 'received in the period', tip: 'Show every manuscript' },
      { f: 'waiting',  i: 'fa-hourglass-start', c: s.not_picked_up ? '#d64545' : '#1e9e61', t: 'Waiting for pickup', v: s.not_picked_up, d: s.not_picked_up ? 'no work recorded anywhere yet' : 'everything has been picked up', tip: 'Show the manuscripts waiting for pickup' },
      s.worked_elsewhere ? { f: 'elsewhere', i: 'fa-random', c: '#7a5af8', t: 'Worked in another stage', v: s.worked_elsewhere, d: 'e.g. went straight to Finals', tip: 'Show manuscripts whose work is recorded under another stage' } : null,
      { f: 'progress', i: 'fa-spinner',         c: '#e8742a', t: 'In progress now', v: s.in_progress, d: 'a department is working on it', tip: 'Show what is being worked on now' },
      { f: 'wait',     i: 'fa-clock-o',         c: '#7a5af8', t: 'Avg. wait before pickup', txt: dur(s.avg_wait_min), d: 'from logged in to first department', tip: 'Show the longest waits first' },
      { f: 'flow',     i: 'fa-road',            c: '#14304a', t: 'Avg. time in flow', txt: dur(s.avg_flow_min), d: 'first pickup to last finish', tip: 'Show the slowest finished manuscripts first' }
    ].filter(Boolean);
    var box = $('#ifxStats');
    box.innerHTML = cards.map(function (c) {
      var on = (S.focus || '') === c.f && (c.f !== '' || !S.focus);
      return '<button type="button" class="ifx-stat' + (on && c.f ? ' on' : '') + '" data-focus="' + c.f + '" title="' + esc(c.tip) + '" style="--c:' + c.c + '"><div class="top"><i class="fa ' + c.i + '"></i>' + esc(c.t) + '</div><b' + (c.txt == null ? ' data-n="' + c.v + '"' : '') + '>' + (c.txt == null ? '0' : esc(c.txt)) + '</b><div class="delta">' + esc(c.d) + '</div></button>';
    }).join('');
    [].forEach.call(box.querySelectorAll('b[data-n]'), function (b) { countUp(b, +b.getAttribute('data-n')); });
  }
  function renderFocusBar() {
    var bar = $('#ffFocus');
    if (!S.focus) { bar.classList.remove('on'); bar.innerHTML = ''; return; }
    var t = FOCUS_TXT[S.focus];
    bar.innerHTML = '<i class="fa fa-filter"></i><span>Showing only <b>' + esc(t.title) + '</b> – ' + esc(t.note) + '. <b id="ffFocusN"></b></span><button type="button" id="ffFocusClear">Show all manuscripts</button>';
    bar.classList.add('on');
  }
  function setFocus(f) {
    S.focus = (S.focus === f) ? '' : f; S.page = 1; S.sort = '';
    writeUrl(); renderFocusBar(); if (S.summary) { renderStats(); renderNote(); } loadList();
  }
  function renderNote() {
    var s = S.summary, note = $('#ffNote'), btn = $('#ffNoteBtn');
    btn.style.display = '';
    if (S.focus) { note.classList.remove('on'); return; }                 /* a card filter is active: keep the page quiet */
    if (S.current) {
      $('#ffNoteTxt').textContent = 'Showing only the ' + nf(s.total) + ' manuscript' + (s.total === 1 ? '' : 's') + ' still in ' + STAGE_NAME[S.stage].toLowerCase() + ' today - this is how the previous report listed them.';
      btn.textContent = 'Show every manuscript received'; btn.setAttribute('data-act', 'all'); note.classList.add('on');
    } else if (s.still_in_stage === 0 && s.total > 0) {
      $('#ffNoteTxt').textContent = 'None of these ' + nf(s.total) + ' are still in ' + STAGE_NAME[S.stage].toLowerCase() + ' today - the previous report would have shown an empty list for this period.';
      btn.style.display = 'none'; note.classList.add('on');
    } else if (s.still_in_stage < s.total) {
      $('#ffNoteTxt').textContent = 'Only ' + nf(s.still_in_stage) + ' of these ' + nf(s.total) + ' are still in ' + STAGE_NAME[S.stage].toLowerCase() + ' today. The previous report listed just those; this one lists them all.';
      btn.textContent = 'Show only those ' + nf(s.still_in_stage); btn.setAttribute('data-act', 'cur'); note.classList.add('on');
    } else { note.classList.remove('on'); }
  }

  /* ------------------------------------------------------------ table */
  function deptCell(d) {
    if (!d) { return '<td class="dp"><span class="mu-zero">-</span></td>'; }
    var st = parseDT(d.start), day = st.getDate() + ' ' + MONTHS[st.getMonth()].slice(0, 3);
    var sub;
    if (d.open) { sub = '<span class="ff-open">in progress</span>'; }
    else if (d.end) { var m = Math.round((parseDT(d.end) - st) / 60000); sub = '<div class="s" title="First start to last finish in this department, waiting included">' + (m > 0 ? dur(m) + ' in dept' : 'finished') + '</div>'; }
    else { sub = ''; }
    return '<td class="dp"><div class="t"><i>' + day + '</i>' + fmtTime(d.start) + '</div>' + sub + '</td>';
  }
  var COLS = [['#', null], ['Manuscript', 'ref'], ['Journal', 'journal'], ['Stage', 'stage'], ['Received', 'received'], ['Due', 'due']];
  function renderTable() {
    var j = S.list, cols = j.columns;
    $('#ffCount').textContent = nf(j.total) + ' ' + (j.total === 1 ? 'row' : 'rows');
    var fn = $('#ffFocusN'); if (fn) { fn.textContent = nf(j.total) + (j.total === 1 ? ' row.' : ' rows.'); }
    if (!j.rows.length) {
      $('#ffTable').innerHTML = '<div class="ifx-empty"><i class="fa fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#c9d2dc"></i>' + (S.q ? 'Nothing matches "' + esc(S.q) + '".' : (S.focus ? 'Nothing here for this filter - good news.' : 'No manuscripts were received in this period for these filters.')) + '</div>';
      $('#ffInfo').textContent = '0 rows'; return;
    }
    var h = '<div class="ff-wrap"><table class="ff-table"><thead><tr>';
    COLS.forEach(function (c) {
      var cls = (c[1] === 'ref' ? 'ref ' : '') + (c[1] && S.sort === c[1] ? S.dir : '');
      h += '<th' + (c[1] ? ' data-sort="' + c[1] + '"' : '') + (cls.trim() ? ' class="' + cls.trim() + '"' : '') + '>' + c[0] + '</th>';
    });
    cols.forEach(function (c) { h += '<th class="dp" title="' + esc(c.name) + ' - first time it picked the file up">' + esc(c.name) + '</th>'; });
    h += '<th>Now at</th><th data-sort="pages" class="' + (S.sort === 'pages' ? S.dir : '') + '">Pages</th><th></th></tr></thead><tbody>';
    var start = (j.page - 1) * j.per_page;
    j.rows.forEach(function (r, i) {
      var ref = '<b>' + esc(r.ref || '-') + '</b>' + (S.cust === 'all' && r.customer ? '<small>' + esc(r.customer) + '</small>' : '') + (r.title ? '<small title="' + esc(r.title) + '">' + esc(r.title) + '</small>' : '');
      var now = r.current_stage ? '<span class="ifx-st ' + stClass(r.current_stage) + '">' + esc(r.current_stage) + '</span>' + (r.current_status ? '<small>' + esc(r.current_status) + '</small>' : '') : '-';
      h += '<tr data-id="' + r.id + '" data-stage="' + esc(r.stage) + '" tabindex="0"><td class="n">' + (start + i + 1) + '</td><td class="ref">' + ref + '</td>' +
        '<td>' + esc(r.journal_code || '-') + '<small>' + esc(r.journal_name || '') + '</small></td>' +
        '<td><span class="ifx-st ' + stClass(r.stage) + '">' + esc(r.stage) + '</span>' + (r.source === 'Archive' ? '<small>archive</small>' : '') + '</td>' +
        '<td>' + fmtDate(r.received) + '</td><td>' + fmtDate(r.due) + '</td>';
      cols.forEach(function (c, ci) { h += (!r.picked_up && ci === 0) ? '<td class="dp">' + stateFlag(r) + '</td>' : deptCell(r.depts[c.key]); });
      h += '<td class="ff-now">' + now + '</td><td class="c">' + (r.pages == null ? '<span class="mu-zero">-</span>' : r.pages) + '</td><td class="ff-go"><i class="fa fa-chevron-right"></i></td></tr>';
    });
    h += '</tbody></table></div>';
    $('#ffTable').innerHTML = h;
    $('#ffInfo').textContent = 'Showing ' + nf(start + 1) + '–' + nf(start + j.rows.length) + ' of ' + nf(j.total) + ' · click a row to see the whole journey';
  }
  function renderPager() {
    var j = S.list, page = j.page, pages = j.pages;
    if (pages <= 1) { $('#ifxPg').innerHTML = ''; return; }
    var b = function (label, p, cls, dis) { return '<button type="button" data-p="' + p + '"' + (cls ? ' class="' + cls + '"' : '') + (dis ? ' disabled' : '') + '>' + label + '</button>'; };
    var h = b('‹', page - 1, '', page <= 1), set = {};
    [1, 2, page - 1, page, page + 1, pages - 1, pages].forEach(function (p) { if (p >= 1 && p <= pages) { set[p] = 1; } });
    var last = 0;
    Object.keys(set).map(Number).sort(function (a, b2) { return a - b2; }).forEach(function (p) { if (last && p - last > 1) { h += '<button type="button" disabled>…</button>'; } h += b(p, p, p === page ? 'cur' : '', false); last = p; });
    $('#ifxPg').innerHTML = h + b('›', page + 1, '', page >= pages);
  }

  /* ------------------------------------------------------------ journey drawer */
  function openTrace(id, focus, op) {
    S.trace = id; journeyFocus = focus || null; opener = op || null;
    var dr = $('#ifxDrawer'); dr.classList.add('on'); dr.setAttribute('aria-hidden', 'false'); $('#ifxScrim').classList.add('on'); document.body.classList.add('ifx-lock');
    $('#ifxDTitle').textContent = 'Journey'; $('#ifxDSub').textContent = ''; $('#ifxDCnt').textContent = '...';
    $('#ifxDBody').innerHTML = '<div style="padding:20px"><div class="mu-skel"></div><div class="mu-skel"></div><div class="mu-skel"></div><div class="mu-skel"></div></div>';
    writeUrl();
    api({ action: 'trace', id: id }, 'trace').then(function (j) {
      if (S.trace !== id) { return; }
      if (!j.ok) { $('#ifxDBody').innerHTML = '<div style="padding:20px">' + failBox(j, false) + '</div>'; return; }
      renderTrace(j);
    }).catch(function (e) { if (e && e.name === 'AbortError') { return; } $('#ifxDBody').innerHTML = '<div style="padding:20px">' + failBox({ error: 'Could not reach the server.' }, false) + '</div>'; });
    setTimeout(function () { $('#ifxClose').focus(); }, 60);
  }
  function closeTrace() {
    if (S.trace == null) { return; }
    if (ctl.trace) { ctl.trace.abort(); }
    S.trace = null; journeyFocus = null; $('#ifxDrawer').classList.remove('on'); $('#ifxDrawer').setAttribute('aria-hidden', 'true'); $('#ifxScrim').classList.remove('on'); document.body.classList.remove('ifx-lock');
    writeUrl(); if (opener && document.body.contains(opener)) { opener.focus(); }
  }
  function renderTrace(j) {
    var a = j.article;
    $('#ifxDTitle').textContent = a.ref || 'Journey';
    $('#ifxDSub').textContent = [a.customer, a.journal_code, a.source === 'Archive' ? 'archived' : ''].filter(Boolean).join(' · ');
    $('#ifxDCnt').textContent = nf(j.steps_total) + ' step' + (j.steps_total === 1 ? '' : 's');
    var h = '<div class="ff-trh"><h3>' + esc(a.title || a.ref) + '</h3><div class="ff-chips">' +
      '<span class="ff-chip">Now <b>' + esc(a.current_stage || '-') + '</b>' + (a.current_status ? ' ' + statusPill(a.current_status) : '') + '</span>' +
      '<span class="ff-chip">Received <b>' + fmtDate(a.received) + '</b></span><span class="ff-chip">Due <b>' + fmtDate(a.due) + '</b></span><span class="ff-chip">Logged <b>' + fmtDT(a.logged_at) + '</b></span></div>';
    if (a.working_parts && a.working_parts.length) { h += '<div class="ifx-wp" style="margin-top:8px">' + a.working_parts.map(function (p) { return '<span class="label label-' + esc(p.k) + '">' + esc(p.t) + '</span>'; }).join('') + '</div>'; }
    h += '<div class="ff-drawer-links"><a class="mu-btn mu-btn-sm" target="_blank" rel="noopener" href="' + esc(a.links.job_card) + '"><i class="fa fa-clock-o"></i> Job card</a>' +
         '<a class="mu-btn mu-btn-sm primary" target="_blank" rel="noopener" href="' + esc(a.links.send_instructions) + '">Send instructions</a></div></div>';
    if (!j.stages.length) { h += '<div class="ifx-empty">No department has worked on this manuscript yet.</div>'; }
    j.stages.forEach(function (g) {
      var el = null;
      var focus = journeyFocus && g.stage === journeyFocus;
      var span = (g.first && g.last && !g.open) ? dur(Math.round((parseDT(g.last) - parseDT(g.first)) / 60000)) : null;
      h += '<section class="ff-stg' + (focus ? ' focus' : '') + '" data-stage="' + esc(g.stage) + '"><header><span class="ifx-st ' + stClass(g.stage) + '">' + esc(g.stage) + '</span>' +
        '<span class="m">' + (g.received ? 'received <b>' + fmtShort(g.received) + '</b>' + (g.due ? ' · due <b>' + fmtShort(g.due) + '</b>' : '') + ' · ' : '') + 'started <b>' + fmtDT(g.first) + '</b></span>' +
        '<span class="m r">' + (g.open ? '<span class="ff-open">in progress</span>' : (span ? 'start to finish <b>' + span + '</b>' : '')) + '</span></header><ol class="ff-steps">';
      g.steps.forEach(function (s) {
        var when = fmtDT(s.start) + (s.end ? ' → ' + fmtTime(s.end) : '');
        var bits = [];
        if (s.minutes != null) { bits.push('took <b>' + dur(s.minutes) + '</b>'); } else if (!s.end) { bits.push('<em>in progress</em>'); }
        if (s.wait_min != null && s.wait_min > 0) { bits.push('waited ' + dur(s.wait_min) + ' after the previous step'); }
        if (s.pages) { bits.push(esc(s.pages) + ' pages'); }
        h += '<li' + (s.end ? '' : ' class="open"') + '><div class="a"><b>' + esc(s.dept) + '</b>' + (s.user ? '<span class="u">' + esc(s.user) + '</span>' : '') + statusPill(s.status) + '</div>' +
          '<div class="b">' + when + (bits.length ? ' · ' + bits.join(' · ') : '') + '</div>' + (s.comment ? '<div class="cm">' + esc(s.comment) + '</div>' : '') + '</li>';
      });
      h += '</ol></section>';
    });
    $('#ifxDBody').innerHTML = h;
    var f = $('#ifxDBody .ff-stg.focus'); if (f) { setTimeout(function () { $('#ifxDBody').scrollTop = Math.max(0, f.offsetTop - 80); }, 60); }
  }

  /* ------------------------------------------------------------ controls */
  function buildMeta(meta) {
    S.meta = meta;
    var sel = $('#ifxCust'), h = '';
    if (meta.all_customers_allowed) { h += '<option value="all">All customers</option>'; }
    meta.customers.forEach(function (c) { h += '<option value="' + c.id + '">' + esc(c.name) + '</option>'; });
    sel.innerHTML = h;
    var has = function (el, v) { return [].some.call(el.options, function (o) { return o.value === String(v); }); };
    if (!has(sel, S.cust)) { var saved = null; try { saved = localStorage.getItem('ffCust'); } catch (e) {} S.cust = has(sel, saved) ? saved : sel.options[0].value; }
    sel.value = S.cust;
    var pl = $('#ffPlat'); pl.innerHTML = '<option value="all">All platforms</option>' + meta.platforms.map(function (p) { return '<option value="' + esc(p) + '">' + esc(p) + '</option>'; }).join('');
    if (!has(pl, S.platform)) { S.platform = 'all'; } pl.value = S.platform;
    $('#ifxMM').innerHTML = MONTHS.map(function (m, i) { return '<option value="' + (i + 1) + '">' + m + '</option>'; }).join('');
    var yh = ''; for (var y = today.getFullYear(); y >= today.getFullYear() - 6; y--) { yh += '<option value="' + y + '">' + y + '</option>'; }
    $('#ifxYY').innerHTML = yh; $('#ifxMM').value = S.mm; $('#ifxYY').value = S.my;
    $('#ffCur').checked = S.current;
    [].forEach.call(document.querySelectorAll('#ffStage button'), function (b) { b.classList.toggle('on', b.getAttribute('data-s') === S.stage); });
  }
  function loadJournals(keep) {
    var sel = $('#ffJ');
    if (S.cust === 'all') { S.j = 'all'; sel.innerHTML = '<option value="all">All journals</option>'; sel.disabled = true; sel.title = 'Choose a customer to filter by journal'; return Promise.resolve(); }
    sel.disabled = false; sel.title = '';
    return apiPlain({ action: 'journals', cust_id: S.cust }).then(function (j) {
      var opts = '<option value="all">All journals</option>' + (j.journals || []).map(function (x) { return '<option value="' + x.id + '">' + esc(x.code) + ' - ' + esc(x.name) + '</option>'; }).join('');
      sel.innerHTML = opts;
      if (!keep || ![].some.call(sel.options, function (o) { return o.value === String(S.j); })) { S.j = 'all'; }
      sel.value = S.j;
    }).catch(function () {});
  }
  function applyModeUi() {
    $('#ifxDaysField').style.display = S.mode === 'days' ? '' : 'none';
    $('#ifxMonthField').style.display = S.mode === 'month' ? '' : 'none';
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.checked = r.value === S.mode; });
  }
  function toDmy(s) { var d = parse(s); return d ? pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear() : ''; }
  function setRangeUi() { var el = $('#ifxRange'); el.setAttribute('data-from', toDmy(S.from)); el.setAttribute('data-to', toDmy(S.to)); }
  var changeT;
  function changed(resetPage) { clearTimeout(changeT); changeT = setTimeout(function () { if (resetPage !== false) { S.page = 1; } if (S.trace != null) { closeTrace(); } writeUrl(); loadAll(); }, 120); }

  function bind() {
    [].forEach.call(document.querySelectorAll('#ffStage button'), function (b) { b.addEventListener('click', function () {
      S.stage = this.getAttribute('data-s'); [].forEach.call(document.querySelectorAll('#ffStage button'), function (x) { x.classList.toggle('on', x === b); }); S.list = null; S.sort = ''; changed(); }); });
    $('#ifxCust').addEventListener('change', function () { S.cust = this.value; S.j = 'all'; loadJournals(false).then(function () { changed(); }); });
    $('#ffJ').addEventListener('change', function () { S.j = this.value; changed(); });
    $('#ffPlat').addEventListener('change', function () { S.platform = this.value; changed(); });
    $('#ffCur').addEventListener('change', function () { S.current = this.checked; changed(); });
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.addEventListener('change', function () { if (this.checked) { S.mode = this.value; applyModeUi(); changed(); } }); });
    $('#ifxMM').addEventListener('change', function () { S.mm = +this.value; changed(); });
    $('#ifxYY').addEventListener('change', function () { S.my = +this.value; changed(); });
    $('#ifxRange').addEventListener('dvr:change', function (e) {
      var fd = /^(\d{2})-(\d{2})-(\d{4})$/.exec(e.detail.from || ''), td = /^(\d{2})-(\d{2})-(\d{4})$/.exec(e.detail.to || ''); if (!fd || !td) { return; }
      var nf2 = fd[3] + '-' + fd[2] + '-' + fd[1], nt = td[3] + '-' + td[2] + '-' + td[1];
      if ((parse(nt) - parse(nf2)) / 86400000 > 399) { toast('Please pick 400 days or fewer.'); return; }
      S.from = nf2; S.to = nt; changed();
    });
    $('#ifxRefresh').addEventListener('click', function () { loadAll(); });
    $('#ifxLink').addEventListener('click', function () {
      var done = function () { toast('Link copied'); };
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(location.href).then(done, function () { toast(location.href); }); } else { toast('Copy the address bar to share this view'); }
    });
    $('#ifxStats').addEventListener('click', function (e) { var b = e.target.closest('button[data-focus]'); if (b) { setFocus(b.getAttribute('data-focus')); } });
    $('#ffNote').parentNode.addEventListener('click', function (e) { if (e.target && e.target.id === 'ffFocusClear') { setFocus(S.focus); } });
    $('#ffNoteBtn').addEventListener('click', function () { S.current = this.getAttribute('data-act') === 'cur'; $('#ffCur').checked = S.current; changed(); });
    var qT; $('#ifxQ').addEventListener('input', function () { var v = this.value.trim(); clearTimeout(qT); qT = setTimeout(function () { S.q = v; S.page = 1; loadList(); }, 280); });
    $('#ifxPer').addEventListener('change', function () { S.per = +this.value; S.page = 1; loadList(); });
    $('#ifxPg').addEventListener('click', function (e) { var b = e.target.closest('button[data-p]'); if (b && !b.disabled) { S.page = +b.getAttribute('data-p'); loadList(); } });
    $('#ffTable').addEventListener('click', function (e) {
      var th = e.target.closest('th[data-sort]');
      if (th) { var k = th.getAttribute('data-sort'); if (S.sort === k) { S.dir = S.dir === 'asc' ? 'desc' : 'asc'; } else { S.sort = k; S.dir = 'asc'; } S.page = 1; loadList(); return; }
      var tr = e.target.closest('tr[data-id]'); if (tr) { openTrace(+tr.getAttribute('data-id'), tr.getAttribute('data-stage'), tr); return; }
      if (e.target.closest('[data-retry]')) { loadList(); }
    });
    $('#ffTable').addEventListener('keydown', function (e) { if (e.key === 'Enter') { var tr = e.target.closest('tr[data-id]'); if (tr) { openTrace(+tr.getAttribute('data-id'), tr.getAttribute('data-stage'), tr); } } });
    $('#ifxClose').addEventListener('click', closeTrace); $('#ifxScrim').addEventListener('click', closeTrace);
    document.addEventListener('keydown', function (e) {
      if (S.trace == null) { return; }
      if (e.key === 'Escape') { closeTrace(); return; }
      if (e.key === 'Tab') {
        var f = [].filter.call($('#ifxDrawer').querySelectorAll('button:not(:disabled),a[href],input,select'), function (x) { return x.offsetParent !== null; });
        if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    $('#ifxExport').addEventListener('click', function () { var extra = { action: 'export' }; if (S.sort) { extra.sort = S.sort; extra.dir = S.dir; } if (S.q) { extra.q = S.q; } if (S.focus) { extra.focus = S.focus; } window.location.href = API + '?' + new URLSearchParams(baseParams(extra)).toString(); });
  }

  /* ------------------------------------------------------------ start */
  var openFromUrl = readUrl();
  setRangeUi(); applyModeUi(); bind();
  apiPlain({ action: 'meta' }).then(function (m) {
    if (!m || !m.ok) { $('#ffTable').innerHTML = failBox(m || { error: 'Could not load the report.' }, false); $('#ifxCust').innerHTML = '<option>-</option>'; return; }
    buildMeta(m); renderFocusBar();
    return loadJournals(true).then(function () { writeUrl(); loadAll(); if (openFromUrl) { openTrace(openFromUrl.id, openFromUrl.stage, null); } });
  }).catch(function () { $('#ffTable').innerHTML = failBox({ error: 'Could not reach the server.' }, false); });
})();
    </script>
    <?php mu_foot(); ?>
  </body>
</html>

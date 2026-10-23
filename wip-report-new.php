<?php include "includes/includes.php";
include "includes/modern-ui.php";
/* ============================================================================
 * Work in Progress                                        /workflow-new/wipreport   (also /wipreport-new)
 * Same report, same counting rules as before; every number comes from api/wip.php (no page reloads).
 * The previous version stays available at /wipreport-old (wipreport.php).
 * ========================================================================== */

/* old links (?date_wise=d&wfrom_dt=..&wto_dt=..&wcust_id=42&dept=10&wgetaction=1) open the same view here */
if (isset($_GET['wcust_id']) || isset($_GET['date_wise']) || isset($_GET['wgetaction'])) {
    $q = array();
    $c = isset($_GET['wcust_id']) ? (string)$_GET['wcust_id'] : '';
    if ($c === 'all' || ctype_digit($c)) { $q['cust'] = $c; }
    $dp = isset($_GET['dept']) ? (string)$_GET['dept'] : '';
    if ($dp === 'all' || ctype_digit($dp)) { $q['dept'] = $dp; }
    $dw = isset($_GET['date_wise']) ? (string)$_GET['date_wise'] : '';
    if ($dw === 'm' && preg_match('/^(\d{2})-(\d{4})$/', isset($_GET['mfrom_dt']) ? (string)$_GET['mfrom_dt'] : '', $m)) {
        $q['month'] = $m[2] . '-' . $m[1];
    } elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', isset($_GET['wfrom_dt']) ? (string)$_GET['wfrom_dt'] : '', $a)
           && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', isset($_GET['wto_dt']) ? (string)$_GET['wto_dt'] : '', $b)
           && checkdate((int)$a[2], (int)$a[1], (int)$a[3]) && checkdate((int)$b[2], (int)$b[1], (int)$b[3])) {
        $q['from'] = $a[3] . '-' . $a[2] . '-' . $a[1];
        $q['to']   = $b[3] . '-' . $b[2] . '-' . $b[1];
    }
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: wipreport' . ($q ? '?' . http_build_query($q) : ''));
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
/* ---------- WIP specifics ---------- */
.ifx-filters .mu-field select{min-width:150px;}
.ifx-stat .delta em.late{background:#fbe5e5;color:#d64545;}
.ifx-stat .delta em.ok{background:#e3f6ec;color:#1e9e61;}
.ifx-grid thead th.od{background:#fdeceb;min-width:92px;} .ifx-grid thead th.od span{color:#d64545;font-size:12px;}
.ifx-grid thead th.od small{color:#d64545;}
.ifx-grid td.od{border-right:2px solid #f3c9c6;}
.ifx-grid .ifx-cell.od{background:color-mix(in srgb,#d64545 calc(var(--i) * 40%),#fff);color:#8f2020;}
.ifx-grid .ifx-cell.od:hover,.ifx-grid .ifx-cell.od:focus-visible{background:#d64545;color:#fff;}
.ifx-grid tr.sum td.od{background:#fdeceb;}
.ifx-grid tr.sum .ifx-cell.od{background:transparent;color:#d64545;font-weight:800;}
.ifx-grid tr.sum .ifx-cell.od:hover{background:#d64545;color:#fff;}
.ifx-late{display:inline-block;margin-top:3px;padding:1px 8px;border-radius:20px;background:#fbe5e5;color:#d64545;font-size:11px;font-weight:700;}
.ifx-dept{display:inline-block;padding:3px 10px;border-radius:20px;background:#eef1f5;color:#4a5865;font-size:11.5px;font-weight:700;}
.ifx-hint{margin:-4px 0 12px;color:var(--mute);font-size:12.5px;}
.ifx-hint b{color:#d64545;}
@media(max-width:700px){.ifx-dtable th:nth-child(8),.ifx-dtable td:nth-child(8){display:table-cell !important;}}   /* keep Status on phones; the shared sheet hides columns 8-11 */
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php"); ?>

      <div class="mu-root ifx" id="ifx">
        <div class="mu-hero">
          <div>
            <small>Reports</small>
            <h1>Work in Progress</h1>
            <p>Open articles, revisions, finals and issues by due date, with everything already overdue</p>
          </div>
          <div class="mu-hero-right">
            <span class="ifx-live" id="ifxLive">LIVE DATA</span>
            <div class="mu-chip"><small>Calculated</small><b id="ifxCalc">-</b></div>
            <a class="ifx-hero-link" href="wipreport-old">Previous version</a>
          </div>
        </div>

        <section class="mu-panel mu-filter-panel">
          <div class="ifx-filters">
            <div class="mu-field"><label>View by</label>
              <div class="mu-seg" id="ifxMode">
                <label><input type="radio" name="ifxmode" value="days" checked><span>Days</span></label>
                <label><input type="radio" name="ifxmode" value="month"><span>Month</span></label>
              </div></div>
            <div class="mu-field" style="min-width:200px;"><label for="ifxCust">Customer</label>
              <select id="ifxCust"><option>Loading...</option></select></div>
            <div class="mu-field" style="min-width:180px;"><label for="ifxDept">Department</label>
              <select id="ifxDept"><option value="all">All departments</option></select></div>
            <div class="mu-field" id="ifxDaysField" style="min-width:250px;"><label>Due between</label>
              <div class="dvr" id="ifxRange" data-from-name="from" data-to-name="to"></div></div>
            <div class="mu-field" id="ifxMonthField" style="display:none;"><label>Month</label>
              <div class="ifx-month"><select id="ifxMM" aria-label="Month"></select><select id="ifxYY" aria-label="Year"></select></div></div>
            <div class="ifx-actions">
              <button type="button" class="mu-btn" id="ifxRefresh" title="Reload the numbers"><i class="fa fa-refresh"></i> Refresh</button>
              <button type="button" class="mu-btn" id="ifxLink" title="Copy a link to this exact view"><i class="fa fa-link"></i> Copy link</button>
            </div>
          </div>
        </section>

        <div class="ifx-stats" id="ifxStats"></div>

        <section class="mu-panel">
          <h3><i class="fa fa-th"></i> Due by day
            <span class="mu-right"><span class="ifx-legend">fewer <i></i> more</span> &nbsp;&middot;&nbsp; click a number to open the articles</span></h3>
          <p class="ifx-hint" id="ifxHint"></p>
          <div id="ifxGridWrap"><div class="ifx-skel-grid"><i></i><i></i><i></i><i></i><i></i></div></div>
        </section>
      </div>

      <!-- drawer -->
      <div class="ifx-scrim" id="ifxScrim"></div>
      <aside class="ifx-drawer" id="ifxDrawer" role="dialog" aria-modal="true" aria-labelledby="ifxDTitle" aria-hidden="true">
        <div class="ifx-dhead">
          <button type="button" class="ifx-dbtn" id="ifxPrev" title="Previous day" aria-label="Previous day"><i class="fa fa-chevron-left"></i></button>
          <button type="button" class="ifx-dbtn" id="ifxNext" title="Next day" aria-label="Next day"><i class="fa fa-chevron-right"></i></button>
          <div class="grow"><h2 id="ifxDTitle">-</h2><div class="sub" id="ifxDSub"></div></div>
          <span class="cnt" id="ifxDCnt">0</span>
          <button type="button" class="ifx-dbtn" id="ifxClose" title="Close (Esc)" aria-label="Close"><i class="fa fa-times"></i></button>
        </div>
        <div class="ifx-tools">
          <input type="search" class="mu-search" id="ifxQ" placeholder="Search article, journal, customer, department" aria-label="Search">
          <select id="ifxPer" aria-label="Rows per page"><option value="25">25 rows</option><option value="50" selected>50 rows</option><option value="100">100 rows</option><option value="200">200 rows</option></select>
          <span class="sp"></span>
          <button type="button" class="mu-btn" id="ifxExport"><i class="fa fa-file-excel-o"></i> Export CSV</button>
        </div>
        <div class="ifx-dbody" id="ifxDBody"></div>
        <div class="ifx-dfoot"><span class="info" id="ifxDInfo"></span><div class="ifx-pg" id="ifxPg"></div></div>
      </aside>
      <div class="ifx-toast" id="ifxToast" role="status"></div>

      <?php echo footer(); ?>
    </div>

    <?php echo scripts(); ?>
    <script src="dv-rangepicker.js?v=2"></script>
    <script>
(function () {
  'use strict';
  var API = 'api/wip.php';
  var STAGES = [
    { k: 'FP',  name: 'First proof', c: '#1b6ca8', ic: 'fa-file-text-o' },
    { k: 'REV', name: 'Revisions',   c: '#e8742a', ic: 'fa-repeat' },
    { k: 'FIN', name: 'Finals',      c: '#1e9e61', ic: 'fa-check-square-o' },
    { k: 'PMC', name: 'PMC',        c: '#c28a00', ic: 'fa-files-o' },
    { k: 'JSTAGE', name: 'JSTAGE',    c: '#d6881d', ic: 'fa-globe' },
    { k: 'ISS', name: 'Issues',      c: '#7a5af8', ic: 'fa-book' }
  ];
  var RS_ON = <?php echo json_encode(rs_row_map()); ?>;   /* which stages the reports show: $report_stage_types (includes/report_stages.php) */
  STAGES = STAGES.filter(function (s) { return RS_ON[s.k]; });
  var STAGE_NAME = { FP: 'First proof', REV: 'Revisions', FIN: 'Finals', PMC: 'PMC', JSTAGE: 'JSTAGE', ISS: 'Issues', ALL: 'All work in progress' };
  var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var WD = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
  var parse = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
  var addDays = function (d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; };
  var today = new Date(); today.setHours(0, 0, 0, 0);
  var todayIso = iso(today), yestIso = iso(addDays(today, -1));
  var fmtShort = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) : ''; };
  var fmtLong = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear() : ''; };
  var fmtDate = function (s) { return (!s || s === '0000-00-00') ? '-' : fmtLong(s); };
  var nf = function (n) { return Number(n || 0).toLocaleString(); };

  /* ------------------------------------------------------------ state */
  var S = {
    mode: 'days', cust: 'all', dept: 'all', from: iso(addDays(today, -5)), to: iso(addDays(today, 2)), my: today.getFullYear(), mm: today.getMonth() + 1,
    meta: null, data: null,
    dr: null,                                   /* { stage, bucket: 'over' | 'range' | 'YYYY-MM-DD' } */
    page: 1, per: 50, sort: '', dir: 'asc', q: ''
  };
  var ctl = { matrix: null, detail: null };
  var drawerOpener = null;

  function range() {
    if (S.mode === 'month') {
      var f = new Date(S.my, S.mm - 1, 1), t = new Date(S.my, S.mm, 0);
      if (S.my === today.getFullYear() && S.mm === today.getMonth() + 1) { t = new Date(today); }       /* the running month stops at today, as before */
      return { from: iso(f), to: iso(t) };
    }
    return { from: S.from, to: S.to };
  }
  function custName() {
    if (S.cust === 'all') { return 'All customers'; }
    var c = (S.meta ? S.meta.customers : []).filter(function (x) { return String(x.id) === String(S.cust); })[0];
    return c ? c.name : 'Customer ' + S.cust;
  }
  function deptName() {
    if (S.dept === 'all') { return ''; }
    var d = (S.meta ? S.meta.departments : []).filter(function (x) { return String(x.id) === String(S.dept); })[0];
    return d ? d.name : 'Department ' + S.dept;
  }
  /* what a drawer bucket means as a due-date window */
  function bucketWindow(b) {
    var r = range();
    if (b === 'over') { return { from: r.from, to: yestIso }; }
    if (b === 'range') { return { from: r.from, to: r.to }; }
    return { from: b, to: b };
  }

  /* ------------------------------------------------------------ url <-> state */
  function readUrl() {
    var p = new URLSearchParams(location.search);
    if (p.get('cust')) { S.cust = p.get('cust'); }
    if (p.get('dept')) { S.dept = p.get('dept'); }
    var mo = /^(\d{4})-(\d{2})$/.exec(p.get('month') || '');
    if (mo) { S.mode = 'month'; S.my = +mo[1]; S.mm = +mo[2]; }
    else if (parse(p.get('from')) && parse(p.get('to'))) { S.from = p.get('from'); S.to = p.get('to'); }
    var h = /^#(FP|REV|FIN|PMC|JSTAGE|ISS|ALL):(over|range|\d{4}-\d{2}-\d{2})$/.exec(location.hash || '');
    return h ? { stage: h[1], bucket: h[2] } : null;
  }
  function writeUrl() {
    var p = new URLSearchParams();
    p.set('cust', S.cust); p.set('dept', S.dept);
    if (S.mode === 'month') { p.set('month', S.my + '-' + pad(S.mm)); } else { p.set('from', S.from); p.set('to', S.to); }
    var h = S.dr ? '#' + S.dr.stage + ':' + S.dr.bucket : '';
    try { history.replaceState(null, '', location.pathname + '?' + p.toString() + h); } catch (e) {}
    try { localStorage.setItem('wipCust', S.cust); localStorage.setItem('wipDept', S.dept); } catch (e) {}
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
  function filterParams(extra) {
    var p = { cust_id: S.cust, dept: S.dept };
    for (var k in extra) { p[k] = extra[k]; }
    return p;
  }

  /* ------------------------------------------------------------ matrix */
  function loadMatrix() {
    var r = range();
    S.loading = true;
    $('#ifxStats').innerHTML = [1, 2, 3, 4, 5].map(function () { return '<div class="ifx-stat skel"><div class="top">&nbsp;</div><b>0</b><div class="delta">&nbsp;</div></div>'; }).join('');
    if (!S.data) { $('#ifxGridWrap').innerHTML = '<div class="ifx-skel-grid"><i></i><i></i><i></i><i></i><i></i></div>'; }
    $('#ifxLive').textContent = 'UPDATING';
    api(filterParams({ action: 'matrix', from: r.from, to: r.to }), 'matrix').then(function (j) {
      S.loading = false; $('#ifxLive').textContent = 'LIVE DATA';
      if (!j || !j.ok) { $('#ifxStats').innerHTML = ''; $('#ifxGridWrap').innerHTML = failBox(j, true); return; }
      S.data = j;
      $('#ifxCalc').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' · ' + (j.ms / 1000).toFixed(2) + 's';
      renderStats(); renderGrid(); renderHint();
    }).catch(function (e) { if (e && e.name === 'AbortError') { return; } S.loading = false; $('#ifxLive').textContent = 'LIVE DATA'; $('#ifxGridWrap').innerHTML = failBox({ error: 'Could not reach the server.' }, true); });
  }

  function countUp(el, to) {
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.textContent = nf(to);                                             /* correct value first: never depends on animation frames */
    if (reduce || to === 0 || document.visibilityState !== 'visible') { return; }
    var t0 = null, dur = 650, done = false;
    el.textContent = nf(0);
    setTimeout(function () { done = true; el.textContent = nf(to); }, dur + 250);
    (function step(ts) { if (done) { return; } if (t0 === null) { t0 = ts; } var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = nf(Math.round(to * e)); if (p < 1) { requestAnimationFrame(step); } })(performance.now());
  }
  function overdueChip(n) {
    return n > 0 ? '<em class="late">' + nf(n) + ' overdue</em>' : '<em class="ok">none overdue</em>';
  }
  function renderStats() {
    var d = S.data, html = '';
    STAGES.forEach(function (s) {
      var r = d.rows[s.k];
      html += '<button type="button" class="ifx-stat" data-open="' + s.k + '" data-bucket="range" style="--c:' + s.c + '"><div class="top"><i class="fa ' + s.ic + '"></i>' + s.name + '</div><b data-n="' + r.total + '">0</b><div class="delta">' + overdueChip(r.prev) + 'in range</div></button>';
    });
    html += '<button type="button" class="ifx-stat" data-open="ALL" data-bucket="range" style="--c:#14304a"><div class="top"><i class="fa fa-tasks"></i>Total in progress</div><b data-n="' + d.totals.grand + '">0</b><div class="delta">' + overdueChip(d.totals.prev) + 'in range</div></button>';
    var box = $('#ifxStats'); box.innerHTML = html;
    [].forEach.call(box.querySelectorAll('b[data-n]'), function (b) { countUp(b, +b.getAttribute('data-n')); });
  }
  function renderHint() {
    var d = S.data, r = range(), parts = [];
    if (d.overdue_range.to) { parts.push('<b>Overdue</b> = due from ' + fmtLong(r.from) + ' up to yesterday (' + fmtLong(d.overdue_range.to) + ') and still open'); }
    else { parts.push('Nothing in this range is overdue yet'); }
    parts.push('<b style="color:var(--navy)">Total</b> = everything due between ' + fmtLong(r.from) + ' and ' + fmtLong(r.to));
    var dn = deptName(); if (dn) { parts.push('department ' + esc(dn) + ' (issues are not split by department)'); }
    $('#ifxHint').innerHTML = parts.join(' &middot; ') + '.';
  }

  function renderGrid() {
    var d = S.data, days = d.days, n = days.length;
    var h = '<div class="ifx-scroll"><table class="ifx-grid" aria-label="Work in progress by stage and due date"><thead><tr><th class="ifx-corner">Stage</th>';
    h += '<th class="od"><span>Overdue</span><small>before today</small></th>';
    days.forEach(function (ds, i) {
      var dt = parse(ds), dow = dt.getDay(), first = (i === 0 || dt.getDate() === 1);
      h += '<th class="' + (dow === 0 || dow === 6 ? 'we ' : '') + (ds === todayIso ? 'now' : '') + '"><em>' + (first ? MONTHS[dt.getMonth()].slice(0, 3) : '') + '</em><span>' + dt.getDate() + '</span><small>' + (ds === todayIso ? 'Today' : WD[dow]) + '</small></th>';
    });
    h += '<th class="ifx-tot"><em></em><span>Total</span><small>in range</small></th></tr></thead><tbody>';
    var cell = function (v, stage, bucket, cls, inten, label) {
      return v > 0 ? '<td class="' + cls + '"><button type="button" class="ifx-cell' + (cls.indexOf('od') > -1 ? ' od' : '') + '" style="--i:' + inten + '" data-open="' + stage + '" data-bucket="' + bucket + '" aria-label="' + label + ', ' + v + '">' + nf(v) + '</button></td>'
                    : '<td class="z ' + cls + '">0</td>';
    };
    STAGES.forEach(function (s) {
      var row = d.rows[s.k], max = Math.max.apply(null, row.counts.concat([row.prev, 1]));
      var inten = function (v) { return (0.12 + 0.88 * (v / max)).toFixed(3); };
      h += '<tr style="--c:' + s.c + '"><th class="ifx-stage"><i></i>' + s.k + '<small>' + s.name + '</small></th>';
      h += cell(row.prev, s.k, 'over', 'od', inten(row.prev), s.name + ' overdue');
      row.counts.forEach(function (v, i) { h += cell(v, s.k, days[i], days[i] === todayIso ? 'now' : '', inten(v), s.name + ', due ' + fmtLong(days[i])); });
      h += '<td class="ifx-tot">' + (row.total > 0 ? '<button type="button" class="ifx-cell" data-open="' + s.k + '" data-bucket="range" aria-label="' + s.name + ' total ' + row.total + '">' + nf(row.total) + '</button>' : '<span style="color:#c3ccd6">0</span>') + '</td></tr>';
    });
    h += '<tr class="sum"><th class="ifx-stage" style="--c:#14304a"><i style="background:#14304a"></i>TOTAL<small>all stages</small></th>';
    h += d.totals.prev > 0 ? '<td class="od"><button type="button" class="ifx-cell od" data-open="ALL" data-bucket="over" aria-label="All overdue ' + d.totals.prev + '">' + nf(d.totals.prev) + '</button></td>' : '<td class="od z">0</td>';
    d.totals.per_day.forEach(function (v, i) {
      h += '<td class="' + (days[i] === todayIso ? 'now' : '') + '">' + (v > 0 ? '<button type="button" class="ifx-cell" data-open="ALL" data-bucket="' + days[i] + '" aria-label="All stages, due ' + fmtLong(days[i]) + ', ' + v + '">' + nf(v) + '</button>' : '<span style="color:#c3ccd6">0</span>') + '</td>';
    });
    h += '<td class="ifx-tot">' + (d.totals.grand > 0 ? '<button type="button" class="ifx-cell" data-open="ALL" data-bucket="range" aria-label="Grand total ' + d.totals.grand + '">' + nf(d.totals.grand) + '</button>' : '0') + '</td></tr>';
    h += '</tbody></table></div>';
    $('#ifxGridWrap').innerHTML = h;
  }

  /* ------------------------------------------------------------ drawer */
  function drawerParams(extra) {
    var w = bucketWindow(S.dr.bucket), p = filterParams({ stage: S.dr.stage, from: w.from, to: w.to });
    if (S.sort) { p.sort = S.sort; p.dir = S.dir; }
    if (S.q) { p.q = S.q; }
    for (var k in extra) { p[k] = extra[k]; }
    return p;
  }
  function openDrawer(stage, bucket, opener) {
    S.dr = { stage: stage, bucket: bucket || 'range' }; S.page = 1; S.sort = ''; S.dir = 'asc'; S.q = ''; $('#ifxQ').value = '';
    drawerOpener = opener || null;
    var dr = $('#ifxDrawer'); dr.classList.add('on'); dr.setAttribute('aria-hidden', 'false'); $('#ifxScrim').classList.add('on'); document.body.classList.add('ifx-lock');
    writeUrl(); loadDetail(); setTimeout(function () { $('#ifxClose').focus(); }, 60);
  }
  function closeDrawer() {
    if (!S.dr) { return; }
    if (ctl.detail) { ctl.detail.abort(); }
    S.dr = null; $('#ifxDrawer').classList.remove('on'); $('#ifxDrawer').setAttribute('aria-hidden', 'true'); $('#ifxScrim').classList.remove('on'); document.body.classList.remove('ifx-lock');
    writeUrl(); if (drawerOpener && document.body.contains(drawerOpener)) { drawerOpener.focus(); }
  }
  function isDay(b) { return /^\d{4}-\d{2}-\d{2}$/.test(b || ''); }
  function dayNav() {
    var one = S.dr && isDay(S.dr.bucket) && S.data;
    $('#ifxPrev').style.visibility = $('#ifxNext').style.visibility = one ? 'visible' : 'hidden';
    if (!one) { return; }
    var i = S.data.days.indexOf(S.dr.bucket);
    $('#ifxPrev').disabled = i <= 0; $('#ifxNext').disabled = i < 0 || i >= S.data.days.length - 1;
  }
  function skelRows() { var r = ''; for (var i = 0; i < 9; i++) { r += '<tr><td colspan="12"><div class="mu-skel" style="margin:4px 0"></div></td></tr>'; } return r; }
  var COLS = [['#', null], ['Article / Issue', 'ref'], ['Journal', 'journal'], ['Stage', 'stage'], ['Dept', 'department'], ['Received', 'received'], ['Due', 'due'], ['Status', 'status'], ['Pages', 'pages'], ['Graphics', null], ['Figures', null], ['', null]];
  function theadHtml() {
    return '<thead><tr>' + COLS.map(function (c) {
      var cls = c[1] && S.sort === c[1] ? ' class="' + S.dir + '"' : '';
      return '<th' + (c[1] ? ' data-sort="' + c[1] + '"' : '') + cls + '>' + c[0] + '</th>';
    }).join('') + '</tr></thead>';
  }
  function statusPill(s) {
    var v = String(s || ''), cls = 'label-default';
    if (/deliver|complet|approved/i.test(v)) { cls = 'label-success'; } else if (/query|hold|reject/i.test(v)) { cls = 'label-warning'; } else if (/review|progress|assign|process/i.test(v)) { cls = 'label-info'; }
    return v ? '<span class="label ' + cls + '">' + esc(v) + '</span>' : '<span class="mu-zero">-</span>';
  }
  function rowHtml(r, i, start) {
    var isIssue = r.kind === 'issue';
    var ref = '<b>' + esc(r.ref || '-') + '</b>' + (r.priority ? '<i class="fa fa-star pri" title="High priority"></i>' : '') + (S.cust === 'all' ? '<small>' + esc(r.customer) + '</small>' : '');
    /* the Status column is inw_inward_dtl.status; the coloured working parts (stored label) are shown only when they agree with it,
       so a stale label such as "Client Review" never overrides the real status */
    var hasParts = !isIssue && r.working_parts && r.working_parts.length && String(r.working_parts[0].t || '').toLowerCase() === String(r.status || '').toLowerCase();
    var status = hasParts ? '' : statusPill(r.status);
    if (isIssue) { status += '<small>' + (r.articles_done || 0) + ' done · ' + (r.articles_pending || 0) + ' pending</small>'; }
    else if (hasParts) { status += '<div class="ifx-wp">' + r.working_parts.map(function (p) { return '<span class="label label-' + esc(p.k) + '">' + esc(p.t) + '</span>'; }).join('') + '</div>'; }
    var actions = isIssue ? '' : '<div class="act"><a class="mu-btn mu-btn-sm primary" target="_blank" rel="noopener" href="' + esc(r.links.send_instructions) + '">Send</a>' +
      '<a class="mu-icon-btn" target="_blank" rel="noopener" title="Job card" href="' + esc(r.links.job_card) + '"><i class="fa fa-clock-o"></i></a></div>';
    var due = fmtDate(r.due) + (r.overdue_days > 0 ? '<br><span class="ifx-late">' + r.overdue_days + (r.overdue_days === 1 ? ' day' : ' days') + ' late</span>' : '');
    return '<tr><td class="n">' + (start + i + 1) + '</td><td class="ref">' + ref + '</td>' +
      '<td>' + esc(r.journal_code || '-') + '<small>' + esc(r.journal_name || '') + '</small></td>' +
      '<td><span class="ifx-st ' + esc(r.stage_group) + '">' + esc(r.stage) + '</span></td>' +
      '<td>' + (r.department ? '<span class="ifx-dept">' + esc(r.department) + '</span>' : '<span class="mu-zero">-</span>') + '</td>' +
      '<td>' + fmtDate(r.received) + (r.received_time ? '<small>' + esc(r.received_time) + '</small>' : '') + '</td><td class="' + (r.overdue ? 'late' : '') + '">' + due + '</td>' +
      '<td class="st">' + status + '</td><td class="c">' + (isIssue ? (r.start_page != null ? 'p.' + r.start_page : '-') : (r.pages == null ? '-' : r.pages)) + '</td>' +
      '<td class="c">' + (isIssue ? '-' : '<span class="mu-pill ' + (r.graphics === 'Yes' ? 'ok' : 'muted') + '">' + r.graphics + '</span>') + '</td>' +
      '<td class="c">' + (isIssue || r.figures == null ? '-' : r.figures) + '</td><td>' + actions + '</td></tr>';
  }
  function drawerHead(total) {
    var dr = S.dr, w = bucketWindow(dr.bucket), what;
    if (dr.bucket === 'over') { what = 'Overdue'; } else if (isDay(dr.bucket)) { what = 'Due ' + fmtLong(dr.bucket); } else { what = 'Due in range'; }
    $('#ifxDTitle').textContent = STAGE_NAME[dr.stage] + ' · ' + what;
    var sub = [custName()]; if (deptName()) { sub.push(deptName()); }
    if (!isDay(dr.bucket)) { sub.push(fmtLong(w.from) + (w.from === w.to ? '' : ' – ' + fmtLong(w.to))); }
    $('#ifxDSub').textContent = sub.join(' · ');
    $('#ifxDCnt').textContent = total == null ? '...' : nf(total);
    dayNav();
  }
  function loadDetail() {
    drawerHead(null);
    $('#ifxDBody').innerHTML = '<table class="ifx-dtable">' + theadHtml() + '<tbody>' + skelRows() + '</tbody></table>';
    $('#ifxDInfo').textContent = ''; $('#ifxPg').innerHTML = '';
    api(drawerParams({ action: 'detail', page: S.page, per_page: S.per }), 'detail').then(function (j) {
      if (!S.dr) { return; }
      if (!j.ok) { $('#ifxDBody').innerHTML = failBox(j, true); return; }
      drawerHead(j.total);
      var start = (j.page - 1) * j.per_page;
      if (!j.rows.length) {
        $('#ifxDBody').innerHTML = '<div class="ifx-empty"><i class="fa fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#c9d2dc"></i>' + (S.q ? 'Nothing matches "' + esc(S.q) + '".' : 'Nothing is open for this selection.') + '</div>';
        $('#ifxDInfo').textContent = '0 records'; return;
      }
      $('#ifxDBody').innerHTML = '<table class="ifx-dtable">' + theadHtml() + '<tbody>' + j.rows.map(function (r, i) { return rowHtml(r, i, start); }).join('') + '</tbody></table>';
      $('#ifxDBody').scrollTop = 0;
      $('#ifxDInfo').textContent = 'Showing ' + nf(start + 1) + '–' + nf(start + j.rows.length) + ' of ' + nf(j.total) + (S.sort ? '' : ' · high priority first, then due date');
      renderPager(j.page, j.pages);
    }).catch(function (e) { if (e && e.name === 'AbortError') { return; } $('#ifxDBody').innerHTML = failBox({ error: 'Could not reach the server.' }, true); });
  }
  function renderPager(page, pages) {
    if (pages <= 1) { $('#ifxPg').innerHTML = ''; return; }
    var b = function (label, p, cls, dis) { return '<button type="button" data-p="' + p + '"' + (cls ? ' class="' + cls + '"' : '') + (dis ? ' disabled' : '') + '>' + label + '</button>'; };
    var h = b('‹', page - 1, '', page <= 1), set = {};
    [1, 2, page - 1, page, page + 1, pages - 1, pages].forEach(function (p) { if (p >= 1 && p <= pages) { set[p] = 1; } });
    var last = 0;
    Object.keys(set).map(Number).sort(function (a, b2) { return a - b2; }).forEach(function (p) {
      if (last && p - last > 1) { h += '<button type="button" disabled>…</button>'; }
      h += b(p, p, p === page ? 'cur' : '', false); last = p;
    });
    $('#ifxPg').innerHTML = h + b('›', page + 1, '', page >= pages);
  }

  /* ------------------------------------------------------------ controls */
  function buildMeta(meta) {
    S.meta = meta;
    var sel = $('#ifxCust'), h = '';
    if (meta.all_customers_allowed) { h += '<option value="all">All customers</option>'; }
    meta.customers.forEach(function (c) { h += '<option value="' + c.id + '">' + esc(c.name) + '</option>'; });
    sel.innerHTML = h;
    var has = function (el, v) { return [].some.call(el.options, function (o) { return o.value === String(v); }); };
    if (!has(sel, S.cust)) { var saved = null; try { saved = localStorage.getItem('wipCust'); } catch (e) {} S.cust = has(sel, saved) ? saved : sel.options[0].value; }
    sel.value = S.cust;
    var ds = $('#ifxDept'); ds.innerHTML = '<option value="all">All departments</option>' + meta.departments.map(function (d) { return '<option value="' + d.id + '">' + esc(d.name) + '</option>'; }).join('');
    if (!has(ds, S.dept)) { S.dept = 'all'; } ds.value = S.dept;
    $('#ifxMM').innerHTML = MONTHS.map(function (m, i) { return '<option value="' + (i + 1) + '">' + m + '</option>'; }).join('');
    var yh = ''; for (var y = today.getFullYear() + 1; y >= today.getFullYear() - 5; y--) { yh += '<option value="' + y + '">' + y + '</option>'; }
    $('#ifxYY').innerHTML = yh; $('#ifxMM').value = S.mm; $('#ifxYY').value = S.my;
  }
  function applyModeUi() {
    $('#ifxDaysField').style.display = S.mode === 'days' ? '' : 'none';
    $('#ifxMonthField').style.display = S.mode === 'month' ? '' : 'none';
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.checked = r.value === S.mode; });
  }
  function toDmy(s) { var d = parse(s); return d ? pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear() : ''; }
  function setRangeUi() { var el = $('#ifxRange'); el.setAttribute('data-from', toDmy(S.from)); el.setAttribute('data-to', toDmy(S.to)); }
  var changeT;
  function changed() { clearTimeout(changeT); changeT = setTimeout(function () { if (S.dr) { closeDrawer(); } writeUrl(); loadMatrix(); }, 120); }

  function bind() {
    $('#ifxCust').addEventListener('change', function () { S.cust = this.value; changed(); });
    $('#ifxDept').addEventListener('change', function () { S.dept = this.value; changed(); });
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.addEventListener('change', function () { if (this.checked) { S.mode = this.value; applyModeUi(); changed(); } }); });
    $('#ifxMM').addEventListener('change', function () { S.mm = +this.value; changed(); });
    $('#ifxYY').addEventListener('change', function () { S.my = +this.value; changed(); });
    $('#ifxRange').addEventListener('dvr:change', function (e) {
      var fd = /^(\d{2})-(\d{2})-(\d{4})$/.exec(e.detail.from || ''), td = /^(\d{2})-(\d{2})-(\d{4})$/.exec(e.detail.to || ''); if (!fd || !td) { return; }
      var nf2 = fd[3] + '-' + fd[2] + '-' + fd[1], nt = td[3] + '-' + td[2] + '-' + td[1];
      if ((parse(nt) - parse(nf2)) / 86400000 > 399) { toast('Please pick 400 days or fewer.'); return; }
      S.from = nf2; S.to = nt; changed();
    });
    $('#ifxRefresh').addEventListener('click', function () { loadMatrix(); if (S.dr) { loadDetail(); } });
    $('#ifxLink').addEventListener('click', function () {
      var done = function () { toast('Link copied'); };
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(location.href).then(done, function () { toast(location.href); }); } else { toast('Copy the address bar to share this view'); }
    });
    document.addEventListener('click', function (e) {
      var o = e.target.closest ? e.target.closest('[data-open]') : null;
      if (o && $('#ifx').contains(o)) { openDrawer(o.getAttribute('data-open'), o.getAttribute('data-bucket'), o); return; }
      if (e.target.closest && e.target.closest('[data-retry]')) { if (S.dr && $('#ifxDrawer').contains(e.target)) { loadDetail(); } else { loadMatrix(); } }
    });
    $('#ifxClose').addEventListener('click', closeDrawer);
    $('#ifxScrim').addEventListener('click', closeDrawer);
    $('#ifxPrev').addEventListener('click', function () { stepDay(-1); });
    $('#ifxNext').addEventListener('click', function () { stepDay(1); });
    document.addEventListener('keydown', function (e) {
      if (!S.dr) { return; }
      if (e.key === 'Escape') { closeDrawer(); return; }
      var typing = /^(INPUT|SELECT|TEXTAREA)$/.test((document.activeElement || {}).tagName || '');
      if (!typing && e.key === 'ArrowLeft') { stepDay(-1); } else if (!typing && e.key === 'ArrowRight') { stepDay(1); }
      if (e.key === 'Tab') {
        var f = [].filter.call($('#ifxDrawer').querySelectorAll('button:not(:disabled),a[href],input,select'), function (x) { return x.offsetParent !== null; });
        if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    var qT;
    $('#ifxQ').addEventListener('input', function () { var v = this.value.trim(); clearTimeout(qT); qT = setTimeout(function () { S.q = v; S.page = 1; loadDetail(); }, 280); });
    $('#ifxPer').addEventListener('change', function () { S.per = +this.value; S.page = 1; loadDetail(); });
    $('#ifxPg').addEventListener('click', function (e) { var b = e.target.closest('button[data-p]'); if (b && !b.disabled) { S.page = +b.getAttribute('data-p'); loadDetail(); } });
    $('#ifxDBody').addEventListener('click', function (e) {
      var th = e.target.closest('th[data-sort]'); if (!th) { return; }
      var k = th.getAttribute('data-sort');
      if (S.sort === k) { S.dir = S.dir === 'asc' ? 'desc' : 'asc'; } else { S.sort = k; S.dir = 'asc'; }
      S.page = 1; loadDetail();
    });
    $('#ifxExport').addEventListener('click', function () { if (S.dr) { window.location.href = API + '?' + new URLSearchParams(drawerParams({ action: 'export' })).toString(); } });
  }
  function stepDay(n) {
    if (!S.dr || !isDay(S.dr.bucket)) { return; }
    var i = S.data.days.indexOf(S.dr.bucket) + n;
    if (i < 0 || i >= S.data.days.length) { return; }
    S.dr.bucket = S.data.days[i]; S.page = 1; writeUrl(); loadDetail();
  }

  /* ------------------------------------------------------------ start */
  var openFromUrl = readUrl();
  setRangeUi(); applyModeUi(); bind();
  apiPlain({ action: 'meta' }).then(function (m) {
    if (!m || !m.ok) { $('#ifxGridWrap').innerHTML = failBox(m || { error: 'Could not load the report.' }, false); $('#ifxCust').innerHTML = '<option>-</option>'; return; }
    buildMeta(m); writeUrl(); loadMatrix();
    if (openFromUrl) {
      var wait = setInterval(function () { if (S.data) { clearInterval(wait); openDrawer(openFromUrl.stage, openFromUrl.bucket, null); } }, 80);
      setTimeout(function () { clearInterval(wait); }, 8000);
    }
  }).catch(function () { $('#ifxGridWrap').innerHTML = failBox({ error: 'Could not reach the server.' }, false); });
})();
    </script>
    <?php mu_foot(); ?>
  </body>
</html>

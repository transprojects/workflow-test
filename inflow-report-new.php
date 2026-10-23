<?php include "includes/includes.php";
include "includes/modern-ui.php";

/* Old bookmarks / links (?date_wise=m&mfrom_dt=06-2026&wcust_id=42&...) open the same view here. */
if (isset($_GET['wcust_id']) || isset($_GET['date_wise']) || isset($_GET['wgetaction'])) {
    $q = array();
    $c = isset($_GET['wcust_id']) ? (string)$_GET['wcust_id'] : '';
    if ($c === 'all' || ctype_digit($c)) { $q['cust'] = $c; }
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
    header('Location: inflowreport' . ($q ? '?' . http_build_query($q) : ''));
    exit;
}
/* ============================================================================
 * Inflow Data Accuracy                                   /workflow-new/inflowreport   (also /inflowreport-new)
 * Same report, same counting rules; every number comes from api/inflow.php (no page reloads).
 * The previous version stays available at /inflowreport-old (inflow-report-all-data.php).
 * ========================================================================== */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <style>
/* ---------- layout bits ---------- */
.ifx .ifx-live{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:20px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);font-size:12px;font-weight:700;color:#fff;}
.ifx .ifx-live:before{content:"";width:8px;height:8px;border-radius:50%;background:#4ade80;animation:muPulse 1.6s infinite;}
.ifx .ifx-hero-link{color:#fff;opacity:.85;font-size:12px;text-decoration:underline;}
.ifx-filters{display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px 18px;}
.ifx-filters .mu-field{min-width:0;}
.ifx-month{display:flex;gap:8px;}
.ifx-month select{min-width:120px;}
.ifx-actions{display:flex;gap:10px;margin-left:auto;}
.ifx-note{display:none;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;padding:11px 16px;border-radius:12px;background:#eef5fb;border:1px solid #cfe3f4;color:#1b4468;font-size:13px;}
.ifx-note.on{display:flex;}
.ifx-note i{color:#1b6ca8;}
.ifx-note button{margin-left:auto;border:0;background:transparent;color:#1b6ca8;font-weight:700;cursor:pointer;text-decoration:underline;}
.ifx-recon{display:none;margin:-6px 0 16px;padding:14px 18px;border-radius:12px;background:#fff;border:1px solid var(--line);}
.ifx-recon.on{display:block;animation:muUp .3s ease both;}
.ifx-recon table{width:100%;max-width:640px;border-collapse:collapse;font-size:13px;}
.ifx-recon th{font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mute);text-align:right;padding:6px 10px;}
.ifx-recon th:first-child,.ifx-recon td:first-child{text-align:left;}
.ifx-recon td{padding:7px 10px;border-top:1px solid var(--line);text-align:right;font-variant-numeric:tabular-nums;}
.ifx-recon .neg{color:#d64545;font-weight:700;} .ifx-recon .pos{color:#1e9e61;font-weight:700;}
.ifx-recon p{margin:10px 0 0;color:var(--mute);font-size:12px;}

/* ---------- summary cards ---------- */
.ifx-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px;}
.ifx-stat{position:relative;overflow:hidden;padding:16px 18px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.05);transition:transform .2s,box-shadow .2s;cursor:pointer;text-align:left;font:inherit;color:inherit;}
.ifx-stat:hover{transform:translateY(-4px);box-shadow:0 12px 24px rgba(20,48,74,.13);}
.ifx-stat:after{content:"";position:absolute;left:0;bottom:0;height:3px;width:100%;background:var(--c);}
.ifx-stat .top{display:flex;align-items:center;gap:10px;color:var(--mute);font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.ifx-stat .top i{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:9px;background:color-mix(in srgb,var(--c) 14%,#fff);color:var(--c);font-size:14px;}
.ifx-stat b{display:block;margin:8px 0 4px;font-size:27px;line-height:1.1;color:var(--navy);font-variant-numeric:tabular-nums;}
.ifx-stat .delta{font-size:12px;color:var(--mute);}
.ifx-stat .delta em{font-style:normal;font-weight:700;padding:1px 7px;border-radius:20px;margin-right:4px;}
.ifx-stat .delta em.up{background:#e1effa;color:#1b6ca8;} .ifx-stat .delta em.down{background:#eef1f5;color:#6b7785;} .ifx-stat .delta em.flat{background:#eef1f5;color:#6b7785;}
.ifx-stat.skel b{height:30px;width:90px;border-radius:8px;background:linear-gradient(90deg,#eceff3 25%,#f7f9fb 50%,#eceff3 75%);background-size:200% 100%;animation:muShimmer 1.3s linear infinite;color:transparent;}

/* ---------- heat-map grid ---------- */
.ifx-legend{display:inline-flex;align-items:center;gap:8px;font-size:12px;color:var(--mute);font-weight:600;text-transform:none;letter-spacing:0;}
.ifx-legend i{display:inline-block;width:90px;height:8px;border-radius:8px;background:linear-gradient(90deg,#eef3f8,var(--navy));}
.ifx-scroll{position:relative;overflow:auto;border:1px solid var(--line);border-radius:12px;max-width:100%;}
.ifx-grid{border-collapse:separate;border-spacing:0;width:100%;font-size:13px;}
.ifx-grid th,.ifx-grid td{padding:0;text-align:center;border-bottom:1px solid var(--line);background:#fff;}
.ifx-grid thead th{position:sticky;top:0;z-index:3;background:#f4f7fa;color:var(--mute);font-weight:700;padding:7px 0 6px;min-width:44px;line-height:1.15;}
.ifx-grid thead th span{display:block;font-size:13px;color:var(--navy);}
.ifx-grid thead th small{display:block;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
.ifx-grid thead th em{display:block;font-style:normal;font-size:10px;color:var(--accent);font-weight:800;text-transform:uppercase;height:11px;}
.ifx-grid thead th.we span,.ifx-grid thead th.we small{color:#a3adb9;}
.ifx-grid thead th.now{box-shadow:inset 0 -3px 0 var(--accent);}
.ifx-grid .ifx-corner,.ifx-grid .ifx-stage{position:sticky;left:0;z-index:4;min-width:150px;text-align:left;padding-left:14px;box-shadow:2px 0 0 var(--line);}
.ifx-grid .ifx-stage{z-index:2;background:#fff;font-weight:700;color:var(--navy);padding:10px 14px;white-space:nowrap;}
.ifx-grid .ifx-stage i{display:inline-block;width:9px;height:9px;border-radius:50%;background:var(--c);margin-right:9px;}
.ifx-grid .ifx-stage small{display:block;margin:2px 0 0 18px;color:var(--mute);font-weight:600;font-size:11px;}
.ifx-grid .ifx-tot{position:sticky;right:0;z-index:2;min-width:74px;box-shadow:-2px 0 0 var(--line);background:#f4f7fa;font-weight:800;}
.ifx-grid thead .ifx-tot{z-index:4;color:var(--navy);}
.ifx-grid tbody tr.sum th,.ifx-grid tbody tr.sum td{background:#f4f7fa;border-bottom:0;}
.ifx-cell{display:block;width:100%;min-height:42px;border:0;border-radius:0;cursor:pointer;font:inherit;font-weight:700;font-variant-numeric:tabular-nums;color:var(--navy);
  background:color-mix(in srgb,var(--c) calc(var(--i) * 42%),#fff);transition:transform .12s,box-shadow .12s,background .2s;position:relative;}
.ifx-cell:hover,.ifx-cell:focus-visible{outline:0;transform:scale(1.14);z-index:5;box-shadow:0 6px 16px rgba(20,48,74,.28);border-radius:8px;background:var(--c);color:#fff;}
.ifx-grid td.z{color:#c3ccd6;font-size:12px;min-height:42px;height:42px;}
.ifx-grid td.now{box-shadow:inset 2px 0 0 rgba(232,116,42,.45),inset -2px 0 0 rgba(232,116,42,.45);}
.ifx-grid .ifx-tot .ifx-cell{background:transparent;color:var(--navy);font-weight:800;}
.ifx-grid .ifx-tot .ifx-cell:hover{background:var(--navy);color:#fff;}
.ifx-grid tr.sum .ifx-cell{background:transparent;color:var(--navy);}
.ifx-grid tr.sum .ifx-cell:hover{background:var(--navy);color:#fff;}
.ifx-empty{padding:34px;text-align:center;color:var(--mute);}
.ifx-skel-grid{display:grid;gap:10px;padding:6px 0;}
.ifx-skel-grid i{display:block;height:34px;border-radius:8px;background:linear-gradient(90deg,#eceff3 25%,#f7f9fb 50%,#eceff3 75%);background-size:200% 100%;animation:muShimmer 1.3s linear infinite;}

/* ---------- drawer ---------- */
.ifx-scrim{position:fixed;inset:0;z-index:2000;background:rgba(14,30,46,.45);backdrop-filter:blur(2px);opacity:0;pointer-events:none;transition:opacity .25s;}
.ifx-scrim.on{opacity:1;pointer-events:auto;}
.ifx-drawer{position:fixed;top:0;right:0;bottom:0;z-index:2001;display:flex;flex-direction:column;width:min(1040px,100%);background:#fff;box-shadow:-24px 0 60px rgba(20,48,74,.28);
  transform:translateX(104%);transition:transform .3s cubic-bezier(.2,.7,.2,1);color:var(--ink);font-size:13.5px;}
.ifx-drawer.on{transform:none;}
.ifx-dhead{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--line);background:linear-gradient(120deg,#14304a,#1f4b73 70%,#1b6ca8);color:#fff;}
.ifx-dhead h2{margin:0;font-size:17px;font-weight:700;color:#fff;}
.ifx-dhead .sub{font-size:12.5px;opacity:.85;}
.ifx-dhead .grow{flex:1;min-width:180px;}
.ifx-dhead .cnt{padding:4px 12px;border-radius:20px;background:rgba(255,255,255,.18);font-weight:700;font-size:13px;}
.ifx-dbtn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.12);color:#fff;cursor:pointer;transition:background .2s;font-size:14px;}
.ifx-dbtn:hover:not(:disabled){background:rgba(255,255,255,.3);} .ifx-dbtn:disabled{opacity:.35;cursor:default;}
.ifx-tools{display:flex;flex-wrap:wrap;align-items:center;gap:10px 12px;padding:12px 20px;border-bottom:1px solid var(--line);background:#fafbfd;}
.ifx-tools .mu-search{flex:1;min-width:200px;max-width:340px;}
.ifx-tools select{height:38px;padding:0 10px;border:1px solid #d5dde6;border-radius:9px;background:#fff;}
.ifx-tools .sp{flex:1;}
.ifx-dbody{flex:1;overflow:auto;padding:0 20px 8px;}
.ifx-dtable{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;margin-top:14px;border:1px solid var(--line);border-radius:12px;overflow:hidden;}
.ifx-dtable th{position:sticky;top:0;z-index:2;background:#f4f7fa;color:var(--mute);font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;text-align:left;padding:10px 12px;white-space:nowrap;user-select:none;}
.ifx-dtable th[data-sort]{cursor:pointer;} .ifx-dtable th[data-sort]:hover{color:var(--navy);background:#e9eef4;}
.ifx-dtable th.asc:after{content:" \25B2";color:var(--accent);font-size:9px;} .ifx-dtable th.desc:after{content:" \25BC";color:var(--accent);font-size:9px;}
.ifx-dtable td{padding:10px 12px;border-top:1px solid var(--line);background:#fff;vertical-align:middle;white-space:nowrap;}
.ifx-dtable tbody tr:first-child td{border-top:0;}
.ifx-dtable tbody tr:hover td{background:#f8fbfd;}
.ifx-dtable td.n{color:var(--mute);font-weight:700;width:44px;}
.ifx-dtable td.c{text-align:center;}
.ifx-dtable td.ref b{color:var(--navy);} .ifx-dtable td small{display:block;color:var(--mute);font-size:11.5px;margin-top:1px;}
.ifx-dtable td.st{white-space:normal;min-width:190px;max-width:320px;}
.ifx-wp{display:flex;flex-wrap:wrap;gap:4px;margin-top:5px;}
.ifx-drawer .label{display:inline-block !important;padding:3px 10px !important;border:0 !important;border-radius:20px !important;font-size:11.5px !important;font-weight:700 !important;line-height:1.4 !important;white-space:nowrap;text-shadow:none !important;vertical-align:middle;}
.ifx-drawer .label-success{background:#e3f6ec !important;color:#1e9e61 !important;} .ifx-drawer .label-default{background:#eef1f5 !important;color:#6b7785 !important;} .ifx-drawer .label-info{background:#e1effa !important;color:#1b6ca8 !important;}
.ifx-drawer .label-warning{background:#fdf1d9 !important;color:#b87800 !important;} .ifx-drawer .label-danger{background:#fbe5e5 !important;color:#d64545 !important;} .ifx-drawer .label-primary{background:#e3eaf2 !important;color:#14304a !important;}
.ifx-wp:first-child{margin-top:0;}

.ifx-dtable td.late{color:#d64545;font-weight:700;}
.ifx-dtable .pri{color:#e8a30a;margin-left:6px;}
.ifx-dtable .act{display:flex;gap:8px;align-items:center;}
.ifx-st{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
.ifx-st.FP{background:#e1effa;color:#1b6ca8;} .ifx-st.REV{background:#fdeee3;color:#c9591a;} .ifx-st.FIN{background:#e3f6ec;color:#1e9e61;} .ifx-st.PMC{background:#fdf1d9;color:#b87800;} .ifx-st.JSTAGE{background:#fdf1dc;color:#b8730f;} .ifx-st.ISS{background:#efeafe;color:#6a49e0;}
.ifx-dfoot{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid var(--line);background:#fff;}
.ifx-dfoot .info{color:var(--mute);font-size:12.5px;margin-right:auto;}
.ifx-pg{display:inline-flex;gap:4px;}
.ifx-pg button{min-width:34px;height:32px;padding:0 10px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--navy);font-weight:600;font-size:12.5px;cursor:pointer;transition:.15s;}
.ifx-pg button:hover:not(:disabled){border-color:var(--blue);color:var(--blue);} .ifx-pg button.cur{background:var(--navy);border-color:var(--navy);color:#fff;} .ifx-pg button:disabled{opacity:.45;cursor:default;}
.ifx-err{margin:20px 0;padding:18px;border-radius:12px;background:#fdecec;border:1px solid #f5c2c2;color:#a33;display:flex;gap:12px;align-items:center;}
.ifx-err button{margin-left:auto;}
.ifx-toast{position:fixed;left:50%;bottom:26px;z-index:3200;transform:translate(-50%,20px);padding:10px 18px;border-radius:10px;background:#14304a;color:#fff;font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:.25s;}
.ifx-toast.on{opacity:1;transform:translate(-50%,0);}
body.ifx-lock{overflow:hidden;}
@media(max-width:700px){.ifx-dtable th:nth-child(n+8):nth-child(-n+11),.ifx-dtable td:nth-child(n+8):nth-child(-n+11){display:none;} .ifx-dtable td small{display:none;} .ifx-dtable td.ref small{display:block;} .ifx-actions{margin-left:0;} .ifx-dhead{padding:12px 14px;} .ifx-tools,.ifx-dbody,.ifx-dfoot{padding-left:14px;padding-right:14px;}}
@media(prefers-reduced-motion:reduce){.ifx-drawer,.ifx-scrim,.ifx-cell{transition:none;}}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php"); ?>

      <div class="mu-root ifx" id="ifx">
        <div class="mu-hero">
          <div>
            <small>Reports</small>
            <h1>Inflow Data Accuracy</h1>
            <p>Articles, revisions, finals and issues received each day, live and archive together</p>
          </div>
          <div class="mu-hero-right">
            <span class="ifx-live" id="ifxLive">LIVE DATA</span>
            <div class="mu-chip"><small>Calculated</small><b id="ifxCalc">-</b></div>
            <a class="ifx-hero-link" href="inflowreport-old">Previous version</a>
          </div>
        </div>

        <section class="mu-panel mu-filter-panel">
          <div class="ifx-filters">
            <div class="mu-field"><label>View by</label>
              <div class="mu-seg" id="ifxMode">
                <label><input type="radio" name="ifxmode" value="days" checked><span>Days</span></label>
                <label><input type="radio" name="ifxmode" value="month"><span>Month</span></label>
              </div></div>
            <div class="mu-field" style="min-width:220px;"><label for="ifxCust">Customer</label>
              <select id="ifxCust"><option>Loading...</option></select></div>
            <div class="mu-field" id="ifxDaysField" style="min-width:250px;"><label>Date range</label>
              <div class="dvr" id="ifxRange" data-from-name="from" data-to-name="to"></div></div>
            <div class="mu-field" id="ifxMonthField" style="display:none;"><label>Month</label>
              <div class="ifx-month"><select id="ifxMM" aria-label="Month"></select><select id="ifxYY" aria-label="Year"></select></div></div>
            <div class="ifx-actions">
              <button type="button" class="mu-btn" id="ifxRefresh" title="Reload the numbers"><i class="fa fa-refresh"></i> Refresh</button>
              <button type="button" class="mu-btn" id="ifxLink" title="Copy a link to this exact view"><i class="fa fa-link"></i> Copy link</button>
            </div>
          </div>
        </section>

        <div class="ifx-note" id="ifxNote"><i class="fa fa-info-circle"></i><span id="ifxNoteTxt"></span><button type="button" id="ifxReconBtn">Compare with the previous report</button></div>
        <div class="ifx-recon" id="ifxRecon"></div>

        <div class="ifx-stats" id="ifxStats"></div>

        <section class="mu-panel">
          <h3><i class="fa fa-th"></i> Inflow by day
            <span class="mu-right"><span class="ifx-legend">fewer <i></i> more</span> &nbsp;&middot;&nbsp; click a number to open the articles</span></h3>
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
          <input type="search" class="mu-search" id="ifxQ" placeholder="Search article, journal, customer" aria-label="Search">
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
  var API = 'api/inflow.php';
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
  var STAGE_NAME = { FP: 'First proof', REV: 'Revisions', FIN: 'Finals', PMC: 'PMC', JSTAGE: 'JSTAGE', ISS: 'Issues', ALL: 'All inflow' };
  var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var WD = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
  var parse = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
  var addDays = function (d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; };
  var today = new Date(); today.setHours(0, 0, 0, 0);
  var fmtShort = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) : ''; };
  var fmtLong = function (s) { var d = parse(s); return d ? d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear() : ''; };
  var fmtDate = function (s) { return (!s || s === '0000-00-00') ? '-' : fmtLong(s); };
  var nf = function (n) { return Number(n || 0).toLocaleString(); };

  /* ------------------------------------------------------------ state */
  var S = {
    mode: 'days', cust: 'all', from: iso(addDays(today, -6)), to: iso(today), my: today.getFullYear(), mm: today.getMonth() + 1,
    meta: null, data: null, prev: null, prevRange: null, loading: false,
    dr: null, page: 1, per: 50, sort: '', dir: 'asc', q: ''
  };
  var ctl = { matrix: null, detail: null };
  var drawerOpener = null;

  function range() {
    if (S.mode === 'month') {
      var f = new Date(S.my, S.mm - 1, 1), t = new Date(S.my, S.mm, 0);
      if (t > today) { t = new Date(today); }
      return { from: iso(f), to: iso(t) };
    }
    return { from: S.from, to: S.to };
  }
  function prevRange(r) {
    var f = parse(r.from), t = parse(r.to);
    var len = Math.round((t - f) / 86400000) + 1;
    if (S.mode === 'month') {
      var pf = new Date(S.my, S.mm - 2, 1), pl = new Date(S.my, S.mm - 1, 0);
      var pt = addDays(pf, len - 1); if (pt > pl) { pt = pl; }
      return { from: iso(pf), to: iso(pt) };
    }
    var pt2 = addDays(f, -1);
    return { from: iso(addDays(pt2, -(len - 1))), to: iso(pt2) };
  }
  function custName() {
    if (S.cust === 'all') { return 'All customers'; }
    var c = (S.meta ? S.meta.customers : []).filter(function (x) { return String(x.id) === String(S.cust); })[0];
    return c ? c.name : 'Customer ' + S.cust;
  }

  /* ------------------------------------------------------------ url <-> state */
  function readUrl() {
    var p = new URLSearchParams(location.search);
    if (p.get('cust')) { S.cust = p.get('cust'); }
    var mo = /^(\d{4})-(\d{2})$/.exec(p.get('month') || '');
    if (mo) { S.mode = 'month'; S.my = +mo[1]; S.mm = +mo[2]; }
    else if (parse(p.get('from')) && parse(p.get('to'))) { S.from = p.get('from'); S.to = p.get('to'); }
    var h = /^#(FP|REV|FIN|PMC|JSTAGE|ISS|ALL):(\d{4}-\d{2}-\d{2}|range)$/.exec(location.hash || '');
    return h ? { stage: h[1], date: h[2] === 'range' ? null : h[2] } : null;
  }
  function writeUrl() {
    var p = new URLSearchParams();
    p.set('cust', S.cust);
    if (S.mode === 'month') { p.set('month', S.my + '-' + pad(S.mm)); } else { p.set('from', S.from); p.set('to', S.to); }
    var h = S.dr ? '#' + S.dr.stage + ':' + (S.dr.date || 'range') : '';
    try { history.replaceState(null, '', location.pathname + '?' + p.toString() + h); } catch (e) {}
    try { localStorage.setItem('ifxCust', S.cust); } catch (e) {}
  }

  /* ------------------------------------------------------------ api */
  function api(params, key) {
    if (ctl[key]) { ctl[key].abort(); }
    var c = ctl[key] = new AbortController();
    var qs = new URLSearchParams(params).toString();
    return fetch(API + '?' + qs, { credentials: 'same-origin', signal: c.signal }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Unexpected response from the server.', status: r.status }; }).then(function (j) { j._status = r.status; return j; });
    });
  }
  function apiPlain(params) {
    return fetch(API + '?' + new URLSearchParams(params).toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function toast(msg) { var t = $('#ifxToast'); t.textContent = msg; t.classList.add('on'); clearTimeout(toast._t); toast._t = setTimeout(function () { t.classList.remove('on'); }, 2200); }
  function failBox(j, retry) {
    var m = j && j.error ? j.error : 'Something went wrong.';
    if (j && j._status === 401) { m += ' <a href="index">Sign in</a>'; }
    return '<div class="ifx-err"><i class="fa fa-exclamation-triangle"></i><span>' + m + '</span>' + (retry ? '<button type="button" class="mu-btn" data-retry>Try again</button>' : '') + '</div>';
  }

  /* ------------------------------------------------------------ matrix */
  function loadMatrix() {
    var r = range(), pr = prevRange(r);
    S.prevRange = pr; S.loading = true;
    $('#ifxStats').innerHTML = STAGES.concat([{ k: 'TOTAL' }]).map(function () { return '<div class="ifx-stat skel"><div class="top">&nbsp;</div><b>0</b><div class="delta">&nbsp;</div></div>'; }).join('');
    if (!S.data) { $('#ifxGridWrap').innerHTML = '<div class="ifx-skel-grid"><i></i><i></i><i></i><i></i><i></i></div>'; }
    $('#ifxLive').textContent = 'UPDATING';
    var base = { action: 'matrix', cust_id: S.cust };
    var cur = api({ action: 'matrix', cust_id: S.cust, from: r.from, to: r.to }, 'matrix');
    var prv = apiPlain({ action: 'matrix', cust_id: S.cust, from: pr.from, to: pr.to }).catch(function () { return null; });
    Promise.all([cur, prv]).then(function (res) {
      var j = res[0];
      S.loading = false; $('#ifxLive').textContent = 'LIVE DATA';
      if (!j || !j.ok) { $('#ifxStats').innerHTML = ''; $('#ifxGridWrap').innerHTML = failBox(j, true); return; }
      S.data = j; S.prev = res[1] && res[1].ok ? res[1] : null;
      $('#ifxCalc').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' · ' + (j.ms / 1000).toFixed(2) + 's';
      renderStats(); renderGrid(); renderNote();
    }).catch(function (e) { if (e && e.name === 'AbortError') { return; } S.loading = false; $('#ifxLive').textContent = 'LIVE DATA'; $('#ifxGridWrap').innerHTML = failBox({ error: 'Could not reach the server.' }, true); });
  }

  function countUp(el, to) {
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.textContent = nf(to);                                             /* correct value first: never depends on animation frames */
    if (reduce || to === 0 || document.visibilityState !== 'visible') { return; }
    var t0 = null, dur = 650, done = false;
    el.textContent = nf(0);
    setTimeout(function () { done = true; el.textContent = nf(to); }, dur + 250);   /* safety net if frames are throttled */
    (function step(ts) { if (done) { return; } if (t0 === null) { t0 = ts; } var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = nf(Math.round(to * e)); if (p < 1) { requestAnimationFrame(step); } })(performance.now());
  }
  function deltaHtml(cur, prev) {
    if (!S.prev) { return '<span>&nbsp;</span>'; }
    var label = 'vs ' + fmtShort(S.prevRange.from) + ' – ' + fmtShort(S.prevRange.to);
    if (prev === 0 && cur === 0) { return '<em class="flat">0%</em>' + label; }
    if (prev === 0) { return '<em class="up">new</em>' + label; }
    var pct = Math.round((cur - prev) / prev * 100);
    return '<em class="' + (pct > 0 ? 'up' : pct < 0 ? 'down' : 'flat') + '">' + (pct > 0 ? '▲ ' : pct < 0 ? '▼ ' : '') + Math.abs(pct) + '%</em>' + label;
  }
  function renderStats() {
    var d = S.data, p = S.prev, html = '';
    STAGES.forEach(function (s) {
      var cur = d.rows[s.k].total, pv = p ? p.rows[s.k].total : 0;
      html += '<button type="button" class="ifx-stat" data-open="' + s.k + '" style="--c:' + s.c + '"><div class="top"><i class="fa ' + s.ic + '"></i>' + s.name + '</div><b data-n="' + cur + '">0</b><div class="delta">' + deltaHtml(cur, pv) + '</div></button>';
    });
    html += '<button type="button" class="ifx-stat" data-open="ALL" style="--c:#14304a"><div class="top"><i class="fa fa-inbox"></i>Total inflow</div><b data-n="' + d.totals.grand + '">0</b><div class="delta">' + deltaHtml(d.totals.grand, p ? p.totals.grand : 0) + '</div></button>';
    var box = $('#ifxStats'); box.innerHTML = html;
    [].forEach.call(box.querySelectorAll('b[data-n]'), function (b) { countUp(b, +b.getAttribute('data-n')); });
  }

  function renderGrid() {
    var d = S.data, days = d.days, n = days.length;
    if (!n) { $('#ifxGridWrap').innerHTML = '<div class="ifx-empty">Pick a date range.</div>'; return; }
    var nowIso = iso(today);
    var h = '<div class="ifx-scroll"><table class="ifx-grid" aria-label="Inflow by stage and day"><thead><tr><th class="ifx-corner">Stage</th>';
    days.forEach(function (ds, i) {
      var dt = parse(ds), dow = dt.getDay(), first = (i === 0 || dt.getDate() === 1);
      h += '<th class="' + (dow === 0 || dow === 6 ? 'we ' : '') + (ds === nowIso ? 'now' : '') + '"><em>' + (first ? MONTHS[dt.getMonth()].slice(0, 3) : '') + '</em><span>' + dt.getDate() + '</span><small>' + WD[dow] + '</small></th>';
    });
    h += '<th class="ifx-tot"><em></em><span>Total</span><small>range</small></th></tr></thead><tbody>';
    STAGES.forEach(function (s) {
      var row = d.rows[s.k], max = Math.max.apply(null, row.counts.concat([1]));
      h += '<tr style="--c:' + s.c + '"><th class="ifx-stage"><i></i>' + s.k + '<small>' + s.name + '</small></th>';
      row.counts.forEach(function (v, i) {
        var now = days[i] === nowIso ? ' now' : '';
        if (v > 0) {
          var inten = (0.12 + 0.88 * (v / max)).toFixed(3);
          h += '<td class="' + now.trim() + '"><button type="button" class="ifx-cell" style="--i:' + inten + '" data-open="' + s.k + '" data-date="' + days[i] + '" aria-label="' + s.name + ', ' + fmtLong(days[i]) + ', ' + v + '">' + nf(v) + '</button></td>';
        } else { h += '<td class="z' + now + '">0</td>'; }
      });
      h += '<td class="ifx-tot">' + (row.total > 0 ? '<button type="button" class="ifx-cell" data-open="' + s.k + '" aria-label="' + s.name + ' total ' + row.total + '">' + nf(row.total) + '</button>' : '<span class="z" style="color:#c3ccd6">0</span>') + '</td></tr>';
    });
    h += '<tr class="sum"><th class="ifx-stage" style="--c:#14304a"><i style="background:#14304a"></i>TOTAL<small>all stages</small></th>';
    d.totals.per_day.forEach(function (v, i) {
      h += '<td class="' + (days[i] === nowIso ? 'now' : '') + '">' + (v > 0 ? '<button type="button" class="ifx-cell" data-open="ALL" data-date="' + days[i] + '" aria-label="All stages, ' + fmtLong(days[i]) + ', ' + v + '">' + nf(v) + '</button>' : '<span style="color:#c3ccd6">0</span>') + '</td>';
    });
    h += '<td class="ifx-tot">' + (d.totals.grand > 0 ? '<button type="button" class="ifx-cell" data-open="ALL" aria-label="Grand total ' + d.totals.grand + '">' + nf(d.totals.grand) + '</button>' : '0') + '</td></tr>';
    h += '</tbody></table></div>';
    $('#ifxGridWrap').innerHTML = h;
    var sc = $('.ifx-scroll'); if (sc) { var nowCol = sc.querySelector('th.now'); if (nowCol) { sc.scrollLeft = Math.max(0, nowCol.offsetLeft - sc.clientWidth + 260); } }
  }

  function renderNote() {
    var d = S.data, dup = d.duplicates_excluded, tot = 0, differs = false;
    Object.keys(dup).forEach(function (k) { tot += dup[k]; });
    STAGES.forEach(function (s) { if (d.legacy[s.k].total !== d.rows[s.k].total) { differs = true; } });
    var note = $('#ifxNote'), rec = $('#ifxRecon');
    rec.classList.remove('on'); rec.innerHTML = '';
    if (!tot && !differs) { note.classList.remove('on'); return; }
    var names = { FP: 'article', REV: 'revision', FIN: 'final', PMC: 'PMC round', JSTAGE: 'JSTAGE round', ISS: 'issue' }, parts = [];
    STAGES.forEach(function (s2) {
      var n = dup[s2.k]; if (!n) { return; }
      parts.push(nf(n) + ' ' + names[s2.k] + (n === 1 ? '' : 's'));
    });
    if (parts.length) { $('#ifxNoteTxt').textContent = parts.join(', ') + ' exist in both live and archive and ' + (tot === 1 ? 'is' : 'are') + ' counted once.'; }
    else { $('#ifxNoteTxt').textContent = 'Some counts differ from the previous report.'; }
    note.classList.add('on');
    var t = '<table><thead><tr><th>Stage</th><th>Previous report</th><th>This page</th><th>Difference</th></tr></thead><tbody>';
    var oldSum = 0;
    STAGES.forEach(function (s) {
      var o = d.legacy[s.k].total, nw = d.rows[s.k].total, df = nw - o; oldSum += o;
      t += '<tr><td>' + s.name + '</td><td>' + nf(o) + '</td><td>' + nf(nw) + '</td><td class="' + (df < 0 ? 'neg' : df > 0 ? 'pos' : '') + '">' + (df > 0 ? '+' : '') + nf(df) + '</td></tr>';
    });
    t += '<tr><td><b>Total</b></td><td><b>' + nf(oldSum) + '</b></td><td><b>' + nf(d.totals.grand) + '</b></td><td><b>' + (d.totals.grand - oldSum > 0 ? '+' : '') + nf(d.totals.grand - oldSum) + '</b></td></tr></tbody></table>';
    t += '<p>Articles and issues that were moved to the archive but still exist in the live tables were counted twice in the previous First proof row. The Issues row there only counted live issues. Everything here counts each article or issue once.</p>';
    rec.innerHTML = t;
  }

  /* ------------------------------------------------------------ drawer */
  function drawerParams(extra) {
    var r = range(), p = { cust_id: S.cust, stage: S.dr.stage };
    if (S.dr.date) { p.date = S.dr.date; } else { p.from = r.from; p.to = r.to; }
    if (S.sort) { p.sort = S.sort; p.dir = S.dir; }
    if (S.q) { p.q = S.q; }
    for (var k in extra) { p[k] = extra[k]; }
    return p;
  }
  function openDrawer(stage, date, opener) {
    S.dr = { stage: stage, date: date || null }; S.page = 1; S.sort = ''; S.dir = 'asc'; S.q = ''; $('#ifxQ').value = '';
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
  function dayNav() {
    if (!S.dr || !S.dr.date || !S.data) { $('#ifxPrev').style.visibility = $('#ifxNext').style.visibility = 'hidden'; return; }
    var days = S.data.days, i = days.indexOf(S.dr.date);
    $('#ifxPrev').style.visibility = $('#ifxNext').style.visibility = 'visible';
    $('#ifxPrev').disabled = i <= 0; $('#ifxNext').disabled = i < 0 || i >= days.length - 1;
  }
  function skelRows() {
    var r = ''; for (var i = 0; i < 9; i++) { r += '<tr><td colspan="12"><div class="mu-skel" style="margin:4px 0"></div></td></tr>'; }
    return r;
  }
  var COLS = [['#', null], ['Article / Issue', 'ref'], ['Journal', 'journal'], ['Stage', 'stage'], ['Received', 'received'], ['Due', 'due'], ['Status', 'status'], ['Pages', 'pages'], ['Graphics', null], ['Figures', null], ['Source', null], ['', null]];
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
    /* the Status column is inw_inward_dtl.status. The coloured working parts (stored label) are only shown when they agree with it
       (first part = the same status); a stale label such as "Client Review" never overrides the real status. */
    var hasParts = !isIssue && r.working_parts && r.working_parts.length && String(r.working_parts[0].t || '').toLowerCase() === String(r.status || '').toLowerCase();
    var status = hasParts ? '' : statusPill(r.status);          /* the coloured parts already start with the same status */
    if (isIssue) { status += '<small>' + (r.articles_done || 0) + ' done · ' + (r.articles_pending || 0) + ' pending</small>'; }
    else if (hasParts) {
      status += '<div class="ifx-wp">' + r.working_parts.map(function (p) { return '<span class="label label-' + esc(p.k) + '">' + esc(p.t) + '</span>'; }).join('') + '</div>';
    }
    var actions = isIssue ? '' : '<div class="act"><a class="mu-btn mu-btn-sm primary" target="_blank" rel="noopener" href="' + esc(r.links.send_instructions) + '">Send</a>' +
      '<a class="mu-icon-btn" target="_blank" rel="noopener" title="Job card" href="' + esc(r.links.job_card) + '"><i class="fa fa-clock-o"></i></a></div>';
    return '<tr><td class="n">' + (start + i + 1) + '</td><td class="ref">' + ref + '</td>' +
      '<td>' + esc(r.journal_code || '-') + '<small>' + esc(r.journal_name || '') + '</small></td>' +
      '<td><span class="ifx-st ' + esc(r.stage_group) + '">' + esc(r.stage) + '</span></td>' +
      '<td>' + fmtDate(r.received) + '</td><td class="' + (r.overdue ? 'late' : '') + '">' + fmtDate(r.due) + '</td>' +
      '<td class="st">' + status + '</td><td class="c">' + (isIssue ? (r.start_page != null ? 'p.' + r.start_page : '-') : (r.pages == null ? '-' : r.pages)) + '</td>' +
      '<td class="c">' + (isIssue ? '-' : '<span class="mu-pill ' + (r.graphics === 'Yes' ? 'ok' : 'muted') + '">' + r.graphics + '</span>') + '</td>' +
      '<td class="c">' + (isIssue || r.figures == null ? '-' : r.figures) + '</td>' +
      '<td><span class="mu-pill ' + (r.source === 'Live' ? 'ok' : 'muted') + '">' + esc(r.source) + '</span></td><td>' + actions + '</td></tr>';
  }
  function drawerHead(total) {
    var dr = S.dr, r = range();
    $('#ifxDTitle').textContent = STAGE_NAME[dr.stage] + (dr.date ? ' · ' + fmtLong(dr.date) : '');
    $('#ifxDSub').textContent = custName() + (dr.date ? '' : ' · ' + fmtLong(r.from) + (r.from === r.to ? '' : ' – ' + fmtLong(r.to)));
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
        $('#ifxDBody').innerHTML = '<div class="ifx-empty"><i class="fa fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#c9d2dc"></i>' + (S.q ? 'Nothing matches "' + esc(S.q) + '".' : 'No records for this selection.') + '</div>';
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
    var ok = [].some.call(sel.options, function (o) { return o.value === String(S.cust); });
    if (!ok) { var saved = null; try { saved = localStorage.getItem('ifxCust'); } catch (e) {} S.cust = ([].some.call(sel.options, function (o) { return o.value === saved; }) ? saved : sel.options[0].value); }
    sel.value = S.cust;
    var mm = $('#ifxMM'), yy = $('#ifxYY');
    mm.innerHTML = MONTHS.map(function (m, i) { return '<option value="' + (i + 1) + '">' + m + '</option>'; }).join('');
    var yh = ''; for (var y = today.getFullYear(); y >= today.getFullYear() - 6; y--) { yh += '<option value="' + y + '">' + y + '</option>'; }
    yy.innerHTML = yh; mm.value = S.mm; yy.value = S.my;
  }
  function applyModeUi() {
    $('#ifxDaysField').style.display = S.mode === 'days' ? '' : 'none';
    $('#ifxMonthField').style.display = S.mode === 'month' ? '' : 'none';
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.checked = r.value === S.mode; });
  }
  function setRangeUi() {
    var el = $('#ifxRange'); el.setAttribute('data-from', toDmy(S.from)); el.setAttribute('data-to', toDmy(S.to));
  }
  function toDmy(s) { var d = parse(s); return d ? pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear() : ''; }
  var changeT;
  function changed() { clearTimeout(changeT); changeT = setTimeout(function () { if (S.dr) { closeDrawer(); } writeUrl(); loadMatrix(); }, 120); }

  function bind() {
    $('#ifxCust').addEventListener('change', function () { S.cust = this.value; changed(); });
    [].forEach.call(document.querySelectorAll('input[name=ifxmode]'), function (r) { r.addEventListener('change', function () { if (this.checked) { S.mode = this.value; applyModeUi(); changed(); } }); });
    $('#ifxMM').addEventListener('change', function () { S.mm = +this.value; changed(); });
    $('#ifxYY').addEventListener('change', function () { S.my = +this.value; changed(); });
    $('#ifxRange').addEventListener('dvr:change', function (e) {
      var f = e.detail.from, t = e.detail.to; if (!f || !t) { return; }
      var fd = /^(\d{2})-(\d{2})-(\d{4})$/.exec(f), td = /^(\d{2})-(\d{2})-(\d{4})$/.exec(t); if (!fd || !td) { return; }
      var nf2 = fd[3] + '-' + fd[2] + '-' + fd[1], nt = td[3] + '-' + td[2] + '-' + td[1];
      if ((parse(nt) - parse(nf2)) / 86400000 > 399) { toast('Please pick 400 days or fewer.'); return; }
      S.from = nf2; S.to = nt; changed();
    });
    $('#ifxRefresh').addEventListener('click', function () { loadMatrix(); if (S.dr) { loadDetail(); } });
    $('#ifxLink').addEventListener('click', function () {
      var done = function () { toast('Link copied'); };
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(location.href).then(done, function () { toast(location.href); }); } else { toast('Copy the address bar to share this view'); }
    });
    $('#ifxReconBtn').addEventListener('click', function () { $('#ifxRecon').classList.toggle('on'); });

    document.addEventListener('click', function (e) {
      var o = e.target.closest ? e.target.closest('[data-open]') : null;
      if (o && $('#ifx').contains(o)) { openDrawer(o.getAttribute('data-open'), o.getAttribute('data-date'), o); return; }
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
      if (e.key === 'Tab') {                                          /* keep focus inside the drawer */
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
    $('#ifxExport').addEventListener('click', function () {
      if (!S.dr) { return; }
      var p = drawerParams({ action: 'export' }); delete p.sort; if (S.sort) { p.sort = S.sort; p.dir = S.dir; }
      window.location.href = API + '?' + new URLSearchParams(p).toString();
    });
    window.addEventListener('popstate', function () { /* replaceState only; nothing to do */ });
  }
  function stepDay(n) {
    if (!S.dr || !S.dr.date) { return; }
    var i = S.data.days.indexOf(S.dr.date) + n;
    if (i < 0 || i >= S.data.days.length) { return; }
    S.dr.date = S.data.days[i]; S.page = 1; writeUrl(); loadDetail();
  }

  /* ------------------------------------------------------------ start */
  var openFromUrl = readUrl();
  setRangeUi(); applyModeUi(); bind();
  apiPlain({ action: 'meta' }).then(function (m) {
    if (!m || !m.ok) { $('#ifxGridWrap').innerHTML = failBox(m || { error: 'Could not load the report.' }, false); $('#ifxCust').innerHTML = '<option>-</option>'; return; }
    buildMeta(m); writeUrl(); loadMatrix();
    if (openFromUrl) {
      var wait = setInterval(function () { if (S.data) { clearInterval(wait); openDrawer(openFromUrl.stage, openFromUrl.date, null); } }, 80);
      setTimeout(function () { clearInterval(wait); }, 8000);
    }
  }).catch(function () { $('#ifxGridWrap').innerHTML = failBox({ error: 'Could not reach the server.' }, false); });
})();
    </script>
    <?php mu_foot(); ?>
  </body>
</html>

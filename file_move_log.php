<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
require_once __DIR__ . '/includes/file_move_log.php';
fml_ensure($db);
$is_ajax = isset($_REQUEST['ajax']);
ob_start();

/* Filters: ?id=<inward id>  ?pub=<article id text>  ?result=FAILED|SKIPPED|MOVED  ?from_dt=dd-mm-yyyy&to_dt=dd-mm-yyyy  ?per=25|50|100  ?pg=<page> */
$inw_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$pub    = isset($_REQUEST['pub']) ? trim($_REQUEST['pub']) : '';
$only   = isset($_REQUEST['result']) && in_array($_REQUEST['result'], array('MOVED', 'FAILED', 'SKIPPED'), true) ? $_REQUEST['result'] : '';
$stage  = isset($_REQUEST['stage']) ? trim($_REQUEST['stage']) : '';
$per    = isset($_REQUEST['per']) && in_array((int)$_REQUEST['per'], array(10, 25, 50, 100), true) ? (int)$_REQUEST['per'] : 25;
$pg     = isset($_REQUEST['pg']) ? max(1, (int)$_REQUEST['pg']) : 1;
$dmy    = function ($s) { return preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', (string)$s, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? $m[3] . '-' . $m[2] . '-' . $m[1] : ''; };
$from_dt = isset($_REQUEST['from_dt']) ? trim($_REQUEST['from_dt']) : '';
$to_dt   = isset($_REQUEST['to_dt']) ? trim($_REQUEST['to_dt']) : '';
$from_iso = $dmy($from_dt); $to_iso = $dmy($to_dt);
if (!$from_iso) { $from_dt = ''; }
if (!$to_iso)   { $to_dt = ''; }

$where = array('1=1');
if ($inw_id > 0)  { $where[] = 'inw_id = ' . $inw_id; }
if ($pub !== '')  { $where[] = "pub_id LIKE '%" . $db->real_escape_string($pub) . "%'"; }
if ($stage !== '') {
	/* a stage from the global list (FP, REV, FIN, PMC, JSTAGE ...) also covers its numbered rounds: REV = REV1, REV2 ...; anything else is matched exactly */
	$where[] = isset($project_status_types[$stage]) ? "stage REGEXP '^" . $db->real_escape_string($stage) . "[0-9]*$'" : "stage = '" . $db->real_escape_string($stage) . "'";
}
if ($from_iso)    { $where[] = "created_on >= '" . $from_iso . " 00:00:00'"; }
if ($to_iso)      { $where[] = "created_on <= '" . $to_iso . " 23:59:59'"; }
$rowcond = $only === 'MOVED' ? "result='MOVED'" : ($only === 'FAILED' ? "result='FAILED'" : ($only === 'SKIPPED' ? "(result='SKIPPED' OR kind='NOTE' OR kind='CONFIG')" : ''));
if ($rowcond !== '') { $where[] = $rowcond; }
$W = implode(' AND ', $where);

$total = 0;
$rt = $db->query("SELECT COUNT(DISTINCT batch_id) AS n FROM inw_file_move_log WHERE $W");
if ($rt && ($x = $rt->fetch_assoc())) { $total = (int)$x['n']; }
$pages = max(1, (int)ceil($total / $per));
if ($pg > $pages) { $pg = $pages; }
$offset = ($pg - 1) * $per;

$batches = array();
$rs = $db->query("SELECT batch_id, MAX(created_on) AS at, MIN(inw_id) AS inw_id, MIN(pub_id) AS pub_id, MIN(stage) AS stage, MIN(from_dept) AS from_dept, MIN(user_name) AS user_name,
                         SUM(kind='FILE' AND result='MOVED') AS moved, SUM(result='FAILED') AS failed, SUM(kind='FILE' AND result='SKIPPED') AS skipped
                  FROM inw_file_move_log WHERE $W GROUP BY batch_id ORDER BY at DESC LIMIT $offset, $per");
while ($rs && ($b = $rs->fetch_assoc())) { $batches[] = $b; }
/* the stage list is the global array (includes/global_variables.php), the same one Add Article and Start Work use */
$stages = isset($project_status_types) && is_array($project_status_types) ? $project_status_types : array();
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$size = function ($b) { if ($b === null || $b === '') { return ''; } $b = (float)$b; return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB'; };
/* a link to another page that keeps every filter */
$qs = function ($over) use ($inw_id, $pub, $only, $per, $from_dt, $to_dt, $stage) {
	$p = array('id' => $inw_id ?: '', 'pub' => $pub, 'stage' => $stage, 'result' => $only, 'per' => $per, 'from_dt' => $from_dt, 'to_dt' => $to_dt);
	foreach ($over as $k => $v) { $p[$k] = $v; }
	return 'file_move_log.php?' . http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null; }));
};
$base = isset($_COOKIE['set_url']) ? $_COOKIE['set_url'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php include('includes/title.php'); ?>
  <?php echo styles(); ?>
  <?php lu_css(); ?>
  <link rel="stylesheet" href="<?php echo $e($base); ?>/dv-rangepicker.css?v=1">
  <style>
    .lu-count{display:none !important;}
    .fm-wrap{width:min(1280px,calc(100% - 28px));margin:14px auto 40px;}
    .fm-filter{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-end;background:#fff;border:1px solid #e4e9ef;border-radius:14px;padding:12px 16px;margin-bottom:14px;box-shadow:0 4px 18px rgba(20,48,74,.06);}
    .fm-filter label{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7785;margin:0 0 5px;}
    .fm-filter input[type=text],.fm-filter select{height:42px;border:1px solid #d5dde6;border-radius:10px;padding:0 12px;background:#fbfcfe;min-width:170px;}
    .fm-filter .dvr{min-width:240px;display:block;}
    .fm-filter > button[type=submit],.fm-filter > a.rst{height:42px;padding:0 20px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);display:inline-flex;align-items:center;text-decoration:none;cursor:pointer;}
    .fm-filter > a.rst{background:#fff;color:#14304a;border:1px solid #cfd8e3;}
    .fm-batch{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);margin-bottom:16px;overflow:hidden;}
    .fm-head{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:14px 20px;background:#f6f9fc;border-bottom:1px solid #e4e9ef;}
    .fm-head b{font-size:15px;color:#14304a;} .fm-head .m{color:#6b7785;font-size:12.5px;}
    .fm-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
    .fm-pill.ok{background:#e7f6ee;color:#1f9d6a;} .fm-pill.bad{background:#fdeeee;color:#d64545;} .fm-pill.skip{background:#fff4e8;color:#d2631d;} .fm-pill.note{background:#eaf2fa;color:#1b6ca8;}
    .fm-tbl{width:100%;border-collapse:collapse;font-size:13.5px;} .fm-tbl th{background:#eef3f8;color:#14304a;text-align:left;padding:9px 14px;font-size:11.5px;text-transform:uppercase;letter-spacing:.3px;}
    .fm-tbl td{padding:9px 14px;border-bottom:1px solid #eef1f5;vertical-align:top;color:#22303f;} .fm-tbl tr:last-child td{border-bottom:0;}
    .fm-path{font-family:Consolas,monospace;font-size:12px;color:#5b6b7b;word-break:break-all;}
    .fm-ext{display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;} .fm-ext .x{display:inline-block;padding:2px 8px;border-radius:6px;background:#eaf2fa;color:#1b6ca8;font-size:11.5px;font-weight:700;font-family:Consolas,monospace;} .fm-ext .x.f{background:#f3eefb;color:#6f42c1;font-family:inherit;}
    .fm-reason{color:#d64545;font-weight:600;} .fm-empty{padding:40px;text-align:center;color:#6b7785;background:#fff;border:1px dashed #cfd8e3;border-radius:16px;}
    #fm-results{transition:opacity .15s;} #fm-results.fm-loading{opacity:.45;pointer-events:none;}
    .fm-pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin:6px 2px 0;}
    .fm-pager .info{color:#6b7785;font-size:13px;} .fm-pager .pages{display:flex;flex-wrap:wrap;gap:4px;}
    .fm-pager a,.fm-pager span.pg{min-width:38px;height:38px;padding:0 12px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d9e2ec;border-radius:9px;background:#fff;color:#33475b;font-weight:600;text-decoration:none;}
    .fm-pager a:hover{background:#eef5fb;color:#14304a;} .fm-pager span.cur{background:linear-gradient(135deg,#e8742a,#f08a45);color:#fff;border-color:transparent;box-shadow:0 4px 10px rgba(232,116,42,.3);}
    .fm-pager span.off{opacity:.45;} .fm-pager span.gap{border:0;background:transparent;min-width:20px;padding:0;}
  </style>
</head>
<body>
<div class="wholeouter">
  <?php include("includes/top-header.php"); ?>
  <?php lu_hero('File movement log', 'Start Work &rsaquo; files copied to the next department', '#', ''); ?>
  <div class="fm-wrap">
    <form class="fm-filter" id="fm-form" method="get" action="file_move_log.php">
      <div><label>Article ID</label><input type="text" name="pub" value="<?php echo $e($pub); ?>" placeholder="e.g. ATCS-OA-26-00129_Terada"></div>
      <div><label>Completed between</label><div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo $e($from_dt); ?>" data-to="<?php echo $e($to_dt); ?>" data-format="dmy"></div></div>
      <div><label>Stage</label><select name="stage"><option value="">All stages</option><?php foreach ($stages as $sg => $sgname) { echo '<option value="' . $e($sg) . '"' . ($sg === $stage ? ' selected' : '') . '>' . $e($sg) . ($sgname !== $sg ? ' - ' . $e($sgname) : '') . '</option>'; } ?></select></div>
      <div><label>Show</label><select name="result"><option value="">Everything</option>
        <option value="FAILED"<?php echo $only === 'FAILED' ? ' selected' : ''; ?>>Only failed</option>
        <option value="SKIPPED"<?php echo $only === 'SKIPPED' ? ' selected' : ''; ?>>Only skipped / notes</option>
        <option value="MOVED"<?php echo $only === 'MOVED' ? ' selected' : ''; ?>>Only copied</option></select></div>
      <div><label>Per page</label><select name="per"><?php foreach (array(10, 25, 50, 100) as $n) { echo '<option value="' . $n . '"' . ($n === $per ? ' selected' : '') . '>' . $n . '</option>'; } ?></select></div>
      <?php if ($inw_id) { ?><input type="hidden" name="id" value="<?php echo (int)$inw_id; ?>"><?php } ?>
      <button type="submit">Search</button><a class="rst" href="file_move_log.php">Clear</a>
    </form>
<!--FM-START--><div id="fm-results">
<?php if (!$batches) { ?>
    <div class="fm-empty"><b>No file movement found<?php echo ($inw_id || $pub !== '' || $from_dt !== '' || $only !== '' || $stage !== '') ? ' for these filters' : ' yet'; ?>.</b><br>A line is written each time a stage is completed in Start Work and its files are copied to the next department.</div>
<?php } foreach ($batches as $b) {
	$cond = "batch_id = '" . $db->real_escape_string($b['batch_id']) . "'" . ($rowcond !== '' ? ' AND ' . $rowcond : '');
	$rows = $db->query("SELECT * FROM inw_file_move_log WHERE $cond ORDER BY id ASC");
?>
    <div class="fm-batch">
      <div class="fm-head">
        <b><?php echo $e($b['pub_id']); ?></b>
        <span class="fm-pill note" title="The stage this was completed in"><?php echo $e($b['stage']); ?></span>
        <span class="m"><?php echo strpos((string)$b['batch_id'], 'CFG-') === 0 ? 'reported from Start Work in' : 'completed in'; ?> <?php echo $e($b['from_dept']); ?> &middot; by <?php echo $e($b['user_name'] ?: '-'); ?> &middot; <?php echo $e(date('d-M-Y H:i:s', strtotime($b['at']))); ?></span>
        <span style="flex:1"></span>
        <?php if (strpos((string)$b['batch_id'], 'CFG-') === 0) { ?><span class="fm-pill note">file movement not configured</span><?php } else { ?><span class="fm-pill ok"><?php echo (int)$b['moved']; ?> copied</span><?php } ?>
        <?php if ((int)$b['failed']) { ?><span class="fm-pill bad"><?php echo (int)$b['failed']; ?> failed</span><?php } ?>
        <?php if ((int)$b['skipped']) { ?><span class="fm-pill skip"><?php echo (int)$b['skipped']; ?> skipped</span><?php } ?>
      </div>
      <table class="fm-tbl">
        <thead><tr><th style="width:150px">To department</th><th>File</th><th style="width:70px">Size</th><th style="width:110px">Result</th><th>From &rarr; To / Reason</th></tr></thead>
        <tbody>
<?php while ($rows && ($r = $rows->fetch_assoc())) {
	$pc = $r['result'] === 'MOVED' ? 'ok' : ($r['result'] === 'FAILED' ? 'bad' : 'skip');
	$lab = $r['kind'] === 'CONFIG' ? ($r['result'] === 'ALERT' ? 'Reported to admin' : 'Report not mailed') : ($r['kind'] === 'NOTE' ? ($r['result'] === 'FAILED' ? 'Problem' : 'Note') : ($r['result'] === 'MOVED' ? 'Copied' : ($r['result'] === 'FAILED' ? 'Failed' : 'Skipped')));
?>
          <tr>
            <td><?php echo $e($r['to_dept'] ?: '-'); ?>
<?php /* what this destination is configured to move (older rows: read it back from the "selected: ..." part of the reason) */
	$mt = trim((string)($r['move_types'] ?? ''));
	if ($mt === '' && preg_match('/\(selected: ([^)]*)\)/', (string)$r['reason'], $mm)) { $mt = trim(str_replace(',', ' ', $mm[1])); }
	if ($mt !== '') { echo '<div class="fm-ext" title="File types / folders this destination is set to receive">'; foreach (preg_split('/\s{2,}|\s+(?=folders:)/', $mt) as $grp) { if (stripos($grp, 'folders:') === 0) { echo '<span class="x f">' . $e($grp) . '</span>'; } else { foreach (preg_split('/\s+/', trim($grp)) as $ext) { if ($ext !== '') { echo '<span class="x">' . $e($ext) . '</span>'; } } } } echo '</div>'; } ?></td>
            <td><?php echo ($r['kind'] === 'NOTE' || $r['kind'] === 'CONFIG') ? '<i style="color:#6b7785">(whole step)</i>' : $e($r['file_name']); ?></td>
            <td><?php echo $e($size($r['size_bytes'])); ?></td>
            <td><span class="fm-pill <?php echo $r['kind'] === 'CONFIG' ? ($r['result'] === 'ALERT' ? 'note' : 'bad') : ($r['kind'] === 'NOTE' && $r['result'] !== 'FAILED' ? 'note' : $pc); ?>"><?php echo $lab; ?></span></td>
            <td>
<?php if ($r['reason'] !== '') { ?><div class="<?php echo $r['result'] === 'FAILED' ? 'fm-reason' : ''; ?>"><?php echo $e($r['reason']); ?></div><?php } ?>
<?php if ($r['src_path'] !== '' || $r['dest_path'] !== '') { ?><div class="fm-path"><?php echo $e($r['src_path']); ?><?php echo $r['dest_path'] !== '' ? ' &rarr; ' . $e($r['dest_path']) : ''; ?></div><?php } ?>
            </td>
          </tr>
<?php } ?>
        </tbody>
      </table>
    </div>
<?php } ?>
<?php if ($total > 0) {
	$first = $offset + 1; $last = min($offset + $per, $total);
	$win = array(1, $pages); for ($i = $pg - 2; $i <= $pg + 2; $i++) { if ($i >= 1 && $i <= $pages) { $win[] = $i; } }
	$win = array_values(array_unique($win)); sort($win);
?>
    <div class="fm-pager" data-total="<?php echo (int)$total; ?>">
      <div class="info">Showing <?php echo $first; ?>&ndash;<?php echo $last; ?> of <?php echo $total; ?> completion<?php echo $total === 1 ? '' : 's'; ?></div>
      <div class="pages">
<?php if ($pg > 1) { ?><a href="<?php echo $e($qs(array('pg' => $pg - 1))); ?>">Previous</a><?php } else { ?><span class="pg off">Previous</span><?php } ?>
<?php $prev = 0; foreach ($win as $n) {
	if ($prev && $n > $prev + 1) { echo '<span class="pg gap">&hellip;</span>'; }
	echo $n === $pg ? '<span class="pg cur">' . $n . '</span>' : '<a href="' . $e($qs(array('pg' => $n))) . '">' . $n . '</a>';
	$prev = $n;
} ?>
<?php if ($pg < $pages) { ?><a href="<?php echo $e($qs(array('pg' => $pg + 1))); ?>">Next</a><?php } else { ?><span class="pg off">Next</span><?php } ?>
      </div>
    </div>
<?php } ?>
</div><!--FM-END-->
  </div>
</div>
<?php echo scripts(); ?>
<script src="<?php echo $e($base); ?>/dv-rangepicker.js?v=1"></script>
<script>
/* paging and filters without reloading the page (the server sends back only the results part) */
(function () {
  var box = document.getElementById('fm-results'), form = document.getElementById('fm-form'); if (!box || !form) { return; }
  var busy = false;
  function load(url, push) {
    if (busy) { return; } busy = true; box.classList.add('fm-loading');
    var u = url + (url.indexOf('?') > -1 ? '&' : '?') + 'ajax=1';
    fetch(u, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
      var m = html.match(/<!--FM-START-->([\s\S]*)<!--FM-END-->/);
      if (!m) { location.href = url; return; }
      var tmp = document.createElement('div'); tmp.innerHTML = m[1];
      var nb = tmp.firstElementChild; box.innerHTML = nb ? nb.innerHTML : m[1];
      if (push) { history.pushState({ u: url }, '', url); }
      var top = form.getBoundingClientRect().bottom + window.pageYOffset - 12; window.scrollTo({ top: top, behavior: 'smooth' });
    }).catch(function () { location.href = url; }).then(function () { busy = false; box.classList.remove('fm-loading'); });
  }
  box.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('.fm-pager a') : null; if (!a) { return; }
    e.preventDefault(); load(a.getAttribute('href'), true);
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var q = [], f = form.elements;
    for (var i = 0; i < f.length; i++) { var x = f[i]; if (x.name && x.value !== '' && x.type !== 'submit') { q.push(encodeURIComponent(x.name) + '=' + encodeURIComponent(x.value)); } }
    load('file_move_log.php' + (q.length ? '?' + q.join('&') : ''), true);
  });
  form.addEventListener('change', function (e) { if (e.target.tagName === 'SELECT') { form.dispatchEvent(new Event('submit', { cancelable: true })); } });
  window.addEventListener('popstate', function () { load(location.pathname.split('/').pop() + location.search, false); });
})();
</script>
<?php echo footer(); ?>
</body>
</html>
<?php
$page = ob_get_clean();
if ($is_ajax && preg_match('/<!--FM-START-->.*<!--FM-END-->/s', $page, $mm)) { echo $mm[0]; } else { echo $page; }

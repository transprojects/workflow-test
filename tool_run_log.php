<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
$is_ajax = isset($_REQUEST['ajax']);
ob_start();

/* Filters: ?pub=<article id text> ?user=<name> ?tool=<tool id> ?result=completed|failed|no_connection|no_response ?from_dt=dd-mm-yyyy&to_dt=dd-mm-yyyy ?per=25|50|100 ?pg=<page> */
$has_tbl = ($rt = $db->query("SHOW TABLES LIKE 'inw_tool_run'")) && $rt->num_rows > 0;
$has_args = $has_tbl && ($rc = $db->query("SHOW COLUMNS FROM inw_tool_run LIKE 'request_args'")) && $rc->num_rows > 0;
$pub    = isset($_REQUEST['pub']) ? trim($_REQUEST['pub']) : '';
$user   = isset($_REQUEST['user']) ? trim($_REQUEST['user']) : '';
$tool   = isset($_REQUEST['tool']) ? trim($_REQUEST['tool']) : '';
$res    = isset($_REQUEST['result']) && in_array($_REQUEST['result'], array('completed', 'failed', 'no_connection', 'no_response'), true) ? $_REQUEST['result'] : '';
$per    = isset($_REQUEST['per']) && in_array((int)$_REQUEST['per'], array(10, 25, 50, 100), true) ? (int)$_REQUEST['per'] : 25;
$pg     = isset($_REQUEST['pg']) ? max(1, (int)$_REQUEST['pg']) : 1;
$dmy    = function ($s) { return preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', (string)$s, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? $m[3] . '-' . $m[2] . '-' . $m[1] : ''; };
$from_dt = isset($_REQUEST['from_dt']) ? trim($_REQUEST['from_dt']) : '';
$to_dt   = isset($_REQUEST['to_dt']) ? trim($_REQUEST['to_dt']) : '';
$from_iso = $dmy($from_dt); $to_iso = $dmy($to_dt);
if (!$from_iso) { $from_dt = ''; }
if (!$to_iso)   { $to_dt = ''; }

$where = array('1=1');
if ($pub !== '')  { $where[] = "pub_id LIKE '%" . $db->real_escape_string($pub) . "%'"; }
if ($user !== '') { $where[] = "user_name LIKE '%" . $db->real_escape_string($user) . "%'"; }
if ($tool !== '') { $where[] = "tool = '" . $db->real_escape_string($tool) . "'"; }
if ($res !== '')  { $where[] = "result = '" . $res . "'"; }
if ($from_iso)    { $where[] = "created_on >= '" . $from_iso . " 00:00:00'"; }
if ($to_iso)      { $where[] = "created_on <= '" . $to_iso . " 23:59:59'"; }
$W = implode(' AND ', $where);

$total = 0; $rows = array(); $tools = array(); $sum = array('completed' => 0, 'failed' => 0, 'no_connection' => 0, 'no_response' => 0);
if ($has_tbl) {
	$r = $db->query("SELECT COUNT(*) AS n FROM inw_tool_run WHERE $W");
	if ($r && ($x = $r->fetch_assoc())) { $total = (int)$x['n']; }
	$r = $db->query("SELECT result, COUNT(*) AS n FROM inw_tool_run WHERE $W GROUP BY result");
	while ($r && ($x = $r->fetch_assoc())) { if (isset($sum[$x['result']])) { $sum[$x['result']] = (int)$x['n']; } }
	$r = $db->query("SELECT tool, MAX(tool_label) AS label FROM inw_tool_run GROUP BY tool ORDER BY label");
	while ($r && ($x = $r->fetch_assoc())) { $tools[$x['tool']] = $x['label'] ?: $x['tool']; }
}
$pages = max(1, (int)ceil($total / $per));
if ($pg > $pages) { $pg = $pages; }
$offset = ($pg - 1) * $per;
if ($has_tbl) {
	$r = $db->query("SELECT * FROM inw_tool_run WHERE $W ORDER BY id DESC LIMIT $offset, $per");
	while ($r && ($x = $r->fetch_assoc())) { $rows[] = $x; }
}
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$qs = function ($over) use ($pub, $user, $tool, $res, $per, $from_dt, $to_dt) {
	$p = array('pub' => $pub, 'user' => $user, 'tool' => $tool, 'result' => $res, 'per' => $per, 'from_dt' => $from_dt, 'to_dt' => $to_dt);
	foreach ($over as $k => $v) { $p[$k] = $v; }
	return 'tool_run_log.php?' . http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null; }));
};
$lab = array('completed' => 'Completed', 'failed' => 'Failed', 'no_connection' => 'Server not connected', 'no_response' => 'No response');
$cls = array('completed' => 'ok', 'failed' => 'bad', 'no_connection' => 'skip', 'no_response' => 'skip');
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
    .fm-wrap{width:min(1400px,calc(100% - 28px));margin:14px auto 40px;}
    .fm-filter{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-end;background:#fff;border:1px solid #e4e9ef;border-radius:14px;padding:12px 16px;margin-bottom:14px;box-shadow:0 4px 18px rgba(20,48,74,.06);}
    .fm-filter label{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7785;margin:0 0 5px;}
    .fm-filter input[type=text],.fm-filter select{height:42px;border:1px solid #d5dde6;border-radius:10px;padding:0 12px;background:#fbfcfe;min-width:160px;}
    .fm-filter .dvr{min-width:240px;display:block;}
    .fm-filter > button[type=submit],.fm-filter > a.rst{height:42px;padding:0 20px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);display:inline-flex;align-items:center;text-decoration:none;cursor:pointer;}
    .fm-filter > a.rst{background:#fff;color:#14304a;border:1px solid #cfd8e3;}
    .tr-sum{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;}
    .tr-sum .c{flex:1 1 180px;background:#fff;border:1px solid #e4e9ef;border-radius:14px;padding:12px 16px;box-shadow:0 4px 18px rgba(20,48,74,.06);}
    .tr-sum .c b{display:block;font-size:24px;color:#14304a;} .tr-sum .c span{font-size:12px;color:#6b7785;text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
    .fm-batch{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);overflow-x:auto;margin-bottom:16px;}
    .fm-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
    .fm-pill.ok{background:#e7f6ee;color:#1f9d6a;} .fm-pill.bad{background:#fdeeee;color:#d64545;} .fm-pill.skip{background:#fff4e8;color:#d2631d;}
    .fm-tbl{width:100%;border-collapse:collapse;font-size:13px;} .fm-tbl th{background:#eef3f8;color:#14304a;text-align:left;padding:9px 12px;font-size:11.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;}
    .fm-tbl td{padding:9px 12px;border-bottom:1px solid #eef1f5;vertical-align:top;color:#22303f;} .fm-tbl tr:last-child td{border-bottom:0;}
    .fm-path{font-family:Consolas,monospace;font-size:11.5px;color:#5b6b7b;word-break:break-all;max-width:360px;}
    .tr-resp{max-width:380px;word-break:break-word;} .tr-resp.bad{color:#d64545;font-weight:600;}
    .fm-empty{padding:40px;text-align:center;color:#6b7785;background:#fff;border:1px dashed #cfd8e3;border-radius:16px;}
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
  <?php lu_hero('Tool run log', 'Start Work &rsaquo; every tool button: who pressed it, what was sent to the server and what came back', '#', ''); ?>
  <div class="fm-wrap">
    <form class="fm-filter" id="fm-form" method="get" action="tool_run_log.php">
      <div><label>Article ID</label><input type="text" name="pub" value="<?php echo $e($pub); ?>" placeholder="e.g. EJ-TRANSFORMA-TEST-09098"></div>
      <div><label>User</label><input type="text" name="user" value="<?php echo $e($user); ?>" placeholder="Name"></div>
      <div><label>Pressed between</label><div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo $e($from_dt); ?>" data-to="<?php echo $e($to_dt); ?>" data-format="dmy"></div></div>
      <div><label>Tool</label><select name="tool"><option value="">All tools</option><?php foreach ($tools as $tid => $tl) { echo '<option value="' . $e($tid) . '"' . ($tid === $tool ? ' selected' : '') . '>' . $e($tl) . '</option>'; } ?></select></div>
      <div><label>Result</label><select name="result"><option value="">Everything</option><?php foreach ($lab as $k => $v) { echo '<option value="' . $k . '"' . ($k === $res ? ' selected' : '') . '>' . $e($v) . '</option>'; } ?></select></div>
      <div><label>Per page</label><select name="per"><?php foreach (array(10, 25, 50, 100) as $n) { echo '<option value="' . $n . '"' . ($n === $per ? ' selected' : '') . '>' . $n . '</option>'; } ?></select></div>
      <button type="submit">Search</button><a class="rst" href="tool_run_log.php">Clear</a>
    </form>
<!--FM-START--><div id="fm-results">
    <div class="tr-sum">
      <div class="c"><span>Completed</span><b><?php echo (int)$sum['completed']; ?></b></div>
      <div class="c"><span>Failed</span><b style="color:#d64545"><?php echo (int)$sum['failed']; ?></b></div>
      <div class="c"><span>Server not connected</span><b style="color:#d2631d"><?php echo (int)$sum['no_connection']; ?></b></div>
      <div class="c"><span>No response</span><b style="color:#d2631d"><?php echo (int)$sum['no_response']; ?></b></div>
    </div>
<?php if (!$rows) { ?>
    <div class="fm-empty"><b>No tool runs found<?php echo ($pub !== '' || $user !== '' || $tool !== '' || $res !== '' || $from_dt !== '') ? ' for these filters' : ' yet'; ?>.</b><br>A line is written each time a tool button (Package creation, ePub, validators ...) is pressed on the Start Work page.</div>
<?php } else { ?>
    <div class="fm-batch">
      <table class="fm-tbl">
        <thead><tr><th>Time</th><th>User</th><th>Article / stage / dept</th><th>Tool</th><th>Server &amp; arguments sent</th><th>Result</th><th>HTTP</th><th>Answered</th><th>Took</th><th>Response / reason</th></tr></thead>
        <tbody>
<?php foreach ($rows as $r) {
	$ms = (int)$r['duration_ms']; $took = $ms >= 60000 ? floor($ms / 60000) . 'm ' . round(($ms % 60000) / 1000) . 's' : round($ms / 1000, 1) . ' s';
?>
          <tr>
            <td style="white-space:nowrap"><?php echo $e(date('d-M-Y H:i:s', strtotime($r['created_on']))); ?></td>
            <td><?php echo $e($r['user_name'] ?: '-'); ?><div class="fm-path"><?php echo $e($r['ip_address']); ?></div></td>
            <td><b><?php echo $e($r['pub_id']); ?></b><div class="fm-path"><?php echo $e($r['stage']); ?> &middot; <?php echo $e($r['dept']); ?></div></td>
            <td><?php echo $e($r['tool_label'] ?: $r['tool']); ?></td>
            <td><?php if ($r['server_url'] !== '' && $r['server_url'] !== null) { ?><div class="fm-path"><?php echo $e($r['server_url']); ?></div><?php } ?>
                <?php if ($has_args && !empty($r['request_args'])) { ?><div class="fm-path" title="Arguments passed to the server"><?php echo $e(str_replace('&', ' &  ', $r['request_args'])); ?></div><?php } ?>
                <?php if (($r['server_url'] === '' || $r['server_url'] === null) && empty($r['request_args'])) { echo '<span style="color:#9aa6b2">&ndash;</span>'; } ?></td>
            <td><span class="fm-pill <?php echo $cls[$r['result']] ?? 'bad'; ?>"><?php echo $e($lab[$r['result']] ?? $r['result']); ?></span></td>
            <td><?php echo (int)$r['http_status'] ?: '-'; ?></td>
            <td><?php echo $r['response_received'] ? 'Yes' : 'No'; ?></td>
            <td style="white-space:nowrap"><?php echo $e($took); ?></td>
            <td class="tr-resp<?php echo $r['result'] !== 'completed' ? ' bad' : ''; ?>"><?php echo $e($r['message']); ?></td>
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
      <div class="info">Showing <?php echo $first; ?>&ndash;<?php echo $last; ?> of <?php echo $total; ?> run<?php echo $total === 1 ? '' : 's'; ?></div>
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
    load('tool_run_log.php' + (q.length ? '?' + q.join('&') : ''), true);
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

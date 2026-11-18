<?php include "includes/includes.php";
// 2026-11-01: TSP CE Style Validator / Bypass Validation enabled; QC tool log added.
include_once 'includes/list_ui.php';

/* QC Tool Log: the first validator report per article + tool (qc_tool_first_report) and every bypass (qc_tool_bypass_log),
   both written by journal_style_validator.php. Stored HTML reports live under E:/Tool_Reports/ (outside the web root) and are
   only served through ?view=first|bypass&id=<row id> below.
   Filters: ?t=first|bypass ?pub= ?tool= ?status=PASS|FAIL ?user= ?from_dt=dd-mm-yyyy&to_dt=dd-mm-yyyy ?per=25|50|100 ?pg= */
$reports_dir = 'E:/Tool_Reports/';
$tool_names = array('peerj' => 'PeerJ CE (XMLQualityChecker)', 'iap' => 'IAP XML (IAPQualityChecker)', 'tsp' => 'TSP CE (XMLQualityChecker)');
$has_first  = ($r = $db->query("SHOW TABLES LIKE 'qc_tool_first_report'")) && $r->num_rows > 0;
$has_bypass = ($r = $db->query("SHOW TABLES LIKE 'qc_tool_bypass_log'")) && $r->num_rows > 0;

/* A row can have report_file NULL even though the HTML is on disk (older code / a race between two runs): look for the file the
   logger would have written - <tool>/<run date>/<tool>_<pub>_<stage>_<dept>_<first|bypass>_<time>.html - and use the newest match. */
function qc_find_report($dir, $r, $kind, $when)
{
	if (!empty($r['report_file'])) { return $r['report_file']; }
	$clean = function ($s) { $s = str_replace('..', '_', preg_replace('/[^A-Za-z0-9._-]+/', '_', trim((string)$s))); return trim($s, '._') === '' ? '_' : $s; };
	$rel = $clean($r['tool']) . '/' . date('Y-m-d', strtotime($when)) . '/';
	$hits = glob($dir . $rel . $clean($r['tool']) . '_' . $clean($r['pub_id']) . '_' . $clean($r['stage']) . '_' . $clean($r['dept']) . '_' . $kind . '_*.html');
	if (!$hits) { return null; }
	sort($hits);
	return $rel . basename($kind === 'first' ? $hits[0] : end($hits));
}

/* Stored report viewer */
if (isset($_GET['view'])) {
	$v = $_GET['view'] === 'bypass' ? 'bypass' : 'first';
	$tbl = $v === 'bypass' ? 'qc_tool_bypass_log' : 'qc_tool_first_report';
	$f = null;
	if (($v === 'bypass' ? $has_bypass : $has_first) && ($r = $db->query("SELECT * FROM $tbl WHERE id = " . (int)$_GET['id'])) && ($x = $r->fetch_assoc())) { $f = qc_find_report($reports_dir, $x, $v, $v === 'bypass' ? $x['bypassed_at'] : $x['run_at']); }
	if (!$f || strpos($f, '..') !== false || !is_file($reports_dir . $f)) { echo 'No HTML report is stored for this entry.'; exit; }
	header('Content-Type: text/html; charset=utf-8');
	readfile($reports_dir . $f);
	exit;
}

$tab    = isset($_REQUEST['t']) && $_REQUEST['t'] === 'bypass' ? 'bypass' : 'first';
$pub    = isset($_REQUEST['pub']) ? trim($_REQUEST['pub']) : '';
$tool   = isset($_REQUEST['tool']) && isset($tool_names[$_REQUEST['tool']]) ? $_REQUEST['tool'] : '';
$status = isset($_REQUEST['status']) && in_array($_REQUEST['status'], array('PASS', 'FAIL'), true) ? $_REQUEST['status'] : '';
$user   = isset($_REQUEST['user']) ? trim($_REQUEST['user']) : '';
$per    = isset($_REQUEST['per']) && in_array((int)$_REQUEST['per'], array(10, 25, 50, 100), true) ? (int)$_REQUEST['per'] : 25;
$pg     = isset($_REQUEST['pg']) ? max(1, (int)$_REQUEST['pg']) : 1;
$dmy    = function ($s) { return preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', (string)$s, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? $m[3] . '-' . $m[2] . '-' . $m[1] : ''; };
$from_dt = isset($_REQUEST['from_dt']) ? trim($_REQUEST['from_dt']) : '';
$to_dt   = isset($_REQUEST['to_dt']) ? trim($_REQUEST['to_dt']) : '';
$from_iso = $dmy($from_dt); $to_iso = $dmy($to_dt);
if (!$from_iso) { $from_dt = ''; }
if (!$to_iso)   { $to_dt = ''; }

$tbl     = $tab === 'bypass' ? 'qc_tool_bypass_log' : 'qc_tool_first_report';
$tbl_ok  = $tab === 'bypass' ? $has_bypass : $has_first;
$datecol = $tab === 'bypass' ? 'bypassed_at' : 'run_at';
$usercol = $tab === 'bypass' ? 'bypassed_by' : 'run_by';
$where = array('1=1');
if ($pub !== '')  { $where[] = "t.pub_id LIKE '%" . $db->real_escape_string($pub) . "%'"; }
if ($tool !== '') { $where[] = "t.tool = '" . $tool . "'"; }
if ($user !== '') { $where[] = "t.$usercol LIKE '%" . $db->real_escape_string($user) . "%'"; }
if ($tab === 'first' && $status !== '') { $where[] = "t.status = '" . $status . "'"; }
if ($from_iso)    { $where[] = "t.$datecol >= '" . $from_iso . " 00:00:00'"; }
if ($to_iso)      { $where[] = "t.$datecol <= '" . $to_iso . " 23:59:59'"; }
$W = implode(' AND ', $where);

/* ZIP download: the selected rows (ids[]) or every row matching the current filters (zip_scope=all), from either tab. For each article + tool
   the zip holds the first report AND every bypass report, plus summary.csv with the details. Layout: <article>/first/*.html, <article>/bypass/*.html */
if (isset($_POST['zip']) && $tbl_ok) {
	@set_time_limit(0); @ini_set('memory_limit', '512M');
	if (!class_exists('ZipArchive')) { echo 'PHP zip extension is not enabled on this server.'; exit; }
	$sel = array();
	if (isset($_POST['zip_scope']) && $_POST['zip_scope'] === 'all') {
		$r = $db->query("SELECT t.pub_id, t.tool FROM $tbl t WHERE $W");
		while ($r && ($x = $r->fetch_assoc())) { $sel[$x['pub_id'] . "\t" . $x['tool']] = $x; }
	} else {
		$ids = array_filter(array_map('intval', isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array()));
		if ($ids) {
			$r = $db->query("SELECT t.pub_id, t.tool FROM $tbl t WHERE t.id IN (" . implode(',', $ids) . ")");
			while ($r && ($x = $r->fetch_assoc())) { $sel[$x['pub_id'] . "\t" . $x['tool']] = $x; }
		}
	}
	if (!$sel) { echo 'Nothing selected.'; exit; }
	$firsts = array(); $byps = array();
	foreach (array_chunk(array_values($sel), 200) as $chunk) {
		$or = array();
		foreach ($chunk as $x) { $or[] = "(pub_id = '" . $db->real_escape_string($x['pub_id']) . "' AND tool = '" . $db->real_escape_string($x['tool']) . "')"; }
		$OR = implode(' OR ', $or);
		if ($has_first)  { $r = $db->query("SELECT * FROM qc_tool_first_report WHERE $OR ORDER BY run_at"); while ($r && ($x = $r->fetch_assoc())) { $firsts[] = $x; } }
		if ($has_bypass) { $r = $db->query("SELECT * FROM qc_tool_bypass_log WHERE $OR ORDER BY bypassed_at"); while ($r && ($x = $r->fetch_assoc())) { $byps[] = $x; } }
	}
	$custs = array();
	$r = $db->query("SELECT id, cust_name FROM adm_customer_master"); while ($r && ($x = $r->fetch_assoc())) { $custs[$x['id']] = $x['cust_name']; }
	$safe = function ($s) { $s = str_replace('..', '_', preg_replace('/[^A-Za-z0-9._-]+/', '_', trim((string)$s))); return trim($s, '._') === '' ? '_' : $s; };
	$zpath = tempnam(sys_get_temp_dir(), 'qcz');
	$zip = new ZipArchive();
	if ($zip->open($zpath, ZipArchive::OVERWRITE) !== true) { echo 'Could not create the ZIP file.'; exit; }
	$csv = fopen('php://temp', 'w+');
	fputcsv($csv, array('Type', 'Article', 'Customer', 'Tool', 'Stage', 'Dept', 'XML file', 'Result', 'Errors', 'Warnings', 'User', 'Date/time', 'Reason', 'IP', 'File in zip'));
	$used = array(); $nfile = 0; $nmiss = 0;
	$add = function ($kind, $x, $when, $user) use (&$zip, &$csv, &$used, &$nfile, &$nmiss, $reports_dir, $safe, $custs) {
		$rf = qc_find_report($reports_dir, $x, $kind, $when);
		$in = '';
		if ($rf && strpos($rf, '..') === false && is_file($reports_dir . $rf)) {
			$in = $safe($x['pub_id']) . '/' . $kind . '/' . basename($rf);
			for ($n = 2; isset($used[$in]); $n++) { $in = $safe($x['pub_id']) . '/' . $kind . '/' . $n . '_' . basename($rf); }
			$used[$in] = 1; $zip->addFile($reports_dir . $rf, $in); $nfile++;
		} else { $nmiss++; }
		fputcsv($csv, array($kind === 'first' ? 'First report' : 'Bypass', $x['pub_id'], isset($custs[$x['cust_id']]) ? $custs[$x['cust_id']] : '', $x['tool'], $x['stage'], $x['dept'], $x['xml_file'],
			$kind === 'first' ? $x['status'] : '', $kind === 'first' ? $x['error_count'] : '', $kind === 'first' ? $x['warning_count'] : '', $user, $when,
			$kind === 'bypass' ? $x['reason'] : '', $kind === 'bypass' ? $x['ip'] : '', $in !== '' ? $in : 'no HTML stored'));
	};
	foreach ($firsts as $x) { $add('first', $x, $x['run_at'], $x['run_by']); }
	foreach ($byps as $x)   { $add('bypass', $x, $x['bypassed_at'], $x['bypassed_by']); }
	rewind($csv);
	$zip->addFromString('summary.csv', "\xEF\xBB\xBF" . stream_get_contents($csv));
	$zip->close();
	if (!is_file($zpath) || !$nfile) { @unlink($zpath); echo 'No stored HTML reports were found for the selection.'; exit; }
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="QC_tool_reports_' . date('Ymd_His') . '.zip"');
	header('Content-Length: ' . filesize($zpath));
	readfile($zpath); @unlink($zpath);
	exit;
}

$total = 0; $rows = array(); $counts = array('first' => 0, 'bypass' => 0);
if ($has_first)  { $r = $db->query("SELECT COUNT(*) n FROM qc_tool_first_report");  if ($r && ($x = $r->fetch_assoc())) { $counts['first'] = (int)$x['n']; } }
if ($has_bypass) { $r = $db->query("SELECT COUNT(*) n FROM qc_tool_bypass_log");   if ($r && ($x = $r->fetch_assoc())) { $counts['bypass'] = (int)$x['n']; } }
if ($tbl_ok) {
	$r = $db->query("SELECT COUNT(*) n FROM $tbl t WHERE $W");
	if ($r && ($x = $r->fetch_assoc())) { $total = (int)$x['n']; }
}
$pages = max(1, (int)ceil($total / $per));
if ($pg > $pages) { $pg = $pages; }
$offset = ($pg - 1) * $per;
if ($tbl_ok) {
	$r = $db->query("SELECT t.*, c.cust_name FROM $tbl t LEFT JOIN adm_customer_master c ON c.id = t.cust_id WHERE $W ORDER BY t.$datecol DESC, t.id DESC LIMIT $offset, $per");
	while ($r && ($x = $r->fetch_assoc())) {
		$x['report_file'] = qc_find_report($reports_dir, $x, $tab, $x[$datecol]);
		if ($x['report_file'] && empty($x['report_size']) && is_file($reports_dir . $x['report_file'])) { $x['report_size'] = filesize($reports_dir . $x['report_file']); }
		$rows[] = $x;
	}
}

$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$size = function ($b) { if ($b === null || $b === '') { return ''; } $b = (float)$b; return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB'; };
$qs = function ($over) use ($tab, $pub, $tool, $status, $user, $per, $from_dt, $to_dt) {
	$p = array('t' => $tab, 'pub' => $pub, 'tool' => $tool, 'status' => $status, 'user' => $user, 'per' => $per, 'from_dt' => $from_dt, 'to_dt' => $to_dt);
	foreach ($over as $k => $v) { $p[$k] = $v; }
	return 'qc_tool_log.php?' . http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null; }));
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
    .qc-wrap{width:min(1280px,calc(100% - 28px));margin:14px auto 40px;}
    .qc-tabs{display:flex;gap:8px;margin-bottom:12px;}
    .qc-tabs a{padding:9px 20px;border-radius:999px;border:1px solid #cfd8e3;background:#fff;color:#14304a;font-weight:700;text-decoration:none;}
    .qc-tabs a.on{background:linear-gradient(135deg,#e8742a,#f08a45);color:#fff;border-color:transparent;}
    .qc-tabs a small{opacity:.75;font-weight:600;margin-left:4px;}
    .qc-filter{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-end;background:#fff;border:1px solid #e4e9ef;border-radius:14px;padding:12px 16px;margin-bottom:14px;box-shadow:0 4px 18px rgba(20,48,74,.06);}
    .qc-filter label{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7785;margin:0 0 5px;}
    .qc-filter input[type=text],.qc-filter select{height:42px;border:1px solid #d5dde6;border-radius:10px;padding:0 12px;background:#fbfcfe;min-width:160px;}
    .qc-filter .dvr{min-width:240px;display:block;}
    .qc-filter > button[type=submit],.qc-filter > a.rst{height:42px;padding:0 20px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);display:inline-flex;align-items:center;text-decoration:none;cursor:pointer;}
    .qc-filter > a.rst{background:#fff;color:#14304a;border:1px solid #cfd8e3;}
    .qc-card{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);overflow:auto;}
    .qc-tbl{width:100%;border-collapse:collapse;font-size:13.5px;} .qc-tbl th{background:#eef3f8;color:#14304a;text-align:left;padding:10px 14px;font-size:11.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;}
    .qc-tbl td{padding:10px 14px;border-bottom:1px solid #eef1f5;vertical-align:top;color:#22303f;} .qc-tbl tr:last-child td{border-bottom:0;}
    .qc-pill{display:inline-flex;align-items:center;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
    .qc-pill.ok{background:#e7f6ee;color:#1f9d6a;} .qc-pill.bad{background:#fdeeee;color:#d64545;} .qc-pill.note{background:#eaf2fa;color:#1b6ca8;} .qc-pill.skip{background:#fff4e8;color:#d2631d;}
    .qc-reason{max-width:380px;white-space:pre-wrap;word-break:break-word;}
    .qc-bar{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;margin:0 0 10px;}
    .qc-bar button{height:40px;padding:0 18px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);cursor:pointer;} .qc-bar button:disabled{opacity:.45;cursor:default;}
    .qc-bar button.alt{background:#fff;color:#14304a;border:1px solid #cfd8e3;} .qc-bar .hint{color:#6b7785;font-size:13px;}
    .qc-tbl input[type=checkbox]{width:17px;height:17px;cursor:pointer;}
    .qc-empty{padding:40px;text-align:center;color:#6b7785;}
    .qc-view{color:#e8742a;font-weight:700;text-decoration:none;} .qc-sub{color:#6b7785;font-size:12px;}
    .qc-pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin:12px 2px 0;}
    .qc-pager .info{color:#6b7785;font-size:13px;} .qc-pager .pages{display:flex;flex-wrap:wrap;gap:4px;}
    .qc-pager a,.qc-pager span.pg{min-width:38px;height:38px;padding:0 12px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d9e2ec;border-radius:9px;background:#fff;color:#33475b;font-weight:600;text-decoration:none;}
    .qc-pager a:hover{background:#eef5fb;color:#14304a;} .qc-pager span.cur{background:linear-gradient(135deg,#e8742a,#f08a45);color:#fff;border-color:transparent;}
    .qc-pager span.off{opacity:.45;} .qc-pager span.gap{border:0;background:transparent;min-width:20px;padding:0;}
  </style>
</head>
<body>
<div class="wholeouter">
  <?php include("includes/top-header.php"); ?>
  <?php lu_hero('QC tool log', 'Reports &rsaquo; first validator reports and bypasses', '#', ''); ?>
  <div class="qc-wrap">
    <div class="qc-tabs">
      <a class="<?php echo $tab === 'first' ? 'on' : ''; ?>" href="qc_tool_log.php?t=first">First reports <small>(<?php echo $counts['first']; ?>)</small></a>
      <a class="<?php echo $tab === 'bypass' ? 'on' : ''; ?>" href="qc_tool_log.php?t=bypass">Bypass log <small>(<?php echo $counts['bypass']; ?>)</small></a>
    </div>
    <form class="qc-filter" method="get" action="qc_tool_log.php">
      <input type="hidden" name="t" value="<?php echo $e($tab); ?>">
      <div><label>Article ID</label><input type="text" name="pub" value="<?php echo $e($pub); ?>" placeholder="e.g. peerj-cs-4079"></div>
      <div><label>Tool</label><select name="tool"><option value="">All tools</option><?php foreach ($tool_names as $k => $n) { echo '<option value="' . $e($k) . '"' . ($k === $tool ? ' selected' : '') . '>' . $e($n) . '</option>'; } ?></select></div>
<?php if ($tab === 'first') { ?>
      <div><label>Result</label><select name="status"><option value="">Pass &amp; fail</option><option value="PASS"<?php echo $status === 'PASS' ? ' selected' : ''; ?>>Passed</option><option value="FAIL"<?php echo $status === 'FAIL' ? ' selected' : ''; ?>>Failed</option></select></div>
<?php } ?>
      <div><label><?php echo $tab === 'bypass' ? 'Bypassed by' : 'Run by'; ?></label><input type="text" name="user" value="<?php echo $e($user); ?>"></div>
      <div><label><?php echo $tab === 'bypass' ? 'Bypassed between' : 'Run between'; ?></label><div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo $e($from_dt); ?>" data-to="<?php echo $e($to_dt); ?>" data-format="dmy"></div></div>
      <div><label>Per page</label><select name="per"><?php foreach (array(10, 25, 50, 100) as $n) { echo '<option value="' . $n . '"' . ($n === $per ? ' selected' : '') . '>' . $n . '</option>'; } ?></select></div>
      <button type="submit">Search</button><a class="rst" href="qc_tool_log.php?t=<?php echo $e($tab); ?>">Clear</a>
    </form>
    <form method="post" id="qcZipForm" action="<?php echo $e($qs(array('pg' => ''))); ?>">
<?php if ($rows) { ?>
    <div class="qc-bar">
      <button type="submit" name="zip" value="1" id="qcZipSel" disabled>Download ZIP of selected (<span id="qcSelN">0</span>)</button>
      <button type="submit" name="zip" value="1" class="alt" id="qcZipAll" onclick="document.getElementById('qcScope').value='all';return confirm('Download a ZIP for all <?php echo (int)$total; ?> rows matching the current filters? This can take a while.');">Download ZIP of all <?php echo (int)$total; ?> matching</button>
      <input type="hidden" name="zip_scope" id="qcScope" value="sel">
      <span class="hint">Each ZIP has the first report and any bypass reports for the article, plus summary.csv.</span>
    </div>
<?php } ?>
    <div class="qc-card">
<?php if (!$rows) { ?>
      <div class="qc-empty"><b>Nothing found<?php echo ($pub !== '' || $tool !== '' || $status !== '' || $user !== '' || $from_dt !== '') ? ' for these filters' : ' yet'; ?>.</b><br>
        <?php echo $tab === 'bypass' ? 'A row is written every time someone bypasses the Style Validator.' : 'A row is written the first time the Style Validator is run for an article.'; ?></div>
<?php } else if ($tab === 'first') { ?>
      <table class="qc-tbl">
        <thead><tr><th><input type="checkbox" class="qc-all" title="Select all on this page"></th><th>Article</th><th>Customer</th><th>Tool</th><th>Stage / Dept</th><th>XML file</th><th>Result</th><th>Errors</th><th>Warnings</th><th>Run by</th><th>Run at</th><th>Report</th></tr></thead>
        <tbody>
<?php foreach ($rows as $r) { ?>
          <tr>
            <td><input type="checkbox" class="qc-row" name="ids[]" value="<?php echo (int)$r['id']; ?>"></td>
            <td><b><?php echo $e($r['pub_id']); ?></b></td>
            <td><?php echo $e($r['cust_name']); ?></td>
            <td><?php echo $e(isset($tool_names[$r['tool']]) ? $tool_names[$r['tool']] : $r['tool']); ?></td>
            <td><?php echo $e($r['stage']); ?> <span class="qc-sub">/ <?php echo $e($r['dept']); ?></span></td>
            <td><?php echo $e($r['xml_file']); ?></td>
            <td><span class="qc-pill <?php echo $r['status'] === 'PASS' ? 'ok' : 'bad'; ?>"><?php echo $r['status'] === 'PASS' ? 'Passed' : 'Failed'; ?></span></td>
            <td><?php echo $r['error_count'] === null ? '-' : (int)$r['error_count']; ?></td>
            <td><?php echo $r['warning_count'] === null ? '-' : (int)$r['warning_count']; ?></td>
            <td><?php echo $e($r['run_by'] ?: '-'); ?></td>
            <td><?php echo $e(date('d-M-Y H:i:s', strtotime($r['run_at']))); ?></td>
            <td><?php echo $r['report_file'] ? '<a class="qc-view" target="_blank" href="qc_tool_log.php?view=first&id=' . (int)$r['id'] . '">View</a> <span class="qc-sub">' . $e($size($r['report_size'])) . '</span>' : '<span class="qc-sub">none</span>'; ?></td>
          </tr>
<?php } ?>
        </tbody>
      </table>
<?php } else { ?>
      <table class="qc-tbl">
        <thead><tr><th><input type="checkbox" class="qc-all" title="Select all on this page"></th><th>Article</th><th>Customer</th><th>Tool</th><th>Stage / Dept</th><th>XML file</th><th>Reason</th><th>Bypassed by</th><th>IP</th><th>Bypassed at</th><th>Failing report</th></tr></thead>
        <tbody>
<?php foreach ($rows as $r) { ?>
          <tr>
            <td><input type="checkbox" class="qc-row" name="ids[]" value="<?php echo (int)$r['id']; ?>"></td>
            <td><b><?php echo $e($r['pub_id']); ?></b></td>
            <td><?php echo $e($r['cust_name']); ?></td>
            <td><?php echo $e(isset($tool_names[$r['tool']]) ? $tool_names[$r['tool']] : $r['tool']); ?></td>
            <td><?php echo $e($r['stage']); ?> <span class="qc-sub">/ <?php echo $e($r['dept']); ?></span></td>
            <td><?php echo $e($r['xml_file']); ?></td>
            <td class="qc-reason"><?php echo $e($r['reason']); ?></td>
            <td><?php echo $e($r['bypassed_by'] ?: '-'); ?></td>
            <td><?php echo $e($r['ip'] ?: '-'); ?></td>
            <td><?php echo $e(date('d-M-Y H:i:s', strtotime($r['bypassed_at']))); ?></td>
            <td><?php echo $r['report_file'] ? '<a class="qc-view" target="_blank" href="qc_tool_log.php?view=bypass&id=' . (int)$r['id'] . '">View</a>' : ((int)$r['had_failing_report'] ? '<span class="qc-pill skip">not saved</span>' : '<span class="qc-pill note">validator not run</span>'); ?></td>
          </tr>
<?php } ?>
        </tbody>
      </table>
<?php } ?>
    </div>
    </form>
<?php if ($total > 0) {
	$first = $offset + 1; $last = min($offset + $per, $total);
	$win = array(1, $pages); for ($i = $pg - 2; $i <= $pg + 2; $i++) { if ($i >= 1 && $i <= $pages) { $win[] = $i; } }
	$win = array_values(array_unique($win)); sort($win);
?>
    <div class="qc-pager">
      <div class="info">Showing <?php echo $first; ?>&ndash;<?php echo $last; ?> of <?php echo $total; ?></div>
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
  </div>
</div>
<?php echo scripts(); ?>
<script>
(function () {
  var f = document.getElementById('qcZipForm'); if (!f) { return; }
  var rows = f.querySelectorAll('.qc-row'), all = f.querySelector('.qc-all'), n = document.getElementById('qcSelN'), b = document.getElementById('qcZipSel'), sc = document.getElementById('qcScope');
  function upd() { var c = f.querySelectorAll('.qc-row:checked').length; if (n) { n.textContent = c; } if (b) { b.disabled = !c; } if (all) { all.checked = c && c === rows.length; } }
  if (all) { all.addEventListener('change', function () { rows.forEach(function (r) { r.checked = all.checked; }); upd(); }); }
  rows.forEach(function (r) { r.addEventListener('change', upd); });
  if (b) { b.addEventListener('click', function () { sc.value = 'sel'; }); }
  upd();
})();
</script>
<script src="<?php echo $e($base); ?>/dv-rangepicker.js?v=1"></script>
<?php echo footer(); ?>
</body>
</html>

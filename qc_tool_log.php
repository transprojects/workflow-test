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

/* Email-a-download-link flow (AJAX, step by step so the page can show progress): start -> step (x N) -> finish -> mail.
   The ZIP is built into E:/Tool_Reports/_zips/<token>.zip and served by qc_zip_download.php?t=<token> (token-protected, expires after 7 days). */
if (isset($_POST['qz']) && $tbl_ok) {
	@set_time_limit(0); @ini_set('memory_limit', '512M');
	$J = function ($a) { while (ob_get_level()) { ob_end_clean(); } header('Content-Type: application/json; charset=utf-8'); echo json_encode($a); exit; };
	if (empty($_COOKIE['user_id'])) { $J(array('ok' => false, 'error' => 'Please sign in again.')); }
	if (!class_exists('ZipArchive')) { $J(array('ok' => false, 'error' => 'PHP zip extension is not enabled on this server.')); }
	$zdir = $reports_dir . '_zips/';
	if (!is_dir($zdir)) { @mkdir($zdir, 0775, true); }
	$db->query("CREATE TABLE IF NOT EXISTS qc_zip_jobs (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT, token CHAR(40) NOT NULL, created_by VARCHAR(100) NOT NULL DEFAULT '', created_by_name VARCHAR(150) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, mode VARCHAR(10) NOT NULL DEFAULT 'full', recipients TEXT NULL, note TEXT NULL,
		items_json MEDIUMTEXT NULL, stats_json TEXT NULL, total INT NOT NULL DEFAULT 0, done INT NOT NULL DEFAULT 0, files INT NOT NULL DEFAULT 0, missing INT NOT NULL DEFAULT 0,
		status VARCHAR(12) NOT NULL DEFAULT 'building', zip_file VARCHAR(255) NULL, zip_size BIGINT NULL, mail_status VARCHAR(10) NULL, mail_error TEXT NULL,
		downloads INT NOT NULL DEFAULT 0, last_download DATETIME NULL, PRIMARY KEY (id), UNIQUE KEY uq_token (token)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$qz = $_POST['qz'];
	$uid = $db->real_escape_string((string)$_COOKIE['user_id']);
	$dfmt = function ($d) { return $d ? date('d-M-Y', strtotime($d)) : ''; };
	try {
		if ($qz === 'start') {
			/* drop expired links + their files */
			$r = $db->query("SELECT id, zip_file FROM qc_zip_jobs WHERE expires_at < NOW()");
			while ($r && ($x = $r->fetch_assoc())) { if ($x['zip_file'] && is_file($zdir . $x['zip_file'])) { @unlink($zdir . $x['zip_file']); } @unlink($zdir . $x['zip_file'] . '.csv'); $db->query("DELETE FROM qc_zip_jobs WHERE id = " . (int)$x['id']); }
			$to = array();
			foreach (preg_split('/[\s,;]+/', isset($_POST['to']) ? $_POST['to'] : '', -1, PREG_SPLIT_NO_EMPTY) as $m) {
				if (!filter_var($m, FILTER_VALIDATE_EMAIL)) { $J(array('ok' => false, 'error' => 'Not a valid email address: ' . $m)); }
				$to[strtolower($m)] = $m;
			}
			$mode = isset($_POST['mode']) && $_POST['mode'] === 'first' ? 'first' : 'full';
			$sel = array();
			if (isset($_POST['zip_scope']) && $_POST['zip_scope'] === 'all') {
				$r = $db->query("SELECT t.pub_id, t.tool FROM $tbl t WHERE $W");
				while ($r && ($x = $r->fetch_assoc())) { $sel[$x['pub_id'] . "\t" . $x['tool']] = $x; }
			} else {
				$ids = array_filter(array_map('intval', isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array()));
				if ($ids) { $r = $db->query("SELECT t.pub_id, t.tool FROM $tbl t WHERE t.id IN (" . implode(',', $ids) . ")"); while ($r && ($x = $r->fetch_assoc())) { $sel[$x['pub_id'] . "\t" . $x['tool']] = $x; } }
			}
			if (!$sel) { $J(array('ok' => false, 'error' => 'Nothing selected.')); }
			$items = array(); $arts = array(); $tools = array(); $nf = 0; $nb = 0; $dmin = ''; $dmax = ''; $nfail = 0; $npass = 0;
			foreach (array_chunk(array_values($sel), 200) as $chunk) {
				$or = array();
				foreach ($chunk as $x) { $or[] = "(pub_id = '" . $db->real_escape_string($x['pub_id']) . "' AND tool = '" . $db->real_escape_string($x['tool']) . "')"; }
				$OR = implode(' OR ', $or);
				if ($has_first) { $r = $db->query("SELECT id, pub_id, tool, status, run_at FROM qc_tool_first_report WHERE $OR ORDER BY run_at");
					while ($r && ($x = $r->fetch_assoc())) { $items[] = array('first', (int)$x['id']); $arts[$x['pub_id']] = 1; $tools[$x['tool']] = 1; $nf++; if ($x['status'] === 'PASS') { $npass++; } else { $nfail++; }
						$d = substr($x['run_at'], 0, 10); if ($tab === 'first') { if ($dmin === '' || $d < $dmin) { $dmin = $d; } if ($d > $dmax) { $dmax = $d; } } } }
				if ($has_bypass && $mode === 'full') { $r = $db->query("SELECT id, pub_id, tool, bypassed_at FROM qc_tool_bypass_log WHERE $OR ORDER BY bypassed_at");
					while ($r && ($x = $r->fetch_assoc())) { $items[] = array('bypass', (int)$x['id']); $arts[$x['pub_id']] = 1; $tools[$x['tool']] = 1; $nb++;
						$d = substr($x['bypassed_at'], 0, 10); if ($tab === 'bypass') { if ($dmin === '' || $d < $dmin) { $dmin = $d; } if ($d > $dmax) { $dmax = $d; } } } }
			}
			if (!$items) { $J(array('ok' => false, 'error' => 'No reports found for the selection.')); }
			$tl = array(); foreach (array_keys($tools) as $k) { $tl[] = isset($tool_names[$k]) ? $tool_names[$k] : $k; }
			$stats = array('tab' => $tab, 'tools' => $tl, 'articles' => count($arts), 'first_n' => $nf, 'bypass_n' => $nb, 'pass' => $npass, 'fail' => $nfail, 'date_min' => $dmin, 'date_max' => $dmax,
				'f_from' => $from_dt, 'f_to' => $to_dt, 'f_pub' => $pub, 'f_tool' => $tool, 'f_status' => $status, 'f_user' => $user, 'scope' => (isset($_POST['zip_scope']) && $_POST['zip_scope'] === 'all') ? 'all' : 'sel');
			$token = function_exists('random_bytes') ? bin2hex(random_bytes(20)) : bin2hex(openssl_random_pseudo_bytes(20));
			$zf = $token . '.zip';
			$db->query("INSERT INTO qc_zip_jobs (token, created_by, created_by_name, created_at, expires_at, mode, recipients, note, items_json, stats_json, total, status, zip_file) VALUES ('" . $token . "', '" . $uid . "', '" . $db->real_escape_string(isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : '') . "', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), '" . $mode . "', '"
				. $db->real_escape_string(implode(', ', $to)) . "', '" . $db->real_escape_string(isset($_POST['note']) ? trim($_POST['note']) : '') . "', '" . $db->real_escape_string(json_encode($items)) . "', '" . $db->real_escape_string(json_encode($stats)) . "', " . count($items) . ", 'building', '" . $zf . "')");
			$jid = (int)$db->insert_id;
			if (!$jid) { $J(array('ok' => false, 'error' => 'Could not create the job: ' . $db->error)); }
			$J(array('ok' => true, 'job' => $jid, 'total' => count($items), 'articles' => count($arts), 'first_n' => $nf, 'bypass_n' => $nb));
		}

		$jid = isset($_POST['job']) ? (int)$_POST['job'] : 0;
		$r = $db->query("SELECT * FROM qc_zip_jobs WHERE id = $jid AND created_by = '$uid'");
		$job = $r ? $r->fetch_assoc() : null;
		if (!$job) { $J(array('ok' => false, 'error' => 'Job not found.')); }
		$zpath = $zdir . $job['zip_file']; $cpath = $zpath . '.csv';
		$items = json_decode($job['items_json'], true); $stats = json_decode($job['stats_json'], true);
		$safe = function ($s) { $s = str_replace('..', '_', preg_replace('/[^A-Za-z0-9._-]+/', '_', trim((string)$s))); return trim($s, '._') === '' ? '_' : $s; };

		if ($qz === 'step') {
			if ($job['status'] !== 'building') { $J(array('ok' => true, 'done' => (int)$job['done'], 'total' => (int)$job['total'])); }
			$batch = array_slice($items, (int)$job['done'], 40);
			$byk = array('first' => array(), 'bypass' => array());
			foreach ($batch as $it) { $byk[$it[0]][] = (int)$it[1]; }
			$rows = array();
			foreach (array('first' => 'qc_tool_first_report', 'bypass' => 'qc_tool_bypass_log') as $k => $t) {
				if ($byk[$k]) { $r = $db->query("SELECT * FROM $t WHERE id IN (" . implode(',', $byk[$k]) . ")"); while ($r && ($x = $r->fetch_assoc())) { $rows[$k . $x['id']] = $x; } }
			}
			$custs = array(); $r = $db->query("SELECT id, cust_name FROM adm_customer_master"); while ($r && ($x = $r->fetch_assoc())) { $custs[$x['id']] = $x['cust_name']; }
			$zip = new ZipArchive();
			if ($zip->open($zpath, ZipArchive::CREATE) !== true) { $J(array('ok' => false, 'error' => 'Could not open the ZIP file for writing.')); }
			$csv = fopen($cpath, 'a'); $nf = 0; $nm = 0;
			foreach ($batch as $it) {
				$kind = $it[0]; $x = isset($rows[$kind . $it[1]]) ? $rows[$kind . $it[1]] : null; if (!$x) { continue; }
				$when = $kind === 'first' ? $x['run_at'] : $x['bypassed_at']; $who = $kind === 'first' ? $x['run_by'] : $x['bypassed_by'];
				$rf = qc_find_report($reports_dir, $x, $kind, $when); $in = '';
				if ($rf && strpos($rf, '..') === false && is_file($reports_dir . $rf)) {
					$in = $safe($x['pub_id']) . '/' . $kind . '/' . basename($rf);
					for ($n = 2; $zip->locateName($in) !== false; $n++) { $in = $safe($x['pub_id']) . '/' . $kind . '/' . $n . '_' . basename($rf); }
					$zip->addFile($reports_dir . $rf, $in); $nf++;
				} else { $nm++; }
				fputcsv($csv, array($kind === 'first' ? 'First report' : 'Bypass', $x['pub_id'], isset($custs[$x['cust_id']]) ? $custs[$x['cust_id']] : '', $x['tool'], $x['stage'], $x['dept'], $x['xml_file'],
					$kind === 'first' ? $x['status'] : '', $kind === 'first' ? $x['error_count'] : '', $kind === 'first' ? $x['warning_count'] : '', $who, $when,
					$kind === 'bypass' ? $x['reason'] : '', $kind === 'bypass' ? $x['ip'] : '', $in !== '' ? $in : 'no HTML stored'));
			}
			fclose($csv); $zip->close();
			$done = (int)$job['done'] + count($batch);
			$db->query("UPDATE qc_zip_jobs SET done = $done, files = files + $nf, missing = missing + $nm WHERE id = $jid");
			$J(array('ok' => true, 'done' => $done, 'total' => (int)$job['total'], 'files' => (int)$job['files'] + $nf, 'missing' => (int)$job['missing'] + $nm));
		}

		if ($qz === 'finish') {
			if ((int)$job['files'] < 1) { $db->query("UPDATE qc_zip_jobs SET status = 'failed' WHERE id = $jid"); $J(array('ok' => false, 'error' => 'No stored HTML reports were found for the selection.')); }
			$zip = new ZipArchive();
			if ($zip->open($zpath, ZipArchive::CREATE) !== true) { $J(array('ok' => false, 'error' => 'Could not open the ZIP file.')); }
			$hdr = fopen('php://temp', 'w+');
			fputcsv($hdr, array('Type', 'Article', 'Customer', 'Tool', 'Stage', 'Dept', 'XML file', 'Result', 'Errors', 'Warnings', 'User', 'Date/time', 'Reason', 'IP', 'File in zip'));
			rewind($hdr);
			$zip->addFromString('summary.csv', "\xEF\xBB\xBF" . stream_get_contents($hdr) . (is_file($cpath) ? file_get_contents($cpath) : ''));
			$zip->close(); @unlink($cpath);
			$sz = is_file($zpath) ? filesize($zpath) : 0;
			$db->query("UPDATE qc_zip_jobs SET status = 'ready', zip_size = $sz WHERE id = $jid");
			$J(array('ok' => true, 'size' => $sz, 'files' => (int)$job['files'], 'missing' => (int)$job['missing']));
		}

		if ($qz === 'mail') {
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
			$link = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/qc_zip_download.php?t=' . $job['token'];
			$res_es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $res_es ? $res_es->fetch_assoc() : null;
			if (!$es || empty($es['from_password'])) {
				$db->query("UPDATE qc_zip_jobs SET mail_status = 'failed', mail_error = 'email_settings id=1 not found' WHERE id = $jid");
				$J(array('ok' => true, 'link' => $link, 'mailed' => false, 'error' => 'Email settings not found, so no email was sent. Copy the link below.'));
			}
			$istb = $stats['tab'] === 'bypass';
			if ($stats['f_from'] !== '' || $stats['f_to'] !== '') { $range = ($stats['f_from'] !== '' ? $dfmt($stats['f_from'] === '' ? '' : implode('-', array_reverse(explode('-', $stats['f_from'])))) : 'start') . ' and ' . ($stats['f_to'] !== '' ? $dfmt(implode('-', array_reverse(explode('-', $stats['f_to'])))) : 'today'); }
			else { $range = $dfmt($stats['date_min']) . ' and ' . $dfmt($stats['date_max']); }
			$toolTxt = implode(', ', $stats['tools']);
			$td = "style='border:1px solid #d5dde6;padding:6px 10px'"; $th = "style='border:1px solid #d5dde6;padding:6px 10px;background:#eef3f8;text-align:left;width:190px'";
			$row = function ($k, $v) use ($td, $th) { return "<tr><th $th>" . htmlspecialchars($k) . "</th><td $td>" . $v . "</td></tr>"; };
			$h  = $row('Tool', htmlspecialchars($toolTxt));
			$h .= $row($istb ? 'Bypassed between' : 'Run between', htmlspecialchars($range));
			if ($stats['f_pub'] !== '')    { $h .= $row('Article ID filter', htmlspecialchars($stats['f_pub'])); }
			if ($stats['f_status'] !== '') { $h .= $row('Result filter', $stats['f_status'] === 'PASS' ? 'Passed' : 'Failed'); }
			if ($stats['f_user'] !== '')   { $h .= $row($istb ? 'Bypassed by' : 'Run by', htmlspecialchars($stats['f_user'])); }
			$h .= $row('Contents', $job['mode'] === 'first' ? 'First reports only + summary.csv' : 'First reports + bypass reports + summary.csv');
			$h .= $row('Articles', (int)$stats['articles']);
			$h .= $row('First reports', (int)$stats['first_n'] . ($stats['first_n'] ? ' (' . (int)$stats['pass'] . ' passed, ' . (int)$stats['fail'] . ' failed)' : ''));
			if ($job['mode'] !== 'first') { $h .= $row('Bypass reports', (int)$stats['bypass_n']); }
			$h .= $row('HTML files in ZIP', (int)$job['files'] . ((int)$job['missing'] ? ' (' . (int)$job['missing'] . ' with no stored HTML, listed in summary.csv)' : ''));
			$h .= $row('ZIP size', htmlspecialchars(round($job['zip_size'] / 1048576, 1) . ' MB'));
			$h .= $row('Prepared by', htmlspecialchars($job['created_by_name'] !== '' ? $job['created_by_name'] : 'user ' . $job['created_by']) . ' on ' . date('d-M-Y H:i'));
			$h .= $row('Link valid until', htmlspecialchars(date('d-M-Y H:i', strtotime($job['expires_at']))));
			include_once("smtpmail/phpmailer/class.phpmailer.php");
			$mail = new PHPMailer();
			$mail->IsSMTP(); $mail->CharSet = 'UTF-8'; $mail->Host = $es['host']; $mail->SMTPAuth = true; $mail->Port = $es['port']; $mail->Timeout = 30;
			$mail->Username = $es['from_email']; $mail->Password = $es['from_password']; $mail->SMTPSecure = $es['secure'];
			$mail->From = $es['from_email']; $mail->FromName = $es['from_name'] ?: $es['from_email']; $mail->isHTML(true);
			$mail->Subject = 'TOOL REPORT ATTACHED - ' . $toolTxt . ' - ' . ($istb ? 'Bypassed' : 'Run') . ' between ' . $range;
			$mail->Body = "<div style='font-family:Arial,sans-serif;font-size:14px;color:#22303f'>Dear Team,<br/><br/>The QC tool reports are ready to download. Details:<br/><br/>"
				. "<table style='border-collapse:collapse;font-size:13px'>" . $h . "</table><br/>"
				. ($job['note'] !== '' ? "<b>Note:</b> " . nl2br(htmlspecialchars($job['note'])) . "<br/><br/>" : '')
				. "<a href='" . htmlspecialchars($link) . "' style='display:inline-block;background:#e8742a;color:#fff;font-weight:bold;text-decoration:none;padding:10px 22px;border-radius:8px'>Download ZIP</a><br/><br/>"
				. "If the button does not work, copy this link into your browser:<br/>" . htmlspecialchars($link) . "<br/><br/><i>For security the link only works for the addresses this email was sent to: open it, enter your email address and confirm with the one-time code that is mailed to you.</i><br/><br/>Thanks &amp; Regards,<br/>Workflow System<br/>Transforma Pvt. Ltd.</div>";
			$mail->AltBody = "QC tool reports ready. Tool: $toolTxt. " . ($istb ? 'Bypassed' : 'Run') . " between $range. Articles: " . (int)$stats['articles'] . ". Download: $link (valid until " . $job['expires_at'] . ")";
			foreach (explode(',', $job['recipients']) as $m) { $mail->addAddress(trim($m)); }
			$ok = $mail->Send();
			$db->query("UPDATE qc_zip_jobs SET status = '" . ($ok ? 'emailed' : 'ready') . "', mail_status = '" . ($ok ? 'sent' : 'failed') . "', mail_error = '" . $db->real_escape_string($ok ? '' : $mail->ErrorInfo) . "' WHERE id = $jid");
			$J(array('ok' => true, 'link' => $link, 'mailed' => (bool)$ok, 'to' => $job['recipients'], 'error' => $ok ? '' : 'The email could not be sent (' . $mail->ErrorInfo . '). Copy the link below and send it yourself.'));
		}
		$J(array('ok' => false, 'error' => 'Unknown step.'));
	} catch (Exception $ex) { $J(array('ok' => false, 'error' => $ex->getMessage())); } catch (Throwable $ex) { $J(array('ok' => false, 'error' => $ex->getMessage())); }
}

/* Sender's own download of a finished ZIP job (signed in as the person who prepared it). */
if (isset($_GET['qzdl'])) {
	$jid = (int)$_GET['qzdl'];
	$r = !empty($_COOKIE['user_id']) ? $db->query("SELECT zip_file FROM qc_zip_jobs WHERE id = $jid AND created_by = '" . $db->real_escape_string((string)$_COOKIE['user_id']) . "' AND status IN ('ready','emailed')") : false;
	$x = $r ? $r->fetch_assoc() : null; $zp = $x ? $reports_dir . '_zips/' . $x['zip_file'] : '';
	if (!$x || !is_file($zp)) { echo 'This ZIP is no longer available.'; exit; }
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="QC_tool_reports_' . date('Ymd_His') . '.zip"'); header('Content-Length: ' . filesize($zp));
	readfile($zp); exit;
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
    .qz-ov{position:fixed;inset:0;background:rgba(20,48,74,.45);display:none;align-items:center;justify-content:center;z-index:9999;} .qz-ov.on{display:flex;}
    .qz-box{background:#fff;border-radius:16px;width:min(560px,94vw);max-height:92vh;overflow:auto;padding:22px 26px;box-shadow:0 12px 40px rgba(20,48,74,.3);}
    .qz-box h3{margin:0 0 4px;color:#14304a;font-size:19px;} .qz-box p.sub{margin:0 0 14px;color:#6b7785;font-size:13px;}
    .qz-box label.row{display:block;margin:6px 0;font-size:14px;cursor:pointer;} .qz-box input[type=text],.qz-box textarea{width:100%;box-sizing:border-box;border:1px solid #d5dde6;border-radius:10px;padding:9px 12px;margin:4px 0 8px;font:inherit;}
    .qz-box .btns{display:flex;gap:10px;justify-content:flex-end;margin-top:14px;} .qz-box .btns button,.qz-box .btns a{height:40px;line-height:40px;padding:0 20px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);cursor:pointer;text-decoration:none;display:inline-block;}
    .qz-box .btns .alt{background:#fff;color:#14304a;border:1px solid #cfd8e3;line-height:38px;}
    .qz-steps{list-style:none;margin:6px 0 0;padding:0;font-size:14px;} .qz-steps li{padding:6px 0;color:#9aa6b2;display:flex;gap:8px;align-items:center;} .qz-steps li.on{color:#14304a;font-weight:700;} .qz-steps li.done{color:#1f9d6a;} .qz-steps li.bad{color:#d64545;font-weight:700;}
    .qz-steps li i{width:18px;text-align:center;font-style:normal;}
    .qz-bar{height:12px;background:#e9eef4;border-radius:999px;overflow:hidden;margin:10px 0 4px;} .qz-bar span{display:block;height:100%;width:0;background:linear-gradient(135deg,#e8742a,#f08a45);transition:width .25s;}
    .qz-link{display:flex;gap:6px;margin-top:8px;} .qz-link input{flex:1;margin:0 !important;font-size:12px;}
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
      <button type="button" class="qc-z" data-scope="sel" disabled>Download / email ZIP &ndash; selected (<span class="qcSelN">0</span>)</button>
      <button type="button" class="qc-z alt" data-scope="all" data-n="<?php echo (int)$total; ?>">Download / email ZIP &ndash; all <?php echo (int)$total; ?> matching</button>
      <span class="hint">Choose first reports only, or first + bypass reports. Every ZIP also has summary.csv.</span>
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
<div class="qz-ov" id="qzOv"><div class="qz-box">
  <h3>Download / email ZIP</h3><p class="sub" id="qzScopeTxt"></p>
  <div id="qzForm">
    <label class="row"><input type="radio" name="qzmode" value="first" checked> First reports only</label>
    <label class="row"><input type="radio" name="qzmode" value="full"> First reports + bypass reports</label>
    <label class="row" style="margin-top:12px"><input type="checkbox" id="qzMailOn"> Also email a secure download link</label>
    <div id="qzMailBox" style="display:none">
      <input type="text" id="qzTo" placeholder="Recipient email(s), separated by comma" autocomplete="off">
      <textarea id="qzNote" rows="2" placeholder="Optional note for the email"></textarea>
      <p class="sub" style="margin:0">The email carries the run details and a link. Recipients must confirm their address with a one-time code, and the link expires in 7 days.</p>
    </div>
  </div>
  <div id="qzProg" style="display:none">
    <div class="qz-bar"><span id="qzBar"></span></div><div class="qc-sub" id="qzPct"></div>
    <ul class="qz-steps" id="qzSteps"><li data-s="1"><i>&#9675;</i>Preparing selection</li><li data-s="2"><i>&#9675;</i><span>Adding reports to the ZIP</span></li><li data-s="3"><i>&#9675;</i>Building summary.csv and closing the ZIP</li><li data-s="4" id="qzMailStep"><i>&#9675;</i>Sending email</li><li data-s="5"><i>&#9675;</i>Downloading</li></ul>
    <div id="qzMsg" style="margin-top:8px;font-size:13px"></div>
    <div class="qz-link" id="qzLinkBox" style="display:none"><input type="text" id="qzLink" readonly><button type="button" class="alt" id="qzCopy" style="height:38px;border:1px solid #cfd8e3;background:#fff;color:#14304a;border-radius:10px;font-weight:700;cursor:pointer;padding:0 14px">Copy</button></div>
  </div>
  <div class="btns"><a class="alt" id="qzDl" style="display:none">Download again</a><button type="button" class="alt" id="qzClose">Close</button><button type="button" id="qzGo">Start</button></div>
</div></div>
<?php echo scripts(); ?>
<script>
(function () {
  var f = document.getElementById('qcZipForm'); if (!f) { return; }
  var $ = function (i) { return document.getElementById(i); };
  var rows = f.querySelectorAll('.qc-row'), all = f.querySelector('.qc-all'), bs = f.querySelectorAll('.qc-z'), url = f.getAttribute('action'), scope = 'sel', busy = false;
  function upd() {
    var c = f.querySelectorAll('.qc-row:checked').length;
    f.querySelectorAll('.qcSelN').forEach(function (x) { x.textContent = c; });
    bs.forEach(function (b) { if (b.getAttribute('data-scope') === 'sel') { b.disabled = !c; } });
    if (all) { all.checked = c && c === rows.length; }
  }
  if (all) { all.addEventListener('change', function () { rows.forEach(function (r) { r.checked = all.checked; }); upd(); }); }
  rows.forEach(function (r) { r.addEventListener('change', upd); });
  bs.forEach(function (b) { b.addEventListener('click', function () {
    scope = b.getAttribute('data-scope');
    $('qzScopeTxt').textContent = scope === 'all' ? 'All ' + b.getAttribute('data-n') + ' rows matching the current filters (every page).' : f.querySelectorAll('.qc-row:checked').length + ' selected row(s).';
    $('qzForm').style.display = ''; $('qzProg').style.display = 'none'; $('qzGo').style.display = ''; $('qzDl').style.display = 'none'; $('qzMsg').innerHTML = ''; $('qzLinkBox').style.display = 'none';
    try { $('qzTo').value = localStorage.getItem('qzTo') || ''; } catch (e) {}
    $('qzOv').classList.add('on');
  }); });
  $('qzMailOn').addEventListener('change', function () { $('qzMailBox').style.display = this.checked ? '' : 'none'; });
  $('qzClose').addEventListener('click', function () { if (!busy) { $('qzOv').classList.remove('on'); } });
  $('qzCopy').addEventListener('click', function () { $('qzLink').select(); try { document.execCommand('copy'); this.textContent = 'Copied'; } catch (e) {} });

  function post(d) {
    var fd = new FormData();
    Object.keys(d).forEach(function (k) { fd.append(k, d[k]); });
    if (d.qz === 'start' && scope === 'sel') { f.querySelectorAll('.qc-row:checked').forEach(function (r) { fd.append('ids[]', r.value); }); }
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
      try { return JSON.parse(t); } catch (e) { throw new Error('Unexpected reply from the server.'); }
    }).then(function (j) { if (!j.ok) { throw new Error(j.error || 'Failed.'); } return j; });
  }
  function st(n, state, txt) {
    var li = document.querySelector('#qzSteps li[data-s="' + n + '"]'); li.className = state;
    li.firstChild.innerHTML = state === 'done' ? '&#10003;' : state === 'bad' ? '&#10007;' : state === 'on' ? '&#9654;' : '&#9675;';
    if (txt) { (li.querySelector('span') || li.lastChild).textContent = txt; }
  }
  function bar(p, t) { $('qzBar').style.width = p + '%'; $('qzPct').textContent = t || ''; }

  $('qzGo').addEventListener('click', function () {
    var mail = $('qzMailOn').checked, to = $('qzTo').value.trim();
    if (mail && !to) { $('qzTo').focus(); return; }
    var mode = document.querySelector('input[name=qzmode]:checked').value, job = 0, total = 0, jobId;
    busy = true; $('qzForm').style.display = 'none'; $('qzProg').style.display = ''; $('qzGo').style.display = 'none'; $('qzClose').disabled = true;
    $('qzMailStep').style.display = mail ? '' : 'none'; $('qzMsg').innerHTML = ''; bar(0, '');
    ['1', '2', '3', '4', '5'].forEach(function (n) { st(n, ''); });
    if (mail) { try { localStorage.setItem('qzTo', to); } catch (e) {} }
    st(1, 'on');
    post({ qz: 'start', zip_scope: scope, mode: mode, to: mail ? to : '', note: mail ? $('qzNote').value : '' }).then(function (j) {
      jobId = j.job; total = j.total; st(1, 'done', 'Selection ready: ' + j.articles + ' article(s), ' + j.first_n + ' first report(s)' + (j.bypass_n ? ', ' + j.bypass_n + ' bypass report(s)' : ''));
      st(2, 'on'); bar(2, '0 / ' + total + ' files');
      return (function loop() {
        return post({ qz: 'step', job: jobId }).then(function (r) {
          var p = Math.round(r.done / total * 85); bar(p, r.done + ' / ' + total + ' files'); st(2, 'on', 'Adding reports to the ZIP (' + r.done + ' / ' + total + ')');
          return r.done < total ? loop() : r;
        });
      })();
    }).then(function (r) {
      st(2, 'done', 'Added reports to the ZIP (' + total + ' / ' + total + ')'); st(3, 'on'); bar(88, 'Building summary.csv');
      return post({ qz: 'finish', job: jobId });
    }).then(function (r) {
      st(3, 'done', 'ZIP ready: ' + r.files + ' HTML file(s) + summary.csv' + (r.missing ? ' (' + r.missing + ' row(s) had no stored HTML)' : '')); bar(mail ? 93 : 96, 'ZIP ready');
      if (!mail) { return null; }
      st(4, 'on'); return post({ qz: 'mail', job: jobId });
    }).then(function (m) {
      if (m) {
        if (m.mailed) { st(4, 'done', 'Email sent to ' + m.to); } else { st(4, 'bad', 'Email not sent'); $('qzMsg').innerHTML = '<span style="color:#d64545">' + m.error.replace(/</g, '&lt;') + '</span>'; }
        $('qzLink').value = m.link; $('qzLinkBox').style.display = '';
      }
      st(5, 'on'); bar(98, 'Starting download');
      var dl = url.split('?')[0] + '?qzdl=' + jobId; $('qzDl').href = dl; $('qzDl').style.display = '';
      var fr = document.createElement('iframe'); fr.style.display = 'none'; fr.src = dl; document.body.appendChild(fr);
      setTimeout(function () { st(5, 'done', 'Download started'); bar(100, 'Done'); busy = false; $('qzClose').disabled = false; }, 800);
    }).catch(function (err) {
      var cur = document.querySelector('#qzSteps li.on'); if (cur) { st(cur.getAttribute('data-s'), 'bad'); }
      $('qzMsg').innerHTML = '<span style="color:#d64545">' + String(err.message).replace(/</g, '&lt;') + '</span>'; busy = false; $('qzClose').disabled = false;
    });
  });
  upd();
})();
</script>
<script src="<?php echo $e($base); ?>/dv-rangepicker.js?v=1"></script>
<?php echo footer(); ?>
</body>
</html>

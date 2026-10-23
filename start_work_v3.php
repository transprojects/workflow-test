<?php
/* Emergency off switch: an empty file named start_work_v3.disabled next to this script sends everybody to start_work_v2.php (same query string). */
if (is_file(__DIR__ . '/start_work_v3.disabled')) { header('Location: start_work_v2.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')); exit; }
require_once __DIR__ . '/includes/path_map.php';   /* J:/ -> E:/New_Workflow_Files/ for server-side folder checks */
/* Login-token cache (this page only). includes/check.php validates the auth cookies on every request: one scan of
   wf_token_auth plus two bcrypt checks. Once that has passed, remember it in the session for 5 minutes and hide the
   two token cookies from that validation for the next requests. The shared includes are left exactly as they were. */
if (session_status() === PHP_SESSION_NONE) { error_reporting(E_ALL & ~E_NOTICE); session_start(); }
$__sw_hidden = array();
$__sw_sig = null;
if (!empty($_COOKIE['staff_login']) && !empty($_COOKIE['generate_password']) && !empty($_COOKIE['generate_selector'])) {
	$__sw_sig = md5($_COOKIE['staff_login'].'|'.$_COOKIE['generate_password'].'|'.$_COOKIE['generate_selector'].'|'.(isset($_COOKIE['generate_password_hash']) ? $_COOKIE['generate_password_hash'] : ''));
	if (!empty($_SESSION['sw_auth_sig']) && $_SESSION['sw_auth_sig'] === $__sw_sig && !empty($_SESSION['sw_auth_t']) && $_SESSION['sw_auth_t'] > time() - 300) {
		foreach (array('generate_password', 'generate_selector') as $__k) { $__sw_hidden[$__k] = $_COOKIE[$__k]; unset($_COOKIE[$__k]); }
	}
}
include "includes/includes-sw2.php";
foreach ($__sw_hidden as $__k => $__v) { $_COOKIE[$__k] = $__v; }
if ($__sw_hidden) { $isLoggedIn = true; }   /* includes/header.php (via top-header.php) redirects to the login page when this is false */
if ($__sw_sig !== null && !$__sw_hidden && !empty($isLoggedIn)) { $_SESSION['sw_auth_sig'] = $__sw_sig; $_SESSION['sw_auth_t'] = time(); }
?>
<?php
/* ============================================================================
 * start_work_v3.php = start_work_v2.php + the pre-delivery confirmation (see the sw3_ functions). Was: start_work_v2.php - test copy of start_work.php (served to user id 54 only,
 * see the router on line 1 of start_work.php).
 *
 * Changes against start_work.php:
 *  - The Submit button posts by AJAX (sw_ajax=1). The update step is unchanged;
 *    its output is captured and turned into JSON so every outcome - success,
 *    error, warning, PHP fatal, redirect - is shown in a SweetAlert instead of
 *    reloading the page.
 *  - Light UI polish, a loader while saving, and bootbox warnings shown as
 *    SweetAlerts.
 * ========================================================================== */
$SW_AJAX = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_ajax']));

function sw_ajax_unescape($s) { return stripslashes(str_replace('\n', "
", $s)); }

/* Turn whatever the update step printed into one result object. */
function sw_ajax_parse($full)
{
	$mark = '<!--SW_AJAX_START-->';
	$pos  = strrpos($full, $mark);
	$tail = ($pos === false) ? '' : substr($full, $pos + strlen($mark));

	/* a redirect header carries the old-style "msg=" error text */
	$redirect_msg = '';
	foreach (headers_list() as $h) {
		if (stripos($h, 'Location:') === 0) {
			if (preg_match('/[?&]msg=([^&]*)/', $h, $m)) { $redirect_msg = trim(urldecode($m[1])); }
			elseif ($redirect_msg === '') { $redirect_msg = 'The server redirected the request.'; }
			header_remove('Location');
		}
	}

	$errors = array(); $successes = array();
	if (preg_match_all("/swal\\(\\{(.*?)\\}\\)/s", $tail, $blocks, PREG_SET_ORDER)) {
		foreach ($blocks as $b) {
			$title = $text = $type = '';
			if (preg_match("/title:\\s*'((?:\\\\.|[^'\\\\])*)'/s", $b[1], $m)) { $title = sw_ajax_unescape($m[1]); }
			if (preg_match("/text:\\s*'((?:\\\\.|[^'\\\\])*)'/s",  $b[1], $m)) { $text  = sw_ajax_unescape($m[1]); }
			if (preg_match("/type:\\s*'(\\w+)'/", $b[1], $m))                     { $type  = $m[1]; }
			if ($type === 'success') { $successes[] = $title; }
			else                     { $errors[] = trim($title.($text !== '' ? ': '.$text : '')); }
		}
	}

	if ($errors && $successes) {
		return array('ok' => false, 'type' => 'warning', 'title' => 'Saved, but something needs attention',
		             'message' => implode("\n", $errors));
	}
	if ($errors)    { return array('ok' => false, 'type' => 'error', 'title' => 'Could not complete', 'message' => implode("\n", $errors)); }
	if ($successes) { return array('ok' => true, 'type' => 'success', 'title' => end($successes), 'message' => ''); }

	if ($redirect_msg !== '') { return array('ok' => false, 'type' => 'error', 'title' => 'Could not complete', 'message' => $redirect_msg); }

	$e = error_get_last();
	if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
		return array('ok' => false, 'type' => 'error', 'title' => 'Server error',
		             'message' => $e['message'].' ('.basename($e['file']).':'.$e['line'].')');
	}

	/* plain text printed by die()/echo */
	$txt = preg_replace('#<(script|style)\b.*?</\1>#is', ' ', $tail);
	$txt = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($txt))));
	if ($txt !== '') { return array('ok' => false, 'type' => 'error', 'title' => 'Could not complete', 'message' => (function_exists('mb_substr') ? mb_substr($txt, 0, 800) : substr($txt, 0, 800))); }

	return array('ok' => false, 'type' => 'error', 'title' => 'Could not complete',
	             'message' => ($pos === false) ? 'The update did not start. Please reload the page and try again.'
	                                           : 'The update did not complete. Please try again.');
}

function sw_ajax_finish()
{
	$parts = array();
	while (ob_get_level() > 0) { $parts[] = ob_get_clean(); }
	$res = sw_ajax_parse(implode('', array_reverse($parts)));
	if (function_exists('sw3_after_submit')) { sw3_after_submit($res); }   /* v3: outcome of the pre-delivery confirmation */
	if (!headers_sent()) {
		http_response_code(200);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
	}
	echo json_encode($res);
}

/* ---------------------------------------------------------------------------
 * Required files for the file movement (inw_move_files / inw_move_files_dept_wise)
 *
 * When a department completes its work, the move step copies the files whose
 * extension is on that department's accept list ("pdf|xml", "doc|docx|html" ...)
 * from its folder to the next department(s). If the folder holds no file with an
 * accepted extension, nothing is moved and the next department starts empty-handed.
 * This finds that out BEFORE anything is saved.
 * ------------------------------------------------------------------------- */
/* Looks for a file with one of $exts under $dir (sub-folders included).  Returns
 *   exists  - the server sees the folder          found - an accepted file is there
 *   seen    - up to 8 file names it did see        err   - why it could not look (if it could not) */
function sw_scan($dir, $exts, $exact_base = null)
{
	$out = array('exists' => false, 'found' => false, 'seen' => array(), 'err' => '', 'dir' => $dir, 'mapped' => false);
	foreach (sw_path_candidates($dir) as $i => $cand) {
		if (@is_dir($cand)) { if ($i > 0) { $out['dir'] = $cand; $out['mapped'] = true; } $dir = $cand; break; }
	}
	if (!@is_dir($dir)) { return $out; }
	$out['exists'] = true;
	$stack = array(rtrim($dir, "/\\"));
	$guard = 0;
	while ($stack && $guard++ < 2000) {
		$d = array_pop($stack);
		$items = @scandir($d);
		if ($items === false) { if ($out['err'] === '') { $out['err'] = 'cannot read '.$d; } continue; }   /* unreadable sub-folder: skip it, keep looking */
		foreach ($items as $n) {
			if ($n === '.' || $n === '..') { continue; }
			$path = $d.'/'.$n;
			if (@is_dir($path)) { $stack[] = $path; continue; }
			if (count($out['seen']) < 8) { $out['seen'][] = $n; }
			$ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
			if (!in_array($ext, $exts, true)) { continue; }
			if ($exact_base !== null && strtolower(pathinfo($n, PATHINFO_FILENAME)) !== $exact_base) { continue; }
			$out['found'] = true;
			return $out;
		}
	}
	return $out;
}
function sw_has_file($dir, $exts, $exact_base = null) { $r = sw_scan($dir, $exts, $exact_base); return $r['found']; }

/* ---------------------------------------------------------------------------
 * Package creation (PeerJ / EPL / NCC / EOS / TSP zip validator, and the NOW finals zip).
 * These two used to run INSIDE the submit (EPLZIPVALIDATOR.pl, NOW_Finals_ZIP.py).  They now have their own "Package creation" buttons, which
 * call the tool server (10.10.10.94/workflow/eos-zip-validation.php and now-zip-validation.php) BEFORE the submit.  The submit only checks that
 * the package was built:  EPL -> <article>_report.html says SUCCESS (written by the validator);  NOW -> the last button run was "completed".
 * Every button run is kept in inw_tool_run (tool = package_EPL / package_NOW), together with every other tool button.
 * ------------------------------------------------------------------------- */
function sw_pkg_kind($cust_id, $j_code, $stage, $dept_name, $dept_id)
{
	$cust_id = (int)$cust_id; $dept_id = (int)$dept_id;
	$sn = preg_replace('/REV[0-9]+/', 'REV', $stage); $sf = preg_replace('/FIN[0-9]+/', 'FIN', $stage);
	/* TSP finals (XML) and EOS finals (ePub) have their own zip scripts (tsp-final-zip.php / eos-final-zip.php); they answer "completed" and write no
	   validator report, so the result of the button run itself is the proof (like the NOW finals) */
	if ($cust_id == 42 && $sf == 'FIN' && $dept_name == 'XML') { return 'TSPFIN'; }
	if ($cust_id == 66 && $sf == 'FIN' && $dept_name == 'ePub') { return 'EOSFIN'; }
	/* SIF: FP in PRQC (14) and REV in XML (5) - Completed needs the SIF package created with the Package creation button first */
	/* PeerJ: every REV round in XML (dept 5) has its own zip script (pj-rev-zip.php), run by the Package creation button */
	if ($cust_id == 33 && preg_match('/^REV[0-9]*$/', $stage) && $dept_id == 5) { return 'PJREV'; }
	/* PeerJ: FP in PRQC (dept 14) uses pj-fp-zip.php */
	if ($cust_id == 33 && preg_match('/^FP[0-9]*$/', $stage) && $dept_id == 14) { return 'PJFP'; }
	if ($cust_id == 7 && (($stage == 'FP' && $dept_id == 14) || (preg_match('/^REV[0-9]*$/', $stage) && $dept_id == 5))) { return 'SIF'; }
	if (($j_code == 'EPL' || $j_code == 'NCC' || $cust_id == 33 || $cust_id == 66 || $cust_id == 42)
		&& (($stage == 'FP' && $dept_name == 'PRQC' && $cust_id != 66 && $cust_id != 42)
			|| ($cust_id != 66 && $sn == 'REV' && $dept_name == 'XML')
			|| ($cust_id == 66 && $sf == 'FIN' && $dept_name == 'ePub')
			|| ($cust_id == 42 && $sf == 'FIN' && $dept_name == 'XML'))) { return 'EPL'; }
	if ($cust_id == 53 && $dept_id == 5) { return 'NOW'; }
	return '';
}
/* the tool-server script behind the "Package creation" button: TSP finals (XML dept) and EOS finals (ePub dept) have their own scripts */
function sw_pkg_url($cust_id, $stage, $dept_name)
{
	$cust_id = (int)$cust_id; $sf = preg_replace('/FIN[0-9]+/', 'FIN', $stage);
	if ($cust_id == 42 && $sf == 'FIN' && $dept_name == 'XML') { return 'http://10.10.10.94/workflow/tsp-final-zip.php'; }
	if ($cust_id == 66 && $sf == 'FIN' && $dept_name == 'ePub') { return 'http://10.10.10.94/workflow/eos-final-zip.php'; }
	if ($cust_id == 33 && preg_match('/^REV[0-9]*$/', $stage)) { return 'http://10.10.10.94/workflow/pj-rev-zip.php'; }
	if ($cust_id == 33 && preg_match('/^FP[0-9]*$/', $stage)) { return 'http://10.10.10.94/workflow/pj-fp-zip.php'; }
	if ($cust_id == 7 && $stage == 'FP') { return 'http://10.10.10.94/workflow/sif-fp-zip.php'; }
	if ($cust_id == 7 && preg_match('/^REV[0-9]*$/', $stage)) { return 'http://10.10.10.94/workflow/sif-rev-zip.php'; }
	return 'http://10.10.10.94/workflow/eos-zip-validation.php';
}
function sw_tool_ensure($db)
{
	static $done = false;
	if ($done) { return; }
	$done = true;
	$db->query("CREATE TABLE IF NOT EXISTS `inw_tool_run` (
		`id` BIGINT(20) NOT NULL AUTO_INCREMENT, `inw_id` INT(11) NOT NULL DEFAULT 0, `pub_id` VARCHAR(150) NULL, `stage` VARCHAR(30) NULL, `dept` VARCHAR(100) NULL,
		`tool` VARCHAR(60) NOT NULL DEFAULT '', `tool_label` VARCHAR(150) NULL, `server_url` VARCHAR(255) NULL,
		`result` VARCHAR(16) NOT NULL DEFAULT '', `http_status` INT(11) NULL, `response_received` TINYINT(1) NOT NULL DEFAULT 0, `duration_ms` INT(11) NULL,
		`message` TEXT NULL, `user_id` INT(11) NULL, `user_name` VARCHAR(120) NULL, `ip_address` VARCHAR(45) NULL, `created_on` DATETIME NOT NULL,
		PRIMARY KEY (`id`), KEY `idx_inw` (`inw_id`, `stage`), KEY `idx_tool` (`tool`, `result`), KEY `idx_created` (`created_on`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$c = $db->query("SHOW COLUMNS FROM inw_tool_run LIKE 'request_args'");
	if ($c && $c->num_rows == 0) { $db->query("ALTER TABLE inw_tool_run ADD `request_args` TEXT NULL AFTER `server_url`"); }
}
/* one row per tool-button click on the Start Work page: who, which tool, which server, what came back (result: completed / failed / no_connection / no_response) */
function sw_tool_add($db, $inw_id, $f)
{
	sw_tool_ensure($db);
	$r = $db->query("SELECT id, pub_id, stage, department_id FROM inw_inward_dtl WHERE id = " . (int)$inw_id);
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a) { return false; }
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']); $dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	$res = (isset($f['result']) && in_array($f['result'], array('completed', 'failed', 'no_connection', 'no_response'), true)) ? $f['result'] : 'failed';
	$e = function ($v, $n) use ($db) { return $db->real_escape_string(substr(trim(strip_tags((string)$v)), 0, $n)); };
	return $db->query("INSERT INTO inw_tool_run (inw_id, pub_id, stage, dept, tool, tool_label, server_url, request_args, result, http_status, response_received, duration_ms, message, user_id, user_name, ip_address, created_on) VALUES ("
		. (int)$a['id'] . ", '" . $e($a['pub_id'], 150) . "', '" . $e($a['stage'], 30) . "', '" . $e($dd['dept_name'], 100) . "', '" . $e(isset($f['tool']) ? $f['tool'] : '', 60) . "', '" . $e(isset($f['label']) ? $f['label'] : '', 150) . "', '"
		. $e(isset($f['server']) ? $f['server'] : '', 255) . "', '" . $e(isset($f['args']) ? $f['args'] : '', 2000) . "', '" . $res . "', " . (int)(isset($f['http_status']) ? $f['http_status'] : 0) . ", " . (empty($f['responded']) ? 0 : 1) . ", " . (int)(isset($f['ms']) ? $f['ms'] : 0) . ", '"
		. $e(isset($f['message']) ? $f['message'] : '', 2000) . "', " . (int)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 0) . ", '" . $e(isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : '', 120) . "', '"
		. $e(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '', 45) . "', NOW())");
}
function sw_pkg_status($db, $kind, $inw_id, $stage, $work_dir, $volume_folder, $pub_id)
{
	sw_tool_ensure($db);
	$last = null;
	$r = $db->query("SELECT result, message, user_name, created_on FROM inw_tool_run WHERE inw_id = " . (int)$inw_id . " AND stage = '" . $db->real_escape_string($stage) . "' AND tool = 'package_" . $db->real_escape_string($kind) . "' ORDER BY id DESC LIMIT 1");
	if ($r && ($x = $r->fetch_assoc())) { $last = $x; }
	$done = false; $rtime = 0;
	if ($kind === 'EPL') {
		foreach (sw_path_candidates($work_dir . $volume_folder . "/" . $pub_id . "/" . $stage . "/" . $pub_id . "_report.html") as $c) {
			if (@is_file($c)) { $done = (bool)preg_match('/<body class="SUCCESS">/i', (string)@file_get_contents($c)); $rtime = (int)@filemtime($c); break; }
		}
	} else {
		$done = ($last && $last['result'] === 'completed');
	}
	return array('kind' => $kind, 'label' => $kind === 'NOW' ? 'NOW finals ZIP' : 'ZIP package', 'done' => $done, 'report_time' => $rtime, 'last' => $last);
}

/* returns array('problems' => [text...], 'checked' => [[dest, exts, found]...]) */
function sw_required_files_core($db, $cust_id, $j_id, $dept_id, $stage, $pub_id, $work_dir, $volume_folder, $dept_name, $J_name, $wf_id = 0)
{
	$none = array('problems' => array(), 'checked' => array());
	$cust_id = (int)$cust_id; $j_id = (int)$j_id; $dept_id = (int)$dept_id;

	/* same rules as the move step for whether files are copied at all */
	$stage_norm = preg_replace("/REV([0-9]+)/", "REV", $stage);
	if ($J_name != 'EPL' && $J_name != 'NCC' && $cust_id != 33) { $will_move = true; }
	else { $will_move = ($stage != 'FP' && $dept_name != 'PRQC') || ($stage_norm != 'REV' && $dept_name != 'XML') || ($stage != 'REV2' && $dept_name != 'ePub'); }
	if (!$will_move) { return $none; }

	$r  = $db->query("SELECT mf_id FROM `inw_move_files` WHERE cust_id = ".$cust_id." AND j_id = ".$j_id." AND dept_id = ".$dept_id);
	$fe = $r ? $r->fetch_assoc() : null;
	$stage_key = preg_replace('/[0-9]+/', '', $stage);                /* the stage as Move Files keys it (JSTAGE1 -> JSTAGE) */
	/* mf_id 0 = nothing set up for this journal + department (the banner offers "Add"); > 0 = set up, but not for this stage ("Edit") */
	$ids = array('cust_id' => $cust_id, 'j_id' => $j_id, 'dept_id' => $dept_id, 'stage_key' => $stage_key);
	if (!$fe) { return array('problems' => array(), 'checked' => array(), 'configured' => false, 'mf_id' => 0) + $ids; }   /* nothing is moved */

	$wf_depts = null;
	if ((int)$wf_id > 0) {
		$r = $db->query("SELECT wf_code FROM `adm_workflow_master` WHERE wf_id = ".(int)$wf_id);
		if ($r && ($w = $r->fetch_assoc())) { $wf_depts = array_map('intval', explode(',', $w['wf_code'])); }
	}

	/* Stages where the submit itself BUILDS the package: the ZIP validator runs during the submit and creates the .zip, so that file
	   cannot exist beforehand. It is not demanded up front; if the validator fails to build it, the package check reports that. */
	$sf = preg_replace('/FIN[0-9]+/', 'FIN', $stage);
	$builds_pkg = ($J_name=='EPL' || $J_name=='NCC' || $cust_id==33 || $cust_id==66 || $cust_id==42)
		&& (($stage=='FP' && $dept_name=='PRQC' && $cust_id!=66 && $cust_id!=42)
			|| ($cust_id!=66 && $stage_norm=='REV' && $dept_name=='XML')
			|| ($cust_id==66 && $sf=='FIN' && $dept_name=='ePub')
			|| ($cust_id==42 && $sf=='FIN' && $dept_name=='XML'))
		|| ($cust_id==53 && $dept_id==5);   /* NOW_Finals_ZIP.py builds the zip during the submit */

	$cur  = preg_replace('/[0-9]+/', '', $stage);
	$r    = $db->query("SELECT d.move_files, d.dept_id, m.dept_name FROM `inw_move_files_dept_wise` d
	                    LEFT JOIN adm_dept_master m ON m.dept_code = d.dept_id
	                    WHERE d.mf_id = ".(int)$fe['mf_id']." AND d.stage = '".$db->real_escape_string($cur)."' AND d.move_files <> ''");
	$src  = $work_dir.$volume_folder."/".$pub_id."/".$stage."/".$dept_name."/";
	$out  = $none;
	$seen = array();
	$configured = false;                                   /* at least one real destination for this stage in this article's workflow */
	while ($r && ($row = $r->fetch_assoc())) {
		if ((int)$row['dept_id'] === $dept_id || $row['dept_name'] === null) { continue; }   /* a destination that is this same department (nothing moves) or not a department at all */
		if ($wf_depts !== null && !in_array((int)$row['dept_id'], $wf_depts, true)) { continue; }
		$configured = true;
		$exts = array();
		foreach (explode('|', $row['move_files']) as $e) { $e = strtolower(trim($e, " .\t")); if ($e !== '') { $exts[] = $e; } }
		if ($builds_pkg) { $exts = array_values(array_diff($exts, array('zip'))); }
		if (!$exts) { continue; }
		$key = implode('|', $exts);
		$scan  = sw_scan($src, $exts, $cust_id == 82 ? strtolower($pub_id) : null);
		$found = $scan['found'];
		$out['checked'][] = array('dest' => $row['dept_name'], 'exts' => $exts, 'found' => $found,
		                          'dir' => $scan['dir'], 'asked' => $src, 'mapped' => $scan['mapped'], 'exists' => $scan['exists'], 'seen' => $scan['seen'], 'err' => $scan['err']);
		if (!$found && !isset($seen[$key.'|'.$row['dept_name']])) {
			$seen[$key.'|'.$row['dept_name']] = 1;
			$out['problems'][] = "Moving to ".$row['dept_name']." needs a .".implode(' / .', $exts)." file in the ".$stage."/".$dept_name." folder"
			                   . (is_dir($src) ? " (none found)" : " (the folder does not exist)");
		}
	}
	$out['configured'] = $configured;
	if (!$configured) { $out['mf_id'] = (int)$fe['mf_id']; $out += $ids; }
	return $out;
}

/* the file check plus the package check (when this stage has a "Package creation" step) */
function sw_required_files($db, $cust_id, $j_id, $dept_id, $stage, $pub_id, $work_dir, $volume_folder, $dept_name, $J_name, $wf_id = 0, $inw_id = 0)
{
	$r = sw_required_files_core($db, $cust_id, $j_id, $dept_id, $stage, $pub_id, $work_dir, $volume_folder, $dept_name, $J_name, $wf_id);
	$kind = sw_pkg_kind($cust_id, $J_name, $stage, $dept_name, $dept_id);
	if ($kind !== '' && (int)$inw_id > 0) {
		$pk = sw_pkg_status($db, $kind, $inw_id, $stage, $work_dir, $volume_folder, $pub_id);
		$r['package'] = $pk;
		if (!$pk['done']) { $r['problems'][] = 'Package creation is not done - click the "Package creation" button (' . $pk['label'] . ') first, then submit again.'; }
	}
	return $r;
}

/* live check used by the page: POST sw_check_files=1 (aid in the URL) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_check_files'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$a = null;
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".(int)$_REQUEST['aid']);
	if ($r) { $a = $r->fetch_assoc(); }
	if (!$a) { echo json_encode(array('problems' => array('Article not found.'), 'checked' => array())); exit; }
	if ($a['volume_folder'] == "Vol00000") { $a['volume_folder'] .= $a['issueorgroup_folder']; }
	$jr = $db->query("SELECT j_code FROM adm_journals WHERE j_id = ".(int)$a['j_id']); $jj = $jr ? $jr->fetch_assoc() : array('j_code' => '');
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".(int)$a['department_id']); $dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	echo json_encode(sw_required_files($db, $a['cust_id'], $a['j_id'], $a['department_id'], $a['stage'], $a['pub_id'],
	                                   $a['work_dir'], $a['volume_folder'], $dd['dept_name'], $jj['j_code'], $a['wf_id'], $a['id']));
	exit;
}

/* "Report to admin" on the "No file movement configured" banner. It replaces the old Add / Edit file movement link: the user is no longer sent to the
   Move Files pages. The problem (article, stage, department-wise what is and is not set up) is e-mailed to the Start Work To / CC / BCC
   (wf_settings, screen: sw_switch_settings.php) and logged in inw_file_move_log (kind CONFIG, result ALERT / ALERTFAIL).
   POST sw_mf_alert=1, aid in the URL.  The same article / stage / department is reported once an hour. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_mf_alert'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	try {
		require_once __DIR__ . '/sw_switch_lib.php';
		require_once __DIR__ . '/includes/file_move_log.php';
		$a = null;
		$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . (int)$_REQUEST['aid']);
		if ($r) { $a = $r->fetch_assoc(); }
		if (!$a) { echo json_encode(array('ok' => false, 'message' => 'Article not found.')); exit; }
		if ($a['volume_folder'] == "Vol00000") { $a['volume_folder'] .= $a['issueorgroup_folder']; }
		$h  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
		$q1 = function ($sql) use ($db) { $x = $db->query($sql); $y = $x ? $x->fetch_assoc() : null; return $y ? $y : array(); };
		$cn = $q1("SELECT cust_name FROM adm_customer_master WHERE id = " . (int)$a['cust_id']);
		$jn = $q1("SELECT j_code, j_name FROM adm_journals WHERE j_id = " . (int)$a['j_id']);
		$dn = $q1("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']);
		$cust = isset($cn['cust_name']) ? $cn['cust_name'] : ''; $jcode = isset($jn['j_code']) ? $jn['j_code'] : ''; $jname = isset($jn['j_name']) ? $jn['j_name'] : '';
		$dept = isset($dn['dept_name']) ? $dn['dept_name'] : (string)$a['status'];
		$chk = sw_required_files($db, $a['cust_id'], $a['j_id'], $a['department_id'], $a['stage'], $a['pub_id'], $a['work_dir'], $a['volume_folder'], $dept, $jcode, $a['wf_id'], $a['id']);
		if (!empty($chk['configured']) || !empty($chk['checked'])) { echo json_encode(array('ok' => false, 'message' => 'File movement is already set up for this stage, so there is nothing to report.')); exit; }

		fml_ensure($db);
		$stage_key = preg_replace('/[0-9]+/', '', $a['stage']);
		$dup = $db->query("SELECT created_on FROM inw_file_move_log WHERE inw_id = " . (int)$a['id'] . " AND kind = 'CONFIG' AND result = 'ALERT' AND stage = '" . $db->real_escape_string($a['stage']) . "'
			AND from_dept = '" . $db->real_escape_string($dept) . "' AND created_on >= (NOW() - INTERVAL 60 MINUTE) ORDER BY id DESC LIMIT 1");
		if ($dup && ($d = $dup->fetch_assoc())) { echo json_encode(array('ok' => true, 'already' => true, 'message' => 'This was already reported to the admin at ' . date('h:i A', strtotime($d['created_on'])) . '.')); exit; }

		/* what is set up, department by department */
		$mf_id = (int)(isset($chk['mf_id']) ? $chk['mf_id'] : 0);
		$wf = $q1("SELECT wf_name, wf_code FROM adm_workflow_master WHERE wf_id = " . (int)$a['wf_id']);
		$wf_codes = !empty($wf['wf_code']) ? array_map('intval', explode(',', $wf['wf_code'])) : array();
		$names = array();
		$nr = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
		while ($nr && ($x = $nr->fetch_assoc())) { $names[(int)$x['dept_code']] = $x['dept_name']; }
		$cfg = array();                                   /* dept_id => files / folder of THIS stage */
		$other = array();                                 /* the other stages that do have a set-up, for the mail */
		if ($mf_id > 0) {
			$cr = $db->query("SELECT stage, dept_id, move_files, move_folders FROM inw_move_files_dept_wise WHERE mf_id = " . $mf_id);
			while ($cr && ($x = $cr->fetch_assoc())) {
				if (trim($x['move_files']) === '' && trim($x['move_folders']) === '') { continue; }
				if ($x['stage'] === $stage_key) { $cfg[(int)$x['dept_id']] = $x; } else { $other[$x['stage']] = (isset($other[$x['stage']]) ? $other[$x['stage']] : 0) + 1; }
			}
		}
		$rows = '';
		$list = $wf_codes ? $wf_codes : array_keys($cfg);
		foreach ($list as $c) {
			if ($c === (int)$a['department_id']) { continue; }
			$has = isset($cfg[$c]);
			$rows .= '<tr><td style="padding:4px 12px 4px 0;">' . $h(isset($names[$c]) ? $names[$c] : 'Dept ' . $c) . '</td><td style="padding:4px 12px 4px 0;">' . ($has ? $h($cfg[$c]['move_files']) : '<span style="color:#c0392b;">not set</span>') . '</td><td style="padding:4px 0;">' . ($has ? $h($cfg[$c]['move_folders']) : '') . '</td></tr>';
		}
		$wf_order = implode(' &rarr; ', array_map(function ($c) use ($names) { return isset($names[$c]) ? $names[$c] : 'Dept ' . $c; }, $wf_codes));
		$wf_order = html_entity_decode($wf_order, ENT_QUOTES, 'UTF-8');
		$what = $mf_id > 0
			? 'A file movement record exists for this customer / journal / department (record ID ' . $mf_id . '), but it has no destination with file types for the stage <b>' . $h($stage_key) . '</b>' . ($wf_codes ? ' inside this article\'s workflow' : '') . '.' . ($other ? ' It is set up only for: ' . $h(implode(', ', array_keys($other))) . '.' : '')
			: 'No file movement record exists for this customer / journal / department.';
		$result = 'No file will be copied to the next department when the user submits this article as Completed; the next department would start without the files.';
		$uname = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ''; $uid = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
		$tr = function ($k, $v) { return '<tr><td style="padding:4px 14px 4px 0;color:#5b6b7b;vertical-align:top;white-space:nowrap;"><b>' . $k . '</b></td><td style="padding:4px 0;">' . $v . '</td></tr>'; };
		$body = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#22303f;"><p>Hi Team,</p>'
			. '<p><b>' . $h($uname) . '</b> opened <b>' . $h($a['pub_id']) . '</b> in Start Work and the page reports that <b>file movement is not configured</b>. Please set up the file movement for this customer / journal / department and stage.</p>'
			. '<table cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">'
			. $tr('Article', $h($a['pub_id']) . ' (ID ' . (int)$a['id'] . ')') . $tr('Customer', $h($cust)) . $tr('Journal', $h($jcode !== '' ? $jcode : $jname) . ($jcode !== '' && $jname !== '' ? ' - ' . $h($jname) : ''))
			. $tr('Stage', $h($a['stage'])) . $tr('Current department', $h($dept)) . $tr('Workflow', $h(isset($wf['wf_name']) ? $wf['wf_name'] : '-') . ($wf_order !== '' ? '<br><span style="color:#5b6b7b;">' . $h($wf_order) . '</span>' : ''))
			. $tr('What happened', $what) . $tr('Effect', $result) . $tr('Reported by', $h($uname) . ' (ID ' . $uid . ', ' . $h($_SERVER['REMOTE_ADDR']) . ')') . $tr('Reported on', date('d-M-Y h:i A'))
			. '</table>'
			. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Department-wise file movement for stage ' . $h($stage_key) . ' (from ' . $h($dept) . ')</h3>'
			. ($rows !== '' ? '<table cellpadding="0" cellspacing="0" border="0" style="font-size:13.5px;"><tr style="color:#5b6b7b;"><td style="padding:4px 12px 4px 0;"><b>Department</b></td><td style="padding:4px 12px 4px 0;"><b>Files to move</b></td><td style="padding:4px 0;"><b>Folder</b></td></tr>' . $rows . '</table>' : '<p style="color:#c0392b;">No workflow departments could be read for this article.</p>')
			. '<p style="color:#888;font-size:12px;margin-top:16px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs. The record is in the File Movement Log (kind CONFIG).</p></div>';
		$subject = 'FILE MOVEMENT NOT CONFIGURED: ' . $a['pub_id'] . ' (' . $a['stage'] . ', ' . $dept . ') - ' . ($jcode !== '' ? $jcode : $jname);

		$mail = '';
		sw_switch_ensure($db);
		$mc = sw_mail_get($db, 'filemove');
		if ($mc['to'] === '' && $mc['cc'] === '' && $mc['bcc'] === '') { $mc = sw_switch_get($db); }   /* nothing saved for this report yet: the Start Work switch addresses */
		$to = sw_switch_parse_list($mc['to']); $cc = sw_switch_parse_list($mc['cc']); $bcc = sw_switch_parse_list($mc['bcc']);
		if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) { $mail = 'Not sent - no recipients configured'; }
		else {
			require_once __DIR__ . '/email-functions.php';
			$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
			if (!$es) { $mail = 'Not sent - email_settings missing'; }
			else {
				$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
					implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']), $subject, $body);
				$mail = $ok ? 'Sent' : 'Failed - see smtp_mail.log';
			}
		}
		$e = function ($v) use ($db) { return $db->real_escape_string((string)$v); };
		$reason = trim(strip_tags(str_replace(array('<b>', '</b>'), '', $what))) . ' ' . $result . ' Reported by the user from Start Work; e-mail: ' . $mail . '.';
		$wf_names = array(); foreach ($wf_codes as $c) { $wf_names[] = isset($names[$c]) ? $names[$c] : $c; }
		$db->query("INSERT INTO `inw_file_move_log` (batch_id, inw_id, pub_id, cust_id, stage, from_dept, to_dept, kind, result, file_name, reason, user_id, user_name, created_on)
			VALUES ('" . $e('CFG-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6)) . "', " . (int)$a['id'] . ", '" . $e($a['pub_id']) . "', " . (int)$a['cust_id'] . ", '" . $e($a['stage']) . "', '" . $e($dept) . "', '" . $e(substr(implode(', ', $wf_names), 0, 120)) . "',
			'CONFIG', '" . ($mail === 'Sent' ? 'ALERT' : 'ALERTFAIL') . "', '', '" . $e($reason) . "', " . $uid . ", '" . $e($uname) . "', NOW())");
		echo json_encode(array('ok' => ($mail === 'Sent'), 'mail' => $mail, 'message' => $mail === 'Sent' ? 'The admin has been informed by e-mail.' : 'The report was logged, but the e-mail could not be sent (' . $mail . '). Please tell the admin directly.'));
	} catch (Throwable $ex) { echo json_encode(array('ok' => false, 'message' => 'Could not send the report: ' . substr($ex->getMessage(), 0, 150))); }
	exit;
}

/* a "Package creation" button finished: keep the result (POST sw_pkg_log=1, aid in the URL, kind, result, message) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_pkg_log'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$r = $db->query("SELECT id, pub_id, stage, department_id FROM inw_inward_dtl WHERE id = " . (int)$_REQUEST['aid']);
	$a = $r ? $r->fetch_assoc() : null;
	$kind = (isset($_POST['kind']) && in_array($_POST['kind'], array('EPL', 'NOW', 'TSPFIN', 'EOSFIN', 'SIF', 'PJREV', 'PJFP'), true)) ? $_POST['kind'] : '';
	if (!$a || $kind === '') { echo json_encode(array('ok' => false)); exit; }
	$res = (isset($_POST['result']) && $_POST['result'] === 'completed') ? 'completed' : 'failed';
	$msg = substr(trim(strip_tags((string)(isset($_POST['message']) ? $_POST['message'] : ''))), 0, 2000);
	sw_tool_add($db, (int)$a['id'], array('tool' => 'package_' . $kind, 'label' => 'Package creation (' . $kind . ')', 'server' => @$_POST['server'], 'args' => @$_POST['args'],
		'result' => (isset($_POST['outcome']) ? $_POST['outcome'] : $res), 'http_status' => @$_POST['http_status'], 'responded' => @$_POST['responded'], 'ms' => @$_POST['ms'], 'message' => $msg));
	echo json_encode(array('ok' => true)); exit;
}

/* any tool button on the page finished (POST sw_tool_log=1, aid in the URL, tool, label, server, result, http_status, responded, ms, message) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_tool_log'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$ok = sw_tool_add($db, (int)$_REQUEST['aid'], array('tool' => @$_POST['tool'], 'label' => @$_POST['label'], 'server' => @$_POST['server'], 'args' => @$_POST['args'], 'result' => @$_POST['result'],
		'http_status' => @$_POST['http_status'], 'responded' => @$_POST['responded'], 'ms' => @$_POST['ms'], 'message' => @$_POST['message']));
	echo json_encode(array('ok' => (bool)$ok)); exit;
}

/* TESTING package: the zip validator on 10.10.10.94 writes <pub_id>_error.html in the stage folder when it fails; hand it to the page
   POST sw_pkg_error=1, aid */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_pkg_error'])) {
	header('Content-Type: text/html; charset=utf-8');
	header('Cache-Control: no-store');
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".(int)$_POST['aid']);
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a) { exit; }
	if ($a['volume_folder'] == "Vol00000") { $a['volume_folder'] .= $a['issueorgroup_folder']; }
	$dir = $a['work_dir'].$a['volume_folder']."/".$a['pub_id']."/".$a['stage']."/";
	if (!empty($_POST['status'])) {
		/* did the tool just finish OK?  the newest <pub_id>_report.html / _error.html, its age (server clock vs server clock) and whether it says SUCCESS */
		header('Content-Type: application/json; charset=utf-8');
		$best = ''; $bt = 0;
		foreach (array($a['pub_id']."_report.html", $a['pub_id']."_error.html") as $n) {
			foreach (sw_path_candidates($dir.$n) as $c) { if (@is_file($c)) { $t = (int)@filemtime($c); if ($t >= $bt) { $bt = $t; $best = $c; } break; } }
		}
		$body = $best !== '' ? (string)@file_get_contents($best) : '';
		$ok = $best !== '' && stripos(basename($best), '_error.html') === false && preg_match('/SUCCESS/i', $body) && !preg_match('/ERROR|FAIL/i', $body);
		echo json_encode(array('found' => $best !== '', 'file' => $best !== '' ? basename($best) : '', 'mtime' => $bt, 'now' => time(), 'success' => (bool)$ok));
		exit;
	}
	if (!empty($_POST['latest'])) {
		/* the package result page: whichever of <pub_id>_report.html / <pub_id>_error.html the tool wrote last, with the file name and time on top */
		$best = ''; $bt = 0;
		foreach (array($a['pub_id']."_report.html", $a['pub_id']."_error.html") as $n) {
			foreach (sw_path_candidates($dir.$n) as $c) { if (@is_file($c)) { $t = (int)@filemtime($c); if ($t >= $bt) { $bt = $t; $best = $c; } break; } }
		}
		if ($best !== '') {
			echo '<div style="font:13px Segoe UI,Arial,sans-serif;color:#14304a;background:#eef3f9;border-radius:8px;padding:8px 12px;margin-bottom:10px;">Package result file: <b>' . htmlspecialchars(basename($best)) . '</b> &middot; written ' . date('d-m-Y H:i:s', $bt) . '</div>';
			echo file_get_contents($best);
		}
		exit;
	}
	$f = $dir.$a['pub_id']."_error.html";
	if (is_file($f)) { echo file_get_contents($f); }
	exit;
}

/* ============================================================================
 * start_work_v3.php - Start Work v2 + a "what will go to Delivery" confirmation.
 *
 * When a department completes work and the NEXT step of the article's workflow is Delivery (or the completion triggers an automatic upload to
 * Client Review), the user first sees what is in the department folder, what the Move Files configuration will send to Delivery, and what the
 * automatic upload will send - and must confirm. An empty / corrupt file, or no package for an automatic upload, blocks; everything else warns.
 * Every time the popup is shown, what was shown, the decision and the outcome of the submit are written to inw_delivery_confirm_log
 * (viewer: Reports > Dispatch Log > "Pre-delivery checks"). The completion report goes to the "Project Dispatch: dispatch report" recipients.
 * ========================================================================== */
require_once __DIR__ . '/sw_switch_lib.php';

function sw3_log_table($db) {
	$db->query("CREATE TABLE IF NOT EXISTS `inw_delivery_confirm_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT, `a_id` int(11) DEFAULT NULL, `pub_id` varchar(100) DEFAULT NULL, `cust_id` int(11) DEFAULT NULL, `j_id` int(11) DEFAULT NULL,
		`stage` varchar(25) DEFAULT NULL, `from_dept` varchar(60) DEFAULT NULL, `to_label` varchar(120) DEFAULT NULL, `kind` varchar(20) DEFAULT NULL,
		`user_id` varchar(20) DEFAULT NULL, `user_name` varchar(100) DEFAULT NULL, `ip` varchar(45) DEFAULT NULL,
		`src_dir` text, `dest_dir` text, `folder` mediumtext, `config` mediumtext, `auto_info` mediumtext, `warnings` mediumtext, `errors` mediumtext,
		`decision` varchar(15) DEFAULT NULL, `shown_at` datetime DEFAULT NULL, `decided_at` datetime DEFAULT NULL,
		`outcome` varchar(15) DEFAULT NULL, `outcome_message` text, `after_folder` mediumtext, `dest_before` mediumtext, `report_mail` varchar(255) DEFAULT NULL, `finished_at` datetime DEFAULT NULL,
		PRIMARY KEY (`id`), KEY `a_id` (`a_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$r = $db->query("SHOW COLUMNS FROM inw_delivery_confirm_log LIKE 'dest_before'");
	if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE inw_delivery_confirm_log ADD COLUMN dest_before MEDIUMTEXT NULL"); }
}
/* automatic upload to Client Review that the completion triggers (same conditions as the include chain in the update step) */
function sw3_auto_route($a) {
	$c = (string)$a['cust_id']; $st = (string)$a['stage']; $d = (string)$a['department_id']; $cur = preg_replace('/[0-9]+/', '', $st); $xs = (string)(isset($a['xperttrack_sync']) ? $a['xperttrack_sync'] : '');
	if ($c == '42' && $st == 'FP' && $d == '18') { return 'Automatic upload to the proofing server'; }
	if ($c == '57' && $st == 'FP' && $d == '14' && (string)$a['j_id'] == '264') { return 'Automatic upload to Xperttrack (IAP)'; }
	if ($c == '66' && $st == 'FP' && $d == '18') { return 'Automatic upload to the proofing server (EOS)'; }
	if ($c == '77' && $st == 'FP' && $d == '18' && $xs == '1') { return 'Automatic upload to Xperttrack (ACG)'; }
	if ($c == '82' && ($cur == 'REV' || $cur == 'FP') && $d == '34') { return 'Automatic FTP upload (EDP)'; }
	if ($c == '77' && $xs == '1' && (($cur == 'REV' && $d == '14') || ($cur == 'FIN' && $d == '5'))) { return 'Automatic upload to Xperttrack (ACG)'; }
	if ($c == '80' && $st == 'FP' && $d == '18') { return 'Automatic upload to the proofing server'; }
	return '';
}
/* does the confirmation apply to this article right now?  array('kind' => delivery|auto|both, 'to' => label) or null.  No file access - cheap enough for the page load. */
function sw3_context($db, $a) {
	$wf = array();
	if ((int)$a['wf_id'] > 0) {
		$r = $db->query("SELECT wf_code FROM adm_workflow_master WHERE wf_id = " . (int)$a['wf_id']);
		if ($r && ($w = $r->fetch_assoc())) { $wf = array_map('intval', explode(',', $w['wf_code'])); }
	}
	$cur = (int)$a['department_id'];
	$idx = array_search($cur, $wf, true);
	$to_delivery = ($idx !== false && isset($wf[$idx + 1]) && $wf[$idx + 1] === 15);
	$auto = sw3_auto_route($a);
	if (!$to_delivery && $auto === '') { return null; }
	$kind = $to_delivery && $auto !== '' ? 'both' : ($to_delivery ? 'delivery' : 'auto');
	$to = $to_delivery ? 'Delivery' : '';
	if ($auto !== '') { $to .= ($to !== '' ? ' + ' : '') . 'Client Review (automatic upload)'; }
	return array('kind' => $kind, 'to' => $to, 'auto' => $auto);
}
function sw3_size_text($b) { return $b >= 1048576 ? number_format($b / 1048576, 2) . ' MB' : ($b >= 1024 ? number_format($b / 1024, 2) . ' KB' : $b . ' bytes'); }
function sw3_file_info($path, $name) {
	$i = array('name' => $name, 'size' => @filesize($path), 'modified' => date('d-M-Y H:i', @filemtime($path)), 'age_hours' => round((time() - @filemtime($path)) / 3600, 1));
	$i['size_text'] = $i['size'] >= 1048576 ? number_format($i['size'] / 1048576, 2) . ' MB' : ($i['size'] >= 1024 ? number_format($i['size'] / 1024, 2) . ' KB' : $i['size'] . ' bytes');
	$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	if ($ext == 'zip') {
		$i['type'] = 'zip'; $z = new ZipArchive();
		if ($i['size'] > 0 && $z->open($path) === true) {
			$en = array();
			for ($k = 0; $k < $z->numFiles; $k++) { $s = $z->statIndex($k); if (substr($s['name'], -1) == '/') { continue; } $en[] = array('name' => $s['name'], 'size' => $s['size'] >= 1048576 ? number_format($s['size'] / 1048576, 2) . ' MB' : ($s['size'] >= 1024 ? number_format($s['size'] / 1024, 2) . ' KB' : $s['size'] . ' bytes')); }
			$z->close();
			$i['count'] = count($en);
			if (count($en) > 1000) { $i['entries_total'] = count($en); $en = array_slice($en, 0, 1000); }
			$i['entries'] = $en;
			if (!$i['count']) { $i['error'] = 'The zip contains no files.'; }
		} else { $i['error'] = 'The zip cannot be opened (empty or corrupt).'; }
	} elseif ($ext == 'pdf') {
		$i['type'] = 'pdf';
		if ($i['size'] == 0) { $i['error'] = 'The PDF is empty (0 KB).'; }
		else {
			$c = file_get_contents($path, false, null, 0, 60 * 1048576);
			$p = preg_match_all('/\/Type\s*\/Page(?![s\w])/', $c);
			if (preg_match_all('/\/Count\s+(\d+)/', $c, $m) && max($m[1]) > $p) { $p = (int)max($m[1]); }
			$i['pages'] = $p > 0 ? $p : null;
			if (substr(ltrim(substr($c, 0, 1024)), 0, 4) !== '%PDF') { $i['error'] = 'This file does not look like a valid PDF.'; }
		}
	} else { $i['type'] = 'other'; if ($i['size'] === 0) { $i['error'] = 'The file is empty (0 KB).'; } }
	return $i;
}
/* every file under a folder (sub-folders included, like the move step), capped */
function sw3_scan($dir, $cap = 300) {
	$out = array(); $stack = array(rtrim($dir, '/\\')); $g = 0;
	while ($stack && $g++ < 500 && count($out) < $cap) {
		$d = array_pop($stack); $items = @scandir($d); if ($items === false) { continue; }
		foreach ($items as $n) {
			if ($n === '.' || $n === '..') { continue; }
			$p = $d . '/' . $n;
			if (@is_dir($p)) { $stack[] = $p; continue; }
			$out[] = array('path' => $p, 'name' => $n, 'rel' => ltrim(substr($p, strlen(rtrim($dir, '/\\'))), '/\\'));
			if (count($out) >= $cap) { break; }
		}
	}
	return $out;
}
/* everything the popup shows (and the log keeps) for one article */
function sw3_pre_delivery_data($db, $a) {
	$ctx = sw3_context($db, $a);
	if (!$ctx) { return null; }
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']); $dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	$vf = $a['volume_folder'] . (isset($a['issueorgroup_folder']) && $a['volume_folder'] == 'Vol00000' ? $a['issueorgroup_folder'] : '');
	$base = $a['work_dir'] . $vf . '/' . $a['pub_id'] . '/' . $a['stage'] . '/';
	$src = wf_existing_dir($base . $dd['dept_name'] . '/');
	$dest = wf_existing_dir($base . 'Delivery/');
	$stage_key = preg_replace('/[0-9]+/', '', $a['stage']);
	$errors = array(); $warnings = array();

	/* Move Files configuration into Delivery for this department / stage */
	$exts = array(); $configured = false;
	if ($ctx['kind'] != 'auto') {
		$r = $db->query("SELECT d.move_files FROM inw_move_files f JOIN inw_move_files_dept_wise d ON d.mf_id = f.mf_id
			WHERE f.cust_id = " . (int)$a['cust_id'] . " AND f.j_id = " . (int)$a['j_id'] . " AND f.dept_id = " . (int)$a['department_id'] . "
			AND d.dept_id = 15 AND d.stage = '" . $db->real_escape_string($stage_key) . "' AND d.move_files <> ''");
		while ($r && ($row = $r->fetch_assoc())) { $configured = true; foreach (explode('|', $row['move_files']) as $e) { $e = strtolower(trim($e, " .\t")); if ($e !== '') { $exts[$e] = $e; } } }
	}
	$files = array(); $moving = 0; $src_paths = array();
	foreach (sw3_scan($src) as $f) {
		$x = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
		$will = isset($exts[$x]) && !((int)$a['cust_id'] == 82 && strtolower($f['name']) !== strtolower($a['pub_id'] . '.' . $x));
		$i = ($will || in_array($x, array('zip', 'pdf'), true)) ? sw3_file_info($f['path'], $f['name']) : sw3_file_info($f['path'], $f['name']);
		if (!$will) { unset($i['entries']); }
		$i['rel'] = $f['rel']; $i['will_move'] = $will; $src_paths[$f['rel']] = $f['path'];
		if ($will) {
			$moving++;
			if (!empty($i['error'])) { $errors[] = $f['rel'] . ': ' . $i['error']; }
			elseif (isset($i['age_hours']) && $i['age_hours'] > 24) { $warnings[] = $f['rel'] . ' was last modified ' . $i['modified'] . ' (older than 24 hours).'; }
			if (in_array($x, array('zip', 'pdf'), true) && stripos($f['name'], $a['pub_id']) === false) { $warnings[] = $f['name'] . ' does not contain the article id ' . $a['pub_id'] . ' in its name.'; }
		}
		$files[] = $i;
	}
	$cfg_rows = array();
	foreach ($exts as $x) {
		$found = array(); foreach ($files as $f) { if ($f['will_move'] && strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) === $x) { $found[] = $f['rel']; } }
		$cfg_rows[] = array('ext' => $x, 'found' => $found);
		if (!$found) { $warnings[] = '.' . $x . ' is required by the Move Files configuration but is not in the ' . $dd['dept_name'] . ' folder.'; }
	}
	if ($ctx['kind'] != 'auto' && !$configured) { $warnings[] = 'No file movement is configured for ' . $stage_key . ' / ' . $dd['dept_name'] . ' - nothing will be moved to Delivery.'; }
	elseif ($ctx['kind'] != 'auto' && $configured && !$moving) { $warnings[] = 'No file in the ' . $dd['dept_name'] . ' folder matches the Move Files configuration - Delivery would receive nothing.'; }

	/* automatic upload: the package it will send */
	$auto = null;
	if ($ctx['auto'] !== '') {
		$cand = array(); $seen = array();
		foreach (array($src, $base . 'Delivery/', $base . 'Proofing/') as $d) {
			$d = wf_existing_dir($d);
			foreach (array('zip', 'pdf') as $x) { $p = $d . $a['pub_id'] . '.' . $x; if (is_file($p) && !isset($seen[realpath($p)])) { $seen[realpath($p)] = 1; $i = sw3_file_info($p, $a['pub_id'] . '.' . $x); $i['where'] = basename(rtrim($d, '/\\')); if ($i['type'] == 'zip' && isset($i['entries'])) { } $cand[] = $i; } }
		}
		$auto = array('label' => $ctx['auto'], 'package' => $cand);
		if (!$cand) { $errors[] = 'No package (' . $a['pub_id'] . '.zip or .pdf) was found for the automatic upload.'; }
		foreach ($cand as $c) { if (!empty($c['error'])) { $errors[] = $c['name'] . ': ' . $c['error']; } }
	}
	/* what is already in the Delivery folder, and which files about to move have the same name (the move copies over an existing file of the same name) */
	$dest_files = array();
	if ($ctx['kind'] != 'auto' && is_dir($dest)) {
		$dmap = array();
		foreach (sw3_scan($dest) as $df) {
			$sz = @filesize($df['path']);
			$dest_files[] = array('rel' => $df['rel'], 'name' => $df['name'], 'size' => $sz, 'size_text' => sw3_size_text($sz), 'modified' => date('d-M-Y H:i', @filemtime($df['path'])), 'path' => $df['path']);
			if (strpos($df['rel'], '/') === false && strpos($df['rel'], '\\') === false) { $dmap[strtolower($df['name'])] = count($dest_files) - 1; }   /* the move puts files at the top level of Delivery */
		}
		foreach ($files as $fi => $f) {
			if (empty($f['will_move'])) { continue; }
			$k = strtolower($f['name']);
			if (!isset($dmap[$k])) { continue; }
			$di = $dmap[$k];
			$same = false;
			if ($dest_files[$di]['size'] === $f['size'] && $f['size'] > 0 && $f['size'] < 200 * 1048576 && isset($src_paths[$f['rel']])) { $same = @md5_file($src_paths[$f['rel']]) === @md5_file($dest_files[$di]['path']); }
			$dest_files[$di]['collides'] = true; $dest_files[$di]['same'] = $same;
			$files[$fi]['dest_exists'] = true;
			$warnings[] = $same
				? $f['name'] . ' already exists in the Delivery folder with identical content - it will be overwritten with the same file.'
				: $f['name'] . ' already exists in the Delivery folder (existing ' . $dest_files[$di]['size_text'] . ', ' . $dest_files[$di]['modified'] . '; new ' . $f['size_text'] . ', ' . $f['modified'] . ') and will be overwritten.';
		}
		foreach ($dest_files as $di => $d) { unset($dest_files[$di]['path']); }   /* server paths stay on the server */
	}
	$cn = ''; $jn = '';
	$q = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = " . (int)$a['cust_id']); if ($q && ($x = $q->fetch_assoc())) { $cn = $x['cust_name']; }
	$q = $db->query("SELECT j_code, j_name FROM adm_journals WHERE j_id = " . (int)$a['j_id']); if ($q && ($x = $q->fetch_assoc())) { $jn = $x['j_code'] !== '' ? $x['j_code'] : $x['j_name']; }
	return array('cust' => $cn, 'journal' => $jn, 'ctx' => $ctx, 'dept' => $dd['dept_name'], 'src_dir' => $src, 'dest_dir' => $dest, 'files' => $files, 'dest_files' => $dest_files, 'config' => array('configured' => $configured, 'rows' => $cfg_rows),
		'auto' => $auto, 'warnings' => array_values(array_unique($warnings)), 'errors' => array_values(array_unique($errors)), 'moving' => $moving);
}

/* live check used by the page: POST sw3_pre_delivery=1 (aid in the URL) -> data + a log row (the popup is shown) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw3_pre_delivery'])) {
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . (int)$_REQUEST['aid']); $a = $r ? $r->fetch_assoc() : null;
	$data = $a ? sw3_pre_delivery_data($db, $a) : null;
	if (!$data) { echo json_encode(array('applies' => false)); exit; }
	sw3_log_table($db);
	$e = function ($v) use ($db) { return $db->real_escape_string((string)$v); };
	$decision = $data['errors'] ? 'blocked' : 'shown';
	$db->query("INSERT INTO inw_delivery_confirm_log (a_id,pub_id,cust_id,j_id,stage,from_dept,to_label,kind,user_id,user_name,ip,src_dir,dest_dir,folder,config,auto_info,warnings,errors,dest_before,decision,shown_at)
		VALUES (" . (int)$a['id'] . ",'" . $e($a['pub_id']) . "'," . (int)$a['cust_id'] . "," . (int)$a['j_id'] . ",'" . $e($a['stage']) . "','" . $e($data['dept']) . "','" . $e($data['ctx']['to']) . "','" . $e($data['ctx']['kind']) . "','" . $e(@$_COOKIE['user_id']) . "','" . $e(@$_COOKIE['full_name']) . "','" . $e($_SERVER['REMOTE_ADDR']) . "',
		'" . $e($data['src_dir']) . "','" . $e($data['dest_dir']) . "','" . $e(json_encode($data['files'])) . "','" . $e(json_encode($data['config'])) . "','" . $e(json_encode($data['auto'])) . "','" . $e(json_encode($data['warnings'])) . "','" . $e(json_encode($data['errors'])) . "','" . $e(json_encode($data['dest_files'])) . "','" . $decision . "',NOW())");
	$data['log_id'] = (int)$db->insert_id; $data['applies'] = true;
	$data['pub_id'] = $a['pub_id']; $data['stage'] = $a['stage'];
	echo json_encode($data); exit;
}
/* v3 server-side check: a Completed submit that goes to Delivery / an automatic upload is only accepted with a confirmation recorded by the popup
   (same article, same user, decision "confirmed", not used yet, within the last 60 minutes). Otherwise it is refused, and the refusal is logged. */
function sw3_enforce($db) {
	if (is_file(__DIR__ . '/start_work_v3.disabled')) { return; }
	if ((string)(isset($_POST['status']) ? $_POST['status'] : '') !== '1') { return; }                                   /* Completed only */
	if (trim((string)(isset($_POST['assign_department']) ? $_POST['assign_department'] : '')) !== '' || trim((string)(isset($_POST['skip_dept']) ? $_POST['skip_dept'] : '')) !== '') { return; }   /* routed elsewhere */
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . (int)(isset($_POST['id']) ? $_POST['id'] : 0)); $a = $r ? $r->fetch_assoc() : null;
	if (!$a) { return; }
	$ctx = sw3_context($db, $a);
	if (!$ctx) { return; }
	sw3_log_table($db);
	$uid = $db->real_escape_string((string)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : ''));
	$tok = (int)(isset($_POST['sw3_log_id']) ? $_POST['sw3_log_id'] : 0);
	$ok = false;
	if ($tok > 0) {
		$q = $db->query("SELECT id FROM inw_delivery_confirm_log WHERE id = " . $tok . " AND a_id = " . (int)$a['id'] . " AND user_id = '" . $uid . "' AND decision = 'confirmed' AND outcome IS NULL AND decided_at >= (NOW() - INTERVAL 60 MINUTE)");
		$ok = $q && $q->num_rows > 0;
	}
	if ($ok) { return; }
	$why = $tok > 0 ? 'the confirmation was missing, already used, or older than 60 minutes' : 'no confirmation was sent with the submit';
	$e = function ($v) use ($db) { return $db->real_escape_string((string)$v); };
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']); $dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	$db->query("INSERT INTO inw_delivery_confirm_log (a_id,pub_id,cust_id,j_id,stage,from_dept,to_label,kind,user_id,user_name,ip,decision,shown_at,outcome,outcome_message,finished_at)
		VALUES (" . (int)$a['id'] . ",'" . $e($a['pub_id']) . "'," . (int)$a['cust_id'] . "," . (int)$a['j_id'] . ",'" . $e($a['stage']) . "','" . $e($dd['dept_name']) . "','" . $e($ctx['to']) . "','" . $e($ctx['kind']) . "','" . $uid . "','" . $e(@$_COOKIE['full_name']) . "','" . $e($_SERVER['REMOTE_ADDR']) . "','refused',NOW(),'failed','Refused by the server: " . $e($why) . "',NOW())");
	header('Location: start_work_v3.php?aid=' . (int)$a['id'] . '&msg=' . rawurlencode('The pre-delivery check was not confirmed (' . $why . '). Please reload this page and submit again so the files can be reviewed and confirmed.'));
	exit;
}

/* the user's decision: POST sw3_decision=1, log_id, decision = confirmed | cancelled */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw3_decision'])) {
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/json; charset=utf-8');
	$d = in_array($_POST['decision'], array('confirmed', 'cancelled'), true) ? $_POST['decision'] : 'cancelled';
	sw3_log_table($db);
	$db->query("UPDATE inw_delivery_confirm_log SET decision = '" . $d . "', decided_at = NOW() WHERE id = " . (int)$_POST['log_id'] . " AND decision <> 'blocked'");
	echo json_encode(array('ok' => true)); exit;
}
/* after the update step: record the outcome + what landed in Delivery, then send the report */
function sw3_after_submit($res) {
	global $db;
	if (empty($_POST['sw3_log_id'])) { return; }
	try {
		sw3_log_table($db);
		$id = (int)$_POST['sw3_log_id'];
		$r = $db->query("SELECT * FROM inw_delivery_confirm_log WHERE id = " . $id); $row = $r ? $r->fetch_assoc() : null;
		if (!$row) { return; }
		$after = array();
		if (is_dir($row['dest_dir'])) { foreach (sw3_scan($row['dest_dir'], 200) as $f) { $i = sw3_file_info($f['path'], $f['name']); $i['rel'] = $f['rel']; $after[] = $i; } }
		$ok = !empty($res['ok']);
		$msg = $ok ? (isset($res['title']) ? $res['title'] : 'Completed') : trim((isset($res['title']) ? $res['title'] : '') . (!empty($res['message']) ? ': ' . $res['message'] : ''));
		$db->query("UPDATE inw_delivery_confirm_log SET outcome = '" . ($ok ? 'completed' : 'failed') . "', outcome_message = '" . $db->real_escape_string($msg) . "', after_folder = '" . $db->real_escape_string(json_encode($after)) . "', finished_at = NOW() WHERE id = " . $id);
		$rm = sw3_notify($db, $id);
		$db->query("UPDATE inw_delivery_confirm_log SET report_mail = '" . $db->real_escape_string($rm) . "' WHERE id = " . $id);
	} catch (Exception $e) { /* a log problem must never break the submit */ }
}
function sw3_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sw3_report($row) {
	$tr = function ($k, $v) { return '<tr><td style="padding:5px 14px 5px 0;color:#5b6b7b;vertical-align:top;white-space:nowrap;"><strong>' . $k . '</strong></td><td style="padding:5px 0;">' . $v . '</td></tr>'; };
	$dec = $row['decision']; $ok = $row['outcome'] === 'completed';
	$files = json_decode($row['folder'], true); if (!is_array($files)) { $files = array(); }
	$cfg = json_decode($row['config'], true); $auto = json_decode($row['auto_info'], true);
	$warn = json_decode($row['warnings'], true); if (!is_array($warn)) { $warn = array(); }
	$after = json_decode($row['after_folder'], true); if (!is_array($after)) { $after = array(); }
	$line = function ($f) { $h = '<div style="margin:5px 0 1px;"><strong>' . sw3_h(isset($f['rel']) ? $f['rel'] : $f['name']) . '</strong> <span style="color:#5b6b7b;">(' . sw3_h(isset($f['size_text']) ? $f['size_text'] : '') . ', modified ' . sw3_h(isset($f['modified']) ? $f['modified'] : '-') . ')</span>';
		if (!empty($f['pages'])) { $h .= ' &middot; ' . (int)$f['pages'] . ' page(s)'; }
		if (!empty($f['error'])) { $h .= ' <span style="color:#c0392b;">' . sw3_h($f['error']) . '</span>'; }
		$h .= '</div>';
		if (!empty($f['entries'])) { $h .= '<div style="margin:1px 0 4px 14px;font-size:12px;line-height:1.5;color:#33414f;"><em>' . (int)(isset($f['entries_total']) ? $f['entries_total'] : count($f['entries'])) . ' file(s) inside:</em><br>'; foreach (array_slice($f['entries'], 0, 150) as $e) { $h .= '&bull; ' . sw3_h($e['name']) . ' <span style="color:#8a95a1;">(' . sw3_h($e['size']) . ')</span><br>'; } $h .= '</div>'; }
		return $h; };
	$moving = ''; $stay = '';
	foreach ($files as $f) { if (!empty($f['will_move'])) { $moving .= $line($f); } else { $stay .= '<div style="font-size:12.5px;color:#5b6b7b;">' . sw3_h($f['rel']) . ' (' . sw3_h(isset($f['size_text']) ? $f['size_text'] : '') . ')</div>'; } }
	$col = $ok ? '#2e8b57' : ($row['outcome'] === 'failed' ? '#c0392b' : '#b87800');
	$res = $ok ? 'Completed' : ($row['outcome'] === 'failed' ? 'Failed' : 'Not completed (' . sw3_h($dec) . ')');
	$b = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#22303f;"><p>Hi Team,</p>'
		. '<p><strong>' . sw3_h($row['user_name']) . '</strong> confirmed the pre-delivery check for <strong>' . sw3_h($row['pub_id']) . '</strong> (' . sw3_h($row['stage']) . ', ' . sw3_h($row['from_dept']) . ' &rarr; ' . sw3_h($row['to_label']) . ') on ' . date('d-M-Y h:i A', strtotime($row['decided_at'] ?: $row['shown_at'])) . '. Result: <strong style="color:' . $col . ';">' . $res . '</strong>' . (!$ok && $row['outcome_message'] !== '' ? ' &mdash; ' . sw3_h($row['outcome_message']) : '') . '.</p>'
		. '<table cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">' . $tr('Article', sw3_h($row['pub_id']) . ' (ID ' . (int)$row['a_id'] . ')') . $tr('Stage / department', sw3_h($row['stage']) . ' / ' . sw3_h($row['from_dept']))
		. $tr('Goes to', sw3_h($row['to_label'])) . $tr('Confirmed by', sw3_h($row['user_name']) . ' (ID ' . sw3_h($row['user_id']) . ', ' . sw3_h($row['ip']) . ')') . $tr('Popup shown', sw3_h($row['shown_at'])) . $tr('Decision', sw3_h($dec)) . '</table>'
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Files that go to Delivery</h3>' . ($moving !== '' ? $moving : '<p style="color:#b87800;margin:6px 0;">No file matched the Move Files configuration.</p>')
		. ($stay !== '' ? '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Other files in the folder (stay here)</h3>' . $stay : '');
	if (is_array($cfg) && !empty($cfg['rows'])) { $b .= '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Move Files configuration</h3>'; foreach ($cfg['rows'] as $c) { $b .= '<div>' . (!empty($c['found']) ? '<strong style="color:#2e8b57;">&#10003;</strong>' : '<strong style="color:#c0392b;">&#10007;</strong>') . ' <strong>.' . sw3_h($c['ext']) . '</strong> &mdash; ' . (!empty($c['found']) ? sw3_h(implode(', ', $c['found'])) : '<span style="color:#c0392b;">not in the folder</span>') . '</div>'; } }
	if (is_array($auto)) { $b .= '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">' . sw3_h($auto['label']) . '</h3>'; foreach ($auto['package'] as $f) { $b .= $line($f) ; } if (empty($auto['package'])) { $b .= '<p style="color:#c0392b;">No package found.</p>'; } }
	$dbf = json_decode(isset($row['dest_before']) ? $row['dest_before'] : '', true); if (!is_array($dbf)) { $dbf = array(); }
	if ($dbf) {
		$b .= '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Already in the Delivery folder before the move (' . count($dbf) . ')</h3>';
		foreach ($dbf as $d) { $b .= '<div style="margin:3px 0;">' . (!empty($d['collides']) ? '<strong style="color:#b87800;">&#9888;</strong> ' : '') . sw3_h($d['rel']) . ' <span style="color:#5b6b7b;">(' . sw3_h($d['size_text']) . ', modified ' . sw3_h($d['modified']) . ')</span>' . (!empty($d['collides']) ? ' &mdash; <span style="color:#b87800;">' . (!empty($d['same']) ? 'same name and identical content, overwritten' : 'same name as a file being moved, overwritten') . '</span>' : '') . '</div>'; }
	}
	if ($warn) { $b .= '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Warnings shown to the user</h3>'; foreach ($warn as $w) { $b .= '<div style="color:#b87800;">&#9888; ' . sw3_h($w) . '</div>'; } }
	if ($after) { $b .= '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Delivery folder after the move (' . count($after) . ')</h3>'; foreach ($after as $f) { $b .= $line($f); } }
	$b .= '<p style="color:#888;font-size:12px;margin-top:16px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs. The full record is in Reports &gt; Dispatch Log &gt; Pre-delivery checks.</p></div>';
	return array('subject' => 'PRE-DELIVERY ' . ($ok ? 'CONFIRMED' : 'CHECK - ' . strtoupper($row['outcome'] ?: $dec)) . ': ' . $row['pub_id'] . ' (' . $row['stage'] . ', ' . $row['from_dept'] . ' to ' . $row['to_label'] . ') by ' . $row['user_name'], 'body' => $b);
}
function sw3_notify($db, $id) {
	try {
		sw_switch_ensure($db);
		$cfg = sw_mail_get($db, 'dispatch');
		$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
		if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) { return 'Not sent - no recipients configured'; }
		require_once __DIR__ . '/email-functions.php';
		$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
		if (!$es) { return 'Not sent - email_settings missing'; }
		$r = $db->query("SELECT * FROM inw_delivery_confirm_log WHERE id = " . (int)$id); $row = $r ? $r->fetch_assoc() : null;
		if (!$row) { return 'Not sent - log row missing'; }
		$m = sw3_report($row);
		$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
			implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']), $m['subject'], $m['body']);
		return $ok ? 'Sent' : 'Failed - see smtp_mail.log';
	} catch (Exception $e) { return 'Failed - ' . substr($e->getMessage(), 0, 150); }
}

/* PeerJ metadata HTML view: pj-meta-xml.php (10.10.10.94) writes <pub_id>-metadata.html into the downloads copy of the stage folder; hand it to the page
   POST sw_pj_meta=1, aid */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_pj_meta'])) {
	header('Content-Type: text/html; charset=utf-8');
	header('Cache-Control: no-store');
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".(int)$_POST['aid']);
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a || $a['cust_id'] != 33) { exit; }
	if ($a['volume_folder'] == "Vol00000") { $a['volume_folder'] .= $a['issueorgroup_folder']; }
	$f = str_replace('production', 'downloads', $a['work_dir']).$a['volume_folder']."/".$a['pub_id']."/".$a['stage']."/".$a['pub_id']."-metadata.html";
	foreach (sw_path_candidates($f) as $c) { if (@is_file($c)) { echo file_get_contents($c); break; } }
	exit;
}

/* "Use old version": the user goes back to start_work.php. A reason is mandatory (screenshots / files may be attached); it is logged
   (sw_switch_log) and e-mailed to the To / CC / BCC kept in wf_settings (screen: sw_switch_settings.php).
   POST (multipart) sw_switch_legacy=1, aid, reason, att[] */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_switch_legacy'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	require_once __DIR__.'/sw_switch_lib.php';
	$reason = trim((string)(isset($_POST['reason']) ? $_POST['reason'] : ''));
	if (function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') < 10 : strlen($reason) < 10) {
		echo json_encode(array('ok' => false, 'message' => 'Please tell us why you are switching (at least 10 characters).')); exit;
	}
	if (strlen($reason) > 2000) { $reason = substr($reason, 0, 2000); }
	$files = sw_switch_collect_files();
	if (isset($files['error'])) { echo json_encode(array('ok' => false, 'message' => $files['error'])); exit; }
	sw_switch_ensure($db);
	$a = array('id' => (int)$_REQUEST['aid'], 'pub_id' => '', 'stage' => '', 'cust_id' => 0, 'j_id' => 0, 'department_id' => 0);
	$r = $db->query("SELECT id, pub_id, stage, cust_id, j_id, department_id, status FROM inw_inward_dtl WHERE id = ".(int)$_REQUEST['aid']);
	if ($r && ($row = $r->fetch_assoc())) { $a = $row; }
	$cn = ''; $jn = ''; $dn = '';
	$r = $db->query("SELECT cust_name FROM adm_customers WHERE cust_id = ".(int)$a['cust_id']); if ($r && ($x = $r->fetch_assoc())) { $cn = $x['cust_name']; }
	$r = $db->query("SELECT j_name, j_code FROM adm_journals WHERE j_id = ".(int)$a['j_id']);   if ($r && ($x = $r->fetch_assoc())) { $jn = $x['j_name']; }
	$r = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".(int)$a['department_id']); if ($r && ($x = $r->fetch_assoc())) { $dn = $x['dept_name']; }
	if ($dn === '' && isset($a['status'])) { $dn = (string)$a['status']; }   /* the current department text when the id is 0 / unknown */
	$uname = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ''; $uid = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';

	/* log first (the attachments are filed under its id), then fill in the files and the e-mail result */
	$db->query("INSERT INTO sw_switch_log (article_id, pub_id, stage, dept, user_id, user_name, reason, mail_status, created_at) VALUES ("
		.(int)$a['id'].", '".$db->real_escape_string($a['pub_id'])."', '".$db->real_escape_string($a['stage'])."', '".$db->real_escape_string($dn)."', '"
		.$db->real_escape_string($uid)."', '".$db->real_escape_string($uname)."', '".$db->real_escape_string($reason)."', 'Pending', NOW())");
	$log_id = (int)$db->insert_id;
	$meta = sw_switch_store_files($log_id, $files);
	if ($meta) { $db->query("UPDATE sw_switch_log SET files = '".$db->real_escape_string(json_encode($meta))."' WHERE id = ".$log_id); }

	$cfg = sw_switch_get($db);
	$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
	$mail_status = '';
	if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) {
		$mail_status = 'Not sent - no recipients configured';
	} else {
		try {
			require_once __DIR__.'/email-functions.php';
			$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
			if (!$es) { $mail_status = 'Not sent - email_settings missing'; }
			else {
				$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
				$att_paths = array(); $att_names = array();
				foreach ($meta as $m) { $att_paths[] = sw_switch_dir().'/'.$m['s']; $att_names[] = $h($m['n']); }
				$body = '<p>Hi Team,</p><p>A user has switched back from the <strong>new Start Work (v3)</strong> to the <strong>old Start Work</strong> page and gave the reason below.</p>'
					. '<table cellpadding="4" cellspacing="0" border="0">'
					. '<tr><td><strong>User</strong></td><td>'.$h($uname).($uid !== '' ? ' (ID '.$h($uid).')' : '').'</td></tr>'
					. '<tr><td><strong>Article</strong></td><td>'.$h($a['pub_id']).' (ID '.(int)$a['id'].')</td></tr>'
					. '<tr><td><strong>Customer</strong></td><td>'.$h($cn).'</td></tr>'
					. '<tr><td><strong>Journal</strong></td><td>'.$h($jn).'</td></tr>'
					. '<tr><td><strong>Stage / Department</strong></td><td>'.$h($a['stage']).' / '.$h($dn).'</td></tr>'
					. '<tr><td><strong>Switched on</strong></td><td>'.date('d-M-Y h:i A').'</td></tr>'
					. '<tr><td style="vertical-align:top;"><strong>Reason</strong></td><td>'.nl2br($h($reason)).'</td></tr>'
					. ($att_names ? '<tr><td style="vertical-align:top;"><strong>Attachments</strong></td><td>'.implode('<br>', $att_names).'</td></tr>' : '')
					. '</table>'
					. '<p>Please look into the issue so the new page can be used for this case.</p>'
					. '<p style="color:#888;font-size:12px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs.</p>';
				$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
					implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']),
					'START WORK: '.$uname.' switched to the old page - '.$a['pub_id'], $body, $att_paths);
				$mail_status = $ok ? 'Sent' : 'Failed - see smtp_mail.log';
			}
		} catch (Exception $e) { $mail_status = 'Failed - '.substr($e->getMessage(), 0, 150); }
	}
	$db->query("UPDATE sw_switch_log SET mail_status = '".$db->real_escape_string($mail_status)."' WHERE id = ".$log_id);
	if ((int)$a['id'] > 0) { $_SESSION['sw_legacy_ok'][(int)$a['id']] = time(); }   /* start_work.php lets this user open the old page for this article */
	echo json_encode(array('ok' => true, 'mail' => $mail_status)); exit;
}

/* ---------------------------------------------------------------------------
 * One read of inw_transactions per page load.
 *
 * inw_transactions has no index on project_id, so every query on it reads the whole
 * table. The page used to run four of them (in-progress check, correction count,
 * first start time, ...). They are all about the same article, so the article's
 * rows are fetched ONCE and the rest is answered from memory.
 * ------------------------------------------------------------------------- */
class SwRows
{
	public $num_rows = 0;
	private $rows;
	function __construct($rows) { $this->rows = array_values($rows); $this->num_rows = count($this->rows); }
	function fetch_assoc() { return $this->num_rows && count($this->rows) ? array_shift($this->rows) : null; }
}
function sw_tx_all($db, $project_id, $push = null)
{
	static $cache = array();
	$project_id = (int)$project_id;
	if (!isset($cache[$project_id])) {
		$cache[$project_id] = array();
		$r = $db->query("SELECT id, dept, stage, current_stage, current_status, process_user, created_by, sub_user_id, start_time
		                 FROM `inw_transactions` WHERE `project_id` = ".$project_id." ORDER BY id ASC");
		while ($r && ($row = $r->fetch_assoc())) { $cache[$project_id][] = $row; }
	}
	if ($push) { $cache[$project_id][] = $push; }
	return $cache[$project_id];
}
/* is this transaction an in-progress one of the logged in user? (same tests as the old queries) */
function sw_tx_is_mine($t, $sub_user)
{
	if ($t['current_status'] !== 'Inprogress') { return false; }
	if ($sub_user) { return (string)$t['sub_user_id'] === (string)$_COOKIE['sub_user_id']; }
	return (string)$t['process_user'] === (string)$_COOKIE['user_id'] && (string)$t['created_by'] === (string)$_COOKIE['user_id'];
}
/* in-progress transactions of this article for the logged in user.
   Fast path: the article row points at its latest transaction, which is a primary key lookup. When the user is simply
   resuming (reload, reopen, the ajax submit) that row is the answer and the table is never scanned. */
function sw_tx_inprogress($db, $project_id, $sub_user)
{
	global $app_res;
	$lt = (isset($app_res['id']) && (int)$app_res['id'] === (int)$project_id && !empty($app_res['last_transaction_id'])) ? (int)$app_res['last_transaction_id'] : 0;
	if ($lt > 0) {
		$r = $db->query("SELECT id, project_id, current_status, process_user, created_by, sub_user_id FROM `inw_transactions` WHERE id = ".$lt);
		$t = $r ? $r->fetch_assoc() : null;
		if ($t && (int)$t['project_id'] === (int)$project_id && sw_tx_is_mine($t, $sub_user)) { return new SwRows(array(array('id' => $t['id']))); }
	}
	$out = array();
	foreach (sw_tx_all($db, $project_id) as $t) {
		if (sw_tx_is_mine($t, $sub_user)) { $out[] = array('id' => $t['id']); }
	}
	return new SwRows($out);
}
function sw_tx_count($db, $project_id, $dept, $stage)
{
	$n = 0;
	foreach (sw_tx_all($db, $project_id) as $t) {
		if ((string)$t['dept'] === (string)$dept && strcasecmp($t['stage'], $stage) === 0) { $n++; }
	}
	return array('cnt' => $n);
}
/* first transaction of this user in this stage (was: ... ORDER BY id ASC LIMIT 1) */
function sw_tx_first_start($db, $project_id, $user_id, $stage)
{
	foreach (sw_tx_all($db, $project_id) as $t) {
		if ((string)$t['process_user'] === (string)$user_id && strcasecmp($t['current_stage'], $stage) === 0) { return array('start_time' => $t['start_time']); }
	}
	return null;
}

if ($SW_AJAX) {
	ob_start();
	register_shutdown_function('sw_ajax_finish');
}
?>

<?php 
include_once("smtpmail/phpmailer/class.phpmailer.php");

session_start();
$fileview=true;

/* $mail_from = "techsupport@transforma.in";
$mail_pass = "Tr@n$!t2018"; */

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];

///echo "fs:".$_REQUEST['fs'];
function readPageDataFromFile($filename) {
    // Check if the file exists
    if (file_exists($filename)) {
        
        // Read the file contents
        $data = file_get_contents($filename);

        // Remove any unnecessary whitespace
        $data = trim($data);

        // Check if the data is a single numeric value
        if (is_numeric($data)) {
            echo intval($data);
        } else {
			echo "Invalid page entry";
		}
    } else {
        echo "File does not exist";
    }
}

if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if ($toatl_count == 0) {   /* v2: unknown article id -> friendly page instead of a blank screen */
		http_response_code(404);
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Article not found</title></head><body style="margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f3f6fa;">'
		   . '<div style="max-width:460px;margin:12vh auto;padding:30px 34px;background:#fff;border:1px solid #e4e9ef;border-left:6px solid #d64545;border-radius:14px;box-shadow:0 8px 24px rgba(20,48,74,.1);">'
		   . '<h2 style="margin:0 0 8px;color:#14304a;font-size:20px;">Article not found</h2>'
		   . '<p style="margin:0 0 18px;color:#4a5b6b;">There is no article with ID <b>'.(int)$_REQUEST['aid'].'</b>. It may have been removed, or the link is wrong.</p>'
		   . '<a href="'.htmlspecialchars($_COOKIE['set_url']).'/currentwork" style="display:inline-block;padding:9px 20px;border-radius:10px;background:#1b6ca8;color:#fff;text-decoration:none;font-weight:600;">Back to Current Work</a></div></body></html>';
		exit;
	}
	if($toatl_count>0)
	{
		
$app_res = $result->fetch_assoc();

		if($_COOKIE['user_level'] == 3)
		{	
			// Convert the string to an array using explode()
			$all_access_dept_array = explode(",", $_COOKIE['department_id'].','.$_COOKIE['odepartment_id']);			
			if ( !in_array($app_res['department_id'], $all_access_dept_array)  && !in_array('All', $all_access_dept_array)) {
				//header("location:projects.php?msg=You do not have access for this Article: ".$app_res['pub_id']);	
			header("location:".$_COOKIE['set_url']."/currentwork?msg=You do not have access for this Article: ".$app_res['pub_id'].""); 	

			}
		}
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET present_user_id = '".$_COOKIE['user_id']."',file_status='Inprogress' WHERE id = ".$_REQUEST['aid']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		
		if($app_res['volume_folder'] =="Vol00000")
		{
			$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
		}
	}
	
	/////////// Here Update the Take Over Transaction record //////
	//if($_REQUEST['tid']!="new")
	/* if(isset($_REQUEST['tid']))
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Inprogress',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);	
	}
	*/
	
	if($_REQUEST['fs']==1)
	{
		$update_fs='YES';
	}
	else
	{
		$update_fs='NO';
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///
if($_COOKIE['user_type']=='S') 
{	
$user_sub=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['sub_user_id']."'"));
//echo $_COOKIE['department_id'];
	$chk_transaction = sw_tx_inprogress($db, $app_res['id'], true);

}
else
{
	
	
	$chk_transaction = sw_tx_inprogress($db, $app_res['id'], false);

	
}

	
	 $transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
	 	  $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`,`sub_user_id`,`process_ulogin_type`,`force_started`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '13', '".$_COOKIE['user_id']."',NOW(),'".$_COOKIE['sub_user_id']."','".$_COOKIE['user_type']."','".$update_fs."');";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
		$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$app_res['department_id']."'"));

		$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">Inprogress</span>';
	 	 $prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$transaction_id."' WHERE id = ".$app_res['id']."; ";			
		$db->query($prj_update_query);
		
		$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes) 
		VALUES ('".$app_res['id']."', 'The work was initiated by ".$_COOKIE['full_name']."', '".$current_user_deptname['dept_name']."', 'Inprogress', '', '')";
		$db->query($article_transactions_sql);
	}
	
	
	 /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
	
}

//echo $transaction_id;

/////////////// Update completed ///////////////
/*
if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET present_user_id = '".$_COOKIE['user_id']."' WHERE id = ".$_REQUEST['aid']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		$app_res = $result->fetch_assoc();
		
		if($app_res['volume_folder'] =="Vol00000")
		{
			$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
		}
	}
	
	/////////// Here Update the Take Over Transaction record //////
	if($_REQUEST['tid']!="new")
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Take Over',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///
if($_COOKIE['user_type']=='S') 
{	
$user_sub=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['sub_user_id']."'"));
//echo $_COOKIE['department_id'];


	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `sub_user_id` =  '".$_COOKIE['sub_user_id']."'  AND current_status ='Inprogress';"); 	

}
else
{
	



	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';"); 	

	
}

	
	 $transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
		 $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`,`sub_user_id`,`process_ulogin_type`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '0', '".$_COOKIE['user_id']."',NOW(),'".$_COOKIE['sub_user_id']."','".$_COOKIE['user_type']."');";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
	}
	
	
	 /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
	
}
*/

//echo $transaction_id;
?>
                        <?php
	//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$app_res['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
	}

	//// Journal Style Validator (WMS) gate — PeerJ/CE and IAP/XML ////
	$style_validator_required = (
		($customer_info['cust_name'] == 'PeerJ' && $pdept_res['dept_name'] == 'CE')
		|| ($customer_info['cust_name'] == 'IAP' && $pdept_res['dept_name'] == 'XML')
	);
						?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS -->
    <style>
th  {
  color: #336199 !important;
  background-color: #fff1f1 !important;
  border-top: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
}
 td {
  color: #336199 !important;
  background-color: #fff !important;
  border-top: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
}
/* td:last-child{
	border-right:  1px solid #dedef3 !important;
} */
table.dataTable tbody td:not(:last-child):after {
    content: "";
    height: 15px;
    width: 0.5px;
    border: none !important;
    position: absolute;
    right: 0;
    top: 20px;
}
.startnav-clock{
	right: 0 !important;
	-webkit-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	-moz-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	background-color: #ec5f19db !important;
	color: #fff !important;
	border-radius: 22px !important;
	font-weight: 600 !important;

}

/* Fullscreen Transparent Overlay */
#overlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Slight opacity for the background */
    z-index: 9998; /* Below the loader */
}

/* Loader Container */
#ajaxLoader {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}


/* Loader Container */

#ajaxLoader_Editlatex{
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_Editlatex span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

#ajaxLoader_Editlatex img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

#ajaxLoader_QC {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}


/* Loader Text */
#ajaxLoader_QC span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_QC img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

#ajaxLoader_SV {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_SV span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_SV img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_e {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_e span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_e img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_html {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_html span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_html img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_tp {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_tp span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_tp img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}
/* Loader Container */
#ajaxLoader_algorithm {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_algorithm span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_algorithm img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

#ajaxLoader_AV {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}


/* Loader Text */
#ajaxLoader_AV span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_AV img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}
  </style>
  </head>	

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=(is_array($_REQUEST['cust_id']) ? implode(",",$_REQUEST['cust_id']) : null);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=(is_array($_REQUEST['j_id']) ? implode(",",$_REQUEST['j_id']) : null);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=(is_array($_REQUEST['stage']) ? implode(",",$_REQUEST['stage']) : null);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

			$fin_stage= preg_replace('/[0-9]+/', '', $app_res['stage']);

?>


      <!-- Page body starts -->
      
      <div class="container-fluid" style="justify-content: space-between;position:sticky;top:120px;background:#fff;padding-bottom:5px;z-index:200;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
           <div class="col-8 col-lg-4">
            <p class="articleheading ms-4">Article Details</p>
			
			
          </div>
 <?php 
	  if(isset($_REQUEST['msg']))
	  {
	  ?>
	  
			<div style="width: 100%; display: flex; justify-content: center; ">
<span style="width:fit-content; height:30px; color:#fff; background:red; border-radius:15px; display:flex; justify-content:center; align-items:center; padding:5px 10px;">
    <?php echo $_REQUEST['msg']; ?>
</span>
      </div>
	  <?php 
		  if($_REQUEST['reset']=='proofing')
		  {
		  ?>
			<div style="width: 100%; display: flex; justify-content: center;">
				<a href="https://xpertproof.transforma.in/xpertproofing/Transforma_XPro/xpertproof/delete_article" target="_blank">Click here to Reset</a>
		   </div>
		  <?php 
		  }
	  }
	  ?>
		  <!-- <div class="col-12 d-none d-lg-flex col-lg-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content;width:-moz-fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
				</div>
			</div>
		    </div>-->
		  
		  <div class="col-4 col-lg-4">
      <div class="row d-flex justify-content-end pe-2">
        <div class="col-3 articlebtns ">
          <!--<a href="javascript:history.back();">-->
          <a href="<?php echo $_COOKIE['set_url'];?>/currentwork">
            <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
        </div>
		<!--<div class="col-12 d-flex d-lg-none col-lg-4 mt-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content;width:-moz-fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
				</div>
			</div>
		</div>-->
      </div>

      
 
					
					      <!-- Table section -->
      <?php /*?> <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
        <div class="row">
       <div class="col-md-12">
                      <div class="" id="widget-box-2">
												<!--<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
													<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-comments"></i>
														Article Information
													</h5>

													
												</div>-->
												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<tbody>
																<tr>
																	<td style="width: 50%" class="">Customer Name</td>
																	<td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
																</tr>
                                <tr>
																	<td class="">Journal</td>
																	<td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
																</tr>
                                <tr>
																	<td class="">Stage</td>
																	<td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
																</tr>
                                <tr>
																	<td class="">Article Title</td>
																	<td class="hidden-480"><?php echo $app_res['pub_id']; ?></td>
																</tr>
                                <tr>
																	<td class="">Received Date</td>
																	<td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></td>
																</tr>
                                <tr>
																	<td class="">Due Date</td>
																	<td class="hidden-480"> <?php
						  if($app_res['stage']=='FP')
						  {
						  echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
						  }
						  else
						  {
						  echo (date('d-m-Y',strtotime($app_res['due_dt'])));
						  }
						  ?></td>
																</tr>
															</tbody>
														</table>
													</div>
												</div>
											</div>
        </div><?php */?>
        <?php /*?><div class="col-md-12">
                    <div class="" id="widget-box-2" >
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
														<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-table"></i>
														File History
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th>																		
																		Stage
																	</th>

																	
																	<th class="hidden-480">View DIR</th>
																</tr>
															</thead>

															<tbody>
																<tr>
																<td style="width: 50%	" class="">All Stages</td>

																	

																	<td class="hidden-480">
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="background: #45766e; color: white; border: 1px solid #45766e;"  href="file:///<?php echo str_replace(WF_DIR,TOOL_DIR,$app_res['work_dir']).$app_res['volume_folder']."/".$app_res['pub_id']."/"; ?>" target="_blank" target="_blank"> <i class="ace-icon fa fa-cloud-download bigger-120"></i> View Previous Files</a>
																		
																	</td>
																</tr>

															</tbody>
														</table>
													</div>
												</div>
											</div><?php */?>
											
                       <?php /*?><div class="" id="widget-box-2" id="widget-box-2" >
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
                         <a style="text-decoration: none !important; color: #ffed8d !important;" href="view_article_emails.php?id=<?php echo $_REQUEST['aid']; ?>">
													<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;border-bottom: 1px solid #ffed8d">
													  	<i class="ace-icon fa fa-envelope"></i>
														&nbsp;Article Information
													</h5>
                        </a>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table" style="border-width: 1px solid white !important">
															
															<tbody>
																<tr style="display: flex; justify-content: space-evenly;">
																	<!--<td class="hidden-480" style="background-color: white !important;">
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="color: white !important" href="<?php echo $_COOKIE['set_url'];?>/view_article_emails.php?id=<?php echo $_REQUEST['aid']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Emails</a>
																		
																	</td>-->
																	
																		<td class="hidden-480" style="background-color: white !important">
                                                                    <a class="btn btn-xs btn-orange btn-sm" href="<?php echo $_COOKIE['set_url'];?>/sendquery/<?php echo $_REQUEST['aid']; ?>" style="color: white !important" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
																		<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup(<?php echo $_REQUEST['aid']; ?>);">View Instructions</a>
																	</td>
																</tr>

															  </tbody>
													  	</table>
												  	</div>
											  	</div>
						</div>
                      </div>
                      </div>
                    </div>
                </div><?php */?>
        <!-- form section  -->
        <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">

       
            <div class="container mt-4">
       		 <form class="form-horizontal" id="validation-form" method="post">
			 
			
<?php
 $squery = "SELECT cust_name,cust_id,id,customer_spec FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
$scustomers = $db->query($squery) or ($db->error);
$customer_sinfo = $scustomers->fetch_assoc();
?>
              <div class="formcont1heading">Work Status and comments to internal department</div>
			  <div class="p-5 pb-0 d-flex flex-column gap-2"> 
              <div class="row mt-1 gap-4 addspacing">
			  
			 <?php 
if ($_COOKIE['department_id'] == 14 && $app_res['stage'] != 'FP' && $app_res['cust_id'] == '66') {

    $manuscriptPath = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/PAG/"; 
    $xmlFile = $manuscriptPath . $app_res['pub_id'] . ".xml";

    // Default values
    $fpage = 0;
    $lpage = 0;

    // Check if file exists before opening
    if (file_exists($xmlFile)) {
        $file = fopen($xmlFile, "r");
        while (!feof($file)) {
            $line = fgets($file);
            if (preg_match('/<fpage>(.*?)<\/fpage>/', $line, $match)) {
                $fpage = $match[1];
            }
            if (preg_match('/<lpage>(.*?)<\/lpage>/', $line, $match)) {
                $lpage = $match[1];
            }
        }
        fclose($file);
    } else {
        // File not found – show blinking, center-aligned error
        echo "
        <style>
            @keyframes blink {
                0% { opacity: 1; }
                50% { opacity: 0; }
                100% { opacity: 1; }
            }
            .blink {
                animation: blink 1s infinite;
                text-align: center;
                color: red;
                font-weight: bold;
                margin-top: 15px;
                font-size: 16px;
            }
        </style>
        <div class='blink'>
            ⚠️ Error: XML file not found at path:<br>$xmlFile
        </div>
        ";
        $fpage = 0;
        $lpage = 0;
    }
}
?>


												 <?php /*?>	<table class="table" style="border-width: 1px solid white !important">
															
															<tbody>
																<tr style="display: flex; justify-content: space-evenly;">
																	<!--<td class="hidden-480" style="background-color: white !important;">
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="color: white !important" href="<?php echo $_COOKIE['set_url'];?>/view_article_emails.php?id=<?php echo $_REQUEST['aid']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Emails</a>
																		
																	</td>-->
																	
																		<td class="hidden-480" style="background-color: white !important">
                                                                    <a class="btn btn-xs btn-orange btn-sm" href="<?php echo $_COOKIE['set_url'];?>/sendquery/<?php echo $_REQUEST['aid']; ?>" style="color: white !important" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
																		<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup(<?php echo $_REQUEST['aid']; ?>);">View Instructions</a>
																	</td>
																</tr>

															  </tbody>
													  	</table><?php */?>
														
														<?php 
					$ceditor_ip = isset($_COOKIE['preediting_server']) && !empty($_COOKIE['preediting_server'])     ? $_COOKIE['preediting_server']     : '10.10.10.109';

					/*  if($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "10.10.10.110" )

					 {
						$ceditor_ip = "10.10.10.109";
					 }
					 else if($_SERVER['HTTP_HOST'] == "117.232.75.162:8020" )
					 {
						$ceditor_ip = "117.232.75.162:8009";

					 }
					 else if($_SERVER['HTTP_HOST'] == "103.66.12.3:8010"  )

					 {
						$ceditor_ip = "103.66.12.3:8030";

					 } */
					 ?>
				<div class=" d-flex gap-3 justify-content-center">
					
					<div class="form_btn cancelbtn">
					 <a class="btn btn-xs btn-orange btn-sm" href="<?php echo $_COOKIE['set_url'];?>/sendquery/<?php echo $_REQUEST['aid']; ?>" style="color: white !important" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
                    </div>
					<div class="form_btn cancelbtn">
                     <a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup(<?php echo $_REQUEST['aid']; ?>);">View Instructions</a>
					</div>
					<?php 
					if($app_res['stage'] == 'FP' && ($app_res['department_id'] =="1" ||$app_res['department_id'] =="2" ))
					{
						if(($app_res['j_id'] == 66 && $app_res['cust_id'] == 7) || ($app_res['cust_id'] == 57 && $app_res['j_id'] == 208) || ($app_res['cust_id'] == 7))
						{
					?>
					<div class="form_btn nxtbtn">
						<a class="btn btn-danger" id="TexprecleanConversionButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Tex Preclean</a>
					</div>
					
					 <?php 
					  include("94_edit_latex_url.php");
						}
					}
					?>
					<?php /* if (!empty($customer_sinfo['customer_spec'])): ?>
						<div class="form_btn cancelbtn">
							<a href="<?php echo $_COOKIE['set_url'];?>/uploads/customer-spec/<?php echo $customer_sinfo['customer_spec']; ?>" download class="btn btn-primary">
								 Spec
							</a>
						</div>
					<?php endif; */ ?>
						<div class="form_btn cancelbtn">
							 <a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal_specs" 
			onclick="return popup_spec(<?php echo $_REQUEST['aid']; ?>);">View Specs</a>
						</div>

					<?php 
					if($app_res['manuscript_report_status']=='1')
					{
					?>
					<div class="form_btn cancelbtn">
                      <a class="btn btn-success" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_login_id']; ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> PE</a>
					</div>
					<?php 
					}
					?>
				</div>

                    <?php 
					
					 
					/*  if($app_res['department_id'] == 1 && ($app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 43) && $app_res['inw_type'] == "Word")
					  {
						  
					  ?>
                      
                      
					 
					    <a class="btn btn-danger" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_login_id']; ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> CE Editor</a>
					   
					    <?php 
					  }
					  */
					  ?>
					  <?php 
					 //echo $_COOKIE['user_id']."--".$app_res['department_id'].$cur_stage = preg_replace('/[0-9]+/', '', $app_res['stage']);
					 $cur_stage = preg_replace('/[0-9]+/', '', $app_res['stage']);

					  if($app_res['department_id'] == 10 && ($_COOKIE['user_id']=='179' || $_COOKIE['user_id']=='54') && $cur_stage=='DRAFT')
					  {
					  ?>
                      
                      
						<div style="display:flex;justify-content:center;">
					    <a class="btn btn-danger" href="https://xperttrack.transforma.in/editorproofing/Transforma_XPro/xproof/user_xpertproof/user_editor.php?fname=<?php echo $app_res['pub_id']; ?>&e=TRANSFORMA" target="_blank" style="width:auto;"><i class="ace-icon fa fa-edit align-top bigger-125"></i> User Proofing</a>
					   </div>
					    <?php 
					  }
					  ?>	
						
						


            <?php 
						 
					
							
					  if(($app_res['department_id'] == 1 || $app_res['department_id'] == 2 || $app_res['department_id'] == 5) && ($app_res['stage'] == 'FP') && ($app_res['cust_id'] == 7 || $app_res['cust_id'] == 33 || $app_res['cust_id'] ==37 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 43 || $app_res['cust_id'] == 57 || $app_res['cust_id'] == 66 || $app_res['cust_id'] == 68 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 76 || $app_res['cust_id'] == 77 || $app_res['cust_id'] == 79 || $app_res['cust_id'] == 46 || $app_res['cust_id'] == 64 || $app_res['cust_id'] == 82 || $app_res['cust_id'] == 84 || $app_res['cust_id'] == 69) && ($app_res['inw_type'] == "Word" || $app_res['inw_type'] == "docx" || $app_res['inw_type'] == "Latex") )
					  {
						$fileview=false;
					  ?>
					  <?php 
	if($app_res['cust_id']!='76')
	{
	?>
<div class="form_btn cancelbtn" style="position:relative;">
    <button id="openModal" type="button" class="btn btn-sm" style="text-decoration:none;color:gray;cursor:pointer;border: 1px solid gray;font-size:14px; height:46px; width: 244px; display: flex; align-items: center; justify-content: space-between; padding: 0 10px;" onclick="buton()">
        <span style="color:black">File Update Status</span>
		<svg class="up" style="display:none;" width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M1 6.5L6 1.5L11 6.5" stroke="#606162" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
		<svg class="down" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="#A2ADB0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
	
    </button>
	<?php include('file_upload_dropdown_v2.php');?>
</div>
	<?php 
	}
	?>

					  <div class="col-lg-12" style="overflow:scroll">
					 <iframe src="http://<?php echo $ceditor_ip; ?>/Transforma/products/word-to-pdf-xml_workflow.php?wid=<?php echo $app_res['id']; ?>" height="500px" width="1300px" scrolling="yes" frameborder="0"></iframe>
					  <!--- <iframe src="http://103.66.12.3:8010/workflow-new/ce-editor-status/index.php?aid=<?php echo $app_res['id']; ?>" height="500px" width="101.8%" scrolling="yes" frameborder="0"></iframe>
						<iframe src="http://103.66.12.3:8030/Transforma/products/manuscript-report-workflow.php?id=<?php echo $app_res['id']; ?>" height="500px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>-->
					  
					 <!--  <div class="space-20"></div>
					  <div class="col-lg-12" style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;">
						<iframe  id="CEEDITOR"  src="http://192.168.0.4/Transforma/ce_editor/home/edit_file/<?php echo $app_res['activity']; ?>/<?php echo $app_res['file_name']; ?>" height="600px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>
					  </div>
					   <div class="space-20"></div> -->
					    <?php 
					  }
					  elseif(($app_res['department_id'] == 22) && ($app_res['stage'] == 'FP' && ($app_res['inw_type'] == "Word" || $app_res['inw_type'] == "docx") && ($app_res['cust_id'] == 66 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 64 | $app_res['cust_id'] == 82 || $app_res['cust_id'] == 77 || $app_res['cust_id'] == 79 || $app_res['cust_id'] == 84 || $app_res['cust_id'] == 69 || $app_res['cust_id'] == 37 )))
					  {
						  
					  ?>
                      
                      <div class="space-20"></div>
					  <div class="col-lg-12" style="border-width:5px;border-radius:20px;border-style:ridge;">
					  <iframe src="http://<?php echo $ceditor_ip; ?>/Transforma/products/word-to-pdf-xml_workflow.php?wid=<?php echo $app_res['id']; ?>" height="500px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>
					  
					
					    <?php 
					  }
					  ?>
					
                      <?php $sif_pkg_only = ($app_res['cust_id'] == "7" && (($app_res['stage'] == "FP" && $app_res['department_id'] == "14") || (preg_match('/^REV[0-9]*$/', $app_res['stage']) && $app_res['department_id'] == "5")));   /* SIF: FP in PRQC (14) and REV in XML (5) - Package creation only */
                      if($sif_pkg_only || (($customer_info['cust_name'] =="TSP" || $customer_info['cust_name'] =="Transforma" || $customer_info['cust_name'] =="EOS" || $app_res['cust_id'] =="80" || $customer_info['cust_name'] =="ACG" || $customer_info['cust_name'] =="RSSDI") && $app_res['stage'] == "FP" && (  ($app_res['status'] == "PRQC" && ($journal_info['j_code'] =="CMC" || $journal_info['j_code'] =="CMES"))  || ($app_res['status'] == "PRQC" || $app_res['status'] == "Proofing" || $app_res['status'] == "PAG")))){
?>	
                                                  
<?php if(!$sif_pkg_only){ ?>
									<span class="btn btn-primary col-2" id="3dtohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> 3D to HTML</span>
								
							<span class="btn btn-primary col-2" id="xmltohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> XML to HTML</span>
							<span class="btn btn-primary col-2" id="opfxmltohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> OPF XML to HTML</span>
							
							<a class="btn btn-primary col-2" id="HTML_View" href="<?php echo Proof_Viewing_URL.$app_res['id']."/".$app_res['pub_id']; ?>" target="_blank"><i class="ace-icon fa fa-eye align-top bigger-125"></i> HTML View</a>
							<?php } ?>
							
           
							<?php 
							if($customer_info['cust_name'] =="EOS")
							{
							?>
							<span class="btn btn-primary col-2" id="eos_fp_package"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php
							}
							elseif($cur_stage!='FIN' && !$sif_pkg_only)
							{
							?>
							<span class="btn btn-primary col-2" id="tsp_fp_package"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php 
							}	
							?>
											
						
							                         
					  <?php }
							if($app_res['cust_id'] =="77" && $cur_stage=='FIN' && $app_res['department_id']=='5')
							{
							?>
							<span class="btn btn-primary col-2" id="acg_fp_package"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<span class="btn btn-primary col-2" id="htmlconversion"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> HTML Conversion</span>
							<?php
							}
							/* TESTING package (not for production delivery) - IAP, XML dept, all REV* and PMC* stages */
							$test_pkg_stage = preg_replace("/(REV|PMC)[0-9]+/", "$1", $app_res['stage']);
							if($customer_info['cust_name'] =="IAP" && $app_res['department_id']=='5' && ($test_pkg_stage=='REV' || $test_pkg_stage=='PMC'))
							{
							?>
							<span class="btn btn-warning col-2" id="testing_package" data-pkg-type="<?php echo $test_pkg_stage=='PMC' ? 'pmc' : 'jstage'; ?>" title="Testing only - not for production delivery"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> TESTING package</span>
							<?php
							}
					      ?>
							<?php
							/* package creation for the zip validator customers and the NOW finals (was part of the submit) */
							$pkg_kind = sw_pkg_kind($app_res['cust_id'], $journal_info['j_code'], $app_res['stage'], $pdept_res['dept_name'], $app_res['department_id']);
							if($pkg_kind=='EPL')
							{
							?>
							<span class="btn btn-primary col-2" id="epl_package" title="Builds the zip package on the tool server. Needed before you submit as Completed."><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php
							}
							elseif($pkg_kind=='TSPFIN' || $pkg_kind=='EOSFIN' || $pkg_kind=='PJREV' || $pkg_kind=='PJFP' || $pkg_kind=='SIF')
							{
							?>
							<span class="btn btn-primary col-2" id="final_zip_package" title="Builds the finals zip on the tool server. Needed before you submit as Completed."><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php
							}
							elseif($pkg_kind=='NOW')
							{
							?>
							<span class="btn btn-primary col-2" id="now_package" title="Builds the NOW finals zip on the tool server. Needed before you submit as Completed."><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php
							}
							?>
					  
						                 </div>
										 
							<div class="col-xs-12 col-sm-12">
									<div style="display:flex; justify-content:center; width:100%;">

									<?php 

									/* =========================
									   INITIAL SETUP
									========================= */
									$copy_files = true;
									$temp_status = $app_res['status'];

									/* Normalize status */
									if (strpos($app_res['status'], "PAG") !== false) {
										$app_res['status'] = "PAG";
									}
									if (strpos($app_res['status'], "CE") !== false) {
										$app_res['status'] = "CE";
									}

									/* =========================
									   CHECK TRANSACTION COUNT
									========================= */
									$trans_res_row = array('cnt' => 0);   /* $copy_files is never read on this page - the per-article count was dead work */

									if ($trans_res_row['cnt'] > 1) {
										$copy_files = false;
									}

									/* =========================
									   COMMON VARIABLES
									========================= */
									$customer = $customer_info['cust_name'];
									$status   = $app_res['status'];
									$stage    = $app_res['stage'];
									$jcode    = $journal_info['j_code'];

									if (
										$stage != "FIN1" &&
										in_array($customer, ["SIF-Editlatex", "SIF", "Transforma", "EDP-Sci", "IAP"]) &&
										(
											// ✅ PAG for all
											$status == "PAG"

											||

											// ✅ CE only for EDP-Sci OR JSIAML
											(
												$status == "CE" &&
												($customer == "EDP-Sci" || $jcode == "JSIAML")
											)

											||

											// ✅ Pre-Editing (EPL / NCC)
											(
												$status == "Pre-Editing" &&
												in_array($jcode, ["EPL", "NCC"])
											)

											||

											// ✅ JSIAML full support
											(
												$jcode == "JSIAML" &&
												in_array($status, ["PAG", "CE", "Pre-Editing"])
											)
										)
									) {

										include_once($_SERVER['DOCUMENT_ROOT'] . "/workflow-new/94_edit_latex_url.php");

										// Special case
										if ($jcode == "JSIAML" && $status == "PAG" && $stage == "FP") {
											$copy_files = false;
										}
									}

									/* Restore original status */
									$app_res['status'] = $temp_status;

									?>

									</div>
									</div>
							   
						  
						  	   <?php if($app_res['status'] == "ePub" && $app_res['department_id']!='5'){
?>	
                      <div class="col-xs-12 col-sm-9">
                            <div class="clearfix">
                                  
							<div class="form_btn nxtbtn">
								<a class="btn btn-success" id="ePub_validation_Creation"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> ePub Creation</a>
							</div>
											
						
							                            </div>
                            </div>
							
                      <div class="space-20"></div>
                      <div class="space-20"></div>
					  <?php } ?>	
                        
                            <input type="hidden" name="action" value="update">
							<input type="hidden" name="cust_id" id="cust_id" value="<?php echo $app_res['cust_id']; ?>" />
							<input type="hidden" name="j_id" value="<?php echo $app_res['j_id']; ?>" /> 
							<input type="hidden" name="tr_id" value="<?php echo $transaction_id; ?>" />
                            <input type="hidden" name="id" value="<?php echo $_REQUEST['aid']; ?>" />
                            <input type="hidden" name="wf_id" value="<?php echo $app_res['wf_id']; ?>" />
                            <input type="hidden" name="stage" id="stage" value="<?php echo $app_res['stage']; ?>" />
                            <input type="hidden" name="work_dir" id="work_dir" value="<?php echo $app_res['work_dir']; ?>">                           
							<input type="hidden" name="department_id" value="<?php echo $app_res['department_id']; ?>" />
                            <input type="hidden" name="dept_name" value="<?php echo $pdept_res['dept_name']; ?>" />
                            <input type="hidden" name="volume_folder" id="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
                            <input type="hidden" name="v_issue_id" value="<?php echo $app_res['v_issue_id']; ?>" />
                            <input type="hidden" name="pub_id" id="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
							<input type="hidden" name="xperttrack_sync" value="<?php echo $app_res['xperttrack_sync']; ?>" />
                            
							
							<?php 
							if($customer_info['id']==42 || $customer_info['id']==72)
							{
								//
							?>
                            <input type="hidden" name="proof_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/Proofing/"; ?>">
                            <input type="hidden" name="article_id" value="<?php echo $app_res['pub_id']; ?>" />
							<!--<input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />-->
                             <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                            <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />	
                            <input name="j_division" type="hidden"  value="<?php echo $journal_info['j_division']; ?>"/>
							<?php
						if ($app_res['stage']== "FP") {
    ?>
							<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
                            <input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
                            <input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
                            <input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                            <input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
							<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
                            <input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
							<textarea name="txtBody" id="txtBody" style="visibility:hidden;" ><?php echo $d_mail_info['message'] ?></textarea>
							<?php
													//COMPUTER DIVISION REVISIES FILES (REV1)

						}
						elseif($app_res['stage']=='REV2' && $app_res['department_id']=='14')
						{
							if($app_res['j_id']!='170' && $app_res['j_id']!='173' && $app_res['j_id']!='167' && $app_res['j_id']!='178' && $app_res['j_id']!='180' && $app_res['j_id']!='257' && $app_res['j_id']!='166' && $app_res['j_id']!='164' && $app_res['j_id']!='175' && $app_res['j_id']!='262')
				{
					?>
										<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
										<input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
										<input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
										<input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
										<input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
										<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
										<input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
										<textarea name="txtBody" id="txtBody" style="visibility:hidden;" type="text" ><?php echo $d_mail_info['message'] ?></textarea>
								<?php 
				}
				else
				{
					?>
										<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
										<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
										<input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
										<input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
								<?php 
					
				}
							
						}
						elseif($fin_stage=='FIN' && $app_res['department_id']=='5')
						{
						
					?>
										<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
										<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
										<input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
										<input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
								<?php 
					
				
							
						}
						elseif($app_res['stage']=='REV1' && $app_res['department_id']=='14' && $journal_info['j_division']=='2' )
			 
						{
								if($app_res['j_id']=='170' && $app_res['j_id']=='173' && $app_res['j_id']=='167' && $app_res['j_id']=='178' && $app_res['j_id']=='180' && $app_res['j_id']=='257' && $app_res['j_id']=='166' && $app_res['j_id']=='164' && $app_res['j_id']=='175' && $app_res['j_id']=='262')
								{								
								?>
										<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
										<input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
										<input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
										<input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
										<input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
										<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
										<input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
										<textarea name="txtBody" id="txtBody" style="visibility:hidden;" type="text" ><?php echo $d_mail_info['message'] ?></textarea>
								<?php 
								}
								elseif($app_res['stage']!='FP')
								{
								?>
										<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
										<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
										<input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
										<input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
								<?php 
								}
					
				
				
			 //COMPUTER DIVISION REVISIES FILES			 (REV1)
			 }
			 else if($journal_info['ftp_send'] == 1 && $app_res['stage']!= "FP" && $journal_info['j_division']==2) {
						?>
                            <input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
							<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                            <input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                            <input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>

						<?php						 
						}else if ( $journal_info['ftp_send'] == 1 && (($app_res['stage']!= "REV1") || ($app_res['stage']!= "FP")) && $journal_info['j_division']==1) {
						?>
							<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
							<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                            <input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                            <input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
						<?php
						}
						
						?>
						
							
							<?php 
							}
							?>
							
							  <?php 
				
			  if($_COOKIE['department_id']==14 && $app_res['stage']!='FP' &&  $app_res['cust_id']=='66')
			  {
			  ?>
			   <input name="fpage" type="hidden" value="<?php echo $fpage; ?>" />
               <input name="lpage" type="hidden" value="<?php echo $lpage; ?>"/>
			  <?php 
			  }
			  ?>
<?php if($fileview){ ?>


<?php 
if($app_res['department_id']==24 && $app_res['stage']=='FP')
{
?>
<div class="col-12 col-sm-12 col-lg-12">
    <label class="mb-2">Choose File Complexity :<span style="color: red">*</span></label>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="file_complexity" id="status_regular" value="regular" checked>
        <label class="form-check-label" for="status_regular">Regular</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="file_complexity" id="status_complex" value="complex">
        <label class="form-check-label" for="status_complex">Complex</label>
    </div>
</div>

<?php 
}
?>

                    <div class="col-12 col-sm-6 col-lg-4">
                    <label class="mb-2" >Choose Status :<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Status" name="status" id="status">
				                                    <option value="">-Select Status-</option>

                                <?php foreach($project_status_array as $id=>$val){?>
										<?php 
										if($val!='Query')
										{
										?>
                                     <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
									 <?php 
										}
										?>
                                 <?php } ?>
                                 </select>
                </div>

                           <div class="col-12 col-sm-3 col-lg-2">
                          <label class="mb-2" >Page Count :<span style="color: red">* (Mandatory for Daily Target for all department)</span></label>
                          <?php if($app_res['cust_id']=="33" && ($app_res['department_id']==14  || $app_res['department_id']==10)){ 
							$work_dir_p = $app_res['work_dir'];
							//$work_dir_p = str_replace(WF_DIR,TOOL_DIR,$work_dir_p);
							$pageCountTxtFile = $work_dir_p.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/PAG/".$app_res['pub_id']."-Pagecount.txt"; 
							?>
                            <div class="clearfix">
                              <input type="number" name="page_count" id="page_count" class="form-control" required  placeholder="Page Count" value="<?php readPageDataFromFile($pageCountTxtFile); ?>" onkeyup="PreventFirstZero(event);">
                            </div>
                            <?php 
						  }
						  else
						  {
							 ?>
							  <div class="clearfix">
                              <input type="number" name="page_count" id="page_count" class="form-control" required  placeholder="Page Count" value="<?php echo ($app_res['page_count'] > 0) ? $app_res['page_count'] : '0'; ?>" onkeyup="PreventFirstZero(event);">
                            </div>
							 <?php 
						  }
						  ?>
							
                          
                         
                        </div>
						 <?php if($app_res['department_id']==14  || $app_res['department_id']==10 || $app_res['department_id']==18){ ?>

						 <?php if(($app_res['status']=="PRQC" || $app_res['status']=="Proofing") && ($app_res['cust_id']=="42" || $app_res['cust_id']=="66"  || $app_res['cust_id']=="77" || $app_res['cust_id']=="80") && $app_res['stage']=="FP"){ ?>
						 <div class="space-2"></div>
						   <div class="col-12 col-sm-10 col-lg-10">
                          <label class="mb-2" >Article title  :<span style="color: red">*</span></label>
                            <div class="clearfix">
								<textarea name="article_title" id="article_title" class="form-control" disabled placeholder="Article title" style="width: 100%; height: 100px; font-size: 16px; resize: none;" ><?php echo htmlspecialchars($app_res['article_title'] ?? ''); ?></textarea>                          
							</div>
                          
                        </div>
						 <div class="space-2"></div>
						   <div class="col-12 col-sm-6 col-lg-4">
                          <label class="mb-2" >Corresponding Author Name  :<span style="color: red">*</span></label>
                          
                            <div class="clearfix">
                              <input type="text" name="corresponding_author" id="corresponding_author" class="form-control" required  placeholder="Corresponding Author Name" value="<?php echo  $app_res['corresponding_author']; ?>">
                            </div>
                          
                        </div>
						 <div class="space-2"></div>
						
						<div class="col-12 col-sm-6 col-lg-6"> <!-- Increased col width -->
							<label class="mb-2">Corresponding Author Email ID  :<span style="color: red">*</span></label>
							<div class="input-group">
								<input type="text" name="corresponding_mail" id="corresponding_mail" 
									   class="form-control" required  
									   placeholder="Corresponding Author Email ID" 
									   value="<?php echo $app_res['corresponding_mail']; ?>">

									   <?php 
										if (
											!isset($app_res['corresponding_mail']) || trim($app_res['corresponding_mail']) === '' ||
											!isset($app_res['corresponding_author']) || trim($app_res['corresponding_author']) === '' ||
											!isset($app_res['corresponding_other_author']) || trim($app_res['corresponding_other_author']) === ''
										) {
										?>
											<button type="button" class="btn btn-danger ms-2" id="Author_Validaion">
												<i class="ace-icon fa fa-check-circle-o align-top bigger-125"></i> Fetch Author Details
											</button>
										<?php 
										}
										?>



							</div>
						</div>
					<div class="space-2"></div>

			<div id="email_error_box" style="color:red; margin-top:8px;"></div>
			<?php 
			if($app_res['cust_id']=="42")
			{
			?>
			<div class="col-12 col-sm-6 col-lg-6"> <!-- Increased col width -->
				<label class="mb-2">Co-Author Email ID :<span style="color: red">*</span></label>
				<div class="input-group">
					<input type="text" name="corresponding_other_author" id="corresponding_other_author" 
						   class="form-control" required  
						   placeholder="Co-Author Email ID" 
						   value="<?php echo $app_res['corresponding_other_author']; ?>">
				</div>
			</div>
			<?php 
			}
			?>


						 <?php } ?>
                        <?php } ?>
                        <div class="space-2"></div>
                        <div class="form-group" id="assign_department_div" style="display:none;">
                        <label class="mb-2" >Assign Department  :<span style="color: red">*</span></label>
                       <div class="col-12 col-sm-6 col-lg-4">
                          <div class="clearfix">
                            <?php
	
		$workflow_query = "SELECT wf_code FROM `adm_workflow_master` WHERE
		`wf_id` =".$app_res['wf_id']."";
		$workflow_details = $db->query($workflow_query) or
		($db->error);
		$workflow_info = $workflow_details->fetch_assoc();
		$all_dpt_info = explode(',',$workflow_info['wf_code']);
		$all_dept_array = $all_dpt_info;
		/// add graphics dpt in list ////
		if (!in_array("9", $all_dpt_info))
		{			
			$all_dpt_info[]="9"; 
		}
		//$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code != ".$app_res['department_id']." ORDER BY `dept_name` ASC";
		$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
		= 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code NOT IN ( ".$app_res['department_id'].",15 ) ORDER BY `dept_name` ASC";
		$customers = $db->query($query) or ($db->error);
			 		

	?>
                            <select id="assign_department" name="assign_department" data-placeholder="Choose" class="form-select customer_drop">
                              <option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
						</div> 

						 <div class="space-2"></div>
                        <div class="form-group" id="assign_user" style="display:none;">
                        <label class="mb-2" >Select User :<span style="color: red">*</span></label>

                        
                       <div class="col-12 col-sm-6 col-lg-4">
                          <div class="clearfix">
                            <select id="a_user_id" name="a_user_id" class="form-select customer_drop" data-placeholder="Choose">
                              <option value="">-Select-</option>
                            </select>
                          </div>
                        </div>
                       </div>
                        
                        <?php 
		if($journal_info['skip_dept'] == '1'){
?>
						<div class="space-2"></div>
                        <div class="form-group" id="skip_department_div" >
                        <label class="mb-2" >Skip to Department :</label>

                       <div class="col-12 col-sm-6 col-lg-4">
                        <div class="clearfix">
    <?php
		
		$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
		= 0 AND dept_code IN (".implode(",",$all_dept_array).") and dept_code !=
		'".$app_res['department_id']."' ";
	    $customers = $db->query($query) or ($db->error);
	?>
                            <select id="skip_dept" name="skip_dept" data-placeholder="Choose" class="form-select customer_drop" >
                              <option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                            </select>
                        </div>
                        </div>
                        </div>
		<?php } ?>
        
                        
                        <div class="space-2"></div>
                         
                          <div class="row" id="checklist_table" style="display:none;">
                           <h3 class="formcont1heading" style="position: relative; left: -37px;">Checklist Form</h3>
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12 pt-3">
                                  <table  id="simple-table" class="table  table-bordered table-hover">
                                    <thead>
                                      <tr>
                                        <th>Details</th>
                                        <th width="10%">Yes</th>
                                        <th width="10%">NA</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php
		$query = "SELECT * FROM `jbm_checklist` WHERE (`stage` = '".$app_res['stage']."' OR `stage` = 'BOTH') AND `dept_id` = '".$app_res['department_id']."' ORDER BY `display_order` ASC ";
		$qry_result = $db->query($query) or ($db->error);		
		while($row = $qry_result->fetch_assoc()){ ?>
		
                                      <tr>
                                        <td><?php echo $row['description']; ?></td>
                                        
<td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="Yes" checked="checked"/>
                                            </td><td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="NA"/>
                                            </td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          <h3 class="formcont1heading" style="position: relative; left: -37px;">Feedback Form</h3>
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12 pt-3">
                                  <table  id="simple-table" class="table  table-bordered table-hover">
                                    <thead>
                                      <tr>
                                        <th width="10%">Name</th>
                                        <th width="10%">Feedback</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php
		$query = "SELECT * FROM `prqc_report_categories` WHERE `stage` LIKE '%".$app_res['stage']."%' ORDER BY `display_order` ASC "; 
		$qry_result = $db->query($query) or ($db->error);		
		while($row = $qry_result->fetch_assoc()){ ?>
		
                                      <tr>
                                        <td>
										<input name="prqc_cats[]" type="hidden" value="<?php echo $row['id']; ?>">
										<?php echo $row['name']; ?></td>
                                        
<td>
                                            <textarea class="form-control mt-2" name="reason[<?php echo $row['id']; ?>]" placeholder="Enter feedback" rows="3"></textarea>
            
                                            </td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
                <div class="col-12 col-sm-12 col-lg-12" id="tmpa" >
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="vcomments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

                <div class="col-12 col-sm-12 col-lg-12"  id="notmpa" style="display:none;">
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="comments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

                <div class="col-12 col-sm-12 col-lg-12"  id="query_div" style="display:none;">
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="message_query" id="message" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

                <div class="container-fluid mt-5 mb-5">
				<?php 
			//echo "folder access :".$_COOKIE['folder_access'];
			if($_COOKIE['folder_access']=='DOMAIN' || $_COOKIE['folder_access']=='BOTH' )
			{
				$work_dir = $app_res['work_dir'];
				$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
				
				// Use preg_replace to remove the "peerj-" prefix
				$updatedFilename = preg_replace('/^peerj-/', '', $app_res['pub_id']);
			?>
				<div class=" d-flex gap-3 justify-content-center">

				<?php 
				if($app_res['v_issue_id'] > 0)
							{
							?>
							 <div class="form_btn">
                             <a class="btn btn-primary" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['stage']."/misc"; ?>" target="_blank">
												<i class="ace-icon fa fa-folder align-top bigger-125  pt-1"></i>
												MISC
											</a>
							</div>
                                            
                     <?php } ?>
                     <div class="form_btn cancelbtn">
                    <a class="btn btn-purple" style="color: white !important" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']; ?>" target="_blank">
					<i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>&nbsp Source</a>
                    </div>
                    <div class="form_btn nxtbtn">
                    <a class="btn btn-success" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Work</a>
                    </div>
					<?php
					if($style_validator_required)
					{
					?>
					<div class="form_btn nxtbtn">
					<a class="btn btn-success" id="styleValidatorButton" target="_blank"><i class="ace-icon fa fa-check-circle align-top bigger-125 pt-1"></i> Style Validator</a>
					</div>
					<div class="form_btn nxtbtn">
					<a class="btn btn-warning" id="bypassValidatorButton" target="_blank"><i class="ace-icon fa fa-exclamation-triangle align-top bigger-125 pt-1"></i> Bypass Validation</a>
					</div>
					<?php
					}
					?>
					<?php
					if($app_res['cust_id']=="33" || $app_res['cust_id']=="57")
					{
					$stage_rev=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);

					?>
					 <div class="form_btn nxtbtn">
							<a class="btn btn-warning" href="<?php echo $journal_info['j_link'];?><?php echo $updatedFilename;?>" target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Forum</a>
					</div>
<?php
						if($app_res['cust_id']=="33" && $app_res['stage']=='FP' && in_array((int)$app_res['department_id'], array(9,10,14)))
						{
						?>
						<div class="form_btn nxtbtn">
								<a class="btn btn-info" id="metaHtmlButton" target="_blank"><i class="ace-icon fa fa-file-code-o align-top bigger-125 pt-1"></i> METADATA HTML</a>
						</div>
						<?php } ?>
						<?php 
						if($stage_rev=='REV' && $pdept_res['dept_name']=="XML")
						{
						?>

						<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="xmlValidationButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> XML Validation</a>
						</div>
						<?php 
						}
						elseif($pdept_res['dept_name']=="PRQC" && $app_res['cust_id']=="33")
						{
						?>
						<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="QCValidationButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> QC Validation</a>
						</div>
						<?php 
						}
						?>
					<?php 
					}
					?>
					<?php 
					if($app_res['cust_id']=="42" || $app_res['cust_id']=="66")
					{
					$stage_rev=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);

					?>
					
						<?php 
						if($stage_rev=='FIN' && $pdept_res['dept_name']=="XML")
						{
						?>
						<div class="form_btn nxtbtn">
							<a class="btn btn-dark" id="xmlCValidationButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> XML Validation</a>
						</div>
						<?php 
					if($app_res['cust_id']=="66" && $app_res['department_id']!='5')
					{
					?>
					<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="ePub_validation_Creation"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> ePub Creation</a>
					</div>
					<?php 
					}
					?>

						
						<?php 
						}
						?>
					<?php 
					}
					?>
					<?php 
					  //if($app_res['department_id'] == 1 && ($app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 43) && $app_res['inw_type'] == "Word")
					  if(($app_res['department_id'] == 1 || $app_res['department_id'] == 2 || $app_res['department_id'] == 22 || $app_res['department_id'] == 29 || $app_res['department_id'] == 5) && ($app_res['stage'] == 'FP') && ($app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 66 || $app_res['cust_id'] == 43 || $app_res['cust_id'] == 68 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 76 || $app_res['cust_id'] == 57 || $app_res['cust_id'] == 58 || $app_res['cust_id'] == 46 || $app_res['cust_id'] == 64 || $app_res['cust_id'] == 82 || $app_res['cust_id'] == 84))
					  {
					  ?>
                      
					 <div class="form_btn nxtbtn">
					 
					    <a class="btn btn-danger" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_login_id']; ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> CE Editor</a>
					    </div>
					    <?php 
					  }
					  ?>
					  
					  	<?php
					if($app_res['status'] =="Tex-pre-clean" && $app_res['department_id'] =="24")
					{
					?>
					<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="htmlConversionButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> HTML Conversion</a>
					</div>
					<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="algorithmConversionButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Algorithm Conversion</a>
					</div>
					<?php 
					}
					$stage_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
					$stage_issf=preg_replace("/ISSFIN([0-9]+)/","ISSFIN",$app_res['stage']);
					if($app_res['cust_id'] == 57 && ($stage_pmc=='PMC' || $stage_issf=='ISSFIN'))
					{
					?>
					<div class="form_btn nxtbtn">
						<a class="btn btn-success" id="xmlCValidationButton"  target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> XML Validation</a>
					</div>
					<?php 
					}						
					?>
						</div>
			<?php 
			}
			?>
			
					
			                <div class="row d-flex gap-3">

					
					</div>
					<?php 
					if($app_res['cust_id']=="76" && $app_res['department_id']=="28")
					{
					?>
							<?php include('tme_data_form.php');?>
					<?php 
					}
					?>
					<?php 
					if($app_res['cust_id']=="76" && $app_res['department_id']=="29")
					{
					?>
							<?php include('amo_data_form.php');?>
					<?php 
					}
					?>
					
				<div class="container-fluid mt-5 mb-5 d-flex justify-content-center">
                <div class="row d-flex gap-3 justify-content-center">
                    <div class="col-12 col-lg-5 form_btn cancelbtn d-flex justify-content-md-end justify-content-center">
                        <button type="button" id="cancel-btn">Cancel</button>
                    </div>
                    <div class="col-12 col-lg-5 form_btn nxtbtn d-flex justify-content-center">
                        <button type="submit" id="submit-btn">Submit</button>
                    </div>
                </div>
          	  </div>
				<?php
	} ?>
			  <!-- Fullscreen Loader -->
<!-- Transparent Overlay -->
<div id="overlay"></div>

<!-- Fullscreen Loader -->
<div id="ajaxLoader">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>XML Validation in Progress...</span>
</div>
<div id="ajaxLoader_e">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>ePUB Creation and Validation in Progress...</span>
</div>
<div id="ajaxLoader_html">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Html Conversion in Progress...</span>
</div>
<div id="ajaxLoader_algorithm">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Algorithm Conversion in Progress...</span>
</div>
<div id="ajaxLoader_tp">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Tex Preclean in Progress...</span>
</div>

<div id="ajaxLoader_QC">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>QC Validation in Progress...</span>
</div>

<div id="ajaxLoader_SV">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Journal Style Validation in Progress...</span>
</div>
<div id="ajaxLoader_AV">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Fetching Author Details in Progress...</span>
</div>

<!-- Fullscreen Loader -->
<div id="ajaxLoader_Editlatex">
    <img src="<?php echo $_COOKIE['set_url'];?>/loader.gif" alt="Loading..." /> <!-- Replace with the path to your loader image -->
    <span>Generating the Edit LaTeX link...</span>
</div>

			  
						<?php 
						   if(isset($_REQUEST['html']))
						   {
						   ?>
                          <div class="form-group" style="">
						  <div class="col-lg-12" style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;padding-bottom:150px;">
						     <?php echo file_get_contents($html_read); ?>
						  </div>
						   <?php 
						   }
						   ?>
						    <div class="col-lg-12" id="result_xml"  style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;padding-bottom:150px;display:none;color:red;justify-content:center;">
						    </div>
							
							<div class="col-lg-12" id="result_qc"  style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;padding-bottom:150px;display:none;color:red;justify-content:center;">
						    </div>
							<div class="col-lg-12" id="result_av"  style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;padding-bottom:150px;display:none;color:red;justify-content:center;">
						    </div>
							<div class="col-lg-12" id="result_tsp_xml"  style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;padding-bottom:150px;display:none;color:red;justify-content:center;">
						    </div>
							

            </div>
			
			</div>
		
		</form>
		</div>
		
			
            </div>
			</div>
			<?php 
			//echo "folder access :".$_COOKIE['folder_access'];
			if($_COOKIE['folder_access']=='WEB' || $_COOKIE['folder_access']=='BOTH' )
			{
			?>
			<div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
			<div class="p-5 pb-0 d-flex flex-column gap-2"> 
                    <?php
                    // $base_path = "C:/xampp/htdocs/New_Workflow/Files/";
                     $base_path = $app_res['work_dir'] .$app_res['volume_folder']. "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/".$pdept_res['dept_name']."/";
                    $base_path = wf_existing_dir($base_path);   /* J:/... (tool path) -> E:/New_Workflow_Files/... (server path) when the J: one is not reachable */
                    //date_default_timezone_set("Asia/Kolkata");
                    extract($_POST);
                    if (isset($back) && $back !== "") {
                        $folder = $backfolder;
                    } else if (isset($upload) && $upload !== "") {
                        $folder = $currentfolder;
                        $uploaddir = $folder;
                        $uploadfile = $uploaddir . basename($_FILES['upload_file']['name']);
						//echo basename($_FILES['upload_file']['name']);exit;
					$expectedName = $app_res['pub_id'];

					$file = $_FILES['upload_file'];
					$filename = $file['name'];
    // Extract filename
	//echo "PUBID: ".$expectedName;
  //  echo "UPLOADED FILENAME : ". $filename = $file['name'];exit;
    

   // Define the regular expression pattern to match variations of the expected name
    $pattern = "/^.*($expectedName(?:\.[\w]+)?).*$/";
    
    // Check if the filename matches the pattern
    if(preg_match($pattern, $filename, $matches)) {
       
  
                        if (file_exists($uploadfile)) {
                            $ext = pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION);
                            $file = pathinfo($_FILES['upload_file']['name'], PATHINFO_FILENAME);

                            if (rename($uploadfile, $uploaddir . $file . "_" . date('dmYHis') . "." . $ext)) {
								
								
                                if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
									
									$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','1','File Already exists with the same name and new File upload success')");
									
                                    echo "<p style='text-align:center;'>Successfully uploaded.</p>";
                                } else {
                                    echo "<p style='text-align:center;'>Error to uploaded.</p>";
									
								$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`dept_name`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','0','Error in  File Upload')");

                                }
                            } else {
                                echo "<p style='text-align:center;'>Error in renaming file</p>";
								
						$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`dept_name`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','0','Error in  File Rename')");

                            }
                        } else {
                            if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
                                echo "<p style='text-align:center;'>Successfully uploaded.</p>";
								
							
							$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`dept_name`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','1','New File Upload success')");

                            } else {
                                echo "<p style='text-align:center;'>Error to uploaded.</p>";
								$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`dept_name`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','1','Error in  File Upload')");

                            }
                        }
					  } else {
        // If filename does not contain the expected prefix or suffix, display an error message
        echo "<p style='text-align:center;'>Error: Uploaded filename must contain '$expectedName' in prefix or suffix.</p>";
		
		$insert_log=mysqli_query($db,"insert into file_log_users (`user_id`,`inw_id`,`user_name`,`dept_id`,`dept_name`,`pub_id`,`file_path`,`file_name`,`log_status`,`remarks`) values ('".$_COOKIE['user_id']."','".$_REQUEST['aid']."','".$_COOKIE['user_name']."','".$app_res['department_id']."','".$pdept_res['dept_name']."','".$app_res['pub_id']."','".$base_path."','".$uploadfile."','0','Error: Uploaded filename must contain ".$expectedName." in prefix or suffix')");

    }
                    } else if (isset($cfolder) && $cfolder !== "") {
                        $folder = $path . $cfolder;
                    } else {
                        $folder = $base_path;
                    }
                    ?>
					<div class="formcont1heading mb-2" style="position: relative; left: -36px;"><p style="width: 100%" class="sw-pth"><?php
						$sw_parts = array_values(array_filter(explode('/', str_replace('\\', '/', $folder)), function ($x) { return $x !== ''; }));
						echo implode('<i class="sw-sep">/</i>', array_map('htmlspecialchars', $sw_parts)); ?></p></div>
					<div style="margin:-4px 0 8px;"><a href="<?php echo $_COOKIE['set_url'];?>/file_move_log.php?id=<?php echo (int)$_REQUEST['aid']; ?>" target="_blank" title="Which files were copied to the next department, and why any failed" style="font-size:12.5px;font-weight:600;color:#1b6ca8;text-decoration:none;"><i class="fa fa-exchange"></i> File movement log</a></div>

                    <?php //if ($base_path !== $folder) { ?>
					 
                        <div style="width:100%;border: none !important">
                            <div class="row">
                             <div class="col-12 col-md-6" style="border: none !important">
                                    <form method="post">
                                        <?php
                                        $narr = explode('/', $folder);
                                        array_pop($narr);
                                        array_pop($narr);
                                        $bpath = implode('/', $narr) . "/";
                                        if ($base_path !== $folder) {
                                            ?>
                                            <input style="width: 100%" type="hidden" name="backfolder" value="<?php echo  $bpath ?>"/>
                                            <button name="back" value="<?php echo  $bpath ?>" style="cursor: pointer;font-weight: bold;padding: 10px;background: #ff9a9a;border: 1px solid #ff9a9a;"><i class="fa fa-arrow-left"></i> Back</button>
                                        <?php } ?>
                                    </form>
                                </div>
                                <div class="col-12 col-md-6" style="width: 100%; display: flex; justify-content: end;border: none !important;" >
                                    <form method="post" enctype="multipart/form-data">
                                        <input style="width: 100%" type="hidden" name="currentfolder" value="<?php echo  $folder ?>"/>
                                        <input class="startworkuploadtype"  type="file" name="upload_file" required/>
                                        <button class="mt-2 mt-md-0" name="upload" value="upload" style="cursor: pointer;padding: 10px;background: #ff9a9a;font-weight: bold;border: 1px solid #ff9a9a;margin-left: 5px"><i class="fa fa-upload"></i> Upload</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php //} ?>
											
            <div class="main pb-5">
                <div class="bottom-content" style="overflow: scroll">
                    <form method="post" onsubmit="">
                        <input type="hidden" id="path" name="path" value="<?php echo  $folder ?>"/>
						<table class="table custtable  dataTable no-footer" id="example" style="border: none !important; background-color: white !important;padding-top: 10px !important">
						<thead>
                            <tr>
                                <th>Sno</th>
                                <th>Name</th>
                                <th>Date Modified</th>
                                <th>Type</th>
                                <th>Size</th>
                            </tr>
						</thead>
							 <tbody>
                            <?php
                            $all_files = is_dir($folder) ? array_diff(scandir($folder), array('..', '.')) : array();   /* v2: a missing folder must not stop the page */
                            if (sizeof($all_files) > 0) {
                                $sno = 1;
                                foreach ($all_files as $file) {
                                    if (is_dir($folder . $file)) {
                                        ?>
                                        <tr>
                                            <td><?php echo  $sno++ ?></td>
                                            <td><i class="fa fa-folder" style="    color: gold;font-size: 20px;"></i> <?php echo  $file ?> <button class="edit-btn" name="cfolder" value="<?php echo  $file . "/" ?>" style="cursor: pointer;">Open</button></td>
                                            <td><?php echo  date('Y.m.d h:i:s A', filemtime($folder . $file)) ?></td>
                                            <td>Folder</td>
                                            <td></td>

                                        </tr>
                                        <?php
                                    }
                                }
                                foreach ($all_files as $file) {
                                    if (!is_dir($folder . $file)) {
                                        ?>

                                        <tr>
                                            <td><?php echo  $sno++ ?></td>
                                            <td><i class="fa fa-file-text-o"></i> <?php echo  $file ?> <a href="<?php echo $_COOKIE['set_url'];?>/download.php?name=<?php echo  $folder . $file ?>" class="edit-btn"><i class="fa fa-download"></i> </a></td>
                                            <td><?php echo  date('Y.m.d h:i:s A', filemtime($folder . $file)) ?></td>
                                            <td><?php echo  filetype($folder . $file) ?></td>
                                            <td><?php echo  round((filesize($folder . $file) / 1024), 0, PHP_ROUND_HALF_UP) . " KB" ?></td>

                                        </tr>
                                        <?php
                                    }
                                }
                            } else {

                                echo is_dir($folder)
                                    ? "<tr><td colspan='5' style='text-align:center;'>This folder is empty!..</td></tr>"
                                    : "<tr><td colspan='5' style='text-align:center;color:#b83232;'>The server cannot see this folder: <code>".htmlspecialchars($folder)."</code></td></tr>";
                            }
                            ?>
							</tbody>
                        </table>
                </div>
        </div>
		</div>
			</div>
		</div>
		<?php 
			}
			?>
			
		<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>

			<div class="modal fade" id="exampleModal_specs" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="width:130% !important">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Journal Specifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup_spec"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>
						
     <!-- Footer Section -->
     <?php echo footer(); ?>
	 </div>



<!-- Footer Section -->
<?php echo scripts(); ?>	

<script>
document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");
    const errorBox = document.getElementById("email_error_box");
    const mainInput = document.getElementById("corresponding_mail");
    const coInput = document.getElementById("corresponding_other_author");

    // ✅ If inputs are not present, stop script safely
    if (!form || !mainInput || !coInput || !errorBox) {
        return;
    }

    function validateEmails(emailString) {
        const emailArray = emailString.split(",");
        const invalidEmails = [];
        const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

        emailArray.forEach(function (email) {
            const trimmed = email.trim();
            if (trimmed !== "" && !emailRegex.test(trimmed)) {
                invalidEmails.push(trimmed);
            }
        });

        return invalidEmails;
    }

    function showValidation() {
        errorBox.innerHTML = "";

        let invalidList = [];
        invalidList = invalidList.concat(validateEmails(mainInput.value));
        invalidList = invalidList.concat(validateEmails(coInput.value));

        if (invalidList.length > 0) {
            let message = "<strong>Invalid Email(s):</strong><br>";
            invalidList.forEach(function (mail) {
                message += "• " + mail + "<br>";
            });
            errorBox.innerHTML = message;
        }
    }

    // Run on page load
    showValidation();

    // Live validation
    mainInput.addEventListener("input", showValidation);
    coInput.addEventListener("input", showValidation);

    // Block submission if invalid
    form.addEventListener("submit", function (e) {
        showValidation();
        if (errorBox.innerHTML !== "") {
            e.preventDefault();
        }
    });

});
</script>
   
       <!-- Initialize DataTables -->
  <script>
        $(document).ready(function() {
           // $('#example').DataTable();
			
			// Correct: Ensure the selector matches the correct table

 $('#example').DataTable({
        "pageLength": 100
    });
    
console.log($('#example').DataTable().mData);


        });
		
		
    </script>		
    


    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>

<script type="text/javascript">

function RestrictFirstZero(e) {
          if (e.srcElement.value.length == 0 && e.which == 48) {
              e.preventDefault();
              return false;
          }
      };

      function PreventFirstZero(event) {
          if (event.srcElement.value.charAt(0) == '0') {
              event.srcElement.value = event.srcElement.value.slice(1);
          }
      };
    
    
    
jQuery(function($) {

$( "#3dtohtml" ).click(function() {
$(this).hide();
});
$( "#xmltohtml" ).click(function() {
$(this).hide();
});
$( "#opfxmltohtml" ).click(function() {
$(this).hide();
});
$( "#HTML_View" ).click(function() {
$(this).hide();
});
$( "#tsp_fp_package" ).click(function() {
$(this).hide();
});
$( "#eos_fp_package" ).click(function() {
$(this).hide();
});
$( "#acg_fp_package" ).click(function() {
$(this).hide();
});
$( "#htmlconversion" ).click(function() {
$(this).hide();
});


      $('#validation-form').validate({errorClass: 'help-block'});	
      $('#message').richText();//this will create the default editor will all buttons			
    })
    
    function timepopup(id)
    {				
      var url = "<?php echo $_COOKIE['set_url'];?>/project_comments.php?id="+id;
      $("#ppopup").load(url);
    }
	 function popup_spec(id)
    {				
      var url = "<?php echo $_COOKIE['set_url'];?>/journal_spec_details.php?id="+id;
      $("#ppopup_spec").load(url);
    }
    
    
$.validator.setDefaults({
  submitHandler: function(form) {
    var a_id = $("input[name='id']").val();
    var dept_name = $("input[name='dept_name']").val();
    var status = $( "#status option:selected" ).text();
    if(status == "")
    {
      // The "File Update Status" modal (file_upload_dropdown.php) uses status
      // radio buttons instead of the #status dropdown — value 1 = Completed.
      var checkedStatus = $("input[name='status']:checked").val();
      status = (checkedStatus == "1") ? "Completed" : "";
    }
    if(dept_name == "PAG" && status == "Completed")
    {
      $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/get_graphics_status.php',
        data:'a_id='+a_id,
        success:function(result){
          if(result == 1)
          {
            swSubmit(form);
          }
          else
          {
          
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> <strong> Warning! </strong>'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
            return false;
          }         
        }
      });
      return false;
    }
    else if(<?php echo $style_validator_required ? 'true' : 'false'; ?> && status == "Completed")
    {
      $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/journal_style_validator.php',
        data:'a_id='+a_id+'&action=check',
        success:function(result){
          if(result == 1)
          {
            swSubmit(form);
          }
          else
          {

            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> <strong> Warning! </strong>'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {

                  }
                }
              }
            });
            return false;
          }
        }
      });
      return false;
    }
    else
    {
      swSubmit(form);
    }
  }
});




  $(document).ready(function(){

  <?php if($customer_info['cust_name'] =="TSP" && $app_res['stage'] == "FP" && (($app_res['status'] == "PRQC" && ($journal_info['j_code'] =="CMC" || $journal_info['j_code'] =="CMES")) || $app_res['status'] == "Proofing")){
    $html_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".html"; 
    if (!file_exists($html_file)) 
  {
    
?>	
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
            $(".loader").fadeOut("slow");
          if(result.includes("completed"))
          {
            $(".loader").fadeOut("slow");
            $URL = $( "#HTML_View" ).attr( "href" );
        
           window.open($URL, '_blank');
          }							
          else
          {
            $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      }); 
    
  <?php } 
  else if (file_exists($html_file)) 
    {		?>
      $(".loader").fadeOut("slow");
      $URL = $( "#HTML_View" ).attr( "href" );
      window.open($URL, '_blank');
  <?php } 
  } ?>		
    ///////////// Get workflow from Journal //////////

$('#tsp_fp_package').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/tsp_fp_package.php',
        data:'id='+a_id,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});


$('#eos_fp_package').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/eos_fp_package.php',
        data:'id='+a_id,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});

$('#acg_fp_package').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  var stage = $("input[name='stage']").val();
  var work_dir = $("input[name='work_dir']").val();
  var pubid = $("input[name='pub_id']").val();

  $.ajax({
        type:'POST',
        url:'http://10.10.10.94/workflow/acg-zip-validation.php',
        data: 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir+ '&pubid=' + pubid,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});

var swPkgBusy = false;
function swRunPackage(url, kind, label)
{
  if (swPkgBusy) { return; }
  swPkgBusy = true;
  var a_id = $("input[name='id']").val(), stage = $("input[name='stage']").val(), work_dir = $("input[name='work_dir']").val(), pubid = $("input[name='pub_id']").val();
  var sent = 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir + '&pubid=' + pubid;
  var t0 = Date.now(), tick = null, poll = null, done = false, polling = false, lastStatus = 0, responded = false, base = window.__pkgBase || 0, lastErr = '';
  var $btns = $('#epl_package, #now_package, #final_zip_package, #sif_zip_package').css({ opacity: 0.55, 'pointer-events': 'none' });
  function mmss() { var s = Math.floor((Date.now() - t0) / 1000); return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function say() { $('#sw-title').text('Creating the package…'); $('#sw-overlay .sw-sub').text((polling ? 'Waiting for the package report – the connection to the package server ended.' : 'The package server is working – this can take a few minutes.') + ' Please keep this page open.  (' + mmss() + ')'); }
  if (window.swLoaderOn) { window.swLoaderOn('Creating the package…'); }
  say(); tick = setInterval(say, 1000);
  function finish(ok, text, outcome) {
    if (done) { return; }
    done = true; clearInterval(tick); clearInterval(poll); $('#sw-stop').remove();
    if (window.swLoaderOff) { window.swLoaderOff(); }
    swPkgBusy = false; $btns.css({ opacity: '', 'pointer-events': '' });
    $.ajax({ type: 'POST', url: location.href.split('#')[0], global: false, data: { sw_pkg_log: 1, kind: kind, result: ok ? 'completed' : 'failed', outcome: outcome || (ok ? 'completed' : 'failed'),
      http_status: lastStatus, responded: responded ? 1 : 0, ms: Date.now() - t0, server: url, args: sent, message: text } });
    var finals = (kind === 'TSPFIN' || kind === 'EOSFIN' || kind === 'SIF' || kind === 'PJREV' || kind === 'PJFP');
    var shown = ok ? (finals ? 'Package created successfully.' : label + ' completed') : String(text).slice(0, 1500);
    if (finals) { shown += ok ? '\n\nThe report of the package tool is shown below the buttons - close this message and scroll down to read it.' : '\n\nClose this message and read the report that is shown below the buttons (the Package result file) - it explains what went wrong. Then correct the files and press Package creation again.'; }
    bootbox.dialog({ message: '<br/><div class="alert alert-' + (ok ? 'success' : 'warning') + '"> ' + $('<div>').text(shown).html().replace(/\n/g, '<br/>') + '</div>',
      buttons: { "button": { "label": "Close", "className": "btn-sm", "callback": function () {
        /* take the user to the report section once the box is closed */
        if (finals && $('#result_qc').length) { setTimeout(function () { if ($('#result_qc').is(':visible')) { $('html,body').animate({ scrollTop: $('#result_qc').offset().top - 80 }, 350); } }, 150); }
      } } } });
    if (window.swRunCheck) { window.swRunCheck(); }
    /* what actually happened: the result page the tool wrote in the stage folder (<pub_id>_report.html / _error.html), shown below the buttons */
    $.post(location.pathname + location.search, { sw_pkg_error: 1, aid: a_id, latest: 1 }, function (html) {
      if ($.trim(html) !== '') { $('#result_qc').css('color', '#1f2d3d').show().html(html); }
      else { $('#result_qc').css('color', '#1f2d3d').show().html('<div style="padding:14px;">The package tool did not leave a result file (<code>' + $('<i>').text(pubid).html() + '_report.html</code> / <code>_error.html</code>) in the stage folder.</div>'); }
    });
  }
  /* the answer never came back (slow server, proxy time-out, dropped connection): the package may still be being built.
     PeerJ / EPL write <article>_report.html when they finish, so keep checking for it instead of reporting a failure. */
  function startPolling() {
    if (polling || done) { return; }
    polling = true;
    if (kind !== 'EPL') { finish(false, 'The package server did not give a usable answer, so the result is not known.' + (lastErr ? '\n\n' + lastErr : '') + '\n\nThe result file written by the tool (if any) is shown below the buttons. Press Package creation again if no package appears.', 'no_response'); return; }
    $('#sw-overlay').append('<a href="#" id="sw-stop" style="margin-top:18px;color:#1b6ca8;font-weight:600;">Stop waiting</a>');
    $('#sw-stop').on('click', function (e) { e.preventDefault(); finish(false, 'You stopped waiting for the package report. If the package finishes later, use Re-check.', 'no_response'); });
    var tries = 0;
    poll = setInterval(function () {
      if (++tries > 150) { finish(false, 'The package report did not appear within 20 minutes.', 'no_response'); return; }
      $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_check_files: 1 } }).done(function (r) {
        var p = r && r.package;
        if (p && p.done && (!base || (p.report_time || 0) > base)) { finish(true, 'completed (confirmed from the package report)'); }
      });
    }, 8000);
  }
  $.ajax({
    type: 'POST', url: url, timeout: 0, global: false, data: 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir + '&pubid=' + pubid,
    success: function (result) {
      if (done) { return; }
      responded = true; lastStatus = 200;
      result = $.trim(result);
      if (kind === 'TSPFIN' || kind === 'EOSFIN' || kind === 'SIF' || kind === 'PJREV' || kind === 'PJFP') {
        /* the finals scripts print "success" or "error"; when they print nothing the result file they wrote decides */
        var once = result, hl = result.length / 2;
        if (result.length % 2 === 0 && result.slice(0, hl) === result.slice(hl)) { once = result.slice(0, hl); }   /* "errorerror" -> "error" */
        if (/error|fail/i.test(result)) { finish(false, 'The package was not created - the package tool answered "' + once + '".', 'failed'); return; }
        if (/success|completed/i.test(result)) { finish(true, 'completed'); return; }
        $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_pkg_error: 1, aid: a_id, status: 1 } }).done(function (j) {
          var age = j && j.found ? (j.now - j.mtime) : 1e9, fresh = age <= Math.round((Date.now() - t0) / 1000) + 30;
          if (j && j.found && fresh && j.success) { finish(true, 'completed (confirmed from ' + j.file + ')'); }
          else if (j && j.found && fresh) { finish(false, 'The package tool reported a problem - see the result file below.', 'failed'); }
          else { finish(false, (result === '' ? 'The script answered with an empty response' : 'The script answered: ' + result) + ' and no new result file (<pub_id>_report.html / _error.html) was written, so the package was not confirmed.'); }
        }).fail(function () { finish(false, 'The script answered ' + (result === '' ? 'with an empty response' : result) + ', and the result file could not be read.'); });
        return;
      }
      if (result === '') { lastErr = 'HTTP 200 - the script answered with an empty response (after ' + Math.round((Date.now() - t0) / 1000) + 's)'; startPolling(); return; }
      /* the remote script echoes whatever the tool printed (last line twice), so test the end of the text, not equality */
      if (/completed\s*$/i.test(result)) { finish(true, 'completed'); return; }
      if (kind !== 'EPL') { finish(false, result); return; }
      /* EPL: the report file is the real proof of success */
      $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_check_files: 1 } }).done(function (r) {
        var pk = r && r.package;
        if (pk && pk.done && (!base || (pk.report_time || 0) > base)) { finish(true, 'completed'); } else { finish(false, result); }
      }).fail(function () { finish(false, result); });
    },
    error: function (xhr) {
      lastStatus = xhr && xhr.status ? xhr.status : 0;
      lastErr = 'HTTP ' + lastStatus + (xhr && xhr.statusText ? ' ' + xhr.statusText : '') + (xhr && $.trim(xhr.responseText || '') ? ' - server response: ' + $.trim(xhr.responseText).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 800) : ' - no response text') + ' (after ' + Math.round((Date.now() - t0) / 1000) + 's)';
      /* nothing answered at all and it failed straight away: the tool server cannot be reached */
      if (lastStatus === 0 && Date.now() - t0 < 60000) {
        var host = url.replace(/^https?:\/\//, '').split('/')[0];
        /* status 0 also happens when the server IS there but the answer carries no CORS header (script missing / 404): a no-cors request only fails when nothing is reachable */
        var reach = window.fetch ? fetch(url, { mode: 'no-cors', cache: 'no-store' }) : Promise.reject();
        reach.then(function () { finish(false, 'The package server (' + host + ') is connected, but the package script did not give a usable answer (the script may not be installed, or it is not allowing this page). Please tell IT with the time of this attempt.', 'failed'); },
                   function () { finish(false, 'Server not connected - the package server (' + host + ') could not be reached. Please check the network / VPN or tell IT, then try again.', 'no_connection'); });
        return;
      }
      if (lastStatus >= 400 && lastStatus < 500) { finish(false, 'The package server refused the request (HTTP ' + lastStatus + ').'); return; }
      startPolling();
    }
  });
}
$('#epl_package').on('click', function () { swRunPackage('http://10.10.10.94/workflow/eos-zip-validation.php', 'EPL', 'Package creation'); });
$('#sif_zip_package').on('click', function () { swRunPackage(<?php echo json_encode(sw_pkg_url($app_res['cust_id'], $app_res['stage'], $pdept_res['dept_name'])); ?>, 'SIF', 'Package creation'); });
$('#final_zip_package').on('click', function () { swRunPackage(<?php echo json_encode(sw_pkg_url($app_res['cust_id'], $app_res['stage'], $pdept_res['dept_name'])); ?>, <?php echo json_encode(sw_pkg_kind($app_res['cust_id'], $journal_info['j_code'], $app_res['stage'], $pdept_res['dept_name'], $app_res['department_id'])); ?>, 'Package creation'); });
$('#now_package').on('click', function () { swRunPackage('http://10.10.10.94/workflow/now-zip-validation.php', 'NOW', 'Package creation'); });
$('#testing_package').on('click',function()
{
  var pkgType = $(this).data('pkg-type'); // 'jstage' or 'pmc'
  var pkgUrl = (pkgType == 'pmc')
    ? 'http://10.10.10.94/workflow/iap-pmc-zip-validation.php'
    : 'http://10.10.10.94/workflow/iap-jstage-zip-validation.php';
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  var stage = $("input[name='stage']").val();
  var work_dir = $("input[name='work_dir']").val();
  var pubid = $("input[name='pub_id']").val();

  $.ajax({
        type:'POST',
        url: pkgUrl,
        timeout: 0, // no client-side timeout: the loader stays until the server answers
        data: 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir+ '&pubid=' + pubid,
        success:function(result){
          $(".loader").fadeOut("slow");
          var cls = (result == "completed") ? 'alert-success' : 'alert-warning';
          if (result != "completed") {
            // failed: show the validator's <pub_id>_error.html on the page itself, like the other reports
            $.post(location.pathname + location.search, { sw_pkg_error: 1, aid: a_id }, function (html) {
              if ($.trim(html) !== '') { $("#result_qc").show().html(html); $('html,body').animate({ scrollTop: $("#result_qc").offset().top - 80 }, 250); return; }
              bootbox.dialog({
                message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
                buttons: { "button" : { "label" : "Close", "className" : "btn-sm" } }
              });
            });
            return;
          }
          bootbox.dialog({
            message: '<br/><div class="alert alert-info">TESTING package - not for production delivery</div><div class="alert '+ cls +'"> '+ result +'</div>',
            buttons: { "button" : { "label" : "Close", "className" : "btn-sm" } }
          });
        }
      });
});
$('#htmlconversion').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  var stage = $("input[name='stage']").val();
  var work_dir = $("input[name='work_dir']").val();
  var pubid = $("input[name='pub_id']").val();
  $.ajax({
        type:'POST',
        url:'http://10.10.10.94/workflow/acg-html-conversion.php',
        data: 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir+ '&pubid=' + pubid,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});

$('#3dtohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
          $(".loader").fadeOut("slow");
          
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});
$('#xmltohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=2',
        success:function(result){
          
          $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});
$('#opfxmltohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  
  var a_id = $("input[name='id']").val();
  var stage = $("input[name='stage']").val();
  var work_dir = $("input[name='work_dir']").val();
  var pubid = $("input[name='pub_id']").val();
  $.ajax({
        type:'POST',
        url:'http://10.10.10.94/workflow/tsp-proofing-conversion.php',
        data: 'a_id=' + a_id + '&stage=' + stage + '&work_dir=' + work_dir+ '&pubid=' + pubid,
        success:function(result){
          
          $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});




  //$(document).ready(function(){	
    ///////////// Get workflow from Journal //////////

$(document).ready(function(){	
$('#status').on('change',function()
{
      var status = $(this).val();
      //alert(status);
  
  if(status==2 || status == 6 || status == 8 || status == 9)
  {
    
    $('#tmpa').show();
    $('#notmpa').hide();
  }
  else
  {
        
         $('#notmpa').show();
     $('#tmpa').hide();

      }
  
      if(status == 6)
  {
    $('#assign_department_div').show();
    //$('#assign_user').show();
  }
  else
  {
          $('#assign_department_div').hide();
    //$('#assign_user').hide();
          
      }
   if(status == 7)
  {
    $('#query_div').show();
  }
  else
  {
          $('#query_div').hide();
          
      } 
   if(status ==1)
  {
    $('#checklist_table').show();
  }
  else
  {
            $('#checklist_table').hide();
          
      }
  
  
  });
});
  

    $('#ePub_Creation').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
          $(".loader").fadeOut("slow");


           response = JSON.parse(result);
        
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }

                 
        }
      });  
});


    $('#ePub_Validaion').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=2',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);
          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
















            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }
                 
        }
      });  
});
      


























$('#rebuild_epub').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=3',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);

          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
                 
        }
      });  
});



    $('#ePub_to_html').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=4',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);
          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
                 
        }
      });  
});


})

  </script>
  <script type="text/javascript">
    $(document).ready(function(){
  $('#assign_department').on('change',function(){
      var dep_id = $('#assign_department').val();
  //alert(dep_id);
  if(dep_id==1 || dep_id==2)
  {
  $('#assign_user').show();	
  }
      if(dep_id==1 || dep_id==2){
    
          $.ajax({
              type:'POST',
              url:'<?php echo $_COOKIE['set_url'];?>/get_cepe_user.php',
              data:'dept_id='+dep_id,
              success:function(html){
                  $('#a_user_id').html(html);                    
              }
          }); 
      }else{
    $('#assign_user').hide();
          $('#a_user_id').html('<option value="">Select User first</option>');
          
      }
  });
    
    


});

    
  </script>
  
  <?php 
  
  
/** copy files
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
require_once __DIR__ . '/includes/file_move_log.php';   /* v2: file movement log (inw_file_move_log) */
function rcopy($src, $dest, $accept_files_list, $suffix, $customer_id, $pub_id)
{
    fml_rc_hit();
    if (!is_dir($src)) {
		fml_add('NOTE', 'FAILED', '', $src, $dest, 'The source folder does not exist or cannot be read, so nothing could be copied.');
		echo "<script>
		swal({
			title: 'Error',
			text: 'Source folder is not a directory',
			type: 'error',
			confirmButtonText: 'OK'
		});
		</script>";
		return false;
	}

    if (!is_dir($dest)) {
        error_clear_last();
        if (!@mkdir($dest, 0777, true)) {
            fml_add('NOTE', 'FAILED', '', $src, $dest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid'));
            return false;
        }
    }

    $dir = new DirectoryIterator($src);

    foreach ($dir as $file) {

        if ($file->isFile()) {

            $filename = $file->getFilename();
            $fileInfo = pathinfo($filename);

            $name = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? strtolower($fileInfo['extension']) : '';

            $fml_matched = false;
            foreach ($accept_files_list as $ext) {

                $ext = strtolower(trim($ext, '.'));

                if ($extension === $ext) {
                    $fml_matched = true;

                    // ✅ ONLY EXACT FILE FOR CUSTOMER 82
                    if ($customer_id == 82) {
                        $expected_filename = strtolower($pub_id . '.' . $extension);

                        if (strtolower($filename) !== $expected_filename) {
                            fml_add('FILE', 'SKIPPED', $filename, $file->getRealPath(), $dest, 'Not copied: for this customer only ' . $pub_id . '.' . $extension . ' is moved.', @filesize($file->getRealPath()));
                            continue; // skip unwanted files
                        }
                    }

                    $sfn = $file->getRealPath();

                    // Filename with suffix
                    if (!empty($suffix)) {
                        $new_filename = $name . "_" . strtolower($suffix) . "." . $extension;
                    } else {
                        $new_filename = $name . "." . $extension;
                    }

                    $dfn = rtrim($dest, "/") . "/" . $new_filename;

                    // 🚫 SKIP DUPLICATES FOR ALL (no counter, no overwrite)
                  /*   if (file_exists($dfn)) {
                        continue;
                    } */

                   /* v2: source and destination are the same file (the next department's folder is this one, or the file is already there):
                      nothing to copy. PHP's copy() returns false for that WITHOUT any error, which used to raise a false "Copy Failed". */
                   $rs = @realpath($sfn); $rd = @realpath($dfn);
                   if ($rs !== false && $rd !== false && strcasecmp(str_replace(chr(92), '/', $rs), str_replace(chr(92), '/', $rd)) === 0) { fml_add('FILE', 'SKIPPED', $filename, $sfn, $dfn, 'Already in the destination folder (the source and destination are the same file).', @filesize($sfn)); continue; }
                   /* v2: an existing destination file that is marked read-only cannot be overwritten - clear that flag first */
                   if (file_exists($dfn) && !is_writable($dfn)) { @chmod($dfn, 0666); clearstatcache(true, $dfn); }
                   error_clear_last();   /* so the reason below is the copy's own, not an older notice from the page */
                   if (!@copy($sfn, $dfn)) {
						/* v2: say WHY it failed (permission denied, file in use, folder missing ...) and where to */
						$why = error_get_last();
						$why = ($why && stripos($why['message'], 'copy(') === 0) ? preg_replace('/^copy\(.*?\): /', '', $why['message']) : 'the file could not be written - it may be open in another program or locked';
						fml_add('FILE', 'FAILED', $filename, $sfn, $dfn, $why, @filesize($sfn));
						$why = str_replace(array("'", "\r", "\n", '\\'), array('', ' ', ' ', '/'), $why);
						$to = str_replace(array("'", '\\'), array('', '/'), basename(dirname($dfn)));
						echo "<script>
						swal({
							title: 'Copy Failed',
							text: 'Error copying file: $filename to the $to folder ($why)',
							type: 'error',
							confirmButtonText: 'OK'
						});
						</script>";
					} else {
                        $dt = filemtime($sfn);
                        if ($dt !== false) {
                            touch($dfn, $dt);
                        }
                        fml_add('FILE', 'MOVED', $new_filename, $sfn, $dfn, '', @filesize($dfn));
                    }
                }
            }
            if (!$fml_matched) { fml_add('FILE', 'SKIPPED', $filename, $file->getRealPath(), $dest, 'Not copied: the file type .' . ($extension !== '' ? $extension : '(none)') . ' is not selected for this destination (selected: ' . implode(', ', array_map(function ($x) { return '.' . trim($x, ' .'); }, $accept_files_list)) . ').', @filesize($file->getRealPath())); }
        } 
        else if (!$file->isDot() && $file->isDir()) {
            rcopy($file->getRealPath(), $dest, $accept_files_list, $suffix, $customer_id, $pub_id);
        }
    }
}

function frcopy($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) { fml_add('NOTE', 'FAILED', '', $src, $dest, 'The source folder does not exist or cannot be read.'); return false; }

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        error_clear_last();
        if(!@mkdir($dest)) {
            // If the destination directory could not be created stop processing
            fml_add('NOTE', 'FAILED', '', $src, $dest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid'));
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            error_clear_last();
            if (@copy($f->getRealPath(), "$dest/" . $f->getFilename())) { fml_add('FILE', 'MOVED', $f->getFilename(), $f->getRealPath(), "$dest/" . $f->getFilename(), '', @filesize($f->getRealPath())); }
            else { fml_add('FILE', 'FAILED', $f->getFilename(), $f->getRealPath(), "$dest/" . $f->getFilename(), fml_why(), @filesize($f->getRealPath())); }
			///// Keep the same file time //////
			$dt = filemtime($f->getRealPath());
			if ($dt != FALSE)
			{ 
				touch("$dest/" . $f->getFilename(), $dt);
			}
						
        } else if(!$f->isDot() && $f->isDir()) {
            frcopy($f->getRealPath(), "$dest/$f");
        }
    }
}
  if(isset($_POST['action']) && $_POST['action']=='update')
{ echo '<!--SW_AJAX_START-->';
	/* the article record can hold the tool path (J:/JOURNAL/...) which Apache cannot see: use the E:/New_Workflow_Files copy for every server-side folder below */
	if (isset($_POST['work_dir'])) { $_POST['work_dir'] = wf_existing_dir($_POST['work_dir']); }
	sw3_enforce($db);   /* v3: refuse a Completed submit that was not confirmed in the pre-delivery popup */
	
	



	if($_REQUEST['cust_id']==76)
	{
	include("save_tme_data.php");
	}
	
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
		if($project_status_array[$post['status']]!="Others")
		{
		$project_status_array[$post['status']]=$project_status_array[$post['status']];
		$post['status']=$post['status'];
		}
		else
		{
		$project_status_array[$post['status']]=$project_status_others_array[$post['ostatus']];
		$post['status']=$post['ostatus'];		
		}
	
	$comments = $post['vcomments'] . $post['comments'];
	$escaped_comments = mysqli_real_escape_string($db, $comments); // $db is your mysqli connection	
	
	//// Get the article details ////
	$query = "SELECT `cust_id`,`j_id`,`activity` FROM `inw_inward_dtl` WHERE id = '".$post['id']."';";
	$articledetails = $db->query($query) or ($db->error);
	$artilce_info = $articledetails->fetch_assoc();
	
	//// Get the journal details ////
	$query = "SELECT `tvl_work_departments`,`j_proofing_system_server` FROM `adm_journals` WHERE j_id = '".$artilce_info['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	//// Here we will get the department's to work in TVL //
	$tvl_dept_info = explode(',',$journalinfo['tvl_work_departments']);	
	
	
	
		
		$query = "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '".$post['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code,j_hid FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();
		
		$Cn= $crow['cust_name'];
		$J_name = $jninfo['j_code']; 	

		/* ---- v2: package check BEFORE anything is saved ----------------------------------
		   start_work.php only looked at the package report at the very end, after the
		   transaction, article status, checklist and file moves were already written, and
		   then reported "Error Please check the package". The same test is made here first,
		   so a bad package stops the update with nothing saved. */
		$pc_stage   = preg_replace("/REV([0-9]+)/", "REV", $post['stage']);
		$pc_stage_f = preg_replace("/FIN([0-9]+)/", "FIN", $post['stage']);
		$pc_applies = ($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			&& (($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42')
				|| ($post['cust_id']!=66 && $pc_stage=='REV' && $post['dept_name']=='XML')
				|| ($post['cust_id']==66 && $pc_stage_f=='FIN' && $post['dept_name']=='ePub')
				|| ($post['cust_id']==42 && $pc_stage_f=='FIN' && $post['dept_name']=='XML'));
		/* DISABLED: the package report is written by the ZIP validator that start_work.php runs later in this same request, so
		   looking for it here (before the validator has run) rejected valid submits ("Error Please check the package").
		   The original order is back: the validator runs, the report is read, and the error (if any) is shown after the save. */
		$sw_early_package_check = false;
		if ($sw_early_package_check && $pc_applies && !($_POST['v_issue_id'] > 0) && $_POST['assign_department']=='' && $post['status']==1)
		{
			$pc_report = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['pub_id']."_report.html";
			$pc_data   = is_file($pc_report) ? file_get_contents($pc_report) : '';
			if (!preg_match('/<body class=\"SUCCESS\">/i', $pc_data))
			{
				echo "<script type='text/javascript'>swal({title: 'Error Please check the package', type: 'error', confirmButtonText: 'OK'});</script>";
				die();
			}
		}

		/* ---- v2: required files for the file movement, checked BEFORE anything is saved ---- */
		if ($post['status'] == 1 && $_POST['assign_department'] == '')   /* Completed only */
		{
			$rf = sw_required_files($db, $post['cust_id'], $post['j_id'], $post['department_id'], $post['stage'], $post['pub_id'],
			                        $_POST['work_dir'], $post['volume_folder'], $post['dept_name'], $J_name, $post['wf_id'], $post['id']);
			if ($rf['problems'])
			{
				echo "<script type='text/javascript'>swal({title: 'Required files are missing', text: '".addslashes(implode('\n', $rf['problems']))."', type: 'error', confirmButtonText: 'OK'});</script>";
				die();
			}
		}

		
		mysqli_query($db,"SET sql_mode = ''");
		
		
				$stage_issue=preg_replace("/ISS([0-9]+)/","ISS",$post['stage']);
				/* if($stage_issue!='ISSCOR')
				{

			    $pbkp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/productionbackup/backup_department_files.py";
				$dest_pbkp_dept_file = $_POST['work_dir'].$post['volume_folder'].$artilce_info['issueorgroup_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/"; // destination folder or file 
				$python_bkp_article_dept ='C:\\Windows\\py.exe "'.$pbkp_python_file.'" "'.$dest_pbkp_dept_file.'" "'.$post['id'].'" "'.$post['stage'].'" "'.$post['department_id'].'" "'.$post['tr_id'].'"';
				system($python_bkp_article_dept);
				} */
		
	//// Adding this script to add the Auto PDF page count using the Python script 
	/// Date : 25-11-2025 	
	/*if (($post['dept_name'] === "PRQC" || $post['dept_name'] === "PAG") && $post['status'] == 1)
	{
		$auto_pdf_page_count_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python-scripts/auto_pdf_page_count.py";
		
		$python_tf_article_appc ='C:\\Windows\\py.exe "'.$auto_pdf_page_count_python_file.'" '.$post['id'].'';
		$output = system($python_tf_article_appc);
	}
	 */
	 
	//// End PDF page count script ///
			
		if($post['dept_name']=="PRQC" && $post['cust_id']=="66" && $post['stage']!="FP")
		{
		$prj_update_querys=mysqli_query($db,"UPDATE  inw_meta_data SET  first_page ='".$post['fpage']."', last_page ='".$post['lpage']."' WHERE inw_id = ".$post['id']." ");
		}
		
		//if($post['status'] == 1)
	//{
		if (($post['dept_name'] == "PRQC" || $post['dept_name'] == "Proofing") &&    ($post['cust_id'] == "42" || $post['cust_id'] == "77" || $post['cust_id'] == "66") &&    $post['stage'] == "FP") {
			$update_article_title = '';
		/* 	if (!empty($post['article_title'])) {
				$update_article_title = ", article_title = '".$post['article_title']."'";
			} */

			$update_other_author = '';
			if (!empty($post['corresponding_other_author'])) {
				$update_other_author = ", corresponding_other_author = '".$post['corresponding_other_author']."'";
			}

			$prj_update_querys = mysqli_query($db, "
				UPDATE inw_inward_dtl SET
					corresponding_author = '".$post['corresponding_author']."',
					corresponding_mail = '".$post['corresponding_mail']."'
					".$update_other_author."
					".$update_article_title."
				WHERE id = ".$post['id']."
			");
		}
	//}
		
	if($post['status'] == 1 || $post['status'] == 6 )
	{
		
	
	if($post['status'] == 1)
	{
		// Ensure that both prqc_cats and reason arrays exist in POST
		if (isset($_POST['prqc_cats'])) 
		{
			$categories = $_POST['prqc_cats']; // Category IDs
			$reasons = $_POST['reason']; // Feedback reasons
			
			// Loop through each category and feedback
				foreach ($categories as $category_id) {
				$reason = 'None';
				if (isset($reasons[$category_id]) && !empty($reasons[$category_id])) {
					$reason = $reasons[$category_id];
				}
				// Insert the feedback into the fp_prqc_report table
				$sql_fp_prqc_report = "INSERT INTO fp_prqc_report (article_id, stage, category_id, reason) VALUES ('".$post['id']."', '".$post['stage']."', '".$category_id."', '".$reason."')";
				$db->query($sql_fp_prqc_report);
				
			}
		}
	}

	//$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Completed',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."' WHERE id = ".$post['tr_id']." ";	
			$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$escaped_comments."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."',`pages_completed`='".$post['page_count']."' WHERE id = ".$post['tr_id']." ";
			
			$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes) 
			VALUES ('".$post['id']."', 'Working User ".$_COOKIE['full_name']."', '". $post['dept_name']."', '".$project_status_array[$post['status']]."', '".$post['assign_department']."', '". $post['comments'] . $post['vcomments'] ."')";
			

	}
	else
	{
		
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$escaped_comments."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."',`pages_completed`='".$post['page_count']."' WHERE id = ".$post['tr_id']." ";


		$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes) 
			VALUES ('".$post['id']."', 'Working User ".$_COOKIE['full_name']."', '". $post['dept_name']."', '".$project_status_array[$post['status']]."', '".$post['assign_department']."', '". $escaped_comments."')";

		
		if($post['status'] == 7)
		{
			$user_jour=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$jninfo['j_hid']."'"));
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['user_id']."'"));

			
		
//echo "insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."')";exit;
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','Query')");
			
		}
	}
	
	$db->query($article_transactions_sql);

	if($db->query($update_query))
	{
		if($post['assign_department']=='')
		{
			if($post['status'] == 1 && $post['cust_id']==33)
			{
				if($post['department_id']==24)
				{
				include("pre-clean.php");
				}
			}
			if($post['status'] == 1 && $post['cust_id']==42)
			{
				if($post['department_id']==22)
				{
				include("pre-clean-tsp.php");
				}
			}
			
			if($post['status'] == 1 && $post['cust_id']==53)
			{
				$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$post['stage']);

				if($post['department_id']==5 && $stage_f='FIN')
				{
				$now_python_file = "E:/NOW-ZIP/NOW_Finals_ZIP.py";
				$dest_python_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
				/* NOW_Finals_ZIP.py is run by the "Package creation" button now (now-zip-validation.php on 10.10.10.94); the submit only checks that it completed */
				}
			}
			
			
			if($post['status'] == 1 && ($post['cust_id']==42 || $post['cust_id']==66) && $post['department_id']==1)
			{
				$ce_perl_file = ($post['cust_id']==66) ? "E:/Transforma/Word2HTML/Exe/METACONFIG1.pl" : "E:/Transforma/Word2HTML/Exe/METACONFIG.pl";
				$dest_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/CE/".$post['pub_id'].".xml";   // destination folder or file
				$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$ce_perl_file.'" "'.$dest_xml_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1"';
				system($perl_update_article);
			}
			//// Get the workflow Steps ////
			$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
			$steps = $db->query($query) or ($db->error);
			$deptinfo = $steps->fetch_assoc();
			$dept_info = explode(',',$deptinfo['wf_code']);

			//// Re-fetch the article's current department from the DB instead of trusting
			//// the posted hidden field, which can be stale on a resubmit/double-submit and
			//// cause the next-department lookup below to skip a department ////
			$current_dept_result = $db->query("SELECT department_id FROM inw_inward_dtl WHERE id = ".intval($post['id'])." ");
			$current_dept_row = $current_dept_result->fetch_assoc();
			$current_department_id = $current_dept_row['department_id'];

			$dept_key = array_keys($dept_info, $current_department_id);

			$dept_key = $dept_key['0'];
			$dept_key = ($dept_key + 1);
			
			if($post['skip_dept'] != "" && $post['skip_dept'] > 0)
			{
				$department_id = $post['skip_dept'];
			}			
			else if($post['status'] == 1 || $post['status'] == 12)
			{			
				$department_id = $dept_info[$dept_key];
			}
			else
			{
				$department_id = $post['department_id'];
			}
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name,accept_files FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}	
			
		
		$err = array();
		///// NCC Client Check List ////
		if($dept_res['dept_name'] == 'Delivery')
		{	
			$tex_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
			//include('ncc_check_list.php');
		}
		if(count($err) == 0)
		{
			/// We update the page count when PRQC Team enters the DATA ////
			$page_count = '';
			if($post['page_count'] > 0)
			{
				$page_count = "page_count ='".$post['page_count']."',";
				$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");

			} 
						
			$corresponding_author_details = "";
			
			if($post['dept_name'] == 'FXML' && $post['stage'] == "FP" )
			{
				$xml_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".xml";
				
				if(file_exists($xml_file_path))
				{
				$html = file_get_contents($xml_file_path);
				
				$html = preg_replace("/(\n|\r|\r\n)/i", " ", $html);
				//// Get the title from XML file ////
				$title = explode("</title-group>",$html);
				$title = preg_replace("/(.*)(<article-title>)(.*)(<\/article-title>)(.*)/i", "$3", $title[0]);
				//// Get the author details from XML file ////
				$corresp = str_replace("<bold>", "", $html);
				$corresp = str_replace("</bold>", "", $corresp);
				$corresp = preg_replace("/(.*)(<corresp (.+?)>)(.*)(<\/corresp>)(.*)/i", "$4", $corresp);
				$corresp = preg_replace("/(.+?)(:\s+)(.*)/i", "$3", $corresp);
				$corresp = preg_replace("/((\,)\s+)/i", "", $corresp);
				$corresp = preg_replace("/(Email\:)/i", "", $corresp);				

				$corresps = explode("; ",$corresp);
				
				
				
				$corresp_name = "";
				$corresp_mail = "";
				if(sizeof($corresps) > 0)
				{
				for($i = 0 ; $i < sizeof($corresps); $i++)
				{
						$corresps_au = $corresps[$i];
						$corresps_au_name = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$1", $corresps_au));
						$corresps_au_name = trim(preg_replace("/(.*)(\.)/i", "$1", $corresps_au_name));
						$corresps_au_mail = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$3", $corresps_au));
				
				
				if($corresp_name !="")
				{
					$corresp_name .= ", ".$corresps_au_name;
				}
				else
				{
					$corresp_name .= $corresps_au_name;
				}
				
				
				if($corresp_mail !="")
				{
					$corresp_mail .= ",".$corresps_au_mail;
				}
				else
				{
					$corresp_mail .= $corresps_au_mail;
				}
				}
				
				}
				$corresponding_author_details = "corresponding_author ='".$corresp_name."', corresponding_mail ='".$corresp_mail."', article_title='".$title."', ";
				
				}

			}	
			//}
			$prj_update_query="UPDATE inw_inward_dtl SET ".$corresponding_author_details ." ".$page_count." `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`assign_department_id` ='0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."', present_user_id = '0' WHERE id = ".$post['id']." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			/// If completed the work files we will move to other department ////
			if($post['status'] == 1  || $post['status'] == 12)
			{
			///// Insert the checklist data to DB //////////
			foreach($_POST['checklist'] as $cl_id=>$value)
			{
				$insert_query = "INSERT INTO `inw_to_checklist` (`inw_id`,dept_id,stage,`cl_id`, `submited_value`, `created_by`, `created_on`) VALUES ('". $post['id']."', '".$post['department_id']."', '".$post['stage']."','". $cl_id."','". $value."', '". $_COOKIE['user_id']."', CURRENT_TIMESTAMP);";	
				$db->query($insert_query) or die($db->error);
			}
			
				/// Move the Files From On Dept to another dept ////
				
					
	$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/";   // source folder or file
	



///// Get the Accept files from New Configuration based on Customer/Journal/Dept/Stage//////

 $fe_query = "SELECT mf_id FROM `inw_move_files` WHERE cust_id = '".$artilce_info['cust_id']."' AND j_id = '".$artilce_info['j_id']."' AND dept_id = '".$post['department_id']."';";
$fe_result = $db->query($fe_query) or ($db->error);
$fe_info = $fe_result->fetch_assoc();
fml_ctx($db, array('inw_id' => (int)$post['id'], 'pub_id' => $post['pub_id'], 'cust_id' => (int)$post['cust_id'], 'stage' => $post['stage'], 'from_dept' => $post['dept_name'], 'to_dept' => '', 'src' => $src, 'counts' => array()));
if (!$fe_info || empty($fe_info['mf_id'])) { fml_add('NOTE', 'SKIPPED', '', $src, '', 'No file movement is configured for this customer / journal / department, so no files were copied to the next department.'); }



//// According to New configuration we are moving the files /////
$cur_stage = preg_replace('/[0-9]+/', '', $post['stage']);
 $fef_query = "SELECT move_files,move_folders,dept_id FROM `inw_move_files_dept_wise` WHERE mf_id = '".$fe_info['mf_id']."' AND stage = '".$cur_stage."';";
$fef_result = $db->query($fef_query) or ($db->error);
if ($fe_info && !empty($fe_info['mf_id']) && $fef_result && $fef_result->num_rows == 0) { fml_add('NOTE', 'SKIPPED', '', $src, '', 'A file movement exists for this customer / journal / department, but it has no destination for stage ' . $cur_stage . ', so no files were copied to the next department.'); }
while($fef_info = $fef_result->fetch_assoc())
{
	/// Create the dept destination folder /////
	$dept_result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$fef_info['dept_id'].";");
	$dept_name = $dept_result ? $dept_result->fetch_assoc() : null;
	if (!$dept_name) { fml_ctx($db, array('to_dept' => '(unknown department ' . (int)$fef_info['dept_id'] . ')')); fml_add('NOTE', 'FAILED', '', $src, '', 'The configured destination is not a department that exists, so nothing was copied.'); continue; }   /* v2: a configured destination that is not a department - nothing sensible to copy to */
	fml_ctx($db, array('to_dept' => $dept_name['dept_name'], 'types' => fml_types($fef_info['move_files'], $fef_info['move_folders'])));
	if ($fef_info['move_files'] == '' && $fef_info['move_folders'] == '') { fml_add('NOTE', 'SKIPPED', '', $src, '', 'This destination has no file types or folders selected in the file movement configuration.'); }
	elseif (!is_dir($src)) { fml_add('NOTE', 'FAILED', '', $src, '', 'The source folder does not exist, so nothing could be copied.'); }
	
	$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
	//mkdir($dest, 0777, true);
	//echo $dest.'<br>';
	
		
	
	
			//zip directory check validation //
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
				$dest_err = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
				
				if($post['stage']=='FP')
				{
				 $stage_pos=1;	
				}
				else
				{
				 $stage_pos=2;		
				}
				$stage=preg_replace("/REV([0-9]+)/","REV",$post['stage']);
				$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$post['stage']);

				//echo $stage;exit;
				if($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42')
				{
				/* the ZIP validator is run by the "Package creation" button now (eos-zip-validation.php on 10.10.10.94); the submit only checks the package */
				}
				elseif($stage_f=='FIN' && $post['cust_id']==66 && $post['dept_name']=='ePub')
				{
				/* the ZIP validator is run by the "Package creation" button now (eos-zip-validation.php on 10.10.10.94); the submit only checks the package */
				}
				elseif($stage_f=='FIN' && $post['cust_id']==42 && $post['dept_name']=='XML')
				{
				/* the ZIP validator is run by the "Package creation" button now (eos-zip-validation.php on 10.10.10.94); the submit only checks the package */
				}
				elseif($stage=='REV' && $post['cust_id']!=66 && $post['dept_name']=='XML')
				{
				/* the ZIP validator is run by the "Package creation" button now (eos-zip-validation.php on 10.10.10.94); the submit only checks the package */
				}
			}
	
		//zip directory check validation //
		
		
	///// Moving Files ////////
	
	if($fef_info['move_files']!='')
	{
		

		if($post['cust_id']==82)
		{
			$dept_id = $fef_info['dept_id'];

			// Apply your rule
			if (in_array($dept_id, [32, 33])) {
				//$suffix = strtolower($dept_name['dept_name']);
				$suffix = '';
			}
			elseif (in_array($dept_id, [34, 36, 38])) {
				//$suffix = strtolower($post['stage']);
				$suffix = '';
			}
		}
		else
		{
				$suffix = '';
		}
		// else → no change


		$accept_files_list = explode("|",$fef_info["move_files"]);
		$fml_rc0 = fml_rc_count();
		if(count($accept_files_list)>0)
		{
			if(!is_dir($dest))
			{
				///echo $dest.'<br>';
				error_clear_last();
				if (!@mkdir($dest, 0777, true)) { fml_add('NOTE', 'FAILED', '', $src, $dest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid')); }
			}
			
					 if ($J_name != 'EPL' && $J_name != 'NCC' && $post['cust_id'] != 33)
					{
						rcopy($src, $dest, $accept_files_list, $suffix, $post['cust_id'], $post['pub_id']);
					}
					elseif ($J_name == 'EPL' || $J_name == 'NCC' || $post['cust_id'] == 33 || $post['cust_id'] == 66 || $post['cust_id'] == 42)
					{
						if ($post['stage'] != 'FP' && $post['dept_name'] != 'PRQC')
						{	
							rcopy($src, $dest, $accept_files_list, $suffix, $post['cust_id'], $post['pub_id']);
						}

						if ($stage != 'REV' && $post['dept_name'] != 'XML')
						{	
							rcopy($src, $dest, $accept_files_list, $suffix, $post['cust_id'], $post['pub_id']);
						}

						if ($post['stage'] != 'REV2' && $post['dept_name'] != 'ePub')
						{	
							rcopy($src, $dest, $accept_files_list, $suffix, $post['cust_id'], $post['pub_id']);
						}
					}
			
			 if (fml_rc_count() == $fml_rc0) { fml_add('NOTE', 'SKIPPED', '', $src, $dest, 'No file copy was attempted: Start Work has a special rule for this customer / journal that does not copy files for stage ' . $post['stage'] . ' from ' . $post['dept_name'] . ' (the files for these customers are handled by the ZIP / delivery step). This is not a missing file.'); }
			 elseif (is_dir($src) && fml_count('MOVED') + fml_count('SKIPPED') + fml_count('FAILED') == 0) { fml_add('NOTE', 'SKIPPED', '', $src, $dest, 'No file with the configured type(s) (' . implode(', ', array_map('trim', $accept_files_list)) . ') was found in the source folder.'); }
			 //// Copy the Files from production folder to another system ///////
			/* 
			$work_dir = $_POST['work_dir'];
			$work_dir = str_replace(WF_DIR,BACKUP_DIR,$work_dir);
			$Backup_dest = $work_dir.$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
			if (!(is_dir($Backup_dest))) 
			{
				if (!mkdir($Backup_dest, 0777, true)) 
				{
					die('Failed to create folders...$jn');
				}
			}	
			rcopy($src,$Backup_dest,$accept_files_list);  */
			
		 
		
		}
		
			//copying batch files to pag folder for automation process
		
	
		
	}
	
	///////// Moving Folders ///////
	if($fef_info['move_folders']!='')
	{
		$accept_folders_list = explode("|",$fef_info["move_folders"]);
		foreach($accept_folders_list as $folder)
		{
			
			if(is_dir($fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder))
			{
				$fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder; 		
				$fdest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/".$folder; 
				error_clear_last();
				if (!is_dir($fdest) && !@mkdir($fdest, 0777, true)) { fml_add('NOTE', 'FAILED', $folder, $fsrc, $fdest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid')); }
				if($J_name!='EPL' && $J_name!='NCC' && $post['cust_id']!=33)
				{
				frcopy($fsrc,$fdest);
				}
				elseif($J_name=='EPL'  || $J_name=='NCC' || $post['cust_id']==33)
				{
						if(($post['stage']!='FP' && $post['dept_name']!='PRQC') && ($stage!='REV' && $post['dept_name']!='XML'))
						{	
						frcopy($fsrc,$fdest);
						}
				}
			}
			
			
		}
	}
	
	
	
		//zip directory check validation //	
		$dest_errc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/Delivery/";   // destination folder or file 
		
		 $dests=$dest_errc.$post['pub_id'].".zip";//exit;
		 
		//echo $stage;echo "<br/>";exit;
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
	
			if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML'))
			{
				
				//echo "eos-fp-prqc";exit;

		 $html_read = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['pub_id']."_report.html";   // destination folder or file 
		 $refdata = is_file($html_read) ? file_get_contents($html_read) : '';   /* no report = package not built: a failed package, not a PHP warning */

			//echo $html_read;
		$success=preg_match('/<body class=\"SUCCESS\">/i', $refdata);
		//echo $success;exit;
			if($success==1)
			{
				//echo "1";exit;
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");

			}
			else
			{
					//echo $stage;echo "<br/>";
					//echo "2";
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET `status` ='".$post['dept_name']."',`department_id` ='". $post['department_id']."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
			//echo "UPDATE inw_transactions SET `status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."' WHERE id = ".$post['tr_id']." ";exit;
			$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");

			$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes) 
			VALUES ('".$post['id']."', 'Working User ".$_COOKIE['full_name']."', '". $post['dept_name']."', '".$project_status_array[$post['status']]."', '".$post['assign_department']."', '". $post['comments'] . $post['vcomments'] ."')";
			$db->query($article_transactions_sql);

			}
		  }
			}
		//  echo $stage;echo "<br/>";
		//  echo $post['dept_name'];exit;
		  
		  //zip directory check validation //
		    
			///// Artile Details /////		
			
	//exit;
	
			
}


if($post['cust_id']==42 && $post['status']==1 && $post['stage']='FP' && $post['department_id']==1 )
		{
			$ps2pdf='PS2PDF.bat';
			$xmltopdf='XML2PDF.bat';
			$dest_pag=$_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/FP/PAG/";
				if (!is_dir($dest_pag)) 
				{
					mkdir($dest_pag, 0777, true);
				}
		
			   //copy($ps2pdf, $dest_pag.$ps2pdf);
			   //copy($xmltopdf, $dest_pag.$xmltopdf);
		} 
		
			}


		} //// NCC Check List End here ////
		
////////////////////////////////////////
////////////////////////////////////////
		
		
		
			//FP
			
			
			//OTHER CUSTOMERS FOR XPERTTRACK PPMS //
			
			$ppms_current_stage=preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			//DISPATCH REV ARTICLES TO KSU VIA XPERTTRACK 
			if($ppms_current_stage == 'REV' && $post['xperttrack_sync']=='1' && $post['cust_id']=='72' && $post['status']==1 && $post['department_id']=='14')
			{
				
					if($_POST['stage']=='REV1')
					{
						//echo "email";exit;
							include("xpertrack_ksu_email_rev1.php");
					}
					elseif($_POST['stage']=='REV2')
					{
						//echo "email";exit;
							include("xpertrack_ksu_email_rev2.php");
					}
					else if($_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $_POST['stage'] != 'REV2')
					{	
						//echo "email";exit;
							include("xpertrack_ksu_email_rev2_above.php");
					} 
					
					
			//DISPATCH REV ARTICLES TO KSU VIA XPERTTRACK 
			}
			
			//DISPATCH FIN ARTICLES TO KSU VIA XPERTTRACK 
			if($ppms_current_stage == 'FIN' && $post['xperttrack_sync']=='1' && $post['cust_id']=='72' && $post['status']==1 && $post['department_id']=='5')
			{
			include("xpertrack_ksu_email_final.php");
		   //DISPATCH FIN ARTICLES TO KSU VIA XPERTTRACK 
			}
			
			//OTHER CUSTOMERS FOR XPERTTRACK PPMS //



			$tsp_current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			if($tsp_current_stage == 'FIN' && $post['cust_id']=='42' && $post['xperttrack_sync']=='0' && $post['status']==1 && $post['department_id']=='5')
			{
				if($post['j_division']=='2'){
					//include("tsp_ftp_fin_computer_division.php");
				}
				if($post['j_division']=='1'){
					//include("tsp_ftp_fin_regular_division.php");					
				}
			}
			
			
			
			//DISPATCH ARTICLES TO AUHTOR VIA XPERTTRACK 

			if($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='1')
			{
						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175')
				{
					///// REV1: Send REV1 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_regular_division.php");
						}

					}elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev2_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev2_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $_POST['stage'] != 'REV2' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_above_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_above_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("xpertrack_tsp_email_rev1_computer_division.php");
					}
				
				}
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev2_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("xpertrack_tsp_email_rev2_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV2 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $_POST['stage']!='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV3 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='REV2' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("xpertrack_tsp_email_rev1_above_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV3 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev1_regular_division.php");
				}
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev2_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			  
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 

			}
			elseif($tsp_current_stage == 'FIN' && $post['xperttrack_sync']=='1')
			{
						
				if($post['cust_id']=='42' && $post['status']==1 && $post['department_id']=='5')
				{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_final_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_final_regular_division.php");
						}
				}

					
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 
			}
			elseif($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='0')
			{
			//DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 

						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175' && $post['j_id']!='262')
				{
					///// REV2: Send REV2 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_email_rev1_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV2' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("tsp_ftp_rev_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_ftp_rev_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("tsp_ftp_rev_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("tsp_ftp_rev_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("tsp_ftp_rev_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("tsp_email_rev1_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			
			}
			
		/* //DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 
				if($post['department_id']==10 || $post['department_id']==14 || $post['department_id']==18)
				{
					if(($post['status'] == 1) && ($post['cust_id']==42 || $post['cust_id']==66 || $post['cust_id']==80 || $post['cust_id']==33))
					{
					$pagecount_perl_file = "E:/Transforma/Page_Count/page_count.pl";
					$dest_pdf_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
					$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$pagecount_perl_file.'" "'.$dest_pdf_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1" "'.$post['id'].'" "'.$post['stage'].'"';
					 system($perl_update_article);
					}
				} */
		
		}
		else
		{
			
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$post['assign_department'].";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			///// Create a assign department folder if not exists ////
			/// Added on 01-10-2019 //////
			$ass_dept_chk = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/";
			if(!is_dir($ass_dept_chk))
			{				
				mkdir($ass_dept_chk, 0777, true);				
			}
			//// END create assign folder /////	
				
			//$assign_dept_status = $dept_res['dept_name']." | Assigned | ".$post['dept_name'];
			$assign_dept_status = $dept_res['dept_name'];
			
			if($dept_res['dept_name'] == 'Delivery')
			{
				$assign_dept_status = $dept_res['dept_name'];
			}	
			
			//$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$dept_res['dept_name']." | Assigned | ".$post['dept_name']."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$assign_dept_status."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$db->query($prj_update_query);
		}
		
		
		
		
	
		
		if($post['status']==7)
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='Query',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
		//echo "UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW() WHERE id = ".$post['tr_id']." ";exit;
		$prj_update_querys_trans=mysqli_query($db,"UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW(),`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");

		$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes) 
			VALUES ('".$post['id']."', 'Working User ".$_COOKIE['full_name']."', '". $post['dept_name']."', 'Query', '".$post['assign_department']."', '".$escaped_comments."')";
			$db->query($article_transactions_sql);

		}
		//// adding the logic to update the status in inw_inward_details table to fetch data directly

$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$post['department_id']."'"));

if($post['status'] == 1)
{
	
	$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>';
	if($dept_res['dept_name'] == 'Delivery')
	{
		$user_working_status = '';
		$graphic_working_status = ", graphic_working_status = ''";
	}
}
else if($post['status'] == 6)
{ 

	if($post['assign_department']==9)
	{
		
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$_COOKIE['user_id']."'"));

				$graphic_working_status = ", graphic_working_status = '<span class='label label-sm label-success'>Graphics</span>'";
				
				$user_working_status = '<span class="label label-sm label-success">Graphics</span>|<span class="label label-sm label-warning">Assigned By</span> | <span class="label label-sm label-info">'.$assigned_user['full_name'].' ('.$current_user_deptname['dept_name'].')</span>';

	}
	else
	{
				$graphic_working_status = ", graphic_working_status = ''";

				if($post['assign_department']=='1')
				{
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$post['a_user_id']."'"));
				}
				else
				{
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$_COOKIE['user_id']."'"));
				}

	   // $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
				if($post['assign_department']=='1')
				{
				$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';
				}
				else
				{
				$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-warning">Assigned By</span> | <span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>';
				}
				
	}
}
else if($post['status'] == 7)
{
    $user_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
//$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";		
	if ($post['department_id'] == 33 && $post['cust_id'] == 82) {

    $prj_update_query = "
    UPDATE inw_inward_dtl 
    SET 
        user_working_status = '".$user_working_status."',
        editlatex_project_id = NULL,
        editlatex_token = NULL,
        last_transaction_id = ".$post['tr_id']." 
        ".$graphic_working_status."
    WHERE id = ".$post['id'];

} else {

    $prj_update_query = "
    UPDATE inw_inward_dtl 
    SET 
        user_working_status = '".$user_working_status."',
        last_transaction_id = ".$post['tr_id']." 
        ".$graphic_working_status."
    WHERE id = ".$post['id'];
}
$db->query($prj_update_query);
//// End logic Here 																						   
					
		if($post['cust_id']=='42' && $_POST['stage']=='FP' && $post['status']==1 && $post['department_id']=='18'){
			//echo "loop came";exit;
			include ('aws_xpertproofing_xpro_xperttrack_auto.php');
		}
		elseif($post['cust_id']=='57' && $_POST['stage']=='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_id']=='264'){
			//echo "loop came";exit;
			include ('xpertrack_auto_upload_iap.php');
		}
		elseif($post['cust_id']=='66' && $_POST['stage']=='FP' && $post['status']==1 && $post['department_id']=='18'){
			//echo "loop came";exit;
			include ('aws_eos_xpertproofing_xpro_auto.php');
		}
		elseif($post['cust_id']=='77' && $_POST['stage']=='FP' && $post['status']==1 && $post['department_id']=='18' && $post['xperttrack_sync']=='1'){
			//echo "loop came";exit;
			include ('aws_xpertproofing_xpro_xperttrack_acg_auto.php');
		}
		elseif ($post['cust_id'] == '82' &&    $post['status'] == 1 && ($ppms_current_stage == 'REV' || $ppms_current_stage == 'FP') &&    $post['department_id'] == '34')
		{
			include('ftp_edp_auto.php');
		}
		elseif ($post['cust_id'] == '77' && $post['status'] == 1 && $post['xperttrack_sync']=='1' && (($ppms_current_stage == 'REV' && $post['department_id'] == '14') ||($ppms_current_stage == 'FIN' && $post['department_id'] == '5')))
		{
			include('xperttrack_rev_final_acg_auto.php');
		}
		elseif($post['cust_id']=='80' && $_POST['stage']=='FP' && $post['status']==1 && $post['department_id']=='18'){
			
			include ('xpertproofing_xpro_xperttrack_blor.php');
		}			
			
				
			
if($post['department_id']==24 && $post['stage']=='FP' && $post['status']=='1')
{
// Check for existing entry
$check_sql = "SELECT COUNT(*) as count FROM wf_file_complexity_users WHERE inw_id = '".$post['id']."' AND stage = '".$post['stage']."' AND fcu_date = '".date('Y-m-d')."'";
$check_result = $db->query($check_sql);
$row = $check_result->fetch_assoc();

	if ($row['count'] == 0) {
	// Update complexity
	$update_query = "UPDATE inw_transactions SET file_complexity = '".$post['file_complexity']."' WHERE id = '".$post['tr_id']."'";
	$db->query($update_query);
	// Insert new entry
	$sql_dcu = "INSERT INTO wf_file_complexity_users(inw_id, transaction_id, user_id, stage, file_status, fcu_date)VALUES('".$post['id']."', '".$post['tr_id']."', '".$_COOKIE['user_id']."', '".$post['stage']."', '".$post['file_complexity']."', '".date('Y-m-d')."')";
	$db->query($sql_dcu);
	}
}
				
		///// Artile Details /////
		
			if($_POST['v_issue_id'] > 0)
			{
				
				//header("location:".$_COOKIE['set_url']."/show_issues.php?id=".$_POST['v_issue_id']."&msg=Successfully completed"); 
				
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." for Issue Article Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
			}
			else
			{
				if($_POST['assign_department']=='')
				{
					if($J_name=='EPL' ||  $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
					{
				if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML'))
				{				   

					if($success==1 && $post['status']==1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
					}
					elseif($success==1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>"; 
					}
					elseif($success!=1 && $post['status']==2)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
					}
					elseif($success!=1 && $post['status']==1)
					{
				    header("location:".$_COOKIE['set_url']."/startwork/".$post['id']."/new&msg=Error Please check the package&html"); 
					}
					elseif($success!=1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
	  
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
	
	
});";
 echo "</script>";
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
					}
				}
				else
				{
				echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
				}
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
					}
				}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Close the current tab
    window.close();
	 // Move to the nearest tab
    window.opener.focus();
    // Refresh the page
    window.opener.location.reload();
});";
 echo "</script>";
					}
			}		
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////
?>

<?php
//echo "SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['aid']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1";
/* the elapsed-time spans this fed are commented out in the markup, so the first-start lookup (a table scan) is skipped */
$startTime = time();
//$startTime = strtotime("2024-03-23 08:57:19"); // Example start time
?>
 <script>
        $(document).ready(function(){
            // Start time from PHP
            var startTime = <?php echo json_encode($startTime * 1000); ?>;
            
            // Function to update the time
            function updateTime() {
                // Current time
                var endTime = new Date().getTime();

                // Calculate the time difference (in seconds)
                var timeDiff = Math.floor((endTime - startTime) / 1000);

                // Calculate days, hours, minutes, and seconds
                var days = Math.floor(timeDiff / (3600 * 24));
                var hours = Math.floor((timeDiff % (3600 * 24)) / 3600);
                var minutes = Math.floor((timeDiff % 3600) / 60);
                var seconds = timeDiff % 60;

                // Update spans with days, hours, minutes, and seconds
                $(".gtime-days").text(formatTime(days) + " days").toggle(days > 0);
                $(".gtime-hours").text(formatTime(hours) + "");
                $(".gtime-min").text(formatTime(minutes) + "");
                $(".gtime-sec").text(formatTime(seconds) + "");
				
				
            }

            // Update time every second
            setInterval(function() {
                updateTime();
            }, 1000);

            // Initial update
            updateTime();
        });

        // Function to format time to add leading zeros if needed
        function formatTime(time) {
            return time < 10 ? "0" + time : time;
        }
		  // Add event listener to the submit button
   /* v2: Submit is no longer hidden on click - swSubmit() disables it while the request runs and re-enables it if anything fails */
	
	

// Assuming you are using jQuery for AJAX

$(document).ready(function () {
    $("#xmlValidationButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        // Show loader and apply slight opacity to the page
        $("#ajaxLoader").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/peerj_xml_validation.php", // URL of the PHP script
            data: { work_dir: work_dir, stage: stage, articleId: articleId, username:username, cust_id: cust_id },
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
        });
    });
});

$(document).ready(function () {
    /* PeerJ metadata HTML: run pj-meta-xml.php on 10.10.10.94, then show the generated <pub_id>-metadata.html in a popup */
    function pjMetaPopup(html) {
        var $p = $("#pjMetaPopup");
        if (!$p.length) {
            $p = $('<div id="pjMetaPopup" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.55);z-index:2147483000;">'
                + '<div style="position:absolute;top:4%;left:5%;width:90%;height:90%;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.4);display:flex;flex-direction:column;overflow:hidden;">'
                + '<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid #ddd;font-weight:600;font-size:16px;color:#14304a;">'
                + '<span>PeerJ Metadata HTML</span><span id="pjMetaClose" style="cursor:pointer;font-size:24px;line-height:1;color:#666;">&times;</span></div>'
                + '<iframe id="pjMetaFrame" style="flex:1;border:0;width:100%;"></iframe></div></div>').appendTo("body");
            $p.on("click", "#pjMetaClose", function () { $p.hide(); });
            $p.on("click", function (e) { if (e.target === this) { $p.hide(); } });
        }
        $("#pjMetaFrame").attr("srcdoc", '<style>body{margin:0;padding:16px 24px;box-sizing:border-box}table{width:100% !important;max-width:100% !important}</style>' + html);
        $p.show();
    }
    $("#metaHtmlButton").click(function () {
        var stage = $("#stage").val();
        var pubid = $("#pub_id").val();
        var work_dir = $("#work_dir").val();
        var a_id = $("input[name='id']").val();
        $("#ajaxLoader").show();
        $("#overlay").show();

        $.ajax({
            type: "GET",
            url: "http://10.10.10.94/workflow/pj-meta-xml.php",
            data: { stage: stage, pubid: pubid, work_dir: work_dir },
            success: function (response) {
                $.ajax({ url: location.href.split('#')[0], type: 'POST', cache: false, global: false, data: { sw_pj_meta: 1, aid: a_id } }).done(function (html) {
                    $("#ajaxLoader").hide();
                    $("#overlay").hide();
                    pjMetaPopup($.trim(html) !== '' ? html : '<div style="font:14px Segoe UI,Arial,sans-serif;padding:20px;color:#b00020;">Metadata HTML file not found. Server response: ' + $('<div>').text(response).html() + '</div>');
                }).fail(function () {
                    $("#ajaxLoader").hide();
                    $("#overlay").hide();
                    pjMetaPopup('<div style="font:14px Segoe UI,Arial,sans-serif;padding:20px;color:#b00020;">Could not load the metadata HTML file.</div>');
                });
            },
            error: function (xhr, status, error) {
                $("#ajaxLoader").hide();
                $("#overlay").hide();
                pjMetaPopup('<div style="font:14px Segoe UI,Arial,sans-serif;padding:20px;color:#b00020;">Error: ' + $('<div>').text(error).html() + '</div>');
            }
        });
    });
});

$(document).ready(function () {
    $("#QCValidationButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        // Show loader and apply slight opacity to the page
        $("#ajaxLoader_QC").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/peerj_qc_validation.php", // URL of the PHP script
            data: { work_dir: work_dir, stage: stage, articleId: articleId, username:username, cust_id: cust_id },
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_QC").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_QC").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
        });
    });
});

$(document).ready(function () {
    $("#styleValidatorButton").click(function () {
        var a_id = $("input[name='id']").val();
        // Show loader and apply slight opacity to the page
        $("#ajaxLoader_SV").show();
        $("#overlay").show();

        $.ajax({
            type: "POST",
            url: "<?php echo $_COOKIE['set_url'];?>/journal_style_validator.php",
            data: { a_id: a_id, action: "run" },
            success: function (response) {
                $("#ajaxLoader_SV").hide();
                $("#overlay").hide();

                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                $("#ajaxLoader_SV").hide();
                $("#overlay").hide();

                $("#result_qc").html("Error: " + error);
            }
        });
    });
});

$(document).ready(function () {
    $("#bypassValidatorButton").click(function () {
        var a_id = $("input[name='id']").val();
        bootbox.prompt({
            title: "Bypass Journal Style Validator — please enter a reason",
            callback: function (reason) {
                if (reason === null) { return; } // user cancelled
                reason = $.trim(reason);
                if (reason === "") {
                    bootbox.alert("A reason is required to bypass the Journal Style Validator.");
                    return;
                }

                $("#ajaxLoader_SV").show();
                $("#overlay").show();

                $.ajax({
                    type: "POST",
                    url: "<?php echo $_COOKIE['set_url'];?>/journal_style_validator.php",
                    data: { a_id: a_id, action: "bypass", reason: reason },
                    success: function (response) {
                        $("#ajaxLoader_SV").hide();
                        $("#overlay").hide();

                        $("#result_qc").show();
                        $("#result_qc").html(response);
                    },
                    error: function (xhr, status, error) {
                        $("#ajaxLoader_SV").hide();
                        $("#overlay").hide();

                        $("#result_qc").html("Error: " + error);
                    }
                });
            }
        });
    });
});

$(document).ready(function () {
    $("#xmlCValidationButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        // Show loader and apply slight opacity to the pageX
        $("#ajaxLoader").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/tsp_xml_conversion_validation.php", // URL of the PHP script
            data: { work_dir: work_dir, articleId: articleId,stage: stage, username: username, cust_id: cust_id},
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
        });
    });
});

$(document).ready(function () {
    $("#ePub_validation_Creation").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        // Show loader and apply slight opacity to the pageX
        $("#ajaxLoader_e").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/eos_epub_validation_creation.php", // URL of the PHP script
            data: { work_dir: work_dir, articleId: articleId,stage: stage, username: username, cust_id: cust_id},
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_e").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_e").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
        });
    });
});

$(document).ready(function () {
    $("#htmlConversionButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        // Show loader and apply slight opacity to the pageX
        $("#ajaxLoader_html").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/tex-pre-clean-all-customers.php", // URL of the PHP script
            data: { work_dir: work_dir, articleId: articleId,stage: stage, username: username, cust_id: cust_id},
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_html").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_html").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
    
 });
    });
	
	 $("#algorithmConversionButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        // Show loader and apply slight opacity to the pageX
        $("#ajaxLoader_algorithm").show();
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/algorithm-all-customers.php", // URL of the PHP script
            data: { work_dir: work_dir, articleId: articleId,stage: stage, username: username, cust_id: cust_id},
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_algorithm").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_algorithm").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
    
 });
    });
});
$(document).ready(function () {
    $("#TexprecleanConversionButton").click(function () {
        var stage = $("#stage").val(); // Get stage value from input field
        var cust_id = $("#cust_id").val(); // Get article ID from input field
        var articleId = $("#pub_id").val(); // Get article ID from input field
        var work_dir = $("#work_dir").val(); // Get article ID from input field
        var username = "<?php echo $_COOKIE['user_name'];?>"; // Get article ID from input field
        // Show loader and apply slight opacity to the pageX
        $("#ajaxLoader_tp").show();
		
        $("#overlay").show();

        $.ajax({
            type: "POST", // Use POST for security
            url: "http://10.10.10.94/workflow/tex-pre-clean-all-customers.php", // URL of the PHP script
            data: { work_dir: work_dir, articleId: articleId,stage: stage, username: username, cust_id: cust_id},
            success: function (response) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_tp").hide();
                $("#overlay").hide();

                // Handle the response from the server
                $("#result_qc").show();
                $("#result_qc").html(response);
            },
            error: function (xhr, status, error) {
                // Hide loader and remove opacity overlay
                $("#ajaxLoader_tp").hide();
                $("#overlay").hide();

                // Handle errors
                $("#result_qc").html("Error: " + error);
            }
    
 });
    });


$("#Author_Validaion").click(function () {
    var stage = $("#stage").val(); // Get stage value from input field
    var articleId = $("#pub_id").val(); // Get article ID from input field
    var username = "<?php echo $_COOKIE['user_name'];?>"; // Get username
    var work_dir = $("#work_dir").val(); 
    var cust_id = $("#cust_id").val(); 

    // Show loader and apply slight opacity to the page
    $("#ajaxLoader_AV").show();
    $("#overlay").show();

    $.ajax({
        type: "POST", 
        url: "http://10.10.10.94/workflow/author_email_validation.php",
        data: { work_dir: work_dir, stage: stage, articleId: articleId, username: username, cust_id: cust_id },
        success: function (response) {
            // Hide loader and remove opacity overlay
            $("#ajaxLoader_AV").hide();
            $("#overlay").hide();

            // Handle the response from the server
            $("#result_av").show();
            $("#result_av").html(response);

            // ✅ Reload the page after successful fetch
            setTimeout(function () {
                location.reload();
            }, 1500); // reload after 1.5 sec (so user sees the response)
        },
        error: function (xhr, status, error) {
            $("#ajaxLoader_AV").hide();
            $("#overlay").hide();
            $("#result_av").html("Error: " + error);
        }
    });
});

});
    </script>
	
  
<style id="sw3-style">
/* delivery check popup (same look as the Comments/Instructions + Dispatch popups) */
.sw3-ovl{position:fixed;inset:0;background:rgba(15,28,45,.55);z-index:100000;display:flex;align-items:center;justify-content:center;padding:18px;}
.sw3-box{background:#fff;border-radius:18px;width:min(860px,100%);max-height:calc(100vh - 36px);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 70px rgba(15,28,45,.45);}
.sw3-hd{background:linear-gradient(90deg,#e8863a,#de7a2a);color:#fff;padding:16px 24px;font-weight:700;font-size:17px;}
.sw3-bd{background:#f3f6fb;padding:18px 24px;overflow:auto;flex:1;}
.sw3-ft{background:#fff;padding:14px 24px;text-align:right;display:flex;justify-content:flex-end;gap:10px;}
.sw3-ghost{background:#fff;color:#1d3557;border:1px solid #cfd8e6;border-radius:10px;padding:9px 18px;font-weight:600;cursor:pointer;}
.sw3-go{background:#2e8b57;color:#fff;border:0;border-radius:10px;padding:10px 26px;font-weight:700;cursor:pointer;} .sw3-go[disabled]{opacity:.45;cursor:not-allowed;}
.sw3-hero{background:linear-gradient(120deg,#1b3050,#2c5f9e);color:#fff;border-radius:16px;padding:16px 20px;margin-bottom:6px;text-align:left;}
.sw3-k{font-size:11px;letter-spacing:.08em;opacity:.8;} .sw3-title{font-size:22px;font-weight:700;line-height:1.25;word-break:break-all;} .sw3-sub{font-size:13px;opacity:.92;}
.sw3-pills{margin-top:10px;} .sw3-pill{display:inline-block;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:12px;padding:5px 12px;margin:0 8px 4px 0;font-size:12px;}
.sw3-pill.ok{background:rgba(46,139,87,.55);} .sw3-pill.warn{background:rgba(214,137,16,.6);} .sw3-pill.bad{background:rgba(192,57,43,.7);}
.sw3-sec{margin:18px 0 8px;font-size:12px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#1d3557;text-align:left;} .sw3-sec .fa{margin-right:4px;color:#2c5f9e;}
.sw3-count{display:inline-block;min-width:20px;text-align:center;background:#dfe8f5;color:#2c5f9e;border-radius:10px;padding:0 7px;font-size:11px;margin-left:4px;}
.sw3-card{background:#fff;border-left:4px solid #9fb3c9;border-radius:12px;padding:11px 14px;margin-bottom:9px;box-shadow:0 1px 4px rgba(20,35,60,.1);text-align:left;font-size:13px;color:#223;}
.sw3-card.ok{border-left-color:#2e8b57;} .sw3-card.warn{border-left-color:#e0a21b;color:#8a6200;} .sw3-card.bad{border-left-color:#c0392b;} .sw3-card.slim{padding:8px 14px;}
.sw3-card.ok .fa-check-circle{color:#2e8b57;} .sw3-card.bad .fa-times-circle,.sw3-card.bad .fa-ban{color:#c0392b;} .sw3-card.warn .fa{color:#e0a21b;}
.sw3-row{display:flex;gap:12px;align-items:flex-start;} .sw3-ico{flex:0 0 36px;height:36px;border-radius:10px;background:#eaf0f9;color:#2c5f9e;display:flex;align-items:center;justify-content:center;font-size:17px;}
.sw3-card.bad .sw3-ico{background:#fbe9e7;color:#c0392b;} .sw3-t{flex:1;min-width:0;} .sw3-t b{word-break:break-all;}
.sw3-tag{display:inline-block;margin-left:6px;padding:0 9px;border-radius:10px;background:#2e8b57;color:#fff;font-size:10.5px;font-weight:700;vertical-align:middle;} .sw3-tag.grey{background:#9aa6b8;}
.sw3-row2{display:flex;align-items:center;gap:8px;flex-wrap:wrap;} .sw3-r{margin-left:auto;color:#5b6b7b;font-size:12px;} .sw3-tag.amberbg{background:#d68910;}
.sw3-meta{margin-top:2px;color:#667;} .sw3-meta span{display:inline-block;margin:0 14px 2px 0;} .sw3-meta .fa{margin-right:4px;color:#8a97ab;} .sw3-meta .amber{color:#c98a0a;}
.sw3-bad{color:#c0392b;margin-top:2px;} .sw3-muted{color:#8a97ab;}
.sw3-zip{margin:-4px 0 9px 4px;max-height:200px;overflow:auto;background:#fff;border:1px solid #e3e9f3;border-top:0;border-radius:0 0 12px 12px;font-size:12px;}
.sw3-zip div{display:flex;justify-content:space-between;gap:10px;padding:4px 12px;border-top:1px solid #f0f3f8;} .sw3-zip div:first-child{border-top:0;} .sw3-zip span{word-break:break-all;} .sw3-zip em{font-style:normal;color:#8a97ab;white-space:nowrap;}
.sw3-review{display:block;margin:14px 0 4px;font-weight:700;color:#1d3557;background:#fff;border-radius:12px;padding:11px 14px;box-shadow:0 1px 4px rgba(20,35,60,.1);cursor:pointer;} .sw3-review input{margin-right:8px;}
.sw3-foot{margin-top:10px;color:#8a97ab;font-size:12px;text-align:left;}
</style>
<!-- ===== start_work_v2: UI polish, loader, ajax submit, sweetalert errors ===== -->
<style>
  .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;}
  .addarticleforminner{background:#fff !important;border:1px solid #e4e9ef !important;border-radius:14px !important;
    box-shadow:0 4px 18px rgba(20,48,74,.07) !important;width:100% !important;max-width:1360px;margin:18px auto !important;padding:22px 26px !important;}
  #validation-form label{font-weight:600;color:#33475b;}
  #validation-form .form-control,#validation-form select,#validation-form textarea,#validation-form input[type=text],#validation-form input[type=number]{
    border:1px solid #cfd8e3;border-radius:8px;transition:border-color .2s,box-shadow .2s;}
  #validation-form .form-control:focus,#validation-form select:focus,#validation-form textarea:focus,#validation-form input:focus{
    border-color:#1b6ca8 !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;outline:0;}
  #validation-form .help-block{color:#d64545;font-size:12px;margin-top:4px;}
  #validation-form .error,#validation-form input.error,#validation-form select.error,#validation-form textarea.error{border-color:#d64545 !important;}
  #submit-btn{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;border:0 !important;border-radius:10px !important;
    padding:11px 38px !important;font-weight:700;box-shadow:0 6px 14px rgba(20,48,74,.25);transition:transform .15s,box-shadow .15s,opacity .2s;}
  #submit-btn:hover{transform:translateY(-1px);box-shadow:0 9px 18px rgba(20,48,74,.3);}
  #submit-btn:disabled{opacity:.6;cursor:wait;transform:none;}
  #cancel-btn{background:#fff !important;color:#33475b !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;
    padding:11px 38px !important;font-weight:600;transition:background .2s;}
  #cancel-btn:hover{background:#f1f5f9 !important;}

  #sw-overlay{position:fixed;inset:0;z-index:99998;display:none;align-items:center;justify-content:center;flex-direction:column;
    background:rgba(255,255,255,.82);backdrop-filter:blur(2px);}
  #sw-overlay.on{display:flex;animation:swFade .2s ease;}
  #sw-overlay .sw-ring{width:60px;height:60px;border-radius:50%;border:5px solid #e9eef4;border-top-color:#e8742a;border-right-color:#1b6ca8;
    animation:swSpin .85s linear infinite;margin-bottom:16px;}
  #sw-overlay .sw-title{font-size:17px;font-weight:700;color:#14304a;}
  #sw-overlay .sw-sub{color:#6b7785;margin-top:3px;}
  #sw-bar{position:fixed;top:0;left:0;height:4px;width:0;z-index:99999;opacity:0;background:linear-gradient(90deg,#e8742a,#f5a35c 60%,#1b6ca8);
    box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;}
  #sw-bar.on{opacity:1;}
  @keyframes swSpin{to{transform:rotate(360deg);}} @keyframes swFade{from{opacity:0;}to{opacity:1;}}
  /* ---- alert dialogs (SweetAlert) : one professional look for every message on this page ---- */
  .swal2-container{background:rgba(15,32,52,.5) !important;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);}
  .swal2-popup,.swal2-popup *{font-family:'Open Sans','Segoe UI',Arial,sans-serif !important;}
  .swal2-popup .fa{font-family:FontAwesome !important;}
  .swal2-popup{--ac:#e8932a;--acbg:#fff3e0;--acring:rgba(232,147,42,.18);width:460px !important;max-width:92vw !important;padding:0 0 24px !important;border-radius:18px !important;overflow:hidden !important;position:relative;box-shadow:0 28px 70px rgba(15,35,60,.38),0 2px 6px rgba(15,35,60,.12) !important;}
  .swal2-popup:has(.swal2-icon.swal2-success:not([style*="display: none"])){--ac:#1e9e61;--acbg:#e5f6ed;--acring:rgba(30,158,97,.18);}
  .swal2-popup:has(.swal2-icon.swal2-error:not([style*="display: none"])){--ac:#d64545;--acbg:#fdeaea;--acring:rgba(214,69,69,.18);}
  .swal2-popup:has(.swal2-icon.swal2-info:not([style*="display: none"])),.swal2-popup:has(.swal2-icon.swal2-question:not([style*="display: none"])){--ac:#1b6ca8;--acbg:#e6f1fa;--acring:rgba(27,108,168,.18);}
  .swal2-popup.swal2-show{animation:swPop .5s cubic-bezier(.2,1.25,.3,1) backwards !important;}
  .swal2-popup.swal2-hide{animation:swOut .2s ease forwards !important;}
  @keyframes swPop{0%{opacity:0;transform:translateY(18px) scale(.9);}100%{opacity:1;transform:none;}}
  @keyframes swOut{to{opacity:0;transform:translateY(8px) scale(.96);}}
  .swal2-popup:before{content:'';display:block;height:5px;background:linear-gradient(90deg,var(--ac),color-mix(in srgb,var(--ac) 55%,#fff));}
  .swal2-icon{display:none !important;}
  .swal2-header{padding:26px 30px 0 !important;}
  .swal2-header:before{content:'!';display:flex;align-items:center;justify-content:center;width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:var(--acbg);color:var(--ac);font:700 32px/1 'Open Sans',Arial,sans-serif;box-shadow:0 0 0 0 var(--acring);animation:swIcon .55s cubic-bezier(.2,1.4,.3,1) .1s backwards,swRing 2.2s ease-out .7s infinite;}
  .swal2-popup:has(.swal2-icon.swal2-success:not([style*="display: none"])) .swal2-header:before{content:'\2713';}
  .swal2-popup:has(.swal2-icon.swal2-error:not([style*="display: none"])) .swal2-header:before{content:'\2715';}
  .swal2-popup:has(.swal2-icon.swal2-info:not([style*="display: none"])) .swal2-header:before{content:'i';font-style:italic;}
  .swal2-popup:has(.swal2-icon.swal2-question:not([style*="display: none"])) .swal2-header:before{content:'?';}
  @keyframes swIcon{0%{transform:scale(0) rotate(-40deg);opacity:0;}100%{transform:none;opacity:1;}}
  @keyframes swRing{0%{box-shadow:0 0 0 0 var(--acring);}70%{box-shadow:0 0 0 14px rgba(255,255,255,0);}100%{box-shadow:0 0 0 0 rgba(255,255,255,0);}}
  .swal2-popup .swal2-title{font-size:21px !important;line-height:1.3 !important;font-weight:700 !important;color:#1c3550 !important;padding:0 !important;margin:0 !important;animation:swUp .4s ease .15s backwards;}
  .swal2-popup .swal2-content,.swal2-popup #swal2-content{font-size:14px !important;line-height:1.55 !important;color:#51606f !important;padding:0 30px !important;margin-top:10px !important;animation:swUp .4s ease .22s backwards;}
  @keyframes swUp{0%{opacity:0;transform:translateY(8px);}100%{opacity:1;transform:none;}}
  .sw-msg{text-align:center;white-space:pre-wrap;max-height:260px;overflow:auto;}
  .sw-list{list-style:none;margin:6px 0 0;padding:0;text-align:left;max-height:260px;overflow:auto;}
  .sw-list li{position:relative;padding:11px 14px 11px 40px;margin:0 0 8px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid var(--ac);border-radius:10px;font-size:13.5px;line-height:1.45;color:#2f4357;animation:swItem .4s cubic-bezier(.2,.9,.3,1) backwards;}
  .sw-list li:nth-child(1){animation-delay:.28s;} .sw-list li:nth-child(2){animation-delay:.36s;} .sw-list li:nth-child(3){animation-delay:.44s;} .sw-list li:nth-child(n+4){animation-delay:.5s;}
  @keyframes swItem{0%{opacity:0;transform:translateX(-14px);}100%{opacity:1;transform:none;}}
  .sw-list li:before{content:'!';position:absolute;left:12px;top:50%;margin-top:-10px;width:20px;height:20px;border-radius:50%;background:var(--acbg);color:var(--ac);font:700 12px/20px 'Open Sans',Arial,sans-serif;text-align:center;}
  .swal2-popup .swal2-actions{margin:22px 0 0 !important;gap:10px;animation:swUp .4s ease .4s backwards;}
  .swal2-popup .swal2-styled{border-radius:10px !important;padding:10px 32px !important;font-size:14px !important;font-weight:600 !important;box-shadow:0 4px 12px rgba(27,108,168,.28) !important;transition:transform .15s ease,box-shadow .15s ease,filter .15s ease !important;margin:0 !important;}
  .swal2-popup .swal2-styled:hover{transform:translateY(-1px);filter:brightness(1.06);box-shadow:0 7px 16px rgba(27,108,168,.34) !important;}
  .swal2-popup .swal2-styled:active{transform:translateY(0) scale(.98);}
  .swal2-popup .swal2-cancel{background:#eef2f6 !important;color:#4b5b6b !important;box-shadow:none !important;}
  .sw-files{width:min(760px,calc(100% - 36px));margin:22px auto 6px;padding:18px 24px;border-radius:14px;border:1px solid;border-left-width:7px;
    box-shadow:0 8px 24px rgba(20,48,74,.12);font-size:15px;animation:swFilesIn .4s ease;}
  .sw-files.ok{background:#effaf4;border-color:#bfe5cf;border-left-color:#1e9e61;}
  .sw-files.bad{background:#fff3f3;border-color:#f3c4c4;border-left-color:#d64545;}
  .sw-files.warn{background:#fffaf0;border-color:#f3dfae;border-left-color:#d98a06;}
  .sw-mf-link{display:inline-flex;align-items:center;gap:7px;margin-top:9px;padding:7px 16px;border-radius:9px;background:#d98a06;color:#fff !important;font-weight:700;font-size:13px;text-decoration:none !important;box-shadow:0 4px 10px rgba(217,138,6,.3);transition:filter .15s,transform .15s;}
  .sw-mf-link:hover{filter:brightness(1.08);transform:translateY(-1px);}
  .sw-files-head{display:flex;align-items:center;gap:16px;}
  .sw-files-ico{flex:0 0 46px;height:46px;line-height:46px;border-radius:50%;text-align:center;font-size:24px;font-weight:700;color:#fff;}
  .sw-files.warn .sw-files-ico{background:#d98a06;} .sw-files.ok .sw-files-ico{background:#1e9e61;} .sw-files.bad .sw-files-ico{background:#d64545;animation:swPulse 1.6s ease-in-out 2;}
  .sw-files-title{font-size:18px;font-weight:700;color:#14304a;} .sw-files-sub{color:#4a5b6b;margin-top:2px;}

  /* full-screen loaders: always above the v2 header / cards, readable card */
  #overlay{z-index:99998 !important;background:rgba(20,48,74,.55) !important;backdrop-filter:blur(2px);}
  #ajaxLoader,#ajaxLoader_e,#ajaxLoader_html,#ajaxLoader_algorithm,#ajaxLoader_tp,#ajaxLoader_QC,#ajaxLoader_SV,#ajaxLoader_AV,#ajaxLoader_Editlatex{z-index:99999 !important;min-width:260px;padding:22px 28px;border-radius:16px;background:#fff;box-shadow:0 24px 60px rgba(20,48,74,.4);}
  #ajaxLoader span,#ajaxLoader_e span,#ajaxLoader_html span,#ajaxLoader_algorithm span,#ajaxLoader_tp span,#ajaxLoader_QC span,#ajaxLoader_SV span,#ajaxLoader_AV span,#ajaxLoader_Editlatex span{color:#14304a !important;font-size:14px !important;margin-top:12px !important;}
  .sw-pth{display:flex;flex-wrap:wrap;align-items:center;gap:2px 0;font-family:Consolas,'Courier New',monospace;font-size:13px;font-weight:700;color:#14304a;line-height:1.5;}
  .sw-pth .sw-sep{font-style:normal;color:#F56500;margin:0 5px;font-weight:700;opacity:.85;}
  .sw-recheck{align-self:flex-start;flex:none;padding:6px 14px;border-radius:99px;border:1px solid #d64545;color:#b83232;font-weight:700;font-size:12.5px;text-decoration:none !important;background:#fff;}
  .sw-recheck:hover{background:#d64545;color:#fff;} .sw-recheck.busy i{animation:swSpin .7s linear infinite;} @keyframes swSpin{to{transform:rotate(360deg)}}
  .sw-dbg{margin-top:8px;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,.7);border:1px dashed #e6b3b3;font-size:12px;color:#4a5b6b;word-break:break-all;}
  .sw-dbg code{background:#f6e3e3;color:#8a2b2b;padding:1px 6px;border-radius:5px;font-size:11.5px;}
  .sw-files ul{list-style:none;margin:14px 0 0;padding:0;}
  .sw-files li{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;padding:10px 12px;margin-top:6px;border-radius:10px;background:#fff;border:1px solid #e4e9ef;}
  .sw-mark{width:24px;height:24px;line-height:24px;border-radius:50%;text-align:center;font-weight:700;font-size:14px;color:#fff;}
  .sw-files li.ok .sw-mark{background:#1e9e61;} .sw-files li.bad .sw-mark{background:#d64545;}
  .sw-ext{padding:3px 11px;border-radius:20px;background:#eaf2fa;color:#1b6ca8;font-weight:700;font-size:13px;}
  .sw-state{margin-left:auto;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;}
  .sw-files li.ok .sw-state{color:#1e9e61;} .sw-files li.bad .sw-state{color:#d64545;}
  @keyframes swFilesIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:none;}}
  @keyframes swPulse{0%,100%{box-shadow:0 0 0 0 rgba(214,69,69,.5);}50%{box-shadow:0 0 0 12px rgba(214,69,69,0);}}

</style>

<style id="sw-theme">
  /* ================= start_work_v2 theme ================= */
  body{background:#f3f6fa !important;}
  .container-fluid:has(.articleheading){background:#fff !important;border-bottom:1px solid #e4e9ef;box-shadow:0 2px 10px rgba(20,48,74,.05);}
  .articleheading{font-size:22px !important;font-weight:700 !important;color:#14304a !important;}

  /* article summary strip */
  .sw-summary{width:min(1180px,calc(100% - 36px));margin:22px auto 0;padding:20px 26px;border-radius:16px;color:#14304a;
    background:linear-gradient(120deg,#f4f9fe 0%,#e8f1fa 100%);border:1px solid #d6e4f1;box-shadow:0 2px 12px rgba(20,48,74,.06);
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 26px;animation:swRise .45s ease;}
  .sw-summary small{display:block;font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:#6b7785;}
  .sw-sum-id{font-size:23px;font-weight:700;line-height:1.25;word-break:break-all;}
  .sw-sum-sub{color:#5b6b7b;margin-top:2px;}
  .sw-sum-chips{display:flex;flex-wrap:wrap;gap:10px;}
  .sw-chip{min-width:112px;padding:9px 14px;border-radius:10px;background:#fff;border:1px solid #dbe6f1;}
  .sw-chip b{display:block;font-size:14px;font-weight:700;color:#14304a;}
  .sw-chip.hot{border-color:#f6d3bb;background:#fff7f1;} .sw-chip.hot b{color:#d2631d;}

  /* cards */
  .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;}
  .addarticleforminner{width:min(1180px,calc(100% - 36px)) !important;max-width:1180px !important;margin:18px auto !important;padding:26px 32px 30px !important;
    border-radius:16px !important;border:1px solid #e4e9ef !important;background:#fff !important;box-shadow:0 4px 18px rgba(20,48,74,.07) !important;animation:swRise .45s ease;}
  .addarticleforminner .container.mt-4{margin-top:0 !important;padding:0 !important;}
  @keyframes swRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}

  /* section titles (was a black arrow tag) */
  .formcont1heading{position:relative;display:block !important;background:none !important;color:#14304a !important;font-size:17px !important;font-weight:700 !important;
    padding:2px 0 14px 18px !important;margin:0 0 4px !important;border:0 !important;border-bottom:1px solid #e4e9ef !important;clip-path:none !important;height:auto !important;
    line-height:1.4 !important;word-break:break-all;}
  .formcont1heading::before{content:"";position:absolute;left:0;top:2px;bottom:16px;width:5px;border-radius:3px;background:linear-gradient(#e8742a,#1b6ca8);}
  .formcont1heading::after{display:none !important;}

  /* top action buttons (queries / instructions / specs ...) */
  #validation-form .d-flex.gap-3.justify-content-center{flex-wrap:wrap;gap:10px !important;margin:6px 0 4px;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger){background:#fff !important;color:#14304a !important;border:1px solid #cfd8e3 !important;border-radius:22px !important;
    padding:8px 18px !important;font-size:13px !important;font-weight:600 !important;width:auto !important;height:auto !important;box-shadow:none !important;transition:all .2s;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger):hover{background:#14304a !important;color:#fff !important;border-color:#14304a !important;}
  #validation-form .form_btn a.btn-orange{background:#fff7f1 !important;border:1px solid #f6d3bb !important;color:#d2631d !important;border-radius:22px !important;}
  #validation-form .form_btn a.btn-orange:hover{filter:brightness(.96);transform:translateY(-1px);}
  #validation-form .btn.btn-purple{background:#7c5cbf !important;color:#fff !important;}
  #validation-form .btn.btn-success{background:#1e9e61 !important;color:#fff !important;}
  #validation-form .btn.btn-danger{background:#d64545 !important;color:#fff !important;border:0 !important;border-radius:22px !important;padding:8px 20px !important;font-weight:600 !important;}   /* keeps its inline white text readable */
  #validation-form #openModal{border-radius:12px !important;padding:11px 18px !important;height:auto !important;width:auto !important;min-width:230px;border:1px solid #1b6ca8 !important;}
  #validation-form #openModal span{color:#1b6ca8 !important;font-weight:700;}

  /* conversion tools row */
  #validation-form .row.addspacing{display:flex;flex-wrap:wrap;justify-content:center;gap:12px !important;margin:12px 0 !important;}
  #validation-form .btn.btn-primary.col-2{width:auto !important;flex:0 0 auto;background:#eaf2fa !important;color:#1b6ca8 !important;border:1px solid #c5daef !important;
    border-radius:12px !important;padding:11px 20px !important;font-weight:600 !important;font-size:14px !important;transition:all .2s;}
  #validation-form .btn.btn-primary.col-2:hover{background:#1b6ca8 !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(27,108,168,.25);}

  /* the form body becomes a grid */
  #validation-form > .p-5{padding:20px 0 6px !important;display:grid !important;grid-template-columns:repeat(12,minmax(0,1fr));gap:20px 22px !important;}
  #validation-form > .p-5 > *{grid-column:1 / -1;min-width:0;}
  #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:span 4;max-width:none;width:auto;padding:0;}
  #validation-form > .p-5 > .sw-wide{grid-column:1 / -1 !important;max-width:none;width:auto;}
  #validation-form > .p-5 > .space-2, #validation-form > .p-5 > .space-20{display:none;}
  @media (max-width:991px){ #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:span 6;} }
  @media (max-width:640px){ #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:1 / -1;} .addarticleforminner{padding:20px 16px !important;} }

  /* labels + fields */
  #validation-form label{display:block;font-size:12.5px !important;font-weight:700 !important;letter-spacing:.3px;text-transform:uppercase;color:#5b6b7b !important;margin:0 0 7px !important;}
  #validation-form label span, #validation-form label font, #validation-form label small{text-transform:none;letter-spacing:0;font-weight:600;font-size:12px;}
  #validation-form label span[style*="red"], #validation-form label .red{color:#d64545 !important;}
  #validation-form input[type=text],#validation-form input[type=number],#validation-form input[type=email],#validation-form input[type=password],
  #validation-form select,#validation-form .form-select,#validation-form .form-control{height:46px !important;width:100% !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;
    background-color:#fbfcfe !important;padding:8px 14px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;transition:border-color .2s,box-shadow .2s,background .2s;}
  #validation-form textarea{width:100% !important;min-height:120px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:12px 14px !important;font-size:14px !important;}
  #validation-form input:focus,#validation-form select:focus,#validation-form textarea:focus{border-color:#1b6ca8 !important;background:#fff !important;box-shadow:0 0 0 4px rgba(27,108,168,.14) !important;outline:0;}
  #validation-form input[type=radio],#validation-form input[type=checkbox]{accent-color:#1b6ca8;width:17px;height:17px;vertical-align:-3px;}
  #validation-form .help-block,#validation-form label.error{display:block;color:#d64545 !important;font-size:12px !important;font-weight:600 !important;text-transform:none !important;margin-top:5px !important;}

  /* tables (checklist, file list) */
  .addarticleforminner table{width:100%;border-collapse:separate !important;border-spacing:0;border:1px solid #e4e9ef !important;border-radius:12px;overflow:hidden;margin:6px 0 !important;}
  .addarticleforminner th{background:#eef3f8 !important;color:#14304a !important;font-size:12px !important;text-transform:uppercase;letter-spacing:.5px;font-weight:700 !important;
    padding:12px 14px !important;border:0 !important;border-bottom:2px solid #d5e1ed !important;text-align:left;}
  .addarticleforminner td{padding:11px 14px !important;border:0 !important;border-bottom:1px solid #eef1f5 !important;background:#fff !important;color:#33475b !important;vertical-align:middle;}
  .addarticleforminner tbody tr:nth-child(even) td{background:#f9fbfd !important;}
  .addarticleforminner tbody tr:hover td{background:#eef5fb !important;}
  .addarticleforminner td[colspan]{text-align:center;color:#8a95a1 !important;padding:22px 14px !important;}

  /* buttons at the bottom of the form */
  #validation-form .btn-purple,#validation-form a.btn-purple,#validation-form .btn-success{border-radius:22px !important;padding:8px 20px !important;font-weight:600 !important;border:0 !important;}
  #validation-form .container-fluid.mt-5{margin:26px 0 0 !important;}
  #validation-form .form_btn.cancelbtn button,#cancel-btn{min-width:130px;}
  #submit-btn,#submit-btnform{min-width:150px;}

  /* file browser card */
  .sw-upload{background:#1b6ca8 !important;color:#fff !important;border:0 !important;border-radius:10px !important;padding:10px 18px !important;font-weight:700 !important;transition:background .2s;}
  .sw-upload:hover{background:#14304a !important;}
  .addarticleforminner input[type=file]{border:1px dashed #b9c8d8;border-radius:10px;padding:8px 10px;background:#fbfcfe;}

  /* "File Update Status" panel */
  .modal-contentcss{border-radius:18px !important;border:1px solid #dbe6f1 !important;box-shadow:0 22px 60px rgba(20,48,74,.28) !important;}
  .modal-contentcss .heading{color:#14304a !important;font-size:19px !important;}
  .modal-contentcss .button-row a{border-radius:20px !important;padding:7px 16px !important;font-size:13px !important;}
  .modal-contentcss .button-group .submit,#submit-btnform{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;border:0 !important;border-radius:10px !important;padding:11px 26px !important;font-weight:700 !important;}
  .modal-contentcss .radio-group label{text-transform:none !important;font-size:14px !important;color:#22303f !important;font-weight:600 !important;margin:0 !important;}
  .modal .modal-content{border-radius:16px;border:0;box-shadow:0 22px 60px rgba(20,48,74,.3);} .modal-header{background:#f4f8fc;border-bottom:1px solid #e4e9ef;}
</style>
<style id="sw-compact">
  /* ================= compact sizing + motion (overrides sw-theme) ================= */
  html{scroll-behavior:smooth;}
  #validation-form{font-size:13px;}
  .articleheading{font-size:19px !important;}

  /* summary strip */
  .sw-summary{margin:14px auto 0;padding:12px 18px;border-radius:12px;gap:10px 18px;}
  .sw-sum-id{font-size:18px;} .sw-sum-sub{font-size:12.5px;margin-top:0;} .sw-summary small{font-size:10px;letter-spacing:.7px;}
  .sw-sum-chips{gap:8px;} .sw-chip{min-width:92px;padding:6px 11px;border-radius:8px;} .sw-chip b{font-size:12.5px;}
  .sw-chip{animation:swPop .4s cubic-bezier(.2,.9,.3,1.3) backwards;}
  .sw-chip:nth-child(1){animation-delay:.10s;} .sw-chip:nth-child(2){animation-delay:.16s;} .sw-chip:nth-child(3){animation-delay:.22s;}
  .sw-chip:nth-child(4){animation-delay:.28s;} .sw-chip:nth-child(5){animation-delay:.34s;}
  @keyframes swPop{from{opacity:0;transform:scale(.85) translateY(6px);}to{opacity:1;transform:none;}}

  /* required-files card */
  .sw-files{width:min(640px,calc(100% - 36px));margin:12px auto 0;padding:11px 16px;border-radius:12px;border-left-width:5px;font-size:13px;animation:swRise .45s ease .08s backwards;}
  .sw-files-head{gap:11px;} .sw-files-ico{flex-basis:34px;height:34px;line-height:34px;font-size:18px;}
  .sw-files-title{font-size:14.5px;} .sw-files-sub{font-size:12px;margin-top:0;}
  .sw-files ul{margin-top:9px;} .sw-files li{padding:6px 10px;margin-top:4px;border-radius:8px;gap:6px 8px;}
  .sw-mark{width:19px;height:19px;line-height:19px;font-size:11px;} .sw-ext{padding:2px 9px;font-size:12px;} .sw-state{font-size:10.5px;}
  .sw-files.bad li.bad{animation:swShake .5s ease .5s backwards;}
  @keyframes swShake{0%,100%{transform:none;}20%{transform:translateX(-5px);}40%{transform:translateX(5px);}60%{transform:translateX(-3px);}80%{transform:translateX(3px);}}

  /* cards */
  .addarticleforminner{margin:12px auto !important;padding:16px 22px 20px !important;border-radius:14px !important;animation:swRise .5s ease backwards;}
  .addarticleformouter:nth-of-type(1) .addarticleforminner{animation-delay:.16s;}
  .addarticleformouter + .addarticleformouter .addarticleforminner{animation-delay:.26s;}
  .formcont1heading{font-size:14.5px !important;padding:1px 0 10px 14px !important;}
  .formcont1heading::before{width:4px;bottom:11px;}

  /* buttons */
  #validation-form .d-flex.gap-3.justify-content-center{gap:7px !important;margin:2px 0;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger),
  #validation-form .form_btn a.btn-orange{padding:5px 13px !important;font-size:12px !important;}
  #validation-form #openModal{padding:7px 14px !important;min-width:190px;font-size:12.5px !important;}
  #validation-form .row.addspacing{gap:8px !important;margin:8px 0 !important;}
  #validation-form .btn.btn-primary.col-2{padding:7px 14px !important;font-size:12.5px !important;border-radius:9px !important;}
  #validation-form .btn.btn-purple,#validation-form .btn.btn-success,#validation-form .btn.btn-danger{padding:5px 15px !important;font-size:12px !important;}
  #validation-form .btn,#submit-btn,#cancel-btn,.sw-upload{transition:transform .15s,box-shadow .2s,background .2s,color .2s,border-color .2s;}
  #validation-form .btn:active,#submit-btn:active,#cancel-btn:active,.sw-upload:active{transform:scale(.96) !important;}
  #submit-btn,#submit-btnform{padding:8px 24px !important;min-width:120px;font-size:13px;background-size:200% 100% !important;background-position:0 0;}
  #submit-btn:hover,#submit-btnform:hover{background-position:100% 0;}
  #cancel-btn{padding:8px 22px !important;min-width:100px;font-size:13px;}
  #validation-form .container-fluid.mt-5{margin:14px 0 0 !important;}

  /* form grid */
  #validation-form > .p-5{padding:12px 0 2px !important;gap:12px 16px !important;}
  #validation-form > .p-5 > *{animation:swRise .4s ease backwards;}
  #validation-form > .p-5 > *:nth-child(1){animation-delay:.20s;} #validation-form > .p-5 > *:nth-child(2){animation-delay:.24s;}
  #validation-form > .p-5 > *:nth-child(3){animation-delay:.28s;} #validation-form > .p-5 > *:nth-child(4){animation-delay:.32s;}
  #validation-form > .p-5 > *:nth-child(5){animation-delay:.36s;} #validation-form > .p-5 > *:nth-child(n+6){animation-delay:.40s;}
  #validation-form label{font-size:11px !important;margin:0 0 4px !important;transition:color .2s;}
  #validation-form label span,#validation-form label font,#validation-form label small{font-size:11px;}
  #validation-form > .p-5 > *:focus-within > label{color:#1b6ca8 !important;}
  #validation-form input[type=text],#validation-form input[type=number],#validation-form input[type=email],#validation-form input[type=password],
  #validation-form select,#validation-form .form-select,#validation-form .form-control{height:36px !important;padding:5px 11px !important;font-size:13px !important;border-radius:8px !important;}
  #validation-form textarea{min-height:76px;padding:8px 11px !important;font-size:13px !important;border-radius:8px !important;}

  /* tables */
  .addarticleforminner table{margin:2px 0 !important;border-radius:10px;}
  .addarticleforminner th{padding:7px 11px !important;font-size:11px !important;}
  .addarticleforminner td{padding:7px 11px !important;font-size:12.5px;}
  .addarticleforminner tbody tr{animation:swRowIn .35s ease backwards;}
  .addarticleforminner tbody tr:nth-child(2){animation-delay:.04s;} .addarticleforminner tbody tr:nth-child(3){animation-delay:.08s;}
  .addarticleforminner tbody tr:nth-child(4){animation-delay:.12s;} .addarticleforminner tbody tr:nth-child(n+5){animation-delay:.16s;}
  .addarticleforminner tbody tr td:first-child{box-shadow:inset 0 0 0 #1b6ca8;transition:box-shadow .2s;}
  .addarticleforminner tbody tr:hover td:first-child{box-shadow:inset 3px 0 0 #1b6ca8;}
  @keyframes swRowIn{from{opacity:0;transform:translateX(-8px);}to{opacity:1;transform:none;}}
  .addarticleforminner td[colspan]{padding:14px 11px !important;}

  /* ===== file browser card ===== */
  .formcont1heading.mb-2[style*="left"]{left:0 !important;padding:0 0 8px 0 !important;border:0 !important;margin:0 0 6px !important;}
  .formcont1heading.mb-2[style*="left"]::before{display:none;}
  .formcont1heading.mb-2 p{display:inline-flex;align-items:center;gap:8px;width:auto !important;max-width:100%;margin:0;padding:6px 12px;border-radius:8px;
    background:#f3f7fb;border:1px solid #dbe6f1;color:#33475b;font:600 12px/1.4 Consolas,'Courier New',monospace;letter-spacing:0;word-break:break-all;}
  .formcont1heading.mb-2 p::before{content:"\f07b";font-family:FontAwesome;color:#e8742a;font-weight:400;font-size:14px;}
  .addarticleforminner .row{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;margin:0 !important;}
  .addarticleforminner .row > .col-12.col-md-6{width:auto !important;flex:0 1 auto;padding:0 !important;}
  .addarticleforminner .row form{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0;}
  .addarticleforminner thead th::before,.addarticleforminner thead th::after{display:none !important;}
  .addarticleforminner table.dataTable thead th{background-repeat:no-repeat !important;background-position:right 8px center !important;background-size:9px !important;padding-right:24px !important;}
  .addarticleforminner input[type=file]{font-size:12px;padding:4px 8px;max-width:280px;border-radius:8px;transition:border-color .2s,background .2s;}
  .addarticleforminner input[type=file]:hover{border-color:#1b6ca8;background:#f3f8fd;}
  .addarticleforminner input[type=file]::file-selector-button{margin-right:8px;padding:4px 10px;border:0;border-radius:6px;background:#eaf2fa;color:#1b6ca8;font-weight:600;cursor:pointer;transition:background .2s;}
  .addarticleforminner input[type=file]::file-selector-button:hover{background:#1b6ca8;color:#fff;}
  .sw-upload{padding:6px 14px !important;font-size:12.5px !important;margin:0 !important;white-space:nowrap;width:auto !important;}
  .addarticleforminner .main.pb-5{padding-bottom:0 !important;}
  .addarticleforminner .bottom-content{overflow:auto !important;max-height:300px;margin-top:6px;border-radius:10px;}
  .addarticleforminner .bottom-content::-webkit-scrollbar{width:8px;height:8px;} .addarticleforminner .bottom-content::-webkit-scrollbar-thumb{background:#c9d6e3;border-radius:8px;}

  /* panel + modal */
  .modal-contentcss .heading{font-size:16px !important;}
  .modal-contentcss .button-row a{padding:5px 12px !important;font-size:12px !important;}
  .modal-contentcss .button-group .submit,#submit-btnform{padding:8px 20px !important;}


  /* input + button groups (e.g. "Fetch Author Details") stay on one line */
  #validation-form .input-group{display:flex !important;flex-wrap:nowrap !important;align-items:center;gap:8px;width:100%;}
  #validation-form .input-group > input,#validation-form .input-group > .form-control{flex:1 1 auto !important;width:auto !important;min-width:0;}
  #validation-form .input-group > .btn{flex:0 0 auto;margin:0 !important;height:36px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;
    padding:0 14px !important;font-size:12.5px !important;font-weight:600 !important;border-radius:8px !important;}
  #validation-form .input-group > .btn i{margin:0;font-size:14px;vertical-align:middle;}
  #validation-form > .p-5 > .sw-span6{grid-column:span 6 !important;}
  @media (max-width:991px){ #validation-form > .p-5 > .sw-span6{grid-column:1 / -1 !important;} }
  /* page header: back button + title + breadcrumb in one tidy bar */
  .sw-bar{padding:8px 0 !important;background:#f3f6fa !important;border:0 !important;box-shadow:none !important;}
  .sw-bar > .row{display:none !important;}
  .sw-bar-in{display:flex;align-items:center;gap:14px;width:min(1180px,calc(100% - 36px));margin:0 auto;padding:10px 18px;background:#fff;border:1px solid #e4e9ef;border-radius:14px;
    box-shadow:0 4px 18px rgba(20,48,74,.07);animation:swRise .4s ease backwards;}
  .sw-back{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:#14304a;
    text-decoration:none !important;box-shadow:0 2px 6px rgba(20,48,74,.08);transition:all .2s;}
  .sw-back:hover{background:#e8742a;border-color:#e8742a;color:#fff;transform:translateX(-2px);box-shadow:0 4px 10px rgba(232,116,42,.35);}
  .sw-back i{font-size:15px;}
  .sw-legacy{margin-right:12px;padding:5px 11px;border:1px solid #d9e2ec;border-radius:999px;background:#fff;color:#5b6b7b !important;font-size:12px;font-weight:600;text-decoration:none !important;white-space:nowrap;transition:all .2s;}
  .sw-legacy:hover{background:#fff7f1;border-color:#f1c4a6;color:#c9591a !important;}
  .sw-legacy i{margin-right:5px;}
  .sw-att{margin:12px 0 0;padding:12px 14px;border:1.5px dashed #c9d6e3;border-radius:12px;background:#f8fafc;text-align:left;transition:all .2s;}
  .sw-att.over{border-color:#1b6ca8;background:#eef6fc;}
  .sw-att-btn{display:inline-flex;align-items:center;gap:7px;margin:0;padding:7px 14px;border-radius:9px;background:#fff;border:1px solid #cfd8e3;color:#14304a;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;}
  .sw-att-btn:hover{border-color:#1b6ca8;color:#1b6ca8;box-shadow:0 3px 8px rgba(27,108,168,.15);}
  .sw-att-btn input{display:none;}
  .sw-att-hint{margin-left:10px;color:#6b7785;font-size:12.5px;}
  .sw-att-count{float:right;color:#1e9e61;font-size:12px;font-weight:700;}
  .sw-att-lim{margin-top:6px;color:#8b97a4;font-size:11.5px;}
  .sw-att-list{list-style:none;margin:8px 0 0;padding:0;}
  .sw-att-list li{display:flex;align-items:center;gap:8px;margin:0 0 6px;padding:6px 8px;background:#fff;border:1px solid #e1e8ef;border-radius:8px;font-size:12.5px;animation:swItem .3s ease backwards;}
  .sw-att-list li i{color:#1b6ca8;} .sw-att-list .n{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#2f4357;} .sw-att-list .z{color:#8b97a4;font-size:11.5px;}
  .sw-att-list .x{border:0;background:none;color:#b13434;font-size:18px;line-height:1;cursor:pointer;padding:0 4px;} .sw-att-list .x:hover{color:#d64545;}
  .sw-legacy.sw-hl{background:#fff7f1;border-color:#e8742a;color:#c9591a !important;animation:swHl 1.3s ease-in-out infinite;}
  @keyframes swHl{0%{box-shadow:0 0 0 0 rgba(232,116,42,.55);transform:scale(1);}50%{box-shadow:0 0 0 9px rgba(232,116,42,0);transform:scale(1.07);}100%{box-shadow:0 0 0 0 rgba(232,116,42,0);transform:scale(1);}}
  .sw-bar-title{font-size:18px;font-weight:700;color:#14304a;line-height:1.2;margin:0;}
  .sw-bar-crumb{font-size:11.5px;color:#7a8898;margin-top:2px;} .sw-bar-crumb a{color:#1b6ca8;text-decoration:none;} .sw-bar-crumb a:hover{text-decoration:underline;}
  .sw-bar-sp{flex:1 1 auto;}
  .sw-bar-id{font-size:12px;font-weight:700;color:#14304a;background:#eef3f8;border:1px solid #dbe6f1;border-radius:20px;padding:4px 12px;}
  /* ONE common box: header + article summary + required-files notice */
  .sw-hero{width:min(1180px,calc(100% - 36px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;
    box-shadow:0 6px 22px rgba(20,48,74,.09);animation:swRise .45s ease backwards;}
  .sw-hero > * + *{border-top:1px solid #e8eef4;}
  .sw-bar{display:none !important;}
  .sw-hero .sw-bar-in{width:auto;margin:0;padding:11px 20px;background:#fff;border:0;border-radius:0;box-shadow:none;animation:none;}
  .sw-hero .sw-summary{width:auto;margin:0;padding:12px 20px;border:0;border-radius:0;box-shadow:none;animation:none;
    background:linear-gradient(120deg,#f7fbff 0%,#edf4fb 100%);}
  .sw-hero .sw-files{width:auto;max-width:none;margin:0;padding:10px 20px;border:0;border-left:5px solid;border-radius:0;box-shadow:none;animation:swSlide .4s ease backwards;}
  .sw-hero .sw-files.ok{background:#f1fbf5;border-left-color:#1e9e61;} .sw-hero .sw-files.bad{background:#fff5f5;border-left-color:#d64545;} .sw-hero .sw-files.warn{background:#fff8e6;border-left-color:#d98a06;}
  .sw-hero .sw-files ul{max-width:760px;}
  @keyframes swSlide{from{opacity:0;max-height:0;}to{opacity:1;max-height:400px;}}
  /* tool buttons (3D to HTML, package creation, ePub ...): one loader for all of them */
  [id^="ajaxLoader"],#overlay{display:none !important;}   /* the old per-button loaders are replaced by the common one below */
  .sw-tool-busy{pointer-events:none;opacity:.8;position:relative;}
  .sw-tool-busy::before{content:"";display:inline-block;width:12px;height:12px;margin-right:7px;border:2px solid currentColor;border-top-color:transparent;
    border-radius:50%;animation:swSpin .7s linear infinite;vertical-align:-2px;}
  /* radio rows must stay on one line (my label rule made them block) */
  .modal-contentcss .radio-group,.modal-contentcss .formgroup1{display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;}
  .modal-contentcss .radio-group label,#validation-form .radio-group label,#validation-form label.form-check-label{display:inline-flex !important;align-items:center;gap:7px;margin:0 !important;
    text-transform:none !important;letter-spacing:0 !important;font-size:13px !important;color:#22303f !important;font-weight:600 !important;cursor:pointer;}
  .modal-contentcss .radio-group input[type=radio]{margin:0;}
  .modal-contentcss .formgroup1{flex-direction:column !important;align-items:flex-start !important;gap:4px !important;margin:10px 0 4px !important;height:auto !important;min-height:0 !important;}
  .modal-contentcss .sw-status-err{margin:0 !important;flex:0 0 auto;}
  #validation-form .modal .btn-close,.modal .btn-close{width:30px !important;min-width:0 !important;height:30px !important;padding:0 !important;border:0 !important;border-radius:50% !important;
    background-color:#eef3f8 !important;opacity:1 !important;transition:background .2s,transform .2s;}
  .modal .btn-close:hover{background-color:#dfe8f1 !important;transform:rotate(90deg);}
  /* dialogs always above the page content */
  .modal{z-index:2050 !important;} .modal-backdrop{z-index:2040 !important;}
  @media (prefers-reduced-motion:reduce){ *{animation:none !important;transition:none !important;} html{scroll-behavior:auto;} }
</style>
<div id="sw-bar"></div>
<div id="sw-overlay"><div class="sw-ring"></div><div class="sw-title" id="sw-title">Saving&hellip;</div><div class="sw-sub">Please wait, do not close this tab</div></div>

<script>
(function ($) {
  var SET_URL = <?php echo json_encode($_COOKIE['set_url']); ?>,
      busy = false, barTimer = null, barVal = 0;

  function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
  window.swLoaderOn = function (x) { loaderOn(x); }; window.swLoaderOff = function () { loaderOff(); };   /* used by the Package creation buttons */
  function alertBox(type, title, message) {
    var html = '';
    if (message) {
      var parts = String(message).split('\n'), bullets = parts.length > 1 || /^\s*•/.test(message);
      html = bullets ? '<ul class="sw-list">' + $.map(parts, function (l) { l = $.trim(l.replace(/^\s*•\s*/, '')); return l ? '<li>' + esc(l) + '</li>' : null; }).join('') + '</ul>'
                     : '<div class="sw-msg">' + esc(message) + '</div>';
    }
    swal({ title: title, html: html, type: type,
           confirmButtonText: 'OK', confirmButtonColor: '#1b6ca8', allowOutsideClick: false });
  }

  /* ---- loader ---- */
  function bar(v) { barVal = v; $('#sw-bar').css('width', v + '%'); }
  function loaderOn(text) {
    $('#sw-title').text(text || 'Saving…');
    $('#sw-overlay').addClass('on');
    clearInterval(barTimer); $('#sw-bar').addClass('on'); bar(6);
    barTimer = setInterval(function () { bar(barVal + (92 - barVal) * 0.06); }, 200);
  }
  function loaderOff() {
    clearInterval(barTimer); bar(100); $('#sw-overlay').removeClass('on');
    setTimeout(function () { $('#sw-bar').removeClass('on'); setTimeout(function () { bar(0); }, 450); }, 250);
  }

  /* ---- ajax submit (called by the validator's submitHandler) ---- */
  /* required files for the file movement - only matters when the status is Completed */
  function isCompleted() {
    var v = $('#status').length ? $('#status').val() : $('input[name=status]:checked').val();
    return String(v) === '1';
  }
  function checkFiles() {
    return $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_check_files: 1 } });
  }

  /* ===== v3: "what will go to Delivery" confirmation (only when the next step is Delivery / an automatic upload to Client Review) ===== */
  $(document).ajaxSuccess(function (e, x, st) {
    if (/journal_style_validator\.php/.test(st.url || '')) { setTimeout(function () { var el = document.getElementById('result_qc'); if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } }, 150); }
  });
  var SW3 = <?php $sw3c = sw3_context($db, $app_res); echo json_encode(array('applies' => (bool)$sw3c, 'to' => $sw3c ? $sw3c['to'] : ''), JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  function sw3Gate(form) {
    var assign = String($('#assign_department').val() || ''), skip = String($('[name=skip_dept]').val() || '');
    if (!SW3.applies || assign !== '' || skip !== '') { doSave(form); return; }      /* routed somewhere else: no delivery check */
    loaderOn('Preparing the delivery check…');
    $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw3_pre_delivery: 1 } })
      .done(function (r) {
        loaderOff();
        if (!r || !r.applies) { doSave(form); return; }
        try { sw3Popup(r, form); }
        catch (e) { sw3Release(); alertBox('error', 'Could not show the delivery check', String(e && e.message ? e.message : e) + '\n\nPlease reload the page and try again, and tell the support team.'); }
      })
      .fail(function () { loaderOff(); doSave(form); });                              /* the server repeats the real checks, so a failed pre-check is not fatal */
  }
  function sw3Release() { busy = false; $('#submit-btn').prop('disabled', false).show(); }
  function sw3Decision(r, d) { return $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw3_decision: 1, log_id: r.log_id, decision: d } }); }
  function sw3Card(f) {
    var cls = f.error ? 'bad' : (f.will_move ? 'ok' : ''), icon = f.type == 'zip' ? 'fa-file-archive-o' : (f.type == 'pdf' ? 'fa-file-pdf-o' : 'fa-file-o'), stale = f.age_hours > 24;
    var h = '<div class="sw3-card ' + cls + '"><div class="sw3-row"><span class="sw3-ico"><i class="fa ' + icon + '"></i></span><div class="sw3-t"><b>' + esc(f.rel || f.name) + '</b> ' +
      (f.will_move ? '<span class="sw3-tag">will move to Delivery</span>' : '<span class="sw3-tag grey">stays in this folder</span>') +
      '<div class="sw3-meta"><span><i class="fa fa-hdd-o"></i> ' + esc(f.size_text) + '</span><span class="' + (stale && f.will_move ? 'amber' : '') + '"><i class="fa fa-clock-o"></i> Modified ' + esc(f.modified) + (stale && f.will_move ? ' &middot; older than 24 hours' : '') + '</span>' +
      (f.type == 'pdf' && !f.error ? '<span><i class="fa fa-file-text-o"></i> ' + (f.pages ? f.pages + ' page' + (f.pages == 1 ? '' : 's') : 'page count n/a') + '</span>' : '') +
      (f.type == 'zip' && f.entries ? '<span><i class="fa fa-folder-open-o"></i> ' + (f.entries_total || f.count) + ' file' + ((f.entries_total || f.count) == 1 ? '' : 's') + ' inside</span>' : '') + '</div>' +
      (f.error ? '<div class="sw3-bad"><i class="fa fa-exclamation-triangle"></i> ' + esc(f.error) + '</div>' : '') + '</div></div>';
    if (f.entries && f.entries.length && (f.will_move || f.where)) {
      h += '<div class="sw3-zip">'; $.each(f.entries.slice(0, 200), function (i, e) { h += '<div><span>' + esc(e.name) + '</span><em>' + esc(e.size) + '</em></div>'; }); h += '</div>';
    }
    return h + '</div>';
  }
  function sw3Popup(r, form) {
    var errors = r.errors || [], warns = r.warnings || [], files = r.files || [], h = '';
    var moving = $.grep(files, function (f) { return f.will_move; }).length, stay = files.length - moving;
    h += '<div class="sw3-hero"><div class="sw3-k">ARTICLE</div><div class="sw3-title">' + esc(r.pub_id) + '</div><div class="sw3-sub">' + esc(r.cust) + ' &middot; ' + esc(r.journal) + ' &middot; stage ' + esc(r.stage) + '</div>' +
         '<div class="sw3-sub" style="margin-top:6px;"><b>' + esc(r.dept) + '</b> &rarr; <b>' + esc(r.ctx.to) + '</b></div>' +
         '<div class="sw3-pills"><span class="sw3-pill"><b>' + moving + '</b> file' + (moving == 1 ? '' : 's') + ' will move</span><span class="sw3-pill"><b>' + stay + '</b> stay here</span>' +
         (errors.length ? '<span class="sw3-pill bad"><b>' + errors.length + '</b> problem' + (errors.length == 1 ? '' : 's') + '</span>' : '') + (warns.length ? '<span class="sw3-pill warn"><b>' + warns.length + '</b> warning' + (warns.length == 1 ? '' : 's') + '</span>' : '') +
         (!errors.length && !warns.length ? '<span class="sw3-pill ok"><i class="fa fa-check"></i> all checks passed</span>' : '') + '</div></div>';
    h += '<div class="sw3-sec"><i class="fa fa-folder-o"></i> FILES IN THE ' + esc((r.dept || '').toUpperCase()) + ' FOLDER <span class="sw3-count">' + files.length + '</span></div>';
    if (!files.length) { h += '<div class="sw3-card"><span class="sw3-muted">The folder is empty or the server cannot see it.</span></div>'; }
    $.each(files, function (i, f) { h += sw3Card(f); });
    if (r.ctx.kind != 'auto') {
      var df = r.dest_files || [];
      h += '<div class="sw3-sec"><i class="fa fa-truck"></i> ALREADY IN THE DELIVERY FOLDER <span class="sw3-count">' + df.length + '</span></div>';
      if (!df.length) { h += '<div class="sw3-card ok slim"><i class="fa fa-check-circle"></i> The Delivery folder is empty - nothing will be overwritten.</div>'; }
      $.each(df, function (i, d) {
        h += '<div class="sw3-card ' + (d.collides ? 'warn' : '') + ' slim"><div class="sw3-row2"><b>' + esc(d.rel) + '</b>' +
             (d.collides ? ' <span class="sw3-tag amberbg">' + (d.same ? 'same name &middot; identical content &middot; will be overwritten' : 'same name &middot; will be overwritten') + '</span>' : ' <span class="sw3-tag grey">stays</span>') +
             '<span class="sw3-r">' + esc(d.size_text) + ' &middot; ' + esc(d.modified) + '</span></div></div>';
      });
      h += '<div class="sw3-sec"><i class="fa fa-exchange"></i> MOVE FILES CONFIGURATION &rarr; DELIVERY</div>';
      if (!r.config.configured) { h += '<div class="sw3-card warn">No file movement is configured for this stage and department.</div>'; }
      $.each(r.config.rows, function (i, c) { var ok = c.found.length > 0; h += '<div class="sw3-card ' + (ok ? 'ok' : 'warn') + ' slim"><b>.' + esc(c.ext) + '</b> &mdash; ' + (ok ? esc(c.found.join(', ')) : 'not in the folder') + '</div>'; });
    }
    if (r.auto) {
      h += '<div class="sw3-sec"><i class="fa fa-cloud-upload"></i> ' + esc(r.auto.label.toUpperCase()) + '</div>';
      if (!r.auto.package.length) { h += '<div class="sw3-card bad">No package was found for the automatic upload.</div>'; }
      $.each(r.auto.package, function (i, f) { f.where = f.where || 'folder'; h += sw3Card($.extend({}, f, { will_move: true, rel: f.name + '  (' + f.where + ')' })); });
    }
    h += '<div class="sw3-sec"><i class="fa fa-check-square-o"></i> CHECKS <span class="sw3-count">' + (errors.length + warns.length) + '</span></div>';
    if (!errors.length && !warns.length) { h += '<div class="sw3-card ok slim"><i class="fa fa-check-circle"></i> All checks passed.</div>'; }
    $.each(errors, function (i, t) { h += '<div class="sw3-card bad slim"><i class="fa fa-times-circle"></i> ' + esc(t) + '</div>'; });
    $.each(warns, function (i, t) { h += '<div class="sw3-card warn slim"><i class="fa fa-exclamation-triangle"></i> ' + esc(t) + '</div>'; });
    if (!errors.length) { h += '<label class="sw3-review"><input type="checkbox" id="sw3-reviewed"> I have reviewed the files above and want to complete this work.</label>'; }
    else { h += '<div class="sw3-card bad slim" style="font-weight:600"><i class="fa fa-ban"></i> Fix the problems marked in red (in the department folder), then submit again.</div>'; }
    h += '<div class="sw3-foot"><i class="fa fa-history"></i> This check and your decision are recorded in the log.</div>';

    var $o = $('<div class="sw3-ovl"><div class="sw3-box"><div class="sw3-hd"><span><i class="fa fa-truck"></i> Check before moving to ' + esc(r.ctx.to) + '</span></div><div class="sw3-bd"></div><div class="sw3-ft"></div></div></div>').appendTo('body');
    $o.find('.sw3-bd').html(h);
    var $ft = $o.find('.sw3-ft');
    function close() { $o.remove(); $('body').css('overflow', ''); }
    $('body').css('overflow', 'hidden');
    $('<button type="button" class="sw3-ghost">' + (errors.length ? 'Close' : 'Cancel, let me check') + '</button>').appendTo($ft).on('click', function () { sw3Decision(r, 'cancelled'); close(); sw3Release(); });
    if (!errors.length) {
      var $go = $('<button type="button" class="sw3-go" disabled>Yes, complete</button>').appendTo($ft);
      $o.on('change', '#sw3-reviewed', function () { $go.prop('disabled', !this.checked); });
      $go.on('click', function () {
        $go.prop('disabled', true).text('Saving…');
        sw3Decision(r, 'confirmed').always(function () {
          var $f = $(form); $f.find('input[name=sw3_log_id]').remove(); $f.append($('<input type="hidden" name="sw3_log_id">').val(r.log_id));
          close(); doSave(form);
        });
      });
    }
  }

  window.swSubmit = function (form) {
    if (busy) { return false; }
    busy = true;
    $('#submit-btn').prop('disabled', true);

    if (isCompleted()) {
      loaderOn('Checking required files…');
      checkFiles().done(function (r) {
        if (r && r.problems && r.problems.length) {
          loaderOff(); busy = false; $('#submit-btn').prop('disabled', false).show();
          var pkgOnly = true; $.each(r.problems, function (i, x) { if (!/^Package creation/.test(x)) { pkgOnly = false; } });
          alertBox('error', pkgOnly ? 'Package creation is pending' : 'Required files are missing', r.problems.join('\n') + (pkgOnly ? '' : '\n\nAdd the file(s) to the department folder and submit again.'));
          return;
        }
        sw3Gate(form);
      }).fail(function () { sw3Gate(form); });   /* the server repeats the check, so a failed pre-check is not fatal */
      return false;
    }
    doSave(form);
    return false;
  };

  function doSave(form) {
    busy = true;
    $('#submit-btn').prop('disabled', true);
    loaderOn('Saving work status…');

    $.ajax({
      url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false,
      data: $(form).serialize() + '&sw_ajax=1'
    }).done(function (r) {
      loaderOff();
      if (r && r.ok) {
        swal({ title: r.title, type: 'success', confirmButtonText: 'CLOSE', confirmButtonColor: '#1e9e61', allowOutsideClick: false })
          .then(function () {
            try { if (window.opener) { window.opener.focus(); window.opener.location.reload(); } } catch (e) {}
            try { window.close(); } catch (e) {}
            setTimeout(function () { location.href = SET_URL + '/currentwork'; }, 400);
          });
      } else {
        busy = false; $('#submit-btn').prop('disabled', false).show();
        alertBox((r && r.type) || 'error', (r && r.title) || 'Error', (r && r.message) || 'Unknown error.');
      }
    }).fail(function (xhr, status) {
      loaderOff(); busy = false; $('#submit-btn').prop('disabled', false).show();
      var body = $.trim($('<div>').html((xhr.responseText || '').replace(/<(script|style)[\s\S]*?<\/\1>/gi, '')).text()).replace(/\s+/g, ' ').slice(0, 500);
      alertBox('error', status === 'parsererror' ? 'Unexpected server response' : 'Request failed',
        (xhr.status ? 'HTTP ' + xhr.status + (xhr.statusText ? ' ' + xhr.statusText : '') + '\n' : '') + (body || 'No response from the server. Check the article status before submitting again.'));
    });
  }

  $(function () {
    /* ---- v2 theme helpers ---- */
    $('#overlay, #ajaxLoader, #ajaxLoader_e, #ajaxLoader_html, #ajaxLoader_algorithm, #ajaxLoader_tp, #ajaxLoader_QC, #ajaxLoader_SV, #ajaxLoader_AV, #ajaxLoader_Editlatex').appendTo('body');
    function tagWide() {
      $('#validation-form > .p-5 > *').each(function () {
        var $c = $(this);
        if ($c.is('textarea, .row, table') || $c.find('textarea, table, iframe, .row').length) { $c.addClass('sw-wide'); }
        else if ($c.find('#Author_Validaion').length) { $c.addClass('sw-span6'); }
      });
    }
    tagWide(); setTimeout(tagWide, 800);
    var $hero = $('<div class="sw-hero"></div>').insertBefore($('.addarticleformouter').first());

    /* tidy page header: [back] Article Details / breadcrumb ...... #id */
    (function () {
      var $bar = $('.articleheading').first().closest('.container-fluid');
      if (!$bar.length || $bar.hasClass('sw-bar')) { return; }
      var href = $bar.find('.back_icon').closest('a').attr('href') || (SET_URL + '/currentwork');
      $bar.addClass('sw-bar'); $hero.append(
        '<div class="sw-bar-in"><a class="sw-back" href="' + esc(href) + '" title="Back to Current Work"><i class="fa fa-arrow-left"></i></a>' +
        '<div><h1 class="sw-bar-title">Article Details<span title="Page version" style="display:inline-block;margin-left:10px;padding:2px 11px;border-radius:12px;background:#e8863a;color:#fff;font-size:12px;font-weight:700;vertical-align:middle;letter-spacing:.3px;">Start Work v3</span></h1><div class="sw-bar-crumb"><a href="' + esc(href) + '">Current Work</a> &rsaquo; Start Work</div></div>' +
        '<span class="sw-bar-sp"></span><a href="#" id="sw-legacy" class="sw-legacy" title="Go back to the old Start Work page"><i class="fa fa-undo"></i> Use old version</a><span class="sw-bar-id">' + esc(<?php echo json_encode('#'.$app_res['pub_id'], JSON_HEX_TAG | JSON_HEX_AMP); ?>) + '</span></div>');
    })();
    $('#validation-form .form_btn a.btn-orange').each(function () {   /* the markup forces white text inline; make it match the other pills */
      this.style.setProperty('color', '#d2631d', 'important'); this.style.setProperty('background', '#fff7f1', 'important'); this.style.setProperty('border', '1px solid #f6d3bb', 'important');
    });
    $('.addarticleforminner button, .addarticleforminner input[type=submit]').filter(function () { return /upload/i.test($(this).text() || this.value); }).addClass('sw-upload');

    var SUM = <?php echo json_encode(array(
      'id'      => (string)$app_res['pub_id'],
      'client'  => (string)$customer_info['cust_name'],
      'journal' => (string)(!empty($journal_info['j_code']) ? $journal_info['j_code'] : $journal_info['j_name']),
      'stage'   => (string)$app_res['stage'],
      'dept'    => (string)$app_res['status'],
      'recv'    => (!empty($app_res['recv_dt']) && $app_res['recv_dt'] != '0000-00-00') ? date('d-m-Y', strtotime($app_res['recv_dt'])) : '',
      'due'     => ($app_res['stage'] == 'FP' && !empty($app_res['target_due_dt'])) ? date('d-m-Y', strtotime($app_res['target_due_dt'])) : date('d-m-Y', strtotime($app_res['due_dt'])),
      'pages'   => (string)$app_res['page_count']
    ), JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    function chip(label, val, hot) { return '<div class="sw-chip' + (hot ? ' hot' : '') + '"><small>' + label + '</small><b>' + esc(val || '-') + '</b></div>'; }
    $hero.append(
      '<div class="sw-summary"><div><small>Article ID</small><div class="sw-sum-id">#' + esc(SUM.id) + '</div>' +
      '<div class="sw-sum-sub">' + esc(SUM.client) + ' &nbsp;&middot;&nbsp; ' + esc(SUM.journal) + '</div></div>' +
      '<div class="sw-sum-chips">' + chip('Stage', SUM.stage) + chip('Department', SUM.dept) + chip('Received', SUM.recv) + chip('Due date', SUM.due, true) + chip('Pages', SUM.pages) + '</div></div>');

    /* info banner: what the next department will need from this one (re-checked on demand and when the tab regains focus) */
        function mfLink(r) {
      /* the Move Files pages are for the admin: the user reports the missing set-up (e-mail + log) instead of opening them */
      return '<a href="#" class="sw-mf-link sw-mf-report"><i class="fa fa-envelope"></i> Report to admin</a>';
    }
    $(document).on('click', '.sw-mf-report', function (e) {
      e.preventDefault();
      var $b = $(this); if ($b.hasClass('busy')) { return; }
      $b.addClass('busy').css('opacity', .6);
      $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_mf_alert: 1 } }).done(function (r) {
        alertBox(r && r.ok ? 'success' : 'error', r && r.ok ? (r.already ? 'Already reported' : 'Admin informed') : 'Not reported', (r && r.message) || 'Unknown response.');
        if (r && r.ok) { $b.replaceWith('<span class="sw-mf-link" style="background:#1e9e61;box-shadow:none;cursor:default;"><i class="fa fa-check"></i> Reported to admin</span>'); }
      }).fail(function () { alertBox('error', 'Not reported', 'The request failed. Please tell the admin directly.'); })
        .always(function () { $b.removeClass('busy').css('opacity', ''); });
    });
    function renderFiles(r) {
      $('#sw-files').remove();
      if (r && r.configured === false && (!r.checked || !r.checked.length)) {
        /* no Move Files set up for this journal / department / stage: say so (nothing will be moved on submit) */
        $hero.append('<div id="sw-files" class="sw-files warn" role="alert"><div class="sw-files-head"><span class="sw-files-ico">!</span><div style="flex:1">' +
          '<div class="sw-files-title">No file movement configured</div>' +
          '<div class="sw-files-sub">No files will be moved to the next department when you submit as <b>Completed</b> &ndash; file movement is not set up for <b>' + esc(SUM.stage) + '</b> / <b>' + esc(SUM.dept) + '</b> of journal <b>' + esc(SUM.journal) + '</b>. ' +
          'Copy the files yourself, or let the admin know so it can be set up:<br>' + mfLink(r) + '</div></div></div></div>');
        return;
      }
      if (!r || !r.checked || !r.checked.length) { return; }
      var miss = (r.problems || []).length, items = '', dbg = '', shown = {};
      $.each(r.checked, function (i, c) {
        var exts = $.map(c.exts, function (e) { return '<span class="sw-ext">.' + esc(e) + '</span>'; }).join('');
        items += '<li class="' + (c.found ? 'ok' : 'bad') + '"><span class="sw-mark">' + (c.found ? '&#10003;' : '&#10007;') + '</span>' +
                 '<span class="sw-what">Moving to <b>' + esc(c.dest) + '</b> needs one of</span> ' + exts +
                 '<span class="sw-state">' + (c.found ? 'found' : 'not found') + '</span></li>';
        if (!c.found && c.dir && !shown[c.dir]) {
          shown[c.dir] = 1;
          dbg += '<div class="sw-dbg">The server looked in <code>' + esc(c.dir) + '</code>' + (c.mapped ? ' <i>(mapped from <code>' + esc(c.asked) + '</code>)</i>' : '') + ' &ndash; ' +
                 (!c.exists ? '<b>it cannot see this folder</b> (wrong path, or the web server has no access to it).'
                            : (c.seen && c.seen.length ? 'folder found; files it saw: ' + esc(c.seen.join(', ')) + ' &ndash; none with the needed extension.' : 'folder found but it is empty.') +
                              (c.err ? ' (' + esc(c.err) + ')' : '')) + '</div>';
        }
      });
      var $b = $('<div id="sw-files" class="sw-files ' + (miss ? 'bad' : 'ok') + '" role="alert">' +
                 '<div class="sw-files-head"><span class="sw-files-ico">' + (miss ? '!' : '&#10003;') + '</span><div style="flex:1">' +
                 '<div class="sw-files-title">' + (miss ? 'Required files are missing' : 'Files are ready for the next department') + '</div>' +
                 '<div class="sw-files-sub">' + (miss ? 'You cannot submit as <b>Completed</b> until these files are in this department&#39;s folder.' : 'All required files were found. You can submit as <b>Completed</b>.') + '</div>' +
                 '</div>' + (miss ? '<a href="#" class="sw-recheck"><i class="fa fa-refresh"></i> Re-check</a>' : '') + '</div><ul>' + items + '</ul>' + (miss ? dbg : '') + '</div>');
      $hero.append($b);
      $b.find('.sw-recheck').on('click', function (e) { e.preventDefault(); var $a = $(this).addClass('busy'); runCheck().always(function () { $a.removeClass('busy'); }); });
    }
    function renderPkg(r) {
      $('#sw-pkg').remove();
      if (!r || !r.package) { return; }
      var p = r.package; window.__pkgBase = p.report_time || 0; var l = p.last, who = l ? ' &middot; last run: ' + esc(String(l.result).replace(/_/g, ' ')) + (l.user_name ? ' by ' + esc(l.user_name) : '') + ' on ' + esc(l.created_on) : '';
      $hero.append('<div id="sw-pkg" class="sw-files ' + (p.done ? 'ok' : 'bad') + '" role="alert"><div class="sw-files-head"><span class="sw-files-ico">' + (p.done ? '&#10003;' : '!') + '</span><div style="flex:1">' +
        '<div class="sw-files-title">' + (p.done ? 'Package is ready' : 'Package creation is pending') + '</div>' +
        '<div class="sw-files-sub">' + (p.done ? esc(p.label) + ' was built.' : 'Use the <b>Package creation</b> button above before you submit as <b>Completed</b>.') + who + '</div></div></div></div>');
    }
    function runCheck() {
      window.swRunCheck = runCheck;
      return checkFiles().done(function (r) { renderPkg(r); renderFiles(r); }).fail(function () {
        if (!$('#sw-files').length) {
          $hero.append('<div id="sw-files" class="sw-files" style="border-left-color:#d98a06;background:#fffaf0;border-color:#f3dfae;"><div class="sw-files-head"><span class="sw-files-ico" style="background:#d98a06;">?</span><div><div class="sw-files-title">Required files could not be checked</div><div class="sw-files-sub">The server will still check them when you submit as Completed.</div></div></div></div>');
        }
      });
    }
    runCheck();
    $(window).on('focus', function () { if ($('#sw-files').hasClass('bad') || $('#sw-files').hasClass('warn')) { runCheck(); } });   /* files copied into the folder while this tab was in the background */

    /* bootbox warnings (graphics / style validator / 3D-to-HTML ...) -> SweetAlert */
    if (window.bootbox && bootbox.dialog) {
      var origDialog = bootbox.dialog;
      bootbox.dialog = function (opts) {
        /* Only the page's own result boxes are converted: message + a single "button" (Close). bootbox.prompt / confirm / alert
           build their dialogs through this same function and must keep working - e.g. the "enter a reason" box of Bypass Validation. */
        var btns = opts && opts.buttons ? Object.keys(opts.buttons) : [];
        if (!opts || opts.title || btns.length !== 1 || btns[0] !== 'button') { return origDialog.apply(this, arguments); }
        /* the page's own messages say what they are in their markup: alert-success / alert-warning / alert-danger */
        var html = (opts && opts.message) || '', txt = $('<div>').html(html).text();
        var type = /alert-success/.test(html) ? 'success' : (/alert-danger|alert-error/.test(html) ? 'error' : 'warning');
        if (type === 'success' && !/completed/i.test(txt) && /not exist|not found|error|fail|invalid|missing|cannot|unable|no such/i.test(txt)) { type = 'warning'; }   /* a "success" box that reports a problem and never says completed */
        swal({ title: { success: 'Completed', warning: 'Please check', error: 'Error' }[type], html: html, type: type, confirmButtonText: 'Close',
               confirmButtonColor: type === 'success' ? '#1e9e61' : '#1b6ca8' });
        return { modal: function () {} };
      };
    }

    /* required-field errors -> one SweetAlert listing them */
    if ($.fn.validate) {
      /* the legacy page accepted a submit with no status chosen (it saved an empty status) - require one */
      if ($('#status').length) {
        $('#status').rules('add', { required: true, messages: { required: 'Please select a status.' } });
      }
      if ($('input[name=status]').length && !$('#status').length) {
        $('input[name=status]').first().rules('add', { required: true, messages: { required: 'Please select a status (Completed / Pending / Assigned / Others).' } });
      }
      if ($('#assign_department').length) {
        $('#assign_department').rules('add', { required: function () { return $('input[name=status]:checked').val() == '6'; },
          messages: { required: 'Please choose the department to assign to.' } });
      }

      {
        $('#validation-form').on('invalid-form.validate', function (e, validator) {
          $('#submit-btn').prop('disabled', false).show();   /* the page hides Submit on click; bring it back when validation fails */
          var lines = [];
          $.each(validator.errorList, function (i, er) {
            var NAMES = { status: 'Status', assign_department: 'Assigned department', page_count: 'Page count', corresponding_author: 'Corresponding author name', corresponding_mail: 'Corresponding author email', corresponding_other_author: 'Co-author email', article_title: 'Article title', comments: 'Remarks' };
            var pretty = function (n) { n = String(n || 'Field').replace(/\[.*$/, '').replace(/_/g, ' '); return n.charAt(0).toUpperCase() + n.slice(1); };
            var $el = $(er.element), label = NAMES[$(er.element).attr('name')] || $.trim($el.closest('.form-group,.col-12,.col-lg-6,div').find('label').first().text()).replace(/[:*]+$/, '') || pretty($el.attr('name'));
            lines.push('• ' + label + ' – ' + er.message);
          });
          alertBox('warning', 'Please complete the required fields', lines.join('\n'));
        });
      }
    }

    /* status radios: show the validation message under the whole group */
    var vld = $('#validation-form').data('validator');
    if (vld) {
      var oldPlace = vld.settings.errorPlacement;
      vld.settings.errorPlacement = function (error, element) {
        if (element.attr('name') === 'status' && element.is(':radio')) {
          var $grp = element.closest('.radio-group, .formgroup1');
          error.addClass('sw-status-err').insertAfter($grp.length ? $grp : element.parent());
        } else if (oldPlace) { oldPlace.apply(this, arguments); } else { error.insertAfter(element); }
      };
    }

    /* ---- tool buttons: loader + progress from the click until the server has answered ---- */
    var TOOLS = '#3dtohtml,#xmltohtml,#opfxmltohtml,#HTML_View,#tsp_fp_package,#eos_fp_package,#acg_fp_package,#testing_package,#htmlconversion,#ePub_Creation,#ePub_Validaion,' +
                '#rebuild_epub,#ePub_to_html,#xmlValidationButton,#QCValidationButton,#metaHtmlButton,#xmlCValidationButton,#ePub_validation_Creation,#htmlConversionButton,' +
                '#algorithmConversionButton,#TexprecleanConversionButton,#Author_Validaion,#styleValidatorButton,#bypassValidatorButton';
    var toolRun = null;
    /* every tool click is kept in inw_tool_run: who clicked, which server, whether it answered, the HTTP status and the first part of the answer */
    function swToolLog(tr) {
      var res = 'completed', st = 0, responded = 0, msg = '', server = '', args = '';
      $.each(tr.calls, function (i, c) {
        if (!server) { server = c.url; args = c.args; }
        if (c.status === 0 && c.statusText !== 'abort') { res = 'no_connection'; st = 0; msg = 'Server not connected (' + c.url + ')'; return false; }
        if (c.status >= 400) { res = 'failed'; st = c.status; msg = 'HTTP ' + c.status + ' - ' + c.text; return false; }
        responded = 1; st = c.status; msg = c.text || msg;
      });
      if (!tr.calls.length) { msg = 'No server call (opened a page or dialog)'; }
      if (res === 'no_connection') {
        alertBox('error', 'Server not connected', 'The server for "' + tr.label + '" could not be reached (' + (server.replace(/^https?:\/\//, '').split('/')[0] || 'unknown') + ').\nPlease check the network / VPN or tell IT, then try again.');
      }
      $.ajax({ type: 'POST', url: location.href.split('#')[0], global: false, data: { sw_tool_log: 1, tool: tr.id, label: tr.label, server: server, args: args, result: res, http_status: st, responded: responded, ms: Date.now() - tr.t0, message: msg } });
    }
    $(document).ajaxSend(function (e, x, s) { if (toolRun && !/sw_(pkg_log|tool_log|check_files|pkg_error)/.test(String(s.data || '') + String(s.url || ''))) { s._swT0 = Date.now(); } });
    $(document).ajaxComplete(function (e, x, s) {
      if (!toolRun || !s._swT0) { return; }
      var txt = ''; try { txt = $.trim($('<div>').html(String(x.responseText || '').slice(0, 3000)).text()).replace(/\s+/g, ' ').slice(0, 300); } catch (er) {}
      toolRun.calls.push({ url: String(s.url || ''), args: typeof s.data === 'string' ? s.data : '', status: x.status, statusText: x.statusText, text: txt, ms: Date.now() - s._swT0 });
    });
    function toolEnd() {
      if (!toolRun) { return; }
      clearTimeout(toolRun.t);
      toolRun.$b.removeClass('sw-tool-busy').css('display', '');
      var tr = toolRun; toolRun = null;
      loaderOff();
      swToolLog(tr);
    }
    $(document).on('click', TOOLS, function () {
      if (toolRun) { return; }
      var $b = $(this), label = $.trim($b.text()).replace(/\s+/g, ' ') || 'tool';
      toolRun = { $b: $b, started: false, id: $b.attr('id') || '', label: label, t0: Date.now(), calls: [] };
      $b.addClass('sw-tool-busy');
      setTimeout(function () { if ($b.css('display') === 'none') { $b.css('display', ''); } }, 0);   /* the page's own handlers hide the clicked button */
      loaderOn(label + ' in progress…');
      /* a button that never calls the server (a plain link, a dialog) must not leave the loader on */
      toolRun.t = setTimeout(function () { if (toolRun && !toolRun.started) { toolEnd(); } }, 1500);
    });
    $(document).ajaxSend(function () { if (toolRun) { toolRun.started = true; clearTimeout(toolRun.t); } });
    /* the server has answered, but the report it returned is rendered afterwards (large html, images, styles, frames) -
       keep the loader until that content has loaded and been painted, so it does not vanish while the page still looks empty */
    $(document).ajaxStop(function () {
      if (!toolRun || !toolRun.started) { return; }
      var t0 = Date.now(), run = toolRun;
      (function waitRender() {
        if (toolRun !== run) { return; }
        var pending = $('#result_qc').find('img,iframe,link[rel=stylesheet],script[src]').filter(function () {
          if (this.tagName === 'IMG') { return !this.complete; }
          if (this.tagName === 'IFRAME') { try { return this.contentDocument && this.contentDocument.readyState !== 'complete'; } catch (e) { return false; } }
          return false;
        }).length;
        if (pending && Date.now() - t0 < 60000) { setTimeout(waitRender, 250); return; }
        requestAnimationFrame(function () { requestAnimationFrame(function () { setTimeout(function () { if (toolRun === run) { toolEnd(); } }, 400); }); });
      })();
    });

    /* ---- opened start_work.php directly? say so, and point at the "Use old version" button ---- */
    (function () {
      var why = <?php echo json_encode(isset($_GET['sw_direct']) ? (string)$_GET['sw_direct'] : '', JSON_HEX_TAG | JSON_HEX_AMP); ?>;
      if (why !== 'url' && why !== 'noreason') { return; }
      try { history.replaceState(null, '', location.pathname + location.search.replace(/([?&])sw_direct=[^&]*&?/, '$1').replace(/[?&]$/, '')); } catch (e) {}   /* a refresh must not show it again */
      var $l = $('#sw-legacy');
      setTimeout(function () {
        swal({
          title: why === 'noreason' ? 'Please give a reason first' : 'You opened the old Start Work page directly',
          html: why === 'noreason'
            ? 'The old Start Work page can only be opened after you tell us why you need it. We have brought you to the new page.<br><br>Use the highlighted <b>Use old version</b> button at the top and enter your reason.'
            : 'The new Start Work page is now the standard one, so we have brought you here.<br><br>If you really need the old page, use the highlighted <b>Use old version</b> button at the top and tell us why.',
          type: 'warning', confirmButtonText: 'Show me', confirmButtonColor: '#1b6ca8', allowOutsideClick: false
        }).then(function () {
          $('html,body').animate({ scrollTop: 0 }, 250);
          $l.addClass('sw-hl'); setTimeout(function () { $l.removeClass('sw-hl'); }, 9000);
        }, function () {});
      }, 350);
    })();

    /* ---- Use old version: reason is mandatory (screenshots / files optional), it is e-mailed to the configured recipients and logged, then the old page opens ---- */
    $(document).on('click', '#sw-legacy', function (e) {
      e.preventDefault();
      if (busy) { return; }
      var LIM = <?php require_once __DIR__.'/sw_switch_lib.php'; echo json_encode(sw_switch_limits()); ?>,
          OK_EXT = <?php echo json_encode(sw_switch_allowed_ext()); ?>,
          picked = [], shot = 0;
      function mb(n) { return n < 1048576 ? Math.max(1, Math.round(n / 1024)) + ' KB' : (n / 1048576).toFixed(1) + ' MB'; }
      function render() {
        var $ul = $('.sw-att-list').empty();
        $.each(picked, function (i, f) {
          $ul.append($('<li></li>').append($('<i class="fa ' + (/^image\//.test(f.type) ? 'fa-file-image-o' : 'fa-file-o') + '"></i>'))
            .append($('<span class="n"></span>').text(f.name)).append($('<span class="z"></span>').text(mb(f.size)))
            .append($('<button type="button" class="x" title="Remove" data-i="' + i + '">&times;</button>')));
        });
        $('.sw-att-count').text(picked.length ? picked.length + ' of ' + LIM.files + ' attached' : '');
      }
      function addFiles(list) {
        var msg = '', total = 0; $.each(picked, function (i, f) { total += f.size; });
        $.each(list, function (i, f) {
          var name = f.name && f.name !== 'image.png' ? f.name : 'screenshot-' + (++shot) + '.png', ext = (name.split('.').pop() || '').toLowerCase();
          if (OK_EXT.indexOf(ext) < 0) { msg = '"' + name + '" is not an allowed type (images, PDF, Word, Excel, CSV, text, log, ZIP).'; return; }
          if (f.size > LIM.each) { msg = '"' + name + '" is larger than ' + mb(LIM.each) + '.'; return; }
          if (picked.length >= LIM.files) { msg = 'You can attach up to ' + LIM.files + ' files.'; return; }
          if (total + f.size > LIM.total) { msg = 'The attachments are too large together (limit ' + mb(LIM.total) + ').'; return; }
          try { if (f.name !== name) { f = new File([f], name, { type: f.type }); } } catch (er) {}
          picked.push(f); total += f.size;
        });
        render();
        if (msg) { swal.showValidationError(msg); } else { swal.resetValidationError(); }
      }
      swal({
        title: 'Switch to the old Start Work?',
        html: 'Please tell us what went wrong with the new page. Your note is sent to the support team so it can be fixed.',
        type: 'question', input: 'textarea', inputPlaceholder: 'Reason for switching (minimum 10 characters)…',
        showCancelButton: true, confirmButtonText: 'Send & switch', cancelButtonText: 'Stay here', confirmButtonColor: '#1b6ca8', allowOutsideClick: false,
        inputValidator: function (v) { return new Promise(function (res) { res($.trim(v).length < 10 ? 'Please enter at least 10 characters.' : undefined); }); },   /* swal 7: resolve(message) = show the error and stay open */
        onOpen: function (el) {
          var $el = $(el);
          $el.find('.swal2-textarea').after(
            '<div class="sw-att"><label class="sw-att-btn"><i class="fa fa-paperclip"></i> Choose file<input type="file" id="sw-att-in" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.log,.zip"></label>' +
            '<span class="sw-att-hint">or drop / paste a screenshot (Ctrl+V)</span><span class="sw-att-count"></span>' +
            '<div class="sw-att-lim">Optional &middot; up to ' + LIM.files + ' files &middot; ' + mb(LIM.each) + ' each</div><ul class="sw-att-list"></ul></div>');
          $el.on('change', '#sw-att-in', function () { addFiles(this.files); this.value = ''; });
          $el.on('click', '.sw-att-list .x', function () { picked.splice($(this).data('i'), 1); render(); swal.resetValidationError(); });
          $el.on('paste', function (ev) {
            var items = (ev.originalEvent.clipboardData || {}).items || [], got = [];
            for (var k = 0; k < items.length; k++) { if (items[k].kind === 'file') { var f = items[k].getAsFile(); if (f) { got.push(f); } } }
            if (got.length) { ev.preventDefault(); addFiles(got); }
          });
          $el.on('dragover', '.sw-att', function (ev) { ev.preventDefault(); $(this).addClass('over'); })
             .on('dragleave drop', '.sw-att', function () { $(this).removeClass('over'); })
             .on('drop', '.sw-att', function (ev) { ev.preventDefault(); addFiles(ev.originalEvent.dataTransfer.files); });
        }
      }).then(function (res) {
        /* swal 7.33 resolves with an object ({value} on confirm, {dismiss} on cancel); older builds give the text itself */
        if (res && typeof res === 'object' && res.dismiss) { return; }
        var reason = (res && typeof res === 'object') ? (res.value === undefined ? '' : res.value) : res;
        var fd = new FormData();
        fd.append('sw_switch_legacy', 1); fd.append('aid', <?php echo (int)$app_res['id']; ?>); fd.append('reason', $.trim(reason));
        $.each(picked, function (i, f) { fd.append('att[]', f, f.name); });
        busy = true; loaderOn(picked.length ? 'Sending your note and ' + picked.length + ' attachment' + (picked.length > 1 ? 's' : '') + '…' : 'Sending your note…');
        $.ajax({ url: location.pathname, type: 'POST', dataType: 'json', cache: false, data: fd, processData: false, contentType: false })
         .done(function (r) {
           if (r && r.ok) { loaderOn('Opening the old page…'); location.href = SET_URL + '/start_work.php?aid=<?php echo (int)$app_res['id']; ?>&tid=<?php echo rawurlencode(isset($_GET['tid']) ? $_GET['tid'] : 'new'); ?>&legacy=1'; return; }
           busy = false; loaderOff(); alertBox('warning', 'Could not switch', (r && r.message) || 'Please try again.');
         })
         .fail(function () { busy = false; loaderOff(); alertBox('error', 'Could not switch', 'The request failed (the attachments may be too large for the server). Please try again with smaller files.'); });
      }, function () {});
    });

    /* Cancel */
    $('#cancel-btn').on('click', function () {
      if (window.opener) { try { window.close(); } catch (e) {} }
      setTimeout(function () { location.href = SET_URL + '/currentwork'; }, 200);
    });

    /* any other ajax call on the page that fails */
    $(document).ajaxError(function (e, xhr, settings, err) {
      if (xhr.statusText === 'abort' || swal.isVisible()) { return; }
      alertBox('error', 'Request failed', xhr.status ? 'HTTP ' + xhr.status + ' ' + (err || '') : 'No response from the server. Please try again.');
    });

<?php if (isset($_REQUEST['msg']) && trim($_REQUEST['msg']) !== '') { ?>
    alertBox('error', 'Notice', <?php echo json_encode(trim(strip_tags($_REQUEST['msg']))); ?>);
<?php } ?>
  });
})(jQuery);
</script>

</body>
</html>
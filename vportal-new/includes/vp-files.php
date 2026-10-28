<?php
/* ============================================================================
 * Vendor portal Start Work v2: "required files" check for the file movement - the same rules as the main app's Start Work v2
 * (copied so the vendor portal stays self-contained).
 *
 * When an article is completed, the move step copies the files whose type is on the next department's accept list from this
 * department's folder. If the folder holds no such file, nothing is moved and the next department starts empty-handed.
 * sw_required_files() finds that out BEFORE anything is saved.
 * ========================================================================== */

/* Folder prefixes that are written one way in the article record (the path the production tools / users' PCs use, e.g. J:/JOURNAL)
 * but live somewhere else on the web server (E:/New_Workflow_Files/JOURNAL).  Apache cannot see the J: drive, so any server-side
 * is_dir() / file scan on such a path fails.  Add more pairs to sw_path_map() when another prefix turns up.
 * Slashes and the case of the drive letter do not matter. */
if (!function_exists('sw_path_map')) {
	function sw_path_map()
	{
		return array(
			'J:/' => 'E:/New_Workflow_Files/',
		);
	}
}

/* the folder as written first, then every mapped alternative */
if (!function_exists('sw_path_candidates')) {
	function sw_path_candidates($path)
	{
		$norm = str_replace('\\', '/', $path);
		$out  = array($path);
		foreach (sw_path_map() as $from => $to) {
			if (strncasecmp($norm, $from, strlen($from)) === 0) { $out[] = $to.substr($norm, strlen($from)); }
		}
		return $out;
	}
}

/* the first of those that exists on this server (the path as written when none does) */
if (!function_exists('wf_existing_dir')) {
	function wf_existing_dir($path)
	{
		foreach (sw_path_candidates($path) as $c) { if (@is_dir($c)) { return $c; } }
		return $path;
	}
}

require_once __DIR__ . '/../../includes/move_naming.php';
function sw_scan($dir, $exts, $rules = array(), $pub_id = '')
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
			if ($rules && !mfn_file_allowed($n, $rules, $pub_id)) { continue; }   /* Move Files naming rule of this extension */
			$out['found'] = true;
			return $out;
		}
	}
	return $out;
}
function sw_has_file($dir, $exts, $rules = array(), $pub_id = '') { $r = sw_scan($dir, $exts, $rules, $pub_id); return $r['found']; }

/* returns array('problems' => [text...], 'checked' => [[dest, exts, found]...]) */
function sw_required_files($db, $cust_id, $j_id, $dept_id, $stage, $pub_id, $work_dir, $volume_folder, $dept_name, $J_name, $wf_id = 0)
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
			|| ($cust_id==66 && $sf=='FIN' && $dept_name=='ePub' && (int)$wf_id==154)
			|| ($cust_id==42 && $sf=='FIN' && $dept_name=='XML' && (int)$wf_id==162))
		|| ($cust_id==53 && $dept_id==5);   /* NOW_Finals_ZIP.py builds the zip during the submit */

	$cur  = preg_replace('/[0-9]+/', '', $stage);
	$r    = $db->query("SELECT d.move_files, d.move_name_rules, d.dept_id, m.dept_name FROM `inw_move_files_dept_wise` d
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
		$rules = mfn_parse_rules($row['move_name_rules']);
		$scan  = sw_scan($src, $exts, $rules, $pub_id);
		$found = $scan['found'];
		$out['checked'][] = array('dest' => $row['dept_name'], 'exts' => $exts, 'rules' => mfn_rules_for_display($rules, $pub_id), 'found' => $found,
		                          'dir' => $scan['dir'], 'asked' => $src, 'mapped' => $scan['mapped'], 'exists' => $scan['exists'], 'seen' => $scan['seen'], 'err' => $scan['err']);
		if (!$found && !isset($seen[$key.'|'.$row['dept_name']])) {
			$seen[$key.'|'.$row['dept_name']] = 1;
			$out['problems'][] = "Moving to ".$row['dept_name']." needs a .".implode(' / .', $exts)." file".mfn_rule_hint($rules)." in the ".$stage."/".$dept_name." folder"
			                   . (is_dir($src) ? " (none found)" : " (the folder does not exist)");
		}
	}
	$out['configured'] = $configured;
	if (!$configured) { $out['mf_id'] = (int)$fe['mf_id']; $out += $ids; }
	return $out;
}

/* live check used by the page: POST sw_check_files=1 (id in the URL) -> JSON */
function vp_files_endpoint($db)
{
	if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_check_files']))) { return; }
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0));
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a) { echo json_encode(array('problems' => array('Article not found.'), 'checked' => array())); exit; }
	echo json_encode(vp_files_for_article($db, $a));
	exit;
}

/* the check for one article row (also used by the save, which repeats it on the server).
 * The folder checked is the one the vendor's uploads land in (do_upload_offline_process.php): WF_DIR/production/JOURNALS/<customer>/<journal>/<volume>/<article>/<stage>/CE/
 * so what the vendor uploaded is exactly what is validated - no drive letters or server paths are ever needed on the vendor's side. */
function vp_files_for_article($db, $a)
{
	if ($a['volume_folder'] == "Vol00000") { $a['volume_folder'] .= isset($a['issueorgroup_folder']) ? $a['issueorgroup_folder'] : ''; }
	$jr = $db->query("SELECT j_code FROM adm_journals WHERE j_id = " . (int)$a['j_id']); $jj = $jr ? $jr->fetch_assoc() : array('j_code' => '');
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']); $dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	$cr = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = " . (int)$a['cust_id']); $cc = $cr ? $cr->fetch_assoc() : array('cust_name' => '');
	$work_dir = $a['work_dir'];
	if (defined('WF_DIR') && $cc['cust_name'] !== '' && $jj['j_code'] !== '') { $work_dir = WF_DIR . "/production/JOURNALS/" . $cc['cust_name'] . "/" . $jj['j_code'] . "/"; }
	$res = sw_required_files($db, $a['cust_id'], $a['j_id'], $a['department_id'], $a['stage'], $a['pub_id'],
	                         $work_dir, $a['volume_folder'], $dd['dept_name'], $jj['j_code'], $a['wf_id']);
	/* what has been uploaded so far (newest first) */
	$src = $work_dir . $a['volume_folder'] . "/" . $a['pub_id'] . "/" . $a['stage'] . "/" . $dd['dept_name'] . "/";
	foreach (sw_path_candidates($src) as $cand) { if (@is_dir($cand)) { $src = $cand; break; } }
	$files = array();
	if (@is_dir($src)) {
		$stack = array(rtrim($src, "/\\")); $guard = 0;
		while ($stack && $guard++ < 500 && count($files) < 60) {
			$d = array_pop($stack);
			$items = @scandir($d); if ($items === false) { continue; }
			foreach ($items as $n) {
				if ($n === '.' || $n === '..') { continue; }
				$pth = $d . '/' . $n;
				if (@is_dir($pth)) { $stack[] = $pth; continue; }
				$files[] = array('name' => $n, 'size' => (int)@filesize($pth), 'time' => (int)@filemtime($pth));
			}
		}
		usort($files, function ($x, $y) { return $y['time'] - $x['time']; });
	}
	$res['uploaded'] = array_slice($files, 0, 30);
	$res['uploaded_total'] = count($files);
	return $res;
}

/* the server repeats the check when the status is Completed; returns true (and shows why) when the save must not go ahead */
function vp_files_blocked($db)
{
	if (!isset($_POST['status']) || (string)$_POST['status'] !== '1') { return false; }
	$id = (int)(isset($_POST['id']) ? $_POST['id'] : (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0));
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $id);
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a) { return false; }
	$res = vp_files_for_article($db, $a);
	if (empty($res['problems'])) { return false; }
	$msg = str_replace(array("'", chr(13), chr(10), chr(92)), array('', ' ', ' ', '/'), implode(' | ', $res['problems']));
	echo "<script>swal({title: 'Required files are missing', text: '" . $msg . " - add the file(s) to the department folder and submit again.', type: 'error', confirmButtonText: 'OK'});</script>";
	return true;
}

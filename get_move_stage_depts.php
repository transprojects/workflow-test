<?php
/* Departments of the workflow assigned to a journal, per stage (used by Add / Edit Move Files to show only those departments).
 * Returns {"FP":[2,1,5,...],"REV":[...], ...}; a stage with no workflow assigned is left out (the page then shows every department). */
include("includes/check.php");
header('Content-Type: application/json; charset=utf-8');
$stage_col = array('FP' => 'j_fp_wf', 'REV' => 'j_rev_wf', 'FIN' => 'j_fin_wf', 'PMC' => 'j_pmc_wf', 'JSTAGE' => 'j_jstage_wf', 'ISSCOR' => 'j_issue_wf', 'ISSFIN' => 'j_fin_issue_wf');
$out = array();
$names = array();
$j_id = (int)(isset($_REQUEST['j_id']) ? $_REQUEST['j_id'] : 0);
if ($j_id > 0) {
	$r = $db->query("SELECT * FROM adm_journals WHERE j_id = " . $j_id);
	$j = $r ? $r->fetch_assoc() : null;
	if ($j) {
		foreach ($stage_col as $stage => $col) {
			$name = isset($j[$col]) ? trim($j[$col]) : '';
			if ($name === '') { continue; }
			$w = $db->query("SELECT wf_name, wf_code FROM adm_workflow_master WHERE wf_name = '" . $db->real_escape_string($name) . "' ORDER BY wf_id DESC LIMIT 1");
			$w = $w ? $w->fetch_assoc() : null;
			if (!$w || trim($w['wf_code']) === '') { continue; }
			$out[$stage] = array_values(array_filter(array_map('intval', explode(',', $w['wf_code']))));
			$names[$stage] = $w['wf_name'];
		}
	}
}
/* workflow name per stage and department names, for the flow strip on the tabs */
$out['_names'] = $names;
$dn = array();
$r = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
if ($r) { while ($d = $r->fetch_assoc()) { $dn[(int)$d['dept_code']] = $d['dept_name']; } }
$out['_depts'] = $dn;
/* departments that already have a Move Files record for this journal (+ the record id) - the Add page greys them out */
$ex = array();
if ($j_id > 0) {
	$cust = (int)(isset($_REQUEST['cust_id']) ? $_REQUEST['cust_id'] : 0);
	$q = $db->query("SELECT mf_id, dept_id FROM inw_move_files WHERE j_id = " . $j_id . ($cust > 0 ? " AND cust_id = '" . $cust . "'" : ""));
	if ($q) { while ($d = $q->fetch_assoc()) { $ex[(int)$d['dept_id']] = (int)$d['mf_id']; } }
}
$out['_existing'] = $ex;
echo json_encode($out);

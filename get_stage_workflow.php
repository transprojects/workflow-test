<?php
/* Add Article: for a journal, which workflow belongs to each stage (FP / REV / FIN / JSTAGE) and the departments each workflow goes through.
   GET j_id -> {"stage":{"FP":"<wf_id>","REV":"..","FIN":".."},"wf":{"<wf_id>":{"name":"..","first":"<dept>","chain":["dept","dept",...]}}} */
include("includes/check.php");
header('Content-Type: application/json; charset=utf-8');
$j = $auth->showjournals((int)$_REQUEST['j_id']);
$names = array('FP' => isset($j[0]['j_fp_wf']) ? (string)$j[0]['j_fp_wf'] : '', 'REV' => isset($j[0]['j_rev_wf']) ? (string)$j[0]['j_rev_wf'] : '', 'FIN' => isset($j[0]['j_fin_wf']) ? (string)$j[0]['j_fin_wf'] : '', 'JSTAGE' => isset($j[0]['j_jstage_wf']) ? (string)$j[0]['j_jstage_wf'] : '');

$dn = array();
$r = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
while ($r && ($x = $r->fetch_assoc())) { $dn[(int)$x['dept_code']] = $x['dept_name']; }

$out = array('stage' => array('FP' => '', 'REV' => '', 'FIN' => '', 'JSTAGE' => ''), 'wf' => array());
foreach ($names as $stage => $name) {
	if ($name === '') { continue; }
	$r = $db->query("SELECT wf_id, wf_name, wf_code FROM adm_workflow_master WHERE wf_name = '".$db->real_escape_string($name)."' ORDER BY wf_id ASC LIMIT 1");
	if ($r && ($w = $r->fetch_assoc())) {
		$chain = array();
		foreach (explode(',', (string)$w['wf_code']) as $c) { $c = (int)trim($c); if ($c && isset($dn[$c])) { $chain[] = $dn[$c]; } }
		$out['stage'][$stage] = (string)$w['wf_id'];
		$out['wf'][(string)$w['wf_id']] = array('name' => $w['wf_name'], 'first' => $chain ? $chain[0] : '', 'chain' => $chain);
	}
}
echo json_encode($out);

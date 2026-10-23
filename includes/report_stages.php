<?php
/* ============================================================================
 * Stages shown in REPORTS - one place.
 *
 * $report_stage_types is the list every report follows: the stage filters of the older reports and the rows / cards / buttons
 * (and the numbers behind them) of the newer ones (WIP, Inflow, File flow).  Remove a stage here and it disappears from the
 * reports; add one and it appears (a new stage still needs its own counting query in the newer reports).
 * Small standalone file (no constants), so the report APIs can include it too; includes/global_variables.php includes it for the pages.
 * ========================================================================== */
if (!isset($report_stage_types)) {
	$report_stage_types = array('FP'=>'FirstProof','REV'=>'Revises','FIN'=>'Final','PMC'=>'PMC','ISSCOR'=>'Issue','ISSFIN'=>'Issue Final','JSTAGE'=>'JSTAGE');
}

/* the newer reports have one row per KIND of work: FP, REV, FIN, PMC, JSTAGE and ISS (issues = ISSCOR and / or ISSFIN) */
function rs_row_enabled($row)
{
	global $report_stage_types;
	if ($row === 'ISS') { return isset($report_stage_types['ISSCOR']) || isset($report_stage_types['ISSFIN']); }
	return isset($report_stage_types[$row]);
}

function rs_row_map()
{
	$m = array();
	foreach (array('FP', 'REV', 'FIN', 'PMC', 'JSTAGE', 'ISS') as $r) { $m[$r] = rs_row_enabled($r); }
	return $m;
}

/* Employee target / dashboard reports: stage columns FP, REV, CORR, FIN, ISS, JSTAGE (+ PRECLEAN for the CE team) follow the same array.
   CORR (corrections) and PRECLEAN are not stages of the list, so they always show. */
function rs_target_enabled($key)
{
	return ($key === 'CORR' || $key === 'PRECLEAN') ? true : rs_row_enabled($key);
}
/* drops the stages that are not in $report_stage_types from a stage => something array (keeps the order) */
function rs_filter_stages($arr)
{
	$out = array();
	foreach ($arr as $k => $v) { if (rs_target_enabled($k)) { $out[$k] = $v; } }
	return $out;
}

/* "wd.stage LIKE 'REV%' OR ..." for the combined REV / FIN queries, limited to what is enabled ('1=0' when neither is) */
function rs_rev_fin_cond($stage = 'ALL')
{
	$c = array();
	foreach (array('REV', 'FIN') as $s) {
		if (($stage === 'ALL' || $stage === $s) && rs_row_enabled($s)) { $c[] = "wd.stage LIKE '" . $s . "%'"; }
	}
	return $c ? '(' . implode(' OR ', $c) . ')' : '(1=0)';
}

<?php
/* Writes one row to article_assignment_log. Call it BEFORE the UPDATE that changes the owner,
   so the previous owner is still readable. $col is 'assigned_user_id' (CE/PE) or 'assigned_od_user_id' (PAG/PRQC...). */
function log_article_assignment($db, $inw_id, $new_user_id, $col, $dept_id, $action = 'assign')
{
	$inw_id = (int)$inw_id; $new_user_id = (int)$new_user_id;
	if ($inw_id <= 0 || $new_user_id <= 0) { return; }
	if ($col !== 'assigned_user_id' && $col !== 'assigned_od_user_id') { return; }
	$r = $db->query("SELECT pub_id, stage, $col AS prev FROM inw_inward_dtl WHERE id = $inw_id");
	$row = $r ? $r->fetch_assoc() : null;
	if (!$row) { return; }
	$prev = (int)$row['prev'] > 0 ? (int)$row['prev'] : 'NULL';
	$by = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
	$db->query("INSERT INTO article_assignment_log (inw_id,pub_id,stage,dept_id,assigned_user_id,prev_user_id,assigned_by,action,assigned_at) VALUES ("
		. $inw_id . ",'" . $db->real_escape_string((string)$row['pub_id']) . "','" . $db->real_escape_string((string)$row['stage']) . "'," . (int)$dept_id . ","
		. $new_user_id . "," . $prev . "," . ($by > 0 ? $by : 'NULL') . ",'" . $db->real_escape_string($action) . "','" . date('Y-m-d H:i:s') . "')");
}

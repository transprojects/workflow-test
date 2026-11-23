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

/* Logs that an owner was REMOVED (not replaced): action 'clear', assigned_user_id 0 (the column is NOT NULL), the removed user in prev_user_id.
   Call it BEFORE the update that empties the owner. Does nothing when the column is already empty. */
function log_article_clear($db, $inw_id, $col, $dept_id)
{
	$inw_id = (int)$inw_id;
	if ($inw_id <= 0 || ($col !== 'assigned_user_id' && $col !== 'assigned_od_user_id')) { return; }
	$r = $db->query("SELECT pub_id, stage, $col AS prev FROM inw_inward_dtl WHERE id = $inw_id");
	$row = $r ? $r->fetch_assoc() : null;
	if (!$row || (int)$row['prev'] <= 0) { return; }
	$by = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
	$db->query("INSERT INTO article_assignment_log (inw_id,pub_id,stage,dept_id,assigned_user_id,prev_user_id,assigned_by,action,assigned_at) VALUES ("
		. $inw_id . ",'" . $db->real_escape_string((string)$row['pub_id']) . "','" . $db->real_escape_string((string)$row['stage']) . "'," . (int)$dept_id . ",0,"
		. (int)$row['prev'] . "," . ($by > 0 ? $by : 'NULL') . ",'clear','" . date('Y-m-d H:i:s') . "')");
}

/* Called after an article's department changed from $from_dept to $to_dept (normal workflow move or Assign handoff).
   PRQC / Proofing: give it back to the user who handled it there before. PAG and PRQC share the one assigned_od_user_id column, so when the article moves between two
   od departments and nobody is remembered, the previous department's user is cleared - otherwise the old PAG user would still own the PRQC article and auto-assign (which only
   fills empty owners) would skip it. */
function am_on_dept_change($db, $inw_id, $from_dept, $to_dept)
{
	if ((string)$from_dept === (string)$to_dept) { return 0; }
	$u = am_reassign_previous_user($db, $inw_id, $to_dept);
	if ($u <= 0 && am_is_od($to_dept) && am_is_od($from_dept)) { log_article_clear($db, $inw_id, 'assigned_od_user_id', $to_dept); $db->query("UPDATE inw_inward_dtl SET assigned_od_user_id = NULL WHERE id = " . (int)$inw_id); am_record_clear($db, $inw_id, $to_dept); }
	return $u;
}

/* Departments whose allocation is stored in assigned_od_user_id (PAG, PRQC, PAG-ELX, PRQC-ELX); every other allocation department uses assigned_user_id. */
if (!function_exists('am_is_od')) {
	function am_is_od($dept) { return in_array((string)$dept, array('10','14','34','38'), true); }
}

/* When an article moves INTO PAG (10), PRQC (14) or Proofing (18), hand it to the user who handled it before - so the same person carries it through:
     PAG / PRQC : the user who last handled the article in that same department, the same stage first (an earlier stage only as a fallback).
     Proofing   : the user who handled the article in PRQC (same stage first), else its own previous Proofing user.
   "Handled" = the article's article_assignments rows, else its inw_transactions (who actually worked it), else article_assignment_log.
   The user must still be active and still have the target department (default or other department).
   Nothing is changed when no one is found; the article then waits for a manual Assign / Re-assign. Returns the user id or 0. */
if (!function_exists('am_reassign_previous_user')) {
	/* Read-only: who would the article go to if it moved into $to_dept now? (0 = nobody) */
	function am_find_previous_user($db, $inw_id, $to_dept)
	{
		$inw_id = (int)$inw_id; $to_dept = (int)$to_dept;
		if ($inw_id <= 0 || !in_array($to_dept, array(10, 14, 18), true)) { return 0; }   // PAG / PRQC / Proofing only; no other department
		$sr = $db->query("SELECT stage FROM inw_inward_dtl WHERE id = $inw_id"); $sx = $sr ? $sr->fetch_assoc() : null; $stg = $sx ? (string)$sx['stage'] : '';
		$sq = ($stg !== '') ? $db->real_escape_string($stg) : '';
		$src_depts = ($to_dept === 18) ? array(14, 18) : array($to_dept);   // Proofing: the PRQC user first
		$uid = 0;
		foreach ($src_depts as $sd) {
			$cand = am_latest_owner($db, $inw_id, $sd, $stg);   // article_assignments: same stage first, else the most recent row
			if ($cand <= 0) {
				$r = $db->query("SELECT process_user FROM inw_transactions WHERE project_id = $inw_id AND dept = $sd AND process_user > 0 ORDER BY " . ($sq !== '' ? "(current_stage = '$sq') DESC, " : '') . "id DESC LIMIT 1");
				if ($r && ($x = $r->fetch_assoc())) { $cand = (int)$x['process_user']; }
			}
			if ($cand > 0 && am_user_has_dept($db, $cand, $to_dept)) { $uid = $cand; break; }
			if ($cand <= 0) {
				$r = $db->query("SELECT assigned_user_id FROM article_assignment_log WHERE inw_id = $inw_id AND dept_id = $sd AND assigned_user_id > 0 ORDER BY id DESC LIMIT 5");
				while ($r && ($x = $r->fetch_assoc())) { if (am_user_has_dept($db, (int)$x['assigned_user_id'], $to_dept)) { $uid = (int)$x['assigned_user_id']; break; } }
				if ($uid > 0) { break; }
			}
		}
		return $uid;
	}

	function am_reassign_previous_user($db, $inw_id, $to_dept, $action = 'return')
	{
		$inw_id = (int)$inw_id; $to_dept = (int)$to_dept;
		$uid = am_find_previous_user($db, $inw_id, $to_dept);
		if ($uid <= 0) { return 0; }
		/* same columns the manual allocation uses: PAG / PRQC -> assigned_od_user_id / od_due_date, Proofing -> assigned_user_id / ce_pe_due_date */
		$col = am_is_od($to_dept) ? 'assigned_od_user_id' : 'assigned_user_id'; $dcol = am_is_od($to_dept) ? 'od_due_date' : 'ce_pe_due_date';
		log_article_assignment($db, $inw_id, $uid, $col, $to_dept, $action);
		$db->query("UPDATE inw_inward_dtl SET $col = $uid, $dcol = '" . date('Y-m-d') . "' WHERE id = $inw_id");
		am_record_assignment($db, $inw_id, $to_dept, $uid, $action, date('Y-m-d'));
		return $uid;
	}
	function am_user_has_dept($db, $user_id, $dept)
	{
		$user_id = (int)$user_id; $dept = (int)$dept;
		if ($user_id <= 0) { return false; }
		$r = $db->query("SELECT 1 FROM users WHERE id = $user_id AND approved = 1 AND banned = 0 AND (department_id = $dept OR FIND_IN_SET($dept, access_other_depts)) LIMIT 1");
		return ($r && $r->num_rows > 0);
	}
}

/* ===== article_assignments: one owner per article AND department (see sql/2026-11-23_article_assignments.sql) =====
   The old columns on inw_inward_dtl are still written by the callers; these functions keep the new table in step with them. They do nothing when the table
   has not been created yet, so the code can be deployed before the SQL. */
if (!function_exists('am_table_ready')) {
	function am_table_ready($db)
	{
		static $ok = null;
		if ($ok === null) { $r = @$db->query("SHOW TABLES LIKE 'article_assignments'"); $ok = ($r && $r->num_rows > 0); }
		return $ok;
	}

	/* $user_id becomes the active owner of the article for $dept_id. The previous active owner of that department (if any) is ended as 'replaced'.
	   Call it right after the UPDATE that writes the owner column. $due is 'Y-m-d' or null. */
	function am_record_assignment($db, $inw_id, $dept_id, $user_id, $source = 'assign', $due = null)
	{
		$inw_id = (int)$inw_id; $dept_id = (int)$dept_id; $user_id = (int)$user_id;
		if ($inw_id <= 0 || $dept_id <= 0 || $user_id <= 0 || !am_table_ready($db)) { return; }
		$cur = $db->query("SELECT id, user_id FROM article_assignments WHERE inw_id = $inw_id AND dept_id = $dept_id AND active_key = 1 LIMIT 1");
		$c = $cur ? $cur->fetch_assoc() : null;
		if ($c && (int)$c['user_id'] === $user_id) {   // same owner: only the due date may change
			if ($due) { $db->query("UPDATE article_assignments SET due_date = '" . $db->real_escape_string($due) . "' WHERE id = " . (int)$c['id']); }
			return;
		}
		if ($c) { $db->query("UPDATE article_assignments SET ended_at = NOW(), end_reason = 'replaced', active_key = NULL WHERE id = " . (int)$c['id']); }
		$st = $db->query("SELECT stage FROM inw_inward_dtl WHERE id = $inw_id"); $sr = $st ? $st->fetch_assoc() : null;
		$by = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
		$db->query("INSERT INTO article_assignments (inw_id,dept_id,stage,user_id,due_date,source,assigned_by,assigned_at,active_key) VALUES ($inw_id,$dept_id,"
			. ($sr ? "'" . $db->real_escape_string((string)$sr['stage']) . "'" : 'NULL') . ",$user_id," . ($due ? "'" . $db->real_escape_string($due) . "'" : 'NULL') . ",'"
			. $db->real_escape_string($source) . "'," . ($by > 0 ? $by : 'NULL') . ",NOW(),1)");
	}

	/* The active owner of the article for that department is removed (kept as history, ended as 'cleared'). */
	function am_record_clear($db, $inw_id, $dept_id)
	{
		$inw_id = (int)$inw_id; $dept_id = (int)$dept_id;
		if ($inw_id <= 0 || $dept_id <= 0 || !am_table_ready($db)) { return; }
		$db->query("UPDATE article_assignments SET ended_at = NOW(), end_reason = 'cleared', active_key = NULL WHERE inw_id = $inw_id AND dept_id = $dept_id AND active_key = 1");
	}

	/* The latest user the article had in that department (active first, otherwise the most recent ended row), or 0. */
	function am_latest_owner($db, $inw_id, $dept_id, $stage = '')
	{
		if (!am_table_ready($db)) { return 0; }
		$pref = ($stage !== '') ? "(stage = '" . $db->real_escape_string($stage) . "') DESC, " : '';   // the user from the same stage wins, an earlier stage is the fallback
		$r = $db->query("SELECT user_id FROM article_assignments WHERE inw_id = " . (int)$inw_id . " AND dept_id = " . (int)$dept_id . " ORDER BY $pref(active_key IS NULL), id DESC LIMIT 1");
		$x = $r ? $r->fetch_assoc() : null;
		return $x ? (int)$x['user_id'] : 0;
	}
}

<?php
/* Current Work / Added Articles: the working status shown in the list comes from the LATEST row of inw_transactions
 * for the article + stage + current department, not from the stored inw_inward_dtl.user_working_status label (which can be stale).
 * The label keeps its look ("<dept>|<user>|<status>") and all three parts come from live data: the article's current department name,
 * the user of that transaction, and the transaction's status. A label without a status chip
 * (article waiting in a department, Client Review, Delivery ...) is returned unchanged. When $dept (the article's current department) is given,
 * only transactions of that department count, so an article that just moved to a new department does not show the previous department's "Completed". */
function tx_status_label($inw_id, $stage, $label, $dept = null)
{
	global $db;
	static $dept_names = null;
	$label = (string)$label;
	if ($label === '' || strpos($label, 'label-warning') === false || !isset($db) || !$db) { return $label; }
	/* special labels whose last chip is not a work status: "Assigned By <user>" and the Query label (its last chip is a department name) */
	/* "Assigned" is written when the article is (re)assigned to a user and no transaction exists for that assignment yet; an older Completed transaction (e.g. the pre-editing round) must not override it */
	if (strpos($label, 'label-warning">Assigned<') !== false) { return $label; }
	if (strpos($label, 'label-warning">Assigned By') !== false || strpos($label, 'label-success">Query<') !== false) { return $label; }
	$r = @$db->query("SELECT t.current_status, u.full_name, u.user_name FROM `inw_transactions` t LEFT JOIN `users` u ON u.id = t.process_user WHERE t.`project_id` = '" . (int)$inw_id . "' AND t.`stage` = '" . $db->real_escape_string((string)$stage) . "'" . ($dept !== null && $dept !== '' ? " AND t.`dept` = '" . (int)$dept . "'" : "") . " ORDER BY t.`id` DESC LIMIT 1");
	$t = $r ? $r->fetch_assoc() : null;
	if (!$t || trim((string)$t['current_status']) === '') { return $label; }
	/* another department may have worked on the article after its owner department's last row (e.g. PRQC assigns to PAG for correction, PAG
	 * submits "Correction Done" while the article stays with PRQC): a newer row of the same stage in a different department wins for user + status, the chip keeps the article's own department (same as the old workflow list: PRQC | Selvamani | Correction Done). Graphics (dept 9) rows are ignored here: the old project_fig_work.php keeps them in graphic_working_status, not in the list label */
	if ($dept !== null && $dept !== '') {
		$r2 = @$db->query("SELECT t.id, t.dept, t.current_status, u.full_name, u.user_name FROM `inw_transactions` t LEFT JOIN `users` u ON u.id = t.process_user WHERE t.`project_id` = '" . (int)$inw_id . "' AND t.`stage` = '" . $db->real_escape_string((string)$stage) . "' AND t.`dept` <> 9 ORDER BY t.`id` DESC LIMIT 1");
		$n =$r2 ? $r2->fetch_assoc() : null;
		if ($n && (int)$n['dept'] !== (int)$dept && trim((string)$n['current_status']) !== '') {
			$r3 = @$db->query("SELECT MAX(id) AS mid FROM `inw_transactions` WHERE `project_id` = '" . (int)$inw_id . "' AND `stage` = '" . $db->real_escape_string((string)$stage) . "' AND `dept` = '" . (int)$dept . "'");
			$m = $r3 ? $r3->fetch_assoc() : null;
			if ($m && (int)$n['id'] > (int)$m['mid']) { $t = $n; }
		}
	}
	if ($dept_names === null) {
		$dept_names = array();
		$q = @$db->query("SELECT dept_code, dept_name FROM adm_dept_master");
		if ($q) { while ($d = $q->fetch_assoc()) { $dept_names[(int)$d['dept_code']] = $d['dept_name']; } }
	}
	$dn = ($dept !== null && $dept !== '' && isset($dept_names[(int)$dept])) ? $dept_names[(int)$dept] : '';
	$user = trim((string)($t['full_name'] !== null && $t['full_name'] !== '' ? $t['full_name'] : $t['user_name']));
	if ($dn === '') { /* department unknown: keep the stored department chip */
		if (preg_match('/^(<span class="label label-sm label-success">.*?<\/span>)/', $label, $m)) { $dnh = $m[1]; } else { $dnh = ''; }
	} else {
		$dnh = '<span class="label label-sm label-success">' . htmlspecialchars($dn) . '</span>';
	}
	return $dnh . '|<span class="label label-sm label-info">' . htmlspecialchars($user) . '</span>|<span class="label label-sm label-warning">' . htmlspecialchars($t['current_status']) . '</span>';
}

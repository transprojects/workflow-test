<?php include "includes/includes.php";
require_once __DIR__.'/edit_log_lib.php';

/* ============================================================================
 * Job card - tabbed page.
 *
 * jobcard.php?id=N              -> page shell (header + tab bar, no heavy data)
 * jobcard.php?id=N&ajax=1&tab=K -> HTML fragment for one tab, fetched on click
 *                                  (K = main | rev-<r_id> | corr-<ic_id>)
 *
 * Opening the page now costs a handful of small queries; the transactions,
 * dispatch history and users are only loaded when a tab is opened, and each
 * tab is loaded once and kept.
 * ========================================================================== */
/* ============================================================================
 * Job card helpers
 *
 * The shell only needs the article / customer / journal rows plus the list of
 * revisions and corrections (for the tab labels).  The heavy part - every
 * transaction, dispatch row and user - is loaded by jc_load_details() and only
 * when a tab is actually opened.
 * ========================================================================== */

function tvl_stage_key($stage)
{
	return strtolower(trim((string)$stage));
}
function tvl_has_end($row)
{
	return $row['end_time'] !== null && $row['end_time'] !== '';
}
function tvl_dept_name($code)
{
	global $DEPT_MAP;
	$k = (int)$code;
	return isset($DEPT_MAP[$k]) ? $DEPT_MAP[$k] : '';
}
function tvl_user($id)
{
	global $USER_MAP;
	return isset($USER_MAP[$id]) ? $USER_MAP[$id] : array('full_name' => '', 'user_name' => '');
}
function tvl_stage_rows($stage)
{
	global $TX_BY_STAGE;
	$k = tvl_stage_key($stage);
	return isset($TX_BY_STAGE[$k]) ? $TX_BY_STAGE[$k] : array();
}
/* rows that the old "t, users u, adm_dept_master dm" inner joins would have kept */
function tvl_joined_rows($stage, $need_dept = false, $need_end = false)
{
	$out = array();
	foreach (tvl_stage_rows($stage) as $t) {
		if ($t['u_ok'] === null || $t['dm_ok'] === null) { continue; }
		if ($need_dept && $t['dept'] == '') { continue; }
		if ($need_end && !tvl_has_end($t)) { continue; }
		$out[] = $t;
	}
	return $out;
}
function tvl_dispatch($stage)
{
	global $DISP_BY_STAGE;
	$k = tvl_stage_key($stage);
	if (!isset($DISP_BY_STAGE[$k])) { return array(0, array('sent_date' => null, 'sent_by' => null)); }
	return array($DISP_BY_STAGE[$k]['rows'], $DISP_BY_STAGE[$k]['first']);
}

/* department summary (header cells + body cells) for one stage */
function tvl_dept_summary($stage)
{
	$groups = array();
	foreach (tvl_stage_rows($stage) as $t) {
		if ($t['dept'] === '' || $t['dept'] === null) { continue; }
		$groups[(int)$t['dept']][] = $t;
	}
	ksort($groups);
	$head = '';
	$body = '';
	foreach ($groups as $dept => $rows) {
		$head .= '<th class="center">'.tvl_dept_name($dept).'</th>';
		$minutes = 0;
		$users = array();
		foreach ($rows as $t) {
			if (tvl_has_end($t) && $t['start_time'] !== null) {
				$s = strtotime($t['end_time']) - strtotime($t['start_time']);
				$minutes += ($s < 0 ? -1 : 1) * (int)floor(abs($s) / 60);
			}
			$users[$t['process_user']] = true;
		}
		$hours = (int)floor($minutes / 60);
		$body .= '<td class="center" style="font-weight:bold;">'.sprintf('%02d Hours %02d  Minutes', $hours, $minutes - $hours * 60).'<br/><br/>';
		$usno = 1;
		foreach ($users as $uid => $_) {
			$u = tvl_user($uid);
			$body .= $usno.")".ucwords($u['full_name'])."<br/>";
			$usno++;
		}
		$body .= '</td>';
	}
	return array($head, $body);
}

function differenceInHours($startdate, $enddate)
{
	$interval = (new DateTime($startdate))->diff(new DateTime($enddate));
	return $interval->format('%h')." Hours ".$interval->format('%i')." Minutes ".$interval->format('%s')." Seconds";
}
function getHoursMinutes($seconds, $format = '%02d:%02d')
{
	if (empty($seconds) || !is_numeric($seconds)) { return false; }
	$minutes = round($seconds / 60);
	$hours = floor($minutes / 60);
	return sprintf($format, $hours, ($minutes % 60));
}
function dateDiffInDays($date1, $date2)
{
	return abs(round((strtotime($date2) - strtotime($date1)) / 86400));
}

/* ---------------------------------------------------------------------------
 * Loaders
 * ------------------------------------------------------------------------- */

/* Cheap part: everything the page shell needs. */
function jc_load_article($db, $job_id)
{
	global $app_res, $customer_info, $journal_info, $REVS, $CORRS, $DRAFTS;
	$app_res = null; $customer_info = array('cust_name' => ''); $journal_info = array('j_name' => '', 'j_code' => '');
	$REVS = array(); $CORRS = array(); $DRAFTS = array();
	if ($job_id <= 0) { return false; }

	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$job_id);
	if (!$r || $r->num_rows == 0) { return false; }
	$app_res = $r->fetch_assoc();

	$r = $db->query("SELECT cust_name, cust_id, id FROM `adm_customer_master` WHERE id = ".(int)$app_res['cust_id']);
	if ($r && ($x = $r->fetch_assoc())) { $customer_info = $x; }
	$r = $db->query("SELECT `j_id`, `j_name`, j_code FROM `adm_journals` WHERE j_id = ".(int)$app_res['j_id']);
	if ($r && ($x = $r->fetch_assoc())) { $journal_info = $x; }

	$r = $db->query("SELECT * FROM `inw_revisions_dtl` WHERE inw_id = ".$job_id." ORDER BY `r_id` ASC");
	while ($d = $r->fetch_assoc()) { $REVS[$d['r_id']] = $d; }

	if ((int)$app_res['v_issue_id'] > 0) {
		$r = $db->query("SELECT * FROM `inw_issue_corrections` WHERE vi_id = ".(int)$app_res['v_issue_id']." ORDER BY `ic_id` ASC");
		while ($d = $r->fetch_assoc()) { $CORRS[$d['ic_id']] = $d; }
	}

	/* issue draft rounds (DRAFT1, DRAFT2 ...) booked from issue_draft_rebook.php; the table exists once the first round has been booked */
	$chk = $db->query("SHOW TABLES LIKE 'inw_issue_article_drafts'");
	if ($chk && $chk->num_rows > 0) {
		$r = $db->query("SELECT * FROM `inw_issue_article_drafts` WHERE article_id = ".$job_id." ORDER BY draft_no ASC");
		$prev = null;
		while ($r && ($d = $r->fetch_assoc())) {
			$d['next_on'] = null;
			$DRAFTS[$d['draft_id']] = $d;
			if ($prev !== null) { $DRAFTS[$prev]['next_on'] = $d['created_on']; }   // a round ends when the next one is booked
			$prev = $d['draft_id'];
		}
	}
	return true;
}

/* Heavy part: transactions, dispatch history, users, departments. */
function jc_load_details($db, $job_id)
{
	global $app_res, $REVS, $DRAFTS, $DEPT_MAP, $TX_BY_STAGE, $DISP_BY_STAGE, $USER_MAP, $EDIT_BY_TX, $EDIT_LIST;
	$DEPT_MAP = array(); $TX_BY_STAGE = array(); $DISP_BY_STAGE = array(); $USER_MAP = array(); $EDIT_BY_TX = array(); $EDIT_LIST = array();
	/* what each Edit Article save changed (edit_log_lib.php); the table exists once the first edit has been saved */
	$chk = $db->query("SHOW TABLES LIKE 'article_edit_log'");
	if ($chk && $chk->num_rows > 0) {
		$er = $db->query("SELECT * FROM article_edit_log WHERE article_id = ".(int)$job_id." ORDER BY id ASC");
		while ($er && ($e = $er->fetch_assoc())) { $e['list'] = json_decode($e['changes'], true); if (!is_array($e['list'])) { $e['list'] = array(); } $EDIT_LIST[] = $e; if ((int)$e['transaction_id'] > 0) { $EDIT_BY_TX[(int)$e['transaction_id']] = $e; } }
	}
	$user_ids = array((int)$app_res['created_by'] => true);

	$r = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
	while ($d = $r->fetch_assoc()) { $DEPT_MAP[(int)$d['dept_code']] = $d['dept_name']; }

	$r = $db->query("SELECT t.*, u.id AS u_ok, u.user_name, u.full_name, dm.dept_code AS dm_ok, dm.dept_name
	                 FROM inw_transactions AS t
	                 LEFT JOIN users AS u ON t.process_user = u.id
	                 LEFT JOIN adm_dept_master AS dm ON t.dept = dm.dept_code
	                 WHERE t.project_id = ".$job_id."
	                 ORDER BY t.created_dt ASC, t.id ASC");
	while ($t = $r->fetch_assoc()) {
		$TX_BY_STAGE[tvl_stage_key($t['stage'])][] = $t;
		$user_ids[(int)$t['process_user']] = true;
	}

	$r = $db->query("SELECT * FROM inw_dispatch_history WHERE a_id = ".$job_id);
	while ($d = $r->fetch_assoc()) {
		$k = tvl_stage_key($d['stage']);
		$DISP_BY_STAGE[$k]['rows'] = isset($DISP_BY_STAGE[$k]['rows']) ? $DISP_BY_STAGE[$k]['rows'] + 1 : 1;
		if (!isset($DISP_BY_STAGE[$k]['first'])) { $DISP_BY_STAGE[$k]['first'] = $d; }
		$user_ids[(int)$d['sent_by']] = true;
	}
	foreach ($REVS as $d) { $user_ids[(int)$d['created_by']] = true; }
	foreach ($DRAFTS as $d) { $user_ids[(int)$d['created_by']] = true; }

	$r = $db->query("SELECT id, full_name, user_name FROM users WHERE id IN (".implode(',', array_keys($user_ids)).")");
	while ($u = $r->fetch_assoc()) { $USER_MAP[$u['id']] = $u; }
}

/* Stage the first ("main") tab reports on - same rule the page always used. */
function jc_main_stage()
{
	global $app_res;
	return count(tvl_joined_rows('FP')) > 1 ? 'FP' : $app_res['stage'];
}

/* ---------------------------------------------------------------------------
 * Rendering
 * ------------------------------------------------------------------------- */

function jc_dur($seconds)
{
	$s = max(0, (int)$seconds);
	return sprintf('%02dh %02dm %02ds', floor($s / 3600), floor(($s % 3600) / 60), $s % 60);
}
function jc_hm($minutes)
{
	$m = max(0, (int)$minutes);
	return sprintf('%02dh %02dm', floor($m / 60), $m % 60);
}
function jc_initials($name)
{
	$p = preg_split('/\s+/', trim((string)$name));
	$i = '';
	foreach (array_slice($p, 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); }
	return $i !== '' ? $i : '?';
}
function jc_status_class($text)
{
	$t = strtolower($text);
	if (strpos($t, 'complet') !== false || strpos($t, 'done') !== false) { return 'ok'; }
	if (strpos($t, 'progress') !== false || strpos($t, 'assign') !== false) { return 'info'; }
	if (strpos($t, 'query') !== false || strpos($t, 'clarif') !== false) { return 'bad'; }
	if (strpos($t, 'pending') !== false || strpos($t, 'break') !== false || strpos($t, 'cross') !== false) { return 'warn'; }
	return 'muted';
}

function jc_user_label($id)
{
	$u = tvl_user($id);
	if (trim($u['full_name']) === '' && trim($u['user_name']) === '') { return '<span class="jc-na">&ndash;</span>'; }
	return '<span class="jc-person"><span class="jc-avatar">'.jc_initials($u['full_name']).'</span>'
	     . ucwords($u['full_name']).' <small>('.ucwords($u['user_name']).')</small></span>';
}

/* One label / value row inside an info card. */
function jc_kv($rows)
{
	$h = '';
	foreach ($rows as $k => $v) {
		if ($v === null || $v === '') { $v = '<span class="jc-na">&ndash;</span>'; }
		$h .= '<div class="jc-kv"><span>'.$k.'</span><b>'.$v.'</b></div>';
	}
	return $h;
}

function jc_uploaded_cell($count, $disp, $duedate)
{
	if ($count <= 0) { return '<span class="jc-pill muted">Not uploaded</span>'; }
	$delivery = date('d-m-Y', strtotime($disp['sent_date']));
	$del_time = strtotime($delivery);
	$due_time = strtotime($duedate);
	if ($del_time == $due_time)    { $cls = 'info'; $lbl = 'On time'; }
	elseif ($del_time > $due_time) { $cls = 'bad';  $lbl = 'Delay'; }
	else                           { $cls = 'ok';   $lbl = 'Ahead'; }
	$days = dateDiffInDays($delivery, $duedate);
	return $delivery.' <span class="jc-pill '.$cls.'">'.$lbl.($days ? ' &middot; '.$days.' day'.($days > 1 ? 's' : '') : '').'</span>';
}

/* Department cards for one stage. Returns [html, departments, people]. */
function jc_dept_cards($stage)
{
	$groups = array();
	foreach (tvl_stage_rows($stage) as $t) {
		if ($t['dept'] === '' || $t['dept'] === null) { continue; }
		$groups[(int)$t['dept']][] = $t;
	}
	ksort($groups);
	if (!$groups) { return array('<div class="jc-empty">No department activity recorded for this stage.</div>', 0, 0); }

	$all_users = array();
	$h = '<div class="jc-depts">';
	foreach ($groups as $dept => $rows) {
		$minutes = 0; $users = array();
		foreach ($rows as $t) {
			if (tvl_has_end($t) && $t['start_time'] !== null) {
				$s = strtotime($t['end_time']) - strtotime($t['start_time']);
				$minutes += ($s < 0 ? -1 : 1) * (int)floor(abs($s) / 60);
			}
			$users[$t['process_user']] = true;
			$all_users[$t['process_user']] = true;
		}
		$h .= '<div class="jc-dept"><div class="jc-dept-name">'.tvl_dept_name($dept).'</div>'
		   .  '<div class="jc-dept-time"><i class="fa fa-clock-o"></i> '.jc_hm($minutes).'</div><div class="jc-dept-users">';
		foreach ($users as $uid => $_) {
			$u = tvl_user($uid);
			$h .= '<span class="jc-chip"><span class="jc-avatar sm">'.jc_initials($u['full_name']).'</span>'.ucwords($u['full_name']).'</span>';
		}
		$h .= '</div></div>';
	}
	return array($h.'</div>', count($groups), count($all_users));
}

/* Transaction table. Returns [html, seconds, rows]. */
/* "View changes (n)" - the pop-up reads the change list from the link itself */
function jc_edit_link($e, $label = null)
{
	$n = (int)$e['change_count'];
	return '<a href="#" class="jc-chg" data-by="'.htmlspecialchars($e['user_name'], ENT_QUOTES).'" data-at="'.htmlspecialchars(date('d-m-Y h:i A', strtotime($e['created_at'])), ENT_QUOTES).'" data-n="'.$n.'" data-c="'.htmlspecialchars(json_encode($e['list']), ENT_QUOTES).'">'
	     . '<i class="fa fa-pencil-square-o"></i> '.($label !== null ? $label : 'View changes ('.$n.')').'</a>';
}

function jc_tx_table($rows, $highlight_dept16 = false, $round_of = null)
{
	global $project_status_array, $EDIT_BY_TX, $EDIT_LIST;
	if (!$rows) { return array('<div class="jc-empty">No transactions recorded for this stage.</div>', 0, 0); }
	$sno = 0; $diff = 0; $h = '';
	foreach ($rows as $row) {
		/* the row an Edit Article save writes (department reassignment) is listed under "Article edits" instead */
		if (isset($EDIT_BY_TX[(int)$row['id']]) || (!empty($EDIT_LIST) && in_array(trim($row['comments']), array(EL_DEPT_LABEL, EL_DEPT_LABEL_OLD, EL_DEPT_LABEL_OLD2), true))) { continue; }
		$sno++;
		$assigndept = ($row['assign_department'] != 0) ? tvl_dept_name($row['assign_department']) : '';
		$secs = null;
		if (tvl_has_end($row)) { $secs = strtotime($row['end_time']) - strtotime($row['start_time']); $diff += $secs; }
		$st = isset($project_status_array[$row['completion_status']]) ? $project_status_array[$row['completion_status']] : '';

		$h .= '<tr'.(($highlight_dept16 && $row['dept'] == 16) ? ' class="jc-hl"' : '').'>'
		   .  '<td class="c">'.$sno.'</td>'
		   .  ($round_of ? '<td class="nw">'.$round_of($row).'</td>' : '')
		   .  '<td><span class="jc-tag">'.$row['dept_name'].'</span></td>'
		   .  '<td><span class="jc-person"><span class="jc-avatar sm">'.jc_initials($row['full_name']).'</span>'.ucwords($row['full_name']).' <small>('.ucwords($row['user_name']).')</small></span></td>'
		   .  '<td class="nw">'.date("d-m-Y", strtotime($row['start_time'])).(tvl_has_end($row) ? ' <i class="fa fa-long-arrow-right"></i> '.date("d-m-Y", strtotime($row['end_time'])) : '')
		   .  '<br/><small>'.date("h:i:s a", strtotime($row['start_time'])).(tvl_has_end($row) ? ' &ndash; '.date("h:i:s a", strtotime($row['end_time'])) : ' &ndash; running').'</small></td>'
		   .  '<td class="nw">'.($secs !== null ? '<span class="jc-dur"><i class="fa fa-clock-o"></i> '.jc_dur($secs).'</span>' : '<span class="jc-na">&ndash;</span>').'</td>'
		   .  '<td>'.($st !== '' ? '<span class="jc-pill '.jc_status_class($st).'">'.$st.'</span>' : '<span class="jc-na">&ndash;</span>').'</td>'
		   .  '<td class="cm">'.str_replace(array(EL_DEPT_LABEL_OLD, EL_DEPT_LABEL_OLD2), EL_DEPT_LABEL, $row['comments'])
		   .  (isset($EDIT_BY_TX[(int)$row['id']]) ? '<br>'.jc_edit_link($EDIT_BY_TX[(int)$row['id']]) : '').'</td>'
		   .  '<td>'.($assigndept !== '' ? '<span class="jc-tag alt">'.$assigndept.'</span>' : '<span class="jc-na">&ndash;</span>').'</td></tr>';
	}
	if ($sno === 0) { return array('<div class="jc-empty">No transactions recorded for this stage.</div>', 0, 0); }
	return array('<div class="jc-table-wrap"><table class="jc-table"><thead><tr>
	<th class="c">#</th>'.($round_of ? '<th>Round</th>' : '').'<th>Dept</th><th>Name</th><th>Date &amp; time</th><th>Time spent</th><th>Status</th><th>Comments</th><th>Assigned dept</th>
	</tr></thead><tbody>'.$h.'</tbody></table></div>', $diff, $sno);
}

function jc_stat($icon, $label, $value, $cls = '')
{
	return '<div class="jc-stat '.$cls.'"><i class="fa '.$icon.'"></i><div><span>'.$label.'</span><b>'.$value.'</b></div></div>';
}

/* Whole tab body. $stats: [total seconds or null, entries]. */
function jc_card($stage, $date, $left, $right, $stage_for_depts, $tx, $with_depts = true)
{
	global $customer_info, $journal_info, $app_res;
	list($tx_html, $diff, $n_rows) = $tx;
	$dept_html = ''; $n_dept = 0; $n_people = 0;
	if ($with_depts) { list($dept_html, $n_dept, $n_people) = jc_dept_cards($stage_for_depts); }

	$o  = '<div class="jc-card">';
	$o .= '<div class="jc-card-head"><div><span class="jc-stage">'.$stage.'</span><h2>Job Card</h2>'
	   .  '<p>'.$customer_info['cust_name'].' &nbsp;&middot;&nbsp; '.$journal_info['j_name'].'</p></div>'
	   .  '<div class="jc-head-meta"><span>Article ID</span><b>#'.$app_res['pub_id'].'</b><span>Date</span><b>'.$date.'</b>'
	   .  '<a href="#" class="jc-print" title="Print this tab"><i class="fa fa-print"></i> Print</a></div></div>';

	$o .= '<div class="jc-stats">'
	   .  jc_stat('fa-hourglass-half', 'Total time', $diff ? jc_dur($diff) : '&ndash;', 'accent')
	   .  jc_stat('fa-list-ul', 'Entries', $n_rows)
	   .  ($with_depts ? jc_stat('fa-sitemap', 'Departments', $n_dept).jc_stat('fa-users', 'People', $n_people) : '')
	   .  '</div>';

	$o .= '<div class="jc-grid2"><section class="jc-panel"><h3><i class="fa fa-briefcase"></i> Customer info</h3>'.jc_kv($left).'</section>'
	   .  '<section class="jc-panel"><h3><i class="fa fa-file-text-o"></i> Proof info</h3>'.jc_kv($right).'</section></div>';

	if ($with_depts) { $o .= '<section class="jc-panel"><h3><i class="fa fa-sitemap"></i> Time by department</h3>'.$dept_html.'</section>'; }
	$o .= '<section class="jc-panel"><h3><i class="fa fa-tasks"></i> Production work details</h3>'.$tx_html.'</section>';
	global $EDIT_LIST;
	$mine = array();
	foreach ($EDIT_LIST as $e) { if ((string)$e['stage'] === (string)$stage) { $mine[] = $e; } }
	if ($mine) {
		$rows = '';
		foreach (array_reverse($mine) as $e) {
			$rows .= '<tr><td class="nw">'.date('d-m-Y', strtotime($e['created_at'])).'<br><small>'.date('h:i:s a', strtotime($e['created_at'])).'</small></td>'
			      .  '<td><span class="jc-person"><span class="jc-avatar sm">'.jc_initials($e['user_name']).'</span>'.htmlspecialchars(ucwords($e['user_name'])).'</span></td>'
			      .  '<td>'.(int)$e['change_count'].' field'.((int)$e['change_count'] === 1 ? '' : 's').'</td><td>'.jc_edit_link($e, 'View changes').'</td></tr>';
		}
		$o .= '<section class="jc-panel"><h3><i class="fa fa-pencil-square-o"></i> Article edits</h3><div class="jc-table-wrap"><table class="jc-table"><thead><tr><th>Date &amp; time</th><th>Edited by</th><th>Changed</th><th></th></tr></thead><tbody>'.$rows.'</tbody></table></div></section>';
	}
	return $o.'</div>';
}

/* ---- main (first proof) tab ---- */
function jc_render_main()
{
	global $app_res, $customer_info, $journal_info;
	$stage = jc_main_stage();
	list($count_dis, $res_dis) = tvl_dispatch('FP');
	$duedate = date("d-m-Y", strtotime($app_res['due_dt']));

	$left = array(
		'Client' => $customer_info['cust_name'],
		'Journal / Book' => $journal_info['j_name'],
		'Article / Chapter' => $app_res['pub_id'],
		'Input type' => $app_res['inw_type'],
		'No. of pages' => $app_res['page_count'],
		'Created on' => date("d-m-Y h:i:s A", strtotime($app_res['created_dt'])),
		'Created by' => jc_user_label($app_res['created_by']),
	);
	$right = array(
		'Received date' => date("d-m-Y", strtotime($app_res['created_dt'])),
		'Due date' => $duedate,
		'Uploaded date' => jc_uploaded_cell($count_dis, $res_dis, $duedate),
		'MSS pages' => $app_res['manuscript_count'],
		'Figures' => $app_res['fig'],
		'Tables' => $app_res['tab'],
		'Uploaded by' => $count_dis > 0 ? jc_user_label($res_dis['sent_by']) : '',
	);
	return jc_card($stage, date("d-m-Y", strtotime($app_res['created_dt'])), $left, $right, 'FP',
	               jc_tx_table(tvl_joined_rows($stage), true));
}

/* ---- revision / PMC / FIN tab ---- */
function jc_render_rev($rev)
{
	global $app_res, $customer_info, $journal_info;
	$stage = $rev['type'].$rev['revision_count'];
	list($count_dis, $disp) = tvl_dispatch($stage);
	$duedate = date("d-m-Y", strtotime($rev['due_date']));

	$left = array(
		'Client' => $customer_info['cust_name'],
		'Journal / Book' => $journal_info['j_name'],
		'Article / Chapter' => $app_res['pub_id'],
		'Input type' => $app_res['inw_type'],
		'No. of pages' => $app_res['manuscript_count'],
		'Created on' => date("d-m-Y h:i:s A", strtotime($rev['created_on'])),
		'Created by' => jc_user_label($rev['created_by']),
	);
	$right = array(
		'Received date' => date("d-m-Y", strtotime($rev['received_date'])),
		'Due date' => $duedate,
		'Uploaded date' => jc_uploaded_cell($count_dis, $disp, $duedate),
		'Correction pages' => $rev['correction_pages'],
		'Figures' => $app_res['fig'],
		'Tables' => $app_res['tab'],
		'Uploaded by' => $count_dis > 0 ? jc_user_label($disp['sent_by']) : '',
	);
	return jc_card($stage, date("d-m-Y", strtotime($rev['created_on'])), $left, $right, $stage,
	               jc_tx_table(tvl_joined_rows($stage, false, true)));
}

/* ---- issue correction tab ---- */
function jc_render_corr($c)
{
	global $app_res, $customer_info, $journal_info, $DRAFTS;
	$stage = $c['type'].$c['correction_count'];

	$left = array(
		'Client' => $customer_info['cust_name'],
		'Journal / Book' => $journal_info['j_name'],
		'Article / Chapter' => $app_res['pub_id'],
		'Input type' => $app_res['inw_type'],
		'No. of pages' => $c['correction_pages'],
	);
	$right = array(
		'Received date' => date("d-m-Y", strtotime($c['received_date'])),
		'Due date' => date("d-m-Y", strtotime($c['due_date'])),
		'MSS pages' => $app_res['manuscript_count'],
		'Figures' => $app_res['fig'],
		'Tables' => $app_res['tab'],
	);
	/* the stage is shared by the draft rounds, so each row says which round it belongs to */
	$round_of = null;
	$mine = array();
	foreach ($DRAFTS as $d) { if ($d['stage'] === $stage) { $mine[] = $d; } }
	if ($mine) {
		$round_of = function ($row) use ($mine, $stage) {
			$label = $stage;
			foreach ($mine as $d) { if ($row['created_dt'] >= $d['created_on']) { $label = $d['sub_stage']; } }
			return '<span class="jc-tag'.($label === $stage ? '' : ' alt').'">'.htmlspecialchars($label).'</span>';
		};
	}
	return jc_card($stage, date("d-m-Y", strtotime($c['created_on'])), $left, $right, $stage,
	               jc_tx_table(tvl_joined_rows($stage, true), false, $round_of), false);
}

/* ---- issue draft round tab (DRAFT1, DRAFT2 ...) ----
 * The article stays in its ISSCOR stage; the work done in a round is the stage's
 * transactions from the booking of this round until the next round is booked. */
function jc_render_draft($d)
{
	global $app_res, $customer_info, $journal_info;
	$stage = $d['stage'];

	$rows = array();
	foreach (tvl_joined_rows($stage, true) as $t) {
		if ($t['created_dt'] < $d['created_on']) { continue; }
		if ($d['next_on'] !== null && $t['created_dt'] >= $d['next_on']) { continue; }
		$rows[] = $t;
	}

	$left = array(
		'Client' => $customer_info['cust_name'],
		'Journal / Book' => $journal_info['j_name'],
		'Article / Chapter' => $app_res['pub_id'],
		'Input type' => $app_res['inw_type'],
		'Correction pages' => $d['correction_pages'],
		'Booked by' => jc_user_label($d['created_by']),
	);
	$right = array(
		'Round' => $d['sub_stage'],
		'Received date' => $d['received_date'] ? date("d-m-Y", strtotime($d['received_date'])) : '',
		'Due date' => $d['due_date'] ? date("d-m-Y", strtotime($d['due_date'])) : '',
		'Instructions' => htmlspecialchars($d['comments']),
		'Attachment' => htmlspecialchars($d['attachment']),
		'Sent to client' => !empty($d['dispatched_on']) ? date('d-m-Y h:i A', strtotime($d['dispatched_on'])).($d['dispatched_by'] !== '' && $d['dispatched_by'] !== null ? ' &middot; '.jc_user_label($d['dispatched_by']) : '') : 'Not sent yet',
	);
	$tx = jc_tx_table($rows, true);
	if (!$rows) { $tx[0] = '<div class="jc-empty">No work recorded in '.htmlspecialchars($d['sub_stage']).' yet. The article was booked for this round on '.date('d-m-Y h:i A', strtotime($d['created_on'])).'.</div>'; }
	return jc_card($stage.' &middot; '.$d['sub_stage'], date("d-m-Y", strtotime($d['created_on'])), $left, $right, $stage,
	               $tx, false);
}

/* ---------------------------------------------------------------------------
 * Request handling
 * ------------------------------------------------------------------------- */
$job_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$found  = jc_load_article($db, $job_id);

/* ---- ajax: one tab ---- */
if (isset($_REQUEST['ajax'])) {
	header('Content-Type: text/html; charset=utf-8');
	header('Cache-Control: no-store');
	ini_set('display_errors', '0');
	register_shutdown_function(function () {      // a fatal error shows up inside the tab instead of a blank pane
		$e = error_get_last();
		if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
			echo '<div class="jc-msg"><b>Server error:</b> '.htmlspecialchars($e['message']).' ('.basename($e['file']).':'.$e['line'].')</div>';
		}
	});
	if (!$found) { http_response_code(404); echo '<div class="alert alert-danger">Article not found.</div>'; exit; }

	$tab = isset($_REQUEST['tab']) ? (string)$_REQUEST['tab'] : 'main';
	jc_load_details($db, $job_id);
	if ($tab === 'main') {
		echo jc_render_main();
	} elseif (preg_match('/^rev-(\d+)$/', $tab, $m) && isset($REVS[$m[1]])) {
		echo jc_render_rev($REVS[$m[1]]);
	} elseif (preg_match('/^corr-(\d+)$/', $tab, $m) && isset($CORRS[$m[1]])) {
		echo jc_render_corr($CORRS[$m[1]]);
	} elseif (preg_match('/^draft-(\d+)$/', $tab, $m) && isset($DRAFTS[$m[1]])) {
		echo jc_render_draft($DRAFTS[$m[1]]);
	} else {
		http_response_code(404);
		echo '<div class="alert alert-danger">Unknown tab.</div>';
	}
	exit;
}

/* ---- page shell ---- */
$tabs = array(array('main', 'First Proof', 'fa-file-text-o'));
foreach ($REVS as $r_id => $rv)  { $tabs[] = array('rev-'.$r_id,  $rv['type'].$rv['revision_count'],  'fa-refresh'); }
foreach ($CORRS as $ic => $cr)   { $tabs[] = array('corr-'.$ic,   $cr['type'].$cr['correction_count'], 'fa-pencil-square-o'); }
foreach ($DRAFTS as $dr_id => $dr) { $tabs[] = array('draft-'.$dr_id, $dr['sub_stage'], 'fa-files-o'); }
$ajax_url = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>

    <style>
      /* flatten the theme's tinted outer box + inner card so the job card sits cleanly on white */
      .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;}
      .addarticleforminner{background:transparent !important;box-shadow:none !important;border:0 !important;
        width:100% !important;max-width:1360px;margin:0 auto !important;padding:0 18px !important;}
      #main-container{margin-left:0 !important;}
      .jc-root{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;
        --line:#e4e9ef;--bg:#f5f7fa;--ok:#1e9e61;--warn:#d98a06;--bad:#d64545;--info:#2a7fc1;
        color:var(--ink);font-size:13.5px;}
      .jc-root *{box-sizing:border-box;}
      .jc-root h2,.jc-root h3,.jc-root p{margin:0;}

      /* ---- top progress bar ---- */
      #jc-progress{position:fixed;top:0;left:0;height:4px;width:0;z-index:3000;opacity:0;border-radius:0 4px 4px 0;
        background:linear-gradient(90deg,var(--accent,#e8742a),#f5a35c 60%,#1b6ca8);
        box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;}
      #jc-progress.on{opacity:1;}

      /* ---- article banner ---- */
      .jc-banner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;
        padding:18px 24px;margin:6px 0 16px;border-radius:12px;color:var(--navy);
        background:linear-gradient(120deg,#f4f9fe 0%,#e9f2fb 100%);border:1px solid #d6e4f1;
        box-shadow:0 2px 10px rgba(20,48,74,.06);animation:jcUp .45s ease both;}
      .jc-banner small{display:block;font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:var(--mute);}
      .jc-banner .jc-art{font-size:22px;font-weight:700;letter-spacing:.2px;line-height:1.25;word-break:break-all;color:var(--navy);}
      .jc-banner .jc-sub{color:var(--mute);margin-top:2px;}
      .jc-lead{display:flex;align-items:center;gap:14px;min-width:0;}
      .jc-back{flex:0 0 auto;width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:var(--navy) !important;text-decoration:none !important;
        box-shadow:0 2px 6px rgba(20,48,74,.08);transition:all .2s ease;}
      .jc-back:hover{background:var(--accent);border-color:var(--accent);color:#fff !important;transform:translateX(-2px);box-shadow:0 5px 12px rgba(232,116,42,.35);}
      .jc-back i{font-size:16px;}
      .jc-facts{display:flex;flex-wrap:wrap;gap:10px;}
      .jc-fact{min-width:120px;padding:9px 14px;border-radius:8px;background:#fff;border:1px solid #dbe6f1;}
      .jc-fact b{display:block;font-size:14px;font-weight:600;color:var(--navy);}

      /* ---- tabs ---- */
      .jc-tabs-wrap{position:sticky;top:0;z-index:50;margin-bottom:16px;}
      .jc-tabs{position:relative;display:flex;gap:4px;padding:5px;overflow-x:auto;scrollbar-width:thin;
        background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 2px 10px rgba(20,48,74,.06);}
      #jc-ink{position:absolute;top:5px;bottom:5px;left:5px;width:0;border-radius:9px;z-index:0;
        background:#e6f0fa;border:1px solid #c5daef;
        transition:left .35s cubic-bezier(.4,0,.2,1),width .35s cubic-bezier(.4,0,.2,1);}
      .jc-tab{position:relative;z-index:1;flex:0 0 auto;display:flex;align-items:center;gap:8px;border:0;background:transparent;
        padding:10px 20px;border-radius:9px;font-size:13.5px;font-weight:600;color:var(--mute);cursor:pointer;
        transition:color .25s,background .25s;outline:0;white-space:nowrap;}
      .jc-tab:hover{color:var(--navy);}
      .jc-tab.active{color:var(--blue);}
      .jc-tab .jc-tick{display:none;font-size:11px;color:#34c98a;}
      .jc-tab.loaded .jc-tick{display:inline;}
      .jc-tab.busy .jc-tick{display:none;}
      .jc-tab .jc-mini{display:none;width:12px;height:12px;border-radius:50%;border:2px solid #c9d2dc;
        border-top-color:var(--blue);animation:jcSpin .7s linear infinite;}
      .jc-tab.busy .jc-mini{display:inline-block;}
      .jc-tools .jc-btn + .jc-btn{margin-left:8px;} .jc-btn:disabled{opacity:.6;cursor:wait;}
      .jc-tools{margin-left:auto;display:flex;align-items:center;padding-left:8px;position:relative;z-index:1;}
      .jc-btn{border:1px solid var(--line);background:#fff;color:var(--navy);border-radius:8px;padding:7px 13px;
        font-size:12.5px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;}
      .jc-btn:hover{background:var(--navy);color:#fff;border-color:var(--navy);}

      /* ---- panes ---- */
      .jc-pane{display:none;min-height:340px;}
      .jc-pane.active{display:block;animation:jcUp .35s ease;}
      @keyframes jcUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}
      @keyframes jcSpin{to{transform:rotate(360deg);}}
      @keyframes jcSlide{0%{left:-40%;}100%{left:100%;}}
      @keyframes jcShimmer{from{background-position:200% 0;}to{background-position:-200% 0;}}

      /* ---- loader ---- */
      .jc-loading{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
        padding:56px 20px 36px;background:#fff;border:1px solid var(--line);border-radius:12px;
        box-shadow:0 2px 12px rgba(20,48,74,.06);}
      .jc-ring{width:58px;height:58px;border-radius:50%;border:5px solid #e9eef4;border-top-color:var(--accent);
        border-right-color:var(--blue);animation:jcSpin .85s linear infinite;margin-bottom:18px;}
      .jc-load-title{font-size:16px;font-weight:700;color:var(--navy);}
      .jc-load-sub{color:var(--mute);margin-top:3px;}
      .jc-load-bar{position:relative;width:260px;max-width:80%;height:6px;border-radius:6px;background:#e9eef4;
        overflow:hidden;margin-top:18px;}
      .jc-load-bar span{position:absolute;top:0;bottom:0;width:40%;border-radius:6px;
        background:linear-gradient(90deg,var(--accent),var(--blue));animation:jcSlide 1.1s ease-in-out infinite;}
      .jc-pct{margin-top:10px;font-size:12px;color:var(--mute);font-variant-numeric:tabular-nums;}
      .jc-ghost{margin-top:22px;width:100%;max-width:760px;opacity:.7;}
      .jc-ghost div{height:14px;margin:12px auto;border-radius:6px;
        background:linear-gradient(90deg,#eceff3 25%,#f7f9fb 50%,#eceff3 75%);background-size:200% 100%;animation:jcShimmer 1.3s linear infinite;}
      .jc-ghost div:nth-child(1){width:92%;}.jc-ghost div:nth-child(2){width:78%;}.jc-ghost div:nth-child(3){width:86%;}
      .jc-msg{padding:18px 20px;border-radius:10px;background:#fdf1f1;border:1px solid #f1c6c6;color:#a94442;}
      .jc-msg button{margin-left:12px;}

      /* ---- card ---- */
      .jc-card{animation:jcUp .35s ease both;}
      .jc-card-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px 20px;
        padding:18px 22px;margin-bottom:14px;background:#fff;border:1px solid var(--line);border-radius:12px;
        border-left:5px solid var(--accent);box-shadow:0 2px 10px rgba(20,48,74,.05);}
      .jc-card-head h2{font-size:20px;font-weight:700;color:var(--navy);display:inline-block;margin-left:10px;vertical-align:middle;}
      .jc-card-head p{color:var(--mute);margin-top:6px;}
      .jc-stage{display:inline-block;vertical-align:middle;padding:4px 12px;border-radius:20px;font-weight:700;font-size:12.5px;
        letter-spacing:.4px;color:var(--accent);background:#fdeee3;border:1px solid #f6d3bb;}
      .jc-head-meta{display:grid;grid-template-columns:auto auto;gap:3px 12px;text-align:right;align-items:baseline;}
      .jc-head-meta span{color:var(--mute);font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;}
      .jc-head-meta b{color:var(--navy);}
      .jc-print{grid-column:1 / 3;margin-top:6px;font-weight:600;color:var(--blue);text-decoration:none;}
      .jc-print:hover{color:var(--accent);}

      .jc-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:14px;}
      .jc-stat{display:flex;align-items:center;gap:14px;padding:14px 16px;background:#fff;border:1px solid var(--line);
        border-radius:12px;box-shadow:0 2px 8px rgba(20,48,74,.04);}
      .jc-stat>i{width:42px;height:42px;line-height:42px;text-align:center;border-radius:10px;font-size:18px;
        background:#eaf2fa;color:var(--blue);}
      .jc-stat span{display:block;color:var(--mute);font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;}
      .jc-stat b{font-size:17px;color:var(--navy);font-variant-numeric:tabular-nums;}
      .jc-stat.accent>i{background:#fdeee3;color:var(--accent);}

      .jc-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
      @media(max-width:860px){.jc-grid2{grid-template-columns:1fr;}}
      .jc-panel{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:14px;
        box-shadow:0 2px 8px rgba(20,48,74,.04);}
      .jc-grid2 .jc-panel{margin-bottom:0;}
      .jc-panel h3{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--navy);
        padding-bottom:10px;margin-bottom:6px;border-bottom:1px solid var(--line);}
      .jc-panel h3 i{color:var(--accent);margin-right:7px;}
      .jc-kv{display:flex;justify-content:space-between;gap:16px;padding:9px 0;border-bottom:1px dashed #edf0f4;}
      .jc-kv:last-child{border-bottom:0;}
      .jc-kv span{color:var(--mute);}
      .jc-kv b{font-weight:600;text-align:right;color:var(--ink);}

      .jc-person{display:inline-flex;align-items:center;gap:8px;}
      .jc-person small{color:var(--mute);}
      .jc-avatar{display:inline-block;width:26px;height:26px;line-height:26px;border-radius:50%;text-align:center;
        font-size:10.5px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--blue),var(--navy2));}
      .jc-avatar.sm{width:22px;height:22px;line-height:22px;font-size:9.5px;}
      .jc-na{color:#b3bcc7;}
      .jc-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;margin-left:4px;}
      .jc-pill.ok{background:#e3f6ec;color:var(--ok);} .jc-pill.warn{background:#fdf1d9;color:var(--warn);}
      .jc-pill.bad{background:#fbe5e5;color:var(--bad);} .jc-pill.info{background:#e1effa;color:var(--info);}
      .jc-pill.muted{background:#eef1f5;color:var(--mute);margin-left:0;}

      /* departments */
      .jc-depts{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-top:10px;}
      .jc-dept{padding:14px;border:1px solid var(--line);border-radius:10px;background:linear-gradient(180deg,#fff,#f9fbfd);
        transition:transform .2s,box-shadow .2s;}
      .jc-dept:hover{transform:translateY(-3px);box-shadow:0 8px 18px rgba(20,48,74,.1);}
      .jc-dept-name{font-weight:700;color:var(--navy);}
      .jc-dept-time{margin:6px 0 10px;color:var(--accent);font-weight:700;font-size:15px;}
      .jc-dept-users{display:flex;flex-direction:column;gap:6px;}
      .jc-chip{display:flex;align-items:center;gap:8px;color:var(--ink);}

      /* table */
      .jc-table-wrap{overflow-x:auto;margin-top:8px;border:1px solid var(--line);border-radius:10px;}
      .jc-table{width:100%;border-collapse:collapse;min-width:900px;margin:0;border:0;}
      .jc-table th{position:sticky;top:0;background:#eef3f8;color:var(--navy);border-bottom:2px solid #d5e1ed !important;padding:11px 12px;font-size:12px;font-weight:600;
        text-transform:uppercase;letter-spacing:.5px;text-align:left;border:0;}
      .jc-table td{padding:11px 12px;border:0;border-bottom:1px solid #eef1f5;vertical-align:middle;background:#fff;}
      .jc-table tbody tr:nth-child(even) td{background:#f9fbfd;}
      .jc-table tbody tr:hover td{background:#eef5fb;}
      .jc-table tr.jc-hl td{background:#fff9d6 !important;}
      .jc-table .c{text-align:center;width:46px;color:var(--mute);} .jc-table .nw{white-space:nowrap;}
      .jc-table .cm{max-width:260px;} .jc-table small{color:var(--mute);}
      .jc-tag{display:inline-block;padding:3px 10px;border-radius:6px;background:#eaf2fa;color:var(--blue);font-weight:600;font-size:12px;}
      .jc-tag.alt{background:#fdeee3;color:var(--accent);}
      .jc-dur{font-weight:600;color:var(--navy);font-variant-numeric:tabular-nums;}
      .jc-dur i{color:var(--accent);margin-right:4px;}
      .jc-empty{padding:26px;text-align:center;color:var(--mute);background:#f9fbfd;border:1px dashed var(--line);border-radius:10px;margin-top:8px;}

      /* ---- article edit pop-up ---- */
      .jc-chg{display:inline-flex;align-items:center;gap:6px;margin-top:5px;padding:3px 11px;border-radius:999px;background:#eaf2fa;border:1px solid #c5daef;color:var(--blue) !important;font-size:12px;font-weight:700;text-decoration:none !important;transition:all .2s;}
      .jc-chg:hover{background:var(--blue);border-color:var(--blue);color:#fff !important;transform:translateY(-1px);box-shadow:0 5px 12px rgba(27,108,168,.3);}
      #jc-dlg{position:fixed;inset:0;z-index:99990;display:none;align-items:center;justify-content:center;background:rgba(15,32,52,.5);backdrop-filter:blur(3px);padding:16px;}
      #jc-dlg.on{display:flex;animation:jcFade .2s ease;}
      #jc-dlg .box{width:min(580px,100%);max-height:90vh;display:flex;flex-direction:column;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 28px 70px rgba(15,35,60,.38);animation:jcPopBox .45s cubic-bezier(.2,1.2,.3,1) backwards;}
      #jc-dlg .box:before{content:"";display:block;height:5px;flex:0 0 auto;background:linear-gradient(90deg,#1b6ca8,#7fb3de);}
      #jc-dlg .in{padding:22px 26px 20px;text-align:center;overflow:auto;}
      #jc-dlg .ic{width:56px;height:56px;margin:0 auto 10px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e6f1fa;color:#1b6ca8;font-size:24px;animation:jcPop .5s cubic-bezier(.2,1.4,.3,1) .1s backwards;}
      #jc-dlg h3{margin:0 0 4px;font-size:20px;font-weight:700;color:#1c3550;} #jc-dlg p{margin:0;color:#51606f;font-size:13.5px;line-height:1.5;}
      #jc-dlg ul{list-style:none;margin:14px 0 0;padding:0;text-align:left;max-height:50vh;overflow:auto;}
      #jc-dlg li{margin:0 0 7px;padding:9px 12px 9px 14px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid #1b6ca8;border-radius:10px;animation:jcRise .35s ease backwards;}
      #jc-dlg li b{display:block;font-size:11.5px;letter-spacing:.4px;text-transform:uppercase;color:#6b7785;margin-bottom:3px;}
      #jc-dlg li .cmp{display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13.5px;} #jc-dlg li .old{color:#9a6a6a;text-decoration:line-through;text-decoration-color:rgba(192,57,43,.5);word-break:break-word;} #jc-dlg li .new{color:#177a49;font-weight:700;word-break:break-word;} #jc-dlg li i{color:#9aa8b8;}
      #jc-dlg .btns{display:flex;justify-content:center;margin-top:16px;}
      #jc-dlg .btns button{min-width:130px;height:42px;border:0;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;color:#fff;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 5px 14px rgba(27,108,168,.3);} #jc-dlg .btns button:hover{filter:brightness(1.1);}
      @keyframes jcPopBox{from{opacity:0;transform:translateY(18px) scale(.93);}to{opacity:1;transform:none;}} @keyframes jcPop{from{opacity:0;transform:scale(.8);}to{opacity:1;transform:none;}}
      @keyframes jcRise{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}} @keyframes jcFade{from{opacity:0;}to{opacity:1;}}
      /* ---- print ---- */
      .jc-print-sp{display:none;}
      .jc-psec{display:none;}
      @page{size:A4;margin:0;}   /* margin 0 = the browser adds no date / title / URL / page-number header and footer */
      @media print{
        html,body{background:#fff !important;margin:0 !important;padding:0 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
        /* everything that is not the job card: site header, menus, footer, tabs, buttons, loaders */
        .wholeouter > *:not(.addarticleformouter),.footer,.footerrow,header,nav,.navbar,.sidebar,.nav-clock,
        #jc-progress,.jc-tabs-wrap,.jc-print,.jc-btn,.jc-loading,.jc-retry,.jc-tools,.jc-banner .jc-print,.jc-back,.jc-chg,#jc-dlg{display:none !important;}
        .addarticleformouter,.addarticleforminner,.main-container,.main-content,.main-content-inner,.page-content,.row,.col-xs-12{
          display:block !important;width:100% !important;max-width:none !important;margin:0 !important;padding:0 !important;float:none !important;
          background:#fff !important;box-shadow:none !important;border:0 !important;}
        .jc-root{padding:0 13mm !important;font-size:11.5px !important;color:#000;}
        .jc-print-sp{display:block;height:12mm;}
        .jc-print-wrap{width:100%;border-collapse:collapse;border:0;}
        .jc-print-wrap > thead{display:table-header-group;}
        .jc-print-wrap > tfoot{display:table-footer-group;}
        .jc-print-wrap td{padding:0;border:0;}

        .jc-banner{background:#fff !important;color:#14304a !important;box-shadow:none !important;border:0 !important;border-bottom:2px solid #14304a !important;
          border-radius:0 !important;padding:0 0 8px !important;margin:0 0 12px !important;display:flex !important;justify-content:space-between;align-items:flex-end;}
        .jc-banner:before,.jc-banner:after{display:none !important;}
        .jc-banner small,.jc-banner .jc-sub,.jc-banner .jc-fact small{color:#555 !important;}
        .jc-banner .jc-art,.jc-banner .jc-fact b{color:#14304a !important;}
        .jc-facts{display:flex !important;gap:18px;}
        .jc-fact{background:none !important;border:0 !important;padding:0 !important;}

        .jc-pane{display:none !important;}
        .jc-pane.active{display:block !important;animation:none !important;}
        body.jc-all .jc-pane{display:block !important;animation:none !important;}
        body.jc-all .jc-pane + .jc-pane{break-before:page;page-break-before:always;}
        body.jc-all .jc-pane > .jc-psec{display:block;font-size:15px;font-weight:700;color:#14304a;border-bottom:1px solid #cfd8e3;padding:0 0 4px;margin:0 0 10px;}
        .jc-card{box-shadow:none !important;border:0 !important;background:#fff !important;border-radius:0 !important;display:block !important;}
        .jc-card-head{border-radius:0 !important;box-shadow:none !important;background:#fff !important;border:0 !important;border-bottom:1px solid #cfd8e3 !important;padding:0 0 8px !important;margin:0 0 10px !important;}
        .jc-panel,.jc-stat,.jc-stats > *,.jc-depts > *{box-shadow:none !important;border:1px solid #cfd8e3 !important;background:#fff !important;border-radius:6px !important;}
        .jc-panel:not(:has(.jc-table-wrap)),.jc-stat,.jc-fact,.jc-kv,.jc-depts > *{break-inside:avoid;page-break-inside:avoid;}
        .jc-table-wrap{overflow:visible !important;}
        .jc-card,.jc-card *,.jc-pane,.jc-pane *{animation:none !important;transition:none !important;}
        .jc-card,.jc-pane{opacity:1 !important;transform:none !important;}
        .jc-panel h3{break-after:avoid;page-break-after:avoid;}
        .jc-table{width:100% !important;border-collapse:collapse !important;}
        .jc-table thead{display:table-header-group;}
        .jc-table tr{break-inside:avoid;page-break-inside:avoid;}
        .jc-table th,.jc-table td{border:1px solid #cfd8e3 !important;padding:4px 6px !important;font-size:10.5px !important;}
        .jc-table th{background:#eef3f8 !important;color:#14304a !important;}
        .jc-table tr.jc-hl td{background:#fff9d6 !important;}
        a{color:inherit !important;text-decoration:none !important;}
        .jc-root *{overflow:visible !important;max-height:none !important;}
      }
    </style>
  </head>

  <body>
    <div id="jc-progress"></div>
    <div id="jc-dlg"><div class="box"><div class="in"><div class="ic"><i class="fa fa-pencil-square-o"></i></div><h3>Article changes</h3><p id="jc-dsub"></p><ul id="jc-dlist"></ul><div class="btns"><button type="button" id="jc-dclose">Close</button></div></div></div></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
<div class="main-container" id="main-container" style="margin-left:-15px;">
  <div class="main-content"><div class="main-content-inner"><div class="page-content">
    <div class="row"><div class="col-xs-12 jc-root">

<?php if (!$found) { ?>
      <div class="jc-msg" style="margin-top:20px;">Article not found.</div>
<?php } else { ?>
      <div class="jc-banner">
        <div class="jc-lead" style="position:relative;z-index:1;">
          <a href="<?php echo htmlspecialchars($_COOKIE['set_url'].'/currentwork', ENT_QUOTES); ?>" class="jc-back" id="jc-back" title="Back"><i class="fa fa-arrow-left"></i></a>
          <div>
            <small>Article ID</small>
            <div class="jc-art">#<?php echo $app_res['pub_id']; ?></div>
            <div class="jc-sub"><?php echo $customer_info['cust_name']; ?> &nbsp;&middot;&nbsp; <?php echo $journal_info['j_name']; ?></div>
          </div>
        </div>
        <div class="jc-facts">
          <div class="jc-fact"><small>Input type</small><b><?php echo $app_res['inw_type'] !== '' ? $app_res['inw_type'] : '&ndash;'; ?></b></div>
          <div class="jc-fact"><small>Current stage</small><b><?php echo $app_res['stage'] !== '' ? $app_res['stage'] : '&ndash;'; ?></b></div>
          <?php if (!empty($DRAFTS)) { $last_draft = end($DRAFTS); reset($DRAFTS); ?><div class="jc-fact"><small>Current round</small><b><?php echo htmlspecialchars($last_draft['sub_stage']); ?></b></div><?php } ?>
          <div class="jc-fact"><small>Current status</small><b><?php echo $app_res['status'] !== '' ? $app_res['status'] : '&ndash;'; ?></b></div>
        </div>
      </div>

      <div class="jc-tabs-wrap">
        <div class="jc-tabs" id="jc-tabs" role="tablist">
          <span id="jc-ink"></span>
          <?php foreach ($tabs as $i => $t) { ?>
            <button type="button" class="jc-tab<?php echo $i === 0 ? ' active' : ''; ?>" role="tab" data-tab="<?php echo $t[0]; ?>">
              <i class="fa <?php echo $t[2]; ?>"></i><?php echo htmlspecialchars($t[1]); ?>
              <span class="jc-mini"></span><i class="fa fa-check-circle jc-tick"></i>
            </button>
          <?php } ?>
          <span class="jc-tools"><button type="button" class="jc-btn" id="jc-refresh" title="Reload this tab"><i class="fa fa-refresh"></i> Reload</button><button type="button" class="jc-btn" id="jc-print-tab" title="Print this tab"><i class="fa fa-print"></i> Print</button><button type="button" class="jc-btn" id="jc-print-all" title="Print every tab"><i class="fa fa-files-o"></i> Print all</button></span>
        </div>
      </div>

      <?php foreach ($tabs as $i => $t) { ?>
        <div class="jc-pane<?php echo $i === 0 ? ' active' : ''; ?>" id="pane-<?php echo $t[0]; ?>" data-tab="<?php echo $t[0]; ?>">
          <?php if ($i === 0) { ?>
          <div class="jc-loading"><div class="jc-ring"></div><div class="jc-load-title">Loading job card&hellip;</div>
            <div class="jc-load-sub">Fetching production details</div><div class="jc-load-bar"><span></span></div></div>
          <?php } ?>
        </div>
      <?php } ?>
<?php } ?>

    </div></div>
  </div></div></div>
</div>
        </div>
      </div>

      <?php echo scripts(); ?>
      <?php echo footer(); ?>

<?php if ($found) { ?>
<script>
(function ($) {
  var URL_BASE = <?php echo json_encode($ajax_url); ?>,
      JOB_ID   = <?php echo (int)$job_id; ?>,
      state = {}, current = null,
      $bar = $('#jc-progress'), barTimer = null, barVal = 0, busy = 0;

  /* ---- progress bar + percentage ---- */
  function setBar(v) {
    barVal = v; $bar.css('width', v + '%');
    $('.jc-pane.active .jc-pct').text(Math.round(v) + '%');
  }
  function barStart() {
    busy++;
    if (busy > 1) { return; }
    clearInterval(barTimer);
    $bar.addClass('on'); setBar(6);
    barTimer = setInterval(function () { setBar(barVal + (92 - barVal) * 0.07); }, 180);
  }
  function barDone() {
    busy = Math.max(0, busy - 1);
    if (busy > 0) { return; }
    clearInterval(barTimer); setBar(100);
    setTimeout(function () { $bar.removeClass('on'); setTimeout(function () { $bar.css('width', 0); }, 450); }, 300);
  }

  /* ---- sliding pill behind the active tab ---- */
  function moveInk() {
    var $a = $('.jc-tab.active');
    if ($a.length) { $('#jc-ink').css({ left: $a[0].offsetLeft, width: $a.outerWidth() }); }
  }

  function loader(label) {
    return '<div class="jc-loading"><div class="jc-ring"></div><div class="jc-load-title">Loading ' + label + '&hellip;</div>' +
           '<div class="jc-load-sub">Fetching production details</div><div class="jc-load-bar"><span></span></div>' +
           '<div class="jc-pct">0%</div><div class="jc-ghost"><div></div><div></div><div></div></div></div>';
  }

  function loadTab(key, force) {
    var $pane = $('#pane-' + key), $tab = $('.jc-tab[data-tab="' + key + '"]'),
        label = $.trim($tab.text());
    if (state[key] === 'loading' || (state[key] === 'loaded' && !force)) { return; }

    state[key] = 'loading';
    $tab.addClass('busy');
    $pane.html(loader(label));
    barStart();

    return $.ajax({
      url: URL_BASE, type: 'POST', data: { id: JOB_ID, ajax: 1, tab: key }, dataType: 'html', cache: false,
      xhr: function () {
        var x = $.ajaxSettings.xhr();
        x.addEventListener('progress', function (e) {
          if (e.lengthComputable && e.total) { setBar(Math.max(barVal, Math.min(95, e.loaded / e.total * 100))); }
        });
        return x;
      }
    }).done(function (html) {
      if (!$.trim(html)) {
        delete state[key];
        $pane.html('<div class="jc-msg">The server returned an empty response for this tab.<button type="button" class="jc-btn jc-retry">Retry</button></div>');
        return;
      }
      state[key] = 'loaded';
      $pane.html(html);
      $tab.addClass('loaded');
    }).fail(function (x, st) {
      delete state[key];
      if (st === 'abort') { return; }
      $pane.html('<div class="jc-msg">Could not load this tab.<button type="button" class="jc-btn jc-retry">Retry</button></div>');
    }).always(function () { $tab.removeClass('busy'); barDone(); });
  }

  function activate(key, push) {
    if (!$('#pane-' + key).length) { key = 'main'; }
    current = key;
    $('.jc-tab').removeClass('active').filter('[data-tab="' + key + '"]').addClass('active');
    $('.jc-pane').removeClass('active').filter('#pane-' + key).addClass('active');
    moveInk();
    var el = $('.jc-tab.active')[0];
    if (el && el.scrollIntoView) { try { el.scrollIntoView({ block: 'nearest', inline: 'center' }); } catch (e) {} }
    if (push) { try { history.replaceState(null, '', '#' + key); } catch (e) { location.hash = key; } }
    loadTab(key, false);
  }


  /* ---- what an Edit Article save changed (the list rides on the link) ---- */
  $(document).on('click', '.jc-chg', function (e) {
    e.preventDefault();
    var $a = $(this), list = []; try { list = JSON.parse($a.attr('data-c')) || []; } catch (er) {}
    $('#jc-dsub').text('Edited by ' + $a.data('by') + ' on ' + $a.data('at') + ' \u2014 ' + list.length + ' change' + (list.length === 1 ? '' : 's'));
    var $l = $('#jc-dlist').empty(), sh = function (s) { s = String(s); return s.length > 120 ? s.slice(0, 120) + '\u2026' : s; };
    $.each(list, function (i, c) {
      $l.append($('<li></li>').css('animation-delay', (i * 0.05) + 's').append($('<b></b>').text(c.label))
        .append($('<span class="cmp"></span>').append($('<span class="old"></span>').text(c.from === '' ? '(empty)' : sh(c.from))).append('<i class="fa fa-long-arrow-right"></i>').append($('<span class="new"></span>').text(c.to === '' ? '(empty)' : sh(c.to)))));
    });
    $('#jc-dlg').addClass('on');
  });
  $('#jc-dclose').on('click', function () { $('#jc-dlg').removeClass('on'); });
  $('#jc-dlg').on('click', function (e) { if (e.target === this) { $(this).removeClass('on'); } });
  $(document).on('keydown', function (e) { if (e.key === 'Escape') { $('#jc-dlg').removeClass('on'); } });

  $('#jc-tabs').on('click', '.jc-tab', function () { activate($(this).data('tab'), true); });
  $('#jc-refresh').on('click', function () { if (current) { loadTab(current, true); } });
  $(document).on('click', '.jc-retry', function () { loadTab(current, true); });
  /* ---- printing ----
     Print = the open tab. Print all = every tab (they are fetched first), one section per page.
     The page margin is 0 so the browser prints no header/footer of its own; the real margins come from a
     repeating spacer table that is wrapped around the job card only while printing. */
  var printWrap = null;
  function tabLabel(key) { return $.trim($('.jc-tab[data-tab="' + key + '"]').clone().children().remove().end().text()); }
  function printStart() {
    if (printWrap || !$('.jc-root').length) { return; }
    var $root = $('.jc-root').first();
    $('.jc-pane').each(function () {
      if (!$(this).children('.jc-psec').length) { $(this).prepend($('<div class="jc-psec"></div>').text(tabLabel($(this).data('tab')))); }
    });
    var $t = $('<table class="jc-print-wrap"><thead><tr><td><div class="jc-print-sp"></div></td></tr></thead><tbody><tr><td></td></tr></tbody><tfoot><tr><td><div class="jc-print-sp"></div></td></tr></tfoot></table>');
    $root.before($t); $t.find('tbody td').append($root); printWrap = { $t: $t, $root: $root };
  }
  function printEnd() {
    if (!printWrap) { return; }
    printWrap.$t.before(printWrap.$root); printWrap.$t.remove(); printWrap = null;
    $('body').removeClass('jc-all'); $('.jc-psec').remove();
  }
  window.addEventListener('beforeprint', printStart);
  window.addEventListener('afterprint', printEnd);
  function doPrint(all) {
    $('body').toggleClass('jc-all', !!all);
    setTimeout(function () { window.print(); }, 60);
  }
  function printAll() {
    var keys = $('.jc-tab').map(function () { return $(this).data('tab'); }).get(), t0 = Date.now(), $b = $('#jc-print-all');
    $b.prop('disabled', true);
    keys.forEach(function (k) { if (state[k] !== 'loaded') { loadTab(k, false); } });
    (function wait() {
      var pending = keys.filter(function (k) { return state[k] !== 'loaded'; }).length;
      if (pending && Date.now() - t0 < 90000) { setTimeout(wait, 250); return; }
      $b.prop('disabled', false);
      doPrint(true);
    })();
  }
  /* Back: return to the page the user came from (Current Work, Added Articles ...); a direct / new-tab visit falls back to Current Work */
  $('#jc-back').on('click', function (e) {
    var sameSite = false;
    try { sameSite = document.referrer && new URL(document.referrer).host === location.host; } catch (er) {}
    if (sameSite && history.length > 1) { e.preventDefault(); history.back(); }
  });
  $(document).on('click', '.jc-print, #jc-print-tab', function (e) { e.preventDefault(); doPrint(false); });
  $('#jc-print-all').on('click', printAll);
  $(window).on('resize', moveInk);

  var start = (location.hash || '').replace('#', '');
  $(function () { activate(start || 'main', false); setTimeout(moveInk, 120); setTimeout(moveInk, 600); });
})(jQuery);
</script>
<?php } ?>

  </body>
</html>

<?php
include ('dbc.php');

/* ============================================================================
 * Manual "Move" of a dispatch-ready article to Client Review (Completed for FIN).
 *
 * The Move button on Under Dispatch Articles asks for confirmation first and only then opens this page.
 * After the move a notification mail goes to the recipients saved in "Start Work Switch Settings"
 * (to / cc / bcc, wf_settings.sw_switch_*), so the team knows the article was moved by hand.
 * A mail problem never undoes the move - the result is shown on the list page.
 * ========================================================================== */

if (!isset($_COOKIE['user_id'])) { http_response_code(401); exit('Please log in.'); }   // the list pages only link here for logged-in users

function mv_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* returns a short sentence about the mail for the list page */
function mv_notify($db, $a, $moved_to, $prev_status)
{
	require_once __DIR__.'/sw_switch_lib.php';
	$cfg = sw_switch_get($db);
	$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
	if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) { return 'No mail sent - set the recipients in Start Work Switch Settings.'; }
	try {
		require_once __DIR__.'/email-functions.php';
		$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
		if (!$es) { return 'No mail sent - email settings are missing.'; }

		$cn = ''; $jn = '';
		$r = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = ".(int)$a['cust_id']." LIMIT 1");
		if ($r && ($x = $r->fetch_assoc())) { $cn = $x['cust_name']; }
		$r = $db->query("SELECT j_code, j_name FROM adm_journals WHERE j_id = ".(int)$a['j_id']." LIMIT 1");
		if ($r && ($x = $r->fetch_assoc())) { $jn = trim($x['j_code'].' - '.$x['j_name'], ' -'); }
		$uname = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ''; $uid = (string)$_COOKIE['user_id'];

		$body = '<p>Hi Team,</p><p>An article was <strong>moved manually</strong> from Under Dispatch to <strong>'.mv_h($moved_to).'</strong>.</p>'
			. '<table cellpadding="4" cellspacing="0" border="0">'
			. '<tr><td><strong>Moved by</strong></td><td>'.mv_h($uname).($uid !== '' ? ' (ID '.mv_h($uid).')' : '').'</td></tr>'
			. '<tr><td><strong>Article</strong></td><td>'.mv_h($a['pub_id']).' (ID '.(int)$a['id'].')</td></tr>'
			. '<tr><td><strong>Customer</strong></td><td>'.mv_h($cn).'</td></tr>'
			. '<tr><td><strong>Journal</strong></td><td>'.mv_h($jn).'</td></tr>'
			. '<tr><td><strong>Stage</strong></td><td>'.mv_h($a['stage']).'</td></tr>'
			. '<tr><td><strong>Status before</strong></td><td>'.mv_h($prev_status).'</td></tr>'
			. '<tr><td><strong>Moved to</strong></td><td>'.mv_h($moved_to).'</td></tr>'
			. '<tr><td><strong>Moved on</strong></td><td>'.date('d-M-Y h:i A').'</td></tr>'
			. '</table>'
			. '<p style="color:#888;font-size:12px;">System-generated notification from the Workflow application. Recipients can be changed in Start Work Switch Settings.</p>';
		$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
			implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']),
			'MOVED MANUALLY: '.$a['pub_id'].' to '.$moved_to.' by '.$uname, $body);
		$n = count($to['valid']) + count($cc['valid']) + count($bcc['valid']);
		return $ok ? 'Notification mail sent ('.$n.' recipient'.($n === 1 ? '' : 's').').' : 'Mail could not be sent - see smtp_mail.log.';
	} catch (Exception $e) {
		return 'Mail could not be sent - '.substr($e->getMessage(), 0, 120);
	}
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($id > 0)
{
	$res = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id." LIMIT 1");
	$app_res = $res ? $res->fetch_assoc() : null;
	if (!$app_res) { header('location:'.$_COOKIE['set_url'].'/underdispatcharticles?msg='.urlencode('Article not found.')); exit; }
	$prev_status = (string)$app_res['status'];

	$is_fin = (isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'FIN');
	$moved_to = $is_fin ? 'Completed' : 'Client Review';
	$set_status = "`status` ='".$moved_to."',";
	$user_working_status = '<span class="label label-sm label-success">'.$moved_to.'</span>';

	$db->query("UPDATE inw_inward_dtl SET ".$set_status." user_working_status = '".$db->real_escape_string($user_working_status)."', `department_id` ='0',`assign_department_id` ='0' WHERE id = ".$id);

	$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('"
		.$db->real_escape_string($app_res['cust_id'])."', '".(int)$app_res['j_id']."', '".(int)$app_res['id']."', '', '', 'Manually moved this Article to under client review.', '"
		.$db->real_escape_string($_COOKIE['user_id'])."', '".$db->real_escape_string($app_res['stage'])."', NOW(),'Moved')";
	$db->query($dispat_qry);

	$mail_note = mv_notify($db, $app_res, $moved_to, $prev_status);
	header('location:'.$_COOKIE['set_url'].'/underdispatcharticles?msg='.urlencode($app_res['pub_id'].' moved to '.$moved_to.'. '.$mail_note));
	exit;
}
header('location:'.$_COOKIE['set_url'].'/underdispatcharticles');
?>

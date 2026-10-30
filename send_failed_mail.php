<?php
/* "Send Mail" on Dispatch Article - Auto Upload/Failed Emails (emailfailedlist): re-sends the client notification of an upload whose mail failed,
   the same way the FTP upload cron does (API or SMTP, according to ftp_customer_stage -> customer_email_settings), and records the result in mail_send. */
include "includes/conn.php";
include "email-functions.php";
header('Content-Type: application/json; charset=utf-8');

function sfm_out($status, $msg) { echo json_encode(array('status' => $status, 'msg' => $msg)); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { sfm_out('error', 'Invalid ID'); }

$r = mysqli_query($db, "SELECT * FROM xperttrack_ftp_upload_history WHERE id = " . $id);
$row = $r ? mysqli_fetch_assoc($r) : null;
if (!$row) { sfm_out('error', 'Record not found'); }
if ($row['mail_send'] === 'SUCCESS') { sfm_out('warning', 'The mail for this article was already sent.'); }
if ($row['status'] === 'YET_TO_UPLOAD') { sfm_out('warning', 'The upload is still in the queue - its mail is sent after the upload.'); }
foreach (array('to_mail' => 'To', 'mail_subject' => 'Subject', 'mail_text' => 'Body') as $col => $label) {
	if (trim((string)$row[$col]) === '') { sfm_out('error', 'The mail cannot be sent: its ' . $label . ' is empty in the upload history.'); }
}

$st = mysqli_query($db, "SELECT mail_type, email_id FROM ftp_customer_stage WHERE cust_id = " . (int)$row['cust_id'] . " AND FIND_IN_SET('" . mysqli_real_escape_string($db, $row['stage']) . "', stage) LIMIT 1");
$stage = $st ? mysqli_fetch_assoc($st) : null;
if (!$stage) { sfm_out('error', 'No mail setup (ftp_customer_stage) for this customer and stage.'); }
$es = mysqli_query($db, "SELECT * FROM customer_email_settings WHERE id = " . (int)$stage['email_id']);
$cfg = $es ? mysqli_fetch_assoc($es) : null;
if (!$cfg) { sfm_out('error', 'No mail credentials (customer_email_settings) for this customer.'); }

$ok = false; $why = '';
if ($stage['mail_type'] === 'API') {
	$data = array('sender' => array('name' => $cfg['email_from_name'], 'email' => $cfg['from_api_email']), 'to' => array(array('email' => trim($row['to_mail']))), 'subject' => $row['mail_subject'], 'htmlContent' => $row['mail_text']);
	foreach (array('cc' => 'to_cc', 'bcc' => 'to_bcc') as $k => $col) {
		$list = array();
		foreach (explode(',', (string)$row[$col]) as $e) { $e = trim($e); if ($e !== '') { $list[] = array('email' => $e); } }
		if ($list) { $data[$k] = $list; }
	}
	$ch = curl_init($cfg['api_endpoint']);
	curl_setopt_array($ch, array(CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
		CURLOPT_HTTPHEADER => array('accept: application/json', 'api-key: ' . $cfg['email_key'], 'content-type: application/json'), CURLOPT_POSTFIELDS => json_encode($data)));
	$resp = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
	$ok = ($code === 201);
	if (!$ok) { $why = $err !== '' ? $err : 'HTTP ' . $code; }
} else {
	$ok = sendMail($cfg['host'], $cfg['port'], $cfg['secure'], $cfg['from_email'], $cfg['from_password'], $cfg['from_email'], $cfg['from_name'],
		$row['to_mail'], $row['to_cc'], $row['to_bcc'], $row['mail_subject'], $row['mail_text']);
	if (!$ok) { $why = 'SMTP error - see smtp_mail.log'; }
}

mysqli_query($db, "UPDATE xperttrack_ftp_upload_history SET mail_send = '" . ($ok ? 'SUCCESS' : 'FAILED') . "' WHERE id = " . $id);
if ($ok) { sfm_out('success', 'Mail sent to ' . $row['to_mail'] . '.'); }
sfm_out('error', 'The mail could not be sent (' . $why . ').');

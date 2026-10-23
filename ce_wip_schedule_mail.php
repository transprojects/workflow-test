<?php
include("dbc.php"); // your DB connection file
date_default_timezone_set('Asia/Kolkata');

/// Get Schedule mail details ////
$query = "SELECT * FROM `adm_schedule_mail_details` WHERE type = 'CE-WIP-Article-Status-Report' ";
$schedule_mail = $db->query($query) or ($db->error);
$schedule_details = $schedule_mail->fetch_assoc();
				
$mail_from = $schedule_details['from_email'];
$mail_pass = $schedule_details['from_email_pwd'];

$to_mails = $schedule_details['to_email'];
$cc_mails = $schedule_details['cc_email'];
$bcc_mails = $schedule_details['bcc_email'];


/* Convert seconds to HH MM */
function getHoursMinutes($seconds, $format = '%02d hours %02d minutes') {
    if (empty($seconds) || !is_numeric($seconds)) {
        return sprintf($format, 0, 0);
    }
    $minutes = floor($seconds / 60);
    $hours   = floor($minutes / 60);
    $remain  = $minutes % 60;
    return sprintf($format, $hours, $remain);
}

/* Main Query */
$sql = "
SELECT
    iwd.id,
    iwd.stage,
    iwd.department_id,
    iwd.pub_id,
    iwd.recv_dt,
    iwd.due_dt,
    iwd.manuscript_count,
    j.j_name,
    acm.cust_name
FROM inw_inward_dtl AS iwd
INNER JOIN adm_customer_master AS acm 
    ON acm.id = iwd.cust_id
LEFT JOIN adm_journals AS j 
    ON j.j_id = iwd.j_id
WHERE iwd.department_id IN (1) AND iwd.recv_dt >= '2026-01-01'
";

$result = $db->query($sql);

/* ================= EMAIL HTML ================= */

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f2f2f2;font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<!-- Container -->
<table width="900" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:6px;overflow:hidden;">

<!-- Header -->
<tr>
<td style="background:#2c3e50;color:#ffffff;padding:15px 20px;">
    <h2 style="margin:0;font-size:18px;">Inward Article Status Report</h2>
    <p style="margin:5px 0 0;font-size:12px;">Workflow System</p>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:20px;">

<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
<thead>
<tr style="background:#f4f4f4;">
    <th style="border:1px solid #ccc;">S.No</th>
    <th align="left" style="border:1px solid #ccc;">Customer</th>
    <th align="left" style="border:1px solid #ccc;">Journal</th>
    <th align="left" style="border:1px solid #ccc;">Article Title</th>
    <th style="border:1px solid #ccc;">Received</th>
    <th style="border:1px solid #ccc;">Due</th>
    <th style="border:1px solid #ccc;">Manuscripts</th>
    <th style="border:1px solid #ccc;">Actual Hours</th>
</tr>
</thead>
<tbody>
';

if ($result && $result->num_rows > 0) {
    $sno = 1;
    while ($row = $result->fetch_assoc()) {

        $diff = 0;
        $pid   = (int)$row['id'];
        $dept   = (int)$row['department_id'];
        $stage = $db->real_escape_string($row['stage']);

        $transsql = $db->query("
            SELECT start_time, end_time
            FROM inw_transactions
            WHERE project_id = $pid
              AND stage = '$stage'
              AND dept = '$dept'
            ORDER BY created_dt ASC
        ");

        while ($row_t = $transsql->fetch_assoc()) {
            if (!empty($row_t['start_time']) && !empty($row_t['end_time'])) {
                $diff += strtotime($row_t['end_time']) - strtotime($row_t['start_time']);
            }
        }

        $html .= '
        <tr>
            <td align="center" style="border:1px solid #ccc;">'.$sno.'</td>
            <td style="border:1px solid #ccc;">'.htmlspecialchars($row['cust_name']).'</td>
            <td style="border:1px solid #ccc;">'.htmlspecialchars($row['j_name']).'</td>
            <td style="border:1px solid #ccc;">'.htmlspecialchars($row['pub_id']).'</td>
            <td align="center" style="border:1px solid #ccc;">'.$row['recv_dt'].'</td>
            <td align="center" style="border:1px solid #ccc;">'.$row['due_dt'].'</td>
            <td align="center" style="border:1px solid #ccc;">'.$row['manuscript_count'].'</td>
            <td align="center" style="border:1px solid #ccc;">'.getHoursMinutes($diff).'</td>
        </tr>';
        $sno++;
    }
} else {
    $html .= '
    <tr>
        <td colspan="8" align="center" style="border:1px solid #ccc;padding:10px;">
            No records found
        </td>
    </tr>';
}

$html .= '
</tbody>
</table>

</td>
</tr>

<!-- Footer -->
<tr>
<td style="background:#f9f9f9;padding:12px 20px;font-size:12px;color:#555;">
    <p style="margin:0;">
        This is an automated email from the Workflow Management System.<br>
        Please do not reply to this email.
    </p>
    <p style="margin:5px 0 0;">
        © '.date('Y').' Workflow System
    </p>
</td>
</tr>

</table>
<!-- Container End -->

</td>
</tr>
</table>

</body>
</html>
';

/* Preview (for testing) */
echo $html;

/* Email Headers */
$to = "recipient@example.com";
$mail_subject = "Workflow Report: Inward Articles & Time Tracking";
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Workflow System <noreply@yourdomain.com>\r\n";

/* Send Mail (enable when ready) */
include("smtpmail/phpmailer/class.phpmailer.php");


$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);

 
$mail->clearAllRecipients();

$mail->Subject = $mail_subject;
$mail->Body = $html;


//// Send mail to more than one user ///
//$to_mails = 'swamy@transforma.in';
$mail_to = explode(',',$to_mails);
foreach($mail_to as $email)
{
	if($email!='')
	{
		$mail->addAddress($email);
	}
}

$mail_tocc 		= explode(',',$cc_mails);
foreach($mail_tocc as $email)
{
	if($email!='')
	{
		$mail->AddCC($email);
	}
}


$mail_tobcc = explode(',', $bcc_mails);
foreach ($mail_tobcc as $email) {
    if ($email != '') {
        $mail->AddBCC($email);
    }
}

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;	
} else {
   echo "Message sent!";
}
?>

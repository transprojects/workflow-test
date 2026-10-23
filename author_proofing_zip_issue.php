<?php
include "includes/conn.php";
include "email-functions.php";
	$email_settings=mysqli_fetch_array(mysqli_query($db,"select * from email_settings where id=2"));

if (isset($_REQUEST['article_id'])) {

    $article_id = $_REQUEST['article_id'];
    $new_status = 'YET_TO_UPLOAD'; // new status to mark

    // Email recipients
    $sendto   = "marimuthu@transforma.in";
    $mail_cc  = "venkateshk@transforma.in";
    $mail_bcc = "dhamodaran@transforma.in";

  // --- 1. Update status in DB ---
    $update_sql = "
        UPDATE xperttrack_ftp_upload_history 
        SET status = '$new_status', updated_at = NOW() 
        WHERE article_id = '$article_id' AND stage = 'FP'
    ";
    $update_result = mysqli_query($db, $update_sql);

    if ($update_result && mysqli_affected_rows($db) > 0) {

        // --- 2. Fetch details for mail ---
        $fetch_sql = "
            SELECT * FROM xperttrack_ftp_upload_history 
            WHERE article_id = '$article_id'
        ";
        $fetch_result = mysqli_query($db, $fetch_sql);
        $article = mysqli_fetch_assoc($fetch_result);

        $zip_name = !empty($article['file_name']) ? $article['file_name'] : 'N/A';

        // --- 3. Success Mail ---
        $subject = "Re-upload Triggered - Author Proofing System Notification";
        $body = "
        Dear Team,<br><br>
        The ZIP file for the following article has been updated in the FTP upload history
        and is ready to be pushed again to the Author Proofing System.<br><br>
        <table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>
            <tr><td><b>Article ID</b></td><td>{$article_id}</td></tr>
            <tr><td><b>ZIP File</b></td><td>{$zip_name}</td></tr>
            <tr><td><b>Status</b></td><td>{$new_status}</td></tr>
        </table><br>
        Regards,<br>
        <b>Transforma Workflow System</b>
        ";

        $mail_status = sendMail(
            $email_settings['host'],
            $email_settings['port'],
            $email_settings['secure'],
            $email_settings['from_email'],
            $email_settings['from_password'],
            $email_settings['from_email'],
            $email_settings['from_name'],
            $sendto,
            $mail_cc,
            $mail_bcc,
            $subject,
            $body
        );

        if ($mail_status) {
            echo "[$article_id] ✅ Status updated and success mail sent.\n";
        } else {
            echo "[$article_id] ⚠️ Status updated, but success mail failed.\n";

            // --- 4. Error Mail (Mail Sending Failed) ---
            $err_subject = "❌ Mail Delivery Failed - Author Proofing System Update";
            $err_body = "
            Dear Admin,<br><br>
            The system attempted to send a success mail for Article ID <b>{$article_id}</b>, 
            but the mail delivery failed.<br><br>
            Please verify the mail configuration or server logs.<br><br>
            Regards,<br>
            Transforma Cron Job
            ";

            sendMail(
                $email_settings['host'],
                $email_settings['port'],
                $email_settings['secure'],
                $email_settings['from_email'],
                $email_settings['from_password'],
                $email_settings['from_email'],
                $email_settings['from_name'],
                $sendto, // notify admin only
                $mail_cc,
				$mail_bcc,
                $err_subject,
                $err_body
            );
        }

    } else {
        echo "[$article_id] ❌ Database update failed or no matching record.\n";

        // --- 5. Error Mail (DB Update Failed) ---
        $err_subject = "❌ FTP Status Update Failed - Author Proofing System";
        $err_body = "
        Dear Admin,<br><br>
        The cron job failed to update the FTP upload status for the following article:<br><br>
        <b>Article ID:</b> {$article_id}<br>
        <b>Expected Status:</b> {$new_status}<br><br>
        Please check the database or cron configuration.<br><br>
        Regards,<br>
        Transforma Cron Job
        ";

        sendMail(
            $email_settings['host'],
            $email_settings['port'],
            $email_settings['secure'],
            $email_settings['from_email'],
            $email_settings['from_password'],
            $email_settings['from_email'],
            $email_settings['from_name'],
            $sendto, // notify admin only
			$mail_cc,
            $mail_bcc,
            $err_subject,
            $err_body
        );
    }

} else {
    echo "❌ No article_id provided.\n";

    // --- 6. Error Mail (No Input) ---
    $err_subject = "❌ Missing Article ID in Cron - Author Proofing System";
    $err_body = "
    Dear Admin,<br><br>
    The cron script <b>update_ftp_status_cron.php</b> was executed without providing an <b>article_id</b> parameter.<br>
    Please verify the cron job command or calling logic.<br><br>
    Regards,<br>
    Transforma Cron Job
    ";

    sendMail(
        $email_settings['host'],
        $email_settings['port'],
        $email_settings['secure'],
        $email_settings['from_email'],
        $email_settings['from_password'],
        $email_settings['from_email'],
        $email_settings['from_name'],
        $sendto, // notify admin only                
        $mail_cc,
        $mail_bcc,
        $err_subject,
        $err_body
    );
}
?>

<?php
include "includes/conn.php";
include 'email-functions.php';

$email_settings = mysqli_fetch_array(mysqli_query($db,"SELECT * FROM email_settings WHERE id=2"));

// Debug: print all values
echo "<pre>";
print_r($email_settings);
echo "</pre>";

// Test recipient
$subject = "Test Email from Workflow System";
$body = "<h3>Hello!</h3><p>This is a test email to check PHPMailer integration.</p>";

// SMTP settings (example)
$result = sendMail(
    $email_settings['host'],
    $email_settings['port'],
    $email_settings['secure'],
    $email_settings['from_email'],
    $email_settings['from_password'],
    $email_settings['from_email'],
    $email_settings['from_name'],
    $email_settings['mail_to'],
    $email_settings['mail_cc'],
    $email_settings['mail_bcc'], // fixed: remove quotes around array
    $subject,
    $body
);

if($result){
    echo "✅ Test email sent successfully!";
}else{
    echo "❌ Failed to send test email. Check smtp_mail.log for details.";
}
?>

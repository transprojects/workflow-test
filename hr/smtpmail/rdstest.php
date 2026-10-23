<?php
$smtp_host 		= "mail.rdsindia.com";
$smtp_user 		= "xxxx@yourdomain.com";
$smtp_password 	= "xxxxxxxxxxxx";
$smtp_port 		= "25";
/*$smtp_ssl 		= "true";*/

$mail_from 		= "xxxx@yourdomain.com";
$mail_from_name	= "RDS Support";

$mail_to 		= "xxx@gmail.com";
$mail_to_name 	= "RDS Support";

?>
<html>
<head>
<title>PHPMailer - SMTP basic test with authentication</title>
</head>
<body>

<?php

//error_reporting(E_ALL);
error_reporting(E_STRICT);

date_default_timezone_set('Asia/Kolkata');

require_once('class.phpmailer.php');
// include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded

$mail             = new PHPMailer();

$body             = file_get_contents('contents.html');
//$body             = preg_replace('/[\]/','',$body);
echo $body;
echo "---";

$mail->IsSMTP(); // telling the class to use SMTP
$mail->Host       = "{$smtp_host}"; 	// SMTP server
$mail->SMTPDebug  = 2;                  // enables SMTP debug information (for testing)
                                        // 1 = errors and messages
                                        // 2 = messages only
$mail->SMTPAuth   = true;               // enable SMTP authentication
$mail->Host       = "{$smtp_host}"; 	// sets the SMTP server
$mail->Port       = $smtp_port;         // set the SMTP port for the GMAIL server
$mail->Username   = "{$smtp_user}"; 	// SMTP account username
$mail->Password   = "{$smtp_password}"; // SMTP account password

$mail->SetFrom("{$mail_from}", "{$mail_from_name}");

$mail->AddReplyTo("{$mail_from}", "{$mail_from_name}");

$mail->Subject    = "PHPMailer Test Subject via smtp, basic with authentication";

$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

$mail->MsgHTML($body);

$address = "{$mail_to}";
$mail->AddAddress($address, "{$mail_to_name}" );

//$mail->AddAttachment("images/phpmailer.gif");      // attachment
//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}

?>

</body>
</html>

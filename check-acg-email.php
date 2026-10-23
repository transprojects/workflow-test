<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // make sure PHPMailer is installed via Composer

// Log helper
function logMessage($message) {
    $logFile = __DIR__ . "/smtp_test.log";
    $date = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

function sendMail($host, $port, $secure, $username, $password, $from, $fromName, $to, $cc, $bcc, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port       = $port;
        $mail->CharSet    = "UTF-8";

        // Sender
        $mail->setFrom($from, $fromName);

        // To
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $mail->addAddress($recipient);
            }
        } else {
            $mail->addAddress($to);
        }

        // CC
        if (!empty($cc)) {
            foreach ((array)$cc as $c) {
                $mail->addCC($c);
            }
        }

        // BCC
        if (!empty($bcc)) {
            foreach ((array)$bcc as $b) {
                $mail->addBCC($b);
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();

        $successMsg = "✅ Email sent successfully via {$host}";
        echo $successMsg . "<br>";
        logMessage($successMsg);
        return true;
    } catch (Exception $e) {
        $errorMsg = "❌ Failed via {$host}. Error: {$mail->ErrorInfo}";
        echo $errorMsg . "<br>";
        logMessage($errorMsg);
        return false;
    }
}

// ===================== MAIN ===================== //

// Gmail Credentials
$username   = "productionoffice@acgpubs.org";   // Gmail or Google Workspace address
$password   = "g l b l v m n z l k c b z r i c";     // MUST use App Password

$from       = $username;
$fromName   = "ACG Test";

// Multiple recipients
$to   = ["marimuthu@transforma.in"];   // array of TO
$cc   = ["bosco@transforma.in"];         // array of CC
$bcc  = ["1987.mahesh@gmail.com"];       // array of BCC

$subject    = "SMTP Test - Gmail Setup with CC/BCC";
$body       = "This is a test email to verify Gmail SMTP connection with CC and BCC.";

// Send via Gmail
sendMail(
    "smtp.gmail.com", 
    465, 
    "ssl", 
    $username, 
    $password, 
    $from, 
    $fromName, 
    $to, 
    $cc, 
    $bcc, 
    $subject, 
    $body
);
?>

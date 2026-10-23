<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // make sure PHPMailer is installed via Composer

// Log helper
function logMessage($message) {
    $logFile = __DIR__ . "/smtp_mail.log";
    $date = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

function sendMail($host, $port, $secure, $username, $password, $from, $fromName, $to, $cc, $bcc, $subject, $body, $attachments = []) {
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

        // 📨 TO
        if (!empty($to)) {
            if (is_string($to)) $to = explode(',', $to);
            foreach ((array)$to as $recipient) {
                $recipient = trim($recipient);
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient);
                }
            }
        }

        // 🟡 CC
        if (!empty($cc)) {
            if (is_string($cc)) $cc = explode(',', $cc);
            foreach ((array)$cc as $c) {
                $c = trim($c);
                if (filter_var($c, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($c);
                }
            }
        }

        // 🔵 BCC
        if (!empty($bcc)) {
            if (is_string($bcc)) $bcc = explode(',', $bcc);
            foreach ((array)$bcc as $b) {
                $b = trim($b);
                if (filter_var($b, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($b);
                }
            }
        }

        // 📎 Attachments
        foreach ((array)$attachments as $filePath) {
            if (!empty($filePath) && file_exists($filePath)) {
                $mail->addAttachment($filePath);
            } else {
                logMessage("⚠️ Attachment skipped (not found or empty): {$filePath}");
            }
        }

        // ✉️ Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        logMessage("✅ Email sent successfully via {$host}");
        return true;
    } catch (Exception $e) {
        logMessage("❌ Failed via {$host}. Error: {$mail->ErrorInfo}");
        return false;
    }
}



?>

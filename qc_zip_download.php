<?php
/* Recipient download page for the QC tool report ZIPs emailed from qc_tool_log.php.
   The link alone is not enough: the visitor must enter one of the email addresses the ZIP was sent to and confirm it with a one-time code
   that is mailed to that address (valid 10 minutes, 5 tries). No workflow login is needed. Links expire after 7 days. */
include "includes/conn.php";   // starts the session and opens $db
$reports_dir = 'E:/Tool_Reports/';
$zdir = $reports_dir . '_zips/';
$token = isset($_GET['t']) ? $_GET['t'] : '';
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$job = null;
if (preg_match('/^[a-f0-9]{40}$/', $token) && ($r = $db->query("SELECT * FROM qc_zip_jobs WHERE token = '" . $token . "' AND status IN ('ready','emailed') AND expires_at > NOW()"))) { $job = $r->fetch_assoc(); }
if ($job && !is_file($zdir . $job['zip_file'])) { $job = null; }
$db->query("CREATE TABLE IF NOT EXISTS qc_zip_otp (id INT UNSIGNED NOT NULL AUTO_INCREMENT, job_id INT UNSIGNED NOT NULL, email VARCHAR(190) NOT NULL, code_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL, attempts INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY k_job (job_id, email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = ''; $err = ''; $stage = 'email'; $mail_in = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$ok_key = 'qz_ok_' . $token;

if ($job && isset($_GET['get']) && !empty($_SESSION[$ok_key]) && $_SESSION[$ok_key] > time()) {
	$db->query("UPDATE qc_zip_jobs SET downloads = downloads + 1, last_download = NOW() WHERE id = " . (int)$job['id']);
	while (ob_get_level()) { ob_end_clean(); }
	$zp = $zdir . $job['zip_file'];
	header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="QC_tool_reports_' . date('Ymd', strtotime($job['created_at'])) . '.zip"'); header('Content-Length: ' . filesize($zp));
	readfile($zp); exit;
}

if ($job && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$allowed = array_map('strtolower', array_map('trim', explode(',', $job['recipients'])));
	$jid = (int)$job['id']; $em = $db->real_escape_string($mail_in);
	if (isset($_POST['send_code'])) {
		$stage = 'code';
		$msg = 'If this address is on the recipient list, a 6-digit code has been emailed to it. It is valid for 10 minutes.';
		$r = $db->query("SELECT COUNT(*) n FROM qc_zip_otp WHERE job_id = $jid AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"); $x = $r ? $r->fetch_assoc() : array('n' => 0);
		$r = $db->query("SELECT COUNT(*) n FROM qc_zip_otp WHERE job_id = $jid AND email = '$em' AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)"); $y = $r ? $r->fetch_assoc() : array('n' => 0);
		if ((int)$x['n'] >= 15) { $err = 'Too many codes were requested. Try again later.'; $msg = ''; }
		elseif ((int)$y['n'] > 0) { $err = 'A code was sent a moment ago. Wait a minute before asking for another.'; $msg = ''; }
		elseif (in_array($mail_in, $allowed, true)) {
			$code = sprintf('%06d', function_exists('random_int') ? random_int(0, 999999) : mt_rand(0, 999999));
			$db->query("DELETE FROM qc_zip_otp WHERE job_id = $jid AND email = '$em'");
			$db->query("INSERT INTO qc_zip_otp (job_id, email, code_hash, expires_at, created_at) VALUES ($jid, '$em', '" . hash('sha256', $code . $token) . "', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())");
			$res_es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $res_es ? $res_es->fetch_assoc() : null;
			if ($es && !empty($es['from_password'])) {
				include_once("smtpmail/phpmailer/class.phpmailer.php");
				$mail = new PHPMailer(); $mail->IsSMTP(); $mail->CharSet = 'UTF-8'; $mail->Host = $es['host']; $mail->SMTPAuth = true; $mail->Port = $es['port']; $mail->Timeout = 30;
				$mail->Username = $es['from_email']; $mail->Password = $es['from_password']; $mail->SMTPSecure = $es['secure']; $mail->From = $es['from_email']; $mail->FromName = $es['from_name'] ?: $es['from_email'];
				$mail->isHTML(true); $mail->Subject = 'Your download code for the QC tool reports';
				$mail->Body = "Your one-time code is <b style='font-size:20px;letter-spacing:3px'>$code</b><br/>It is valid for 10 minutes. If you did not ask for it, ignore this email.";
				$mail->AltBody = "Your one-time code is $code (valid for 10 minutes).";
				$mail->addAddress($mail_in); if (!$mail->Send()) { error_log('qc_zip_download otp mail: ' . $mail->ErrorInfo); }
			}
		}
	} elseif (isset($_POST['verify'])) {
		$stage = 'code';
		$code = preg_replace('/\D/', '', isset($_POST['code']) ? $_POST['code'] : '');
		$r = $db->query("SELECT * FROM qc_zip_otp WHERE job_id = $jid AND email = '$em' AND expires_at > NOW() ORDER BY id DESC LIMIT 1"); $o = $r ? $r->fetch_assoc() : null;
		if (!$o || (int)$o['attempts'] >= 5) { $err = 'That code has expired or was tried too many times. Request a new code.'; }
		else {
			$db->query("UPDATE qc_zip_otp SET attempts = attempts + 1 WHERE id = " . (int)$o['id']);
			if (hash_equals($o['code_hash'], hash('sha256', $code . $token))) {
				$db->query("DELETE FROM qc_zip_otp WHERE id = " . (int)$o['id']);
				$_SESSION[$ok_key] = time() + 1800; $stage = 'ready';
			} else { $err = 'Incorrect code.'; }
		}
	}
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>QC tool reports download</title>
<style>body{font-family:Arial,sans-serif;background:#f3f6fa;color:#22303f;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center}
.c{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.08);padding:28px 32px;width:min(420px,92vw)}
h1{font-size:20px;margin:0 0 6px;color:#14304a}p{color:#6b7785;font-size:14px;line-height:1.5}input[type=text],input[type=email]{width:100%;box-sizing:border-box;height:44px;border:1px solid #d5dde6;border-radius:10px;padding:0 12px;font-size:15px;margin:6px 0 14px}
button,a.b{display:inline-block;height:44px;line-height:44px;padding:0 22px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);cursor:pointer;text-decoration:none;font-size:15px}
.err{color:#d64545;font-size:14px;margin:0 0 10px}.ok{color:#1f9d6a;font-size:14px;margin:0 0 10px}small{color:#6b7785}</style></head><body><div class="c">
<?php if (!$job) { ?>
<h1>Link not available</h1><p>This download link is invalid or has expired. Ask the sender to prepare a new one.</p>
<?php } elseif ($stage === 'ready' || (!empty($_SESSION[$ok_key]) && $_SESSION[$ok_key] > time())) { ?>
<h1>QC tool reports</h1><p>Verified. Your download is ready (<?php echo $e(round($job['zip_size'] / 1048576, 1)); ?> MB). The link expires on <?php echo $e(date('d-M-Y H:i', strtotime($job['expires_at']))); ?>.</p>
<a class="b" href="?t=<?php echo $e($token); ?>&amp;get=1">Download ZIP</a>
<?php } elseif ($stage === 'code') { ?>
<h1>Enter your code</h1><?php if ($msg) { echo '<p class="ok">' . $e($msg) . '</p>'; } if ($err) { echo '<p class="err">' . $e($err) . '</p>'; } ?>
<form method="post"><input type="hidden" name="email" value="<?php echo $e($mail_in); ?>"><label>6-digit code</label><input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus>
<button type="submit" name="verify" value="1">Verify</button></form>
<form method="post" style="margin-top:12px"><input type="hidden" name="email" value="<?php echo $e($mail_in); ?>"><small>No code? </small><button type="submit" name="send_code" value="1" style="height:auto;line-height:1;padding:6px 0;background:none;color:#e8742a">Send a new code</button></form>
<?php } else { ?>
<h1>QC tool reports</h1><p>To protect these reports, confirm your email address. A one-time code will be sent to it.</p>
<form method="post"><label>Your email address</label><input type="email" name="email" required autofocus><button type="submit" name="send_code" value="1">Send code</button></form>
<?php } ?>
</div></body></html>

<?php
session_name('tooltester');
session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax'));
session_start();
$cfg = include __DIR__ . '/auth_config.php';
$error = '';

// Simple brute-force throttle: 5 failures per session -> 30s lockout.
if (!isset($_SESSION['tt_fail'])) { $_SESSION['tt_fail'] = 0; $_SESSION['tt_lock'] = 0; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$u = isset($_POST['username']) ? trim($_POST['username']) : '';
	$p = isset($_POST['password']) ? $_POST['password'] : '';
	if (time() < $_SESSION['tt_lock']) {
		$error = 'Too many attempts. Wait a few seconds and try again.';
	} elseif (isset($cfg['users'][$u]) && password_verify($p, $cfg['users'][$u])) {
		session_regenerate_id(true);
		$_SESSION['tt_user'] = $u;
		$_SESSION['tt_fail'] = 0;
		header('Location: index.php');
		exit;
	} else {
		usleep(500000);
		$error = 'Invalid username or password.';
		if (++$_SESSION['tt_fail'] >= 5) { $_SESSION['tt_lock'] = time() + 30; $_SESSION['tt_fail'] = 0; }
	}
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Tool Tester - Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:-apple-system,'Segoe UI',Arial,sans-serif;background:#f4f5f7;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
form{background:#fff;padding:32px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.12);width:300px}
h2{margin:0 0 20px;font-size:18px}
input{width:100%;box-sizing:border-box;padding:10px;margin-bottom:12px;border:1px solid #ccc;border-radius:6px;font-size:14px}
button{width:100%;padding:10px;background:#e85a00;color:#fff;border:0;border-radius:6px;font-weight:600;cursor:pointer;font-size:14px}
.err{color:#c00;font-size:13px;margin-bottom:12px}
</style></head><body>
<form method="post">
<h2>Tool Tester - Login</h2>
<?php if ($error) { echo '<div class="err">' . htmlspecialchars($error) . '</div>'; } ?>
<input name="username" placeholder="Username" autofocus autocomplete="username">
<input name="password" type="password" placeholder="Password" autocomplete="current-password">
<button type="submit">Log in</button>
</form></body></html>
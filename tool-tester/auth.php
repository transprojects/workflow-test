<?php
// Include at the top of every page/endpoint in this folder. Redirects browsers to
// login.php (or returns 401 JSON to AJAX callers) unless a valid session exists.
session_name('tooltester');
session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax'));
session_start();

if (empty($_SESSION['tt_user'])) {
	$is_ajax = ($_SERVER['REQUEST_METHOD'] === 'POST');
	if ($is_ajax) {
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode(array('ok' => false, 'error' => 'Session expired - please reload and log in again.'));
	} else {
		header('Location: login.php');
	}
	exit;
}
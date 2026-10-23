<?php
require __DIR__ . '/auth.php';
// Deletes one run folder. Path is re-validated against the actual runs/ tree the
// same way view_file.php does, so this can only ever remove something under runs/.
header('Content-Type: application/json');

$tool_key = isset($_POST['tool']) ? $_POST['tool'] : '';
$run_id   = isset($_POST['run']) ? $_POST['run'] : '';

if (!preg_match('/^[A-Za-z0-9_-]+$/', $tool_key) || !preg_match('/^[A-Za-z0-9_]+$/', $run_id)) {
	echo json_encode(array('ok' => false, 'error' => 'Invalid request.'));
	exit;
}

$run_dir = realpath(__DIR__ . '/runs/' . $tool_key . '/' . $run_id);
$runs_root = realpath(__DIR__ . '/runs');
if ($run_dir === false || strpos($run_dir, $runs_root) !== 0) {
	echo json_encode(array('ok' => false, 'error' => 'Run not found.'));
	exit;
}

foreach (scandir($run_dir) as $f) {
	if ($f === '.' || $f === '..') { continue; }
	unlink($run_dir . DIRECTORY_SEPARATOR . $f);
}
rmdir($run_dir);

echo json_encode(array('ok' => true));

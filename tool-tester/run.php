<?php
require __DIR__ . '/auth.php';
// AJAX handler: runs a registered tool against an uploaded file inside an isolated
// run folder, then reports whatever files appeared/changed as a result.
header('Content-Type: application/json');

function fail($msg) {
	echo json_encode(array('ok' => false, 'error' => $msg));
	exit;
}

$tools = include __DIR__ . '/tools_config.php';

$tool_key = isset($_POST['tool']) ? $_POST['tool'] : '';
if (!isset($tools[$tool_key])) {
	fail('Unknown tool.');
}
$tool = $tools[$tool_key];

if (!file_exists($tool['exe'])) {
	fail('Configured exe not found on disk: ' . $tool['exe']);
}

if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
	fail('No file uploaded, or upload failed.');
}

$orig_name = basename($_FILES['upload_file']['name']);
$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
$accept_ext = isset($tool['accept_ext']) ? $tool['accept_ext'] : array();
if (!empty($accept_ext) && !in_array($ext, $accept_ext)) {
	fail('This tool only accepts: .' . implode(', .', $accept_ext));
}

// Keep only safe filename characters.
$safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig_name);
if ($safe_name === '') { $safe_name = 'upload.' . $ext; }

// Isolated per-run folder so concurrent tests never collide.
$run_id = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
$run_dir = __DIR__ . '/runs/' . $tool_key . '/' . $run_id;
if (!mkdir($run_dir, 0777, true)) {
	fail('Could not create run folder.');
}

$target_file = $run_dir . DIRECTORY_SEPARATOR . $safe_name;
if (!move_uploaded_file($_FILES['upload_file']['tmp_name'], $target_file)) {
	fail('Could not save uploaded file into run folder.');
}

// Build extra CLI flags strictly from the enumerated choices in config —
// never from free-typed input — so this stays immune to shell injection.
$extra_args = '';
if (!empty($tool['options'])) {
	foreach ($tool['options'] as $opt_key => $opt) {
		$posted = isset($_POST['opt_' . $opt_key]) ? $_POST['opt_' . $opt_key] : $opt['default'];
		if (in_array($posted, $opt['choices'], true)) {
			$extra_args .= ' ' . $opt['flag'] . ' ' . escapeshellarg($posted);
		}
	}
}

file_put_contents($run_dir . '/_meta.json', json_encode(array(
	'tool_key'   => $tool_key,
	'tool_label' => $tool['label'],
	'input_file' => $safe_name,
	'created_at' => date('Y-m-d H:i:s'),
)));

// Prepend any declared helper-tool folders to PATH so bare-name subprocess calls
// (e.g. IAPQualityChecker.exe shelling out to "xsltproc") can find them, without
// needing a system-wide PATH change.
if (!empty($tool['path_dirs'])) {
	$path_prefix = '';
	foreach ($tool['path_dirs'] as $dir) {
		if (is_dir($dir)) {
			$path_prefix .= $dir . ';';
		}
	}
	if ($path_prefix !== '') {
		putenv('PATH=' . $path_prefix . getenv('PATH'));
	}
}

$before = scandir($run_dir);

$cmd = '"' . $tool['exe'] . '" "' . $target_file . '"' . $extra_args . ' 2>&1';
$output = shell_exec($cmd);

$after = scandir($run_dir);
$new_or_changed = array_values(array_diff($after, $before));
// Also surface the uploaded file's own presence for context, but list result files separately.
$result_files = array();
foreach ($new_or_changed as $fname) {
	if ($fname === '.' || $fname === '..') { continue; }
	$fpath = $run_dir . DIRECTORY_SEPARATOR . $fname;
	if (is_file($fpath)) {
		$result_files[] = array(
			'name'  => $fname,
			'size'  => filesize($fpath),
			'ext'   => strtolower(pathinfo($fname, PATHINFO_EXTENSION)),
			'url'   => 'view_file.php?tool=' . urlencode($tool_key) . '&run=' . urlencode($run_id) . '&file=' . urlencode($fname),
		);
	}
}

echo json_encode(array(
	'ok'          => true,
	'run_id'      => $run_id,
	'command'     => '"' . basename($tool['exe']) . '" "' . $safe_name . '"' . $extra_args,
	'output'      => trim((string)$output),
	'input_file'  => $safe_name,
	'result_files'=> $result_files,
));

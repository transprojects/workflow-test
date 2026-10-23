<?php
require __DIR__ . '/auth.php';
// Serves a single file out of a specific run folder. Everything is re-derived and
// re-validated from the run folder's actual listing — nothing in the path is trusted
// as given, so ../ traversal or reaching outside runs/<tool>/<run_id>/ is not possible.
$tool_key = isset($_GET['tool']) ? $_GET['tool'] : '';
$run_id   = isset($_GET['run']) ? $_GET['run'] : '';
$file     = isset($_GET['file']) ? $_GET['file'] : '';

if (!preg_match('/^[A-Za-z0-9_-]+$/', $tool_key) || !preg_match('/^[A-Za-z0-9_]+$/', $run_id)) {
	http_response_code(400);
	exit('Invalid request.');
}

$run_dir = realpath(__DIR__ . '/runs/' . $tool_key . '/' . $run_id);
$runs_root = realpath(__DIR__ . '/runs');
if ($run_dir === false || strpos($run_dir, $runs_root) !== 0) {
	http_response_code(404);
	exit('Run not found.');
}

// Only ever serve a filename that's actually present in this run folder's own listing.
$actual_files = scandir($run_dir);
$safe_file = basename($file);
if (!in_array($safe_file, $actual_files, true)) {
	http_response_code(404);
	exit('File not found in this run.');
}

$full_path = $run_dir . DIRECTORY_SEPARATOR . $safe_file;
$ext = strtolower(pathinfo($safe_file, PATHINFO_EXTENSION));

$inline_types = array(
	'html' => 'text/html; charset=UTF-8',
	'htm'  => 'text/html; charset=UTF-8',
	'txt'  => 'text/plain; charset=UTF-8',
	'xml'  => 'text/xml; charset=UTF-8',
	'json' => 'application/json; charset=UTF-8',
	'md'   => 'text/plain; charset=UTF-8',
);

if ($ext === 'html' || $ext === 'htm') {
	// Inject a floating "Download PDF" button that just triggers the browser's own
	// print-to-PDF — reports already ship @media print rules, so this stays
	// pixel-faithful and works for any future tool's report without extra wiring.
	$html = file_get_contents($full_path);
	$pdf_button = '<button id="__pdfDownloadBtn" onclick="window.print()" '
		. 'style="position:fixed;top:16px;right:16px;z-index:99999;background:#e85a00;'
		. 'color:#fff;border:none;border-radius:8px;padding:10px 18px;'
		. 'font-family:-apple-system,\'Segoe UI\',Arial,sans-serif;font-size:13px;'
		. 'font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.25);">'
		. 'Download PDF</button>'
		. '<style>@media print { #__pdfDownloadBtn { display:none !important; } }</style>';

	if (stripos($html, '</body>') !== false) {
		$html = str_ireplace('</body>', $pdf_button . '</body>', $html);
	} else {
		$html .= $pdf_button;
	}

	header('Content-Type: text/html; charset=UTF-8');
	header('Content-Length: ' . strlen($html));
	echo $html;
	exit;
}

if (isset($inline_types[$ext])) {
	header('Content-Type: ' . $inline_types[$ext]);
} else {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . $safe_file . '"');
}
header('Content-Length: ' . filesize($full_path));
readfile($full_path);

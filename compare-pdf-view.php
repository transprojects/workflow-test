<?php
/* PDF compare tool: show the generated report inline (result frame / "Open Full Report").
   Serves the self-contained copy (page images embedded), so the pictures show wherever it is opened from. */

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';                 // signed-in users only (401 otherwise)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';

ini_set('display_errors', 0);

$jobId = $_GET['job_id'] ?? '';

if ($jobId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $jobId)) {
    http_response_code(400);
    echo 'Invalid job ID.';
    exit;
}

$jobDir = cmp_jobs_dir() . $jobId . '/';
$reportFile = $jobDir . 'compare_report.html';

if (!is_file($reportFile)) {
    http_response_code(404);
    include_once __DIR__ . '/dbc.php';
    echo htmlspecialchars(cmp_filed_note($db, $jobId));
    exit;
}

$meta = is_file($jobDir . 'meta.json') ? json_decode(file_get_contents($jobDir . 'meta.json'), true) : null;
$file = cmp_standalone_report($jobDir, $reportFile, $meta);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, max-age=300');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;

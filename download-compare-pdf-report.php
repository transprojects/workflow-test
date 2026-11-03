<?php

/*
==================================================
DOWNLOAD A PDF COMPARISON REPORT

Streams the self-contained report (page images
embedded) to the browser as an attachment.

The copy in the article's PRQC folder
({work_dir}{volume_folder}/{pub_id}/{stage}/PRQC/) is
made automatically when the comparison completes
(compare-pdf-status.php -> cmp_file_report), so it
is already there even if pdf_compare_jobs is cleaned
later. This script reports that outcome back through
response headers (read by the frontend), since the
response body itself is the file.
==================================================
*/

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';      // signed-in users only (401 otherwise)
include_once __DIR__ . '/dbc.php';                       // $db (mysqli)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);


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
    echo cmp_filed_note($db, $jobId);
    exit;

}


/* normally already filed at completion; files it now if that did not happen (older jobs) */
$res = cmp_file_report($db, $jobDir, $jobId);

if ($res === null) {

    $res = [
        'saved' => false, 'prqc_path' => '', 'work_link' => '', 'source_link' => '',
        'message' => 'The report is still being filed. Please try again in a moment.'
    ];

}

$meta = is_file($jobDir . 'meta.json') ? json_decode(file_get_contents($jobDir . 'meta.json'), true) : null;
$file = cmp_standalone_report($jobDir, $reportFile, $meta);


$downloadName = 'PDF_Comparison_Report_' . $jobId . '.html';

header('Content-Description: File Transfer');
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));

header('X-Prqc-Saved: ' . (!empty($res['saved']) ? '1' : '0'));
header('X-Prqc-Path: ' . rawurlencode($res['prqc_path'] ?? ''));
header('X-Prqc-Message: ' . rawurlencode($res['message'] ?? ''));
header('X-Prqc-Link: ' . rawurlencode($res['work_link'] ?? ''));
header('X-Source-Link: ' . rawurlencode($res['source_link'] ?? ''));
header('Access-Control-Expose-Headers: X-Prqc-Saved, X-Prqc-Path, X-Prqc-Message, X-Prqc-Link, X-Source-Link');

readfile($file);
exit;

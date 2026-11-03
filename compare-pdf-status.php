<?php

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';                 // signed-in users only (401 otherwise)
include_once __DIR__ . '/dbc.php';                                  // $db (mysqli)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';



/*
==================================================
GET JOB ID
==================================================
*/

$jobId =
    $_GET['job_id'] ?? '';



/*
==================================================
VALIDATE JOB ID
==================================================
*/

if (
    empty($jobId) ||
    !preg_match(
        '/^[A-Za-z0-9_-]+$/',
        $jobId
    )
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid job ID.'
    ]);

    exit;

}



/*
==================================================
JOB DIRECTORY
==================================================
*/

$jobDir = cmp_jobs_dir() . $jobId . '/';



/*
==================================================
STATUS FILE
==================================================
*/

$statusFile =
    $jobDir .
    'status.json';



/*
==================================================
CHECK STATUS FILE
==================================================
*/

if (
    !file_exists(
        $statusFile
    )
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Comparison job status not found.'
    ]);

    exit;

}



/*
==================================================
READ STATUS
==================================================
*/

$status =
    json_decode(
        file_get_contents(
            $statusFile
        ),
        true
    );



if (
    !is_array($status)
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid comparison status.'
    ]);

    exit;

}



/*
==================================================
DEFAULT RESPONSE
==================================================
*/

/*
==================================================
STUCK JOB: the worker never started or crashed
(missing Python libraries, wrong Python path ...).
Show the real reason instead of "queued" forever.
==================================================
*/

if (($status['status'] ?? '') === 'queued') {

    $pyLog = $jobDir . 'python.log';
    $logText = is_file($pyLog) ? (string)file_get_contents($pyLog) : '';
    $age = time() - (int)filemtime($statusFile);

    if (preg_match('/Traceback|ModuleNotFoundError|ImportError|is not recognized|No such file|not found/i', $logText)) {

        $tail = array_slice(array_filter(array_map('trim', explode("\n", $logText))), -4);

        echo json_encode([
            'success' => true,
            'status' => 'error',
            'message' => 'The comparison could not start on the server: ' . implode(' | ', $tail)
        ]);

        exit;

    }

    if ($age > 120) {

        echo json_encode([
            'success' => true,
            'status' => 'error',
            'message' => 'The comparison did not start within 2 minutes. Ask IT to check that Python (py.exe) and its libraries (pymupdf, opencv, numpy) are installed for the web server. Details: ' .
                ($logText !== '' ? trim(substr($logText, -300)) : 'python.log is empty.')
        ]);

        exit;

    }

}


$response = [

    'success' =>
        true,

    'status' =>
        $status['status']
        ?? 'processing',

    'progress' =>
        (int)(
            $status['progress']
            ?? 0
        ),

    'current_page' =>
        (int)(
            $status['current_page']
            ?? 0
        ),

    'total_pages' =>
        (int)(
            $status['total_pages']
            ?? 0
        ),

    'message' =>
        $status['message']
        ?? 'Processing...'

];



/*
==================================================
COMPLETED
==================================================
*/

if (
    ($status['status'] ?? '') ===
    'completed'
) {


    $reportFile =
        $jobDir .
        'compare_report.html';


    if (
        file_exists(
            $reportFile
        )
    ) {


        /*
        ------------------------------------------
        IMPORTANT:
        This is a browser-accessible URL.
        ------------------------------------------
        */

        $response['report'] = 'compare-pdf-view.php?job_id=' . rawurlencode($jobId);

        /* file the report into the article's PRQC folder now (once), so it is safe even if this job folder is cleaned */
        $filing = cmp_file_report($db, $jobDir, $jobId);

        if ($filing !== null) {
            $response['filing'] = $filing;
        }

    } else {

        $response['status'] =
            'error';

        $response['message'] =
            'Comparison completed but HTML report was not found.';

    }

}



/*
==================================================
RETURN JSON
==================================================
*/

echo json_encode(
    $response
);

exit;
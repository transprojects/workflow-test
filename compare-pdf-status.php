<?php

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);



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

$jobDir =
    __DIR__ .
    '/pdf_compare_jobs/' .
    $jobId .
    '/';



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

        $response['report'] =
            'pdf_compare_jobs/' .
            rawurlencode(
                $jobId
            ) .
            '/compare_report.html';

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
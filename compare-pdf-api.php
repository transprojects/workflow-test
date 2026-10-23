<?php

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);


/*
==================================================
DIRECTORIES
==================================================
*/

$baseDir =
    __DIR__;


$uploadDir =
    $baseDir . '/uploads/';


$jobsDir =
    $baseDir . '/pdf_compare_jobs/';



/*
==================================================
CREATE DIRECTORIES
==================================================
*/

if (!is_dir($uploadDir)) {

    mkdir(
        $uploadDir,
        0777,
        true
    );

}


if (!is_dir($jobsDir)) {

    mkdir(
        $jobsDir,
        0777,
        true
    );

}



/*
==================================================
CHECK FILES
==================================================
*/

if (
    !isset($_FILES['file1']) ||
    !isset($_FILES['file2'])
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Please select both PDF files.'
    ]);

    exit;

}



/*
==================================================
UPLOAD ERROR
==================================================
*/

if (
    $_FILES['file1']['error'] !==
    UPLOAD_ERR_OK ||

    $_FILES['file2']['error'] !==
    UPLOAD_ERR_OK
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'PDF upload failed.'
    ]);

    exit;

}



/*
==================================================
VALIDATE PDF EXTENSION
==================================================
*/

function isPdfFile($file)
{

    $extension =
        strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );


    return (
        $extension === 'pdf'
    );

}


if (
    !isPdfFile($_FILES['file1']) ||
    !isPdfFile($_FILES['file2'])
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Only PDF files are allowed.'
    ]);

    exit;

}



/*
==================================================
GENERATE UNIQUE JOB ID
==================================================
*/

$jobId =
    date('Ymd_His') .
    '_' .
    bin2hex(
        random_bytes(5)
    );


$jobDir =
    $jobsDir .
    $jobId .
    '/';



/*
==================================================
CREATE JOB DIRECTORY
==================================================
*/

if (
    !mkdir(
        $jobDir,
        0777,
        true
    )
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Unable to create comparison job.'
    ]);

    exit;

}



/*
==================================================
PDF PATHS
==================================================
*/

$file1Path =
    $jobDir . 'file1.pdf';


$file2Path =
    $jobDir . 'file2.pdf';



/*
==================================================
MOVE UPLOADED FILE 1
==================================================
*/

if (
    !move_uploaded_file(
        $_FILES['file1']['tmp_name'],
        $file1Path
    )
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Unable to save first PDF.'
    ]);

    exit;

}



/*
==================================================
MOVE UPLOADED FILE 2
==================================================
*/

if (
    !move_uploaded_file(
        $_FILES['file2']['tmp_name'],
        $file2Path
    )
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Unable to save second PDF.'
    ]);

    exit;

}



/*
==================================================
SAVE ORIGINAL FILE NAMES

Needed later (download-compare-pdf-report.php) to
work out which article/PRQC folder these PDFs
belong to. The PDFs themselves get renamed to
file1.pdf/file2.pdf above, so the original names
would otherwise be lost.
==================================================
*/

$metaFile =
    $jobDir .
    'meta.json';


file_put_contents(
    $metaFile,
    json_encode([
        'file1_name' =>
            basename($_FILES['file1']['name']),

        'file2_name' =>
            basename($_FILES['file2']['name'])
    ])
);



/*
==================================================
INITIAL STATUS
==================================================
*/

$statusFile =
    $jobDir .
    'status.json';


file_put_contents(
    $statusFile,
    json_encode([
        'status' =>
            'queued',

        'progress' =>
            0,

        'current_page' =>
            0,

        'total_pages' =>
            0,

        'message' =>
            'Comparison queued.'
    ])
);



/*
==================================================
PYTHON CONFIGURATION
==================================================

IMPORTANT:

Change this depending on your environment.

Windows example:

$python = 'C:/Python312/python.exe';

or:

$python = 'C:/Users/username/AppData/Local/Programs/Python/Python312/python.exe';


Linux:

$python = '/usr/bin/python3';

Virtual environment:

$python = '/home/user/venv/bin/python';

==================================================
*/

$python =
    'C:\\Windows\\py.exe';


$pythonScript =
    $baseDir .
    '/python-scripts/pdf_compare/pdf_compare.py';



/*
==================================================
PYTHON COMMAND
==================================================
*/

$command =
    escapeshellarg($python) .
    ' ' .
    escapeshellarg($pythonScript) .
    ' ' .
    escapeshellarg($file1Path) .
    ' ' .
    escapeshellarg($file2Path) .
    ' ' .
    escapeshellarg($jobDir);



/*
==================================================
LOG FILE
==================================================
*/

$pythonLog =
    $jobDir .
    'python.log';



/*
==================================================
START PYTHON IN BACKGROUND
==================================================
*/

if (
    strtoupper(
        substr(
            PHP_OS,
            0,
            3
        )
    ) === 'WIN'
) {


    /*
    ----------------------------------------------
    Windows
    ----------------------------------------------
    */

    $windowsCommand =
        'start /B "" ' .
        $command .
        ' > ' .
        escapeshellarg(
            $pythonLog
        ) .
        ' 2>&1';


    pclose(
        popen(
            $windowsCommand,
            'r'
        )
    );


} else {


    /*
    ----------------------------------------------
    Linux / Unix
    ----------------------------------------------
    */

    $linuxCommand =
        $command .
        ' > ' .
        escapeshellarg(
            $pythonLog
        ) .
        ' 2>&1 &';


    exec(
        $linuxCommand
    );

}



/*
==================================================
RETURN JOB ID IMMEDIATELY
==================================================
*/

echo json_encode([

    'success' =>
        true,

    'job_id' =>
        $jobId,

    'message' =>
        'PDF comparison started.'

]);

exit;
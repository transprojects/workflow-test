<?php

header('Content-Type: application/json');

ini_set('display_errors', 0);
@set_time_limit(0);


/*
==================================================
UPLOAD SIZE HANDLING

Large PDFs (hundreds of MB) hit the server's
upload_max_filesize / post_max_size. Say so
plainly instead of a generic "upload failed".
==================================================
*/

function cmpIniBytes($v)
{
    $v = trim((string)$v);
    $n = (float)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        case 'm': $n *= 1024;
        case 'k': $n *= 1024;
    }
    return (int)$n;
}

function cmpFmtSize($b)
{
    if ($b >= 1073741824) { return round($b / 1073741824, 2) . ' GB'; }
    if ($b >= 1048576) { return round($b / 1048576, 2) . ' MB'; }
    return round($b / 1024, 1) . ' KB';
}

function cmpUploadError()
{
    $codes = [
        UPLOAD_ERR_INI_SIZE   => 'is larger than the server limit upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => 'is larger than the allowed size',
        UPLOAD_ERR_PARTIAL    => 'was only partly uploaded (connection interrupted) - please try again',
        UPLOAD_ERR_NO_FILE    => 'was not received',
        UPLOAD_ERR_NO_TMP_DIR => 'could not be stored: the server has no temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'could not be written to disk on the server (disk full or no permission)',
        UPLOAD_ERR_EXTENSION  => 'was stopped by a server extension'
    ];
    $msgs = [];
    foreach (['file1' => 'First PDF', 'file2' => 'Second PDF'] as $key => $label) {
        $err = $_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            $msgs[] = $label . ' (' . basename($_FILES[$key]['name'] ?? '') . ') ' . ($codes[$err] ?? 'failed to upload (error ' . $err . ')') . '.';
        }
    }
    return 'PDF upload failed. ' . implode(' ', $msgs);
}

$cmpPostMax = cmpIniBytes(ini_get('post_max_size'));

if (
    empty($_FILES) &&
    $cmpPostMax > 0 &&
    isset($_SERVER['CONTENT_LENGTH']) &&
    (int)$_SERVER['CONTENT_LENGTH'] > $cmpPostMax
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'The two PDFs together (' . cmpFmtSize((int)$_SERVER['CONTENT_LENGTH']) .
            ') are larger than the server limit post_max_size (' . ini_get('post_max_size') .
            '). Please ask IT to raise upload_max_filesize and post_max_size.'
    ]);

    exit;

}
error_reporting(E_ALL);

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';                 // signed-in users only (401 otherwise)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';


/*
==================================================
DIRECTORIES
==================================================
*/

$baseDir =
    __DIR__;


$uploadDir =
    $baseDir . '/uploads/';


$jobsDir = cmp_jobs_dir();



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
            cmpUploadError()
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

        'file1_size' =>
            filesize($file1Path),

        'file2_size' =>
            filesize($file2Path),

        'uploaded_at' =>
            date('Y-m-d H:i:s'),

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

/*
Live server: C:\Windows\py.exe. On any other machine (a developer PC, Linux)
fall back to the interpreter on PATH instead of leaving the job queued forever.
*/

$python =
    'C:\\Windows\\py.exe';

if (!file_exists($python)) {

    $python =
        (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? 'py'
            : 'python3';

}


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
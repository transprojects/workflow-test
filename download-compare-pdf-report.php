<?php

/*
==================================================
DOWNLOAD (AND AUTO-FILE) A PDF COMPARISON REPORT

Streams the generated compare_report.html back to
the browser as an attachment. Before streaming, it
tries to work out which article the compared PDFs
belong to (from their original filenames, which
match the article's pub_id) and, if found, copies
the report into that article's PRQC folder on the
server:

    {work_dir}{volume_folder}/{pub_id}/{stage}/PRQC/

The outcome of that auto-file attempt is reported
back via response headers (read by the frontend)
since the response body itself is the file.
==================================================
*/

require_once __DIR__ . '/includes/DBController.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);



/*
==================================================
JOB ID
==================================================
*/

$jobId =
    $_GET['job_id'] ?? '';


if (
    empty($jobId) ||
    !preg_match(
        '/^[A-Za-z0-9_-]+$/',
        $jobId
    )
) {

    http_response_code(400);
    echo 'Invalid job ID.';
    exit;

}


$jobDir =
    __DIR__ .
    '/pdf_compare_jobs/' .
    $jobId .
    '/';


$reportFile =
    $jobDir .
    'compare_report.html';


if (!file_exists($reportFile)) {

    http_response_code(404);
    echo 'Comparison report not found.';
    exit;

}



/*
==================================================
TRY TO IDENTIFY THE SOURCE ARTICLE

The compared PDFs are named file1.pdf/file2.pdf on
disk, but their ORIGINAL names (saved at upload
time in meta.json) follow the workflow convention
of being named after the article's pub_id, e.g.
"TSP_CMC_88018.pdf".
==================================================
*/

$prqcSaved = false;
$prqcPath = '';
$prqcMessage = 'Could not determine the source article automatically.';

$metaFile = $jobDir . 'meta.json';

$candidateNames = [];

if (file_exists($metaFile)) {

    $meta =
        json_decode(
            file_get_contents($metaFile),
            true
        );

    if (is_array($meta)) {

        foreach (['file1_name', 'file2_name'] as $key) {

            if (!empty($meta[$key])) {

                $candidateNames[] =
                    pathinfo(
                        $meta[$key],
                        PATHINFO_FILENAME
                    );

            }

        }

    }

}


function findArticleByPubIdCandidate($dbc, $candidate)
{

    $candidate = trim($candidate);

    if ($candidate === '') {
        return null;
    }


    /*
    Exact match first — this is the standard
    convention (PDF is literally named
    "{pub_id}.pdf").
    */

    $rows = $dbc->runQuery(
        "SELECT work_dir, volume_folder, pub_id, stage
         FROM inw_inward_dtl
         WHERE pub_id = ?
         LIMIT 1",
        's',
        [$candidate]
    );

    if (!empty($rows)) {
        return $rows[0];
    }


    /*
    Fallback: strip a trailing " (1)" style
    duplicate-download suffix and try again.
    */

    $stripped =
        preg_replace(
            '/\s*\(\d+\)$/',
            '',
            $candidate
        );

    if ($stripped !== $candidate && $stripped !== '') {

        $rows = $dbc->runQuery(
            "SELECT work_dir, volume_folder, pub_id, stage
             FROM inw_inward_dtl
             WHERE pub_id = ?
             LIMIT 1",
            's',
            [$stripped]
        );

        if (!empty($rows)) {
            return $rows[0];
        }

    }


    return null;

}


if (!empty($candidateNames)) {

    $dbc = new DBController();

    $article = null;

    foreach ($candidateNames as $candidate) {

        $article = findArticleByPubIdCandidate($dbc, $candidate);

        if ($article !== null) {
            break;
        }

    }


    if (
        $article !== null &&
        !empty($article['work_dir']) &&
        !empty($article['volume_folder']) &&
        !empty($article['pub_id']) &&
        !empty($article['stage'])
    ) {


        $prqcPath =
            $article['work_dir'] .
            $article['volume_folder'] .
            '/' .
            $article['pub_id'] .
            '/' .
            $article['stage'] .
            '/PRQC/';


        if (
            is_dir($prqcPath) ||
            mkdir($prqcPath, 0777, true)
        ) {

            $destFile =
                $prqcPath .
                $article['pub_id'] .
                '_Compare_Report_' .
                date('Ymd_His') .
                '.html';


            if (@copy($reportFile, $destFile)) {

                $prqcSaved = true;

                $prqcMessage =
                    'Report saved to PRQC folder: ' .
                    $destFile;

            } else {

                $prqcMessage =
                    'Found article ' .
                    $article['pub_id'] .
                    ' but could not write the report to its PRQC folder (check server path/permissions).';

            }

        } else {

            $prqcMessage =
                'Found article ' .
                $article['pub_id'] .
                ' but its PRQC folder could not be created: ' .
                $prqcPath;

        }

    } else {

        $prqcMessage =
            'No matching article (pub_id) found for the compared files.';

    }

}



/*
==================================================
STREAM THE REPORT TO THE BROWSER
==================================================
*/

$downloadName =
    'PDF_Comparison_Report_' .
    $jobId .
    '.html';


header('Content-Description: File Transfer');
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($reportFile));

header('X-Prqc-Saved: ' . ($prqcSaved ? '1' : '0'));
header('X-Prqc-Path: ' . rawurlencode($prqcPath));
header('X-Prqc-Message: ' . rawurlencode($prqcMessage));
header('Access-Control-Expose-Headers: X-Prqc-Saved, X-Prqc-Path, X-Prqc-Message');

readfile($reportFile);
exit;

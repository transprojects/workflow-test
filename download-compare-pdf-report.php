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

The article lookup (live / archive, status, stage,
folder access) is shared with the page's article
check: includes/compare_pdf_lookup.php.

The outcome of that auto-file attempt is reported
back via response headers (read by the frontend)
since the response body itself is the file.
==================================================
*/

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';      // signed-in users only (401 otherwise)
include_once __DIR__ . '/dbc.php';                       // $db (mysqli)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';

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


$report = [];
$article = null;
$destFile = '';

foreach ($candidateNames as $candidate) {

    $info = cmp_article_lookup($db, $candidate);

    $report[] = $info;

    /* prefer a live article over an archived one */
    if (
        $info['found_in'] === 'Live' &&
        ($article === null || $article['found_in'] !== 'Live')
    ) {
        $article = $info;
    } elseif ($article === null && $info['found_in'] !== '') {
        $article = $info;
    }

}


if (!empty($candidateNames)) {

    if ($article === null) {

        $prqcMessage =
            'No matching article (pub_id) found for the compared files (' .
            implode(', ', $candidateNames) .
            '). Name the PDFs exactly as the article Pub ID.';

        if (!empty($report[0]['suggestions'])) {
            $prqcMessage .= ' Did you mean: ' . implode(', ', $report[0]['suggestions']) . '?';
        }

    } else {

        $prqcPath = $article['prqc_path'];

        $facts =
            'Article ' . $article['pub_id'] .
            ' - ' . $article['state'] .
            ' (found in ' . $article['found_in'] .
            ', stage ' . $article['stage'] .
            ', status ' . $article['status'] . '). ';

        if ($article['found_in'] === 'Archive') {

            $prqcMessage =
                $facts .
                'Archived articles are not filed into a PRQC folder.';

        } elseif ($article['state'] === 'Withdrawn') {

            $prqcMessage =
                $facts .
                'Withdrawn articles are not filed into a PRQC folder.';

        } elseif ($prqcPath === '') {

            $prqcMessage =
                $facts .
                implode(' ', $article['notes']);

        } elseif (
            is_dir($prqcPath) ||
            @mkdir($prqcPath, 0777, true)
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
                    $facts .
                    'Report saved to PRQC folder: ' .
                    $destFile;

            } else {

                $lastErr = error_get_last();

                $prqcMessage =
                    $facts .
                    'Could not write the report to its PRQC folder (check server path/permissions). Server said: ' .
                    ($lastErr ? $lastErr['message'] : 'no details');

            }

        } else {

            $lastErr = error_get_last();

            $prqcMessage =
                $facts .
                'Its PRQC folder could not be created: ' .
                $prqcPath .
                ' Server said: ' .
                ($lastErr ? $lastErr['message'] : 'no details');

        }

    }

}



/*
==================================================
STREAM THE REPORT TO THE BROWSER
==================================================
*/

/*
==================================================
LOG THE FILING ATTEMPT (shown in compare-pdf-log.php)
==================================================
*/

$workLink = $article !== null ? $article['work_link'] : '';
$sourceLink = $article !== null ? $article['source_link'] : '';

cmp_log_filing($db, [
    'user_id'        => $_COOKIE['user_id'] ?? '',
    'job_id'         => $jobId,
    'file1_name'     => $meta['file1_name'] ?? '',
    'file2_name'     => $meta['file2_name'] ?? '',
    'file1_size'     => $meta['file1_size'] ?? 0,
    'file2_size'     => $meta['file2_size'] ?? 0,
    'pub_id'         => $article['pub_id'] ?? '',
    'found_in'       => $article['found_in'] ?? '',
    'article_state'  => $article['state'] ?? 'Not found',
    'stage'          => $article['stage'] ?? '',
    'article_status' => $article['status'] ?? '',
    'prqc_path'      => $prqcPath,
    'saved_file'     => $prqcSaved ? ($destFile ?? '') : '',
    'saved'          => $prqcSaved,
    'message'        => $prqcMessage,
    'work_link'      => $workLink,
    'source_link'    => $sourceLink
]);



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
header('X-Prqc-Link: ' . rawurlencode($workLink));
header('X-Source-Link: ' . rawurlencode($sourceLink));
header('Access-Control-Expose-Headers: X-Prqc-Saved, X-Prqc-Path, X-Prqc-Message, X-Prqc-Link, X-Source-Link');

readfile($reportFile);
exit;

<?php
/* PDF compare tool: look up the selected file names in the workflow (no upload needed).
   POST names[] = the PDF file names; returns, for each, the article found (live / archive), its status, stage and PRQC folder. */

$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';                 // 401 JSON when not signed in
include_once __DIR__ . '/dbc.php';                                  // $db (mysqli)
require_once __DIR__ . '/includes/compare_pdf_lookup.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

$names = isset($_POST['names']) && is_array($_POST['names']) ? array_slice($_POST['names'], 0, 2) : array();
$out = array();

foreach ($names as $n) {
	$n = pathinfo(basename((string)$n), PATHINFO_FILENAME);
	$out[] = cmp_article_lookup($db, $n);
}

echo json_encode(array('ok' => true, 'items' => $out), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

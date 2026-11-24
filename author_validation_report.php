<?php
/* Streams the author-validation HTML report that the 10.10.10.94 validator wrote
 * (e.g. J:/JOURNAL/downloads/.../TSP_SV_TEST0005/FP/TSP_SV_TEST0005-metadata.xml_author_validation.html)
 * so Start Work can show it in a popup. Signed-in users only; only *_author_validation.html files under the
 * journal folders are served; scripts in the report are sandboxed. */
require_once __DIR__ . '/includes/ajax_guard.php';
require_once __DIR__ . '/includes/path_map.php';

$f = isset($_GET['f']) ? str_replace('\\', '/', trim($_GET['f'])) : '';
if ($f === '' || strpos($f, '..') !== false || !preg_match('#^[A-Za-z]:/JOURNAL/[A-Za-z0-9_./ -]+_author_validation\.html$#i', $f)) {
    http_response_code(400); echo 'Invalid report path.'; exit;
}
foreach (sw_path_candidates($f) as $c) {
    if (@is_file($c)) {
        if (isset($_GET['status'])) {
            /* pass/fail check: the report's own "Status: SUCCESS / ERROR" line decides */
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            $txt = strip_tags(html_entity_decode((string) @file_get_contents($c)));
            $pass = (preg_match('/Status:\s*(SUCCESS|ERROR)/i', $txt, $m) && strtoupper($m[1]) === 'SUCCESS');
            echo json_encode(array('ok' => true, 'pass' => $pass));
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Security-Policy: sandbox');
        header('Cache-Control: no-store');
        readfile($c);
        exit;
    }
}
http_response_code(404);
echo 'Report not found on this server yet (' . htmlspecialchars($f) . ').';

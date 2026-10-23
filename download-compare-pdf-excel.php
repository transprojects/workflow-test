<?php
// download-compare-pdf-excel.php

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo "No file specified.";
    exit;
}

$filename = basename($_GET['file']);
$filepath = __DIR__ . '/logs/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

$mimeType = mime_content_type($filepath);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;

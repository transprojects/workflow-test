<?php
$zipFile = 'E:/New_WorkFlow_Files/JOURNAL/downloads/JOURNALS/SIF/EPL/Vol00000/epl25100353/FP/epl25100353-20250729054519.tar.gz'; // Update path

$apiUrl = "https://editlatex-dev.transforma.in:8444/api/v2/internal/create/project";
$postData = [
    'file' => curl_file_create($zipFile, 'application/zip', basename($zipFile)),
    'articleId' => 'epl25100353',
    'client' => 'EPL',
    'token' => '7a8dc3b8-11a9-4219-a6f4-a95239271304'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For self-signed cert
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";
?>
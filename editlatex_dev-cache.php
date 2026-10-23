<?php

// Define the necessary variables
$client = "EPL";
$token = "7a8dc3b8-11a9-4219-a6f4-a95239271304";
$zipFilepath = "D:/Transforma/FTPDOWNLOADER/Files/2024-11-15-epl24100339-corrections.zip";
$articleId = "epl24100339";

try {
    // Validate that the file path is provided and the file exists
    if (empty($zipFilepath)) {
        throw new Exception('File path is missing.');
    }

    if (!file_exists($zipFilepath)) {
        throw new Exception('File not found: ' . $zipFilepath);
    }

    // Prepare file for upload
    $filePath = $zipFilepath;
    $fileName = basename($zipFilepath);

    // Read file content
    $fileBinary = file_get_contents($filePath);
    if ($fileBinary === false) {
        throw new Exception('Unable to read file: ' . $filePath);
    }

    // API Endpoint on the server
    $apiUrl = "https://editlatex-dev.transforma.in:8444/api/v2/internal/create/project";

    // Initialize cURL session
    $ch = curl_init($apiUrl);

    if ($ch === false) {
        throw new Exception('Failed to initialize cURL session.');
    }

    // Prepare data for API
    $postData = [
        'file' => curl_file_create($filePath, 'application/zip', $fileName),
        'articleId' => $articleId,
        'client' => $client,
        'token' => $token
    ];

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Get HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    // Close cURL session
    curl_close($ch);

    // Check if the request was successful
    if ($httpCode === 200) {
        // Decode the response to handle further
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decoding error: ' . json_last_error_msg());
        }

        // Declare new variables and assign values
        $eplToken = $data['token'] ?? null;
        $eplProjectId = $data['project_id'] ?? null;

        // Check if required keys exist in the response
        if (empty($eplToken) || empty($eplProjectId)) {
            throw new Exception('Invalid response from the API. Missing token or project_id.');
        }

        // Successfully got a response from the API, output the result
        echo json_encode([
            'status' => 'success',
            'eplToken' => $eplToken,
            'eplProjectId' => $eplProjectId,
            'response' => $data
        ]);
    } else {
        throw new Exception("API request failed with HTTP code: $httpCode");
    }

} catch (Exception $e) {
    // Catch any exceptions and return a meaningful error message
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>

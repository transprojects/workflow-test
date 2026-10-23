<?php

    $articleId = $fnArticleId;    
    $client = "EPL";
    $token  = "7a8dc3b8-11a9-4219-a6f4-a95239271304";
	
	//$zipFilepath = "D:/Transforma/FTPDOWNLOADER/Files/2024-11-15-epl24100339-corrections.zip";
	//$articleId = "epl24100339";

    
    

    $filePath = $zipFilepath;
    $fileName = basename($zipFilepath);

    // Ensure the file exists (extra precaution)
    if (!file_exists($filePath)) {
        echo json_encode(['error' => 'File not found.']);       
    }

    $fileBinary = file_get_contents($filePath);

    // API Endpoint on the Linux server
    $apiUrl = "https://editlatex-dev.transforma.in:8444/api/v2/internal/create/project";

    $ch = curl_init($apiUrl);

    // Prepare data for API
    $postData = [
        'file' => curl_file_create($filePath, 'application/zip', $fileName),
        'articleId' => $articleId,
		'client' => $client,
        'token' => $token
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $jsonResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode === 200) {
        //echo $response; 
		$api_response_data = json_decode($jsonResponse, true);
		// Declare new variables and assign values
		echo $eplToken = $api_response_data['token'] ?? null;
		echo $eplProjectId = $api_response_data['project_id'] ?? null;
		//print_r($response);		
    } else {
        echo json_encode(['error' => 'Failed to send file to the API.']);
    }

?>
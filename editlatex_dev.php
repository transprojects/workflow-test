<?php
header('Content-Type: application/json');
include_once "includes/conn.php";
require_once __DIR__ . '/includes/path_map.php';   // J:/... in the article record -> E:/New_Workflow_Files/... on this server

// 🔥 LOG FUNCTION
function writeLog($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/editlatex_' . date('Y-m-d') . '.log';
    $time = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$time] $message\n", FILE_APPEND);
}

// 🔹 INPUTS
$pub_id = $_GET['pub_id'] ?? null;
$vf = $_GET['vf'] ?? null;
$stage = $_GET['stage'] ?? null;
$custname = $_GET['custname'] ?? null;

writeLog("START: pub_id=$pub_id | vf=$vf | stage=$stage");

if (!$pub_id || !$vf || !$stage) {
    writeLog("ERROR: Missing parameters");
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// 🔹 FETCH DB
$query = mysqli_query($db, "SELECT pub_id, work_dir, cust_id FROM inw_inward_dtl WHERE pub_id = '$pub_id'");
$app_res = mysqli_fetch_array($query);

if (!$app_res) {
    writeLog("ERROR: Invalid pub_id");
    echo json_encode(['status' => 'error', 'message' => 'Invalid pub_id']);
    exit;
}

$articleId = $app_res['pub_id'];
$client = $custname;
$api_token = "7a8dc3b8-11a9-4219-a6f4-a95239271304";
$cust_id = $app_res['cust_id'];

writeLog("DB FETCH: work_dir=" . $app_res['work_dir'] . " | cust_id=" . $cust_id);

// 🔹 BASE DIR
if ($cust_id != 82) {
    $base_dir = str_replace("production", "downloads", rtrim($app_res['work_dir'], '/'));
	$work_dir = $base_dir . '/' . $vf . '/' . $pub_id . '/' . $stage . '/'; 

} else {
    $base_dir = rtrim($app_res['work_dir'], '/');
	$work_dir = $base_dir . '/' . $vf . '/' . $pub_id . '/' . $stage . '/CE-ELX/';

}

$work_dir = wf_existing_dir($work_dir);   // use the E:/New_Workflow_Files copy when the J:/ path is not visible to the web server
writeLog("WORK DIR: $work_dir");

if (!is_dir($work_dir)) {
    writeLog("ERROR: Directory not found");
    echo json_encode(['status' => 'error', 'message' => "Directory not found: $work_dir"]);
    exit;
}

// 🔹 FILE PATHS
$timestamp = date("YmdHis");
$filename = $articleId . '-' . $timestamp;
$tar_path = $work_dir . $filename . '.tar';
$gz_path = $work_dir . $filename . '.tar.gz';

try {

    // 🔥 CLEAN OLD FILES
    writeLog("Cleaning old archives...");
    foreach (glob($work_dir . $articleId . '-*.{tar}', GLOB_BRACE) as $oldFile) {
        unlink($oldFile);
        writeLog("Deleted old file: $oldFile");
    }

    // 🔥 CREATE TAR
    $phar = new PharData($tar_path);
    $phar->buildFromDirectory($work_dir, '/^(?!.*\.(tar|gz)$).*$/i');

    if (!file_exists($tar_path)) {
        throw new Exception("TAR not created");
    }
    writeLog("TAR CREATED: $tar_path");

    // 🔥 COMPRESS
    $phar->compress(Phar::GZ);

    if (!file_exists($gz_path)) {
        throw new Exception("GZ not created");
    }
    writeLog("GZ CREATED: $gz_path");

    unlink($tar_path);
    writeLog("Deleted TAR");

    // 🔍 CHECK BEFORE CURL
    if (!file_exists($gz_path)) {
        throw new Exception("GZ missing before curl");
    }

    $size = filesize($gz_path);
    writeLog("GZ READY FOR CURL | Size: $size bytes");

    if ($size == 0) {
        throw new Exception("GZ file is empty");
    }

    // 🔹 API CALL
    $apiUrl = "https://editlatex-dev.transforma.in:8444/api/v2/internal/create/project";
    writeLog("Calling API...");

    $postData = [
        'file' => curl_file_create($gz_path, 'application/x-gzip', basename($gz_path)),
        'articleId' => $articleId,
        'client' => $client,
        'token' => $api_token
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // 🔥 CURL VERBOSE LOG
    $verbose = fopen(__DIR__ . '/logs/curl_debug.log', 'a+');
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        writeLog("CURL ERROR: $error");

        echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $error]);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    writeLog("API RESPONSE CODE: $httpCode");
    writeLog("API RESPONSE: $response");

    // 🔹 SUCCESS
    if ($httpCode === 200) {

        $res = json_decode($response, true);
        $editlatex_token = $res['token'] ?? null;
        $editlatex_project_id = $res['project_id'] ?? null;

        if ($editlatex_token && $editlatex_project_id) {

            // 🔥 DELETE ONLY ON SUCCESS
            unlink($gz_path);
            writeLog("Deleted GZ after success");

            $stmt = $db->prepare("UPDATE inw_inward_dtl SET editlatex_token = ?, editlatex_project_id = ? WHERE pub_id = ?");
            $stmt->bind_param("sss", $editlatex_token, $editlatex_project_id, $pub_id);
            $stmt->execute();
            $stmt->close();

            writeLog("SUCCESS: Project Created ID=$editlatex_project_id");

            echo json_encode([
                'status' => 'success',
                'editlatex_token' => $editlatex_token,
                'editlatex_project_id' => $editlatex_project_id
            ]);

        } else {
            writeLog("ERROR: Invalid API response");
            echo json_encode(['status' => 'error', 'message' => 'Invalid response from API']);
        }

    } else {
        writeLog("API FAILED - GZ retained for retry");
        echo json_encode(['status' => 'error', 'message' => 'API call failed', 'response' => $response]);
    }

} catch (Exception $e) {

    writeLog("EXCEPTION: " . $e->getMessage());

    if (file_exists($tar_path)) {
        unlink($tar_path);
        writeLog("Deleted TAR (cleanup)");
    }

    // ❌ KEEP GZ FOR DEBUG
    writeLog("GZ retained due to failure: $gz_path");

    echo json_encode([
        'status' => 'error',
        'message' => 'Archiving failed: ' . $e->getMessage()
    ]);
}
?>
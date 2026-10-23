<?php
header('Content-Type: application/json');
include_once "includes/conn.php";

// 🔥 Performance settings
ini_set('memory_limit', '512M');
set_time_limit(300);

// 🔹 Inputs
$pub_id = $_GET['pub_id'] ?? null;
$vf     = $_GET['vf'] ?? null;
$stage  = $_GET['stage'] ?? null;

if (!$pub_id || !$vf || !$stage) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// 🔹 Fetch article info
$query = mysqli_query($db, "SELECT pub_id, work_dir, cust_id, editlatex_project_id 
                            FROM inw_inward_dtl 
                            WHERE pub_id = '$pub_id'");

$app_res = mysqli_fetch_array($query);

if (!$app_res) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid pub_id']);
    exit;
}

$articleId    = $app_res['pub_id'];
$project_uuid = $app_res['editlatex_project_id'];
$cust_id      = $app_res['cust_id'];

if (!$project_uuid) {
    echo json_encode(['status' => 'error', 'message' => 'Project not created yet']);
    exit;
}

// 🔹 Base directory logic
if ($cust_id != 82) {
    $base_dir = str_replace("production", "downloads", rtrim($app_res['work_dir'], '/'));
} else {
    $base_dir = rtrim($app_res['work_dir'], '/');
}

// 🔹 GRAPHICS PATH
$graphics_dir = $base_dir . '/' . $vf . '/' . $pub_id . '/' . $stage . '/Graphics/';

if (!is_dir($graphics_dir)) {
    echo json_encode([
        'status' => 'error',
        'message' => "Graphics folder not found: $graphics_dir"
    ]);
    exit;
}

// 🔥 Check empty folder
$files = scandir($graphics_dir);
if (count($files) <= 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Graphics folder is empty'
    ]);
    exit;
}

// 🔹 File names
$timestamp = date("YmdHis");
$filename  = $articleId . '-graphics-' . $timestamp;

$tar_path = $graphics_dir . $filename . '.tar';
$gz_path  = $graphics_dir . $filename . '.tar.gz';

$api_token = "7a8dc3b8-11a9-4219-a6f4-a95239271304";

try {

    // 🔥 Create TAR (exclude tar/gz itself)
    $phar = new PharData($tar_path);

    $phar->buildFromDirectory($graphics_dir, '/^(?!.*\.(tar|gz)$).*$/i');

    // 🔥 Compress
    $phar->compress(Phar::GZ);

    // Remove .tar
    if (file_exists($tar_path)) {
        unlink($tar_path);
    }

    // 🔹 API URL
    $apiUrl = "https://editlatex-dev.transforma.in:8444/api/v2/internal/sync/project";

    // 🔹 POST DATA
    $postData = [
        'file' => curl_file_create($gz_path, 'application/x-gzip', basename($gz_path)),
        'token' => $api_token,
        'project_uuid' => $project_uuid
    ];

    // 🔹 CURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    // 🔥 CURL ERROR HANDLING
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);

        echo json_encode([
            'status' => 'error',
            'message' => 'cURL Error: ' . $error
        ]);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 🔥 DELETE FILE AFTER UPLOAD
    if (file_exists($gz_path)) {
        unlink($gz_path);
    }

    // 🔹 RESPONSE
    if ($httpCode === 200) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Graphics synced successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sync API failed',
            'response' => $response
        ]);
    }

} catch (Exception $e) {

    // 🔥 CLEANUP (if error happens)
    if (file_exists($tar_path)) unlink($tar_path);
    if (file_exists($gz_path)) unlink($gz_path);

    echo json_encode([
        'status' => 'error',
        'message' => 'Archiving failed: ' . $e->getMessage()
    ]);
}
?>
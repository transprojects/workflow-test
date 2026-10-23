<?php
include "includes/conn.php";

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(["status" => "error", "msg" => "Invalid ID"]);
    exit;
}

$id = intval($_POST['id']); // ✅ prevent SQL injection

// 🔍 Check record exists
$check = mysqli_query($db, "
    SELECT id, status, mail_send, retry_count 
    FROM xperttrack_ftp_upload_history 
    WHERE id = '$id'
");

if (mysqli_num_rows($check) == 0) {
    echo json_encode(["status" => "error", "msg" => "Record not found"]);
    exit;
}

$row = mysqli_fetch_assoc($check);

// 🚫 Prevent unnecessary retry
if ($row['status'] == 'YET_TO_UPLOAD') {
    echo json_encode(["status" => "warning", "msg" => "Already in retry queue"]);
    exit;
}

// 🔁 Update with retry tracking
mysqli_query($db, "
    UPDATE xperttrack_ftp_upload_history 
    SET 
        status = 'YET_TO_UPLOAD',
        mail_send = 'PENDING',
        retry_count = IFNULL(retry_count,0) + 1,
        last_retry_at = NOW()
    WHERE id = '$id'
");

// 📝 Optional: Log action
mysqli_query($db, "
    INSERT INTO upload_retry_logs (upload_id, retry_time)
    VALUES ('$id', NOW())
");

echo json_encode([
    "status" => "success",
    "msg" => "Moved to retry queue"
]);
?>
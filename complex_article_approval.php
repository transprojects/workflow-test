<?php
include "includes/Auth.php";
include "includes/function.php";
$db_handle = new DBController();

$data = json_decode(file_get_contents('php://input'), true);
$complex_ids = $data['complex_ids'] ?? [];

$response = ['success' => false];

if (!empty($complex_ids)) {
    $ids = implode(',', array_map('intval', $complex_ids)); // sanitize input
    $query = "UPDATE wf_file_complexity_users SET approval_status = 'Approved', approved_on = NOW(), approved_by = {$_COOKIE['user_id']} WHERE fcu_id IN ($ids)";
    
    if (mysqli_query($db_handle->connectDB(), $query)) {
        $response['success'] = true;
    }
}

echo json_encode($response);
?>

<?php
include "includes/Auth.php";
include "includes/function.php";
$db_handle = new DBController();

$data = json_decode(file_get_contents('php://input'), true);
$leave_ids = $data['leave_ids'] ?? [];

$response = ['success' => false];

if (!empty($leave_ids)) {
    $ids = implode(',', array_map('intval', $leave_ids)); // sanitize input
    $query = "UPDATE wf_user_leave SET approval_status = 'Approved', approved_on = NOW(), approved_by = {$_COOKIE['user_id']} WHERE ul_id IN ($ids)";
    
    if (mysqli_query($db_handle->connectDB(), $query)) {
        $response['success'] = true;
    }
}

echo json_encode($response);
?>

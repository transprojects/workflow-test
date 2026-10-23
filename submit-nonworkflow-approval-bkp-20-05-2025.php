<?php
include "includes/includes.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $user_name = $_POST['user_name'];
    $total_page_count = intval($_POST['total_page_count']);
    $total_percentage = intval($_POST['total_percentage']);
    $total_pt = $_POST['total_hrs'];
    $records = $_POST['records'];
    $nw_date = $_POST['nw_date'];

    // Check for existing entry for this user and date
    $check_query = "SELECT nw_id FROM `non_workflow_user_percentage` WHERE `user_id` = '$user_id' AND `nw_date` = '".$nw_date."' LIMIT 1";
    $result = mysqli_query($db, $check_query);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Approval already submitted for this user and date.'
        ]);
        exit;
    }

    // Insert summary row
    $insert_query = "INSERT INTO `non_workflow_user_percentage` 
        (`user_id`, `user_name`, `nw_date`, `nw_page_count`, `nw_percentage`, `nw_pt`, `nw_cdatetime`) 
        VALUES 
        ('$user_id', '$user_name', '$nw_date', '$total_page_count', '$total_percentage', '$total_pt', NOW())";

    if (!mysqli_query($db, $insert_query)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Insert failed: ' . mysqli_error($db)
        ]);
        exit;
    }
	
	// Get the last inserted ID
$last_inserted_id = mysqli_insert_id($db);

    // Loop through each record and update non_workflow_user_percentage_article
    foreach ($records as $record) {
        $nwa_id = intval($record['id']);  // Assuming this is the nwa_id
        $percentage = floatval($record['percentage']);

        $update_query = "UPDATE `non_workflow_user_percentage_article` 
                         SET `nwa_percentage` = '$percentage',
						 `nw_id`='$last_inserted_id'
                         WHERE `nwa_id` = '$nwa_id'";

        mysqli_query($db, $update_query);  // Optional: you can check for errors per query
    }

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}

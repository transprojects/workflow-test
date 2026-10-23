<?php
include "includes/includes.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON if data is sent as JSON
    $usersData = $_POST['users'] ?? json_decode(file_get_contents('php://input'), true)['users'];

    if (!is_array($usersData) || empty($usersData)) {
        echo json_encode(['status' => 'error', 'message' => 'No user data received.']);
        exit;
    }

    $errors = [];
    foreach ($usersData as $user) {
        $user_id = mysqli_real_escape_string($db, $user['user_id']);
        $user_name = mysqli_real_escape_string($db, $user['user_name']);
        $total_page_count = intval($user['total_page_count']);
        $total_percentage = intval($user['total_percentage']);
        $approval_comments = mysqli_real_escape_string($db, $user['approval_comments']);
        $total_pt = mysqli_real_escape_string($db, $user['total_hrs']);
        $nw_date = mysqli_real_escape_string($db, $user['nw_date']);
        $records = $user['records'];

        // Check if already approved for this user and date
        $check_query = "SELECT nw_id FROM non_workflow_user_percentage WHERE user_id = '$user_id' AND nw_date = '$nw_date' LIMIT 1";
        $result = mysqli_query($db, $check_query);

        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Approval already submitted for user $user_name ($user_id) on $nw_date.";
            continue;
        }

        // Insert summary row
        $insert_query = "INSERT INTO non_workflow_user_percentage 
            (user_id, user_name, nw_date, nw_page_count, nw_percentage, nw_pt, nw_cdatetime, nw_comments) 
            VALUES 
            ('$user_id', '$user_name', '$nw_date', '$total_page_count', '$total_percentage', '$total_pt', NOW(), '$approval_comments')";

        if (!mysqli_query($db, $insert_query)) {
            $errors[] = "Insert failed for $user_name: " . mysqli_error($db);
            continue;
        }

        $nw_id = mysqli_insert_id($db);

        // Update each approved article
        foreach ($records as $record) {
            $nwa_id = intval($record['id']);
            $percentage = floatval($record['percentage']);

            $update_query = "UPDATE non_workflow_user_percentage_article 
                             SET nwa_percentage = '$percentage',
                                 nwa_status = 'Approved',
                                 nw_id = '$nw_id'
                             WHERE nwa_id = '$nwa_id'";
            mysqli_query($db, $update_query); // Optionally check for success
        }
    }

    if (count($errors) > 0) {
        echo json_encode(['status' => 'partial', 'message' => $errors]);
    } else {
        echo json_encode(['status' => 'success']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

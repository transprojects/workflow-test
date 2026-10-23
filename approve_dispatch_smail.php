<?php
include('includes/conn.php');
date_default_timezone_set('Asia/Kolkata');

$today = $_POST['today'] ?? date('Y-m-d');

// Update 1: daily table
$update_sql1 = "UPDATE plan_article_to_dispatch_mail_daily 
                SET status = 'Approved' 
                WHERE schedule_date = ?";

if (!$stmt1 = $db->prepare($update_sql1)) {
    die("Prepare failed (daily): " . $db->error);
}

if (!$stmt1->bind_param('s', $today)) {
    die("Bind failed (daily): " . $stmt1->error);
}

if (!$stmt1->execute()) {
    die("Execute failed (daily): " . $stmt1->error);
}

// Update 2: history table
$update_sql2 = "UPDATE plan_article_to_dispatch_mail_history 
                SET approve_status = 'Approved' 
                WHERE DATE(created_dt) = ?";

if (!$stmt2 = $db->prepare($update_sql2)) {
    die("Prepare failed (history): " . $db->error);
}

if (!$stmt2->bind_param('s', $today)) {
    die("Bind failed (history): " . $stmt2->error);
}

if (!$stmt2->execute()) {
    die("Execute failed (history): " . $stmt2->error);
}

echo "success";
?>

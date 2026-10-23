<?php 
include('dbc.php');
date_default_timezone_set('Asia/Kolkata');

session_start();
$user_id = $_COOKIE['user_id'] ?? $_SESSION['user_id'] ?? 54;

// 🔹 Inputs
$cust_name     = $_POST['cust_name'] ?? '';
$j_code        = $_POST['j_code'] ?? '';
$cust_id        = $_POST['cust_id'] ?? '';
$j_id        = $_POST['j_id'] ?? '';
$inw_id        = $_POST['article_id'] ?? '';
$pub_id        = $_POST['pub_id'] ?? '';
$article_id    = $_POST['article_id'] ?? $inw_id;
$stage         = $_POST['stage'] ?? '';
$status        = $_POST['status'] ?? '';
$received_date = !empty($_POST['recv_dt']) ? date('Y-m-d', strtotime($_POST['recv_dt'])) : null;
$due_date      = !empty($_POST['due_dt']) ? date('Y-m-d', strtotime($_POST['due_dt'])) : null;
$page_count    = $_POST['page_count'] ?? 0;
$action        = $_POST['action'] ?? 'schedule'; // 'schedule' or 'unschedule'

if (!$article_id || !$pub_id || !$inw_id) {
    echo "Missing required data.";
    exit;
}

mysqli_begin_transaction($db);

try {
    $today = date('Y-m-d');
    $schedule_id = null;

    // 🔹 Step 1: Check if today's schedule exists for this user
    $stmt = $db->prepare("SELECT id FROM plan_article_to_dispatch_mail_daily WHERE  schedule_date = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $stmt->bind_result($schedule_id);
    $stmt->fetch();
    $stmt->close();

    // 🔹 Step 2: Create schedule if missing
    if (!$schedule_id) {
        $insert = $db->prepare("INSERT INTO plan_article_to_dispatch_mail_daily (user_id, schedule_date, status, created_dt)
                                VALUES (?, ?, 'Pending', NOW())");
        $insert->bind_param("is", $user_id, $today);
        if (!$insert->execute()) {
            throw new Exception("Daily schedule insert failed: " . $insert->error);
        }
        $schedule_id = $insert->insert_id;
        $insert->close();
    }

    // 🔹 Step 3: Schedule / Unschedule Logic
    if ($action === 'schedule') {

        // ✅ Check if already scheduled today to avoid duplicates
        $check = $db->prepare("SELECT COUNT(*) FROM plan_article_to_dispatch_mail_history h
                               JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
                               WHERE h.article_id = ? AND d.schedule_date = ?");
        $check->bind_param("is", $article_id, $today);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();

        if ($exists > 0) {
            // Already scheduled, skip insertion
            mysqli_commit($db);
            echo "already_scheduled";
            exit;
        }

        // ✅ Insert new record
        $insertHistory = $db->prepare("INSERT INTO plan_article_to_dispatch_mail_history 
            (schedule_id, cust_id, j_id, cust_name, j_code, article_id, pub_id, inw_id, stage, status, received_date, due_date, page_count, created_by)
            VALUES (?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertHistory->bind_param(
            "iiissisissssii",
            $schedule_id,
            $cust_id,
            $j_id,
            $cust_name,
            $j_code,
            $article_id,
            $pub_id,
            $inw_id,
            $stage,
            $status,
            $received_date,
            $due_date,
            $page_count,
            $user_id
        );
        if (!$insertHistory->execute()) {
            throw new Exception("Insert history failed: " . $insertHistory->error);
        }
        $insertHistory->close();

        mysqli_commit($db);
        echo "scheduled";

    } elseif ($action === 'unschedule') {

        $delete = $db->prepare("DELETE h FROM plan_article_to_dispatch_mail_history h
                                JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
                                WHERE h.article_id = ? AND d.schedule_date = ? AND d.user_id = ?");
        $delete->bind_param("isi", $article_id, $today, $user_id);
        if (!$delete->execute()) {
            throw new Exception("Unschedule delete failed: " . $delete->error);
        }
        $delete->close();

        mysqli_commit($db);
        echo "unscheduled";
    }

} catch (Exception $e) {
    mysqli_rollback($db);
    echo "Error: " . $e->getMessage();
}
?>

<?php
require_once("includes/conn.php");

$user_id = $_COOKIE['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

$today = date('Y-m-d');

// ✅ Only return today's incomplete tasks
$query = "SELECT id, task_description FROM user_todo_tasks 
          WHERE user_id = ? AND task_date = ? AND is_completed = 0";

$stmt = $db->prepare($query);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);

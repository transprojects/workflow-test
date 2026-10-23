<?php
require_once("includes/conn.php");

header('Content-Type: text/plain');

$user_id = $_COOKIE['user_id'] ?? null;

if (!$user_id || empty($_POST['completed_tasks']) || !is_array($_POST['completed_tasks'])) {
    echo "No tasks submitted";
    exit;
}

$task_ids = $_POST['completed_tasks'];
$placeholders = implode(',', array_fill(0, count($task_ids), '?'));

// Build dynamic types for bind_param (i.e., 'iii...i')
$types = str_repeat('i', count($task_ids)) . 'i';

// Merge all task IDs with user ID
$params = array_merge($task_ids, [$user_id]);

// Build query
$query = "UPDATE user_todo_tasks SET is_completed = 1 
          WHERE id IN ($placeholders) AND user_id = ?";

// Prepare statement
$stmt = $db->prepare($query);

// Create dynamic reference array for bind_param
$bind_names[] = $types;
foreach ($params as $key => $value) {
    $bind_names[] = &$params[$key];
}

// Bind and execute
call_user_func_array([$stmt, 'bind_param'], $bind_names);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Tasks updated";
} else {
    echo "No tasks updated";
}

$stmt->close();
?>

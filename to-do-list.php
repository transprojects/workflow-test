<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['completed_ids'])) {
    $ids = $_POST['completed_ids'];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids);

    $sql = "UPDATE user_todo_tasks SET is_completed = 1, completed_time = NOW() WHERE id IN ($in)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
header("Location: todolist.php");
exit;

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'today';

switch ($filter) {
  case 'overdue':
    $condition = "task_date < CURDATE()";
    break;
  case 'upcoming':
    $condition = "task_date > CURDATE()";
    break;
  default:
    $condition = "task_date = CURDATE()";
}

$stmt = $pdo->prepare("SELECT * FROM user_todo_tasks WHERE user_id = ? AND $condition ORDER BY task_date ASC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();
?>

<div>
  <a href="?filter=today">Today</a> |
  <a href="?filter=overdue">Overdue</a> |
  <a href="?filter=upcoming">Upcoming</a>
</div>

<form method="post" action="mark_complete.php">
  <?php foreach ($tasks as $task): ?>
    <div style="margin-bottom:10px;">
      <input type="checkbox" name="completed_ids[]" value="<?= $task['id'] ?>" <?= $task['is_completed'] ? 'checked disabled' : '' ?>>
      <span style="<?= $task['is_completed'] ? 'text-decoration: line-through; color: gray;' : '' ?>">
        <?= htmlspecialchars($task['task_description']) ?> (<?= $task['task_date'] ?>)
      </span>
    </div>
  <?php endforeach; ?>
  <button type="submit">Mark Selected as Completed</button>
</form>

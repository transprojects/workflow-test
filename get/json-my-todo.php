<?php
include "../includes/Auth.php";
include "../includes/function.php";

$auth = new Auth();
$db_handle = new DBController();

$start = $_REQUEST['iDisplayStart'] ?? 0;
$length = $_REQUEST['iDisplayLength'] ?? 10;
$sSearch = $_REQUEST['sSearch'] ?? '';
$col = $_REQUEST['iSortCol_0'] ?? 0;
$sort_type = $_REQUEST['sSortDir_0'] ?? 'desc';

$sort_columns = [
  0 => 'id',
  1 => 'task_description',
  2 => 'task_date',
  3 => 'created_at'
];
$sort_by = $sort_columns[$col] ?? 'id';

$user_id = $_COOKIE['user_id'] ?? 0;

$query = "SELECT * FROM user_daily_tasks WHERE user_id = $user_id";
if (!empty($sSearch)) {
  $sSearch = mysqli_real_escape_string($db, $sSearch);
  $query .= " AND (task_description LIKE '%$sSearch%')";
}
$count_query = "SELECT COUNT(*) AS cnt FROM ($query) as total";
$query .= " ORDER BY $sort_by $sort_type LIMIT $start, $length";

$result = mysqli_query($db, $query);
$count_result = mysqli_query($db, $count_query);
$total_count = ($row = mysqli_fetch_assoc($count_result)) ? $row['cnt'] : 0;

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = [
    $row['id'],
    nl2br(htmlspecialchars($row['task_description'])),
    date('d-m-Y', strtotime($row['task_date'])),
    date('d-m-Y H:i', strtotime($row['created_at'])),
    '<button data-delete="' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>'
  ];
}

echo json_encode([
  "iTotalRecords" => $total_count,
  "iTotalDisplayRecords" => $total_count,
  "aaData" => $data
]);

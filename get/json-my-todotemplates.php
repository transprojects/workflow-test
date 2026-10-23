<?php
include "../includes/Auth.php";
include "../includes/function.php";

$auth = new Auth();
$db_handle = new DBController();

// DataTable parameters
$start      = $_REQUEST['iDisplayStart'] ?? 0;
$length     = $_REQUEST['iDisplayLength'] ?? 10;
$sSearch    = $_REQUEST['sSearch'] ?? '';
$col        = $_REQUEST['iSortCol_0'] ?? 0;
$sort_type  = $_REQUEST['sSortDir_0'] ?? 'desc';

// Sortable columns
$sort_columns = array(
    0 => 'tt.id',
    1 => 'ur.user_role_name',
    2 => 'tt.task_description'
);
$sort_by = $sort_columns[$col] ?? 'tt.id';

// User info from cookies
$user_level = intval($_COOKIE['user_level'] ?? 0);
$user_id    = intval($_COOKIE['user_id'] ?? 0);

// Base query
$query = "
    SELECT 
        tt.id,
        tt.role,
        tt.task_description,
        ur.user_role_name
    FROM todo_templates tt
    LEFT JOIN user_role_master ur ON ur.user_role_id = tt.role
    WHERE 1 = 1
";

// Apply search filter
if (!empty($sSearch)) {
    $search = mysqli_real_escape_string($db_handle->conn, $sSearch);
    $query .= " AND (
        tt.task_description LIKE '%$search%' OR
        ur.user_role_name LIKE '%$search%'
    )";
}

// Count query for filtered records
$count_query = "SELECT COUNT(*) as cnt FROM ($query) AS sub";

// Apply sorting and limit
$query .= " ORDER BY $sort_by $sort_type LIMIT $start, $length";

// Execute data query
$result = mysqli_query($db, $query);
if (!$result) {
    die(json_encode([
        'error' => 'Query Failed: ' . mysqli_error($db),
        'query' => $query
    ]));
}

// Execute count query
$count_result = mysqli_query($db, $count_query);
$total_count = 0;
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_count = $row['cnt'];
}

// Build response for DataTables
$response = [
    'iTotalRecords' => $total_count,
    'iTotalDisplayRecords' => $total_count,
    'aaData' => []
];
$j=1;
while ($row = mysqli_fetch_assoc($result)) {
    $data = [];

    $data[] = $j;
    $data[] = htmlspecialchars($row['user_role_name'] ?? 'Unknown');
    $data[] = nl2br(htmlspecialchars($row['task_description']));
    // Action buttons
    $data[] = '
        <button data-delete="' . $row['id'] . '" class="btn btn-danger delete-template" type="button">
            <i class="ace-icon fa fa-trash-o bigger-120"></i>
        </button>
    ';

    $response['aaData'][] = $data;
	$j++;
}

echo json_encode($response);

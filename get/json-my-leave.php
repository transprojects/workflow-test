<?php
include "../includes/Auth.php";
include "../includes/function.php";

$auth = new Auth();
$db_handle = new DBController();

// DataTable params
$start = $_REQUEST['iDisplayStart'] ?? 0;
$length = $_REQUEST['iDisplayLength'] ?? 10;
$sSearch = $_REQUEST['sSearch'] ?? '';
$col = $_REQUEST['iSortCol_0'] ?? 0;
$sort_type = $_REQUEST['sSortDir_0'] ?? 'desc';

// Sortable columns
$sort_columns = array(
    0 => 'wf_leave_type.lt_title',
    1 => 'u1.full_name',
    2 => 'wf_user_leave.created_on',
    3 => 'wf_user_leave.leave_session', // not alias!
    4 => 'wf_user_leave.reasons',
    5 => 'u2.full_name',
    6 => 'u3.full_name',
    7 => 'wf_user_leave.approval_status'
);
$sort_by = $sort_columns[$col] ?? 'wf_user_leave.created_on';

// User info from cookies
$user_level = isset($_COOKIE['user_level']) ? intval($_COOKIE['user_level']) : 0;
$user_id    = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : 0;

// Main query
$query = "SELECT 
    wf_user_leave.ul_id,
    wf_user_leave.reasons,
    wf_user_leave.created_on,
    wf_user_leave.approved_on,
    wf_user_leave.leave_session,
    CASE 
        WHEN wf_user_leave.leave_session = 1 THEN 'Full Day'
        WHEN wf_user_leave.leave_session = 2 THEN 'First Half'
        WHEN wf_user_leave.leave_session = 3 THEN 'Second Half'
        ELSE 'Unknown'
    END AS leave_session_label,
    wf_user_leave.approval_status,
    wf_leave_type.lt_title,
    u1.full_name AS employee_name,
    u2.full_name AS created_by_name,
    u3.full_name AS approved_by_name 
FROM wf_user_leave
LEFT JOIN wf_leave_type ON wf_leave_type.lt_id = wf_user_leave.leave_type
LEFT JOIN users u1 ON u1.id = wf_user_leave.user_id
LEFT JOIN users u2 ON u2.id = wf_user_leave.created_by
LEFT JOIN users u3 ON u3.id = wf_user_leave.approved_by
WHERE 1=1";

// Apply user restriction
if ($user_level === 3) {
    $query .= " AND wf_user_leave.user_id = $user_id";
}

// Apply search filter
if (!empty($sSearch)) {
    $search = mysqli_real_escape_string($db_handle->conn, $sSearch);
    $query .= " AND (
        wf_leave_type.lt_title LIKE '%$search%' OR
        wf_user_leave.reasons LIKE '%$search%' OR
        u1.full_name LIKE '%$search%' OR
        u2.full_name LIKE '%$search%' OR
        u3.full_name LIKE '%$search%'
    )";
}

// Save count query for filtered records
$count_query = "SELECT COUNT(*) as cnt FROM ($query) AS sub";

// Apply sorting and limit
$query .= " ORDER BY $sort_by $sort_type LIMIT $start, $length";

// Execute final data query
$result = mysqli_query($db,$query);
if (!$result) {
    die(json_encode([
        'error' => 'Query Failed: ' . mysqli_error($db_handle->conn),
        'query' => $query
    ]));
}

// Execute count query
$count_result = mysqli_query($db, $count_query);
$total_count = 0;
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_count = $row['cnt'];
}

// Build response
$rec = array(
    'iTotalRecords' => $total_count,
    'iTotalDisplayRecords' => $total_count,
    'aaData' => array()
);

while ($row = mysqli_fetch_assoc($result)) {
    $isApproved = strtolower($row['approval_status']) === 'approved';

    $data = array(
        $row['lt_title'] ?? 'Unknown',
        $row['employee_name'],
        date('d-m-Y', strtotime($row['created_on'])),
        $row['leave_session_label'],
        nl2br(htmlspecialchars($row['reasons'])),
        $row['created_by_name'],
        $row['approved_by_name'] ?: 'Pending',
        $row['approval_status'] ?: 'Pending'
    );

    // Add checkbox (not for user_level 3)
    if ($user_level != 3) {
        $disabled = $isApproved ? 'disabled' : '';
        $data[] = '<input type="checkbox" class="leave_checkbox" value="' . $row['ul_id'] . '" ' . $disabled . '>';
    } else {
        $data[] = '';
    }

    // Add delete button
    $data[] = '<button data-delete="' . $row['ul_id'] . '" class="btn btn-danger demo4 delete-leave" type="button">
        <i class="ace-icon fa fa-trash-o bigger-120"></i>
    </button>';

    $rec['aaData'][] = $data;
}

echo json_encode($rec);

<?php
include "../includes/Auth.php";
include "../includes/function.php";
$auth = new Auth();
$db_handle = new DBController();

$start = $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];

$sort_columns = array(
    0 => 'u1.full_name',              // user
    1 => 'c.cust_name',               // customer
    2 => 'wd.pub_id',                 // article id
    3 => 'wf_file_complexity_users.file_status',
    4 => 'wf_file_complexity_users.fcu_date',
    5 => 'u2.full_name',             // approved by
    6 => 'wf_file_complexity_users.approval_status'
);

$sort_by = $sort_columns[$col] ?? 'wf_file_complexity_users.fcu_cdatetime';
$sort_type = $_REQUEST['sSortDir_0'] ?? 'desc';

// Get user info from cookies
$user_level = isset($_COOKIE['user_level']) ? intval($_COOKIE['user_level']) : 0;
$user_id    = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : 0;

// Base query
$query = "SELECT 
    wf_file_complexity_users.fcu_id,
    wf_file_complexity_users.file_status,
    wf_file_complexity_users.fcu_date,
    wf_file_complexity_users.fcu_cdatetime,
    wf_file_complexity_users.approval_status,
    wf_file_complexity_users.approved_on,
    u1.full_name AS user_name,
    u2.full_name AS approved_by_name,
    wd.pub_id AS articleid,
    c.cust_name AS customername
FROM wf_file_complexity_users
LEFT JOIN inw_inward_dtl wd ON wd.id = wf_file_complexity_users.inw_id
LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
LEFT JOIN users u1 ON u1.id = wf_file_complexity_users.user_id
LEFT JOIN users u2 ON u2.id = wf_file_complexity_users.approved_by
WHERE 1=1";

// Filter for normal users
if ($user_level === 3) {
    $query .= " AND wf_file_complexity_users.user_id = $user_id";
}

// Search filter
if (!empty($sSearch)) {
    $search = mysqli_real_escape_string($db_handle->conn, $sSearch);
    $query .= " AND (
        wf_file_complexity_users.stage LIKE '%$search%' OR
        wf_file_complexity_users.file_status LIKE '%$search%' OR
        u1.full_name LIKE '%$search%' OR
        u2.full_name LIKE '%$search%' OR
        wd.pub_id LIKE '%$search%' OR
        c.cust_name LIKE '%$search%'
    )";
}

// Sorting and pagination
$query .= " ORDER BY $sort_by $sort_type LIMIT $start, $length";

$result = mysqli_query($db, $query);
$total_count = mysqli_num_rows($result);


// Prepare response
$rec = array(
    'iTotalRecords' => $total_count,
    'iTotalDisplayRecords' => $total_count,
    'aaData' => array()
);

while ($row = mysqli_fetch_assoc($result)) {
    $isApproved = strtolower($row['approval_status']) === 'approved';

    // Safe fallback for date values
    $createdDate = $row['fcu_cdatetime'] ?: $row['fcu_cdatetime'];
    $approvedOn = $row['approved_on'] ? date('d-m-Y H:i:s', strtotime($row['approved_on'])) : 'Pending';

    $data = array(
        htmlspecialchars($row['user_name'] ?? 'Unknown'),
        htmlspecialchars($row['customername'] ?? 'Unknown'),
        htmlspecialchars($row['articleid'] ?? 'Unknown'),
        htmlspecialchars($row['file_status'] ?? 'Unknown'),
        date('d-m-Y H:i:s', strtotime($createdDate)),
        htmlspecialchars($row['approved_by_name'] ?? 'Pending'),
        htmlspecialchars($row['approval_status'] ?? 'Pending'),
        $approvedOn
    );

    // Checkbox only for user_level != 3
    if ($user_level != 3) {
        $disabled = $isApproved ? 'disabled' : '';
        $data[] = '<input type="checkbox" class="complex_checkbox" value="' . $row['fcu_id'] . '" ' . $disabled . '>';
    } else {
        $data[] = ''; // Maintain column structure
    }

    $rec['aaData'][] = $data;
}

echo json_encode($rec);
?>

<?php
ini_set("display_errors", "0");
include("../includes/conn.php");

$requestData = $_REQUEST;

// Define columns (for DataTables ordering)
$columns = array(
    0 => 'c.cust_name',
    1 => 's.cs_title',
    2 => 'j.category',
    3 => 'j.element_name',
    4 => 'j.js_description',
    5 => 'j.status',
    6 => 'j.created_at'
);

// Base query with joins
$sql = "SELECT 
            j.js_id,
            c.cust_name,
            s.cs_title,
            j.category,
            j.element_name,
            j.js_description,
            j.status,
            j.created_at
        FROM journal_spec_master j
        LEFT JOIN adm_customer_master c ON j.cust_id = c.id
        LEFT JOIN inw_customer_styles s ON j.cs_id = s.cs_id";

// Get total count
$query = mysqli_query($db, $sql);
$totalData = mysqli_num_rows($query);
$totalFiltered = $totalData;

// Search filter
if (!empty($requestData['sSearch'])) {
    $search = mysqli_real_escape_string($db, $requestData['sSearch']);
    $sql .= " WHERE (c.cust_name LIKE '%$search%' 
              OR s.cs_title LIKE '%$search%'
              OR j.category LIKE '%$search%' 
              OR j.element_name LIKE '%$search%' 
              OR j.js_description LIKE '%$search%')";
}

// Filtered count
$query = mysqli_query($db, $sql);
$totalFiltered = mysqli_num_rows($query);

// Ordering + Pagination
$orderColumnIndex = $requestData['iSortCol_0'] ?? 0;
$orderColumn = $columns[$orderColumnIndex];
$orderDir = $requestData['sSortDir_0'] ?? 'asc';
$start = intval($requestData['iDisplayStart']);
$length = intval($requestData['iDisplayLength']);

$sql .= " ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$query = mysqli_query($db, $sql);

// Data formatting
$data = array();
while ($row = mysqli_fetch_assoc($query)) {
    $nestedData = array();

    $nestedData["0"] = $row["cust_name"] ?: '-';
    $nestedData["1"] = $row["cs_title"] ?: '-';
    $nestedData["2"] = $row["category"];
    $nestedData["3"] = $row["element_name"];
    $nestedData["4"] = $row["js_description"];
    $nestedData["5"] = $row["status"];
    $nestedData["6"] = $row["created_at"] ? date("d-M-Y", strtotime($row["created_at"])) : '-';
    $nestedData["7"] = "
        <button class='btn btn-sm btn-primary' 
            onclick=\"window.location='add-journal-specification?edit={$row['js_id']}'\" title='Edit'><i class='fa fa-pencil'></i></button>
        <button class='btn btn-sm btn-danger delete-spec' 
            data-delete='{$row['js_id']}' title='Delete'><i class='fa fa-trash-o'></i></button>
    ";

    $data[] = $nestedData;
}

// JSON response
$json_data = array(
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

echo json_encode($json_data);
?>

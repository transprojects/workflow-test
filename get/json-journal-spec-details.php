<?php
ini_set("display_errors", "0");
include("../includes/conn.php");

$requestData = $_REQUEST;

// Columns mapping for ordering
$columns = array(
    0 => 'cust.cust_name',       // Customer
    1 => 'sty.cs_title',       // Style
    2 => 'jsm.category',         // Category
    3 => 'jsm.element_name',     // Element
    4 => 'jsd.description',      // Description
    5 => 'jsd.version',
    6 => 'jsd.updated_at'
);

// ✅ Updated base query using customer + style (not journal)
$sql = "
    SELECT 
        jsd.id,
        cust.cust_name AS customer_name,
        sty.cs_title AS cs_title,
        jsm.category,
        jsm.element_name,
        jsd.description,
        jsd.version,
        jsd.updated_at
    FROM journal_spec_details jsd
    LEFT JOIN adm_customer_master cust ON jsd.customer_id = cust.id
    LEFT JOIN inw_customer_styles sty ON jsd.cs_id = sty.cs_id
    LEFT JOIN journal_spec_master jsm ON jsd.element_id = jsm.js_id
    WHERE 1=1
";

// Filtering (Search)
if (!empty($requestData['sSearch'])) {
    $search = mysqli_real_escape_string($db, $requestData['sSearch']);
    $sql .= " AND (
                cust.cust_name LIKE '%$search%' 
                OR sty.cs_title LIKE '%$search%'
                OR jsm.category LIKE '%$search%'
                OR jsm.element_name LIKE '%$search%'
                OR jsd.description LIKE '%$search%'
                OR jsd.version LIKE '%$search%'
              )";
}

// Get total records after filtering
$query = mysqli_query($db, $sql);
$totalFiltered = mysqli_num_rows($query);

// Ordering
$orderColumn = isset($requestData['iSortCol_0']) ? intval($requestData['iSortCol_0']) : 0;
$orderDir    = isset($requestData['sSortDir_0']) && in_array($requestData['sSortDir_0'], ['asc','desc']) 
               ? $requestData['sSortDir_0'] : 'asc';
$sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;

// Pagination
$start  = isset($requestData['iDisplayStart']) ? intval($requestData['iDisplayStart']) : 0;
$length = isset($requestData['iDisplayLength']) ? intval($requestData['iDisplayLength']) : 10;
$sql .= " LIMIT $start, $length";

$query = mysqli_query($db, $sql);

// Prepare data
$data = array();
while ($row = mysqli_fetch_assoc($query)) {
    $messageContent = '<div class="message-content">' . nl2br(htmlspecialchars((string)$row['description'])) . '</div>';

    $editUrl = "addjournalspecdescriptions?id=" . $row['id'];
    
    $actionBtns = "
        <a href='$editUrl' class='btn btn-sm btn-primary me-2' title='Edit'><i class='fa fa-pencil'></i></a>
        <button class='btn btn-sm btn-danger delete-spec' data-delete='{$row['id']}' title='Delete'><i class='fa fa-trash-o'></i></button>
    ";

    $nestedData = array(
        "customer"    => htmlspecialchars((string)$row['customer_name']),
        "style"       => htmlspecialchars((string)$row['cs_title']),
        "category"    => htmlspecialchars((string)$row['category']),
        "element"     => htmlspecialchars((string)$row['element_name']),
        "description" => $messageContent,
        "version"     => $row['version'],
        "updated_at"  => date("d-M-Y H:i", strtotime($row['updated_at'])),
        "action"      => $actionBtns
    );

    $data[] = $nestedData;
}

// Total records without filtering
$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM journal_spec_details");
$totalData = mysqli_fetch_assoc($totalQuery)['total'];

// JSON response
$json_data = array(
    "recordsTotal"    => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
);

echo json_encode($json_data);
?>

<?php
include "includes/includes.php";

$cs_id    = isset($_GET['cs_id']) ? intval($_GET['cs_id']) : 0;
$category = isset($_GET['category']) ? mysqli_real_escape_string($db, $_GET['category']) : '';

$response = [
    'categories' => [],
    'elements'   => []
];

if ($cs_id > 0) {

    // ✅ Fetch categories available for this style
    $cat_qry = mysqli_query($db, "
        SELECT DISTINCT category 
        FROM journal_spec_master 
        WHERE cs_id = $cs_id AND status = 'Active'
        ORDER BY category
    ");

    while ($cat = mysqli_fetch_assoc($cat_qry)) {
        $response['categories'][] = $cat['category'];
    }

    // ✅ If a category is selected, also fetch elements under it
    if ($category != '') {
        $el_qry = mysqli_query($db, "
            SELECT js_id, element_name 
            FROM journal_spec_master 
            WHERE cs_id = $cs_id 
              AND category = '$category' 
              AND status = 'Active' 
            ORDER BY element_name
        ");
        while ($row = mysqli_fetch_assoc($el_qry)) {
            $response['elements'][] = $row;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

<?php
include "../includes/Auth.php";
include "../includes/function.php";
$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$gSearch="";


$sort_columns = array(0 => 'h_id', 1 => 'h_title', 2 => 'created_by', 3 => 'h_cdate');
$sort_by = $sort_columns[$col];
$sort_type = $_REQUEST['sSortDir_0'];



// Prepare the base query
$query_happenings = "SELECT h_id, h_title,wf_happenings.created_by, h_cdate,full_name FROM wf_happenings left join users on users.id=wf_happenings.created_by where 1=1";

// If a search term is provided, add the search query to the base query
$search_qry = '';    
if (!empty($sSearch)) {
    $search_qry = " AND (h_title LIKE '%".$sSearch."%' 
                        OR created_by LIKE '%".$sSearch."%')";
}

// Combine the base query with search query, sorting, and pagination
$query_happenings .= $search_qry . ' ORDER BY ' . $sort_by . ' ' . $sort_type . " LIMIT " . $start . ", " . $length;

// Run the query directly using mysqli_query
$result_happenings = mysqli_query($db, $query_happenings); 

// Get the total count of rows
$total_count = mysqli_num_rows($result_happenings);

// Initialize the response array
$rec = array(
    'iTotalRecords' => $total_count,
    'iTotalDisplayRecords' => $total_count,
    'aaData' => array()
);

// Check if there are results
if ($total_count > 0) {
    // Fetch each happenings row and add it to the response array
    while ($happenings = mysqli_fetch_assoc($result_happenings)) {
        $rec['aaData'][] = array(
            $happenings['h_id'],
            $happenings['h_title'],
            date('d-m-Y h:i:s', strtotime($happenings['h_cdate'])),
            $happenings['full_name'],
            '<div class="hidden-sm hidden-xs action-buttons"><button data-delete="'.(int)$happenings['h_id'].'" class="btn btn-danger demo4 delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button></div>'
        );
    }
}

// Return the data in JSON format
echo json_encode($rec);
?>



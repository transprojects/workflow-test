<?php
$servername ="68.178.166.2";
$username = "mytickettransfor_system";
$password = "1Dvwp3u6%4Kb";
$database_name = "mytickettransfor_system";
// Create connection
$mytickets_db = new mysqli($servername, $username, $password, $database_name);


$start = $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];

$sort_columns = array(0 => 'ticket_id', 1 => 'name', 2 => 'email', 3 => 'category_id', 4 => 'priority', 5 => 'subject', 6 => 'status', 7 => 'type', 8 => 'created_at');
$sort_by = $sort_columns[$col];
$sort_type = $_REQUEST['sSortDir_0'];



// Prepare the base query
$query_tickets = "SELECT id, ticket_id, name, email, mobile_no, category_id, priority, subject, status, type, created_by, created_at 
          FROM tickets 
          WHERE email = '".$_COOKIE['user_email']."'";

// If a search term is provided, add the search query to the base query
$search_qry = '';    
if (!empty($sSearch)) {
    $search_qry = " AND (ticket_id LIKE '%".$sSearch."%' 
                        OR name LIKE '%".$sSearch."%' 
                        OR email LIKE '%".$sSearch."%' 
                        OR subject LIKE '%".$sSearch."%' 
                        OR status LIKE '%".$sSearch."%' 
                        OR priority LIKE '%".$sSearch."%' 
                        OR type LIKE '%".$sSearch."%')";
}

// Combine the base query with search query, sorting, and pagination
$query_tickets .= $search_qry . ' ORDER BY ' . $sort_by . ' ' . $sort_type . " LIMIT " . $start . ", " . $length;

// Run the query directly using mysqli_query
$result_tickets = mysqli_query($mytickets_db, $query_tickets); 

// Get the total count of rows
$total_count = mysqli_num_rows($result_tickets);

// Initialize the response array
$rec = array(
    'iTotalRecords' => $total_count,
    'iTotalDisplayRecords' => $total_count,
    'aaData' => array()
);

// Check if there are results
if ($total_count > 0) {
    // Fetch each ticket row and add it to the response array
    while ($ticket = mysqli_fetch_assoc($result_tickets)) {
        $rec['aaData'][] = array(
            $ticket['ticket_id'],
            $ticket['name'],
            $ticket['email'],
            $ticket['category_id'],
            $ticket['priority'],
            $ticket['subject'],
            $ticket['status'],
            $ticket['type'],
            date('d-m-Y h:i:s', strtotime($ticket['created_at']))
        );
    }
}

// Return the data in JSON format
echo json_encode($rec);
?>



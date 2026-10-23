<?php
error_reporting(E_ALL & ~E_NOTICE);
include "../includes/Auth.php";
include "../includes/function.php";


$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$gSearch="";

$arr = array(0 => 'i.pub_id', 1 => 'a.stage', 2 => 'a.created_at', 3 => 'category_1_response', 4 => 'category_2_response', 5 => 'category_3_response', 6 => 'category_4_response', 7 => 'category_5_response', 8 => 'category_6_response');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];




 /*$query = "SELECT 
    i.pub_id,
    c.name AS category_name,
    a.reason,
    a.created_at
FROM 
    fp_prqc_report a
JOIN 
    prqc_report_categories c ON a.category_id = c.id
JOIN 
    inw_inward_dtl i ON a.article_id = i.id";*/
	
	$query = "SELECT 
    i.pub_id,
    a.article_id,
    a.stage,
	a.created_at,
    MAX(CASE WHEN a.category_id = 1 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_1_response,
    MAX(CASE WHEN a.category_id = 2 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_2_response,
    MAX(CASE WHEN a.category_id = 3 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_3_response,
    MAX(CASE WHEN a.category_id = 4 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_4_response,
    MAX(CASE WHEN a.category_id = 5 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_5_response,
    MAX(CASE WHEN a.category_id = 6 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_6_response,
	MAX(CASE WHEN a.category_id = 7 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_7_response,
	MAX(CASE WHEN a.category_id = 8 THEN CONCAT(c.name, ' - Feedback : ', a.reason) ELSE NULL END) AS category_8_response	
FROM 
    fp_prqc_report a
JOIN 
    prqc_report_categories c ON a.category_id = c.id
JOIN 
    inw_inward_dtl i ON a.article_id = i.id
";

$search_qry = '';    
if(!empty($sSearch)) {
    $search_qry = " WHERE ( i.pub_id LIKE '%".$sSearch."%' OR c.name LIKE '%".$sSearch."%' OR  a.reason LIKE '%".$sSearch."%' )";
}

$query = $query . $search_qry . ' GROUP BY a.article_id, a.stage ORDER BY '.$sort_by.' '.$sort_type;

$chk_transaction = $db->query($query); 
$transaction_toatl_count = $chk_transaction->num_rows;

$query = $query . "  LIMIT ".$start.", ".$length;
$chk_transaction = $db->query($query);

//$rec='';
$rec = array(
	'iTotalRecords' => 0,
	'iTotalDisplayRecords' =>0,
	'aaData' => array()
);
if($transaction_toatl_count!='')
{

	if($transaction_toatl_count > 0)
	{
		while ($row = $chk_transaction->fetch_assoc()) {
			$data[] = $row;
		}
	}
	else {
		$data[] = '';
	}


$iTotal = $transaction_toatl_count;

//echo $iTotal;

$rec = array(
	'iTotalRecords' => $iTotal,
	'iTotalDisplayRecords' => $iTotal,
	'aaData' => array()
);

$k=0;
if (isset($data) && is_array($data)) {
	foreach ($data as $workflow) {
		
		$sent_date=date('d-m-Y h:i:s',strtotime($workflow['created_at']));	
		if($workflow['stage'] == 'FP')
		{
			$rec['aaData'][$k] = array(
			0 => $workflow['pub_id'],
			1 => $workflow['stage'],
			2=> $sent_date,
			3=> $workflow['category_1_response'],
			4=> $workflow['category_2_response'],
			5=> $workflow['category_3_response'],
			6=> $workflow['category_4_response'],
			7=> $workflow['category_5_response'],
			8=> $workflow['category_6_response']					
						
		);
			
		}else {
			$rec['aaData'][$k] = array(
			0 => $workflow['pub_id'],
			1 => $workflow['stage'],
			2 => $sent_date,
			3=> $workflow['category_7_response'],
			4=> $workflow['category_8_response'],
			5=> $workflow['category_3_response'],
			6=> $workflow['category_4_response'],
			7=> $workflow['category_5_response'],
			8=> ''	
		);
			
		}
		
		$k++;
	}
}
}

echo json_encode($rec);
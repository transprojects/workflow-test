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

$arr = array(0 => 'staff_name', 1 => 'staff_email', 2 => 'staff_id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


if($_COOKIE['staff_type']!='1')
	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
else	
	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->Selectstaffs($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
 //print_r($res_prof);
//echo count($res_prof);
//$rec='';
$rec = array(
	'iTotalRecords' => 0,
	'iTotalDisplayRecords' =>0,
	'aaData' => array()
);
if($res_prof!='')
{

	if(count($res_prof)>0 && count($res_prof)!='NULL')
	{
		foreach($res_prof as $row)
		{
			$data[] = $row;
		}
	}
	else {
		$data[] = '';
	}

$qry = "";	
$qry = $auth->Countstaffs($sSearch,$gSearch);
foreach($qry as $row)
{	
	$iTotal = $row['count'];
}
//echo $iTotal;

$rec = array(
	'iTotalRecords' => $iTotal,
	'iTotalDisplayRecords' => $iTotal,
	'aaData' => array()
);

$k=0;
if (isset($data) && is_array($data)) {
	foreach ($data as $staffs) {		
		$rec['aaData'][$k] = array(
			0 => $staffs['staff_name'],
			1 => $staffs['staff_email'],
			2 => $staffs['staff_id']
		);
		$k++;
	}
}
}

echo json_encode($rec);
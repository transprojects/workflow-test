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

$arr = array(0 => 'cust_name', 1 => 'j_code', 2 => 'stage', 3 => 'subject', 4 => 'dm.created_on', 5 => 'dm_id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectDispatchEmails($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountDispatchEmails($sSearch,$gSearch);
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
	foreach ($data as $dispatchemail) {		
		$rec['aaData'][$k] = array(
			0 => $dispatchemail['cust_name'],
			1 => $dispatchemail['j_code'],
			2 => $dispatchemail['stage'],
			3 => $dispatchemail['subject'],
			4 => $dispatchemail['created_on'],
			5 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-email/'.$dispatchemail['dm_id'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$dispatchemail['dm_id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
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

$arr = array(0 => 'user_name', 1 => 'customer_name', 2 => 'j_codes', 3 => 'stage', 4 => 'per_day_limit', 5 => 'assign_order', 6 => 'id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectUsersTargetAssignConfig($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountSelectUsersTargetAssignConfig($sSearch,$gSearch);
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
	foreach ($data as $employees) {		

//$arr = array(0 => 'user_name', 1 => 'customer_name', 2 => 'j_codes', 3 => 'stage', 4 => 'per_day_limit', 5 => 'assign_order', 6 => 'id');

		$rec['aaData'][$k] = array(
			0 => $employees['user_name'],
			1 => $employees['customer_name'],
			2 => $employees['j_codes'],
			3 => $employees['stage'],
			4 => $employees['per_day_limit'],
			5 => $employees['assign_order'],
			6 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-user-target-assign-config/'.$employees['id'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$employees['id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
			</div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
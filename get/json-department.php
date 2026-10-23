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

$arr = array(0 => 'dept_name', 1 => 'dept_sn', 2 => 'created_dt', 3 => 'parent_id', 4 => 'dept_code');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectDepartment($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountDepartment($sSearch,$gSearch);
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
	foreach ($data as $department) {	
		
		$parent_name = '';
		if($department['parent_id']>0)
		{
			$pr = $db_handle->runBaseQuery("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".(int)$department['parent_id']." ");
			$parent_name = ($pr && isset($pr[0]['dept_name'])) ? $pr[0]['dept_name'] : '';
		}


		$rec['aaData'][$k] = array(
			0 => $department['dept_name'],
			1 => $department['dept_sn'],
			2=> $department['created_dt'],
			3 => $parent_name,
			4 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-department/'.$department['dept_code'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$department['dept_code'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
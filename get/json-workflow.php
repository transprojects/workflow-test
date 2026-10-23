<?php
error_reporting(E_ALL & ~E_NOTICE);
include "../includes/Auth.php";
include "../includes/function.php";
include "../dbc.php";

$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$gSearch="";

$arr = array(0 => 'wf_name', 1 => 'wf_code', 2 => 'created_dt', 3 => 'wf_id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectWorkflow($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountWorkflow($sSearch,$gSearch);
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
	foreach ($data as $workflow) {	
		
		
		if($workflow['wf_code']!='')
					{
						$exist_departments = explode(',',$workflow['wf_code']);
						
					$dept_str = '';
					foreach($exist_departments as $dept_id)
					{
						$query_dept = "SELECT dept_name FROM `adm_dept_master` WHERE dept_code = ".$dept_id.";";
						$dept_info = $db->query($query_dept) or ($db->error);	 
						$dept_row = $dept_info->fetch_assoc();
						if($dept_str=='')
						$dept_str =  $dept_row['dept_name']; 
						else
						$dept_str .=  ' <b><i class="fa fa-arrow-right"></i></b> '.$dept_row['dept_name']; 
					}
					 $dep=$dept_str;
					}


		$rec['aaData'][$k] = array(
			0 => $workflow['wf_name'],
			1 =>  $dep,
			2=> $workflow['created_dt'],
			3 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-workflow/'.$workflow['wf_id'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$workflow['wf_id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
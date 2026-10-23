<?php
ini_set("display_errors", "0");
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

$arr = array(0 => 'nwa_cust_name', 1 => 'nwa_pub_id', 2 => 'nwa_page_count', 3 => 'user_name', 4 => 'nwa_cdatetime', 5 => 'nwa_cdatetime', 6 => 'nwa_pt', 7 => 'nwa_comments');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


if($_REQUEST['article_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!='' || $_REQUEST['user_id']!='')
{
	

	
	if(isset($_REQUEST['from_dt']) && $_REQUEST['to_dt']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and (DATE_FORMAT(nwa_date,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(nwa_date,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'")';


	if(isset($_REQUEST['article_id']) && $_REQUEST['article_id']!='')

            $gSearch.=' and nwa_pub_id LIKE "%'.$_REQUEST['article_id'].'%" ';  
			
	if(isset($_REQUEST['nwa_status']) && $_REQUEST['nwa_status']!='')

            $gSearch.=' and nwa_status ="'.$_REQUEST['nwa_status'].'"';  
			
	if(isset($_REQUEST['user_id']) && $_REQUEST['user_id']!='')

            $gSearch.=' and user_id ="'.$_REQUEST['user_id'].'"';  
			
	if(isset($_REQUEST['dep_id']) && $_REQUEST['dep_id']!='')

            $gSearch.=' and department_id ="'.$_REQUEST['dep_id'].'"';  

	if($_COOKIE['user_level']=='3')

		$gSearch .=' and user_id='.$_COOKIE['user_id'];
		
	if($_COOKIE['user_level']!='3')

		$gSearch .=' ';
	

}
elseif($_COOKIE['user_level']=='3')
{
 	$gSearch .=' and user_id='.$_COOKIE['user_id'];

}
else	
{
	$gSearch .='1=0';
}


 $res_prof = $auth->SelectNonworkflowArticleUsers($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountNonworkflowArticleUsers($sSearch,$gSearch);
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
		
		
		$rec['aaData'][$k] = array(
			0 => '<span data-user-id="'.$department['user_id'].'">'.$department['full_name'].'</span>',
			1 => $department['nwa_cust_name'],
			2 => $department['nwa_pub_id'],
			3=> date('d-m-Y',strtotime($department['nwa_date'])),
			4 => $department['nwa_page_count'],
			5 => '<input type="number" class="form-control percent-input" name="percentage['.$department['nwa_id'].']" value="' . ($department['nwa_percentage'] !== null ? $department['nwa_percentage'] : '') . '" min="0" max="100">',
			6 => ($department['nwa_pt'] !== null ? $department['nwa_pt'] : ''),
			7 => '<div style="max-height:100px; overflow-y:auto; white-space:normal; word-break:break-word;">'.htmlspecialchars($department['nwa_comments']).'</div>',
			8 => ($department['nwa_status'] === 'Approved')? '<span class="badge bg-success">Approved</span>': '<input type="checkbox" name="approved['.$department['nwa_id'].']" class="form-check-input approval-checkbox">',
			9 => '<div class="hidden-sm hidden-xs action-buttons"><button data-delete="'.$department['nwa_id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button></div>'

		);
		$k++;
	}
}
}

echo json_encode($rec);
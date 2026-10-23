<?php
error_reporting(0);
include "../includes/Auth.php";
include "../includes/function.php";
$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$col1 = $_REQUEST['iSortCol_1'];

$gSearch="";
session_start();
$arr = array(0 => 'j_id',1 => 'stage', 2 => 'dept_id', 3 => 'path', 4 => 'out_path', 5 => '');
$sort_by = $arr[$col];
$sort_by_d = $arr[$col1];
$sort_type = $_REQUEST['sSortDir_0'];
$sort_type_d = $_REQUEST['sSortDir_1'];



if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['stage']!='' || $_REQUEST['dept_id']!=''))
{
	

	if(isset($_REQUEST['cust_id']) && $_REQUEST['cust_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(wd.cust_id,"'.$_REQUEST['cust_id'].'")';
        

	if(isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(wd.j_id,"'.$_REQUEST['j_id'].'")';
		
if(isset($_REQUEST['stage']) && $_REQUEST['stage']!='')
	{

		 $ex_stage=explode(",",$_REQUEST['stage']);
		 if(count($ex_stage)==1)
		 {
			$and=' and';
			$or='';
		 }
		 else
		 {
			$and=' and (';
			$orf=' or ';  
		}
		$or='';
		 for($i=0;$i<=count($ex_stage)-1;$i++)
		 {

			if($i==count($ex_stage)-1)
			{
				$or = ')';

			}
			
			if($i==0)
			{
				$gSearch.=''.$and.' wd.stage LIKE "%'.$ex_stage[$i].'%" ';  
			}
			else
			{
				$gSearch.=''.$orf.' wd.stage LIKE "%'.$ex_stage[$i].'%" '.$or.' ';  

			}
			
		}

	}
		
		
if(isset($_REQUEST['dept_id']) && $_REQUEST['dept_id']!='')

         //$dept = implode(",",$_REQUEST['dept']);

		$gSearch .= ' and FIND_IN_SET(wd.dept_id,"'.$_REQUEST['dept_id'].'")';
		
	


	

}
else
{
	$gSearch .= 'and 1=1';
}

$_SESSION['varname'] = $gSearch;
//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;

// $res_prof = $auth->showArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length,$gSearch);
 $res_prof = $auth->SelectArtConfig($sSearch, $sort_by, $sort_type, $start, $length,$_SESSION['varname']);
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
$qry = $auth->countArtconfig($sSearch,$_SESSION['varname']);
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
	foreach ($data as $artconfig) {		
	$rec['aaData'][$k] = array(
			0 => $artconfig['j_code'],
			1 => $artconfig['stage'],
			2 => $artconfig['dept_name'],
			3 => $artconfig['path'],
			4 => $artconfig['out_path'],
			5 => '<div class="hidden-sm hidden-xs action-buttons">
		<button data-delete="'.$artconfig['art_id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
			
		
		);
		$k++;
	}
}
}

echo json_encode($rec);
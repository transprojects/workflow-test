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
$arr = array(0 => 'j_code', 1 => 'pub_id', 2 => 'recv_dt', 3 => 'publication_date', 4 => 'stage', 5 => 'stage_with', 6 => 'staff_name', 7 => 'id');
$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];

if(($_REQUEST['j_id']!='' || $_REQUEST['article_id']!='' || $_REQUEST['user']!=''))
{
	
	if(isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(wd.j_id,"'.$_REQUEST['j_id'].'")';

	
	if(isset($_REQUEST['article_id']) && $_REQUEST['article_id']!='')

            $gSearch.='and wd.job_id LIKE "%'.$_REQUEST['article_id'].'%" ';  


	if(isset($_REQUEST['user']) && $_REQUEST['user']!='')
                 
            //$user = implode(",",$_REQUEST['user']);

         $gSearch.='and FIND_IN_SET(wd.staff_id,"'.$_REQUEST['user'].'")';


}
else
{
	$gSearch .= "";
}

if($_COOKIE['staff_type']!='1')
	$gSearch .=' and wd.staff_id='.$_COOKIE['staff_id'];
else	
	$gSearch .="";

//echo $gSearch; exit;
$res_prof = $auth->SelectCinvoice($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->countcinvoicelist($sSearch,$gSearch);
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
	foreach ($data as $item) {

		$stage_r=preg_replace("/REV([0-9]+)/","REV",$item['stage']);

		if($item['stage']== 'FP')
			$stage = 'FIRST PROOF';
		elseif($item['stage']== 'REV1')
			$stage = 'REVISES';
		elseif($item['stage']!= 'REV1' && $stage_r== 'REV')
			$stage = 'FINALS';
		elseif($item['stage']== 'Published')
			$stage = 'PUBLISHED';
             if($item['publication_date']!='' && $item['publication_date']!='0' && $item['publication_date']!='NULL')
			{
			    $pd=date('d-m-Y',strtotime($item['publication_date']));
			}
			else
			{
			   $pd='';  
			}

		$rec['aaData'][$k] = array(
			0 => $item['j_code'],
			1 => $item['pub_id'],
			2 => date('d-m-Y',strtotime($item['recv_dt'])),
			3 => $pd,
			4 => $stage,
			5 => $item['stage_with'],			
            6 => $item['staff_name']
			
		);
		$k++;
	}
}
}
echo json_encode($rec);
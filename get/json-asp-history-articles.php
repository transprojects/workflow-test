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

$arr = array(0 => 'loaded_date', 1 => 'original_article_name_number', 2 => 'journal', 3 => 'id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectASPHistoryArticles($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountASPHistoryArticles($sSearch,$gSearch);
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
	

		$inw_details=mysqli_fetch_array(mysqli_query($db,"select * from inw_inward_dtl where pub_id='".$workflow['article_no']."'"));

		$sent_to_author=mysqli_query($db,"select *  from inw_dispatch_history where a_id='".$inw_details['id']."'");
		$num_dh=mysqli_num_rows($sent_to_author);
		$res_dh=mysqli_fetch_array($sent_to_author);
		
		$vol_details=mysqli_fetch_array(mysqli_query($db,"select * from  inw_volume_issues where vi_id='".$inw_details['v_issue_id']."'"));
		$rev_num=mysqli_num_rows(mysqli_query($db,"select * from inw_inward_dtl where pub_id='".$workflow['article_no']."' and stage LIKE '%REV%'"));
		$query_num=mysqli_fetch_array(mysqli_query($db,"select * from tbl_query_client where aid='".$inw_details['id']."' "));
		if($num_dh>0)
		{
			$sent_date=date('d-m-Y h:i:s',strtotime($res_dh['sent_date']));

		}
		else
		{
			$sent_date='-';
		}
		if($rev_num>0){  $status= "Yes"; }else {  $status="No";}
		if($inw_details['v_issue_id']>0){  $vol=$vol_details['volume_number'].$workflow['journal'].$vol_details['issue_number']; }else{ $vol="-";}
		//if($query_num['message']!='') {  $message=$query_num['message']; } else { $message='-'; }
		$rec['aaData'][$k] = array(
			0 => $workflow['loaded_date'],
			1=> $workflow['original_article_name_number'],
			2=> $workflow['journal'],
			3=> $inw_details['article_title'],
			4=> $inw_details['corresponding_author'],
			5=> $inw_details['corresponding_mail'],
			6=> $workflow['article_no'],
			7=> $inw_details['page_count'],
			8=> $sent_date ,
			9=> $status,
			10=> $vol,
			11=> $messages='1',
		);
		$k++;
	}
}
}

echo json_encode($rec);
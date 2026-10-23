<?php
//error_reporting(0);
include '../includes/global_variables.php';
include "../includes/Auth.php";
include "../includes/function.php";
include "../includes/issue_drafts.php";
$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];

$gSearch="";

$arr = array(0 => 'cust_name', 1 => 'j_code', 2 => 'volume_number', 3 => 'starting_page_number', 4 => 'rcv_dt', 5 => 'due_dt', 6 => 'stage', 7 => 'ccount', 8 => 'pcount', 9 => '', 10 => '');
$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!=''))
{
	

	if(isset($_REQUEST['cust_id']) && $_REQUEST['cust_id']!='')
	$gSearch .= ' and FIND_IN_SET(vi.cust_id,"'.$_REQUEST['cust_id'].'")';
    
	if(isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='')
	$gSearch .= ' and FIND_IN_SET(vi.j_id,"'.$_REQUEST['j_id'].'")';

	if(isset($_REQUEST['from_dt']) && $_REQUEST['from_dt']!='' && isset($_REQUEST['to_dt']) && $_REQUEST['to_dt']!='')
	{
		/* the condition is pasted inside the single-quoted argument of call Manageissues('...'), so the dates must use double quotes */
		$gSearch .= ' and vi.received_date BETWEEN "'.date('Y-m-d',strtotime($_REQUEST['from_dt'])).'" and "'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'"';
	}
}


if($_COOKIE['user_level'] != 1) 
{
	//$gSearch .= " and (vi.cust_id = '".$_COOKIE['cust_id']."' OR FIND_IN_SET(vi.cust_id,'".$_COOKIE['ocust_id']."'))";
	$gSearch .= " ";
}
		
//echo $gSearch;


$res_prof = $auth->SelectManageissues($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
//var_dump($res_prof); exit;
//echo count($res_prof);
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
$qry = $auth->CountManageissues($sSearch,$gSearch);
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
$idr_withclient = idr_withclient_map(function ($sql) use ($db_handle) { $r = $db_handle->runBaseQuery($sql); return is_array($r) ? $r : array(); });
$idr_map = idr_issue_map(function ($sql) use ($db_handle) { $r = $db_handle->runBaseQuery($sql); return is_array($r) ? $r : array(); });
if (isset($data) && is_array($data)) {
	foreach ($data as $row) {		
		 
		
		if($row['received_date']!= "")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['rcv_dt'])));
		}
		
		/* one button group per row so the buttons always sit on one line; colours: Start blue, Upload purple, Collation teal, Skip green */
		$btns = '';
		if($row['pcount'] > 0) {
                $btns .='<a class="btn btn-xs btn-primary" href="show_issues.php?id='.$row['vi_id'].'"><i class="ace-icon fa fa-tasks bigger-120"></i> Start </a><a class="btn btn-xs btn-purple" href="issue_upload_process.php?id='.$row['vi_id'].'"><i class="ace-icon fa fa-upload bigger-120"></i> Upload </a>';

															 $num = preg_replace("/([a-zA-Z])/","",$row['stage']);
					if(($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1) && $num > 1 ){
                $btns .='<a class="btn btn-xs btn-danger" title="Skip articles to the next stage" href="skip_issue_articles.php?id='.$row['vi_id'].'"><i class="ace-icon fa fa-fast-forward bigger-120"></i> Skip </a>';
                 } } else {
                $btns .='<a class="btn btn-xs btn-info" href="show_issues_merge.php?id='.$row['vi_id'].'"><i class="ace-icon fa fa-files-o bigger-120"></i> Collation </a>';
                }
		/* articles of this issue that are with the client can be booked for another draft round, even while other articles are in production */
		if(!empty($idr_withclient[$row['vi_id']]) && ($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1)) {
			$btns .= '<a class="btn btn-xs btn-success" title="Book more articles for client corrections (DRAFT1, DRAFT2 ...)" href="issue_draft_rebook.php?id='.$row['vi_id'].'"><i class="ace-icon fa fa-pencil bigger-120"></i> Draft </a>';
		}
		$action_status = '<div class="hidden-sm hidden-xs action-buttons">'.$btns.'</div>';
							
		$rec['aaData'][$k] = array(
			0 => $row['cust_name'],
			1 => $row['j_code'],
			2 => VOLUME_PREFIX.$row['volume_number'].$row['issue_number'],
			3 => $row['starting_page_number'],
			4 => $recv_dt,
			5 => $due_dt,
			6 => $row['stage'],
			7 => $row['ccount'],
			8 => $row['pcount'],
			9 => idr_issue_cell($idr_map, $row['vi_id']),
			10 => $action_status
		);
		$k++;
	
		
	}
}
}

echo json_encode($rec);


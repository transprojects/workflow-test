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

$arr = array(0 => 'j_code', 1 => 'file_name', 2 => 'work_mode', 3 => '', 4 => 'corresponding_author');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];

 $res_prof = $auth->showAuthorApprovedArticlelist($sSearch, $sort_by, $sort_type, $start, $length);
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
$qry = $auth->countAuthorApprovedArticlelist($sSearch);
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
	foreach ($data as $articles) {		
        if($articles['is_approved']== '1')
        {
        	if($articles['work_mode']== '1')
        			$workmode = 'OFFLINE';
        		elseif($articles['work_mode']== '2')
        			$workmode = 'ONLINE';
        			
        			$rec['aaData'][$k] = array(
        			0 => $articles['j_code'],
        			1 => $articles['file_name'],
        			2 => $workmode,
        			3 => '<a href="download_proof.php?aid='.$articles['project_id'].'"><img src="images/filezip.svg" style="width:25px;">Download</a>',
        			4 => $articles['author_name']
        		);
        		$k++;
    	}
	}
}
}

echo json_encode($rec);
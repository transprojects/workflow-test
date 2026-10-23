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

$arr = array(0 => 'end_date', 1 => 'file_name', 2 => '', 3 => 'work_mode', 4 => '', 5 => '', 6 => '', 7 => 'author_name');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];

 $res_prof = $auth->showAuthorArticlelist($sSearch, $sort_by, $sort_type, $start, $length);
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
$qry = $auth->countAuthorArticlelist($sSearch);
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
        
             if($articles['is_approved']!= '1')
            {
                	if($articles['work_mode']== '1')
                			$workmode = 'OFFLINE';
                		elseif($articles['work_mode']== '2')
                			$workmode = 'ONLINE';
                			
                			$rec['aaData'][$k] = array(
                			0 => $articles['j_code'],
                			1 => $articles['file_name'],
                			2 => '<a href="download_tproof.php?aid='.$articles['project_id'].'"><img src="images/filezip.svg" style="width:25px;">Download</a>',
                			3 => $workmode,
                			4 => '<a href="download_proof.php?aid='.$articles['project_id'].'"><img src="images/filezip.svg" style="width:25px;">Download</a>',
                			5 => '<button class="correction_button" data-follow='.$articles['project_id'].' class="btn-sm btn-success waves-effect waves-light" >Upload</button>',
                		//	5 => '<button class="nocorrection_button" onclick="nocorrection('.$k.','.$articles['project_id'].');">No Correction</button>',
                			6 => '<button class="nocorrection_button"  onclick="alert_approve('.$articles['project_id'].');">Approve</button>',
                			7 => $articles['author_name']
                		);
                		$k++;
    	}
	}
}
}

echo json_encode($rec);
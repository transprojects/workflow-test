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

$arr = array(0 => 'j_code', 1 => 'pub_id', 2 => 'publication_date', 3 => '', 4 => '', 5 => '', 6 => '', 7 => 'staff_name');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];

 $res_prof = $auth->showFinalArticlelist($sSearch, $sort_by, $sort_type, $start, $length);
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
$qry = $auth->countFinalArticlelist($sSearch);
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


			$rec['aaData'][$k] = array(
			0 => $articles['j_code'],
			1 => $articles['pub_id'],
			2 => date('d-m-Y',strtotime($articles['publication_date'])),
			3 => '<a href="revised_package.php?aid='.$articles['id'].'"><img src="images/filezip.svg" style="width:25px;">Download</a>',
			4 => '<button class="correction_button" data-follow='.$articles['id'].' class="btn-sm btn-success waves-effect waves-light" data-toggle="modal" data-target=".bs-example-modal-lg">Correction</button>',
			5 => '<button class="nocorrection_button" onclick="nocorrection('.$k.','.$articles['id'].');">No Correction</button>',
			6 => '<div id="pid'.$k.'" style="display:none;"  onclick="call_publish('.$articles['id'].');"><button class="publish_button">Publish</div></div>',
			7 => $articles['staff_name']
		);
		$k++;
	}
}
}

echo json_encode($rec);
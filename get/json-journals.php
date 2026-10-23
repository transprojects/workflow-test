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

/* column filters from the list page (cf_<n>): letters, digits, space and - _ . @ / & ( ) + , : only, so nothing can break out of the SQL.
   $gSel goes inside the CALL ... '<here>' string (quotes escaped), $gCnt is used by the plain count query. */
function lu_filter_clause($cols)
{
	$sel = ''; $cnt = '';
	foreach ($cols as $i => $col) {
		$v = isset($_REQUEST['cf_' . $i]) ? preg_replace('/[^A-Za-z0-9 \-_.@\/&()+,:]/', '', (string)$_REQUEST['cf_' . $i]) : '';
		$v = trim($v);
		if ($v === '') { continue; }
		$sel .= " and " . $col . " like \\'%" . $v . "%\\'";
		$cnt .= " and " . $col . " like '%" . $v . "%'";
	}
	return array($sel, $cnt);
}
list($gSearch, $gCount) = lu_filter_clause(array(0 => 'c.cust_name', 1 => 'j.j_name', 2 => 'j.j_code', 3 => 'j.j_fp_wf', 4 => 'j.j_rev_wf', 5 => 'j.j_fin_wf', 6 => 'j.j_issue_wf', 7 => 'j.j_jstage_wf', 8 => 'j.j_pmc_wf', 9 => 'j.created_on'));

$arr = array(0 => 'cust_name', 1 => 'j_name', 2 => 'j_code', 3 => 'j_fp_wf', 4 => 'j_rev_wf', 5 => 'j_fin_wf', 6 => 'j_issue_wf', 7 => 'j_jstage_wf', 8 => 'j_pmc_wf', 9 => 'created_on', 10 => 'j_id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectJournals($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountJournalsFiltered($sSearch,$gCount);
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
	foreach ($data as $journals) {		
		$rec['aaData'][$k] = array(
			0 => $journals['cust_name'],
			1 => $journals['j_name'],
			2 => $journals['j_code'],
			3 => $journals['j_fp_wf'],
			4 => $journals['j_rev_wf'],
			5 => $journals['j_fin_wf'],
			6 => $journals['j_issue_wf'],
			7 => isset($journals['j_jstage_wf']) ? $journals['j_jstage_wf'] : '',
			8 => isset($journals['j_pmc_wf']) ? $journals['j_pmc_wf'] : '',
			9 => $journals['created_on'],
			10 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-journal/'.$journals['j_id'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$journals['j_id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
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
list($gSearch, $gCount) = lu_filter_clause(array(0 => 'cust_name', 1 => 'email', 2 => 'created_dt'));

$arr = array(0 => 'cust_name', 1 => 'email', 2 => 'id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';


 $res_prof = $auth->SelectCustomers($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->CountCustomers($sSearch,$gCount);
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
	foreach ($data as $customers) {		
		$rec['aaData'][$k] = array(
			0 => $customers['cust_name'],
			1 => $customers['email'],
			2 => $customers['created_dt'],
			3 => '<div class="hidden-sm hidden-xs action-buttons">
		  <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-customer/'.$customers['id'].'">
		   <i class="ace-icon fa fa-pencil bigger-120"></i>
		    </a> <button data-delete="'.$customers['id'].'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
																		 </div>'
		);
		$k++;
	}
}
}

echo json_encode($rec);
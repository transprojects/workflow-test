<?php
error_reporting(0);
include "../includes/Auth.php";
include "../includes/function.php";
if (!defined('WF_DIR') || !defined('TOOL_DIR')) { require_once __DIR__ . '/../includes/global_variables.php'; }
$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$col1 = $_REQUEST['iSortCol_1'];

$gSearch="";
session_start();
$arr = array(0 => '',1 => 'cn', 2 => 'j_code', 3 => 'pub_id', 4 => 'stage', 5 => 'recv_dt', 6 => 'j_code', 7 => 'status', 8 => 'manuscript_count', 9 => '');
$sort_by = $arr[$col];
$sort_by_d = $arr[$col1];
$sort_type = $_REQUEST['sSortDir_0'];
$sort_type_d = $_REQUEST['sSortDir_1'];

if(($_COOKIE['user_level'] != 1) &&  ($_COOKIE['user_level'] != 5)  &&  ($_COOKIE['user_level'] != 12) &&  ($_COOKIE['user_level'] != 14))
{


	if($_COOKIE['department_id'] == '1' || $_COOKIE['department_id'] == '2')
	{
		$gSearch .= '  and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'"))
		and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
	}
	elseif($_COOKIE['department_id'] == '16')
	{
		$gSearch .= ' and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
	}
	else if($_COOKIE['department_id'] == '9')
	{
		$gSearch .= ' and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND (wd.fig_complete_status = 0 OR department_id = '.$_COOKIE['department_id'].') AND (wd.assign_department_id = '.$_COOKIE['department_id'].' OR fig > 0)';

	}
	else
	{
		$gSearch .= '  and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND  FIND_IN_SET(department_id,"'.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].'")'; 
	}
}
else
{
	$gSearch .= '';
}

if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['stage']!='' || $_REQUEST['dept']!='' || $_REQUEST['graphics_status']!='' || $_REQUEST['article_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!=''))
{
	

	if(isset($_REQUEST['cust_id']) && $_REQUEST['cust_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(wd.cust_id,"'.$_REQUEST['cust_id'].'")';
        

	if(isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(wd.j_id,"'.$_REQUEST['j_id'].'")';
		
		if(isset($_REQUEST['graphics_status']) && $_REQUEST['graphics_status']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and wd.fig_complete_status in ('.$_REQUEST['graphics_status'].') ';
        

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
if(isset($_REQUEST['dept']) && $_REQUEST['dept']!='')

         //$dept = implode(",",$_REQUEST['dept']);

		$gSearch .= ' and FIND_IN_SET(wd.department_id,"'.$_REQUEST['dept'].'")';
		
	if(isset($_REQUEST['from_dt']) && $_REQUEST['to_dt']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and (DATE_FORMAT(recv_dt,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(recv_dt,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'" OR DATE_FORMAT(received_date,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(received_date,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'")';


	if(isset($_REQUEST['article_id']) && $_REQUEST['article_id']!='')

            $gSearch.=' and wd.pub_id LIKE "%'.$_REQUEST['article_id'].'%" ';  


	

}
else
{
	$gSearch .= 'and 1=0';
}

// Query tab: only the articles whose status is Query (no single quotes: $gSearch goes through a single-quoted CALL argument)
$gBase = $gSearch;
$ptab = (isset($_REQUEST['ptab']) && $_REQUEST['ptab'] === 'query') ? 'query' : 'all';
if ($ptab === 'query') { $gSearch .= ' and wd.status = "Query" '; }
$_SESSION['varname'] = $gSearch;
//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;

// $res_prof = $auth->showArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length,$gSearch);
 $res_prof = $auth->showallArticlelist($sSearch, $sort_by, $sort_type, $start, $length,$_SESSION['varname']);
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
$qry = $auth->countallArticlelist($sSearch,$_SESSION['varname']);
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

//JSTAGE mark: articles whose workflow (inw_inward_dtl.wf_id) is the journal's JSTAGE Workflow (adm_journals.j_jstage_wf = adm_workflow_master.wf_name)
//The list comes from a stored procedure, so the flag is looked up for this page's article ids in one extra query.
$jstage_ids = array();
if (isset($data) && is_array($data)) {
	$page_ids = array();
	foreach ($data as $d_row) { if (is_array($d_row) && isset($d_row['id'])) { $page_ids[] = (int)$d_row['id']; } }
	if ($page_ids) {
		try {
			$js_rows = $db_handle->runBaseQuery('SELECT wd.id FROM inw_inward_dtl AS wd JOIN adm_journals AS j ON j.j_id = wd.j_id JOIN adm_workflow_master AS jm ON jm.wf_id = wd.wf_id AND jm.wf_name = j.j_jstage_wf WHERE j.j_jstage_wf <> "" AND wd.id IN (' . implode(',', $page_ids) . ')');
			if (is_array($js_rows)) { foreach ($js_rows as $js_r) { $jstage_ids[$js_r['id']] = true; } }
		} catch (Throwable $e) { /* j_jstage_wf column missing (JSTAGE migration not run): no tag */ }
	}
}

//Source / Work folder links (same paths as the Start Work page): one extra query for this page's article ids
$dir_info = array();
if (isset($page_ids) && $page_ids) {
	$dir_rows = $db_handle->runBaseQuery('SELECT wd.id, wd.work_dir, wd.volume_folder, wd.pub_id, wd.stage, d.dept_name FROM inw_inward_dtl AS wd LEFT JOIN adm_dept_master AS d ON d.dept_code = wd.department_id WHERE wd.id IN (' . implode(',', $page_ids) . ')');
	if (is_array($dir_rows)) { foreach ($dir_rows as $dr) { $dir_info[$dr['id']] = $dr; } }
}

$k=0;
if (isset($data) && is_array($data)) {
	foreach ($data as $articles) {		


		//$trans_status = $auth->showArticleTransactionlimitr($articles['id'],$articles['stage']);
//print_r($trans_status);
$aid=$articles['id'];

		$stage_r=preg_replace("/REV([0-9]+)/","REV",$articles['stage']);
	$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$articles['stage']);
		
		if ($articles['stage'] == "FP") {

    if ($_COOKIE['department_id'] == 1) {
        $due_dt = !empty($articles['target_due_dt'])
            ? date('d-m-Y', strtotime($articles['target_due_dt']))
            : '';
    } else {
        $due_dt = !empty($articles['due_dt'])
            ? date('d-m-Y', strtotime($articles['due_dt']))
            : '';
    }

    $recv_dt = !empty($articles['recv_dt'])
        ? date('d-m-Y', strtotime($articles['recv_dt']))
        : '';

    $manuscript_count = !empty($articles['manuscript_count'])
        ? $articles['manuscript_count']
        : 0;

} elseif ($stage_r == "REV") {

    $due_dt = !empty($articles['due_date'])
        ? date('d-m-Y', strtotime($articles['due_date']))
        : '';

    $recv_dt = !empty($articles['received_date'])
        ? date('d-m-Y', strtotime($articles['received_date']))
        : '';

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;

} elseif ($stage_i == 'ISSCOR') {

    $due_dt = !empty($articles['due_dt'])
        ? date('d-m-Y', strtotime($articles['due_dt']))
        : '';

    $recv_dt = !empty($articles['created_dt'])
        ? date('d-m-Y', strtotime($articles['created_dt']))
        : '';

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;

} else {

    $due_dt = !empty($articles['due_date'])
        ? date('d-m-Y', strtotime($articles['due_date']))
        : '';

    $recv_dt = !empty($articles['received_date'])
        ? date('d-m-Y', strtotime($articles['received_date']))
        : '';

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;
}
	
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		
		$xperttrack_sync=$articles['xperttrack_sync'];
		  if($articles['xperttrack_sync']=="1")
		{
			$ppms=" <b style='color:red';>(PPMS)</b>";
		}
		elseif($articles['xperttrack_sync']=="0")
		{
			$ppms=" ";
		}
		if($articles['issueorgroup_folder']!="")
		{
			$gf='<b style="color:red;">'.ltrim($articles['issueorgroup_folder'],"/").'</b>';
		}
		else
		{
			$gf="";
		}
		
		if($articles['highpriority']==1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($articles['status'] == 'Query') || ($articles['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $Due_dateTimestamp2 && $articles['status']!= 'Client Review')
		{
			//$tr_class = 'alert alert-danger';
			$due_highlight = '1';
		}
		elseif($current_dateTimestamp1 <= $Due_dateTimestamp2 && $articles['status']!= 'Client Review')
		{
			//$tr_class = 'alert alert-danger';
			$due_highlight = '0';
		}
		
if($articles['approved_by']>0)
{

		
			$start='';

}
else
{
	//$start='<a title="Start work"  class="btn btn-xs btn-primary"><i class="ace-icon fa fa-tasks bigger-120"></i>Not</a>';
	$start='';
}
if(!empty($articles['aq_status'])){  $aq_status='|<span class="label label-sm label bg-secondary text-white">'.$articles['aq_status'].'</span>'; } 

	if(trim($articles['status'])=='Client Review')
{
		/* inw_inward_dtl.status is authoritative: a stale working-status label / old transaction must not hide Client Review */
		$current_status='<label class="label label-success ams_label">Client Review</label>';
}
elseif($articles['user_working_status']=='<span class="label label-sm label-success">Client Review</span>' && $articles['department_id']=='0')
{
		
				$current_status='<label class="label label-success ams_label">Client Review</label>';	
			
}
elseif($articles['user_working_status']!='<span class="label label-sm label-success">Client Review</span>' && $articles['user_working_status']!='')
{
		
				$current_status=$articles['user_working_status']."".$aq_status;
			
}
else
			{
				$current_status='<label class="label label-success ams_label">'.$articles['status'].'</label>';	
			}

/* status from the latest inw_transactions row, not the stored label */
require_once __DIR__ . '/../includes/tx_status.php';
if(trim($articles['status'])!='Client Review') { $current_status = tx_status_label($articles['id'], $articles['stage'], $current_status, $articles['department_id']); }
			$instru='<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup('.$articles['id'].');">Click Here</a>';

			
		$dir_li = '';
		if (isset($dir_info[$articles['id']])) {
			$di = $dir_info[$articles['id']];
			$di_wd = str_replace(WF_DIR, TOOL_DIR, $di['work_dir']);
			/* the Local Explorer extension turns file:/// links into localexplorer:<path> only when the page loads, so rows added by the table's AJAX call get the final form directly */
			$di_src = 'localexplorer:' . str_replace('/', '\\', rtrim(str_replace('production', 'downloads', $di_wd) . $di['volume_folder'] . '/' . $di['pub_id'] . '/' . $di['stage'], '/'));
			$di_work = 'localexplorer:' . str_replace('/', '\\', rtrim($di_wd . $di['volume_folder'] . '/' . $di['pub_id'] . '/' . $di['stage'] . '/' . $di['dept_name'], '/'));
			$dir_li = '<li><a class="dropdown-item" href="' . htmlspecialchars($di_src) . '">Source</a></li>
                  <li><a class="dropdown-item" href="' . htmlspecialchars($di_work) . '">Work</a></li>';
		}
		$rec['aaData'][$k] = array(
			0 => '<div class="d-flex gap-3">
			<div class="dropdown">
                <div
                  style="border: 1px solid gray; background-color: #141414;color: #fff;padding: 7px;border-radius: 5px;d-flex;"
                  type="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  Actions
                </div>
                <ul
                  class="dropdown-menu" style="font-size: 0.945em;"
                  aria-labelledby="dropdownMenuButton1"
                >
                  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/sendinstruction/'.$articles['id'].'">Send Instructions</a></li>
                  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/jobcard/'.$articles['id'].'">Job Card</a></li>
				  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/article-checklist/'.$articles['id'].'">Checklist</a></li>

                  <li>
                    <a class="dropdown-item" href="'.$_COOKIE['set_url'].'/uploadfiles/'.$articles['id'].'">Upload multiple files</a>
                  </li>
                  '.$dir_li.'
                  <li>
                    <a class="dropdown-item" href="'.$_COOKIE['set_url'].'/edit-article/'.$articles['id'].'">Edit article</a>
                  </li>
                  <li>
                    <button class="dropdown-item" onclick="delete_article('.$articles['id'].');">Delete</button>
                  </li>
                </ul>
              </div>
			  </div>',
			1 => $articles['cn'],
			2 => $articles['j_code'],
			3 => $articles['pub_id'].$ppms. $gf.(isset($jstage_ids[$articles['id']]) ? ' <b style="color:#e8863a;">(JSTAGE)</b>' : ''),
			4 => $articles['stage'],
			5 => $recv_dt,
			6 => $due_dt,
			7 => $current_status,
			8 => $manuscript_count,
			9 => $instru,
		 10 =>$articles['id'],
		 11 =>$articles['highpriority'],
		 12 =>$due_highlight
		
		);
		$k++;
	}
}
}

// counts for the All / Query tabs, over the same filters
$qa = $auth->countallArticlelist($sSearch, $gBase);
$qq = $auth->countallArticlelist($sSearch, $gBase . ' and wd.status = "Query" ');
$rec['tabCounts'] = array('all' => (is_array($qa) && isset($qa[0]['count'])) ? (int)$qa[0]['count'] : 0, 'query' => (is_array($qq) && isset($qq[0]['count'])) ? (int)$qq[0]['count'] : 0);
echo json_encode($rec);
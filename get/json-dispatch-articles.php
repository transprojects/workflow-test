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

$arr = array(0 => 'cn', 1 => 'journal_title', 2 => 'pub_id', 3 => 'stage', 4 => 'highpriority', 5 => 'due_dt', 6 => 'status', 7 => '', 8 => '', 9 => '');
$sort_by = $arr[$col];
$sort_by_d = $arr[$col1];
$sort_type = $_REQUEST['sSortDir_0'];
$sort_type_d = $_REQUEST['sSortDir_1'];

if($_COOKIE['user_level'] != 1 && $_COOKIE['user_level'] != 13)
{


	if($_COOKIE['department_id'] == '1' || $_COOKIE['department_id'] == '2')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 
		and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
	}
	elseif($_COOKIE['department_id'] == '16')
	{
		//$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 ';
	}
	else if($_COOKIE['department_id'] == '9')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND (wd.fig_complete_status = 0 OR department_id = '.$_COOKIE['department_id'].') AND (wd.assign_department_id = '.$_COOKIE['department_id'].' OR fig > 0)';

	}
	else
	{
		$gSearch .= ' and wd.v_issue_id <= 0 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND  FIND_IN_SET(department_id,"'.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].'")'; 
	}
}
else
{
	$gSearch .= 'and wd.v_issue_id <= 0';
}

if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['stage']!='' || $_REQUEST['graphics_status']!='' || $_REQUEST['article_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!=''))
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

	if(isset($_REQUEST['from_dt']) && $_REQUEST['to_dt']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and (DATE_FORMAT(recv_dt,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(recv_dt,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'" OR DATE_FORMAT(received_date,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(received_date,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'")';


	if(isset($_REQUEST['article_id']) && $_REQUEST['article_id']!='')

            $gSearch.=' and wd.pub_id LIKE "%'.$_REQUEST['article_id'].'%" ';  


	

}
else
{
	//$gSearch .= '';
}


//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;


// ---- priority tabs: 1 = high priority, 2 = overdue, 3 = due today, 4 = everything else ----------------
// "due" is the date this table shows: FP / ISSCOR -> due_dt, everything else -> the revision due date.
// No single quotes below: $gSearch is passed through a single-quoted CALL argument.
$today_sql = date("Y-m-d");
$due_expr  = 'DATE(CASE WHEN wd.stage = "FP" THEN NULLIF(wd.due_dt,"0000-00-00") '
           . 'WHEN wd.stage REGEXP "^ISSCOR[0-9]+$" THEN NULLIF(wd.due_dt,"0000-00-00") '
           . 'ELSE NULLIF(r.due_date,"0000-00-00") END)';
$cat_expr  = 'CASE WHEN wd.highpriority = 1 THEN 1 WHEN ' . $due_expr . ' < "' . $today_sql . '" THEN 2 '
           . 'WHEN ' . $due_expr . ' = "' . $today_sql . '" THEN 3 ELSE 4 END';
$ptab = isset($_REQUEST['ptab']) ? $_REQUEST['ptab'] : 'all';
if (!in_array($ptab, array('all', 'high', 'overdue', 'today', 'others'), true)) { $ptab = 'all'; }
$gBase = $gSearch;                                   // filters only - used for the tab counts
$tab_no = array('high' => 1, 'overdue' => 2, 'today' => 3, 'others' => 4);
if ($ptab !== 'all') { $gSearch .= ' and (' . $cat_expr . ') = ' . $tab_no[$ptab] . ' '; }
// no column chosen by the user -> priority order: high, overdue, due today, others (then the earliest due date first).
// selectDispatchArticle() only accepts VARCHAR(50) for the sort columns, so in this mode the page is read with the same SELECT directly.
$priority_mode = ($sort_by === '' || $sort_by === null);
$ss  = $db->real_escape_string((string)$sSearch);
$from_sql = ' FROM `inw_inward_dtl` AS wd LEFT JOIN inw_revisions_dtl AS r ON (wd.`id` = r.`inw_id` AND r.r_id = (SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.`id` ORDER BY r_id DESC LIMIT 1)), '
          . 'adm_customer_master AS c, adm_journals AS j '
          . 'WHERE wd.cust_id = c.id AND wd.j_id = j.j_id AND wd.status IN ("Delivery","DELIVERY-ELX") '
          . 'AND (pub_id LIKE "%' . $ss . '%" OR article_title LIKE "%' . $ss . '%") ';

 if ($priority_mode) {
    $res_prof = array();
    $lq = $db->query('SELECT c.cust_name as cn,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.cust_id,wd.id,j.j_code,manuscript_count,wd.page_count,graphics,fig_complete_status,wd.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,wd.target_due_dt,wd.present_user_id,wd.file_status,wd.xperttrack_sync'
        . $from_sql . $gSearch
        . ' ORDER BY (' . $cat_expr . ') ASC, (' . $due_expr . ' IS NULL) ASC, ' . $due_expr . ' ASC, wd.id DESC'
        . ' LIMIT ' . (int)$start . ', ' . ((int)$length > 0 ? (int)$length : 10));
    while ($lq && ($lr = $lq->fetch_assoc())) { $res_prof[] = $lr; }
    if (!$res_prof) { $res_prof = ''; }          // same "nothing found" value the procedure path gives
 } else {
    $res_prof = $auth->showDispatchArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length,$gSearch);
 }
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
$qry = $auth->countDispatchArticlelist($sSearch,$gSearch);
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

//JSTAGE mark: articles whose workflow (inw_inward_dtl.wf_id) is the journal's JSTAGE Workflow (adm_journals.j_jstage_wf = adm_workflow_master.wf_name), same as the current work page
$jstage_ids = array();
if (isset($data) && is_array($data)) {
	$page_ids = array();
	foreach ($data as $d_row) { if (is_array($d_row) && isset($d_row['id'])) { $page_ids[] = (int)$d_row['id']; } }
	if ($page_ids) {
		try {
			$js_q = $db->query('SELECT wd.id FROM inw_inward_dtl AS wd JOIN adm_journals AS j ON j.j_id = wd.j_id JOIN adm_workflow_master AS jm ON jm.wf_id = wd.wf_id AND jm.wf_name = j.j_jstage_wf WHERE j.j_jstage_wf <> "" AND wd.id IN (' . implode(',', $page_ids) . ')');
			while ($js_q && ($js_r = $js_q->fetch_assoc())) { $jstage_ids[$js_r['id']] = true; }
		} catch (Throwable $e) { /* j_jstage_wf column missing (JSTAGE migration not run): no tag */ }
	}
}

$k=0;
if (isset($data) && is_array($data)) {
	foreach ($data as $articles) {		


$aid=$articles['id'];

		$stage_r=preg_replace("/REV([0-9]+)/","REV",$articles['stage']);
		$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$articles['stage']);
		$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$articles['stage']);
		
		 if($articles['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['recv_dt'])));
			$manuscript_count = $articles['manuscript_count'];
		}
		elseif($stage_r== "REV")
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['received_date'])));
			$manuscript_count = $articles['page_count'];
		}
		elseif($stage_f== "FIN")
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['received_date'])));
			$manuscript_count = $articles['page_count'];
		}
		elseif($stage_i=='ISSCOR')
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['created_dt'])));
			$manuscript_count = $articles['page_count'];
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['received_date'])));
			$manuscript_count = $articles['page_count'];
		}	
		$row_src = ($articles['stage'] == "FP" || preg_match("/^ISSCOR[0-9]+$/", $articles['stage'])) ? $articles['due_dt'] : $articles['due_date'];
		$row_ts  = ($row_src && strpos($row_src, "0000-00-00") !== 0) ? strtotime($row_src) : false;
		$row_due = $row_ts ? date("Y-m-d", $row_ts) : "";
		if ($articles['highpriority'] == 1)                   { $row_cat = 1; }
		elseif ($row_due !== "" && $row_due < date("Y-m-d")) { $row_cat = 2; }
		elseif ($row_due !== "" && $row_due == date("Y-m-d")) { $row_cat = 3; }
		else                                                  { $row_cat = 4; }
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		
		$status='<label class="label label-success ams_label">Delivery</label>';
		
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

$upload_button = ''; 
$hours = date("H");
$dayName = date("l");

if( ($dayName == 'Friday' || $dayName == 'Saturday' ||  $dayName == 'Sunday' || $dayName == 'Monday') && $articles['cn'] == 'PeerJ' && $articles['stage'] == "FP")
//if($articles['cn'] == 'PeerJ' && $articles['stage'] == "FP")
{
	if(($dayName == 'Friday' && $hours <= '23'))
    {
    	$upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="sendtoclient/'.$articles['id'].'"><i class="ace-icon fa fa-cloud-upload bigger-100"></i> Upload</a><a class="btn btn-warning btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="move_to_client_review.php?id='.$articles['id'].'"><i class="ace-icon fa fa-refresh bigger-100"></i> Move</a>'; 
    }
	elseif((($dayName == 'Friday' && $hours >='23') || ($dayName == 'Saturday' && $hours <= '02')))
    {
    	$upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="sendtoclient/'.$articles['id'].'"><i class="ace-icon fa fa-cloud-upload bigger-100"></i> Upload</a><a class="btn btn-warning btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="move_to_client_review.php?id='.$articles['id'].'"><i class="ace-icon fa fa-refresh bigger-100"></i> Move</a>'; 
    }
    else if($dayName == 'Monday' && $hours >= '11')
    {
    	$upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="sendtoclient/'.$articles['id'].'"><i class="ace-icon fa fa-cloud-upload bigger-100"></i> Upload</a><a class="btn btn-warning btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="move_to_client_review.php?id='.$articles['id'].'"><i class="ace-icon fa fa-refresh bigger-100"></i> Move</a>'; 
    } else {
    	$upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content">
		<i class="ace-icon fa fa-cloud-upload bigger-100"></i> Hold for Weekend</a><a class="btn btn-warning btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="move_to_client_review.php?id='.$articles['id'].'"><i class="ace-icon fa fa-refresh bigger-100"></i> Move</a>';
    }
}

elseif ($articles['cust_id'] != '42' && !($articles['cust_id'] == '66' && $articles['stage'] == 'FP')) {
    $upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="sendtoclient/'.$articles['id'].'">
        <i class="ace-icon fa fa-cloud-upload bigger-100"></i> Upload
    </a>
    <a class="btn btn-warning btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="move_to_client_review.php?id='.$articles['id'].'">
        <i class="ace-icon fa fa-refresh bigger-100"></i> Move
    </a>';
}
//// Commeting this code due to remove the hold 13-1-2026///
/*
if($articles['cn'] == 'PeerJ' && $articles['stage'] == "FP")
{
	$upload_button = '<a class="btn btn-purple btn-xs" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content">
		<i class="ace-icon fa fa-cloud-upload bigger-100"></i> On Hold</a>';
} */




			$instru='<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup('.$articles['id'].');">Click Here</a>';

			
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
                  <li>
                    <a class="dropdown-item" href="'.$_COOKIE['set_url'].'/uploadfiles/'.$articles['id'].'">Upload multiple files</a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="'.$_COOKIE['set_url'].'/edit-article/'.$articles['id'].'">Edit article</a>
                  </li>
                
                </ul>
              </div>
			  </div>',
			1 => $articles['cn'],
			2 => $articles['j_code'],
			3 => $articles['pub_id'].(trim($gf.$ppms) !== '' || isset($jstage_ids[$articles['id']]) ? '<div style="font-family:Consolas,monospace;font-size:11px;line-height:1.3;margin-top:2px;">'.trim($gf.' '.$ppms.(isset($jstage_ids[$articles['id']]) ? ' <b style="color:#e8863a;">(JSTAGE)</b>' : '')).'</div>' : ''),
			4 => $articles['stage'],
			5 => $recv_dt,
			6 => $due_dt,
			7 => $status,
			8 => $manuscript_count,
			9 => '
<div class="d-flex gap-3">
    <div class="hidden-sm hidden-xs action-buttons">
        ' . $upload_button . '
        <a class="btn btn-xs btn-danger"
           style="text-decoration:none; color:white; cursor:pointer; border:1px solid white; font-size:0.85em; width:fit-content"
           href="edit-article/' . $articles['id'] . '">
           <i class="ace-icon fa fa-undo bigger-120"></i> Undo
        </a>
    </div>
</div>',



		 10 =>$articles['id'],
		 11 =>$row_cat
		);
		$k++;
	}
}
}


$tabCounts = array('all' => 0, 'high' => 0, 'overdue' => 0, 'today' => 0, 'others' => 0);
$cq = $db->query('SELECT cat, COUNT(*) AS n FROM (SELECT (' . $cat_expr . ') AS cat' . $from_sql . $gBase . ') t GROUP BY cat');
$cat_key = array(1 => 'high', 2 => 'overdue', 3 => 'today', 4 => 'others');
while ($cq && ($cr = $cq->fetch_assoc())) {
    if (isset($cat_key[(int)$cr['cat']])) { $tabCounts[$cat_key[(int)$cr['cat']]] = (int)$cr['n']; $tabCounts['all'] += (int)$cr['n']; }
}
$rec['tabCounts'] = $tabCounts;
echo json_encode($rec);
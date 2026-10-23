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
session_start();
$arr = array(0 => '',1 => '', 2 => 'inw_type', 3 => 'pub_id', 4 => 'stage', 5 => 'created_dt', 6 => 'j_code', 7 => 'status', 8 => 'manuscript_count', 9 => '', 10 => 'highpriority');
$sort_by = $arr[$col];
$sort_by_d = $arr[$col1];
$sort_type = $_REQUEST['sSortDir_0'];
$sort_type_d = $_REQUEST['sSortDir_1'];

// Build $stage_condition based on $_REQUEST['stage']
$stage_condition = '';
if (!empty($_COOKIE['stage_access'])) {
    $ex_stage = explode(",", $_COOKIE['stage_access']);
    if (count($ex_stage) == 1) {
        $stage_condition = ' AND wd.stage LIKE "%' . $ex_stage[0] . '%"';
    } else {
        $parts = [];
        foreach ($ex_stage as $stg) {
            $parts[] = 'wd.stage LIKE "%' . $stg . '%"';
        }
        $stage_condition = ' AND (' . implode(' OR ', $parts) . ')';
    }
}
function buildStartBtn($aid, $tid, $dept, $fs = 0, $labelOverride = "") {
    $btnId = "startBtn_" . $aid;
    $label = $labelOverride ?: ($fs ? "Force Start" : "Start");

    return '
        <a id="'.$btnId.'"
           class="btn btn-xs btn-primary"
           style="font-size:0.945em"
           onclick="return check_work_status('.$aid.','.$tid.','.$dept.','.$fs.', \''.$btnId.'\');">
            <i class="ace-icon fa fa-tasks bigger-120"></i>&nbsp;'.$label.'
        </a>';
}


if(($_COOKIE['user_level'] != 1) &&  ($_COOKIE['user_level'] != 5) &&  ($_COOKIE['user_level'] != 14))
{


	if(($_COOKIE['department_id'] == '1' || $_COOKIE['department_id'] == '2') && ($_REQUEST['dept']==''))
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 and assigned_user_id='.$_COOKIE['user_id'].'  and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'"))
		AND department_id in ('.$_COOKIE['department_id'].')' . $stage_condition;
	}
	elseif($_COOKIE['department_id']!= '1' || $_COOKIE['department_id']!= '2' && (isset($_REQUEST['dept']) && $_REQUEST['dept']!=''))
	{
		$gSearch .= ' and wd.v_issue_id <= 0  and  (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')' . $stage_condition;
	}
	elseif($_COOKIE['department_id'] == '16')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND department_id in ('.$_COOKIE['department_id'].')' . $stage_condition;
	}
	else if($_COOKIE['department_id'] == '9')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND (wd.fig_complete_status = 0 OR department_id = '.$_COOKIE['department_id'].') AND (wd.assign_department_id = '.$_COOKIE['department_id'].')';

	}
	else
	{
		$gSearch .= ' and wd.v_issue_id <= 0 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'")) AND department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')' . $stage_condition;
	}
}
else
{
	$gSearch .= 'and wd.v_issue_id <= 0';
}

if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['stage']!='' || $_REQUEST['dept']!='' || $_REQUEST['graphics_status']!='' || $_REQUEST['article_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!='' || $_REQUEST['allocated']!=''))
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

if(isset($_REQUEST['allocated']) && $_REQUEST['allocated']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= 'and assigned_od_user_id="'.$_COOKIE['user_id'].'"';
	

}
else
{
// leading space: the admin branch above has already written "... <= 0", and "0and" is a syntax error
$gSearch .= ' and wd.v_issue_id <= 0 and test_article!=1 and (wd.cust_id = '.$_COOKIE['cust_id'].' OR FIND_IN_SET(wd.cust_id,"'.$_COOKIE['ocust_id'].'"))
and department_id in ('.$_COOKIE['department_id'].')';
}


// ---- priority tabs: 1 = high priority, 2 = overdue, 3 = due today, 4 = everything else ----------------
// "due" is the same date the table shows: FP -> target/due date, ISSCOR -> due_dt, otherwise the revision due date.
// No single quotes below: $gSearch is passed through a single-quoted CALL argument.
$today_sql = date("Y-m-d");
$due_expr  = 'DATE(CASE WHEN wd.stage = "FP" THEN COALESCE(NULLIF(wd.target_due_dt,"0000-00-00"), NULLIF(wd.due_dt,"0000-00-00")) '
           . 'WHEN wd.stage REGEXP "^ISSCOR[0-9]+$" THEN NULLIF(wd.due_dt,"0000-00-00") '
           . 'ELSE NULLIF(r.due_date,"0000-00-00") END)';
$cat_expr  = 'CASE WHEN wd.highpriority = 1 THEN 1 WHEN ' . $due_expr . ' < "' . $today_sql . '" THEN 2 '
           . 'WHEN ' . $due_expr . ' = "' . $today_sql . '" THEN 3 ELSE 4 END';
$ptab = isset($_REQUEST['ptab']) ? $_REQUEST['ptab'] : 'all';
if (!in_array($ptab, array('all', 'high', 'overdue', 'today', 'others', 'd1', 'd2', 'd3', 'd4', 'query'), true)) { $ptab = 'all'; }
$is_query_tab = ($ptab === 'query');   // articles whose status is Query (every other tab leaves them out, as before)
$gBase = $gSearch;                                   // filters only - used for the tab counts
// the tabs go by the due date only - the high priority flag no longer moves an article between Overdue / Due today; "High priority" lists the flagged ones
$tab_cond = array('high' => 'wd.highpriority = 1', 'overdue' => $due_expr . ' < "' . $today_sql . '"', 'today' => $due_expr . ' = "' . $today_sql . '"',
                  'others' => '(' . $due_expr . ' IS NULL OR ' . $due_expr . ' > "' . $today_sql . '")');
if (isset($tab_cond[$ptab])) { $gSearch .= ' and (' . $tab_cond[$ptab] . ') '; }
// due-in tabs: d1 = due tomorrow, d2 = in 2 days, d3 = in 3 days, d4 = in 4 days or more. They go by the due date alone, so a
// high priority article that is due tomorrow is also in "Due in 1 day" (the Overdue / Due today tabs above keep their original rule).
elseif ($ptab === 'd1' || $ptab === 'd2' || $ptab === 'd3' || $ptab === 'd4') {
    $gSearch .= ' and ' . $due_expr . ' IS NOT NULL and DATEDIFF(' . $due_expr . ', "' . $today_sql . '") ' . ($ptab === 'd4' ? '>= 4' : '= ' . (int)substr($ptab, 1)) . ' ';
}
// no column chosen by the user -> earliest due date first (no due date last); the high priority flag does not change the order.
// selectArticle() only accepts VARCHAR(50) for the sort column, so in this mode the page is read with the same SELECT directly.
// Sorting follows the table's REAL column index (view_articles_new.php): 1 File Type, 2 File Name, 3 Stage, 4 Received Date, 5 Due Date, 6 Status, 8 MS Count.
// (The old $arr above is shifted by one column - Received Date used to sort by stage - and the stored procedure cannot take the date expressions.)
$col_i = ($col === '' || $col === null) ? -1 : (int)$col;
$ss  = $db->real_escape_string((string)$sSearch);
$from_tpl = ' FROM `inw_inward_dtl` AS wd LEFT JOIN inw_revisions_dtl AS r ON (wd.`id` = r.`inw_id` AND r.r_id = (SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.`id` ORDER BY r_id DESC LIMIT 1)), '
          . 'adm_customer_master AS c, adm_journals AS j '
          . 'WHERE wd.cust_id = c.id AND wd.j_id = j.j_id AND wd.status %STATUS% AND is_conversion != "yes" '
          . 'AND (pub_id LIKE "%' . $ss . '%" OR article_title LIKE "%' . $ss . '%" OR c.cust_name LIKE "%' . $ss . '%" OR j.j_code LIKE "%' . $ss . '%" OR wd.stage LIKE "%' . $ss . '%" '
          . 'OR wd.status LIKE "%' . $ss . '%" OR wd.user_working_status LIKE "%' . $ss . '%" OR wd.graphic_working_status LIKE "%' . $ss . '%") ';

// JSTAGE mark: 1 when the article's workflow (wd.wf_id) is the journal's "JSTAGE Workflow" (adm_journals.j_jstage_wf = adm_workflow_master.wf_name).
// j_jstage_wf only exists after the JSTAGE migration, so the column is checked first.
$js_select = '';
$js_col = $db->query("SHOW COLUMNS FROM `adm_journals` LIKE 'j_jstage_wf'");
if ($js_col && $js_col->num_rows > 0) {
    $js_select = ', (j.j_jstage_wf IS NOT NULL AND j.j_jstage_wf <> "" AND EXISTS (SELECT 1 FROM adm_workflow_master AS jm WHERE jm.wf_id = wd.wf_id AND jm.wf_name = j.j_jstage_wf)) AS is_jstage_wf';
}

$from_sql   = str_replace('%STATUS%', 'NOT IN ("Delivery","Client Review","Completed","Query","Hold","With Drawn")', $from_tpl);   // what every tab counts
$from_sql_q = str_replace('%STATUS%', '= "Query"', $from_tpl);                                                                              // the Query tab
$from_list  = $is_query_tab ? $from_sql_q : $from_sql;                                                                                          // what the table lists
// ORDER BY for the direct query. Every header sorts by what it shows; ties fall back to the newest article.
$sdir = (strtolower((string)$sort_type) === 'asc') ? 'ASC' : 'DESC';
$recv_expr = 'CASE WHEN wd.stage REGEXP "^(REV|FIN)[0-9]*$" THEN COALESCE(TIMESTAMP(r.received_date, TIME(r.created_on)), r.created_on, wd.created_dt) ELSE wd.created_dt END';   // same date the Received Date column shows
$ms_expr   = 'CAST(CASE WHEN wd.stage = "FP" THEN wd.manuscript_count ELSE wd.page_count END AS UNSIGNED)';                       // same number the MS Count column shows
$by = array(1 => 'wd.inw_type', 2 => 'wd.pub_id', 3 => 'wd.stage', 4 => $recv_expr, 6 => 'wd.status', 8 => $ms_expr);
if ($col_i !== 5 && isset($by[$col_i])) {
    $order_sql = ' ORDER BY ' . $by[$col_i] . ' ' . $sdir . ', wd.id DESC';
} else {   // default (no column chosen) and the Due Date header: due date, no due date always last
    $order_sql = ' ORDER BY (' . $due_expr . ' IS NULL) ASC, ' . $due_expr . ' ' . ($col_i === 5 ? $sdir : 'ASC') . ', wd.id DESC';
}

$_SESSION['varname'] = $gSearch;

//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;

// $res_prof = $auth->showArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length,$gSearch);
 if (true) {   // always the direct query: it can sort by the date expressions above, which the stored procedure (VARCHAR(50) sort argument) cannot
    $res_prof = array();
    $lq = $db->query('SELECT c.cust_name as cn,c.id as custid,journal_title,pub_id,recv_dt,due_dt,wd.inw_type,highpriority,wd.created_dt,file_flow,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,wd.stage,wd.page_count,r.received_date,r.due_date,r.correction_pages,r.revision_count, r.created_on,wd.target_due_dt,wd.department_id,wd.present_user_id,wd.file_status,wd.issueorgroup_folder,wd.fig,wd.tab,wd.aq_status,wd.user_working_status,wd.last_transaction_id,wd.graphic_working_status,wd.assigned_od_user_id,wd.od_due_date,wd.xperttrack_sync' . $js_select
        . $from_list . $_SESSION['varname']
        . $order_sql
        . ' LIMIT ' . (int)$start . ', ' . ((int)$length > 0 ? (int)$length : 10));
    while ($lq && ($lr = $lq->fetch_assoc())) { $res_prof[] = $lr; }
    if (!$res_prof) { $res_prof = ''; }          // same "nothing found" value the procedure path gives
 } else {
    $res_prof = $auth->showArticlelist($sSearch, $sort_by, $sort_type, $start, $length,$_SESSION['varname']);
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
$qry = $auth->countArticlelist($sSearch,$_SESSION['varname']);
foreach($qry as $row)
{	
	$iTotal = $row['count'];
}
if ($is_query_tab) {   // the stored count only knows the non-Query list
	$cq0 = $db->query('SELECT COUNT(*) AS n ' . $from_list . $_SESSION['varname']);
	$iTotal = ($cq0 && ($cr0 = $cq0->fetch_assoc())) ? (int)$cr0['n'] : 0;
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

	$instruction_num=mysqli_num_rows(mysqli_query($db,"select * from inw_dept_comments where inw_id = ".$articles['id']." and DATE_FORMAT(created_on, '%Y-%m-%d') = '".date('Y-m-d')."'"));				

		//$trans_status = $auth->showArticleTransactionlimitr($articles['id'],$articles['stage']);
//print_r($trans_status);
$aid=$articles['id'];
if ($articles['fig_complete_status'] == 1) {
    $gs = '<span class="label label-success">Yes' . 
          (!empty($articles['fig']) ? ' (' . $articles['fig'] . ')' : '(0)') . 
          '</span>';
} else { 
    $gs = '<span class="label label-default">No</span>';
}
		$stage_r=preg_replace("/REV([0-9]+)/","REV",$articles['stage']);
		$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$articles['stage']);
	$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$articles['stage']);
		
	if ($articles['stage'] == "FP") {

    // Determine due date safely
    if (!empty($articles['target_due_dt'])) {
        $due_dt = date('d-m-Y', strtotime($articles['target_due_dt']));
    } elseif (!empty($articles['due_dt'])) {
        $due_dt = date('d-m-Y', strtotime($articles['due_dt']));
    } else {
        $due_dt = "No target date assigned";
    }

    // Received date (with fallback check)
    $recv_dt = !empty($articles['created_dt'])
        ? date('d-m-Y H:i:s', strtotime($articles['created_dt']))
        : '';

    // Manuscript count safe default
    $manuscript_count = !empty($articles['manuscript_count'])
        ? $articles['manuscript_count']
        : 0;

} elseif ($stage_r == "REV") {

    $due_dt = !empty($articles['due_date'])
        ? date('d-m-Y', strtotime($articles['due_date']))
        : '';

    // revision received_date (what the inflow report counts)
    $recv_dt = !empty($articles['received_date'])
        ? date('d-m-Y', strtotime($articles['received_date']))
        : (!empty($articles['created_on']) ? date('d-m-Y', strtotime($articles['created_on'])) : '');

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;

}elseif ($stage_f == "FIN") {

    $due_dt = !empty($articles['due_date'])
        ? date('d-m-Y', strtotime($articles['due_date']))
        : '';

    // revision received_date (what the inflow report counts)
    $recv_dt = !empty($articles['received_date'])
        ? date('d-m-Y', strtotime($articles['received_date']))
        : (!empty($articles['created_on']) ? date('d-m-Y', strtotime($articles['created_on'])) : '');

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;

} elseif ($stage_i == 'ISSCOR') {

    $due_dt = !empty($articles['due_dt'])
        ? date('d-m-Y', strtotime($articles['due_dt']))
        : '';

    $recv_dt = !empty($articles['created_dt'])
        ? date('d-m-Y H:i:s', strtotime($articles['created_dt']))
        : '';

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;

} else {

    $due_dt = !empty($articles['due_date'])
        ? date('d-m-Y', strtotime($articles['due_date']))
        : '';

    $recv_dt = !empty($articles['created_dt'])
        ? date('d-m-Y H:i:s', strtotime($articles['created_dt']))
        : '';

    $manuscript_count = !empty($articles['page_count'])
        ? $articles['page_count']
        : 0;
}

		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		
		
		if($articles['highpriority']==1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($articles['status'] == 'Query') || ($articles['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $Due_dateTimestamp2)
		{
			//$tr_class = 'alert alert-danger';
			$due_highlight = '1';
		}
		elseif($current_dateTimestamp1 <= $Due_dateTimestamp2)
		{
			//$tr_class = 'alert alert-danger';
			$due_highlight = '0';
		}
		
		$row_due_ts = strtotime($due_dt);
		$row_due    = $row_due_ts ? date("Y-m-d", $row_due_ts) : "";
		if ($articles['highpriority'] == 1)                   { $row_cat = 1; }
		elseif ($row_due !== "" && $row_due < date("Y-m-d")) { $row_cat = 2; }
		elseif ($row_due !== "" && $row_due == date("Y-m-d")) { $row_cat = 3; }
		else                                                  { $row_cat = 4; }

		$xperttrack_sync=$articles['xperttrack_sync'];
		if ($articles['custid'] == "82" ) {
		$ppms = " <b style='color:blue;'>(SAGA)</b>";
		} 
		elseif ($articles['xperttrack_sync'] == "1") {
			$ppms = " <b style='color:red;'>(PPMS)</b>";
		} 
		else {
			$ppms = "";
		}
		if($articles['issueorgroup_folder']!="")
		{
			$gf=' <b style="color:red;">('.ltrim($articles['issueorgroup_folder'],"/").')</b>';
		}
		else
		{
			$gf="";
		}
		//articles running on the journal's JSTAGE workflow are marked in brackets next to the article id
		$jsm = !empty($articles['is_jstage_wf']) ? ' <b style="color:#e8863a;">(JSTAGE)</b>' : '';
	


/* ==============================================
   START BUTTON LOGIC (FULLY OPTIMIZED)
   ============================================== */

$start = "";

if ($articles['approved_by'] > 0) {

    $aid  = $articles['id'];
    $tid  = $articles['last_transaction_id'] ?: 0;
    $dept = $_COOKIE['department_id'];
    $uid  = $_COOKIE['user_id'];

    /* -----------------------------------------
       GRAPHICS DEPARTMENT (ID = 9)
       ----------------------------------------- */
    if ($dept == 9) {

        if ($tid > 0) {
            $start = '
                <a id="startBtn_'.$aid.'" class="btn btn-xs btn-primary"
                   style="font-size:0.945em"
                   onclick="return check_graphics_work_status('.$aid.','.$tid.','.$dept.');">
                    <i class="ace-icon fa fa-tasks bigger-120"></i>&nbsp;Start
                </a>';
        } else {
            $start = '
                <a id="startBtn_'.$aid.'" class="btn btn-xs btn-primary"
                   style="font-size:0.945em"
                   onclick="return check_graphics_work_status('.$aid.',0,'.$dept.');">
                    <i class="ace-icon fa fa-tasks bigger-120"></i>&nbsp;Start
                </a>';
        }

    } 
    /* -----------------------------------------
       NON-GRAPHICS DEPARTMENTS
       ----------------------------------------- */
    else {

        // If same user already working -> Resume
        if ($uid == $articles['present_user_id'] && $tid > 0) {

            $start = '
                <a id="startBtn_'.$aid.'"
                   target="_blank"
                   href="'.$_COOKIE['set_url'].'/startworknew/'.$aid.'/'.$tid.'"
                   class="btn btn-xs btn-primary"
                   style="font-size:0.945em">
                    <i class="ace-icon fa fa-tasks bigger-120"></i>&nbsp;Resume
                </a>';

        }

        // Dept 10 or 14 → FORCE START logic
        elseif (($dept == 10 || $dept == 14)) {

            if ($uid == $articles['assigned_od_user_id']) {
                // Normal start
                $start = buildStartBtn($aid, $tid, $dept, 0, "Start");
            } 
            elseif ($uid != $articles['present_user_id']) {
                // Force Start
                $start = buildStartBtn($aid, 0, $dept, 1, "Force Start");
            } 
            else {
                // Default normal start
                $start = buildStartBtn($aid, $tid, $dept, 0, "Start");
            }

        }

        // Everyone else → Standard Start
        else {

            $start = buildStartBtn($aid, $tid, $dept, 0, "Start");

        }
    }
}

if(!empty($articles['aq_status'])){  $aq_status='|<span class="label label-sm label bg-secondary text-white">'.$articles['aq_status'].'</span>'; }else{ $aq_status=''; }
	if($articles['user_working_status']!='' && $articles['user_working_status']!='<span class="label label-sm label-success">Client Review</span>' && $articles['user_working_status']!='<span class="label label-sm label-success">Delivery</span>')
{
		
				$current_status=$articles['user_working_status']."".$aq_status;
			
}
elseif($articles['user_working_status']=='<span class="label label-sm label-success">Client Review</span>')
{
		
				$current_status='<label class="label label-success ams_label">'.$articles['status'].'</label>';	
			
}
elseif($articles['user_working_status']=='<span class="label label-sm label-success">Delivery</span>')
{
		
				$current_status='<label class="label label-success ams_label">'.$articles['status'].'</label>';	
			
}

else
			{
				$current_status='<label class="label label-success ams_label">'.$articles['status'].'</label>';	
			}
/* status from the latest inw_transactions row, not the stored label */
require_once __DIR__ . '/../includes/tx_status.php';
$current_status = tx_status_label($articles['id'], $articles['stage'], $current_status, $articles['department_id']);
if($instruction_num>0)
{
	$inst_class="blink";
	$inst_style="; animation: colorChange 1s infinite alternate;";
	//$inst_class="";
	//$inst_style="";
}
else
{
	$inst_class="";
	$inst_style="";
}

if($articles['cn']!='DEV-JRNL')
{
	$stage=$articles['stage'];
}
else
{
	$stage=$articles['file_flow'];
}

			$instru='<a class="btn btn-sm '.$inst_class.'" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content '.$inst_style.'" data-bs-toggle="modal" data-bs-target="#exampleModal" 
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
                  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/sendinstruction/'.$articles['id'].'">Add Instruction</a></li>
                  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/jobcard/'.$articles['id'].'">Job Card</a></li>
                  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/sendquery/'.$articles['id'].'">Send Query</a></li>
				  <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exampleModal2" 
			onclick="return transactionspopup('.$articles['id'].');">Article Transactions</a></li>
				  <li><a class="dropdown-item" href="'.$_COOKIE['set_url'].'/article-checklist/'.$articles['id'].'">Checklist</a></li>
                </ul>
              </div>
			  </div>',
			2 => $articles['inw_type'],
			3 => $articles['pub_id'].(trim($gf.$ppms.$jsm) !== '' ? '<div style="font-family:Consolas,monospace;font-size:11px;line-height:1.3;margin-top:2px;">'.trim($gf.$ppms.$jsm).'</div>' : ''),
			4 => $stage,
			5 => $recv_dt,
			6 => $due_dt,
			//6 => '<label class="label label-success ams_label">'.$trans_status[0]['full_name'].'</label> <label class="label label-success ams_label">'.$trans_status[0]['current_status'].'</label>',
			7 => $current_status,
			// 8 => $due_dt,
			8=> $manuscript_count,
			1 => $gs,
			9 => $instru,

			// 9 => '<--<div class="hidden-sm hidden-xs action-buttons">'.$start.'
			// <a title="Send instructions to other Departments" class="btn btn-primary btn-xs" href="'.$_COOKIE['set_url'].'/sendinstruction/'.$articles['id'].'">
			// <i class="ace-icon fa fa-comments icon-only"></i>
			// </a>
			// <!--<a title="Send queries to Account manager" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/sendmailclient/'.$articles['id'].'">
			// <i class="ace-icon fa fa-envelope"></i>
			// </a>-->
			// <a title="Job Card" href="'.$_COOKIE['set_url'].'/jobcard/'.$articles['id'].'" target="_blank" class="btn btn-xs btn-info">
			// <i class="ace-icon fa fa-clock-o bigger-120"></i>
			// </a>  
			// <a title="Upload multiple files" class="btn btn-xs btn-pink" href="'.$_COOKIE['set_url'].'/uploadfiles/'.$articles['id'].'">
			//  <i class="ace-icon fa  fa-cloud-upload bigger-130"></i>
			//   </a>
		  	// <a title="Edit Article" class="btn btn-xs btn-warning" href="'.$_COOKIE['set_url'].'/edit-article/'.$articles['id'].'">
		   	// <i class="ace-icon fa fa-pencil bigger-120"></i>
		    // </a> <button data-delete="'.$articles['id'].'" class="btn btn-primary demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
			// </div>-->
			10 =>'<div class="d-flex gap-3">
			<div class="hidden-sm hidden-xs action-buttons">'.$start.'</div>
			  </div>',


		 11 =>$articles['id'],
		 12 =>$articles['highpriority'],
		 13 =>$due_highlight,
		 14 =>$row_cat
			//7 => $payment['status'] == '0' ? 'Success' : 'Failed',
			//6 => $articles['id']
		);
		$k++;
	}
}
}


$tabCounts = array('all' => 0, 'high' => 0, 'overdue' => 0, 'today' => 0, 'others' => 0);
// by due date only (an article can be both High priority and Overdue)
$cq = $db->query('SELECT COUNT(*) AS n, SUM(wd.highpriority = 1) AS hi, SUM(' . $due_expr . ' < "' . $today_sql . '") AS od, SUM(' . $due_expr . ' = "' . $today_sql . '") AS td' . $from_sql . $gBase);
if ($cq && ($cr = $cq->fetch_assoc())) { $tabCounts['all'] = (int)$cr['n']; $tabCounts['high'] = (int)$cr['hi']; $tabCounts['overdue'] = (int)$cr['od']; $tabCounts['today'] = (int)$cr['td']; }
// due-in tabs (1, 2, 3 and 4+ days from today) - counted over the same filters, whatever the priority flag says
$tabCounts['d1'] = 0; $tabCounts['d2'] = 0; $tabCounts['d3'] = 0; $tabCounts['d4'] = 0;
$dd = 'DATEDIFF(' . $due_expr . ', "' . $today_sql . '")';
$dq = $db->query('SELECT b, COUNT(*) AS n FROM (SELECT (CASE WHEN ' . $due_expr . ' IS NULL THEN 0 WHEN ' . $dd . ' >= 4 THEN 4 WHEN ' . $dd . ' BETWEEN 1 AND 3 THEN ' . $dd . ' ELSE 0 END) AS b' . $from_sql . $gBase . ') t WHERE b > 0 GROUP BY b');
while ($dq && ($dr = $dq->fetch_assoc())) { $tabCounts['d' . (int)$dr['b']] = (int)$dr['n']; }
$tabCounts['query'] = 0;
$qq = $db->query('SELECT COUNT(*) AS n ' . $from_sql_q . $gBase);
if ($qq && ($qr = $qq->fetch_assoc())) { $tabCounts['query'] = (int)$qr['n']; }
$rec['tabCounts'] = $tabCounts;
echo json_encode($rec);
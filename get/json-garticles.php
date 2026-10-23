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

$arr = array(0 => '',1 => 'cn', 2 => 'j_code', 3 => 'pub_id', 4 => 'stage', 5 => 'recv_dt', 6 => 'j_code', 7 => 'status', 8 => '', 9 => '', 10 => 'highpriority', 11 => '', 12 => '', 13 => '', 14 => '');
$sort_by = $arr[$col];
$sort_by_d = $arr[$col1];
$sort_type = $_REQUEST['sSortDir_0'];
$sort_type_d = $_REQUEST['sSortDir_1'];

if($_COOKIE['user_level'] != 1)
{


	if($_COOKIE['department_id'] == '1' || $_COOKIE['department_id'] == '2')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 
		and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
	}
	elseif($_COOKIE['department_id'] == '16')
	{
		$gSearch .= 'and wd.v_issue_id <= 0 and test_article!=1 and department_id in ('.$_COOKIE['department_id'].','.$_COOKIE['odepartment_id'].')';
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
// no filter chosen: the list loads anyway (it used to stay empty until Get Result)


// ---- due-date tabs, same as Current Work: All, High priority, Overdue, Due today, Due in 1 / 2 / 3 / 4+ days ----
$today_sql = date("Y-m-d");
$due_expr  = 'DATE(CASE WHEN wd.stage = "FP" THEN COALESCE(NULLIF(wd.target_due_dt,"0000-00-00"), NULLIF(wd.due_dt,"0000-00-00")) '
           . 'WHEN wd.stage REGEXP "^ISSCOR[0-9]+$" THEN NULLIF(wd.due_dt,"0000-00-00") '
           . 'ELSE NULLIF(r.due_date,"0000-00-00") END)';
$ptab = isset($_REQUEST['ptab']) ? $_REQUEST['ptab'] : 'all';
if (!in_array($ptab, array('all', 'high', 'overdue', 'today', 'd1', 'd2', 'd3', 'd4'), true)) { $ptab = 'all'; }
$gBase = $gSearch;                                   // filters only - used for the tab counts
if ($ptab === 'high') { $gSearch .= ' and wd.highpriority = 1 '; }
elseif ($ptab === 'overdue') { $gSearch .= ' and ' . $due_expr . ' < "' . $today_sql . '" '; }
elseif ($ptab === 'today') { $gSearch .= ' and ' . $due_expr . ' = "' . $today_sql . '" '; }
elseif ($ptab !== 'all') { $gSearch .= ' and ' . $due_expr . ' IS NOT NULL and DATEDIFF(' . $due_expr . ', "' . $today_sql . '") ' . ($ptab === 'd4' ? '>= 4' : '= ' . (int)substr($ptab, 1)) . ' '; }

// the list: the same rows the stored procedure gave (active articles), read directly so it can be ordered by the due date
$ss = $db->real_escape_string((string)$sSearch);
$from_sql = ' FROM `inw_inward_dtl` AS wd LEFT JOIN inw_revisions_dtl AS r ON (wd.`id` = r.`inw_id` AND r.r_id = (SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.`id` ORDER BY r_id DESC LIMIT 1)), '
          . 'adm_customer_master AS c, adm_journals AS j '
          . 'WHERE wd.cust_id = c.id AND wd.j_id = j.j_id AND wd.status NOT IN ("Delivery","Client Review","Completed","Query","Hold","With Drawn") AND is_conversion != "yes" '
          . 'AND (pub_id LIKE "%' . $ss . '%" OR article_title LIKE "%' . $ss . '%" OR c.cust_name LIKE "%' . $ss . '%" OR j.j_code LIKE "%' . $ss . '%" OR wd.stage LIKE "%' . $ss . '%" '
          . 'OR wd.status LIKE "%' . $ss . '%" OR wd.user_working_status LIKE "%' . $ss . '%" OR wd.graphic_working_status LIKE "%' . $ss . '%") ';
// default = earliest due date first (no due date last); the high priority flag does not change the order. Headers sort by what they show.
$recv_expr = 'CASE WHEN wd.stage = "FP" THEN wd.recv_dt WHEN wd.stage REGEXP "^ISSCOR[0-9]+$" THEN wd.created_dt ELSE r.received_date END';
$col_i = ($col === '' || $col === null) ? -1 : (int)$col;
$sdir  = (strtolower((string)$sort_type) === 'desc') ? 'DESC' : 'ASC';
$by = array(1 => 'wd.pub_id', 2 => $recv_expr, 4 => 'wd.fig', 5 => 'wd.stage', 6 => 'wd.status', 7 => 'wd.fig_complete_status');
if ($col_i !== 3 && isset($by[$col_i])) { $order_sql = ' ORDER BY ' . $by[$col_i] . ' ' . $sdir . ', wd.id DESC'; }
else { $order_sql = ' ORDER BY (' . $due_expr . ' IS NULL) ASC, ' . $due_expr . ' ' . ($col_i === 3 ? $sdir : 'ASC') . ', wd.id DESC'; }

$res_prof = array();
$lq = $db->query('SELECT c.cust_name as cn,c.id as custid,journal_title,pub_id,recv_dt,due_dt,wd.inw_type,highpriority,wd.created_dt,file_flow,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,wd.stage,wd.page_count,r.received_date,r.due_date,r.correction_pages,r.revision_count, r.created_on,wd.target_due_dt,wd.department_id,wd.present_user_id,wd.file_status,wd.issueorgroup_folder,wd.fig,wd.tab,wd.aq_status,wd.user_working_status,wd.last_transaction_id,wd.graphic_working_status,wd.assigned_od_user_id,wd.od_due_date,wd.xperttrack_sync'
    . $from_sql . $gSearch . $order_sql . ' LIMIT ' . (int)$start . ', ' . ((int)$length > 0 ? (int)$length : 10));
while ($lq && ($lr = $lq->fetch_assoc())) { $res_prof[] = $lr; }
if (!$res_prof) { $res_prof = ''; }
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

$iTotal = 0;
$cq0 = $db->query('SELECT COUNT(*) AS n ' . $from_sql . $gSearch);
if ($cq0 && ($cr0 = $cq0->fetch_assoc())) { $iTotal = (int)$cr0['n']; }
//echo $iTotal;

$rec = array(
	'iTotalRecords' => $iTotal,
	'iTotalDisplayRecords' => $iTotal,
	'aaData' => array()
);

$k=0;
if (isset($data) && is_array($data)) {
	foreach ($data as $articles) {		


		//$trans_status = $auth->showArticleTransaction($articles['id'],$articles['stage']);
//print_r($trans_status);
$aid=$articles['id'];

		$stage_r=preg_replace("/REV([0-9]+)/","REV",$articles['stage']);
	$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$articles['stage']);
		
		 if($articles['stage'] == "FP")
		{
			$gf_td = (!empty($articles['target_due_dt']) && strpos($articles['target_due_dt'], '0000') !== 0 && strtotime($articles['target_due_dt']) > 86400) ? $articles['target_due_dt'] : $articles['due_dt'];
			$due_dt = (!empty($gf_td) && strpos($gf_td, '0000') !== 0 && strtotime($gf_td) > 86400) ? date('d-m-Y', strtotime($gf_td)) : '';
			$recv_dt = (date('d-m-Y',strtotime($articles['recv_dt'])));
			$manuscript_count = $articles['manuscript_count'];
		}
		elseif($stage_r== "REV")
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['received_date'])));
			$manuscript_count = $articles['correction_pages'];
		}
		elseif($stage_i=='ISSCOR')
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['created_dt'])));
			$manuscript_count = $articles['correction_pages'];
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($articles['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($articles['received_date'])));
			$manuscript_count = $articles['correction_pages'];
		}	
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt);
			$due_highlight = ($due_dt !== '' && $current_dateTimestamp1 > $Due_dateTimestamp2) ? '1' : '0';   // overdue stays marked for every article, flagged or not 
		
		
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
		
		if($articles['fig_complete_status']==1){ $fig_complete_status='Yes';}else{ $fig_complete_status='No';}
		
				
		if($articles['user_working_status']!='')
		{
		// Split the labels string by the pipe character (|)
$labelParts = explode('|', $articles['user_working_status']);

// Extract the second and fourth parts (Rabin K and PAG)
$name = trim(strip_tags($labelParts[3])); // Trim to remove extra spaces and strip_tags to remove HTML markup
$status = trim(strip_tags($labelParts[2]));
$otherteam_status=$articles['user_working_status'];

// Output the extracted parts
		}
		else
		{
		$otherteam_status='';
		}
		
		

		
if($articles['approved_by']>0)
{

		
		if($_COOKIE['department_id'] != 9){

			if($articles['last_transaction_id']!='')
			{
				
				$start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" onClick="return check_work_status('.$articles['id'].','.$articles['last_transaction_id'].','.$_COOKIE['department_id'].');">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
			
			}
			else
			{
				$start='<a title="Start work" style="font-size: 0.945em"  class="btn btn-xs btn-primary" onClick="return check_work_status('.$articles['id'].',0,'.$_COOKIE['department_id'].');">
				<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';	
			}
	}
	else
	{
			if($articles['last_transaction_id']!='')
			{
				
				$start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" onClick="return check_graphics_work_status('.$articles['id'].','.$articles['last_transaction_id'].','.$_COOKIE['department_id'].');">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
			
			}
			else
			{
				$start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" onClick="return check_graphics_work_status('.$articles['id'].',0,'.$_COOKIE['department_id'].');">
				<i class="ace-icon fa fa-tasks bigger-120"></i>Start</a>';	
			}
	}
}
else
{
	//$start='<a title="Start work"  class="btn btn-xs btn-primary"><i class="ace-icon fa fa-tasks bigger-120"></i>Not</a>';
	$start='';
}
				$current_qstatus= 'Graphics'; 

			if($articles['last_transaction_id']=='' && $articles['graphic_working_status']!="")
{
		
				if($articles['graphic_working_status']!="")
				{
				$current_status=$articles['graphic_working_status'];
				}
				else
				{
				$current_status='<label class="label label-success ams_label">'.$current_qstatus.'</label>'.$aq_status.'';	
				}

			

		
		
			
}
else if($articles['last_transaction_id']!='' && $articles['graphic_working_status']!="")
{
					
				$current_status=$articles['graphic_working_status'];
	
}
else
			{
				$current_status='<label class="label label-success ams_label">'.$current_qstatus.'</label>'.$aq_status.'';	
			}

			$instru='<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup('.$articles['id'].');">Click Here</a>';

			
					$gf_h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
			$gf_folder = ltrim((string)$articles['issueorgroup_folder'], "/");
			$gf_file = '<div class="gf-file" title="' . $gf_h($articles['pub_id'] . ' - ' . $articles['cn'] . ' - ' . $articles['j_code'] . ($gf_folder !== '' ? ' (' . $gf_folder . ')' : '')) . '"><b>' . $gf_h($articles['pub_id']) . '</b><small>' . $gf_h($articles['cn']) . ' &middot; ' . $gf_h($articles['j_code']) . ($gf_folder !== '' ? ' (' . $gf_h($gf_folder) . ')' : '') . '</small></div>';
			/* Fig / Table in one cell: a chip for each that has any - amber while the graphics are pending, green once they are done */
			$gf_fg = (int)$articles['fig']; $gf_tb = (int)$articles['tab'];
			$gf_cls = ((int)$articles['fig_complete_status'] === 1) ? 'done' : 'pend';
			$fig_tab = '';
			if ($gf_fg > 0) { $fig_tab .= '<span class="gf-chip ' . $gf_cls . '" title="Figures">Fig ' . $gf_fg . '</span>'; }
			if ($gf_tb > 0) { $fig_tab .= '<span class="gf-chip ' . $gf_cls . '" title="Tables">Table ' . $gf_tb . '</span>'; }
			if ($fig_tab === '') { $fig_tab = '<span class="gf-none">No fig / table</span>'; }
			/* Status: this department's status, and under it the other team's status (department / user / state) - same chips as the other pages */
			$gf_chips = function ($txt, $color) use ($gf_h) {
				$p = array_values(array_filter(array_map('trim', explode('|', trim(strip_tags((string)$txt)))), function ($x) { return $x !== ''; }));
				if (!$p) { return ''; }
				$h = '<span class="af-status af-dept" style="--c:' . $color . '">' . $gf_h(array_shift($p)) . '</span>';
				foreach ($p as $i => $x) { $h .= ' <span class="af-sub ' . ($i === 0 ? 'af-user' : 'af-state') . '">' . $gf_h($x) . '</span>'; }
				return $h;
			};
			$gf_status = '<span class="af-stat-wrap">' . $gf_chips($current_status, '#1b6ca8') . '</span>';
			if ($articles['user_working_status'] != '') {
				$gf_o = $gf_chips($articles['user_working_status'], '#7a4fb5');
				if ($gf_o !== '') { $gf_status .= '<div class="gf-oth"><i>Other team</i>' . $gf_o . '</div>'; }
			}
			$gf_yn = '<span class="gf-yn ' . ($fig_complete_status == 'Yes' ? 'yes' : 'no') . '">' . $fig_complete_status . '</span>';

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
                  <li>
                    <button class="dropdown-item" href="#">Delete</button>
                  </li>
                </ul>
              </div>
			  </div>',
			1 => $gf_file,
			2 => $recv_dt,
			3 => $due_dt,
			4 => $fig_tab,
			5 => $articles['stage'],
			6 => $gf_status,
			7 => $gf_yn,
			8 => $instru,
			9 => '<div class="d-flex gap-3"><div class="hidden-sm hidden-xs action-buttons">'.$start.'</div></div>',
			10 => $articles['id'],
			11 => $articles['highpriority'],
			12 => $due_highlight
		);
		$k++;
	}
}
}

$tabCounts = array('all' => 0, 'high' => 0, 'overdue' => 0, 'today' => 0, 'd1' => 0, 'd2' => 0, 'd3' => 0, 'd4' => 0);
$cq = $db->query('SELECT COUNT(*) AS n, SUM(wd.highpriority = 1) AS hi, SUM(' . $due_expr . ' < "' . $today_sql . '") AS od, SUM(' . $due_expr . ' = "' . $today_sql . '") AS td' . $from_sql . $gBase);
if ($cq && ($cr = $cq->fetch_assoc())) { $tabCounts['all'] = (int)$cr['n']; $tabCounts['high'] = (int)$cr['hi']; $tabCounts['overdue'] = (int)$cr['od']; $tabCounts['today'] = (int)$cr['td']; }
$dd = 'DATEDIFF(' . $due_expr . ', "' . $today_sql . '")';
$dq = $db->query('SELECT b, COUNT(*) AS n FROM (SELECT (CASE WHEN ' . $due_expr . ' IS NULL THEN 0 WHEN ' . $dd . ' >= 4 THEN 4 WHEN ' . $dd . ' BETWEEN 1 AND 3 THEN ' . $dd . ' ELSE 0 END) AS b' . $from_sql . $gBase . ') t WHERE b > 0 GROUP BY b');
while ($dq && ($dr = $dq->fetch_assoc())) { $tabCounts['d' . (int)$dr['b']] = (int)$dr['n']; }
$rec['tabCounts'] = $tabCounts;
echo json_encode($rec);
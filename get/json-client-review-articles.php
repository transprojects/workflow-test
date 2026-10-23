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

            $gSearch.=' and wd.pub_id LIKE "%'.$db->real_escape_string($_REQUEST['article_id']).'%" ';  


	

}
else
{
	$gSearch .= 'and 1=0';
}


//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;

 // Read with a direct query instead of the stored procedure: the procedure appends "ORDER BY <col>, <col2>" and fails (empty table) when only one column is
 // sorted; and the old $arr above is one column out of step with the table. Column index -> what the column shows: 1 Client, 2 Journal, 3 File Name, 4 Stage,
 // 5 Received Date, 6 Due Date, 8 Page Count (0, 7 and 9 are not sortable).
 $col_i = ($col === '' || $col === null) ? -1 : (int)$col;
 $sdir = (strtolower((string)$sort_type) === 'asc') ? 'ASC' : 'DESC';
 $is_fp  = 'wd.stage = "FP"'; $is_iss = 'wd.stage REGEXP "^ISSCOR[0-9]+$"';
 $recv_e = 'CASE WHEN ' . $is_fp . ' THEN wd.recv_dt WHEN ' . $is_iss . ' THEN wd.created_dt ELSE r.received_date END';
 $due_e  = 'CASE WHEN ' . $is_fp . ' THEN wd.target_due_dt WHEN ' . $is_iss . ' THEN wd.due_dt ELSE r.due_date END';
 $pg_e   = 'CAST(CASE WHEN ' . $is_fp . ' THEN wd.manuscript_count ELSE r.correction_pages END AS UNSIGNED)';
 $by = array(1 => 'c.cust_name', 2 => 'j.j_code', 3 => 'wd.pub_id', 4 => 'wd.stage');
 if (isset($by[$col_i]))      { $order_sql = ' ORDER BY ' . $by[$col_i] . ' ' . $sdir . ', wd.id DESC'; }
 elseif ($col_i === 5)        { $order_sql = ' ORDER BY ((' . $recv_e . ') IS NULL OR (' . $recv_e . ') < "1000-01-01") ASC, (' . $recv_e . ') ' . $sdir . ', wd.id DESC'; }
 elseif ($col_i === 6)        { $order_sql = ' ORDER BY ((' . $due_e . ') IS NULL OR (' . $due_e . ') < "1000-01-01") ASC, (' . $due_e . ') ' . $sdir . ', wd.id DESC'; }
 elseif ($col_i === 8)        { $order_sql = ' ORDER BY ' . $pg_e . ' ' . $sdir . ', wd.id DESC'; }
 else                         { $order_sql = ' ORDER BY wd.id DESC'; }
 $ss_e = $db->real_escape_string((string)$sSearch);
 $res_prof = array();
 $lq = $db->query('SELECT c.cust_name as cn,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,wd.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,wd.target_due_dt,wd.present_user_id,wd.file_status,wd.xperttrack_sync,wd.issueorgroup_folder'
    . ' FROM `inw_inward_dtl` AS wd LEFT JOIN inw_revisions_dtl AS r ON (wd.`id` = r.`inw_id` AND r.r_id = (SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.`id` ORDER BY r_id DESC LIMIT 1)), adm_customer_master AS c, adm_journals AS j'
    . ' WHERE wd.cust_id = c.id AND wd.j_id = j.j_id AND wd.status IN ("Client Review") AND (pub_id LIKE "%' . $ss_e . '%" OR article_title LIKE "%' . $ss_e . '%") ' . $gSearch
    . $order_sql . ' LIMIT ' . (int)$start . ', ' . ((int)$length > 0 ? (int)$length : 10));
 while ($lq && ($lr = $lq->fetch_assoc())) { $res_prof[] = $lr; }
 if (!$res_prof) { $res_prof = ''; }          // same "nothing found" value the procedure path gives
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
$qry = $auth->countClientReviewArticlelist($sSearch,$gSearch);
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


$aid=$articles['id'];

		$stage_r=preg_replace("/REV([0-9]+)/","REV",$articles['stage']);
	$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$articles['stage']);
		
		 if($articles['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($articles['target_due_dt'])));
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
		$status='<label class="label label-success ams_label">Client Review</label>';
		


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
			3 => $articles['pub_id'].$ppms. $gf,
			4 => $articles['stage'],
			5 => $recv_dt,
			6 => $due_dt,
			7 => $status,
			8 => $manuscript_count,
			9 =>'<div class="d-flex gap-3">
			<div class="hidden-sm hidden-xs action-buttons">
			<a class="btn btn-xs btn-purple" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="revarticles/'.$articles['id'].'"><i class="ace-icon fa fa-history bigger-120"></i> Revises</a>
			<a class="btn btn-xs btn-pink btn-sm" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="finalarticles/'.$articles['id'].'"><i class="ace-icon fa fa-share bigger-120"></i> Final</a>
			<a class="btn btn-xs btn-info btn-sm" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="jstagearticles/'.$articles['id'].'"><i class="ace-icon fa fa-globe bigger-120"></i> JSTAGE</a>
			<a class="btn btn-xs btn-warning" style="text-decoration:none;color:white;cursor:pointer;border: 1px solid white;font-size:0.85em;width: fit-content" href="edit-article/'.$articles['id'].'"><i class="ace-icon fa fa-undo bigger-120"></i> Undo</a>
			</div>
			  </div>',


		 10 =>$articles['id'],
		);
		$k++;
	}
}
}

echo json_encode($rec);
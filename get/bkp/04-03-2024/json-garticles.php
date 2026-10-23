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

            $gSearch.='and wd.pub_id LIKE "%'.$_REQUEST['article_id'].'%" ';  


	

}
else
{
	//$gSearch .= '';
}


//echo $gSearch; exit;
//echo $sort_by_d;
//echo $sort_type_d;

 $res_prof = $auth->showArticlelist($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
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
$qry = $auth->countArticlelist($sSearch,$gSearch);
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


		//$trans_status = $auth->showArticleTransaction($articles['id'],$articles['stage']);
//print_r($trans_status);
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
		
				$prev_depart_sql = mysqli_fetch_array(mysqli_query($db,"SELECT u.full_name,t.current_status,t.id,dpm.dept_name,dpm.dept_sn FROM `inw_transactions` as t,users as u,adm_dept_master as dpm WHERE t.process_user = u.id and t.dept = dpm.dept_code AND t.`project_id` = '".$articles['id']."'  AND t.current_status IN ('Completed','Assign','Correction Done') AND t.stage = '".$articles['stage']."' ORDER BY t.`id` DESC limit 1;"));
				
		if($prev_depart_sql['full_name']!='')
		{
		$otherteam_status='<span class="label label-sm label-success">'.$prev_depart_sql['dept_sn'].'</span> | <span class="label label-sm label-info">'.$prev_depart_sql['full_name'].'</span>';
		}
		else
		{
		$otherteam_status='';
		}
		
		
		if($_COOKIE['department_id'] == 9)
		{
			$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$articles['id']."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$articles['stage']."' AND (t.id=(select id from `inw_transactions` where dept=9 and `project_id` = '".$articles['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		}
		else
		{
			$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$articles['stage']."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$articles['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		}
		
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();	
		
if($articles['approved_by']>0)
{

		
		if($_COOKIE['department_id'] != 9){

			if($transaction_toatl_count!='0')
			{
				if($transaction_toatl_count>0)
				{
				$start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" onClick="return check_work_status('.$articles['id'].','.$trans_res['id'].','.$_COOKIE['department_id'].');">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
				}
			
			}
			else
			{
				$start='<a title="Start work" style="font-size: 0.945em"  class="btn btn-xs btn-primary" onClick="return check_work_status('.$articles['id'].',0,'.$_COOKIE['department_id'].');">
				<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';	
			}
	}
	else
	{
		if($transaction_toatl_count!='0')
			{
				if($transaction_toatl_count>0)
				{
				$start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" onClick="return check_graphics_work_status('.$articles['id'].','.$trans_res['id'].','.$_COOKIE['department_id'].');">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
				}
			
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

	if($transaction_toatl_count!='0')
{
		if($transaction_toatl_count>0 )
			{
				
		   if(!empty($articles['aq_status']))
			{
			$aq_status='|<span class="label label-sm label-primary">'.$articles['aq_status'].'</span>'; 
			} 
			if($_COOKIE['department_id'] == 9) 
			{
				$current_status='<label class="label label-success ams_label">'.$current_qstatus.'</label> | <label class="label label-info ams_label">'.$trans_res['full_name'].'</label> <label class="label label-danger ams_label">'.$trans_res['current_status'].'</label> '.$aq_status.'';

			}
			else
			{
				$current_status='<label class="label label-success ams_label">'.$current_qstatus.'</label> | <label class="label label-info ams_label">'.$trans_res['full_name'].'</label> <label class="label label-danger ams_label">'.$trans_res['current_status'].'</label> '.$aq_status.'';
			}
			

		
		
			}
			
}
else
			{
				$current_status='<label class="label label-success ams_label">'.$current_qstatus.'</label>'.$aq_status.'';	
			}

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
                  <li>
                    <button class="dropdown-item" href="#">Delete</button>
                  </li>
                </ul>
              </div>
			  </div>',
			1 => $articles['cn'],
			2 => $articles['j_code'],
			3 => $articles['pub_id'],
			4 => ltrim($articles['issueorgroup_folder'],"/"),
			5 => $recv_dt,
			6 => $due_dt,
			7 => $articles['fig'],
			8 => $articles['tab'],
			9 => $articles['stage'],
			10 => $current_status,
			11 => $fig_complete_status,
			12 => $otherteam_status,
			13 => $instru,

			14 =>'<div class="d-flex gap-3">
			<div class="hidden-sm hidden-xs action-buttons">'.$start.'</div>
			  </div>',
			15 => $due_highlight,


		 16 =>$articles['id'],
		 17 =>$articles['highpriority'],
		 18 =>$due_highlight
			//7 => $payment['status'] == '0' ? 'Success' : 'Failed',
			//6 => $articles['id']
		);
		$k++;
	}
}
}

echo json_encode($rec);
<?php
set_time_limit(0); 
include ('dbc.php'); 

/// Get all the departments ///
$dept_query = "SELECT dept_name,dept_code FROM `adm_dept_master`   ORDER BY `dept_name` ASC ";
$dept_query_res = $db->query($dept_query) or ($db->error);
while($dept_row = $dept_query_res->fetch_assoc())
{
	///Get all the active users for particuler department
	$user_query = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE (department_id = '".$dept_row['dept_code']."' or FIND_IN_SET('".$dept_row['dept_code']."',access_other_depts))  and  approved='1' and user_level in ('1','3',4','5','6','7','11') ORDER BY `full_name` ASC";
	
	$users = $db->query($user_query) or ($db->error);
	while($user_row = $users->fetch_assoc()){
		
			$user_condition= "and dept='".$dept_row['dept_code']."' and inw.process_user= '".$user_row['id']."'  ";	
		
			 $start_date = date('Y-m-d', strtotime('-1 day'));
			 $end_date =date('Y-m-d', strtotime('-1 day'));

			//$start_date =date('Y-m-d');
			//$end_date=date('Y-m-d');

		
		
		while (strtotime($start_date) <= strtotime($end_date)){			
			
			$cnt_query = "SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Completed') ";
			
			$cnt_query_fp = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_fp from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Completed') and current_stage='FP'"));
			
			
			$cnt_query_rev = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_rev from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Completed') and current_stage LIKE '%REV%'  "));
			
			$cnt_query_fin = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_finals from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Completed') and (current_stage LIKE '%ISSFIN%' OR current_stage LIKE '%FIN%') "));
			
			$cnt_query_issue = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_issue from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Completed') and current_stage LIKE '%ISSCOR%'  "));
			
			
			
			$assigned_query_progress = "SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and (current_status='Inprogress')";
			
			
			$assign_qry = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd where DATE_FORMAT(wd.target_due_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."' and `assigned_user_id` ='".$user_row['id']."' AND status='".$dept_row['dept_name']."'";
			
			
			$worked_query_articles= "SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id and wd.id = inw.project_id    ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."'";		
			
			
			//echo $assign_qry;
			$givendate=date('Y-m-d', strtotime($start_date));
			$MyGivenDateIn = strtotime($givendate);
			$ConverDate = date("l", $MyGivenDateIn);
			$ConverDateTomatch = strtolower($ConverDate);
			// echo $ConverDateTomatch;
			
			
			$customers = $db->query($cnt_query) or ($db->error);
			$page_count = $customers->fetch_assoc();
			$total_pages = $page_count['num'];			
			
			// FP TOTAL COMPLETED COUNT
			$total_pages_fp_completed=$cnt_query_fp['num_fp'];
			
			// REV TOTAL COMPLETED COUNT
			$total_pages_rev_completed=$cnt_query_rev['num_rev'];
			
			
			// ISS FINAL TOTAL COMPLETED COUNT
			$total_pages_final_completed=$cnt_query_fin['num_finals'];
			
			// ISS ISSUE TOTAL COMPLETED COUNT
			$total_pages_issue_completed=$cnt_query_issue['num_issue'];
			
			
			/// Get the assign count 
			$assign_qry_res = $db->query($assign_qry) or ($db->error);
			$assign_res = $assign_qry_res->fetch_assoc();
			$assign_res_count = $assign_res['num'];	
			
			
			/// Get the assign count in progress in transaction table 
			$assign_qry_res_progress = $db->query($assigned_query_progress) or ($db->error);
			$assign_res_progress = $assign_qry_res_progress->fetch_assoc();
			$assign_res_count_progress = $assign_res_progress['num'];	
			
			
			
			/// Get the WORKED count of staff progress in transaction table 
			$worked_query_articles_res = $db->query($worked_query_articles) or ($db->error);
			$worked_res_progress = $worked_query_articles_res->fetch_assoc();
			$worked_res_count_articles = $worked_res_progress['num'];
			
			$assign=$assign_res_count+$assign_res_count_progress+$total_pages;
			$pending=($assign_res_count+$total_pages) - $total_pages;
			
			
			//// Get the worflow Steps ////
			$ati_query = "SELECT id FROM `user_report` WHERE user_id = '".$user_row['id']."' AND dept_code = '".$dept_row['dept_code']."' AND date = '".$start_date."';";
			$ati_result = $db->query($ati_query) or ($db->error);
			$ati_exist_status = $ati_result->fetch_assoc();
			$ati_toatl_count=$ati_result->num_rows;
			
			if($ati_toatl_count <= 0)
			{
				$user_report_query = "INSERT INTO `user_report` (`user_id`, `user_name`, `dept_code`, `dept_name`, `date`, `assigned`, `completed_total`, `completed_fp`, `completed_rev`, `completed_issu`, `completed_final`, `pending`, `wip`) VALUES ('".$user_row['id']."', '".$user_row['full_name']."', '".$dept_row['dept_code']."', '".$dept_row['dept_name']."', '".$start_date."', '".$assign."', '".$total_pages."', '".$total_pages_fp_completed."', '".$total_pages_rev_completed."', '".$total_pages_issue_completed."', '".$total_pages_final_completed."', '".$pending."', '".$worked_res_count_articles."')";
			} else {
				$user_report_query = "UPDATE user_report SET 
				assigned = '".$assign."', 
				completed_total = '".$total_pages."', 
				completed_fp = '".$total_pages_fp_completed."', 
				completed_rev =  '".$total_pages_rev_completed."',  
				completed_issu = '".$total_pages_issue_completed."', 
				completed_final = '".$total_pages_final_completed."', 
				pending = '".$pending."', 
				wip = '".$worked_res_count_articles."'  
				WHERE user_id = '".$user_row['id']."' AND dept_code = '".$dept_row['dept_code']."' AND date = '".$start_date."'AND id = '".$ati_exist_status['id']."';";
			}
			
			
			
			
			echo $user_report_query . "<br>";
			$user_report_query_res = $db->query($user_report_query) or ($db->error);
			
			
			
			
			$start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));
			
			} /// insert record end while loop
		
		} /// user row while loop end 	
} /// Department while loop end	

include('cron_run_send_mail.php');

?>

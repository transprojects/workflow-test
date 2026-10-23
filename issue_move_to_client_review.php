<?php 
include ('includes/includes.php'); include_once 'includes/issue_drafts.php';

if(isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
{
	
	$set_status = "`status` ='Client Review'";
    $user_working_status = '<span class="label label-sm label-success">Client Review</span>';
	
	if($_REQUEST['stage']=='FIN')
	{
		$set_status = "`status` ='Completed'";
		$user_working_status = '<span class="label label-sm label-success">Completed</span>';
	}
		
	$prj_update_query="UPDATE inw_volume_issues SET ".$set_status." WHERE vi_id = ".$_REQUEST['id']." ";
		
	//echo $prj_update_query; exit;
	$db->query($prj_update_query);
	
	/// draft rounds: the finished draft articles are now with the client
	idr_stamp_dispatch($db, (int)$_REQUEST['id'], true);

	//// Adding log to dispatch history table 
	
	$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		
		$ia_query = "SELECT id FROM `inw_inward_dtl` WHERE `v_issue_id` = ".$_REQUEST['id']." AND `status` = 'Delivery';";
		$issue_articles = $db->query($ia_query) or ($db->error);				
		while($row = $issue_articles->fetch_assoc())
		{
			$dispat_qry = "INSERT INTO `inw_issue_dispatch_history` (`cust_id`, `j_id`, `a_id`, `vi_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('".$app_res['cust_id']."', '".$app_res['j_id']."', '".$row['id']."', '".$_REQUEST['id']."', '', '', 'Manually moved this Article to under client review.', '".$_COOKIE['user_id']."', '".$app_res['stage']."', NOW(),'Moved')";
			$db->query($dispat_qry);			
		}
	}	
	
	header('location:'.$_COOKIE['set_url'].'/underdispatchissues');
}
?>
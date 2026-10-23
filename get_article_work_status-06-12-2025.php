<?php
	include ('dbc.php');
	$error_status = '';
	


	$sql = "SELECT * FROM `inw_inward_dtl` inner join inw_transactions on inw_inward_dtl.id=inw_transactions.project_id WHERE stage='".$_POST['stage']."' AND current_status ='Inprogress' and highpriority=1 AND present_user_id > 0";	
	$result = $db->query($sql);
	
	if($result->num_rows!=0)
	{
		$row = $result->fetch_assoc();
		$cwsql = "SELECT full_name FROM users WHERE id ='". $row['present_user_id']."';";	
		$result_cw = $db->query($cwsql);
		$cwrow = $result_cw->fetch_assoc();		
		echo 'Kindly Process the High Priority Articles First';
		exit;
	}
	else
	{
		$error_status = '0';
	}
	
	
	$sql="SELECT * FROM `inw_transactions` WHERE `project_id` != '".$_POST['id']."' AND `dept` = '".$_POST['stage']."' AND  `process_user` = '". $_COOKIE['user_id']."' AND `end_time` IS NULL AND current_status ='Inprogress' AND created_dt > '2024-01-01'";
	$result = $db->query($sql);
	if($result->num_rows > 0)
	{
		echo 'Please complete all the Inprogress Articles before you start this work';		
		exit;
	}
	else
	{
		$error_status = '0';
	}
 
	
	$sql = "SELECT present_user_id FROM `inw_inward_dtl` WHERE id = '".$_POST['id']."' AND present_user_id !='". $_COOKIE['user_id']."' AND present_user_id > 0;";	
	$result = $db->query($sql);
	
	if($result->num_rows > 0)
	{
		$row = $result->fetch_assoc();
		$cwsql = "SELECT full_name FROM users WHERE id ='". $row['present_user_id']."';";	
		$result_cw = $db->query($cwsql);
		$cwrow = $result_cw->fetch_assoc();		
		echo 'This Article currently in <strong>Progress.</strong> It is already started the work BY '. $cwrow['full_name'];
		exit;		
	}
	else
	{
		$error_status = '0';
	}
	
	echo $error_status;
	
	
?>

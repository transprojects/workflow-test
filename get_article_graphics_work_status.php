<?php
	include ('dbc.php'); 
	 
	$sql="SELECT id FROM `inw_transactions` WHERE `project_id` != '".$_POST['id']."' AND `dept` = '".$_POST['stage']."'  and  `process_user` = '". $_COOKIE['user_id']."' AND `end_time` IS NULL AND current_status ='Inprogress' AND created_dt > '2021-08-25'";
	
	$result = $db->query($sql);
	if($result->num_rows > 1)
	{
		echo 'Please complete all the Inprogress Articles before you start this work';
		exit;
	} 
	
	$sql_check_user = "SELECT * FROM `inw_inward_dtl` where id = '".$_POST['id']."'";	
	$result_check_user = $db->query($sql_check_user);
	$row_check_user= $result_check_user->fetch_assoc();
	
	
	$sql_check_users = "SELECT * FROM `inw_inward_dtl` where  present_user_id='". $_COOKIE['user_id']."' and id!= '".$_POST['id']."' AND created_dt > '2020-12-01' AND status NOT IN ('Delivery','Client Review','Completed')";	
	$result_check_users = $db->query($sql_check_users);
	$num_rows=$result_check_users->num_rows;
	
	
	if($num_rows>=3)
	{
	 echo 'Please complete all the Pending Articles before you start this work';exit;
	}
	
	$sql = "SELECT t.process_user FROM `inw_transactions` as t WHERE t.process_user != '". $_COOKIE['user_id']."' AND t.`project_id` = '".$_POST['id']."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$row_check_user['stage']."' AND `dept` = '".$_POST['stage']."';";
	
	$result = $db->query($sql);
	
	if($result->num_rows > 0)
	{
		$row = $result->fetch_assoc();
		$cwsql = "SELECT full_name FROM users WHERE id ='". $row['process_user']."';";	
		$result_cw = $db->query($cwsql);
		$cwrow = $result_cw->fetch_assoc();		
		echo 'This Article currently in <strong>Progress.</strong> It is already started the work BY '. $cwrow['full_name'];		
	}
	
	
	
	else
	{
		echo '0';
	}
	
	
?>

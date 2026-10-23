<?php
	include ('dbc.php'); 


	$sql="SELECT * FROM `inw_transactions` WHERE `project_id` != '".$_POST['id']."' AND `dept` = '".$_POST['stage']."' AND  `process_user` = '". $_COOKIE['user_id']."' AND `end_time` IS NULL AND current_status ='Inprogress' AND created_dt > '2019-07-12'";
	$result = $db->query($sql);
	if($result->num_rows > 1000)
	{
		echo 'Please complete all the Inprogress Articles before you start this work';
		// while($row = $result->fetch_assoc())
		// {
			// echo $row['project_id']."\n";
		// }
		exit;
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
	}
	else
	{
		echo '0';
	}
	
	
?>

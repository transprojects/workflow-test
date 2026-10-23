<?php
require_once('dbc.php');
$cwsql = "SELECT full_name FROM users WHERE id ='". $_SESSION['user_id']."';";	
		$result_cw = $db->query($cwsql);
		$cwrow = $result_cw->fetch_assoc();	
		
$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Take overed by ".$cwrow['full_name']."' WHERE id = ".$_POST['tid']."";	
$db->query($update_query);
	
?>
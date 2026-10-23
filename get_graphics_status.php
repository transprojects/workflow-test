<?php
require_once('dbc.php');

	$query = "SELECT * FROM `inw_inward_dtl` WHERE id='".$_REQUEST['a_id']."'";
	$fileinfo = $db->query($query) or ($db->error);
	$row = $fileinfo->fetch_assoc();
	
	if($row['fig_complete_status'] == 0 && $row['graphics'] > 0 && $row['stage']== "FP")
	{
		echo "Graphics files are not completed, Please check with graphics team.";
		exit;
	}
	else
	{
		echo 1;
		exit;
	}	
	
	
	
?>
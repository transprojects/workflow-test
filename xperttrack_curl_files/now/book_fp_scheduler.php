<?php
error_reporting(E_ALL); // Enable error reporting
ini_set('display_errors', 1); // Display errors
	include('conn.php');
	include ('dbc.php'); 
	echo 'Booking Articles started<br>';
	
	/// Get the not booked Articles from Godaddy Server ///
	$book_articles_qry = $xperttrack_db->query("SELECT * FROM `article_tracking_to_workflow` WHERE `stage`= 'FP' AND `stage` != '' AND `status` = 0 limit 2");
	
	
	if($book_articles_qry->num_rows == 0)	
	{
		die('No Pending Articles to book');
	}
	
	
	while($book_articles_list = $book_articles_qry->fetch_assoc())
	{
		
	
		//// Check in TVL workflow this Article is already booked////
		$tvl_wf_inw_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$book_articles_list['pub_id']."' AND stage = '".$book_articles_list['stage']."'";		
		
		
		$tvl_wf_inw_qry_res = $db->query($tvl_wf_inw_qry) or die($db->error);		
		$tvl_wf_inw_qry_row_track = $tvl_wf_inw_qry_res->fetch_assoc();
		if ($tvl_wf_inw_qry_res->num_rows == 0) {
			/// Add the Article to TVL workflow ///

			echo '<br>Imposting the Article - '.$book_articles_list['pub_id'];
			$tvl_inw_query = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$book_articles_list['pub_id']."';";
			$tvl_artile_res = $db->query($tvl_inw_query) or ($db->error);
			$tvl_wf_inw_qry_row = $tvl_artile_res->fetch_assoc();
			include("book_fp.php");

		} else {
			echo '<br>Updated the Article - '.$book_articles_list['pub_id'];
			$atw_update_query = "UPDATE `article_tracking_to_workflow` set status ='1',utime =NOW() where id='".$book_articles_list['id']."'";	
			$xperttrack_db->query($atw_update_query) or die($db->error);				
		}
		
	}
	
	
	
	
	
	 
?>
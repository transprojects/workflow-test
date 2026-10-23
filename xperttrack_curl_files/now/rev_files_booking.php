<?php
set_time_limit(0);
ini_set('max_execution_time', 9000000);

error_reporting(E_ALL); // Enable error reporting
ini_set('display_errors', 1); // Display errors
include('conn.php');
include ('dbc.php'); 
echo 'Revised Booking Articles started<br>';
	

	
	
	
$query = "SELECT * FROM revises_tracking_info WHERE  is_files_downloaded IS NULL  order by id desc LIMIT 2";

$proofs = $xperttrack_db->query($query) or ($xperttrack_db->error);
$aws_aca_cnt = $proofs->num_rows;
	if( $aws_aca_cnt > 0 )
	{	


			while ($app_res_track = $proofs->fetch_assoc()) 
			{
				
				
				//// Check in TVL workflow this Article is already booked////
		$tvl_wf_inw_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res_track['pub_id']."' AND stage = '".$app_res_track['stage']."'";		
		
		
		$tvl_wf_inw_qry_res = $db->query($tvl_wf_inw_qry) or die($db->error);		
		if ($tvl_wf_inw_qry_res->num_rows == 0) {
			/// Add the Article to TVL workflow ///

			echo '<br>Imposting the Article - '.$book_articles_list['pub_id'];
			$tvl_inw_query = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res_track['pub_id']."';";
			$tvl_artile_res = $db->query($tvl_inw_query) or ($db->error);
			$tvl_wf_inw_qry_row = $tvl_artile_res->fetch_assoc();
			if($app_res_track['stage']=='REV1')
			{
			include("add_rev_to_workflow_from_clientportal_sch.php");
			}
			elseif($app_res_track['stage']=='REV2')
			{
			include("add_rev_corrections_to_workflow_sch.php");
			}
			else
			{
			include("add_rev_corrections_to_workflow_sch.php");
			}

		} else {
			echo '<br>Updated the Article - '.$app_res_track['pub_id'];
			$atw_update_query = "UPDATE `revises_tracking_info` set is_files_downloaded ='1',utime ='".date('Y-m-d H:i:s')."' where id='".$app_res_track['id']."'";	
			$xperttrack_db->query($atw_update_query) or die($db->error);				
		}

			}
			
	}
	else
	{
				die('No Pending Articles to book');
	
	}
?>
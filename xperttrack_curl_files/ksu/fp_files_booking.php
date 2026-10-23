<?php
	set_time_limit(0);
	ini_set('max_execution_time', 9000000);

	//echo phpinfo();exit;
	include('conn.php');
	include ('dbc.php'); 

	//exit;
	$file_check='new';

	
	
	
$query = "SELECT * FROM article_tracking_info WHERE  is_files_downloaded IS NULL  order by id desc LIMIT 5";

$proofs = $xperttrack_db->query($query) or ($xperttrack_db->error);
$aws_aca_cnt = $proofs->num_rows;
	if( $aws_aca_cnt > 0 )
	{	


//echo $aws_aca_cnt;
while ($app_res_track = $proofs->fetch_assoc()) 
{


	$track_update_ip=mysqli_query($xperttrack_db,"UPDATE article_tracking_info SET  is_download_inprogress ='1',cron_starttime ='".date('Y-m-d H:i:s')."' WHERE id='".$app_res_track['id']."' ");

				//// Get the Article details form Workflow
			$wf_art_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res_track['pub_id']."' ";
			$result = $xperttrack_db->query($wf_art_qry) or ($db->error);	
			$app_res = $result->fetch_assoc();
			//$proofsinfo['pub_id'],$proofsinfo['id']
			
			$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '72' ";
			$customers = $db->query($query) or ($db->error);
			$crow = $customers->fetch_assoc();
			
			//// Journal name ///	
			$query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res['j_id']."';";
			$steps = $db->query($query) or ($db->error);
			$jninfo = $steps->fetch_assoc();

			
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$jninfo['j_cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	$wf_id=145;
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $_REQUEST['article_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$pub_id=$app_res_track['pub_id'];
	
$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	
$destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/FP/";

 
//$target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/".$pub_id."/FP/"; 
//$target_dir_auto ="New_Workflow_Files/AUTOMATION-SYSTEM/".$Cn."/"; 
// $target_dir_auto ="E:/New_Workflow_Files/AUTOMATION-SYSTEM/".$Cn."/"; 
$target_dir_auto ="E:/New_Workflow_Files/AUTOMATION-SYSTEM/KSU/"; 


 // Upload directory */`

/* 
if (!(is_dir($target_dir))) 
	{
		if (!mkdir($target_dir, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	} */
	
	if (!(is_dir($target_dir_auto))) 
	{
		if (!mkdir($target_dir_auto, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}
	 

     $filename ="/".$app_res_track['godaddy_article_path'];
     $name=$app_res_track['godaddy_article_zip'];
				
if($app_res_track['is_files_downloaded']!='1')
{


			///echo '<br/>';
	//$conn_id = ftp_connect($ftp_server) or die("<span style='color:#FF0000'><h2>Couldn't connect to $ftp_server</h2></span>");        // set up basic connection
	$conn_id = ftp_connect($ftp_server);        // set up basic connection
	
		if (!$conn_id) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','6','not connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
			include ("ftp_fp_failed.php");
		}
		else
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','6','connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");
		}

	//$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) or die("<span style='color:#FF0000'><h2>You do not have access to this ftp server!</h2></span>" );   // login with username and password, or give invalid user message
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);   // login with username and password, or give invalid user message
	
		if (!$login_result) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','7','No Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
			include ("ftp_fp_failed.php");
		}
		else
		{
		$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','7','Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		}
		
	// IMPORTANT!!! turn passive mode on
	
	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
	ftp_pasv($conn_id, false);
			
	if (($conn_id) && ($login_result)) 
	{
		
		
			//echo ' ftp connected';
		//$upload = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the file
						$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','7','ftp server connected!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		$maxRetries = 3;
        $retryCount = 0;

		//echo $target_dir_auto.$name;


do {
    //$upload =ftp_put($conn_id, $destination_file . $name, $filename, FTP_BINARY);
	$upload_auto = ftp_get($conn_id, $target_dir_auto.$name, $filename, FTP_BINARY);  // upload the file
	//$upload_data = ftp_get($conn_id, $target_dir.$data_articleid, $article_data, FTP_BINARY);  // upload the FP article data
	//$upload_download_data = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the FP article data

    $fileSize_xperttrack = ftp_size($conn_id,$filename);
    $fileSize_tvl_server =filesize($target_dir_auto.$name);
   // echo $fileSize_xperttrack."<br/>";
    //echo $fileSize_tvl_server;

    if (!$upload_auto || $fileSize_xperttrack != $fileSize_tvl_server) 
    {

				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','8','File not uploaded to workflow server Retry count :".$retryCount."','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");

				include ("upload_fp_failed.php");

        //echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
		 sleep(5); // Wait for a few seconds before retrying
    }
} while (!$upload_auto && $retryCount < $maxRetries);

		if($upload_auto) 
		{
			

				
				$track_update_file=mysqli_query($xperttrack_db,"UPDATE article_tracking_info SET  is_files_downloaded ='1',cron_endtime ='".date('Y-m-d H:i:s')."' WHERE id='".$app_res_track['id']."' ");

				
				//echo $app_res_track['is_files_downloaded'];
				 $ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$app_res_track['pub_id']."';";
					$ati_result = $xperttrack_db->query($ati_query) or ($xperttrack_db->error);
				$ati_exist_status = $ati_result->fetch_assoc();
			
  
  				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','8','File uploaded to workflow server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		
	$result_aws_inw = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."' "); 	
	$app_res_aws = $result_aws_inw->fetch_assoc();

	
	$godaddy_article_id=$app_res_aws['id'];
	$article_type = $app_res_aws['inw_type'];

	
				$result_tvl_inw = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."'";

				$tvl_wf_file = $db->query($result_tvl_inw) or ($db->error);
				$tvl_wf_cnt = $tvl_wf_file->num_rows;
	
				
				
				
				
				
	

				
		
				
	
		
		}
		
		else
		{
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','8','File upload failure in workflow last','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
				


			
			include("upload_fp_failed.php");
		}
		}
		
		
		
		
	}
	else
	{
		
					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','6','You do not have access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


		include("ftp_fp_failed.php");
	}
}
//sleep(10);
	}
?>
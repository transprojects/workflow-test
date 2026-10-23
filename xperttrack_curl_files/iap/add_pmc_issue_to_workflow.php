<?php
	set_time_limit(0);
	ini_set('max_execution_time', 9000000);

	//echo phpinfo();exit;
	include('conn.php');
	include ('dbc.php'); 

function rmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE')
			{
				rmove($f->getRealPath(), "$dest/$f");
				unlink($f->getRealPath());
				if(empty($f))
				{
					unlink($f);
				}
			}
        }
    }
    //unlink($src);
}
 $query = "SELECT * FROM issuefinal_tracking_info WHERE  is_files_downloaded IS NULL  order by isi_id desc ";

$proofs = $xperttrack_db->query($query) or ($xperttrack_db->error);
$aws_aca_cnt = $proofs->num_rows;
	if( $aws_aca_cnt > 0 )
	{	


//echo $aws_aca_cnt;
while ($app_res_track = $proofs->fetch_assoc()) 
{
	
	
	if($app_res_track['is_files_downloaded']!='1')
{

	echo "SELECT * FROM inw_volume_issues WHERE  vi_id='".$app_res_track['vi_id']."'  and cust_id='".$app_res_track['cust_id']."' and j_id='".$app_res_track['j_id']."' ";
	 $issue_volume_workflow= mysqli_fetch_array(mysqli_query($xperttrack_db,"SELECT * FROM inw_volume_issues WHERE  vi_id='".$app_res_track['vi_id']."'  and cust_id='".$app_res_track['cust_id']."' and j_id='".$app_res_track['j_id']."' "));

	 $currentDate = date('Y-m-d'); // Get the current date in the format 'YYYY-MM-DD'
			 $current_date_time=date('Y-m-d H:i:s');
		     $afternoon=date('Y-m-d 12:00:00');	
			 $beforenewday=date('Y-m-d 23:59:59');
			 
			 //echo $beforenewday;
			 if(strtotime($current_date_time)>strtotime($afternoon) || strtotime($current_date_time<$beforenewday) )
			 {
				//echo "nextday". $due_date = date('Y-m-d', strtotime('+1 day', strtotime($currentDate))); // Add one day to the current date
				$due_date = date('Y-m-d', strtotime('+1 day', strtotime($currentDate))); // Add one day to the current date
			 }
			 else
			 {
				//echo "sameday". $due_date = date('Y-m-d'); 
				$due_date = date('Y-m-d'); 
			 }
	
	//// Journal name ///	
			$query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res_track['j_id']."';";
			$steps = $db->query($query) or ($db->error);
			$jninfo = $steps->fetch_assoc();

			
	echo $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$jninfo['j_cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $app_res_track['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$volume_no=$app_res_track['volume_folder_name'];
	$issue_correction="ISSFIN1";
	$stage="ISSFIN1";

echo $work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/$volume_no";
$destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/$volume_no/$issue_correction/";
echo $target_dir_auto = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/ISSFIN1/"; 
$target_dir_auto_misc = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/ISSFIN1/misc/"; 

		if (!(is_dir($target_dir_auto))) 
	{
		if (!mkdir($target_dir_auto, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}
		if (!(is_dir($target_dir_auto_misc))) 
	{
		if (!mkdir($target_dir_auto_misc, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}
	
	  $filename ="/".$app_res_track['godaddy_article_path'];
      $name=$app_res_track['godaddy_article_zip'];
		///echo '<br/>';
	//$conn_id = ftp_connect($ftp_server) or die("<span style='color:#FF0000'><h2>Couldn't connect to $ftp_server</h2></span>");        // set up basic connection
	$conn_id = ftp_connect($ftp_server);        // set up basic connection
	
		if (!$conn_id) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','76','not connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
		}
		else
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','76','connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");
		}

	//$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) or die("<span style='color:#FF0000'><h2>You do not have access to this ftp server!</h2></span>" );   // login with username and password, or give invalid user message
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);   // login with username and password, or give invalid user message
	
		if (!$login_result) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','77','No Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
		}
		else
		{
		$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','77','Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		}
		
	// IMPORTANT!!! turn passive mode on
	
	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
	ftp_pasv($conn_id, false);
			
	if (($conn_id) && ($login_result)) 
	{
		
		
			//echo ' ftp connected';
		//$upload = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the file
						$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','77','ftp server connected!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		$maxRetries = 3;
        $retryCount = 0;

	do {
    //$upload =ftp_put($conn_id, $destination_file . $name, $filename, FTP_BINARY);
	$upload_auto = ftp_get($conn_id, $target_dir_auto_misc.$name, $filename, FTP_BINARY);  // upload the file
	//$upload_data = ftp_get($conn_id, $target_dir_auto_misc.$name_misc, $filename_misc, FTP_BINARY);  // upload the ISSUE CREATION article data
	
	//$upload_download_data = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the ISSUE CREATION article data

    $fileSize_xperttrack = ftp_size($conn_id,$filename);
    $fileSize_tvl_server =filesize($target_dir_auto.$name);
   // echo $fileSize_xperttrack."<br/>";
    //echo $fileSize_tvl_server;

    if (!$upload_auto || $fileSize_xperttrack != $fileSize_tvl_server) 
    {

				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','78','File not uploaded to workflow server Retry count :".$retryCount."','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


        //echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
		 sleep(5); // Wait for a few seconds before retrying
    }
} while (!$upload_auto && $retryCount < $maxRetries);

		if($upload_auto) 
		{


	
$track_update_file=mysqli_query($xperttrack_db,"UPDATE issuefinal_tracking_info SET  is_files_downloaded ='1',cron_endtime ='".date('Y-m-d H:i:s')."' WHERE isi_id='".$app_res_track['isi_id']."' ");
		

		
	//// Get the worflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '117';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
	$toatl_count=$result->num_rows;
	$dept_res = array();
	if($toatl_count>0)
	{
		$dept_res = $result->fetch_assoc();
	}
	
	$xmlFilePath   = '';
	
	$insert_query = "INSERT INTO `inw_issue_corrections` (`vi_id`,correction_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`,type) VALUES ('".$issue_volume_workflow['workflow_vi_id']."','1','".date('Y-m-d')."', '".$due_date."', '0', '".addslashes($app_res_track['instructions'])."', '".$destination_dir."','54', NOW(),'ISSFIN');";
	//echo $insert_query; exit;
	$db->query($insert_query) or die($db->error);
	
	
		/////////////////////////////////////////////////
		//// Here get the Articles Under this Issue /////
		////////////////// Loop Of Articles Start Here ///
		//////////////////////////////////////////////////
		/////////////////////////////////////////////////
		
		
		//// Delivery DIR /////
		mkdir($work_dir."/Delivery/".$stage."/", 0777, true);
		
		/////// Update Valoume and Issue Table ////
		$inw_volume_issues_update_query="UPDATE inw_volume_issues SET `work_dir` ='".$work_dir."',`dispatch_dir` ='".$work_dir."/Delivery/".$stage."/"."',`status` ='',`stage` ='".$stage."' WHERE vi_id = ".$issue_volume_workflow['workflow_vi_id'].";";		
		$db->query($inw_volume_issues_update_query);			
		
		
		$query = "SELECT * FROM `inw_inward_dtl` WHERE `v_issue_id` = ".$issue_volume_workflow['workflow_vi_id']." ORDER BY `status` ASC;";
		$articles_res = $db->query($query) or ($db->error);				
		while($app_res = $articles_res->fetch_assoc())
		{ 			
				
		
		$destination_dir = $work_dir."/".$app_res['pub_id']."/".$stage."/";
		
		if (!(is_dir($destination_dir))) {
		if (!mkdir($destination_dir, 0777, true)) {
		die('Failed to create folders...$jn');
		}
		}
	
	
	/// Move the Previos stage production Files current stage production ///
		$production_dir = $work_dir."/".$app_res['pub_id']."/".$app_res['stage']."/";		
		$production_dest_dir = $destination_dir;
		
		rmove($production_dir, $production_dest_dir);
		
		//// Crete the production Next Dept folder ///
		if (!(is_dir($production_dest_dir.$dept_res['dept_name']))) 
		{
			if (!mkdir($production_dest_dir.$dept_res['dept_name'], 0777, true)) 		{
				die('Failed to create folders...$jn');
			}
		}	
		$prj_update_query="UPDATE inw_inward_dtl SET `prev_stage` ='".$app_res['stage']."',`prev_workflow` ='".$app_res['wf_id']."',`prev_dept_id` ='".$app_res['department_id']."',`prev_status` ='".$app_res['status']."',`stage` ='".$stage."',wf_id = '117', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = ".$app_res['id'].";";
		//echo $prj_update_query; exit;
		$db->query($prj_update_query);
			
			
		}


	}
	}
}
	}
	}	
	
?>
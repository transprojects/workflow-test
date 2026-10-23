<?php
	set_time_limit(0);
	ini_set('max_execution_time', 9000000);

	//echo phpinfo();exit;
	include('conn.php');
	include ('dbc.php'); 

	//exit;
	$file_check='new';

	function rev_rmove($src, $dest){

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
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				rmove($f->getRealPath(), "$dest/$f");
				//unlink($f->getRealPath());
				if(empty($f))
				{
					//unlink($f);
				}
			}
        }
    }
    //unlink($src);
}
 
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
        } else if(!$f->isDot() && $f->isDir()) {
            rmove($f->getRealPath(), "$dest/$f");
            //rmdir($f->getRealPath());
        }
    }
    //rmdir($src);
}

function frcopy($src, $dest){

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
            copy($f->getRealPath(), "$dest/" . $f->getFilename());
			///// Keep the same file time //////
			$dt = filemtime($f->getRealPath());
			if ($dt != FALSE)
			{ 
				touch("$dest/" . $f->getFilename(), $dt);
			}
						
        } else if(!$f->isDot() && $f->isDir()) {
            frcopy($f->getRealPath(), "$dest/$f");
        }
    }
}
	
	
 $query = "SELECT * FROM issue_tracking_info WHERE  is_files_downloaded IS NULL  order by isi_id desc ";

$proofs = $xperttrack_db->query($query) or ($xperttrack_db->error);
$aws_aca_cnt = $proofs->num_rows;
	if( $aws_aca_cnt > 0 )
	{	


//echo $aws_aca_cnt;
while ($app_res_track = $proofs->fetch_assoc()) 
{


	$track_update_ip=mysqli_query($xperttrack_db,"UPDATE issue_tracking_info SET  is_download_inprogress ='1',cron_starttime ='".date('Y-m-d H:i:s')."' WHERE isi_id='".$app_res_track['isi_id']."' ");
	
	
	
 $issue_volume_xperttrack= mysqli_fetch_array(mysqli_query($xperttrack_db,"SELECT * FROM inw_volume_issues WHERE  vi_id='".$app_res_track['vi_id']."'"));

				//// Get the Article details form Workflow
			$wf_art_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res_track['pub_id']."' ";
			$result_inw = $xperttrack_db->query($wf_art_qry) or ($db->error);	
			$app_res_inw_track = $result_inw->fetch_assoc();
			//$proofsinfo['pub_id'],$proofsinfo['id']
			
			$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '77' ";
			$customers = $db->query($query) or ($db->error);
			$crow = $customers->fetch_assoc();
			
			//// Journal name ///	
			$query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res_inw_track['j_id']."';";
			$steps = $db->query($query) or ($db->error);
			$jninfo = $steps->fetch_assoc();

			
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$jninfo['j_cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	$wf_id=145;
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $app_res_track['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$pub_id=$app_res_track['pub_id'];
	$volume_no=$app_res_track['volume_folder_name'];
	
$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	
$destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/$volume_no/$artile_name/ISSCOR1/";

 
$target_dir_auto = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/ISSCOR1/"; 
$target_dir_auto_misc = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/ISSCOR1/misc/"; 


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
	
		if (!(is_dir($target_dir_auto_misc))) 
	{
		if (!mkdir($target_dir_auto_misc, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}
	 
	 

     $filename ="/".$app_res_track['godaddy_article_path'];
     $name=$app_res_track['godaddy_article_zip'];
	 
	 
		$filename_misc = "/".$issue_volume_xperttrack['upload_misc_path'];
	    $name_misc=$issue_volume_xperttrack['upload_file'];
				
if($app_res_track['is_files_downloaded']!='1')
{


			///echo '<br/>';
	//$conn_id = ftp_connect($ftp_server) or die("<span style='color:#FF0000'><h2>Couldn't connect to $ftp_server</h2></span>");        // set up basic connection
	$conn_id = ftp_connect($ftp_server);        // set up basic connection
	
		if (!$conn_id) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','76','not connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
			include ("ftp_issue_failed.php");
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
			
			include ("ftp_issue_failed.php");
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

		//echo $target_dir_auto.$name;


do {
    //$upload =ftp_put($conn_id, $destination_file . $name, $filename, FTP_BINARY);
	$upload_auto = ftp_get($conn_id, $target_dir_auto.$name, $filename, FTP_BINARY);  // upload the file
	$upload_data = ftp_get($conn_id, $target_dir_auto_misc.$name_misc, $filename_misc, FTP_BINARY);  // upload the ISSUE CREATION article data
	
	//$upload_download_data = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the ISSUE CREATION article data

    $fileSize_xperttrack = ftp_size($conn_id,$filename);
    $fileSize_tvl_server =filesize($target_dir_auto.$name);
   // echo $fileSize_xperttrack."<br/>";
    //echo $fileSize_tvl_server;

    if (!$upload_auto || $fileSize_xperttrack != $fileSize_tvl_server) 
    {

				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','78','File not uploaded to workflow server Retry count :".$retryCount."','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");

				include ("upload_issue_failed.php");

        //echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
		 sleep(5); // Wait for a few seconds before retrying
    }
} while (!$upload_auto && $retryCount < $maxRetries);

		if($upload_auto) 
		{
			

				
				$track_update_file=mysqli_query($xperttrack_db,"UPDATE issue_tracking_info SET  is_files_downloaded ='1',cron_endtime ='".date('Y-m-d H:i:s')."' WHERE isi_id='".$app_res_track['isi_id']."' ");

				
				//echo $app_res_track['is_files_downloaded'];
				 $ati_query = "SELECT * FROM `issue_tracking_info` WHERE pub_id = '".$app_res_track['pub_id']."';";
					$ati_result = $xperttrack_db->query($ati_query) or ($xperttrack_db->error);
				$ati_exist_status = $ati_result->fetch_assoc();
			
  
  				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','78','File uploaded to workflow server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		
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
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','78','File upload failure in workflow last','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
				


			
			include("upload_issue_failed.php");
		}
		}
		
		
		
		
	}
	else
	{
		
					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','ISSUE CREATION','76','You do not have access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


		include("ftp_issue_failed.php");
	}
	
	
	
	////// Upload File to server END Here////
	
	
			
 
// echo "SELECT * FROM inw_volume_issues WHERE  volume_number='".$issue_volume_xperttrack['volume_number']."' and issue_number='".$issue_volume_xperttrack['issue_number']."' and cust_id='77' and j_id='".$issue_volume_xperttrack['j_id']."'";
 $issue_volume_workflow_count= mysqli_num_rows(mysqli_query($db,"SELECT * FROM inw_volume_issues WHERE  volume_number='".$issue_volume_xperttrack['volume_number']."' and issue_number='".$issue_volume_xperttrack['issue_number']."' and cust_id='77' and j_id='".$issue_volume_xperttrack['j_id']."'"));
 //echo "Volume Issue Count in Workflow --> ".$issue_volume_workflow_count."<br/>";
 if($issue_volume_workflow_count==0)
 {
	 
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

	$insert_query = "INSERT INTO `inw_volume_issues` (`cust_id`, `j_id`, `volume_number`,issue_number,issue_count,`received_date`, `due_date`, `correction_pages`, `comments`,`created_by`, `created_on`,work_dir,dispatch_dir,stage,starting_page_number,automation_process,automation_status,hlink_month,hlink_year) VALUES ('".$issue_volume_xperttrack['cust_id']."', '".$issue_volume_xperttrack['j_id']."', '".$issue_volume_xperttrack['volume_number']."', '".$issue_volume_xperttrack['issue_number']."','".$issue_volume_xperttrack['issue_count']."','".$currentDate."', '".$due_date."', '0', '".addslashes($issue_volume_xperttrack['instructions'])."','54', NOW(),'".$work_dir."','".$work_dir."/Delivery/ISSCOR1/"."','ISSCOR1','".$issue_volume_xperttrack['starting_page_number']."','5','Waiting for Sequence number update','".$issue_volume_xperttrack['hlink_month']."','".$issue_volume_xperttrack['hlink_year']."');";
		
		$db->query($insert_query) or die($db->error);
		
		$volume_issue_id = $db->insert_id;	
 }
 else
 {
	  $issue_volume_workflow= mysqli_fetch_array(mysqli_query($db,"SELECT * FROM inw_volume_issues WHERE  volume_number='".$issue_volume_xperttrack['volume_number']."' and issue_number='".$issue_volume_xperttrack['issue_number']."' and cust_id='77' and j_id='".$issue_volume_xperttrack['j_id']."' "));
	  
	  $volume_issue_id = $issue_volume_workflow['vi_id'];	

 }


	//// Get the workflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '80';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
	$toatl_count_dep=$result->num_rows;
	$dept_res = array();
	if($toatl_count_dep>0)
	{
		$dept_res = $result->fetch_assoc();
	}
		
		//foreach ($_issue_volume_xperttrack['article_ids'] as $aid) 
		//{
			//$id = filter($aid);
			//echo "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."' ";
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."' "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			if($toatl_count>0)
			{
				$app_res = $result->fetch_assoc();	
				
				/// This is added for group folder moving to issue 				
				$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];		
				
				//// Articles are in Delivery stage will move it to the Issue and volume folder //////
				if($app_res['status'] == 'Client Review')
				{
					
				/// Productoin articles moving to new vloume and issue folder
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$volume_no."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				/// Downloads articles moving to new vloume and issue folder
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$volume_no."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				 $prj_update_query="UPDATE inw_inward_dtl SET v_issue_sequence_id='".$app_res_track['sequence_no']."',`volume_folder` ='".$volume_no."',`v_issue_id` ='". $volume_issue_id."',`prev_stage` ='".$app_res['stage']."',`prev_workflow` ='".$app_res['wf_id']."',`prev_dept_id` ='".$app_res['department_id']."',`prev_status` ='".$app_res['status']."',`stage` ='ISSCOR1',wf_id = '80', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE pub_id = '".$pub_id."' ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			
			//// Create a Folder under Downloads ISSCUR1 ///			
			$fdest = $app_res['work_dir'].$volume_no."/".$app_res['pub_id']."/";
			$dest = $fdest."ISSCOR1/";   // destination folder or file 
			if(!is_dir($dest))
			{
				mkdir($dest, 0777, true);
			}
			
			//// Create a Folder under Production ISSCUR1 ///
			$app_res['work_dir'] = str_replace('downloads','production',$app_res['work_dir']);
			$fdest = $app_res['work_dir'].$volume_no."/".$app_res['pub_id']."/";
			$dest = $fdest."ISSCOR1/".$dept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			
			/// Move the Previos stage production Files current stage production ///
			$production_dir = $fdest.$app_res['stage']."/";		
			$production_dest_dir = $fdest."ISSCOR1/";
			/// We are moving the latest stage file to isscor1 folder//////			
			//frcopy($production_dir, $production_dest_dir);
			rev_rmove($production_dir, $production_dest_dir);
		
			/////// Get the department name /////
		if($app_res['fig']>0)
		{
			//// Graphics Dept ID is : 9 ////
			
			$gresult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
			$gtoatl_count=$gresult->num_rows;
			$gdept_res = array();
			if($gtoatl_count>0)
			{
				$gdept_res = $gresult->fetch_assoc();
			}
			
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/Print";   // PAG Folder 
			mkdir($dest, 0777, true);
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/Web";   // XML Folder 
			mkdir($dest, 0777, true);			
			//rcopy($src,$dest);
		
		}
				
				}
				else
				{
					//// Here we updated the Temp stages to move the article automatically issue once complete all the stages //////
					/*$prj_update_query="UPDATE inw_inward_dtl SET `temp_volume_folder` ='".$newvolume_folder."',`temp_v_issue_id` ='". $volume_issue_id."',`v_issue_id` ='". $volume_issue_id."',`prev_stage` ='ISSCOR1',`prev_workflow` ='".$issue_volume_xperttrack['wf_id']."',`prev_dept_id` ='".$dept_info[0]."',`prev_status` ='".$dept_res['dept_name']."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);*/
			
				}
					
				
			}

		//}
		echo $target_dir_auto. "-->".$pub_id."-->"."Updated in Workflow Directory"."<br/>";

}
//sleep(10);

	}
	else
	{
		echo "No Issue Corrections";
	}
?>
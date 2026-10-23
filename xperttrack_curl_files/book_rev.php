<?php
	 $inw_id = $book_articles_list['inw_id'];
	 $workflow_id = $tvl_wf_inw_qry_row['id'];
	 $corrections = $book_articles_list['corrections'];
	 $stage = $book_articles_list['stage'];
	 $stage_prev = $book_articles_list['prev_stage'];
	 $upload_path_linux=$book_articles_list['linux_file_path'];
	 $art_current_stage = preg_replace('/[0-9]+/', '', $stage);
	 $art_prev_stage = preg_replace('/[0-9]+/', '', $stage_prev);
 
	 $volume_no = "Vol00000";
	
	//exit;



	function custom_copy($src, $dst) { 
  
    // open the source directory
    $dir = opendir($src); 
  
    // Make the destination directory if not exist
    @mkdir($dst); 
  
    // Loop through the files in source directory
    while( $file = readdir($dir) ) { 
  
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) 
            { 
  
                // Recursively calling custom copy function
                // for sub directory 
                custom_copy($src . '/' . $file, $dst . '/' . $file); 
  
            } 
            else { 
                copy($src . '/' . $file, $dst . '/' . $file); 
            } 
        } 
    } 
  
    closedir($dir);
} 
  
	
	//// Journal name ///	
		$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = '".$workflow_id."' "); 	
	    $app_res = $result->fetch_assoc();
	
	 $query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res['j_id']."'";
	 $steps = $db->query($query) or ($db->error);
	 $jninfo = $steps->fetch_assoc();
	
	

			
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$jninfo['j_cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
if($art_current_stage == 'REV' && $stage=='REV1')
{
$file_path_folder='editor';
$wf_id=147;
}
elseif($art_current_stage == 'REV' && $stage!='REV1')
{
$file_path_folder='client-files';
$wf_id=147;

}
elseif($art_current_stage == 'FIN' && $stage=='FIN1')
{
$file_path_folder='editor';
$wf_id=154;

}
elseif($art_current_stage == 'FIN' && $stage!='FIN1')
{
$file_path_folder='client-files';
$wf_id=148;

}
	
	
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $app_res['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';

	
$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	
 $destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/$stage/";

 //echo "<br/>";
$target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/".$artile_name."/".$stage."/"; 
 // Upload directory */`	


if (!(is_dir($target_dir))) 
{
	if (!mkdir($target_dir, 0777, true)) 
	{
		die('Failed to create folders...$jn');
	}
}
$zip_filename= $artile_name.".zip";


 $filename = $upload_path_linux."/".$artile_name.".zip";



// Now you can use $filename
if ($filename) {
    echo "File found at: " . $filename;
}

echo '<br> Target DIR : '.$target_dir;
echo '<br> Remote DIR : '.$filename;
$remote_file_path = $filename;
$local_file_path = $target_dir;


$log_file = "error_log.txt";
$use_passive_mode = true; // Change to false to test active mode

// Set up error logging
function log_error($message) {
    global $log_file;
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);
    echo $message . "<br/>";
}

// Establish connection
$conn_id = ftp_connect($ftp_server,21);

if (!$conn_id) {
    log_error("Could not connect to FTP server: $ftp_server");
    die("Could not connect to FTP server: $ftp_server");
}

// Login to FTP
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

if (!$login_result) {
    log_error("Could not log in with username: $ftp_user_name");
    ftp_close($conn_id);
    die("Could not log in with username: $ftp_user_name");
}

// Set connection mode
if ($use_passive_mode) {
    if (!ftp_pasv($conn_id, true)) {
        log_error("Could not switch to passive mode");
        ftp_close($conn_id);
        die("Could not switch to passive mode");
    }
} else {
    if (!ftp_pasv($conn_id, false)) {
        log_error("Could not switch to active mode");
        ftp_close($conn_id);
        die("Could not switch to active mode");
    }
}

// Check if the remote file exists
$file_size = ftp_size($conn_id, $remote_file_path);

if ($file_size == -1) {
    log_error("File does not exist: $remote_file_path");
    ftp_close($conn_id);
    die("File does not exist: $remote_file_path");
} else {
    log_error("File exists and its size is $file_size bytes.");
}

// Check if local directory is writable
$local_dir = dirname($local_file_path);
if (!is_writable($local_dir)) {
    log_error("Local directory is not writable: $local_dir");
    die("Local directory is not writable: $local_dir");
}


// Download the file
//$upload_auto = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the meta file

if (ftp_get($conn_id, $local_file_path.$zip_filename, $remote_file_path, FTP_BINARY)) {
    echo "File downloaded successfully.";
	$upload_auto = true;
} else {
	$upload_auto = false;
    log_error("Failed to download file: $remote_file_path to $local_file_path");
    
    // Debugging information
    $error = error_get_last();
    if ($error) {
        log_error("Error message: " . $error['message']);
    }

    // Additional debug info
    $ftp_rawlist = ftp_rawlist($conn_id, dirname($remote_file_path));
    log_error("FTP Raw List of Directory: " . print_r($ftp_rawlist, true));
}

// Close the connection
ftp_close($conn_id);
	

		
		if($upload_auto) 
		{
  


						$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','FINAL CORRECTIONS ','51','$stage Final Corrections ZIP File uploaded to workflow server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");



			


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
			// exit;



			 $stage_rcount = preg_replace('/[^0-9]/', '',$stage);
			 $rev_source=$work_dir.VOLUME_FOLDER_NAME."/$artile_name/$stage_prev/";
			 $rev_destination=$work_dir.VOLUME_FOLDER_NAME."/$artile_name/$stage/";
				
				
						$result_aws_inw_staff = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$artile_name."' "); 	
						$app_res_aws_staff = $result_aws_inw_staff->fetch_assoc();
						
					 $update_query = "UPDATE `inw_inward_dtl` set stage ='".$stage."',prev_stage ='".$stage_prev."',status='PAG',department_id=10,wf_id='".$wf_id."' where id='".$workflow_id."'";	
					$db->query($update_query) or die($db->error);
					
					/// Checking the REV DB for existing article values ////
					
					$inwerd_rev_detl = "SELECT * FROM inw_revisions_dtl where inw_id='".$workflow_id."' and revision_count='".$stage_rcount."' and type = '".$art_current_stage."' ";
					$inwerd_rev_query = $db->query($inwerd_rev_detl) or die($db->error);
					$inwed_rev = $inwerd_rev_query->fetch_assoc();
					$num_row_rev = $inwerd_rev_query->num_rows;
						if ($num_row_rev==0)
						{
							$insert_rev=mysqli_query($db,"insert into inw_revisions_dtl (`inw_id`,`revision_count`,`received_date`,`due_date`,`file_name`,`created_by`,`created_on`,`type`) values ('".$workflow_id."','".$stage_rcount."','".date('Y-m-d')."','".$due_date."','".$destination_dir."','54','".date('Y-m-d H:i:s')."','".$art_current_stage."')");
							
						}

					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','FINAL CORRECTIONS','48','IN WORKFLOW RECORD CREATED IN REV TABLE & RECORD UPDATED AS $stage ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

			
				
				
					
				
				
				custom_copy($rev_source,$rev_destination);

$files = glob($rev_destination."/Delivery/*"); // get all file names from Delivery folder
foreach($files as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file
  }
}

$files_prqc = glob($rev_destination."/PRQC/*"); // get all file names from PRQC FOLDER
foreach($files_prqc as $file_prqc){ // iterate files
  if(is_file($file_prqc)) {
    unlink($file_prqc); // delete file
  }
}		
echo "upload completed";
//// Updating the Article Status
$atw_update_query = "UPDATE `article_tracking_to_workflow` set status ='1',utime ='".date('Y-m-d H:i:s')."' where id='".$book_articles_list['id']."'";	
$xperttrack_db->query($atw_update_query) or die($db->error);
			
				
		
		
		
		
		//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '1'";
	 $steps_cp = $db->query($query_cp) or ($db->error);
	 $cpemail = $steps_cp->fetch_assoc();
	 
	 $result_aws_inw = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$artile_name."' "); 	
	$app_res_aws = $result_aws_inw->fetch_assoc();
	 
	 $result_staff = $xperttrack_db->query("SELECT * FROM eos_staffs WHERE staff_id = '".$app_res_aws['staff_id']."' "); 	
	$app_res_staff = $result_staff->fetch_assoc();
	 
	 	$result_jour = $xperttrack_db->query("SELECT * FROM adm_journals WHERE j_id= '".$app_res_aws['j_id']."'");
		$app_res_journals = $result_jour->fetch_assoc();
		
		 $to=$cpemail['mail_to'];
		// $cc=$app_res_journals['j_to_email'].",".$app_res_staff['staff_email'];
		// $bcc=$cpemail['mail_bcc']; 
		 
		// $cc=$_REQUEST['mail_cc'];	
		// $bcc=$_REQUEST['mail_cc1']; 
		// $cc=$cpemail['mail_cc'];	
		 
		
		
		echo $subject="EOS Article booked using scheduler : ".$artile_name."";

		echo $message='Dear Team,<br/><br/> 
			This Article have issue while booking first time. Now it is booked through scheduler.
			<br>
			Stage :'.$stage.'
			<br/>
			Prev Stage : '.$stage_prev.'
			<br/>
			WF Download Path : '.$target_dir.' 
			<br/>
			<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/><br/>
			 Regards,<br/><br/>
			'.$Cn.' Team <br/>';
		
			
		include("../smtpmail/phpmailer/class.phpmailer.php");
		echo $mail_from=$email_st['from_email'];
		echo $mail_pass=$email_st['from_password'];
			
		$mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
        $mail->SMTPAuth = true;
        $mail->Port = 587;
        $mail->Username = $mail_from;
        $mail->Password = $mail_pass;
        $mail->SMTPSecure = 'tls';
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
		
		
		
        $mail->Subject = $subject;
        $mail->Body = $message;

		$mail->AltBody    = ""; // optional, comment out and test

			
		//// Send mail to more than one user ///
		$mail->addAddress('marimuthu@transforma.in');
		
		
		$mail_tocc 		= explode(',',$cc);
		foreach($mail_tocc as $email)
		{
			if($email!='')
			{
				$mail->AddCC($email);
			}
		}
		$mail_tobcc 		= explode(',',$bcc);
		foreach($mail_tobcc as $email)
		{
			if($email!='')
			{
				$mail->AddCC($email);
			}
		}
	

				
		
				$maxRetries = 3;
				$retryInterval = 5; // Retry interval in seconds
				
				
				for ($retryCount = 1; $retryCount <= $maxRetries; $retryCount++) {
    
    // Send the email
    if (!$mail->send()) {
        echo 'Error sending email: ' . $mail->ErrorInfo . '<br>';

        // Retry after a delay
        sleep($retryInterval);
    } else {
        echo 'Email sent successfully.';
        break; // Exit the loop if email is sent successfully
		
    }
}
	   if ($retryCount > $maxRetries) {
    echo 'Email sending failed after maximum retries.';
}
	
	}
	else
		{
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','FINAL CORRECTIONS','51','File upload failure in workflow last','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
				
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','FINAL CORRECTIONS','48','IN WORKFLOW RECORD CREATED IN REV TABLE & RECORD UPDATED AS $stage ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


			
			echo "upload_final_corrections_rev_failed.php";
		}

	
	
    ?>
<?php

	include('conn.php');
	include ('dbc.php'); 
	
	 $inw_id = $_REQUEST['inw_id'];
	 $workflow_id = $_REQUEST['workflow_id'];
	 $corrections = $_REQUEST['corrections'];
	 $stage = $_REQUEST['stage'];
	 $stage_prev = $_REQUEST['stage_prev'];
 
	 $volume_no = "Vol00000";
	
	//exit;
		//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '7'";
	 $steps_cp = $db->query($query_cp) or ($db->error);
	 $cpemail = $steps_cp->fetch_assoc();


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
	$wf_id=115;
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $app_res['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	
$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	
 $destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/$stage/CORRECTIONS/"; 

 //echo "<br/>";
  echo $target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$volume_no."/".$artile_name."/".$stage."/CORRECTIONS/".date('d-m-Y')."/"; 
 // Upload directory */`
 
 
		


if (!(is_dir($target_dir))) 
	{
		if (!mkdir($target_dir, 0777, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}



	//$conn_id = ftp_connect($ftp_server) or die("<span style='color:#FF0000'><h2>Couldn't connect to $ftp_server</h2></span>");        // set up basic connection
	$conn_id = ftp_connect($ftp_server);        // set up basic connection

		if (!$conn_id) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction  ','45','not connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
			include ("ftp_final_corrections_rev_failed.php");
		}
		else
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction  ','45','connect to $ftp_server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");
		}

	//$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) or die("<span style='color:#FF0000'><h2>You do not have access to this ftp server!</h2></span>");   // login with username and password, or give invalid user message

		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);   // login with username and password, or give invalid user message

		if (!$login_result) 
		{
			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction ','46','No Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
			
			include ("ftp_final_corrections_rev_failed.php");
		}
		else
		{
		$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction ','46','Access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		}

	// IMPORTANT!!! turn passive mode on
	
	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
	ftp_pasv($conn_id, false);
			
	if (($conn_id) && ($login_result)) 
	{
		
		
		$stage_r=preg_replace("/REV([0-9]+)/","REV",$stage);

if($stage=='FP')
{
		$filename = $ftp_path."/REV/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/".$artile_name.".zip";
		$name=$artile_name.".zip";


		$correction_data = $ftp_path."/REV/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/corrections.txt";
	    $corr_articleid="corrections.txt";
}
elseif($stage_r=='REV')
{
		$filename = $ftp_path."/REV/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/".$artile_name.".zip";
		$name=$artile_name.".zip";


		$correction_data = $ftp_path."/REV/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/corrections.txt";
	    $corr_articleid="corrections.txt";
}
else
{
		$filename = $ftp_path."/PMC/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/".$artile_name.".zip";
		$name=$artile_name.".zip";


		$correction_data = $ftp_path."/PMC/".$crow['cust_name']."/".$jninfo['j_code']."/".$artile_name."/".$stage."/corrections/".date('d-m-Y')."/corrections.txt";
	    $corr_articleid="corrections.txt";
}


		


		$maxRetries = 3;
        $retryCount = 0;

		do {
	//$upload = ftp_get($conn_id, $target_dir.$name_meta, $filename_meta, FTP_BINARY);  // upload the meta file

	$upload_auto = ftp_get($conn_id, $target_dir.$name, $filename, FTP_BINARY);  // upload the meta file
	$upload_pap = ftp_get($conn_id, $target_dir.$corr_articleid, $correction_data, FTP_BINARY);  // upload the  additional corrections data with updated corrections details if any 
    $fileSize_aws = ftp_size($conn_id,$filename);
    $fileSize_tvl_server =filesize($target_dir.$name);
   // echo $fileSize_aws."<br/>";
    //echo $fileSize_tvl_server;

    if (!$upload_auto || $fileSize_aws != $fileSize_tvl_server) 
    {

				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction ','51','$stage Additional Correction ZIP File not uploaded to workflow server Retry count :".$retryCount."','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");

				include ("upload_final_corrections_rev_failed.php");

        //echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
    }
} while (!$upload_auto && $retryCount < $maxRetries);

		
		if($upload_auto) 
		{
  


						$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction ','51','$stage Additional Correction ZIP File uploaded to workflow server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");



			/* rename($destination_dir.$jninfo['j_code']."_".$pub_id.".zip",$destination_dir.$artile_name.".zip");
			$zip = new ZipArchive;
			$res = $zip->open($destination_dir.$artile_name.".zip");	
			if ($res === TRUE)
			{
				$zip->extractTo($destination_dir);
				$zip->close();  
				unlink($destination_dir.$artile_name.".zip");
			} */


			 $currentDate = date('Y-m-d'); // Get the current date in the format 'YYYY-MM-DD'
			 
			 // echo "<br/>". $current_date_time=date('Y-m-d H:i:s');
		     // echo "<br/>". $afternoon=date('Y-m-d 12:20:00');	
			 // echo "<br/>". $beforenewday=date('Y-m-d 23:59:59'); 
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
						
					 $update_query = "UPDATE `inw_inward_dtl` set additional_correction_xperttrack ='yes' where id='".$workflow_id."'";	
					$db->query($update_query) or die($db->error);
					
					/// Checking the REV DB for existing article values ////
					
					$inwerd_rev_detl = "SELECT * FROM inw_additional_correction_xperttrack where inw_id='".$workflow_id."' and revision_count='".$stage_rcount."' ";
					$inwerd_rev_query = $db->query($inwerd_rev_detl) or die($db->error);
					$inwed_rev = $inwerd_rev_query->fetch_assoc();
					$num_row_rev = $inwerd_rev_query->num_rows;
						if ($num_row_rev==0)
						{
							$insert_rev=mysqli_query($db,"insert into inw_additional_correction_xperttrack (`inw_id`,`revision_count`,`received_date`,`due_date`,`file_name`,`created_by`,`created_on`,`type`,'stage') values ('".$workflow_id."','".$stage_rcount."','".date('Y-m-d')."','".$due_date."','".$destination_dir."','54','".date('Y-m-d H:i:s')."','CORR','".$stage."')");
							
							$insert_rev_xpertdb=mysqli_query($xperttrack_db,"insert into inw_additional_correction_xperttrack (`inw_id`,`revision_count`,`received_date`,`due_date`,`file_name`,`created_by`,`created_on`,`type`,'stage') values ('".$app_res_aws_staff['id']."','".$stage_rcount."','".date('Y-m-d')."','".$due_date."','".$destination_dir."','".$app_res_aws_staff['staff_id']."','".date('Y-m-d H:i:s')."','CORR','".$stage."')");
						}

					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction','48','IN WORKFLOW RECORD CREATED IN CORR TABLE & RECORD UPDATED AS $stage ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

			
				
				
					
				
				
				
				echo "upload completed";
		
		
		
		
		//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '7'";
	 $steps_cp = $db->query($query_cp) or ($db->error);
	 $cpemail = $steps_cp->fetch_assoc();
	 
	 $result_aws_inw = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$artile_name."' "); 	
	$app_res_aws = $result_aws_inw->fetch_assoc();
	 
	 $result_staff = $xperttrack_db->query("SELECT * FROM staffs WHERE staff_id = '".$app_res_aws['staff_id']."' "); 	
	$app_res_staff = $result_staff->fetch_assoc();
	 
	 	$result_jour = $xperttrack_db->query("SELECT * FROM adm_journals WHERE j_id= '".$app_res_aws['j_id']."'");
		$app_res_journals = $result_jour->fetch_assoc();
		
		$to=$cpemail['mail_to'];
		$cc=$app_res_journals['j_to_email'].",".$app_res_staff['staff_email'];
		$bcc=$cpemail['mail_bcc']; 
		 
		// $to='marimuthu@transforma.in';
		// $cc='';	
		// $bcc=''; 
		 
		
		
		$subject="IAP Additional Correction Request with Attachments Submitted Successfully for : ".$artile_name."";

		$message='Dear Team,<br/><br/> 
			The Additional Correction Request with Attachmentsfor the above mentioned article details are uploaded to the portal now. Please check and update.
			<br/>
			<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/><br/>
			 Regards,<br/><br/>
			'.$Cn.' Team <br/>';
		
			
		include("../../smtpmail/phpmailer/class.phpmailer.php");
		$mail_from=$email_st['from_email'];
		$mail_pass=$email_st['from_password'];
			
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
		$mail_to 		= explode(',',$to);
		foreach($mail_to as $email)
		{
			if($email!='')
			{
				$mail->addAddress($email);
			}
		}
		
		
		
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
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction','51','File upload failure in workflow last','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
				
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction','48','IN WORKFLOW RECORD CREATED IN REV TABLE & RECORD UPDATED AS $stage ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


			
			include("upload_final_corrections_rev_failed.php");
		}

	}
	else
	{
		
					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$artile_name."','Additional Correction','45','You do not have access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


		include("ftp_final_corrections_rev_failed.php");
	}
?>
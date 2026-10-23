<?php

	include('conn.php');
	include ('dbc.php'); 

	//exit;
	$file_check='new';
		//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '9'";
	 $steps_cp = $db->query($query_cp) or ($db->error);
	 $cpemail = $steps_cp->fetch_assoc();
	
	
	
$query = "SELECT * FROM article_tracking_info WHERE is_workflow_updated IS NULL";

$proofs = $xperttrack_db->query($query) or ($xperttrack_db->error);
$aws_aca_cnt = $proofs->num_rows;
	if( $aws_aca_cnt > 0 )
	{	
while ($app_res_track = $proofs->fetch_assoc()) 
{

				//// Get the Article details form Workflow
			$wf_art_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res_track['pub_id']."' ";
			$result = $xperttrack_db->query($wf_art_qry) or ($db->error);	
			$app_res = $result->fetch_assoc();
			//$proofsinfo['pub_id'],$proofsinfo['id']
			
			$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '53' ";
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
$target_dir_auto ="New_Workflow_Files/AUTOMATION-SYSTEM/".$Cn."/"; 

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
	 

    $filename =$ftp_path."/".$app_res_track['godaddy_article_path'];
     $name=$app_res_track['godaddy_article_zip'];
				

			//echo '<br/>';
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
    }
} while (!$upload_auto && $retryCount < $maxRetries);

		if($upload_auto) 
		{
			

				
				$track_update_file=mysqli_query($xperttrack_db,"UPDATE article_tracking_info SET  is_files_downloaded ='1' WHERE id='".$app_res_track['id']."' ");

				
				//echo $app_res_track['is_files_downloaded'];
				echo $ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$app_res_track['pub_id']."';";
					$ati_result = $xperttrack_db->query($ati_query) or ($xperttrack_db->error);
				$ati_exist_status = $ati_result->fetch_assoc();
				echo "is_files_downloaded PUBID (".$pub_id.") :".$ati_exist_status['is_files_downloaded']."";
				echo "<br/>";
	if($ati_exist_status['is_files_downloaded'] == 1)
	{
  
  				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','8','File uploaded to workflow server','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

		//echo "test";
			/* rename($destination_dir.$jninfo['j_code']."_".$pub_id.".zip",$destination_dir.$artile_name.".zip");
			$zip = new ZipArchive;
			$res = $zip->open($destination_dir.$artile_name.".zip");	
			if ($res === TRUE)
			{
				$zip->extractTo($destination_dir);
				$zip->close();  
				unlink($destination_dir.$artile_name.".zip");
			} */
	$result_aws_inw = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."' "); 	
	$app_res_aws = $result_aws_inw->fetch_assoc();

	
	$godaddy_article_id=$app_res_aws['id'];
	$article_type = $app_res_aws['inw_type'];

	
				$result_tvl_inw = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$pub_id."'";

				$tvl_wf_file = $db->query($result_tvl_inw) or ($db->error);
				echo "WORKFLOW COUNT PUBID (".$pub_id.") :".$tvl_wf_cnt = $tvl_wf_file->num_rows;echo "<br/>";
	
				$mail_status=false;
				if($tvl_wf_cnt==0)
				{
				 $insert_query = "INSERT INTO `inw_inward_dtl` (`cust_id`,j_id, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`, `pub_id`,`created_dt`,stage,wf_id,department_id,status,work_dir,volume_folder,inw_type,approved_by,zip_size) VALUES ('".$jninfo['j_cust_id']."','".$j_id."', '".$pub_id."', '".$jn."', '".$pub_id."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".date('Y-m-d')."', '".$pub_id."', NOW(),'0','".$wf_id."','1','0','".$work_dir."','".VOLUME_FOLDER_NAME."','".$article_type."','1','".$zipSize."');";	
				$db->query($insert_query) or die($db->error);
				$db_article_id = $db->insert_id;
						
				
				
				$update_godaddy_server=mysqli_query($xperttrack_db,"update inw_inward_dtl set workflow_id='".$db_article_id."',zip_size='".$zipSize."' where id='".$godaddy_article_id."' ");
				
					$sql_author_article=mysqli_query($xperttrack_db,"SELECT * FROM inw_inward_dtl_author WHERE inw_id = '".$godaddy_article_id."' "); 	
					while($res_author_article=mysqli_fetch_array($sql_author_article))
					{
						$insert_author_workflow=mysqli_query($db,"INSERT INTO `inw_inward_dtl_author`(`inw_id`,`cust_id`,j_id,`journal_title`, `pub_id`, `author_name`,`author_email`, `orcid_id`,`created_dt`,`corresponding_author`,`staff_id`) VALUES ('".$db_article_id."','".$res_author_article['cust_id']."','".$res_author_article['j_id']."', '".$res_author_article['journal_title']."', '".$res_author_article['pub_id']."', '".$res_author_article['author_name']."', '".$res_author_article['author_email']."', '".$res_author_article['orcid_id']."', NOW(), '".$res_author_article['corresponding_author']."','".$res_author_article['staff_id']."') ");
					}
				//echo "upload completed";
				
				
		 	 			$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','9','IN WORKFLOW RECORD CREATED IN DB ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");



	
	$result_staff = $xperttrack_db->query("SELECT * FROM staffs WHERE staff_id = '".$app_res_aws['staff_id']."' "); 	
	$app_res_staff = $result_staff->fetch_assoc();
	
	
		   $to=$cpemail['mail_to'];
		   //$cc=$jninfo['j_to_email'].",".$app_res_staff['staff_email'];
		  // $cc=$cpemail['mail_cc'];	
		  // $bcc=$cpemail['mail_bcc']; 
		   $cc=$cpemail['mail_cc'];	
		   $cc=$cpemail['mail_cc'];	
		   
		
		$s_stage = "FP";
		$subject="KSU File : $name - First Proof - Downloaded";
		$message='Dear Transforma,<br/><br/> 
			We would like to inform you that the first proof article has been successfully posted for processing, and the relevant details are as follows:<br/><br/>
			<table style="border-collapse: collapse;">
			<tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
			<th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Information</th>
			<th style="word-wrap: break-word;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Details</th>
			</tr>
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; Journal Name</td>
			<td style="border: 1px solid #F4B083;">'.$jninfo['j_code'].'</td>
			</tr>	         	
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; File Name</td>
			<td style="color:#0080ff; border: 1px solid #F4B083;">' . $name .'</td>
			</tr>
		
			</table>
			<br/><br/>
			If you have any questions or require further assistance, please feel free to contact us.
			<br/><br/>
			Thank you for your cooperation.
			<br/><br/>
			Best regards,<br/><br/>
			'.$app_res_staff['staff_name'].'<br/>
			KSU Team.<br/><br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/>';
			
		include("smtpmail/phpmailer/class.phpmailer.php");
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
		
						$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','10','Mail sent error','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");



        echo 'Error sending email: ' . $mail->ErrorInfo . '<br>';

        // Retry after a delay
        sleep($retryInterval);
    } else {
		
								$track_update=mysqli_query($xperttrack_db,"UPDATE article_tracking_info SET  is_workflow_updated ='1' WHERE id='".$app_res_track['id']."' ");

		

								$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','10','Mail sent successfully','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','1','".date('Y-m-d H:i:s')."')");

        echo 'Email sent successfully for file moved to workflow.';
        break; // Exit the loop if email is sent successfully
		
    }
}
	   if ($retryCount > $maxRetries) {
    echo 'Email sending failed after maximum retries.';
}
			


				
			}
	}
	else
	{
	$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','9','IN WORKFLOW RECORD NOT CREATED N DB AFTER FILE DOWNLOAD ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");

	}
		
		}
		
		else
		{
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','8','File upload failure in workflow last','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");
				
				$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','9','IN WORKFLOW RECORD CREATED IN DB ','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


			
			include("upload_fp_failed.php");
		}
		
		
		
		
	}
	else
	{
		
					$insert_logs=mysqli_query($xperttrack_db,"insert into logs(file_name,stage,cl_id,remarks,servers,ip,status,cdatetime) value ('".$pub_id."','FP','6','You do not have access to this ftp server!','WORKFLOW','".$_SERVER['REMOTE_ADDR']."','0','".date('Y-m-d H:i:s')."')");


		include("ftp_fp_failed.php");
	}
}
sleep(10);
	}
?>
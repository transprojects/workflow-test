<?php

if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2 || $_POST['optionRad'] == 3) 
	{
		  if ($_FILES['fileAttachment']['name'] == "" && sizeof($_POST['attach_files']) == 0) 
		  {
			
			 header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=File missed, Please check it.");
			 exit;
		  
		  }
	}	 
	if ($_POST['optionRad'] == 4)
	{
		
		
		 // To check proof folder exist or not
		if(!is_dir($_POST['work_dir'])) { 
			 header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Delivery folder missed, Please check it.");
			 exit;
		}
		
		if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
		{ 
			 header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
		}
		if($post['txtTo'] != $app_res['corresponding_mail'] && $post['txtToName'] != $app_res['corresponding_author'])
		{
			 header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Please check author name and email id!.");
			exit;
		}

		
	}
	// if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2) 
	if ($_POST['optionRad'] == 2) 
	{
		///////////////////////////////////////////////////
		/////// Upload the files to Server START HERE /////
		///////////////////////////////////////////////////
		// get FTP access parameters
		
		
        $current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);

        $host = $_POST['FtpHost'];

        $user = $_POST['FtpUser'];

        $pass = $_POST['FtpPass'];

        $destDir = $_POST['FtpPath'];
		
		
		// Checking the FTP connection
		// $conn = ftp_connect($host); 
		$conn = ftp_connect($host);
		if($conn)
		{
			/// Checking the FTP Login
			 $FTP_login = ftp_login($conn, $user, $pass);
			if ($FTP_login) 
			{	
				// Switched to passive mode after logging in
				ftp_pasv($conn, true);
				if (ftp_nlist($conn, $destDir) == true) 
				{
					$upload_satus = false;
					foreach ($_POST['attach_files'] as $file) {
						// $upload = ftp_put($conn, $destDir . "/" . $file, $_POST['work_dir'] . "/" . $file, FTP_BINARY);
						// upload a file
						if (ftp_put($conn, $destDir . "/" . $file, $_POST['work_dir'] . "/" . $file, FTP_BINARY)) 
						{
							$upload_satus = true;
							
						}
						else
						{
							
							$upload_satus = false;
							header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=There was a problem while uploading $file to FTP server!.");
							exit;
						}

					}
					if($upload_satus)
					{
						///// Add records in Artile Dispatch history ///

						$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $app_res['cust_id'] . "', '" . $app_res['j_id'] . "', '" . $app_res['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body . "', '" . $_COOKIE['user_id'] . "', '" . $app_res['stage'] . "', NOW(),'" . $dispatch_method[$_POST['optionRad']] . "')";
						$db->query($dispat_qry);
					
					
	
						//// Update the next stage status ////
						 $set_status = "`status` ='Client Review',";
						if ($_POST['stage'] == 'FIN') {
							$set_status = "`status` ='Completed',";
						}
					
						$prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
					
						//echo $prj_update_query; exit;
						$db->query($prj_update_query);
						
						//header("location:projects.php?msg=Successfully completed");
						header("location:".$_COOKIE['set_url']."/underdispatcharticles?msg=Successfully completed");
						exit;
					}
					else
					{
						header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=There was a problem while uploading files to FTP server!.");
						exit;
					}
					
				}
				else
				{
					header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=$destDir not exist in FTP server!.");
					exit;
				}
			}
			else
			{

			header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Login to FTP server failed!.");
			 exit;
			}
		}
		else
		{
			 header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Connection to FTP server failed!.");
			 exit;
		}
		
		// close the FTP stream

        ftp_close($conn);
	}
	
?>
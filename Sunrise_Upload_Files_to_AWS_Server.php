<?php
require_once('includes/includes.php');
$query = "SELECT * FROM `inw_inward_dtl` WHERE id='".$_REQUEST['a_id']."'";
	$fileinfo = $db->query($query) or ($db->error);
	$artilce_info = $fileinfo->fetch_assoc();
	
	/// Get the Customer details ///
	$query = "SELECT * FROM `adm_customer_master` WHERE id = ".$artilce_info['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
	
	//// Get the journal details ////
	$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$artilce_info['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	
	$src = $artilce_info['work_dir'].$artilce_info['volume_folder']."/".$artilce_info['pub_id']."/".$artilce_info['stage'];
	$meta_xml_name = $src."/PAG/Meta.xml";
	$meta_xml_file_name ="Meta.xml";
	
	$pdf_name="";
	$pdf_file_name="";
	
	if($journalinfo['j_code'] == "CFR") // if CFR onely
	{
		$pdf_name = $src."/Delivery/".$artilce_info['pub_id'].".pdf";   // source folder or file
		$pdf_file_name = $artilce_info['pub_id'].".pdf";   // source folder or file
	}
	else
	{
		$pdf_name = $src."/Delivery/".$artilce_info['pub_id']."_AU1.pdf";   // source folder or file
		$pdf_file_name =  $artilce_info['pub_id']."_AU1.pdf";   // source folder or file
	}
	$message = "";
	$status = 1;
	if (!file_exists($meta_xml_name)) 
	{
		$status = 0;
		$message = "Meta XML file not exists in PAG folder, Please check it";
	}
	else
	{
		if (!file_exists($pdf_name)) 
		{
			$status = 0;
			$message = $pdf_file_name." file not exists in Delivery folder, Please check it";
		}
	}
	
	
	if ($status) 
	{
		include("awsdb.php");
		///////////////////////////////////////////////////
		/////// Upload the files to AWS FTP Server START HERE /////
		///////////////////////////////////////////////////
		// get FTP access parameters
		
		$host = $ftp_server;

        $user = $ftp_user_name;

        $pass = $ftp_user_pass;

        $destDir = "/In/";
		
		
		// Checking the FTP connection
		// $conn = ftp_connect($host); 
		$conn = ftp_connect($host);
		if($conn)
		{
			/// Checking the FTP Login
			 $FTP_login = ftp_login($conn, $user, $pass);
			if ($FTP_login) 
			{	
				ftp_set_option($conn, FTP_USEPASVADDRESS, false);
				// Switched to passive mode after logging in
				ftp_pasv($conn, true);
					
				//if (ftp_nlist($conn, $destDir) == true) 
				//{
					$destination_dir= $destDir.$customer_info['cust_name']."/".$journalinfo['j_code']."/".$artilce_info['pub_id']."/";
					if (ftp_nlist($conn, $destination_dir) == false) 
					{
						if (!ftp_mkdir($conn, $destination_dir)) 
						{
							// $status = 0;
							// $message = "Error while creating $destination_dir in FTP server!";
						} 
						
					}
					
					$upload_satus = false;
					if($status)
					{
						// $upload = ftp_put($conn, $destDir . "/" . $file, $_POST['work_dir'] . "/" . $file, FTP_BINARY);
						// upload a file
						if (ftp_put($conn, $destination_dir .  $meta_xml_file_name, $meta_xml_name, FTP_BINARY)) 
						{
							
							$upload_satus = true;
							
						}
						else
						{
							$upload_satus = false;
							$status = 0;
							$message = "There was a problem while uploading $meta_xml_file_name to FTP server!";
							
						
						}
						
						if (ftp_put($conn, $destination_dir . $pdf_file_name, $pdf_name, FTP_BINARY)) 
							{
								$upload_satus = true;
								
							}
							else
							{
								
								$upload_satus = false;
								$status = 0;
								$message = "There was a problem while uploading $pdf_file_name to FTP server!";
								
							}
						

					
					}
					
					if($upload_satus)
					{
						$status = 1;
						$message = "Uploading files to FTP server Successfully completed!";
					}
					else
					{
						$status = 0;
						$message = "There was a problem while uploading files to FTP server!";
					}
					
				//}
				//else
				//{
				//		$status = 0;
				//		$message = "$destDir not exist in FTP server!";
				//}
			}
			else
			{
				$status = 0;
				$message = "Login to FTP server failed!.";
			}
		}
		else
		{
			
			$status = 0;
			$message = "Connection to FTP server failed!.";
		}
		
		// close the FTP stream

        ftp_close($conn);
	}

	$array_info['status'] = $status;
	$array_info['message'] = $message;	
	echo json_encode($array_info);
	
?>

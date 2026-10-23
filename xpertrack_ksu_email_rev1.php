<?php 
include('linux_db_ksu.php');


//echo "xpertrack page";exit;

/// Checking the XPERTTRACKING DB for existing article values ////
    $inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl where pub_id ='" . $_POST['article_id'] . "';";
    $inwerd_query_xperttrack = $xpertrack_db->query($inwerd_detl_xperttrack) or die($xpertrack_db->error);
    $inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();
	//echo $inwed_run_xperttrack['workflow_id'];
	
		//// Get the article details ////
	$query_xarticle = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$_POST['article_id']."';";
	$articledetails_xpert = $db->query($query_xarticle) or ($db->error);
	$artilce_info_xpert= $articledetails_xpert->fetch_assoc();
	//echo $artilce_info_xpert['corresponding_mail'];
	
	
	
	/////// Get the Journal Name ////
		//// Journal name ///	
		$query_xpert = "SELECT * FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
		$steps_xpert = $db->query($query_xpert) or ($db->error);
		$jninfo_xpert = $steps_xpert->fetch_assoc();
	
        $mail_from = $jninfo_xpert['j_from_email'];
        $mail_from_name = "";
        $mail_to_name = "";
        date_default_timezone_set('Asia/Kolkata');
        $body ="<div><div>Dear Editor,<br><br><div><div>We have uploaded the revised proof of ".$_POST['article_id']." to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us.<br></div><br>Best regards,<br>Transforma</div>
</div></div>
<br>
*** This is an automated system generated e-mail, please do not return your correction to this mail.</div>";

	 $spassword=date('Y')."-".$inwed_run_xperttrack['id']."-".date('m')."-".date('d');
	//$spassword="12";
	//echo $artilce_info_xpert['corresponding_author'];
      $body = str_replace('##AuthorNames##', $artilce_info_xpert['corresponding_author'], $body);
    $body = str_replace('##FileName##', $_POST['article_id'], $body);
    $body = str_replace('##ARTICLETITLE##', $_POST['article_title'], $body);
	//	echo $body;
	$sub="[##FileName##] - Revised Proof for your record";
    $Subject = str_replace('##FileName##', $_POST['article_id'], $sub);
	
	

		$filename =$post['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$_POST['stage']."/Delivery/".$post['pub_id'].".pdf";
		$name=$post['pub_id'].".pdf";
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $jninfo_xpert['j_from_email_pwd'];
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject =$Subject;
        $mail->Body = $body;
		//$mail->AddAttachment($filename);
		$txtTo=$jninfo_xpert['j_cc_email'];
//// Send mail to more than one user ///
        $mail_to = explode(',', $txtTo);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


        $mail->AltBody = ""; // optional, comment out and test

        $mail_tocc = explode(',', $jninfo_xpert['j_bcc_email']);
        foreach ($mail_tocc as $email) {
            if ($email != '') {
                $mail->AddCC($email);
            }
        }

        $mail_tobcc = explode(',', $jninfo_xpert['j_bcc_email']);
        foreach ($mail_tobcc as $email) {
            if ($email != '') {
                $mail->AddBCC($email);
            }
        }
	
           
       if(file_exists($filename))
	   {
		   
		   
		 //  echo $mail_from;
	//echo $txtTo;
	//echo $Subject;
	//echo $body;

        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {	
            echo "Message sent!";
       
        //exit;
		
		
					$set_status = "`status` ='Client Review',";
				if ($_POST['stage'] == 'FIN') {
					$set_status = "`status` ='Completed',";
				}
				
				 $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";

				//echo $prj_update_query; exit;
				$db->query($prj_update_query);
				
				///// Add records in Artile Dispatch history ///

				$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $mail_from . "', '" . $txtTo . "', '" . $body. "', '" . $_COOKIE['user_id'] . "', '" . $_POST['stage'] . "', NOW(),'Attach file &amp; mail')";
				$db->query($dispat_qry);
				
				
				
				
				
//LINUX SERVER REV1  FILE UPLOAD FROM TRANSFORMA TO AUTHOR
    

     $destination_file_linux = $ftp_linux_path . "/REV/" . $post['cust_name'] . "/" . $post['j_code'] . "/" . $_POST['article_id']."/REV1/transforma-to-client/";  // Upload directory */`
    // $name = $_POST['article_id'] . ".pdf";

     $conn_id_linux = ftp_connect($ftp_linux_server) or die("Couldn't connect to $ftp_linux_server");        // set up basic connection

    $login_result_linux = ftp_login($conn_id_linux, $ftp_linux_user_name, $ftp_linux_user_pass) or die("You do not have access to this ftp server");   // login with username and 	password, or give invalid user message
	
	
/* 		echo $filename;
	echo "<br/>";
	echo $name;
	echo "<br/>";
	echo $destination_file_linux;
exit; */

	ftp_set_option($conn_id_linux, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($conn_id_linux, true);

            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/REV/" . $post['cust_name'] . "/" . $post['j_code'] . "/")) {

                echo "Successfully created $destination_file_linux"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/REV/" . $post['cust_name'] . "/" . $post['j_code'] . "/" . $_POST['article_id'] . "/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
			
			 if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/REV/" . $post['cust_name'] . "/" . $post['j_code'] . "/" . $_POST['article_id'] . "/". "/" . $_POST['stage'] . "/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }

if (ftp_mkdir($conn_id_linux, $destination_file_linux)) {
            echo "Successfully created $destination_file_linux";
        } else {
            echo "Error while creating $destination_file_linux";
        }

    if ((!$conn_id_linux) || (!$login_result_linux)) {  // check connection
        // wont ever hit this, b/c of the die call on ftp_login
        echo "FTP connection has failed";
        echo "Attempted to connect to $ftp_linux_server for user $ftp_linux_user_name";
      
    } 
	
	
    if (file_exists($filename)) {

     
        $maxRetries_linux = 3;
        $retryCount_linux = 0;


do {
    $upload_linux =ftp_put($conn_id_linux, $destination_file_linux . $name, $filename, FTP_BINARY);
    $fileSize_linux = ftp_size($conn_id_linux, $destination_file_linux. $name);
    $fileSize_tvl_server =filesize($filename);
    echo $fileSize_linux."<br/>";
    echo $fileSize_tvl_server;

    if (!$upload_linux || $fileSize_linux != $fileSize_tvl_server) 
    {
        echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount_linux++;
    }
} while (!$upload_linux && $retryCount_linux < $maxRetries_linux);

	}
	
	
	$update_inwrd_dtl_xperttrack = "UPDATE `inw_inward_dtl` SET stage='".$_POST['stage']."',stage_with='KSU',corrections='no',final_package_status='0' WHERE id='" . $inwed_run_xperttrack['id'] . "'";
     $xpertrack_db->query($update_inwrd_dtl_xperttrack);

//LINUX SERVER REV1  FILE UPLOAD FROM TRANSFORMA TO AUTHOR
			}
	   }
//exit;
	?>
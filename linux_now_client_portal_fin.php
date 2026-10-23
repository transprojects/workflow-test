<?php
set_time_limit(0);
include ('linux_db_now.php');


    
	$project_id = "";
	$cust_id = "";
	$j_id = "";
	
    $stage = $_POST['stage'];


	    /// Checking the LINUX SERVER DB for existing article values ////
     $inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$app_res['id']."'";
    $inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
    $inwed_run = $inwerd_query->fetch_assoc();

    $inwerd_detl_tracker = "SELECT * FROM article_tracker where pub_id='".$app_res['pub_id']."'";
    $inwerd_query_tracker = $xpertrack_db->query($inwerd_detl_tracker) or die($xpertrack_db->error);
    $inwed_run_tracker = $inwerd_query_tracker->fetch_assoc();

     /// Checking the WORKFLOW SERVER DB for existing article values ////





	
	
    /// $app_res in this variable we are geting the fulle article details ////
	
	
	
	
	
	

     $num_row = $inwerd_query->num_rows;
    $num_row_tracker = $inwerd_query_tracker->num_rows;
	
    if ($num_row==1) {
         
		
    
		$final_package_status='1';
	
		
         $update_inwrd_dtl = "UPDATE `inw_inward_dtl` SET job_id='".$app_res['job_id']."',article_title='".mysqli_real_escape_string($xpertrack_db,$app_res['article_title'])."',pub_id='".$app_res['pub_id']."',stage='".$stage."',prev_stage='".$stage."',stage_with='NOW',status='Delivery',final_package_status='".$final_package_status."' WHERE workflow_id='".$app_res['id']."' ";
        $xpertrack_db->query($update_inwrd_dtl);
		

			$stage_fin=preg_replace("/FIN([0-9]+)/","FIN",$stage);
			$stage_fin_count = preg_replace('/[^0-9]/', '',$stage);

	
			if($num_row_tracker==0)
			{
	    $update_tracker_articles=mysqli_query($xpertrack_db,"INSERT INTO article_tracker(j_id,inw_id,pub_id,finals_from_transforma) VALUES('".$inwed_run['j_id']."','".$inwed_run['id']."','".$app_res['pub_id']."','".date('Y-m-d H:i:s')."')");
			}
			else
			{
	    $update_tracker_articles=mysqli_query($xpertrack_db,"UPDATE article_tracker set finals_from_transforma='".date('Y-m-d H:i:s')."' where pub_id='".$app_res['pub_id']."' ");
			}		
    }
	
	

	
	 $filename = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/Delivery/".$app_res['pub_id'].".zip";
     $name = $app_res['pub_id'] . ".zip";	

	

     //$destination_file = "/Vendor/".$_POST['cust_name']."/".$_POST['j_code']."/". $app_res['pub_id']."/".$stage."/";
     
     echo $destination_file = $ftp_linux_path . "/FIN/NOW/" . $_POST['j_code'] . "/" . $app_res['pub_id']."/".$stage."/";  // Upload directory */`

     //where you want to throw the file on the webserver (relative to your login dir)
     $conn_id = ftp_connect($ftp_linux_server) or die("Couldn't connect to $ftp_linux_server");        // set up basic connection

    $login_result = ftp_login($conn_id, $ftp_linux_user_name, $ftp_linux_user_pass) or die("You do not have access to this ftp server");   // login with username and 	password, or give invalid user message
	
	
/* 		echo $filename;
	echo "<br/>";
	echo $name;
	echo "<br/>";
	echo $destination_file;
exit;  ;*/

	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($conn_id, true);
// Check if the folder exists
$folder_exists = ftp_nlist($conn_id, $destination_file);

// If the folder doesn't exist, create it
if (!$folder_exists) {

			
			//echo "test folder exists or not";exit;
            if (ftp_mkdir($conn_id, $ftp_linux_path . "/FIN/NOW/" . $_POST['j_code'] . "/")) {

                echo "Successfully created $destination_file"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id, $ftp_linux_path . "/FIN/NOW/" . $_POST['j_code'] . "/" . $app_res['pub_id'] . "/")) {
                echo "Successfully created $destination_file"; 
            } else {
                echo "Error while creating folder";
            }

if (ftp_mkdir($conn_id, $destination_file)) {
            echo "Successfully created $destination_file";
        } else {
            echo "Error while creating $destination_file";
        }
		}

    if ((!$conn_id) || (!$login_result)) {  // check connection
        // wont ever hit this, b/c of the die call on ftp_login
        echo "FTP connection has failed";
        echo "Attempted to connect to $ftp_linux_server for user $ftp_linux_user_name";
      
    } 
	
	
    if (file_exists($filename)) {

     
        $maxRetries = 3;
        $retryCount = 0;


do {
    $upload =ftp_put($conn_id, $destination_file . $name, $filename, FTP_BINARY);
    $fileSize_aws = ftp_size($conn_id, $destination_file. $name);
    $fileSize_tvl_server =filesize($filename);
   // echo $fileSize_aws."<br/>";
   // echo $fileSize_tvl_server;

    if (!$upload || $fileSize_aws != $fileSize_tvl_server) 
    {
        echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
    }
} while (!$upload && $retryCount < $maxRetries);





    if (!$upload)
    {
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
   
        header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $app_res['id'] . "&msg=File upload FTP Issue, Please try again.");
        exit;
        
       
    }
}
else{
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
        
        
    header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $app_res['id'] . "&msg=Source file path is missing please check and try again.");
        exit;
}

ftp_close($conn_id);

   /////////// Send metadata mail to client ///////

    $mail_from = $_POST['txtFrom'];
    $mail_from_name = "";


    $mail_to_name = "";

    date_default_timezone_set('Asia/Kolkata');
    $body = $_POST['txtBody'];
    $Subject = $_POST['txtSub'];


/////// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();

      $body = str_replace('##FileName##', $_POST['article_id'], $_POST['txtBody']);
    
        





    include_once("smtpmail/phpmailer/class.phpmailer.php");

    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = "outlook.office365.com";
    $mail->SMTPAuth = true;
    $mail->Port = 587;
    $mail->Username = $mail_from;
    $mail->Password = $_POST['txtMpass'];
    $mail->SMTPSecure = 'tls';
    $mail->From = $mail_from;
    $mail->FromName = $mail_from;
    $mail->isHTML(true);
    //$mail->Subject = $Subject;
   	$mail->Subject = str_replace('[##FileName##]', $_POST['article_id'], $_POST['txtSub']);
    $mail->Body = $body;

//////// Send mail to more than one user ///
    $mail_to = explode(',', $_POST['txtTo']);
    foreach ($mail_to as $email) {
        if ($email != '') {
            $mail->addAddress($email);
        }
    }
/////////////// End here send mail ////////


    $mail->AltBody = ""; // optional, comment out and test

    $mail_tocc = explode(',', $_POST['txtCc']);
    foreach ($mail_tocc as $email) {
        if ($email != '') {
            $mail->AddCC($email);
        }
    }

    $mail_tobcc = explode(',', $_POST['txtBcc']);
    foreach ($mail_tobcc as $email) {
        if ($email != '') {
            $mail->AddBCC($email);
        }
    }



				$maxRetries = 3;
				$retryInterval = 5; // Retry interval in seconds
				
				
				for ($retryCount = 1; $retryCount <= $maxRetries; $retryCount++) {
    
    // Send the email
    if (!$mail->send()) {
        echo 'Error sending email: ' . $mail->ErrorInfo . '<br>';
		
		$prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
			//echo $prj_update_query; exit;
		$db->query($prj_update_query);

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


	
	
?>
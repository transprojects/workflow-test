<?php
set_time_limit(0);
include_once ('includes/includes.php');
include ('linux_db_iap.php');


    
	$project_id = "";
	$cust_id = "";
	$j_id = "";
	
    $stage = $_POST['stage'];

	echo "SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." ";
	$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];
	}

/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	$journal_title=$journal_info['j_code'];
	
		$app_res['volume_folder'] = VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];
	 $folder=$journal_title."_".$app_res['volume_number']."(".$app_res['issue_number'].")";
	 $folder_zip=$journal_title."_".$app_res['volume_number']."(".$app_res['issue_number'].")".".zip";

	 echo $filename = $app_res['dispatch_dir'].$folder_zip;
     $name = $folder_zip;

	

     //$destination_file = "/Vendor/".$_POST['cust_name']."/".$journal_title."/". $app_res['pub_id']."/".$stage."/";
     
     echo $destination_file = $ftp_linux_path . "/ISSUE/" . $_POST['cust_name'] . "/" . $journal_title . "/" . $app_res['volume_folder']."/".$stage."/from_transforma/";  // Upload directory */`
	// exit;

     //where you want to throw the file on the webserver (relative to your login dir)
     $conn_id = ftp_connect($ftp_linux_server) or die("Couldn't connect to $ftp_linux_server");        // set up basic connection

    $login_result = ftp_login($conn_id, $ftp_linux_user_name, $ftp_linux_user_pass) or die("You do not have access to this ftp server");   // login with username and 	password, or give invalid user message
	
	
/* 		echo $filename;
	echo "<br/>";
	echo $name;
	echo "<br/>";
	echo $destination_file;
exit; */

	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($conn_id, true);
// Check if the folder exists
$folder_exists = ftp_nlist($conn_id, $destination_file);

// If the folder doesn't exist, create it
if (!$folder_exists) {

			
			//echo "test folder exists or not";exit;
            if (ftp_mkdir($conn_id, $ftp_linux_path . "/ISSUE/" . $_POST['cust_name'] . "/" . $journal_title . "/")) {

                echo "Successfully created $destination_file"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id, $ftp_linux_path . "/ISSUE/" . $_POST['cust_name'] . "/" . $journal_title . "/" . $app_res['volume_folder'] . "/")) {
                echo "Successfully created $destination_file"; 
            } else {
                echo "Error while creating folder";
            }
			 if (ftp_mkdir($conn_id, $ftp_linux_path . "/ISSUE/" . $_POST['cust_name'] . "/" . $journal_title . "/" . $app_res['volume_folder'] . "/".$stage."/")) {
                echo "Successfully created $destination_file"; 
            } else {
                echo "Error while creating folder";
            }
			if (ftp_mkdir($conn_id, $ftp_linux_path . "/ISSUE/" . $_POST['cust_name'] . "/" . $journal_title . "/" . $app_res['volume_folder'] . "/".$stage."/from_transforma/")) {
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
    
        $set_status = "`status` ='Client Review'";
	if($_POST['stage']=='FIN')
	{
		$set_status = "`status` ='Completed'";
	}
		
	$prj_update_query="UPDATE inw_volume_issues SET ".$set_status." WHERE vi_id = ".$_REQUEST['id']." ";
		
	//echo $prj_update_query; exit;
	$db->query($prj_update_query);
   
        header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $_REQUEST['id'] . "&msg=File upload FTP Issue, Please try again.");
        exit;
        
       
    }
}
else{
    
       		$prj_update_query = "UPDATE inw_volume_issues SET status='Delivery' WHERE vi_id = " . $_REQUEST['id'] . "";
//echo $prj_update_query; exit;
	$db->query($prj_update_query);
   
        
    header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $_REQUEST['id'] . "&msg=Issue Source file path is missing please check and try again.");
        exit;
}

ftp_close($conn_id);

   /////////// Send metadata mail to client ///////

    $mail_from = $_POST['txtFrom'];
    $mail_from_name = "";


    $mail_to_name = "";

    date_default_timezone_set('Asia/Kolkata');
    $body = $_POST['txtBody'];
    $Subject = str_replace('[##FileName##]', $folder, $_POST['txtSub']);
	if($stage=='ISSCOR1')
	{
    $Subject = str_replace('[##Stage##]', "Flatplan", $Subject);
	}
	else
	{
    $Subject = str_replace('[##Stage##]', "updated finals", $Subject);
	}


/////// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();

    $body = str_replace('[##FileName##]', $folder, $_POST['txtBody']);
	if($stage=='ISSCOR1')
	{
    $body = str_replace('[##Stage##]', "final files", $body);
	}
	else
	{
    $body = str_replace('[##Stage##]', "updated final files", $body);
	}

        





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
    $mail->Subject = $Subject;
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
		
		$prj_update_query = "UPDATE inw_volume_issues SET status='Delivery' WHERE vi_id = " . $_REQUEST['id'] . "";
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
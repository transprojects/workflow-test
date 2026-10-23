<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

include ('linux_db_dev.php');


	    /// Checking the AWS DB for existing article values ////
    echo $inwerd_detl = "SELECT * FROM inw_inward_dtl where pub_id ='" . $app_res['pub_id'] . "';";exit;
    $inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
    $inwed_run = $inwerd_query->fetch_assoc();
    $aws_wid = str_replace('E:', 'D:/xampp/htdocs', $app_res['work_dir']);
    /// $app_res in this variable we are geting the fulle article details ////
	
	
	
	   /// Checking the XPERTTRACKING DB for existing article values ////
    $inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl where pub_id ='" . $app_res['pub_id'] . "';";
    $inwerd_query_xperttrack = $xpertrack_db->query($inwerd_detl_xperttrack) or die($xpertrack_db->error);
    $inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();

	
	

    $num_row = $inwerd_query->num_rows;
    if ($num_row <= 0) {
         $insert_inwd_dtl = "INSERT INTO `inw_inward_dtl`( `cust_id`, `j_id`, `worklocation`, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`, `pub_id`, `doi`, `pub_dt`, `fig`, `tab`, `location_id`, `manuscript_count`, `corresponding_author`, `corresponding_mail`, `activity`, `file_name`, `stage`, `status`, `allocate_to`, `allocate_time`, `created_by`, `created_dt`, `modified_by`, `modified_dt`, `approved_by`, `approved_on`, `department_id`, `sub_department_id`, `assign_department_id`, `wf_id`, `instructions`, `issueorgroup_folder`, `highpriority`, `graphics`, `inw_type`, `complexity`, `work_dir`, `fig_complete_status`, `volume_folder`, `v_issue_id`, `prev_stage`, `prev_workflow`, `prev_status`, `prev_dept_id`, `present_user_id`, `temp_volume_folder`, `temp_v_issue_id`, `type`, `page_count`, `notes`, `img_disk_count`, `img_redraw_count`, `img_relabel_count`, `img_color_figures`, `reminder_mail`,`stage_with`) VALUES ('" . $cust_id . "','".  $j_id."', 'TVL','" . $app_res['job_id'] . "','" . $app_res['journal_title'] . "','" . $app_res['article_title'] . "','" . $app_res['recv_dt'] . "','" . $app_res['accpt_dt'] . "','" . $app_res['due_dt'] . "','" . $app_res['pub_id'] . "','" . $app_res['doi'] . "','" . $app_res['pub_dt'] . "','" . $app_res['fig'] . "','" . $app_res['tab'] . "','" . $app_res['location_id'] . "','" . $app_res['manuscript_count'] . "','" . $app_res['corresponding_author'] . "','" . $app_res['corresponding_mail'] . "','" . $app_res['activity'] . "','" . $app_res['file_name'] . "','" . $app_res['stage'] . "','" . $app_res['status'] . "','" . $app_res['allocate_to'] . "','" . $app_res['allocate_time'] . "','" . $app_res['created_by'] . "','" . $app_res['created_dt'] . "','" . $app_res['modified_by'] . "','" . $app_res['modified_dt'] . "','" . $app_res['approved_by'] . "','" . $app_res['approved_on'] . "','" . $app_res['department_id'] . "','" . $app_res['sub_department_id'] . "','" . $app_res['assign_department_id'] . "','" . $app_res['wf_id'] . "','" . $app_res['instructions'] . "','" . $app_res['issueorgroup_folder'] . "','" . $app_res['highpriority'] . "','" . $app_res['graphics'] . "','" . $app_res['inw_type'] . "','" . $app_res['complexity'] . "','" .  $aws_wid . "','" . $app_res['fig_complete_status'] . "','" . $app_res['volume_folder'] . "','" . $app_res['v_issue_id'] . "','" . $app_res['prev_stage'] . "','" . $app_res['prev_workflow'] . "','" . $app_res['prev_status'] . "','" . $app_res['prev_dept_id'] . "','" . $app_res['present_user_id'] . "','" . $app_res['temp_volume_folder'] . "','" . $app_res['temp_v_issue_id'] . "','" . $app_res['type'] . "','" . $app_res['page_count'] . "','" . $app_res['notes'] . "','" . $app_res['img_disk_count'] . "','" . $app_res['img_redraw_count'] . "','" . $app_res['img_relabel_count'] . "','" . $app_res['img_color_figures'] . "','" . $app_res['reminder_mail'] . "','AUTHOR')";
        $xpertrack_db->query($insert_inwd_dtl);
		$project_id = $xpertrack_db->insert_id;
		
    } else {
		$project_id = $inwed_run['id'];
           $update_inwrd_dtl = "UPDATE `inw_inward_dtl` SET `auto_id`='" . $app_res['auto_id'] . "',cust_id='" . $cust_id . "',`j_id`='" . $j_id . "',`worklocation`='TVL', `job_id`='" . $app_res['job_id'] . "',`journal_title`='" . $app_res['journal_title'] . "',`article_title`='" . $app_res['article_title'] . "',`recv_dt`='" . $app_res['recv_dt'] . "',`accpt_dt`='" . $app_res['accpt_dt'] . "',`due_dt`='" . $app_res['due_dt'] . "',`pub_id`='" . $app_res['pub_id'] . "', doi='" . $app_res['doi'] . "',`pub_dt`='" . $app_res['pub_dt'] . "',`fig`='" . $app_res['fig'] . "',`tab`='" . $app_res['tab'] . "',`location_id`='" . $app_res['location_id'] . "',`manuscript_count`='" . $app_res['manuscript_count'] . "',`corresponding_author`='" . $app_res['corresponding_author'] . "',`corresponding_mail`='" . $app_res['corresponding_mail'] . "',`activity`='" . $app_res['activity'] . "',`file_name`='" . $app_res['file_name'] . "',`stage`='" . $app_res['stage'] . "',`status`='" . $app_res['status'] . "',`allocate_to`='" . $app_res['allocate_to'] . "',`allocate_time`='" . $app_res['allocate_time'] . "',`created_by`='" . $app_res['created_by'] . "',`created_dt`='" . $app_res['created_dt'] . "',modified_by='" . $app_res['modified_by'] . "',modified_dt='" . $app_res['modified_dt'] . "',approved_by='" . $app_res['approved_by'] . "',approved_on='" . $app_res['approved_on'] . "',department_id='" . $app_res['department_id'] . "',sub_department_id='" . $app_res['sub_department_id'] . "',assign_department_id='" . $app_res['assign_department_id'] . "',wf_id='" . $app_res['wf_id'] . "',instructions='" . $app_res['instructions'] . "',issueorgroup_folder='" . $app_res['issueorgroup_folder'] . "',highpriority='" . $app_res['highpriority'] . "',graphics='" . $app_res['graphics'] . "',inw_type='" . $app_res['inw_type'] . "',complexity='" . $app_res['complexity'] . "',work_dir='" . $aws_wid . "', fig_complete_status='" . $app_res['fig_complete_status'] . "', volume_folder='" . $app_res['volume_folder'] . "', v_issue_id='" . $app_res['v_issue_id'] . "', prev_stage='" . $app_res['prev_stage'] . "',prev_workflow='" . $app_res['prev_workflow'] . "',prev_status='" . $app_res['prev_status'] . "',prev_dept_id='" . $app_res['prev_dept_id'] . "',present_user_id='" . $app_res['present_user_id'] . "',temp_volume_folder='" . $app_res['temp_volume_folder'] . "',temp_v_issue_id='" . $app_res['temp_v_issue_id'] . "',type='" . $app_res['type'] . "',page_count='" . $app_res['page_count'] . "',notes='" . $app_res['notes'] . "',img_disk_count='" . $app_res['img_disk_count'] . "',img_redraw_count='" . $app_res['img_redraw_count'] . "',img_relabel_count='" . $app_res['img_relabel_count'] . "',img_color_figures='" . $app_res['img_color_figures'] . "',reminder_mail='" . $app_res['reminder_mail'] . "',stage_with='AUTHOR' WHERE pub_id='" . $app_res['pub_id'] . "'";
        $xpertrack_db->query($update_inwrd_dtl);
		
    }
	
	

	
	
    $p_id = $_POST['pub_id'];
    $password = randomPassword(12);
    $spassword = randomPassword(4);
    $author_name = $_POST['txtToName'];
    $author_email = $_POST['txtTo'];
    $status = "1";
    $EditingStatus = $_POST['EditingStatus'];
    $work_mode = $_POST['work_mode'];
    $sent_date = date('Y-m-d H:i:s');
    $stage = $_POST['stage'];
    $correction_type = $_POST['correction_type'];
    $ce_quality = $_POST['ce_quality'];
    $proof_quality = $_POST['proof_quality'];
    $sent_by = $_COOKIE['user_id'];
    $sent_on = date('Y-m-d H:i:s');
    $gen_notes = $_POST['gen_notes'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
	
	
	//// Insert Details in inw_proof_dtl  AWS DB  ////
	
	/// Checking the AWS DB for existing article Proofing Table values ////
    $proof_detl = "SELECT * FROM inw_proof_dtl where pub_id ='" . $app_res['pub_id'] . "';";
    $proof_query = $xpertrack_db->query($proof_detl) or die($xpertrack_db->error);
    $proof_run = $proof_query->fetch_assoc();
	$num_row_proof = $proof_query->num_rows;
    if ($num_row_proof <= 0) {
    /// $app_res in this variable we are geting the fulle article details ////
    $serv_inser_query = "INSERT INTO `inw_proof_dtl`(`project_id`, `pub_id`, `password`, `spassword`, `author_name`, `author_email`, `status`, `EditingStatus`, `work_mode`, `sent_date`, `stage`, `correction_type`, `ce_quality`, `proof_quality`, `sent_by`, `sent_on`, `gen_notes`, `start_date`, `end_date`,`u_pub_id`) VALUES ('" . $project_id . "','" . $app_res['pub_id'] . "', '" . $password . "', '" . $spassword . "', '" . $author_name . "', '" . $author_email . "', '" . $status . "', '" . $EditingStatus . "', '" . $work_mode . "', '" . $sent_date . "', '" . $stage . "', '" . $correction_type . "', '" . $ce_quality . "', '" . $proof_quality . "', '" . $sent_by . "', '" . $sent_on . "', '" . $gen_notes . "', '" . $start_date . "', '" . $end_date . "', '" . $app_res['pub_id'] . "')";

    $pubs_id = $app_res['pub_id'];
    $xpertrack_db->query($serv_inser_query);
    $pd_id = mysqli_insert_id($xpertrack_db);
	
	
	//// Insert Details in inw_proof_dtl  XPERTTRACK LINUX SERVER DB  ////
        /// Checking the XPERTTRACK DB for existing article Proofing Table values ////
        $proof_detl_xpertrack = "SELECT * FROM inw_proof_dtl where pub_id ='" . $app_res['pub_id'] . "';";
        $proof_query_xpertrack = $xpertrack_db->query($proof_detl_xpertrack) or die($xpertrack_db->error);
        $proof_run_xpertrack = $proof_query_xpertrack->fetch_assoc();
        $num_row_proof_xpertrack = $proof_query_xpertrack->num_rows;
        if ($num_row_proof_xpertrack >0) {
         $deletepubid_prooftablexpertrack=mysqli_query($xpertrack_db,"delete from inw_proof_dtl where pub_id='".$app_res['pub_id']."'");
        }
	
    $serv_inser_query_xt = "INSERT INTO `inw_proof_dtl`(`aws_project_id`,`aws_proof_id`,`project_id`, `pub_id`, `password`, `spassword`, `author_name`, `author_email`, `status`, `EditingStatus`, `work_mode`, `sent_date`, `stage`, `correction_type`, `ce_quality`, `proof_quality`, `sent_by`, `sent_on`, `gen_notes`) VALUES ('" . $project_id . "','" . $pd_id . "','" . $inwed_run_xperttrack['id'] . "','" . $app_res['pub_id'] . "', '" . $password . "', '" . $spassword . "', '" . $author_name . "', '" . $author_email . "', '" . $status . "', '0', '0', '" . $sent_date . "', '" . $stage . "', '0', '0', '0', '" . $sent_by . "', '" . $sent_on . "', '0')";

    $pubs_id = $app_res['pub_id'];
    $xpertrack_db->query($serv_inser_query_xt) or die($xpertrack_db->error);
    $xpertrack_pd_id = mysqli_insert_id($xpertrack_db);
	
	   $update_tracker_articles=mysqli_query($xpertrack_db,"UPDATE article_tracker set first_proof_sent_to_author='".$sent_date."' where pub_id='".$app_res['pub_id']."' ");

	//// Insert Details in inw_proof_dtl local  DB  //// 
	$dispat_qry = "INSERT INTO `inw_proof_dtl` (`project_id`, `password`, `spassword`, `status`, `sent_date`, `stage`, `sent_by`, `sent_on`) VALUES ('".$project_id."', '".$password."', '".$spassword."', '".$status."', '".$sent_date."', '".$stage."', '".$sent_by."', '".$sent_on."')";
	$db->query($dispat_qry);
	$pd_id=mysqli_insert_id($db);
	
	 $update_inwrd_dtl_xperttrack = "UPDATE `inw_inward_dtl` SET stage_with='AUTHOR' WHERE id='" . $inwed_run_xperttrack['id'] . "'";
     $xpertrack_db->query($update_inwrd_dtl_xperttrack);
	
	
	}
	else
	{
	header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $app_res['id'] . "&reset=1&msg=Article Trying to Upload Already exist in Server.Please delete and try again ");exit;
	}
	

    $filename = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/Delivery/".$app_res['pub_id'].".zip";
    $name = $app_res['pub_id'] . ".zip";
	//$filename = "C:/LAMP/Apache/htdocs/workflow/uploads/uploads.zip";
    //$name = "uploads.zip";




//LINUX SERVER FP FILE UPLOAD FROM TRANSFORMA
    
	// $destination_file = "/In/".$_POST['cust_name']."/".$_POST['j_code']."/". $app_res['pub_id']."/"; //where you want to throw the file on the webserver (relative to your login dir)

     $destination_file_linux = $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/" . $app_res['pub_id']."/FP/Proofing/AU/";  // Upload directory */`

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

            if (ftp_mkdir($conn_id_linux, $ftp_linux_path  . "/" . $_POST['j_code'] . "/")) {

                echo "Successfully created $destination_file_linux"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/" . $app_res['pub_id'] . "/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/" . $app_res['pub_id'] . "/FP/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/" . $app_res['pub_id'] . "/FP/Proofing/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
            if (ftp_mkdir($conn_id_linux, $ftp_linux_path . "/" . $_POST['j_code'] . "/Vol00000/" . $app_res['pub_id'] . "/FP/Proofing/AU/")) {
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

//LINUX SERVER FP FILE UPLOAD FROM TRANSFORMA


    if (!$upload)
    {
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
   
        header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $app_res['id'] . "&msg=File upload FTP Issue, Please try again.");
        exit;
        
       
    }

else{
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
        
        
    header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $app_res['id'] . "&msg=Source file path is missing please check and try again.");
        exit;
}

ftp_close($conn_id);
/////////// Send proofing link to author ///////

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

	$body = str_replace('##AuthorNames##', $_POST['txtToName'], $body);
	$body = str_replace('##ARTICLETITLE##', $app_res['article_title'], $body);
    $body = str_replace('##FileName##', $_POST['article_id'], $body);
	$body = str_replace('##HOSTNAME##', Linux_Proofing_URL  ."Transforma_XPro/transoct/user_xpertproof/user_editor.php?fname=".$app_res['pub_id']."&e=TRANSFORMA&ut=AU", $body);
    

    $Subject = str_replace('##FileName##', $_POST['article_id'], $Subject);




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


    if (!$mail->Send()) {

        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
			//echo $prj_update_query; exit;
		$db->query($prj_update_query);


        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {
        echo "Message sent!";
    }
	
	
?>
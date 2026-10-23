<?php /* Project Dispatch v2 rollout switch (same idea as start_work.php).
   - Everybody is sent to send_to_client_v2.php; the old page opens ONLY after the user used "Use old version" on the new page and gave a reason
     (that step leaves a ticket in the session for the article and adds &legacy=1). Anybody who types send_to_client.php / sendtoclient/.. without
     that ticket is sent to the new page, which tells them why with a pop-up (sc_direct) and highlights the "Use old version" button.
   - Emergency off switch: create an empty file named send_to_client_v2.disabled next to this script (everybody is back on this page at once). */
if (isset($_COOKIE['user_id']) && isset($_COOKIE['set_url']) && is_file(__DIR__.'/send_to_client_v2.php') && !is_file(__DIR__.'/send_to_client_v2.disabled')) {
	if (session_status() === PHP_SESSION_NONE) { @session_start(); }
	$__aid = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$__ticket = isset($_SESSION['sc_legacy_ok'][$__aid]) ? (int)$_SESSION['sc_legacy_ok'][$__aid] : 0;
	/* a reason was given for this article in the last 8 hours; the old page's own error redirects (msg=) and its form post keep working */
	$__allowed = $__ticket > 0 && (time() - $__ticket) < 28800 && (isset($_GET['legacy']) || isset($_GET['msg']) || $_SERVER['REQUEST_METHOD'] === 'POST');
	if ($__allowed) { session_write_close(); }   /* hand the session back: includes/check.php starts it itself */
	if (!$__allowed) {
		$__q = array_intersect_key($_GET, array_flip(array('id', 'msg')));
		if (!isset($__q['id']) && $__aid > 0) { $__q['id'] = $__aid; }
		/* the pop-up is for people who typed / bookmarked the old address; a request coming from a page of this same site (the dispatch list,
		   a redirect after an error) is just taken to the new page */
		$__ref  = isset($_SERVER['HTTP_REFERER']) ? (string)parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
		$__host = isset($_SERVER['HTTP_HOST']) ? (string)parse_url('//'.$_SERVER['HTTP_HOST'], PHP_URL_HOST) : '';
		$__from_app = ($__ref !== '' && strcasecmp($__ref, $__host) === 0);
		if (isset($_GET['legacy']))   { $__q['sc_direct'] = 'noreason'; }
		elseif (!$__from_app)         { $__q['sc_direct'] = 'url'; }
		header('Location: '.$_COOKIE['set_url'].'/send_to_client_v2.php?'.http_build_query($__q)); exit;
	}
} ?><?php 
ob_start();
include "includes/includes.php";
ini_set('max_execution_time', 0);
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function randomPassword($len) {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

$dispatch_method = array(1 => 'FTP upload &amp; mail', 2 => 'Only FTP &amp; manual mail', 3 => 'Attach file &amp; mail', 4 => 'xProof upload &amp; mail', 5 => 'EProof');

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    foreach ($_POST as $key => $value) {
        $post[$key] = $value;
    }
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	
		/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();
	
	if($customer_info['cust_name'] == 'PeerJ')
	{
		
		include ("PeerJ_sent_to_client.php");
	}
	else if($customer_info['cust_name'] == 'Sunrise-NOW')
	{
		
		include ("Sunrise_NOW_sent_to_client.php");
	}
	else
	{
			if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2 || $_POST['optionRad'] == 3) 
	{
		  if ($_FILES['fileAttachment']['name'] == "" && sizeof($_POST['attach_files']) == 0) 
		  {
			
			 header("location:send_to_client.php?id=" . $post['id'] . "&msg=File missed, Please check it.");
			 exit;
		  
		  }
	}
	
		 
	if ($_POST['optionRad'] == 4)
	{

        $stage_r=preg_replace("/REV([0-9]+)/","REV",$post['stage']);
        $stage_drft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$post['stage']);

		
		 // To check proof folder exist or not
		if(!is_dir($_POST['work_dir'])) { 
			 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Delivery folder missed, Please check it.");
			 exit;
		}
		
		
		if($post['cust_id']!=66)
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']=='FP')
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $stage_drft=='DRAFT')
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']!='FP' && $stage_drft!='DRAFT')
		{
			
			if($post['corrections']=='yes' || $post['corrections']=='')
			{
				if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".pdf"))  
				{ 
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=PDF File missed in Delivery folder, Please check it.");
				 exit;
				}
			}
            elseif($app_res['wf_id']=='147' && $stage_r=='REV' &&  $post['stage']!='REV1')
            {
                if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".pdf"))  
				{ 
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=PDF File missed in Delivery folder, Please check it.");
				 exit;
				}
            }
             elseif($app_res['wf_id']=='148' && $stage_r=='REV')
            {
                if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
				{ 
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
				 exit;
				}
            }
			else
			{
				if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
				{ 
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
				 exit;
				}	
			}
		}
		
		
		if($post['cust_id']!=66)
		{
			if($post['txtTo'] != $app_res['corresponding_mail'] && $post['txtToName'] != $app_res['corresponding_author'])
			{
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Please check author name and email id!.");
				exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']=='FP')
		{
			if($post['txtTo'] != $app_res['corresponding_mail'] && $post['txtToName'] != $app_res['corresponding_author'])
			{
				 header("location:send_to_client.php?id=" . $post['id'] . "&msg=Please check author name and email id!.");
				exit;
			}
		}
		

		
	}
	
	
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $post['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	$query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();
    /* $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";

    //echo $prj_update_query; exit;
    $db->query($prj_update_query); */

///////////////////////////////////////////////////
/////// Upload the files to Server START HERE /////
///////////////////////////////////////////////////
// get FTP access parameters

    if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2) {

        $current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);



        $host = $_POST['FtpHost'];

        $user = $_POST['FtpUser'];

        $pass = $_POST['FtpPass'];

        $destDir = $_POST['FtpPath'];

        $workDir = "uploads"; // define this as per local system
// get temporary file name for the uploaded file

        $tmpName = basename($_FILES['fileAttachment']['name']);

// copy uploaded file into current directory
        if ($_FILES['fileAttachment']['name'] != "") {
            move_uploaded_file($_FILES['fileAttachment']['tmp_name'], $workDir . "/" . $tmpName) or die("Cannot move uploaded file to working directory");
        }

// open connection

        $conn = ftp_connect($host) or die("Cannot initiate connection to host");

// send access parameters

        ftp_login($conn, $user, $pass) or die("Cannot login");

		// Switched to passive mode after logging in
			ftp_pasv($conn, true);

///// Create DIR ///
        if ($current_stage == 'FP') {
            $destDir .= "/Firstproofs/";
        } else if ($current_stage == 'REV') {
            $destDir .= "/Revises/";
        } else {
            $destDir .= "/" . $current_stage . "/";
        }

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }

///// Create Month and Year DIR ///

        $destDir .= date('Y') . "/";

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }

///// Create Month and DATE DIR ///

        $destDir .= date('md');

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
////// END Create DIR ////
        $email_ftp_path = '';

// perform file upload
        if ($_FILES['fileAttachment']['name'] != "") {
            $upload = ftp_put($conn, $destDir . "/" . $_FILES['fileAttachment']['name'], $workDir . "/" . $tmpName, FTP_BINARY);

            $email_ftp_path .= "ftp://" . $user . "@" . $host . $destDir . "/" . $_FILES['fileAttachment']['name'] . "<br>";
            // delete local copy of uploaded file
            unlink($workDir . "/" . $tmpName) or die("Cannot delete uploaded file from working directory — manual deletion recommended");
        }

        foreach ($_POST['attach_files'] as $file) {
            $upload = ftp_put($conn, $destDir . "/" . $file, $_POST['work_dir'] . "/" . $file, FTP_BINARY);

            $email_ftp_path .= "ftp://" . $user . "@" . $host . $destDir . "/" . $file . "<br>";
        }

//var_dump($upload);
// check upload status
// display message

        if (!$upload) {

            echo "Cannot upload to FTP";
        } else {

            echo "Upload complete";
        }


// close the FTP stream

        ftp_close($conn);
    }

//exit;
////////////////////////////////////////
////// End files upload to server //////
////////////////////////////////////////			
////// send mail to clinet with attachment ////
    /* $smtp_host 		= "ssl://smtp.gmail.com";
      $smtp_user 		= "trans.robatic.msg@gmail.com";
      $smtp_password 	= "Technet16";
      $smtp_port 		= "465";
      $smtp_ssl 		= "true";

      $smtp_host 		= "smtp.office365.com";
      $smtp_port 		= "587";
      $smtp_ssl 		= "false";
      $smtp_user 		= $_POST['txtFrom'];
      $smtp_password 	= $_POST['txtMpass']; */

    if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 3) {

        $mail_from = $_POST['txtFrom'];
        $mail_from_name = "";


        $mail_to_name = "";

        date_default_timezone_set('Asia/Kolkata');

        $body = str_replace('ARTICLEID', $_POST['article_id'], $_POST['txtBody']);
        $body = str_replace('CLIENTNAME', $_POST['cust_name'], $body);
        $body = str_replace('AUTHORNAME', $_POST['txtToName'], $body);
		$body= str_replace("##AuthorNames##",$_POST['txtToName'],$body);

		
//echo $app_res['cust_id'];exit;
        if ($app_res['cust_id'] == 39 || $app_res['cust_id'] == 37) {
            $body = str_replace('FTPFILEPATH', $destDir . "/" . $_FILES['fileAttachment']['name'], $body);
        } else {
            $body = str_replace('FTPFILEPATH', $email_ftp_path, $body);
        }

        $querysrc = $_POST['work_dir'];
        $querystr = "";
        $i = new DirectoryIterator($querysrc);
        foreach ($i as $f) {
            if ($f->isFile()) {

                $filename = $f->getFilename();
                if (strpos($filename, '_Query.txt') !== false) {
                    $file = fopen($querysrc . $filename, "r");
                    while (!feof($file)) {
                        $querystr .= str_replace("\n", "<br/>", fgets($file));
                    }
                    $querystr = "---------- Query ----------<br/>" . $querystr . "<br/><br/>---------------------------<br/>";
                    break;
                }
            }
        }

        $body = str_replace('AUTHORQUERY', $querystr, $body);
//exit;


		$sub_mail= $journal_info['j_code']."--".substr($app_res['article_title'],0,40)."... [".$app_res['pub_id']."]";

        include_once("smtpmail/phpmailer/class.phpmailer.php");

        $mail = new PHPMailer();
        $mail->IsSMTP();		
		 if($app_res['cust_id']!=37)
		{
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';

		}
		else
		{
		$mail->CharSet = 'UTF-8';
        $mail->Host = "smtp.gmail.com";
		$mail->Port = 465;
		$mail->SMTPSecure = "ssl";	
        } 
		/* $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587; */
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
		 if($app_res['cust_id']!=37)
		{
        $mail->Subject = str_replace('ARTICLEID', $_POST['article_id'], $_POST['txtSub']);
		}
		else
		{
		//$mail->Subject = str_replace('##FileName##',$sub_mail, $_POST['txtSub']);
		//$mail->Subject =$_POST['txtSub'];
		$mail->Subject ='=?UTF-8?B?'.base64_encode($_POST['txtSub']).'?=';

		}
        $mail->Body = $body;


//// Send mail to more than one user ///
        $mail_to = explode(',', $_POST['txtTo']);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


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

         if ($_POST['optionRad'] == 3 || $_POST['optionRad'] == 4) {
            // $mail->AddAttachment($_POST['work_dir']."Rouzies Onyemah final draft (2).pdf");      // attachment
            if ($_FILES['fileAttachment']['name'] != "") {
                $mail->AddAttachment($_FILES['fileAttachment']['tmp_name'], $_FILES['fileAttachment']['name']);
            }
            //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
            foreach ($_POST['attach_files'] as $file) {
                $mail->AddAttachment($_POST['work_dir'] . $file);
            }
			 foreach ($_POST['attach_filess'] as $file) {
                $mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/" . $file);
            }
			foreach ($_POST['attach_filesss'] as $file) {
                $mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/" . $file);
            }
        } 

        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
			header("location:".$_COOKIE['set_url']."/send_to_client.php?id=" . $post['id'] . "&msg=Mailer Sending Error. Please try again");
			 exit;
        } else {	
            echo "Message sent!";
        }
        //exit;
    }

    //// FTP UPLOAD TO LINUX SERVER FOR CLIENT PORTAL AND LATEX EDITOR -- START
    if($_POST['optionRad'] == 1 && $app_res['cust_id']==7)
    {
        if($post['stage']=='FP' && $app_res['pub_id']=='EPL_6767')
        {
     
         //// latex editor ftp upload
        include('linux_sif_latex_ftp_upload.php');

         //// client portal  ftp upload
        include('linux_sif_clientportal_ftp_upload.php');
        }
    }
	
	
	
	
	//// XPERTTRACK SYSTEM UPLOAD FOR IAP CUSTOMERS
	
	if ($_POST['optionRad'] == 6) 
	{
		if($app_res['cust_id'] == 57 || $app_res['cust_id'] == 58)
		{
			
					
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
			
			if($app_res['stage']=='FP')
			{
                include ('linux_iap_client_portal_fp.php');
			}
			elseif($stage_check=='FIN')
            {
                include ('linux_iap_client_portal_fin.php');
            }
			elseif($stage_pmc=='PMC')
            {
                include ('linux_iap_client_portal_pmc.php');
            }
            else
            {
                include ('linux_iap_client_portal_rev.php');
            }
		}	
		
		if($app_res['cust_id'] == 53)
		{
			
					
            $stage_check=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);
           // $stage_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
			
			if($app_res['stage']=='FP')
			{
                include ('linux_now_client_portal_fp.php');
			}
			elseif($stage_check=='REV')
            {
                include ('linux_now_client_portal_rev.php');
            }
            else
            {
                include ('linux_now_client_portal_fin.php');
            }
		}
		
	}
	
	//// XPERTTRACK SYSTEM UPLOAD FOR IAP CUSTOMERS


    //// FTP UPLOAD TO LINUX SERVER FOR CLIENT PORTAL AND LATEX EDITOR -- END


    if ($_POST['optionRad'] == 4) 
	{

		if($app_res['cust_id'] == 43)
		{
			include ('jmir_xpertproofing.php');
			// include ('jmir_upload_file.php');
		}
		else if($app_res['cust_id'] == 42 && $journal_info['j_proofing_system_server']=='AWS1')
		{
			include ('tsp_xpertproofing.php');
		}
		else if($app_res['cust_id'] == 84)
		{
			include ('aws_xpertproofing_xpro.php');
		}
		else if($app_res['cust_id'] == 42  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='0')
		{
			include ('aws_xpertproofing_xpro.php');
		}
		else if($app_res['cust_id'] == 42  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='1')
		{
			include ('aws_xpertproofing_xpro_xperttrack.php');
		}
		else if($app_res['cust_id'] == 72  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='1')
		{
			include ('aws_xpertproofing_xpro_xperttrack_ksu.php');
		}
        else if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS1' && $app_res['stage']== 'FP')
		{
			include ('aws_eos_xpertproofing_xpro.php');
		}
        else if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['stage']== 'FP')
		{
			include ('aws_eos_xpertproofing_xpro.php');
		}
		
		else if($app_res['cust_id'] == 42 && $journal_info['j_proofing_system_server']=='GODADDY' && $app_res['stage']== 'FP')
		{
			include ('godaddy_xpertproofing.php');
		}
        else if($app_res['cust_id'] == 76 && $app_res['stage']== 'FP')
		{
			//echo "test dev jrnl";exit;
			include('linux_devjrnl_xpertproofing_xpro.php');
		}
		 else if($app_res['cust_id'] == 77 && $app_res['stage']== 'FP')
		{
			//echo "acg jrnl";exit;
			include('aws_xpertproofing_xpro_xperttrack_acg.php');
		}
		 else if($app_res['cust_id'] == 79 && $app_res['stage']== 'FP')
		{
			//echo "csiro jrnl";exit;
			//include('aws_xpertproofing_xpro_xperttrack_csiro.php');
			include('xpertproofing_xpro_xperttrack_csiro.php');
		}
		else if ($app_res['cust_id'] == 66 && $app_res['stage']!= 'FP') {
			
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
			
			 if($stage_check_draft=='DRAFT')
            {
                include ('linux_eos_client_portal_draft.php');

            }
            elseif($stage_check=='FIN')
            {
                include ('linux_eos_client_portal_fin.php');

            }
            else
            {
                include ('linux_eos_client_portal_rev.php');

            }
			//include ('aws_eos_client_portal_rev.php');
            
        }
		else if ($app_res['cust_id'] == 79 && $app_res['stage']!= 'FP') {
			
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
			
			 if($stage_check_draft=='DRAFT')
            {
                include ('linux_csiro_client_portal_draft.php');

            }
            elseif($stage_check=='FIN')
            {
                include ('linux_csiro_client_portal_fin.php');

            }
            else
            {
                include ('linux_csiro_client_portal_rev.php');

            }
			//include ('aws_eos_client_portal_rev.php');
            
        }
		else if($app_res['cust_id'] == 42 && ($journal_info['j_proofing_system_server']=='' || $journal_info['j_proofing_system_server']=='NULL'))
		{
			include ('tsp_xpertproofing.php');
			// include ('tsp_proofing.php');
		}
		else if($app_res['cust_id'] == 44)
		{
			include ('plos_xpertproofing.php');
			// include ('tsp_proofing.php');
		}
	}
	
	if ($_POST['optionRad'] == 5) {
		
		
		if($app_res['cust_id'] == 34 && $_POST['stage'] == "FP")
		{
			include ('now_eproofing.php');
		
		}
		
	}


/// Here Update Article status to Client Review

	$set_status = "`status` ='Client Review',";
    $user_working_status = '<span class="label label-sm label-success">Client Review</span>';

    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";

    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0',`user_working_status`='".$user_working_status."' WHERE id = " . $post['id'] . "";

    //echo $prj_update_query; exit;
    $db->query($prj_update_query);


///// Add records in Artile Dispatch history ///

    $dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $app_res['cust_id'] . "', '" . $app_res['j_id'] . "', '" . $app_res['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body . "', '" . $_COOKIE['user_id'] . "', '" . $app_res['stage'] . "', NOW(),'" . $dispatch_method[$_POST['optionRad']] . "')";
    $db->query($dispat_qry);


    header("location:".$_COOKIE['set_url']."/underdispatcharticles?msg=Successfully completed");
}
}
/////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
	
	
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
        $app_res['volume_folder'] = $app_res['volume_folder'] . $app_res['issueorgroup_folder'];
    }

/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();

/// Get the journal name ///
    $query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();

    /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##ARTICLETITLE##",$app_res['article_title'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
		/// Changes the Subject and Body Text for NOW Publisher ///
if($customer_info['cust_name'] == "Sunrise-NOW" || $customer_info['cust_name'] == "TSP")
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($customer_info['cust_name'].":". $journal_info['j_code'].":".$app_res['pub_id'].":".$app_res['page_count']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
			//##AuthorNames##
		
	}
	
	$sub_mail= $journal_info['j_code']."--".substr($app_res['article_title'],0,40)."... [".$app_res['pub_id']."]";

	
}
//echo $journal_info['j_proofing_system_server'];
//echo $transaction_id;

if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS2')
{
include ('linux_db.php');
	
	    /// Checking the EOS XPERTTRACK DB for existing article values ////
$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='1'"));

}
if(($app_res['cust_id'] == 57 || $app_res['cust_id'] == 58))
{
include ('linux_db_iap.php');
	
/// Checking the IAP XPERTTRACK DB for existing article values ////

$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='1'"));

}
elseif($app_res['cust_id'] == 53)
{
include ('linux_db_now.php');
	
/// Checking the NOW XPERTTRACK DB for existing article values ////

$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='1'"));

}
else if($app_res['cust_id'] == 66 && $journal_info['j_proofing_system_server']=='GODADDY')
{
include ('godaddy-client-portal/godaddydb_new.php');
}
if($app_res['cust_id'] == 7 && $app_res['stage'] == 'FP' && $app_res['pub_id']=='EPL_6767')
{
include ('linux_sif_db.php');
	
/// Checking the LINUX SIF DB for existing article values ////
$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $linuxsif_db->query($inwerd_detl) or die($linuxsif_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$linux_inw_id=$inwed_run['id'];

}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
     <meta charset="UTF-8" />
     <meta name="viewport" content="width=device-width, initial-scale=1.0" />   <!-- without this phones show the desktop layout shrunk -->
     <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS --->
     <style>
/* ===== Project Dispatch (send to client) - responsive layout =====
   Only the look changes: field names, ids, the show / hide of #div1 / #emaildiv / #reminder and the page scripts are untouched. */
.wholeouter{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;--ok:#1e9e61;--bad:#d64545;}
body{background:#f3f6fa;}
.wholeouter *{box-sizing:border-box;}
@keyframes stcUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:none;}}
@keyframes stcShine{0%{transform:translateX(-120%) skewX(-20deg);}100%{transform:translateX(320%) skewX(-20deg);}}

/* ---- title bar ---- */
.stc-hero{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;width:calc(100% - 2 * clamp(10px,2.5vw,24px));max-width:1180px;margin:14px auto 14px;padding:16px 22px;border-radius:16px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 8px 22px rgba(20,48,74,.2);animation:stcUp .45s ease both;}
.stc-hero:before{content:"";position:absolute;top:0;bottom:0;width:26%;left:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.13),transparent);animation:stcShine 6s ease-in-out infinite;}
.stc-hero>*{position:relative;}
.stc-hero small{display:block;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.stc-hero h1{margin:0;font-size:22px;font-weight:700;color:#fff;line-height:1.2;}
.stc-back{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);color:#fff !important;font-weight:600;text-decoration:none !important;transition:transform .2s,background .2s;}
.stc-back:hover{background:rgba(255,255,255,.26);transform:translateX(-3px);}
.stc-wrap{max-width:1180px;margin:0 auto;padding:0 clamp(10px,2.5vw,0px);}
.stc-msg{display:flex;flex-direction:column;align-items:center;gap:8px;max-width:1180px;margin:0 auto 14px;}
.stc-msg span{padding:8px 16px !important;height:auto !important;text-align:center;}

/* ---- layout shell: cards instead of the old nested boxes ---- */
.divouter{display:block !important;padding:0 clamp(10px,2.5vw,24px) 40px !important;margin-top:0 !important;}
.form_outer{width:100% !important;max-width:1180px;margin:0 auto;padding:0 !important;display:block !important;background:transparent !important;}
.form_inner{width:100% !important;padding:0 !important;display:block !important;background:transparent !important;}
.form_inner>.container,.form_inner form>.container,.form_inner form>#div1,.form_inner form>#emaildiv{
  width:100%;max-width:100% !important;margin:0 0 16px !important;padding:20px 22px !important;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 12px rgba(20,48,74,.06);animation:stcUp .45s ease both;}
.form_inner .container.mt-4,.form_inner .container.mt-5,.form_inner .container.mb-4,.form_inner .container.mb-5{margin-top:0 !important;margin-bottom:16px !important;}
#div1 .container,#emaildiv .container{max-width:100% !important;padding:0 !important;margin:0 !important;}
#div1 .row,#emaildiv .row{margin:0 !important;--bs-gutter-x:0;padding-top:0 !important;}
#div1 .row>*,#emaildiv .row>*{padding:0 !important;width:100% !important;max-width:100%;}
.form_inner form{margin:0;}
.form_inner>.container.stc-formwrap{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;animation:none;}

/* section headings (were absolutely positioned) */
.formcont1heading{position:static !important;display:block !important;width:100% !important;height:auto !important;margin:0 0 14px !important;padding:0 0 10px !important;border-bottom:1px solid var(--line);background:transparent !important;color:var(--navy) !important;z-index:auto !important;}
/* the theme draws this heading as a black arrow tab - switch that off */
.formcont1heading:before,.formcont1heading:after{display:none !important;content:none !important;}
.formcont1heading h3,.widget-title{display:flex;align-items:center;gap:8px;margin:0 !important;font-size:13px !important;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--navy);}
.formcont1heading h3:before{content:"";width:4px;height:16px;border-radius:3px;background:var(--accent);}
.widget-title i{color:var(--accent);}
#widget-box-2 .widget-body{padding:0 !important;} #widget-box-2 .widget-main{padding:0 !important;}

/* ---- article information: table -> tiles ---- */
#widget-box-2 table{display:block;width:100%;margin:0;border:0 !important;background:transparent;}
#widget-box-2 tbody{display:grid !important;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px;border:0 !important;}
#widget-box-2 tr{display:flex;flex-direction:column;gap:3px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:#f9fbfd;transition:box-shadow .2s,transform .2s;}
#widget-box-2 tr:hover{box-shadow:0 8px 18px rgba(20,48,74,.1);transform:translateY(-2px);}
.table-hover>tbody>tr:hover>*{--bs-table-accent-bg:transparent;}
/* Bootstrap's table-hover paints every cell with an inset box-shadow (the grey bars on hover) - switch it off for the tiles */
#widget-box-2 table,#widget-box-2 tbody,#widget-box-2 tr,#widget-box-2 td{--bs-table-bg:transparent;--bs-table-accent-bg:transparent;--bs-table-hover-bg:transparent;--bs-table-striped-bg:transparent;}
#widget-box-2 tr:hover>*,#widget-box-2 td{box-shadow:none !important;color:inherit;}
#widget-box-2 td{display:block !important;width:auto !important;padding:0 !important;border:0 !important;background:transparent !important;word-break:break-word;}
#widget-box-2 tr:hover td{background:transparent !important;}
#widget-box-2 td.tdstyle{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--mute);}
#widget-box-2 td+td{font-size:14px;font-weight:600;color:var(--navy);}
#widget-box-2 tr:nth-last-child(-n+2){grid-column:1/-1;}
#widget-box-2 tr:nth-last-child(-n+2) td+td{font-weight:500;color:var(--ink);}

/* ---- dispatch method: radio chips ---- */
.radio_btn{margin:0 0 16px !important;height:auto !important;}
.form_inner .radio_btn .form-group:not(#reminder){display:block !important;width:100% !important;margin:0 !important;}
.radio_btn .control-label{display:block;width:auto !important;float:none !important;padding:0 0 10px !important;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--navy) !important;}
.radio_btn .d-flex{display:flex !important;flex-wrap:wrap;gap:10px !important;width:100% !important;}
.radio_btn input[type=radio]{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;}
.radio_btn input[type=radio]+label{display:inline-flex;align-items:center;gap:10px;margin:0;padding:11px 16px;border:1px solid #d5dde6;border-radius:12px;background:#fff;color:var(--ink);font-weight:600;font-size:13.5px;cursor:pointer;transition:all .2s;}
.radio_btn input[type=radio]+label:before{content:"";flex:0 0 auto;width:16px;height:16px;border-radius:50%;border:2px solid #b3bcc7;background:#fff;transition:all .2s;}
.radio_btn input[type=radio]+label:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:0 8px 16px rgba(20,48,74,.1);}
.radio_btn input[type=radio]:checked+label{background:#eaf2fa;border-color:var(--blue);color:var(--blue);box-shadow:0 6px 16px rgba(27,108,168,.18);}
.radio_btn input[type=radio]:checked+label:before{border-color:var(--blue);background:radial-gradient(circle,var(--blue) 0 4px,#fff 5px);}
.radio_btn input[type=radio]:focus-visible+label{outline:3px solid rgba(27,108,168,.35);outline-offset:2px;}

/* ---- form rows: label | field on desktop, stacked on phones ---- */
.form_inner .form-group{display:grid;grid-template-columns:190px minmax(0,1fr);gap:6px 18px;align-items:start;width:100% !important;margin:0 0 14px !important;}
/* Bootstrap's .d-flex is !important - beat it, except for #reminder whose inline show()/hide() must keep working */
.form_inner .form-group:not(#reminder){display:grid !important;}
.form_inner .form-group>div.d-flex,.form_inner .form-group>div{display:flex;}
.form_inner .form-group>.d-flex>input.form-control,.form_inner .form-group>div>input.form-control{flex:1 1 220px;min-width:0;}
.form_inner .form-group>label.control-label,.form_inner .form-group>label{width:auto !important;max-width:none !important;float:none !important;text-align:left !important;margin:0;padding:11px 0 0 !important;font-size:13px;font-weight:600;color:var(--navy) !important;}
.form_inner .form-group>div{width:100% !important;max-width:100% !important;padding:0 !important;flex-wrap:wrap;gap:8px !important;}
.form_inner .form-control,.form_inner input[type=text],.form_inner input[type=email],.form_inner input[type=password],.form_inner input[type=file]{
  width:100% !important;max-width:100% !important;height:42px;padding:0 12px;border:1px solid #d5dde6 !important;border-radius:10px !important;background:#fff;color:var(--ink);font-size:14px;box-shadow:none !important;transition:border-color .2s,box-shadow .2s;}
.form_inner input[type=file]{padding:8px 12px;height:auto;background:#f9fbfd;}
.form_inner .form-control:focus,.form_inner input:focus{outline:0;border-color:var(--blue) !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;}
.form_inner input[readonly]{background:#f3f6fa;color:var(--mute);}
.form_inner .form-group label.error,.form_inner label.error{color:var(--bad);font-size:12px;margin:2px 0 0;font-weight:500;}
.form_inner input.error{border-color:var(--bad) !important;}
.form_inner .form-group>div>*{max-width:100%;}

/* mail body editor */
.richText{width:100% !important;max-width:100% !important;border-radius:12px;overflow:hidden;border:1px solid #d5dde6 !important;}
.richText .richText-toolbar{background:#f6f9fc;border-bottom:1px solid var(--line);}
.richText .richText-toolbar ul{display:flex;flex-wrap:wrap;}
.richText .richText-editor{min-height:200px;max-width:100%;overflow:auto;border:0 !important;padding:12px 14px;}
#txtBody{width:100% !important;min-height:200px;}

/* work dir links, AWS button, file pick-list */
.form_inner .btn.btn-primary{display:inline-flex !important;align-items:center;gap:8px;margin:0 8px 8px 0;padding:9px 18px !important;border:0 !important;border-radius:20px !important;background:linear-gradient(135deg,#14304a,#1f4b73) !important;color:#fff !important;font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 3px 8px rgba(20,48,74,.25);transition:transform .15s,box-shadow .2s;}
.form_inner .btn.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(20,48,74,.32);}
.form_inner .checkbox{padding:0 !important;}
.form_inner .checkbox .row{display:block;margin:0 0 8px !important;}
.form_inner .checkbox .row>*{padding:0 !important;width:100%;}
.form_inner .checkbox label{display:flex;align-items:center;flex-wrap:wrap;gap:8px;width:100%;margin:0;padding:10px 14px;border:1px solid #edf1f5;border-radius:10px;background:#f9fbfd;cursor:pointer;word-break:break-all;transition:background .2s,border-color .2s;}
.form_inner .checkbox label:hover{background:#eef5fb;border-color:#c5daef;}
#total_file_size_div{display:inline-block;margin-top:8px;padding:6px 14px;border-radius:20px;background:#fdeee3;color:var(--accent);font-weight:700;}

.form_inner .form-group .btn.btn-danger{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 16px !important;border-radius:10px !important;border:1px solid #f3c4c4 !important;background:#fdeaea !important;color:#c0392b !important;font-weight:700;font-size:13.5px;transition:all .2s;}
.form_inner .form-group .btn.btn-danger:hover{background:var(--bad) !important;border-color:var(--bad) !important;color:#fff !important;}


/* ---- who is uploading + priority ---- */
.stc-sub{margin-top:4px;font-size:13px;opacity:.9;} .stc-sub i{margin-right:6px;} .stc-sub b{font-weight:700;}
.stc-hero-r{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;}
.stc-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border-radius:20px;font-weight:700;font-size:12.5px;white-space:nowrap;background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.28);}
.stc-hero .stc-chip.high{background:#d64545;border-color:#d64545;box-shadow:0 4px 12px rgba(214,69,69,.45);}
.stc-hero .stc-chip.late{background:#e8742a;border-color:#e8742a;box-shadow:0 4px 12px rgba(232,116,42,.45);animation:stcPulse 1.8s ease-in-out infinite;}
.stc-hero .stc-chip.today{background:#1b9cc2;border-color:#1b9cc2;}
.stc-hero .stc-chip.ok{background:rgba(30,158,97,.9);border-color:transparent;}
@keyframes stcPulse{0%,100%{box-shadow:0 0 0 0 rgba(232,116,42,.5);}50%{box-shadow:0 0 0 8px rgba(232,116,42,0);}}
/* the same chips on the light tiles */
#widget-box-2 .stc-chip{margin:0 6px 4px 0;background:#eef1f5;color:var(--mute);border-color:transparent;font-size:12px;}
#widget-box-2 .stc-chip.high{background:#fde8e8;color:#c0392b;} #widget-box-2 .stc-chip.late{background:#fff0e3;color:#c4590f;}
#widget-box-2 .stc-chip.today{background:#e1effa;color:#1b6ca8;} #widget-box-2 .stc-chip.ok{background:#e3f6ec;color:#1e9e61;}
.stc-who{display:inline-flex;align-items:center;gap:9px;font-weight:700;}
.stc-av{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--navy));color:#fff;font-size:12px;}
.stc-note{display:block;margin-top:3px;color:var(--mute);font-weight:500;font-size:11.5px;}
#widget-box-2 tr.stc-tile-by,#widget-box-2 tr.stc-tile-pri{background:#f3f8ff;border-color:#cfe1f3;}
@media (max-width:640px){.stc-hero-r{justify-content:flex-start;width:100%;} .stc-chip{font-size:12px;padding:6px 11px;}}
/* ---- actions ---- */
.stc-actions{background:#fff;}
.form_inner .stc-actions .form-group:not(#reminder){display:block !important;margin:0 !important;}
.stc-actions .form-group>div{display:flex !important;flex-wrap:wrap;justify-content:flex-end;gap:10px !important;}
.stc-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 24px !important;border-radius:12px !important;font-weight:700;font-size:14px;text-decoration:none !important;cursor:pointer;transition:transform .15s,box-shadow .2s,background .2s,color .2s;}
.stc-actions .btn-info{border:0 !important;background:linear-gradient(135deg,var(--accent),#f08a45) !important;color:#fff !important;box-shadow:0 6px 16px rgba(232,116,42,.35);min-width:150px;}
.stc-actions .btn-info:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(232,116,42,.45);}
.stc-actions .btn-grey{border:1px solid #d5dde6 !important;background:#fff !important;color:var(--navy) !important;}
.stc-actions .btn-grey:hover{background:#eef3f8 !important;}
.stc-actions .btn-danger{border:1px solid #f3c4c4 !important;background:#fdeaea !important;color:#c0392b !important;}
.stc-actions .btn-danger:hover{background:var(--bad) !important;border-color:var(--bad) !important;color:#fff !important;}

/* ---- tablet ---- */
@media (max-width:900px){
  .form_inner .form-group:not(#reminder){grid-template-columns:150px minmax(0,1fr);gap:6px 14px;}
  .stc-hero h1{font-size:19px;}
}
/* ---- phone ---- */
@media (max-width:640px){
  .form_inner>.container,.form_inner form>.container,.form_inner form>#div1,.form_inner form>#emaildiv{padding:16px 14px !important;border-radius:14px;}
  .form_inner .form-group:not(#reminder){grid-template-columns:1fr;gap:4px;margin-bottom:12px !important;}
  #reminder{grid-template-columns:1fr;}
  .form_inner .form-group>label.control-label,.form_inner .form-group>label{padding:0 !important;}
  #widget-box-2 tbody{grid-template-columns:1fr 1fr;}
  .radio_btn input[type=radio]+label{width:100%;}
  .stc-hero{padding:14px 16px;} .stc-back span{display:none;} .stc-back{padding:10px 12px;}
  /* Send / Cancel stay within thumb reach while the long form scrolls */
  .stc-actions{position:sticky;bottom:0;z-index:30;margin-left:calc(-1 * clamp(10px,2.5vw,24px)) !important;margin-right:calc(-1 * clamp(10px,2.5vw,24px)) !important;width:auto !important;border-radius:16px 16px 0 0 !important;box-shadow:0 -8px 24px rgba(20,48,74,.14) !important;padding:12px 14px !important;}
  .stc-actions .form-group>div{justify-content:stretch;}
  .stc-actions .btn{flex:1 1 30%;padding:0 12px !important;min-width:0;}
  .stc-actions .btn-info{flex:1 1 100%;order:-1;}
}
@media (max-width:420px){ #widget-box-2 tbody{grid-template-columns:1fr;} }
@media (prefers-reduced-motion:reduce){.wholeouter *,.stc-hero:before{animation:none !important;transition:none !important;}}
img,iframe,table{max-width:100%;}
</style>
  </head>

   <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


    <?php
/* who is dispatching, and how urgent is this article?  (same due-date rule as the article lists) */
$stc_user_name = isset($_COOKIE['full_name']) ? trim($_COOKIE['full_name']) : '';
if ($stc_user_name === '' && isset($_COOKIE['user_id'])) {
	$stc_u = $db->query("SELECT full_name FROM users WHERE id = ".(int)$_COOKIE['user_id']." LIMIT 1");
	if ($stc_u && ($stc_r = $stc_u->fetch_assoc())) { $stc_user_name = trim($stc_r['full_name']); }
}
$stc_last_by = '';
if (!empty($app_res['last_worked_user_id'])) {
	$stc_u = $db->query("SELECT full_name FROM users WHERE id = ".(int)$app_res['last_worked_user_id']." LIMIT 1");
	if ($stc_u && ($stc_r = $stc_u->fetch_assoc())) { $stc_last_by = trim($stc_r['full_name']); }
}
$stc_stage = (string)$app_res['stage'];
$stc_due_raw = '';
if ($stc_stage == 'FP')                              { $stc_due_raw = !empty($app_res['target_due_dt']) ? $app_res['target_due_dt'] : $app_res['due_dt']; }
elseif (preg_match('/^ISSCOR[0-9]+$/', $stc_stage))  { $stc_due_raw = $app_res['due_dt']; }
else {
	$stc_rv = $db->query("SELECT due_date FROM inw_revisions_dtl WHERE inw_id = ".(int)$app_res['id']." ORDER BY r_id DESC LIMIT 1");
	if ($stc_rv && ($stc_r = $stc_rv->fetch_assoc())) { $stc_due_raw = $stc_r['due_date']; }
	if (empty($stc_due_raw) || strpos($stc_due_raw, '0000-00-00') === 0) { $stc_due_raw = $app_res['due_dt']; }
}
$stc_ts   = (!empty($stc_due_raw) && strpos($stc_due_raw, '0000-00-00') !== 0) ? strtotime($stc_due_raw) : false;
$stc_days = $stc_ts ? (int)round((strtotime(date('Y-m-d', $stc_ts)) - strtotime(date('Y-m-d'))) / 86400) : null;   // <0 late, 0 today
$stc_high = ((int)$app_res['highpriority'] === 1);
$stc_chips = '';
if ($stc_high) { $stc_chips .= '<span class="stc-chip high"><i class="fa fa-flag"></i> High priority</span>'; }
if ($stc_days !== null && $stc_days < 0)       { $stc_chips .= '<span class="stc-chip late"><i class="fa fa-exclamation-triangle"></i> Overdue &middot; '.(-$stc_days).' day'.((-$stc_days) === 1 ? '' : 's').'</span>'; }
elseif ($stc_days === 0)                        { $stc_chips .= '<span class="stc-chip today"><i class="fa fa-clock-o"></i> Due today</span>'; }
elseif ($stc_days !== null)                     { $stc_chips .= '<span class="stc-chip ok"><i class="fa fa-calendar-check-o"></i> Due in '.$stc_days.' day'.($stc_days === 1 ? '' : 's').'</span>'; }
else                                            { $stc_chips .= '<span class="stc-chip muted"><i class="fa fa-calendar-o"></i> No due date</span>'; }
?>
<div class="stc-hero">
      <div><small>Dispatch</small><h1>Project Dispatch</h1>
        <div class="stc-sub"><i class="fa fa-user-circle-o"></i> Uploading by <b><?php echo htmlspecialchars($stc_user_name !== '' ? $stc_user_name : 'you'); ?></b></div></div>
      <div class="stc-hero-r">
        <?php echo $stc_chips; ?>
        <a class="stc-back" style="background:#2e8b57;border-color:#2e8b57" title="Package check, dispatch log and delivery-folder checks" href="<?php echo $_COOKIE['set_url'];?>/send_to_client_v2.php?id=<?php echo (int)$app_res['id'];?>"><i class="fa fa-flask"></i><span>Try new version</span></a>
        <a class="stc-back" href="javascript:history.back();"><i class="fa fa-arrow-left"></i><span>Back</span></a>
      </div>
    </div>
<?php 
	  if(isset($_REQUEST['msg']))
	  {
	  ?>
	  
			<div style="width: 100%; display: flex; justify-content: center; ">
        <span style="width:fit-content; height: 30px; color: #fff; background:#ed424a; border-radius: 15px; display: flex; justify-content: center; align-items: center; padding: 5px 10px;"><?php echo htmlspecialchars($_REQUEST['msg']);?></span>
      </div>
	  </br>
	   <div style="width: 100%; display: flex; justify-content: center;">
	  		<a href="https://xpertproof.transforma.in/xpertproofing/Transforma_XPro/xpertproof/delete_article" target="_blank">Click here to Reset</a>
	   </div>
	  <?php 
	  }
	  ?>
	  
	
	  
      <div class="divouter mt-4">
        <div class="form_outer">
          <div class="form_inner d-flex flex-column align-items-center">
         <div class="container" id="widget-box-2">
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 class="widget-title bigger lighter" style="font-size: 1.275em">
              <i class="ace-icon fa fa-comments"></i>
              Article Information
            </h3>            
          </div>

          <div class="widget-body pt-5">
            <div class="widget-main no-padding pt-4">
              <table class="table table-bordered table-hover">
                <tbody style="border: 1px solid #dedef3;">
                  <tr class="stc-tile-by">
                    <td class="tdstyle">Uploading by</td>
                    <td class="hidden-480"><span class="stc-who"><span class="stc-av"><?php echo htmlspecialchars(strtoupper(substr($stc_user_name !== '' ? $stc_user_name : '?', 0, 1))); ?></span><?php echo htmlspecialchars($stc_user_name !== '' ? $stc_user_name : '-'); ?></span><?php echo $stc_last_by !== '' ? '<small class="stc-note">Last worked on by '.htmlspecialchars($stc_last_by).'</small>' : ''; ?></td>
                  </tr>
                  <tr class="stc-tile-pri">
                    <td class="tdstyle">Priority</td>
                    <td class="hidden-480"><?php echo $stc_chips; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Customer Name</td>
                    <td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Journal</td>
                    <td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Stage</td>
                    <td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Article Title</td>
                    <td class="hidden-480"><?php echo $app_res['pub_id']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Received Date</td>
                    <td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Due Date</td>
                    <td class="hidden-480"> <?php
if($app_res['stage']=='FP')
{
echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
}
else
{
echo (date('d-m-Y',strtotime($app_res['due_dt'])));
}
?></td>
                  </tr>
				   <tr>
                    <td class="tdstyle">Insructions</td>
                    <td class="hidden-480"><?php echo $app_res['instructions']; ?></td>
                  </tr>
				   <tr>
                    <td class="tdstyle">Department Comments </td>
                    <td class="hidden-480">  <?php
                                                                        $di_query = "SELECT comments FROM `inw_dept_comments`  WHERE dept_ids IN (" . $app_res['department_id'] . ") ";
                                                                        $di_result = $db->query($di_query);

                                                                        $di_count = $di_result->num_rows;
                                                                        if ($di_count > 0) {
                                                                            $di_res = $di_result->fetch_assoc();
                                                                            echo $di_res['comments'];
                                                                        }
                                                                        ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div
      </div>

  <div class="container stc-formwrap">
           <form method="post" enctype="multipart/form-data">

      <div class="container radio_btn">
        <div class="form-group d-flex">
          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Choose Dispatch Method :</label>
          <div class="col-xs-12 col-sm-12 d-flex gap-3">
              <input id="optionRad_0" name="optionRad" value="1"  type="radio" checked="checked">
              <label for="optionRad_0">FTP upload and mail</label>
              </td>
              <td><input id="optionRad_1" name="optionRad" value="4"  type="radio">
                  <label for="optionRad_1">xProof upload &amp; mail</label></td>
              <td><input id="optionRad_2" name="optionRad" value="2" type="radio">
                  <label for="optionRad_2">Only FTP &amp; manual mail</label></td>
              <td><input id="optionRad_3" name="optionRad" value="3" type="radio">
                  <label for="optionRad_3">Attach file &amp; mail</label></td>
              <td><input id="optionRad_4" name="optionRad" value="5" type="radio">
                  <label for="optionRad_4">EProofing</label></td>
			 <td><input id="optionRad_5" name="optionRad" value="6" type="radio">
                  <label for="optionRad_5">Xperttrack</label></td>
          </div>
      </div>
    </div>
         <div id="div1"> 
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
              <h3 style="font-size: 1.275em"> FTP </h3>
          </div>
          <div class="container mt-4 mb-4 pt-5">
          <div class="row pt-5">
              <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Site URL</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpHost" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_host']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex ">
                      <label class="col-sm-3 control-label no-padding-right">Target DIR</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPath" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                          Note: No slash end of the path </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">User</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpUser" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Password</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPass" type="password"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
      <!-- Email div  -->
      <div  id="emaildiv">
        <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 style="font-size: 1.275em"> E-Mail </h3>
        </div>
        <div class="container mt-4 mb-4 pt-5">
        <div class="row pt-5">
            <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">From</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtFrom" type="email"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email']; ?>"/>
                    </div>
                </div>
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Password</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtMpass" type="password"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                    </div>
                </div>
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">To</label>
                    <div class="col-sm-9 d-flex gap-2">
					
					   <?php
                                                                                if ($app_res['cust_id'] == 42 || $app_res['cust_id'] == 43  || $app_res['cust_id'] == 34 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 77) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $app_res['corresponding_mail']; ?>" readonly />
                                                                                    <?php
                                                                                }elseif($app_res['cust_id'] == 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $app_res['corresponding_mail']; ?>" readonly />
                                                                                    <?php
                                                                                }
																				elseif($app_res['cust_id'] == 76   && $app_res['stage']=='FP') {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_to_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
																				
																				elseif($app_res['cust_id'] == 66 && $app_res['stage']!='FP' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $staffs_to_email['staff_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
                                                                                elseif($app_res['cust_id'] == 7 && $app_res['stage']=='FP' && $app_res['pub_id']=='EPL_6767' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="marimuthu@transforma.co"/>
                                                                                    <?php
                                                                                }
																				else {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_to_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
                                                                                ?>
						
						
						
                          <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                        <span class="validemail"></span>
                        Note- Add the Email ids with comma seperated. </div>
              </div> 
			  				<?php 
                                                                            $stage_check_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
                                                                            $stage_check_fin=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
                                                                            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
                                                                            $stage_check_rev=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);
																		if($app_res['cust_id'] == 66) {
                                                                                    ?>
                                                                                     <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">CC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtCc" type="text"  class="col-sm-6 form-control" value="<?php echo $staff_cc_email['staff_email']; ?>,<?php echo $journal_info['j_to_email']; ?>,<?php echo $staff_cc_email_superadmin['staff_email']; ?>"/>
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">BCC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtBcc" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['j_bcc_email']; ?>"  />
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>	
                                                                                    <?php
                                                                                }
																				else
																				{
																				?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">CC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                               <input name="txtCc" type="text" class="col-sm-6 form-control" 
       value="<?php echo $journal_info['j_cc_email'] . (!empty($app_res['corresponding_other_author']) ? ',' . $app_res['corresponding_other_author'] : ''); ?>"/>
Note:- Add the Email IDs with comma separated. </div>
                                                                        </div>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">BCC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtBcc" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['j_bcc_email']; ?>" />
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>	
																			<?php 
																				}
																				?>
              
               <?php
                                                                                if($app_res['cust_id']== 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                   
																					
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																		<?php 
																				}
																				 if(($app_res['cust_id']== 76 || $app_res['cust_id']== 77 || $app_res['cust_id']== 79) && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                   
																					
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																		<?php 
																				}
																				elseif($app_res['cust_id']!= 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
																					
																					  <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																				<?php 
																				}
																				
																				elseif($app_res['cust_id']!= 66 && $app_res['stage']!='FP' ) {
                                                                                    ?>
																					
																					  <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																				<?php 
																				}
																				?>
                    
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Reply To</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtreplyto" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['reply_to']; ?>"/>
                        Note:- Corresponding ReplyTo fetched from journal details.</div>
                </div>
				
				     <?php 
																		if($app_res['cust_id']==37)
																		{
																		?>




                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $sub_mail; ?> - Proof"/>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		elseif($app_res['cust_id']==57 || $app_res['cust_id']==58)
																		{
																			if($app_res['stage']=='FP')
																			{
																			$subject_iap='[##ARTICLEID##] - Firstproof Uploaded';
																			}
																			elseif($stage_check_fin=='FIN')
																			{
																			$subject_iap='[##ARTICLEID##] - Final Delivery';
																			}
																			elseif($stage_check_pmc=='PMC')
																			{
																			$subject_iap='[##ARTICLEID##] - PMC Data Files Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) || $inwed_run['pap']=='no' && ($journal_info['j_code']=='JSIAML') ))
																			{
																			$subject_iap='[##ARTICLEID##] - Revised Proof Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) || $inwed_run['pap']=='no' ))
																			{
																			$subject_iap='[##ARTICLEID##] - Revised Proof Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && $inwed_run['pap']=='yes')
																			{
																			$subject_iap='[##ARTICLEID##] – Jstage Data Files Uploaded';
																			}
																			else
																			{
																			$subject_iap='[##ARTICLEID##] – Jstage Data Files Uploaded';
																			}
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_iap; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		elseif(($app_res['cust_id']==66 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $stage_check_fin=='FIN' && $inwed_run['corrections']=='no')
																		{
																		$subject_eos='[##ARTICLEID##] - Final Delivery';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
                                                                        elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $app_res['stage']!='REV1' && $stage_check_rev=='REV' && $inwed_run['corrections']=='no')
																		{
																		$subject_eos='[##ARTICLEID##] - Final Proof PDF';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		  elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $stage_check_rev!='REV' && $stage_check_fin!='FIN' && $stage_check_draft=='DRAFT')
																		{
																		$subject_eos='[##ARTICLEID##] - Updated HTML,PDF for Online Editor ';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		else
																		{
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $d_mail_info['subject']; ?>"/>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		?>

				
                 <?php 
                                                                    

																		if(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $stage_check_fin=='FIN' && $inwed_run['corrections']=='no')
																		{
																		$message_eos='Dear Editor,<br/><br/>Final Package for the above mentioned article has been uploaded to the portal with xml,pdf and Epub File.<br/><br/>Best regards,<br/>Transforma';
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
                                                                        elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $app_res['stage']!='REV1' && $stage_check_rev=='REV' && $inwed_run['corrections']=='no')
																		{
																		$message_eos='Dear Editor,<br/><br/>Final Proof PDF for the above mentioned article has been uploaded to the portal.<br/><br/>Best regards,<br/>Transforma';
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		 elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $stage_check_rev!='REV' && $stage_check_fin!='FIN' && $stage_check_draft=='DRAFT')
																		{
																		$message_eos='Dear Editor,<br/><br/>Corrected HTML,PDF,XML for the above mentioned article has been uploaded to the portal and you can click the link again and edit the article if required or you can directly appprove if no correction required.<br/><br/>Best regards,<br/>Transforma';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		  elseif($app_res['cust_id']==57 || $app_res['cust_id']==58)
																		{
																			
																			if($app_res['stage']=='FP')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the firstproof for '.$app_res['pub_id'].' to the portal.<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_fin=='FIN')
																			{
																			$message_iap='Dear Editor,<br/><br/>Final Package for the above mentioned article has been uploaded to the portal with xml,pdf and Epub File.<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_pmc=='PMC')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the PMC Data Package of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in reviewing the package or need further revisions, please let us know.<br/><br/>Best regards,<br/>Transforma';
																			}
																		elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) ||  $inwed_run['pap']=='no' && ($journal_info['j_code']=='JSIAML') ))
																			{
																			$message_iap='Dear Editors,<br/><br/>We have uploaded the Revised Proof of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) ||  $inwed_run['pap']=='no' ))
																			{
																			$message_iap='Dear Editors,<br/><br/>We have uploaded the revised proof of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_rev=='REV' && $inwed_run['pap']=='yes')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the J-stage Data Files of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in reviewing the package or need further revisions, please let us know.<br/><br/>Best regards,<br/>Transforma';
																			}
																			
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_iap;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		else
																		{
																		?>
																		<div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $d_mail_info['message'] ?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		?>
              
                  <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Attachment</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="fileAttachment" type="file" id="image-file" class="col-sm-6 form-control " />
                    </div>
                  </div>
                </div>
        </div>
        </div>
    </div>
    <!-- <div class="space-2"></div> -->
	
	  <?php
//// Get Prev dept name /////
                                                            $presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . $app_res['department_id'] . ";");
                                                            $ptoatl_count = $presult->num_rows;
                                                            $pdept_res = array();
                                                            if ($ptoatl_count > 0) {
                                                                $pdept_res = $presult->fetch_assoc();
                                                            }
                                                            ?>
    <div class="container mt-4 mb-4">
         <div class="form-group d-flex">
           <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Work DIR:</label>
               <div class="col-xs-12 col-sm-9">
                  <?php
                                                                    $work_dir = $app_res['work_dir'];
                                                                    $work_dir = str_replace(WF_DIR, TOOL_DIR, $work_dir);
                                                                    ?>
                                                                    <div class="clearfix"> <a class="btn btn-primary" href="file:///<?php echo $work_dir . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>&nbspView</a> 
																	<?php
																		if($customer_info['cust_name'] == "Sunrise-NOW")
																		{
																	?>
																		<span class="btn btn-primary" id="Sunrise_Upload_Files_to_AWS_Server"> <i class="ace-icon fa fa-cloud-upload align-top bigger-125"></i> Upload Files to AWS Server </span>
																	<?php
																		}
																	?>
                </div>
          </div>
        </div>
        <div class="container mt-4 mb-4">
           <div class="form-group d-flex">
                                                                <label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Check the files to send</label>
                                                                <div class="checkbox col-xs-12 col-sm-9">
                                                                    <?php
// Open the source directory to read in files
                                                                     $src = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/";
                                                                    if (is_dir($src)) {
                                                                        $i = new DirectoryIterator($src);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                 $filename = $f->getFilename();
                                                                                // $filesize = filesize($src."/".$f);
                                                                                $fsize = filesize($src . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        <?php
                                                                                        if (strpos($filename, '.zip') !== false) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
                                                                                            <?php
                                                                                        } elseif (strpos($filename, '-ori.pdf') == true) {
                                                                                            ?>
                                                                                           <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }else if (strpos($filename, '.pdf') !== false && ($app_res['cust_id'] == 42 && $app_res['stage']== "REV1")) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
                                                                                            <?php
                                                                                        }
																						elseif($app_res['cust_id']==37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked" />
                                                                                            <?php
                                                                                        }elseif($app_res['cust_id']!=37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }
																						
                                                                                            ?>
                                                                                                                                                                                  <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>

                                                                                            <?php
                                                                                        ?>
                                                                                </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
																	
																	   <?php
																	    if($app_res['cust_id']==37)
																	   { 
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    $fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							     if($fsizes >2500000)
								{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.txt') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filess[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>

                                                                                           
                                                                                </div>
																				 <?php
                                                                                        } 
                                                                                        ?>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
								}
                                                                    ?>
																	
																	   <?php
																	 
																	  
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    //$fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							    // if($fsizes >2500000)
								//{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.doc') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filesss[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>
	
                                                                                </div>
																				
                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
																	   }
                                                                    ?>
                                                                </div>
                                                            </div>
          </div>
		  
										<div class="form-group" id="reminder" style="display:none;">
                                                                <label class="col-sm-3 control-label no-padding-right">Need Reminder Mail ?</label>
                                                                <div class="col-sm-9">
                                                                    <label>  <input name="reminder_mail" type="checkbox"  id="reminder_mail" class="ace ace-checkbox-2" value="1" /><span class="lbl"></span></label>
                                                                </div>
                                                            </div>
          <div class="container mt-5 mb-5 stc-actions">
          <div class="form-group d-flex justify-content-center">
            <div class="col-xs-12 d-flex gap-3">
                <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
																	<?php 
																	if($app_res['cust_id']=='66' && $app_res['stage']!='FP')
																	{
																	?>
                                                                    <input type="hidden" name="corrections" value="<?php echo $inwed_run['corrections']; ?>" />
																	<?php 
																	}
																	?>
                                                                    <input type="hidden" name="fileattach" id="fileattach" value="0" />
                                                                    <input type="hidden" name="article_id" value="<?php echo $app_res['pub_id']; ?>" />
                                                                    <input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />
                                                                    <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                                                                    <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />
                                                                    <input type="hidden" name="j_id" value="<?php echo $journal_info['j_id']; ?>" />
                                                                    <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                                                                    <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                                                                    <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>">

                                                                    <input type="hidden" name="proof_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/Proofing/"; ?>">
                                                                    <?php 
                                                                    if($app_res['cust_id'] == 7 && $app_res['stage'] == 'FP' && $app_res['pub_id']=='EPL_6767')
                                                                    {
                                                                    ?>
                                                                    <input type="hidden" name="linux_inw_id" value="<?php echo $linux_inw_id; ?>" />
                                                                    <?php 
                                                                    }
                                                                    ?>
               <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit Article </a>
               <a href="<?php echo $_COOKIE['set_url'];?>/send_to_client_v2.php?id=<?php echo (int)$app_res['id'];?>" class="btn btn-success" title="Package check, dispatch log and delivery-folder checks"><i class="ace-icon fa fa-flask"></i> Try new version</a>
                <input type="button" value="Cancel" onClick="window.history.go(-1);" class="btn btn-grey"/>
                <button type="submit" value="Save" id="saver" class="btn btn-info" name="submit">Send</button>
            </div>
        </div>
		</form>
        </div>
      </div>
      </div>
    </div>
</div>                                       
  <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
</div>

     

        <!-- /.main-container --> 

        <!-- basic scripts --> 

        <!--[if !IE]> -->
        <script>
        /* the Send / Cancel bar is nested inside the Work DIR block by the old markup; make it a direct child of the form
           so it lays out (and sticks on phones) like every other card - it stays inside the form, so the submit still works */
        document.addEventListener('DOMContentLoaded', function () {
          var a = document.querySelector('.stc-actions'), f = a && a.closest('form');
          if (f && a.parentNode !== f) { f.appendChild(a); }
        });
        </script>
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-2.1.4.min.js"></script>

        <!-- <![endif]--> 

        <!--[if IE]>
        <script src="assets/js/jquery-1.11.3.min.js"></script>
        <![endif]--> 
        <script type="text/javascript">
                                                                        if ('ontouchstart' in document.documentElement)
                                                                            document.write("<script src='<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
        </script> 

        <!-- page specific plugin scripts --> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtext.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.validate.min.js"></script> 

        <!-- ace scripts --> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/ace-elements.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/ace.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/bootbox.js"></script> 


       <!-- inline scripts related to this page --> 
        <script type="text/javascript">
                                                                      jQuery(function ($) {
                                                                            $('#validation-form').validate({errorClass: 'help-block'});
                                                                            $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                        })


																		$(window).load(function() 
																		{
																			$(".loader").fadeOut("slow");
																		});
                                                                        $('#Sunrise_Upload_Files_to_AWS_Server').click(function () {
																		
																			$(".loader").fadeIn("slow");
		var a_id = $("input[name='id']").val();
		 //alert(a_id);
		$.ajax({
					type:'POST',
					url:'<?php echo $_COOKIE['set_url'];?>/Sunrise_Upload_Files_to_AWS_Server.php',
					data:'a_id='+a_id+'&type=1',
					success:function(result){
						$(".loader").fadeOut("slow");
						 response = JSON.parse(result);
					
						 if(response.status == 1)
						 {
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						else
						{
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						       
					}
				});  
			});                                                              // $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                       // })



                                                                        $('#validation-form').submit(function () {

                                                                            var article_id = $("input[name='article_id']").val();
                                                                            var cust_name = $("input[name='cust_name']").val();
                                                                            var j_code = $("input[name='j_code']").val();
                                                                            var j_id = $("input[name='j_id']").val();
                                                                            var cust_id = $("input[name='cust_id']").val();
                                                                            var stage = $("input[name='stage']").val();


                                                                            var returnval = true;
                                                                            $.ajax({
                                                                                url: 'main_sample.php',
                                                                                async: false,
                                                                                type: 'POST',
                                                                                data: {article_id: article_id, cust_name: cust_name, j_code: j_code, j_id: j_id, cust_id: cust_id, stage: stage},
                                                                                context: this,
                                                                                success: function (result) {
                                                                                    // alert(result);
                                                                                    if (result == 1)
                                                                                    {
                                                                                        returnval = true;
                                                                                        this.submit();
                                                                                    } else if (result == 0)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("No Proper File Name | Text Name  Or Modify Configuration");
                                                                                    } else if (result == 2)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Sub Folder Must Contain Files Or Modify Configuration.");
                                                                                    } else if (result == 3)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Folder Must Not Contain Zip Or Modify Configuration.");
                                                                                    } else if (result == 7)
                                                                                    {
                                                                                        returnval = true;
                                                                                        ;
                                                                                    } else
                                                                                    {
                                                                                        returnval = true;
                                                                                    }

                                                                                }
                                                                            });
                                                                            return returnval;

                                                                        });

        </script> 

        <script type="text/javascript">
            $(function () {


                $("input[id$='optionRad_0']").click(function () {
                    var re = $(this).val();
                    $("#fileattach").val(0);
                    $("#div1,#emaildiv").show();
                    $("#reminder").hide();
                    $('#image-file').val('');

                });


                $("input[id$='optionRad_1']").click(function () {
                    var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');

                });



                $("input[id$='optionRad_2']").click(function () {
                    var mail = $(this).val();
                    $("#emaildiv").hide();
                    $("#fileattach").val(0);
                    $("#div1").show();
                    $("#reminder").hide();
                });


                $("input[id$='optionRad_3']").click(function () {
                    var mail = $(this).val();
                    $("#fileattach").val(3);
                    $("#div1").hide();
                    $("#emaildiv").show();
                    $('#image-file').val('');
                    $("#reminder").hide();

                    $("#image-file").bind('change', function () {
                        var fat = $("#fileattach").val();
                        if (fat == 3)
                        {
                            var res = this.files[0].size / 1024 / 1024;
                            if (res > 20) {
                                alert('File size exceeds 20 MB Cannot Accept');
                                $('#image-file').val(''); //for clearing with Jquery
                            }
                        }
                    });
                });
				
				
				$("input[id$='optionRad_4']").click(function () {
					var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');
                });
				
				$("input[id$='optionRad_5']").click(function () {
					var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');
                });
				
                $("#saver").click(function ()
                {
                    var fat = $("#fileattach").val();
                    if (fat == 3)
                    {
                        var x;
                        x = document.getElementById("image-file").value;
                      /*   if (x == "") {
                            alert("Attachment is Empty Cannot Send");
                            return false;
                        }
                        ;
 */
                    }
                    ;

                });

<?php
if (($customer_info['id'] == 42 && $app_res['stage']== "FP") || ($customer_info['id'] == 72  && $app_res['stage']== "FP")  || $customer_info['id'] == 43 || $customer_info['id'] == 44 || $customer_info['id'] == 66 || $customer_info['id']== 77 || $customer_info['id'] == 79 || $customer_info['id'] == 76  || $customer_info['id'] == 84) {
    ?>
                    $("input[id$='optionRad_1']").click();
    <?php
}
else if ($customer_info['id'] == 57  || $customer_info['id'] == 58 || $customer_info['id'] == 53) {
    ?>
                    $("input[id$='optionRad_5']").click();
    <?php
}else if ($journal_info['ftp_send'] == 1 && $journal_info['j_email_send'] == 1) {
    ?>
                    $("input[id$='optionRad_0']").click();
    <?php
}  else if ($journal_info['ftp_send'] == 1) {
    ?>
                    $("input[id$='optionRad_2']").click();
    <?php
} else if ($journal_info['j_email_send'] == 1 || ($customer_info['id'] == 42 && $app_res['stage']== "REV1")) {
    ?>
                    $("input[id$='optionRad_3']").click();
    <?php
}else if ($customer_info['id'] == 34) {
    ?>  
                    $("input[id$='optionRad_4']").click();
    <?php
}

?>
                $("#total_file_size_div").hide();
				<?php 
				if($app_res['cust_id']==37)
				{
				?>
                $('.ace-checkbox-2').click(function () {
                    $total_checked_file_size = 0;
                    $(".ace-checkbox-2:checked").each(function () {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    });
                    if ($total_checked_file_size != 0)
                    {
                        $("#total_file_size_div").show();
                        $fsize = formatSizeUnits($total_checked_file_size);
                        if ($total_checked_file_size > 1048576 * 2.5)
                        {
                            bootbox.dialog(
                                    {
                                        message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>2.5MB</b>. Please check and send mail.</div> '
                                    });
                            $(this).prop('checked', false);
                            return false;
                        }
                        $("#total_file_size").html($fsize);
                    } else
                    {
                        $("#total_file_size_div").hide();
                    }
                });
				
				<?php 
				}
				else
				{
				?>
				 $('.ace-checkbox-2').click(function () {
                    $total_checked_file_size = 0;
                    $(".ace-checkbox-2:checked").each(function () {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    });
                    if ($total_checked_file_size != 0)
                    {
                        $("#total_file_size_div").show();
                        $fsize = formatSizeUnits($total_checked_file_size);
                        if ($total_checked_file_size > 1048576 * 20)
                        {
                            bootbox.dialog(
                                    {
                                        message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> '
                                    });
                            $(this).prop('checked', false);
                            return false;
                        }
                        $("#total_file_size").html($fsize);
                    } else
                    {
                        $("#total_file_size_div").hide();
                    }
                });
				<?php 
				}
				?>
            });
            $(window).load(function () {
				
				<?php 
				if($app_res['cust_id']==37)
				{
				?>
                $total_checked_file_size = 0;
                $temp_file_size = 0;
                $(".ace-checkbox-2:checked").each(function () {
                    $temp_file_size += parseInt($(this).attr("data-size"));
                    if ($temp_file_size > 1048576 * 2.5)
                    {
                        // alert('Hi');
                        //$(this).prop('checked', false);
                    } else
                    {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    }
                });
                if ($total_checked_file_size != 0)
                {
                    $("#total_file_size_div").show();
                    $fsize = formatSizeUnits($total_checked_file_size);

                    if ($temp_file_size > 1048576 * 2.5)
                    {
                        bootbox.dialog(
                                {
                                    message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>2.5MB</b>. Please check and send mail.</div> '
                                });
                        $("#total_file_size").html($fsize);
                    }
                } else
                {
                    $("#total_file_size_div").hide();
                }
				
				<?php 
				}
				else
				{
				?>
				
				 $total_checked_file_size = 0;
                $temp_file_size = 0;
                $(".ace-checkbox-2:checked").each(function () {
                    $temp_file_size += parseInt($(this).attr("data-size"));
                    if ($temp_file_size > 1048576 * 20)
                    {
                        // alert('Hi');
                        //$(this).prop('checked', false);
                    } else
                    {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    }
                });
                if ($total_checked_file_size != 0)
                {
                    $("#total_file_size_div").show();
                    $fsize = formatSizeUnits($total_checked_file_size);

                    if ($temp_file_size > 1048576 * 20)
                    {
                        bootbox.dialog(
                                {
                                    message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> '
                                });
                        $("#total_file_size").html($fsize);
                    }
                } else
                {
                    $("#total_file_size_div").hide();
                }
				<?php 
				}
				?>
            });

            function formatSizeUnits(bytes) {
                if (bytes >= 1073741824) {
                    bytes = (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    bytes = (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    bytes = (bytes / 1024).toFixed(2) + ' KB';
                } else if (bytes > 1) {
                    bytes = bytes + ' bytes';
                } else if (bytes == 1) {
                    bytes = bytes + ' byte';
                } else {
                    bytes = '0 byte';
                }
                return bytes;
            }

$(document).ready(function() {
	
	validateMultipleEmails($('#txtTo').val());
    $('#txtTo').blur(function() {
        validateMultipleEmails($('#txtTo').val());
    });
});

function validateMultipleEmails(emailInput) {
    // Get value on emails input as a string
    var emails = emailInput;
    
    // Split string by comma into an array
    emails = emails.split(",");

    var valid = true;
    var regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    
    var invalidEmails = [];
    
    for (var i = 0; i < emails.length; i++) {
        // Trim whitespaces from email address
        emails[i] = emails[i].trim();
        
        // Check email against our regex to determine if email is valid
        if( emails[i] == "" || ! regex.test(emails[i])){
            invalidEmails.push(emails[i]);
        }
    }
    
    // Output invalid emails
    $('.validemail .text-danger').remove();
    if(invalidEmails != 0) {
        $('.validemail').append('<p class="text-danger">Invalid emails: ' + invalidEmails.join(', ') + '</p>');
		$('#saver').hide();
    }
	else
	{
		$('#saver').show();
	}
}

        </script>
    </body>
</html>

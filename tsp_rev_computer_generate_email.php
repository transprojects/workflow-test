<?php 

//include ('dbc.php'); 
include ('tvldb.php'); 
session_start();
date_default_timezone_set('Asia/Kolkata');

/// Get Schedule mail details ////
$query = "SELECT * FROM `adm_schedule_mail_details` WHERE type = 'WIP_Schedule_mail_details' ";
$schedule_mail = $tvldb->query($query) or ($tvldb->error);
$schedule_details = $schedule_mail->fetch_assoc();
				
$mail_from = $schedule_details['from_email'];
$mail_pass = $schedule_details['from_email_pwd'];

$to_mails = 'uma@transforma.in';
$cc_mails = 'inward@transforma.in';
$bcc_mails = 'swamy@transforma.in,marimuthu@transforma.in';

	$body ="<body>";
	$article_query= '';

	
	$start_date = date('Y-m-d')." 00:00:00";	
	$end_date = date("Y-m-d")." 23:59:59";  /// today date
	//$start_date = '2022-02-14 00:00:00';
	//$end_date = '2022-02-14 23:59:59';
	$today = date('Y-m-d');

	
	$tsp_customer_id = 42;
		
     // TSP customer FTP Details
$ftp_server    = 'ftp.transforma.co';
$user          = 'Tsp2019@transforma.co';
$password      = 'T$p@2019';
$document_root = '/To-Transforma/';
	// Checking the FTP connection
	$conn_id = ftp_connect($ftp_server);
	if ($conn_id) 
	{
		echo 'FTP Connection Success!' ;
		
		/// Checking the FTP Login
		$login_result = ftp_login($conn_id, $user, $password);
		if ($login_result) 
		{
			echo 'login to server Success!' ;
			
			// Switched to passive mode after logging in
			ftp_pasv($conn_id, true);
		}
		else {
			echo 'FTP login to server Failed!' ;
			exit;
		}
	}
	else
	{
		echo 'FTP Connection Failed!' ;
		exit;
	}
	
	
	//// Get the Journal Details ///	
	$query = "SELECT j_id,j_name,j_code,j_division FROM `adm_journals` WHERE j_cust_id = '".$tsp_customer_id."' AND j_division = 2  ORDER BY `j_division` ASC;";
	$run_row = $tvldb->query($query) or ($tvldb->error);					
    while ($row = $run_row->fetch_assoc()) 
	{		
        
		//Dispatched
		//$cnt_query = "SELECT DISTINCT(wd.pub_id), dh.query_comments FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd WHERE dh.a_id = wd.id AND wd.cust_id = ".$tsp_customer_id." AND wd.j_id = '".$row['j_id']."' AND dh.stage LIKE 'REV%' AND dh.sent_date BETWEEN '".$start_date."' AND '".$end_date."' ORDER BY dh.`sent_date` DESC;";
		$cnt_query = "SELECT DISTINCT(wd.pub_id), dh.query_comments FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd WHERE dh.a_id = wd.id AND wd.cust_id = ".$tsp_customer_id." AND wd.j_id = '".$row['j_id']."' AND dh.stage LIKE 'REV%' AND dh.tsp_sent_date = '".$today."' ORDER BY dh.`sent_date` DESC;";
		
		
		$fp_run_query = $tvldb->query($cnt_query) or die($tvldb->error);
		
		if($fp_run_query->num_rows > 0 )
		{
			// j_division = 2 = COMPUTER DIVISION
			// j_division = 1 = REGULAR DIVISION
			$ftp_path = '';
			if($row['j_division']==2)
			{
				$ftp_path = "/From-Transforma/Computer Division/".date('Y')."/".$row['j_code']."/".date('F')."/".date('md');
			}
			
			if($row['j_division']==1)
			{
				$ftp_path = "/From-Transforma/Regular Division/".date('Y')."/".$row['j_code']."/".date('F')."/".date('md');
			}
			
			$body .="<br>".$ftp_path."<br>";
			
			while ($ainfo = $fp_run_query->fetch_assoc()) 
			{
				/// Check any Article received extra corrections while delevering
				$wip_check_query = "SELECT id FROM `inw_inward_dtl` WHERE `cust_id` = '".$tsp_customer_id."' AND `job_id` = '".trim($ainfo['pub_id'])."' AND `status` != 'Client Review';";
				$wip_chk_res = $tvldb->query($wip_check_query);
				$wip_article_msg = '';
				if($wip_chk_res->num_rows > 0 )
				{
					$wip_article_msg = ' - This Article has received additional corrections and it is WIP status.';
				}
				
				if(empty($wip_article_msg))
				{
					
					$file_size = ftp_size($conn_id, $ftp_path."/".$ainfo['pub_id'].".pdf");
					if ($file_size != -1) 
					{
						$body .="<div>".$ainfo['pub_id'] . "</div><br>";
					}
					else 
					{
						$body .="<div style='background-color:red' >".$ainfo['pub_id'] ."</div><br>";
					}


				}
				else
				{	
					$body .=$ainfo['pub_id'] . $wip_article_msg."<br>";
				}
				//$body .=$ainfo['pub_id']."<br>";
				
				if($ainfo['query_comments']!='')
				{
					$article_query .= "<b>".$ainfo['pub_id']."</b>: ".$ainfo['query_comments']."<br44>";
				}
			}
			
			
		}
			
    }
		
			
			
 
			
	//echo $body;exit;
	
	 if($body != "")
	 {
		 
	
	if($article_query!='')
	{
		$body .= '<br><b>Note:</b><br><br>';
		$body .= $article_query;
	}
	
	echo $html = $body;
	$month_date = date('d-m-Y');
	
	///// Send Mail /////
	
$mail_subject = "TSP Revised articles Upload -- ".$month_date;
$mail_body = "Dear Team, <br/><br/>Kindly follow the posted TSP revises into the below FTP path.<br/><br/>".$html."<br/><br/>Regards,<br><br>
	         	Transforma .<br><br>
	         	*** This is an automated system generated e-mail, please do not return your correction to this mail.";
				

//echo $mail_body; exit;				
				
	include("smtpmail/phpmailer/class.phpmailer.php");


$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);

 
$mail->clearAllRecipients();

$mail->Subject = $mail_subject;
$mail->Body = $mail_body;


//// Send mail to more than one user ///
$mail_to = explode(',',$to_mails);
foreach($mail_to as $email)
{
	if($email!='')
	{
		$mail->addAddress($email);
	}
}

//$mail->addAddress('swamy@transforma.in');
$mail_tocc 		= explode(',',$cc_mails);
foreach($mail_tocc as $email)
{
	if($email!='')
	{
		$mail->AddCC($email);
	}
}


$mail_tobcc = explode(',', $bcc_mails);
foreach ($mail_tobcc as $email) {
    if ($email != '') {
        $mail->AddBCC($email);
    }
}

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo; 
} else {
   echo "Message sent!";  
   /// Addign the record for today Articles mail generated ///
   //$tsp_sent_qry = "INSERT INTO `tsp_mail_send_history` (`type`, `sent_date`, `sent_by`) VALUES ('COMP', '".date('Y-m-d')."', '" . $_COOKIE['user_id'] . "');";
   $tsp_sent_qry = "INSERT INTO `tsp_mail_send_history` (`type`, `sent_date`, `sent_by`) VALUES ('COMP', '".date('Y-m-d')."', '1');";
   $tvldb->query($tsp_sent_qry);
			 
}
/////////// End here send mail ////////

	 }
	//Close the connection 
$tvldb->close(); 
?>
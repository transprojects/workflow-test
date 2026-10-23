<?php 

//include ('dbc.php'); 
include ('tvldb.php'); 

date_default_timezone_set('Asia/Kolkata');

/// Get Schedule mail details ////
$query = "SELECT * FROM `adm_schedule_mail_details` WHERE type = 'WIP_Schedule_mail_details' ";
$schedule_mail = $tvldb->query($query) or ($tvldb->error);
$schedule_details = $schedule_mail->fetch_assoc();
				
$mail_from = $schedule_details['from_email'];
$mail_pass = $schedule_details['from_email_pwd'];

$to_mails = 'swamy@transforma.in';
$cc_mails = '';
$bcc_mails = '';

	$body ="<body>";

	
	$start_date = date('Y-m-d')." 00:00:00";	
	$end_date = date("Y-m-d");  /// today date
	$start_date = '2022-02-14 00:00:00';
	$end_date = '2022-02-14 23:59:59';

	
	$tsp_customer_id = 42;
		
    
	
	//// Get the Journal Details ///	
	$query = "SELECT j_id,j_name,j_code,j_division FROM `adm_journals` WHERE j_cust_id = '".$tsp_customer_id."'  ORDER BY `j_division` ASC;";
	$run_row = $tvldb->query($query) or ($tvldb->error);					
    while ($row = $run_row->fetch_assoc()) 
	{		
        
		//Dispatched
		$cnt_query = "SELECT wd.pub_id FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd WHERE dh.a_id = wd.id AND wd.cust_id = ".$tsp_customer_id." AND wd.j_id = '".$row['j_id']."' AND dh.stage LIKE 'REV%' AND dh.sent_date BETWEEN '".$start_date."' AND '".$end_date."' LIMIT 5;";
		
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
				$body .=$ainfo['pub_id']."<br>";
			}
			
			
		}
			
    }
		
			
			
 
			
	//echo $body;exit;
	
	 if($body != "")
	 {
		 
	$html = $body;
	$month_date = date('d-m-Y');
	
	///// Send Mail /////
	
$mail_subject = "TSP revises Upload on".$month_date;
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
}
/////////// End here send mail ////////

	 }
	//Close the connection 
$tvldb->close(); 
?>
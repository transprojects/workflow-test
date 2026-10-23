<?php 

		$result_aws_inw = $xperttrack_db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$artile_name."' "); 	
	$app_res_aws = $result_aws_inw->fetch_assoc();
	
	
	//// Journal name ///	
	 $query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res_aws['j_id']."'";
	$steps = $db->query($query) or ($db->error);
	$jninfo = $steps->fetch_assoc();

	$article_type = $app_res_aws['inw_type'];
	
		   $to='marimuthu@transforma.in';
		   $cc='techsupport@transforma.in';
		   $bcc='';
		$subject="KSU Revised PDF & other attachment OF : $artile_name - FTP Download ERROR";
		$message='Dear Transforma,<br/><br/> 
			The Revised PDF & other attachments for the above mentioned article details<b style="color:red;">has not downloaded successfully</b> for processing, and the relevant details are as follows:<br/><br/>
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
			<td style="color:#0080ff; border: 1px solid #F4B083;">' . $artile_name .'</td>
			</tr>
			
			</table>
			<br/><br/>
			If you have any questions or require further assistance, please feel free to contact us.
			<br/><br/>
			<br/><br/>
			 Regards,<br/><br/>
			KSU<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/>';
			
		include("../../smtpmail/phpmailer/class.phpmailer.php");
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
    
    /// Send the email
    if (!$mail->send()) {
        echo 'Error sending email: ' . $mail->ErrorInfo . '<br>';

        // Retry after a delay
        sleep($retryInterval);
    } else {
        echo 'Email sent successfully for FTP Failed Issue.';
        break; // Exit the loop if email is sent successfully
		
    }
}
	   if ($retryCount > $maxRetries) {
    echo 'Email sending failed after maximum retries.';
}
?>
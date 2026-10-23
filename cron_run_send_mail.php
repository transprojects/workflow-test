<?php
include("smtpmail/phpmailer/class.phpmailer.php");
$mail = new PHPMailer(true);

           $to='techsupport@transforma.in,tvlsysadmin@transforma.in';
		   $cc='dhamodaran@transforma.co';
		   //$bcc='techsupport@transforma.in,swamy@transforma.in,marimuthu@transforma.co';
		   
		
		$subject="User report Cron Job started at : ". date("Y-m-d H:i:s");
		$message='Dear Transforma,<br/><br/> 
			We would like to inform you that<b style="color:red;">Generate User report is DONE</b>:<br/><br/>
	        	<table style="border-collapse: collapse;">
			<tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
			<th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Information</th>
			<th style="word-wrap: break-word;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Details</th>
			</tr>
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; Date</td>
			<td style="border: 1px solid #F4B083;">'.date("Y-m-d H:i:s").'</td>
			</tr>	         	
			</table>         	
			<br/><br/>
			If you have any questions or require further assistance, please feel free to contact us.
			<br/><br/>
			Thank you for your cooperation.
			<br/><br/>
			Best regards,<br/><br/>
			Tech Team<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/>';
			
			
	try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // for detailed debug output
    $mail->isSMTP();
    $mail->Host = 'outlook.office365.com';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->Username = 'techsupport@transforma.in'; // YOUR gmail email
    $mail->Password = 'Tr@n$!t2018'; // YOUR gmail password
    // Sender and recipient settings
    $mail->setFrom('techsupport@transforma.in', 'Tehsupport');
    $mail->addReplyTo('techsupport@transforma.in', 'Tehsupport'); // to set the reply to

    // Setting the email content
    $mail->IsHTML(true);
    $mail->Subject = $subject;
    $mail->Body = 	$message;
   // $mail->AltBody = 'Plain text message body for non-HTML email client. Gmail SMTP email body.';



			
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
				$mail->AddBCC($email);
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
} catch (Exception $e) {
    echo "Error in sending email. Mailer Error: {$mail->ErrorInfo}";
}
?>
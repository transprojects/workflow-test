		<?php
        include("smtpmail/phpmailer/class.phpmailer.php");

		$mail_from = "techsupport@transforma.in";
		$mail_pass = "Tr@n$!t2018";
		$subject="Integrity Checks Report Generated - ".$_REQUEST['fileno']."";
		$body='Dear Team,<br/><br/> 
			Integrity Checks Report received and generated for the mentioned article - Please check the article details captured below :<br/><br/>
			<table style="border-collapse: collapse;">
			<tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
			<th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Information</th>
			<th style="word-wrap: break-word;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Details</th>
			</tr>
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; Client Name</td>
			<td style="border: 1px solid #F4B083;">TSP</td>
			</tr>	         	
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; File Name</td>
			<td style="color:#0080ff; border: 1px solid #F4B083;">' . $_REQUEST['fileno'] .'</td>
			</tr>
			</table>
			<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/><br/>
			Thanks &amp; Regards,<br/><br/>
			FTP Virtual Assistant<br/>
			Transforma Pvt. Ltd.<br/>';

		//$to = "tec0304@transforma.co";
		$to='marimuthu@transforma.in';
		$cc="dhamodaran@transforma.in,bala@transforma.in,siva@transforma.in";
		$bcc="techsupport@transforma.in";
		
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
        $mail->Body = $body;
        $mail->AltBody = ""; // optional, comment out and test

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
		
        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
       

?>
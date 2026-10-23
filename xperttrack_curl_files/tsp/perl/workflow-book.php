
<?php

date_default_timezone_set('Asia/Kolkata');


		///echo $argv[1]."Hi welcome to trans";

        include("../../../smtpmail/phpmailer/class.phpmailer.php");

		$mail_from = "techsupport@transforma.in";
		$mail_pass = "Tr@n$!t2018";
		//$mail_from = "tec0304@transforma.co";
		//$mail_pass = "Trans@20211"
		$subject="New articles (Date:".date('d-m-Y H:i:s').")";
		$body='Dear Murali,<br/><br/> 
			We have uploaded the new articles for process to the TSP XPERT Platform.<br/><br/>
			<table style="border-collapse: collapse;">
			<tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
			<th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">First Proof Articles List</th>
			</tr>	         	
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="color:#0080ff; border: 1px solid #F4B083;">' . $argv[1] .'</td>
			</tr>
			</table>
			<br/>
			<br/>
			<br/>Please kindly check and proceed. <br/><br/><br/>
			If you have any questions, please do not hesitate to contact us.<br/><br/><br/>
			Kind regards,<br/><br/>
			Zillah Zhang<br/><br/>
			P.O. Box 308, 2590 Windmill Ln, <br/><br/>
			Henderson, NV 89074, USA<br/><br/>
			Tel. +1 702 673 0457<br/><br/>
			Fax: +1 844 635 2598<br/><br/>
			Email: typesetting@techscience.com<br/><br/>
			Website: www.techscience.com';

		$to = "marimuthu@transforma.co";
		$cc='marimuthu@transforma.co,techsupport@transforma.in';
		//$to='marimuthu@transforma.co';
		//$cc="techsupport@transforma.in";
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
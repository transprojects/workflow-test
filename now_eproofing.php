<?php
require_once('includes/includes.php');

        $mail_from = $_POST['txtFrom'];
        $mail_from_name = "";


        $mail_to_name = "";

        date_default_timezone_set('Asia/Kolkata');

        $body =$_POST['txtBody'];



        include_once("smtpmail/phpmailer/class.phpmailer.php");

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "ssl://smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Port = 465;
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];
        // $mail->SMTPSecure = 'tls';
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject = $_POST['txtSub'];
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
		//echo $_POST['txtreplyto'];
		$mail->addReplyTo($_POST['txtreplyto'], '');
					
					//adding terms&condtion pdf for cfr journal alone from static path
					if($app_res['j_id']==85)
					{						
					$mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/Sunrise-NOW/CFR/misc/JournalTermsConditions.pdf");
					}			


        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
       
    
	
?>
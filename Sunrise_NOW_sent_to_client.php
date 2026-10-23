<?php
	 require_once('includes/includes.php');

	if ($_POST['optionRad'] == 5) 
	{
	


        $mail_from = $_POST['txtFrom'];
        $mail_from_name = "";


        $mail_to_name = "";

        date_default_timezone_set('Asia/Kolkata');

        $body =$_POST['txtBody'];



        include_once("smtpmail/phpmailer/class.phpmailer.php");

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';        
        $mail->SMTPAuth = true;
		
		/*$mail->Host = "smtp.gmail.com";
        $mail->Port = 465;
		$mail->SMTPSecure = 'ssl';
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];*/
		$mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];
        
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject = $_POST['txtSub'];
        $mail->Body = $body;
		
					if($app_res['j_id']==85)
					{						
					$mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/Sunrise-NOW/CFR/misc/JournalTermsConditions.pdf");
					}	
					
					
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
		echo $_POST['txtreplyto'];
		$mail->addReplyTo($_POST['txtreplyto'], '');

       

        if (!$mail->Send()) {
			header("location:send_to_client.php?id=" . $post['id'] . "&msg=Mailer Error: " . $mail->ErrorInfo.".");
						exit;
            
        } else {
            echo "Message sent!";
			$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $app_res['cust_id'] . "', '" . $app_res['j_id'] . "', '" . $app_res['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body . "', '" . $_COOKIE['user_id'] . "', '" . $app_res['stage'] . "', NOW(),'" . $dispatch_method[$_POST['optionRad']] . "')";
						$db->query($dispat_qry);
					
					
	
						//// Update the next stage status ////
						 $set_status = "`status` ='Client Review',";
						if ($_POST['stage'] == 'FIN') {
							$set_status = "`status` ='Completed',";
						}
					
						$prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
					
						//echo $prj_update_query; exit;
						$db->query($prj_update_query);
						
						header("location:".$_COOKIE['set_url']."/underdispatcharticles?msg=Successfully completed");
						exit;
        }
	} 
    
	
?>
	
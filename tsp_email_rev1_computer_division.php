<?php 

        $mail_from = $_POST['txtFrom'];
        $mail_from_name = "";
        $mail_to_name = "";
        date_default_timezone_set('Asia/Kolkata');
        $body = $_POST['txtBody'];
		$filename =$post['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$_POST['stage']."/Delivery/".$post['pub_id'].".pdf";
		$name=$post['pub_id'].".pdf";
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject =$_POST['txtSub'];
        $mail->Body = $body;
		$mail->AddAttachment($filename);

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
			
           
       if(file_exists($filename))
	   {

        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {	
            echo "Message sent!";
       
        //exit;
		
		
					$set_status = "`status` ='Client Review',";
				if ($_POST['stage'] == 'FIN') {
					$set_status = "`status` ='Completed',";
				}
				
				 $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";

				//echo $prj_update_query; exit;
				$db->query($prj_update_query);
				
				///// Add records in Artile Dispatch history ///

				$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body. "', '" . $_COOKIE['user_id'] . "', '" . $_POST['stage'] . "', NOW(),'Attach file &amp; mail')";
				$db->query($dispat_qry);
			}
	   }

	?>
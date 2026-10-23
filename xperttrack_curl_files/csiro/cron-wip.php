<?php

	include('conn.php');
	include ('../dbc.php'); 
	

		//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '3'";
	 $steps_cp = $db->query($query_cp) or ($db->error);
	 $cpemail = $steps_cp->fetch_assoc();

	$sno=1;
	$select_wip=mysqli_query($xperttrack_db,"SELECT wd.staff_id,wd.stage_with,wd.department_id,wd.assign_department_id,wd.staff_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,wd.stage,work_dir,wd.notes,wd.corrections,s.staff_name,md.publication_date FROM `inw_inward_dtl` as wd left join eos_staffs as  s on s.staff_id=wd.staff_id left join adm_customer_master as c on  wd.cust_id = c.id  left join adm_journals as j on wd.j_id = j.j_id  LEFT JOIN inw_meta_data AS md ON wd.id = md.inw_id    where wd.cust_id=66 and wd.stage!='Published' and (wd.workflow_id!=0 and wd.workflow_id is not null)");


        $to='marimuthu@transforma.co';
		$cc=$cpemail['mail_cc'];	
		   
		
		$subject='EOS WIP ARTICLES';

		$message=$_REQUEST['message'];

		$message='<table  cellspacing="0" width="100%" border="1" style="width:90%;align-items:center;">
		<thead>
			<tr>
				<th>S.no</th>
				<th>Journal</th>
				<th>Article</th>
				<th>Received </th>
				<th>Publication</th>
				 <th>Stage</th>
				<th>@</th>									
				<th>User</th>
			</tr>
		</thead><tbody>';
		while($res_wip=mysqli_fetch_array($select_wip))
		{
			$stage_r=preg_replace("/REV([0-9]+)/","REV",$res_wip['stage']);

			if($res_wip['stage']== 'FP')
			$stage = 'FIRST PROOF';
		elseif($res_wip['stage']== 'REV1')
			$stage = 'REVISES';
		elseif($res_wip['stage']!= 'REV1' && $stage_r== 'REV')
			$stage = 'FINALS';
		elseif($res_wip['stage']== 'Published')
			$stage = 'PUBLISHED';
			if($res_wip['publication_date']!='' && $res_wip['publication_date']!='0' && $res_wip['publication_date']!='NULL')
			{
			    $pd=date('d-m-Y',strtotime($res_wip['publication_date']));
			}
			else
			{
			   $pd='';  
			}
			$message.='
			<tr>
			<td>'.$sno.')</td>
			<td>'.$res_wip['j_code'].'</td>
			<td>'.$res_wip['pub_id'].'</td>
			<td>'.date('d-m-Y',strtotime($res_wip['recv_dt'])).'</td>
			<td>'.$pd.'</td>
			<td>'.$stage.'</td>
			<td>'.$res_wip['stage_with'].'</td>
			<td>'.$res_wip['staff_name'].'</td>
			</tr>';
			$sno++;
		}
		


		$message.='</tbody></table>';

		//echo $message;exit;
			
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
    
    // Send the email
    if (!$mail->send()) {
        echo 'Error sending email: ' . $mail->ErrorInfo . '<br>';

        // Retry after a delay
        sleep($retryInterval);
    } else {
        echo 'Email sent successfully.';
        break; // Exit the loop if email is sent successfully
		
    }
}
	   if ($retryCount > $maxRetries) {
    echo 'Email sending failed after maximum retries.';
}
			
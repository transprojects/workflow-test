<?php 
include('linux_db_tsp.php');

/// Checking the XPERTTRACKING DB for existing article values ////
$inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl where pub_id ='" . $_POST['article_id'] . "';";
$inwerd_query_xperttrack = $xpertrack_db->query($inwerd_detl_xperttrack) or die($xpertrack_db->error);
$inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();

//// Get the article details ////
$query_xarticle = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$_POST['article_id']."';";
$articledetails_xpert = $db->query($query_xarticle) or ($db->error);
$artilce_info_xpert= $articledetails_xpert->fetch_assoc();

/////// Get the Journal Name ////
$query_xpert = "SELECT * FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
$steps_xpert = $db->query($query_xpert) or ($db->error);
$jninfo_xpert = $steps_xpert->fetch_assoc();

$mail_from = $jninfo_xpert['j_from_email'];
$mail_from_name = "";
$mail_to_name = "";
date_default_timezone_set('Asia/Kolkata');

$body ="<div><div>Dear Arlo,<br><br><div><div>We have uploaded the revised proof of ".$_POST['article_id']." to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us.<br></div><br>Best regards,<br>Transforma</div>
</div></div>
<br>
*** This is an automated system generated e-mail, please do not return your correction to this mail.</div>";

$spassword=date('Y')."-".$inwed_run_xperttrack['id']."-".date('m')."-".date('d');

$sub="[##FileName##] - Revised Proof for your record";
$Subject = str_replace('##FileName##', $_POST['article_id'], $sub);

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
$mail->Password = $jninfo_xpert['j_from_email_pwd'];
$mail->From = $mail_from;
$mail->FromName = $mail_from;
$mail->isHTML(true);
$mail->Subject =$Subject;
$mail->Body = $body;
//$mail->AddAttachment($filename);

$txtTo=$jninfo_xpert['j_cc_email'];
//// Send mail to more than one user ///
$mail_to = explode(',', $txtTo);
foreach ($mail_to as $email) {
    if ($email != '') {
        $mail->addAddress($email);
    }
}

$mail->AltBody = ""; // optional

$mail_tocc = explode(',', $jninfo_xpert['j_bcc_email']);
foreach ($mail_tocc as $email) {
    if ($email != '') {
        $mail->AddCC($email);
    }
}

$mail_tobcc = explode(',', $jninfo_xpert['j_bcc_email']);
foreach ($mail_tobcc as $email) {
    if ($email != '') {
        $mail->AddBCC($email);
    }
}

if(file_exists($filename)) {

    /* if (!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {	
        echo "Message sent!";
    } */

    $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
    $db->query($prj_update_query);

    ///// Add records in Artile Dispatch history ///
    $dispat_qry = "INSERT INTO `inw_dispatch_history` 
        (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `to_cc`, `to_bcc`, `mail_subject`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) 
        VALUES 
        ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $mail_from . "', '" . $txtTo . "', '" . $jninfo_xpert['j_bcc_email'] . "', '" . $jninfo_xpert['j_bcc_email'] . "', '" . $Subject . "', '" . $body. "', '" . $_COOKIE['user_id'] . "', '" . $_POST['stage'] . "', NOW(),'Attach file &amp; mail')";
    $db->query($dispat_qry);

    $ftp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python_ftp_upload/ftp_upload_insert_table.py";
    $python_article_delivery ='C:\\Windows\\py.exe "'.$ftp_python_file.'" "'.$post['id'].'" ';
    system($python_article_delivery);

    // Update article stage in xperttrack
    $update_inwrd_dtl_xperttrack = "UPDATE `inw_inward_dtl` 
        SET stage='".$_POST['stage']."',stage_with='TRANSFORMA',corrections='no',final_package_status='0' 
        WHERE id='" . $inwed_run_xperttrack['id'] . "'";
    $xpertrack_db->query($update_inwrd_dtl_xperttrack);

}

?>

<?php 
include('linux_db_tsp.php');

//echo "xpertrack page";exit;

/// Checking the XPERTTRACKING DB for existing article values
$inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl WHERE pub_id ='" . $_POST['article_id'] . "';";
$inwerd_query_xperttrack = $xpertrack_db->query($inwerd_detl_xperttrack) or die($xpertrack_db->error);
$inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();

//// Get the article details
$query_xarticle = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$_POST['article_id']."';";
$articledetails_xpert = $db->query($query_xarticle) or die($db->error);
$artilce_info_xpert= $articledetails_xpert->fetch_assoc();

//// Get the Journal Name
$query_xpert = "SELECT * FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
$steps_xpert = $db->query($query_xpert) or die($db->error);
$jninfo_xpert = $steps_xpert->fetch_assoc();

$mail_from = $jninfo_xpert['j_from_email'];
$mail_from_name = "";
$mail_to_name = "";
date_default_timezone_set('Asia/Kolkata');

$body = "<div>Dear Dr. ##AuthorNames##,&nbsp;</div><div><br></div><div>Please check the attachment carefully and send your response back due in 48 hours. The proof will be considered the final version.<br/><br/>
If you have corrections, it is important to ensure that all of your corrections are highlighted in the proof and sent back to us. Please use the attached PDF for any corrections.<br/><br/>
If you have no corrections, please also respond within the required time to us with your confirmation.<br/><br/>
If you have any problems, please do not hesitate to contact us. If we don't receive your final revision email within 48 hours, we will assume no changes are needed, and proceed to schedule this version for finalization.<br/><br/>
</div>
<br>
<br>
<table style='border-collapse: collapse;'>
    <tbody>
      <tr style='border: 1px solid rgb(244, 176, 131); border-image: none;' bgcolor='#fbe4d5'>
        <td style='font-size: 14px; font-weight: bold;'>Link</td>
        <td style='color: rgb(0, 128, 255);'><a href='##HOSTNAME##=##URLPASSWORD##' target='_blank'>##HOSTNAME##=##URLPASSWORD##</a></td>
      </tr>
     </tbody>
</table>
<br>
Regards,
<br>
<br>Tech Science Publications.<br>
*** This is an automated system generated e-mail, please do not return your correction to this mail.</div>";

$spassword = date('Y') . "-" . $inwed_run_xperttrack['id'] . "-" . date('m') . "-" . date('d');

$body = str_replace('##AuthorNames##', $artilce_info_xpert['corresponding_author'], $body);
$body = str_replace('##FileName##', $_POST['article_id'], $body);
$body = str_replace('##ARTICLETITLE##', $artilce_info_xpert['article_title'], $body);

$url = 'https://xperttrack.transforma.in/publishers/tsp/author-revision/index.php?id';
$sub = "[##FileName##] - Finalised Proof for your record";
$body = str_replace('##HOSTNAME##',$url, $body);
$body = str_replace('##URLPASSWORD##', base64_encode($spassword), $body);
$Subject = str_replace('##FileName##', $_POST['article_id'], $sub);

$filename = $post['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$_POST['stage']."/Delivery/".$post['pub_id'].".pdf";
$name = $post['pub_id'].".pdf";

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
$mail->Subject = $Subject;
$mail->Body = $body;
//$mail->AddAttachment($filename);

$txtTo = $artilce_info_xpert['corresponding_mail'];
$mail_to = explode(',', $txtTo);
foreach ($mail_to as $email) {
    if ($email != '') {
        $mail->addAddress($email);
    }
}

$mail->AltBody = ""; // optional

$mail_tocc = explode(',', $jninfo_xpert['j_cc_email']);
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

    $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
    $db->query($prj_update_query);

    ///// Add records in Article Dispatch history
    $dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `to_cc`, `to_bcc`, `mail_subject`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) 
    VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $mail_from . "', '" . $txtTo . "', '" . $jninfo_xpert['j_cc_email'] . "', '" . $jninfo_xpert['j_bcc_email'] . "', '" . $Subject . "', '" . mysqli_real_escape_string($db,$body) . "', '" . $_COOKIE['user_id'] . "', 'REV2', NOW(),'Attach file & mail')";
    $db->query($dispat_qry);

    // Call Python script (keep this)
    $ftp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python_ftp_upload/ftp_upload_insert_table.py";
    $python_article_delivery ='C:\\Windows\\py.exe "'.$ftp_python_file.'" "'.$post['id'].'" ';
    system($python_article_delivery);

    // Update XpertTrack record
    $update_inwrd_dtl_xperttrack = "UPDATE `inw_inward_dtl` SET stage='".$_POST['stage']."',stage_with='TRANSFORMA',corrections='no',final_package_status='1',to_author_submit_date='".date('Y-m-d H:i:s')."' WHERE id='" . $inwed_run_xperttrack['id'] . "'";
    $xpertrack_db->query($update_inwrd_dtl_xperttrack);
}
?>

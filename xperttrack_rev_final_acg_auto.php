<?php 
include('linux_db_acg.php');

//echo "xpertrack page";exit;

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

$txtTo=$jninfo_xpert['j_to_email'];
$txtcc=$jninfo_xpert['j_cc_email'];
$txtbcc=$jninfo_xpert['j_bcc_email'];
//$txtTo="marimuthu@transforma.in";

$mail_from = $jninfo_xpert['j_from_email'];
$mail_from_name = "";
$mail_to_name = "";
date_default_timezone_set('Asia/Kolkata');
$xcurrent_stage=preg_replace('/[0-9]+/', '', $_POST['stage']);

  /// Get Email Subject and Body ///
     $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $post['j_id'] . " AND cust_id=" . $post['cust_id'] . " AND stage ='".$xcurrent_stage."'";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();

	
	$body = $d_mail_info['message'];
    $Subject = $d_mail_info['subject'];
	$articleTitle = !empty($app_res['article_title']) ? $app_res['article_title'] : $_POST['pub_id'];

    $Subject = str_replace('##FileName##', $_POST['pub_id'], $Subject);
    $body = str_replace('##FileName##', $_POST['pub_id'], $body);
    $body = str_replace('##ARTICLEID##', $_POST['pub_id'], $body);
    $body = str_replace('##ARTICLETITLE##', $articleTitle, $body);


if ($xcurrent_stage == 'REV') {
    $filename = $post['work_dir'] . $post['volume_folder'] . "/" . $post['pub_id'] . "/" . $post['stage'] . "/Delivery/" . $post['pub_id'] . ".zip";
    $name = $post['pub_id'] . ".zip";
} elseif ($xcurrent_stage == 'FIN') {
    $filename = $post['work_dir'] . $post['volume_folder'] . "/" . $post['pub_id'] . "/" . $post['stage'] . "/Delivery/" . $post['pub_id'] . ".zip";
    $name = $post['pub_id'] . ".zip";
}

if(file_exists($filename)) {
    $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'REV' || $_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
    $db->query($prj_update_query);

    ///// Add records in Article Dispatch history ///
    $dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `to_cc`, `to_bcc`, `mail_subject`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) 
    VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $mail_from . "', '" . $txtTo . "', '" . $txtcc . "', '" . $txtbcc . "', '" . $Subject . "', '" . $body. "', '" . $_COOKIE['user_id'] . "', '" . $_POST['stage'] . "', NOW(),'Attach file &amp; mail')";
    $db->query($dispat_qry);
	
	


    // Call Python script
    $ftp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python_ftp_upload/ftp_upload_insert_table.py";
    $python_article_delivery ='C:\\Windows\\py.exe "'.$ftp_python_file.'" "'.$post['id'].'" ';
    system($python_article_delivery);

    // Update Xperttrack DB
	
	$update_inwrd_dtl_xperttrack = "UPDATE `inw_inward_dtl` SET stage='".$_POST['stage']."',stage_with='TRANSFORMA' WHERE pub_id ='" . $_POST['pub_id'] . "'";
    $xpertrack_db->query($update_inwrd_dtl_xperttrack);

}
?>

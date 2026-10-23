<?php
function bulk_archive($db,$conn,$pub_id)
{

$maindir=CLONE_WF_DIR."/lock_file";
if (!(is_dir($maindir))) {
	if (!mkdir($maindir, 0777, true)) {
    die('Failed to create folders...$jn-----');
	}
	}

$fp = fopen("$maindir/lock_archive.txt", "c+");
if (flock($fp, LOCK_EX))
{

$excel = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$pub_id."'";
echo $excel;
echo "</br>";
 $query1=mysqli_query($db,$excel) or die("Error: " . mysqli_error($db));
/*  
 $query11= "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$row['3']."' AND j_id = '".$journalinfo['j_id']."'";
  	$query1 = $db->query($query11) or ($db->error);
	$quer = $query1->fetch_assoc(); */

 if (mysqli_num_rows($query1)>0)
 {

	$query1=mysqli_query($db,$excel) or die("Error: " . mysqli_error($db));
	 while($post=mysqli_fetch_array($query1))
	 {
		
		  $query = "SELECT * FROM `adm_customer_master` WHERE id = '".$post['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
			//$customer_id = $app_res['cust_id'];
/// Get the Client Name ////
 $query = "SELECT id FROM `adm_customer_master` WHERE id = '" . $crow['id'] . "';";

$customers = $conn->query($query) or die( $conn->error);

$total_count = $customers->num_rows;
if ($total_count > 0) {
	
	
	
    $customer_info = $customers->fetch_assoc();
    //customer tabe update query 
     $adm_cust_update_query = "UPDATE `adm_customer_master` SET `cust_id`='" . $crow['cust_id'] . "', `cust_name`='" . $crow['cust_name'] . "', `catg_id`='" . $crow['catg_id'] . "', `cust_sno`='" . $crow['cust_sno'] . "', `address_line_1`='" . $crow['address_line_1'] . "', `address_line_2`='" . $crow['address_line_2'] . "', `city`='" . $crow['city'] . "', `state`='" . $crow['state'] . "', `country`='" . $crow['country'] . "', `zip`='" . $crow['zip'] . "', `email`='" . $crow['email'] . "', `password`='" . $crow['password'] . "', `due_date_no_of_days`='" . $crow['due_date_no_of_days'] . "', `due_date_no_of_first_proof`='" . $crow['due_date_no_of_first_proof'] . "', `due_date_no_of_revises`='" . $crow['due_date_no_of_revises'] . "', `due_date_no_of_final`='" . $crow['due_date_no_of_final'] . "', `ftp_host_name`='" . $crow['ftp_host_name'] . "', `ftp_user_name`='" . $crow['ftp_user_name'] . "', `ftp_password`='" . $crow['ftp_password'] . "', `ftp_access_dir`='" . $crow['ftp_access_dir'] . "', `delivery_options`='" . $crow['delivery_options'] . "', `phone`='" . $crow['phone'] . "', `url`='" . $crow['url'] . "', `created_by`='" . $crow['created_by'] . "', `created_dt`='" . $crow['created_dt'] . "', `modified_by`='" . $crow['modified_by'] . "', `modified_dt`='" . $crow['modified_dt'] . "', `ctime`='" . $crow['ctime'] . "', `ckey`='" . $crow['ckey'] . "' WHERE id ='" . $crow['id'] . "';";

    $conn->query($adm_cust_update_query)  or die( $conn->error);
	$cust_id = $customer_info['id'];
} else {
    // customer table insert query
     $cust_insert_query = "INSERT INTO `adm_customer_master` (id, `cust_id`, `cust_name`, `catg_id`, `cust_sno`, `address_line_1`, `address_line_2`, `city`, `state`, `country`, `zip`, `email`, `password`, `due_date_no_of_days`, `due_date_no_of_first_proof`, `due_date_no_of_revises`, `due_date_no_of_final`, `ftp_host_name`, `ftp_user_name`,`ftp_password`, `ftp_access_dir`, `delivery_options`, `phone`, `url`, `created_by`, `created_dt`, `modified_by`, `modified_dt`, `ctime`, `ckey`) VALUES ('" . $crow['id'] . "','" . $crow['cust_id'] . "', '" . $crow['cust_name'] . "', '" . $crow['catg_id'] . "', '" . $crow['cust_sno'] . "', '" . $crow['address_line_1'] . "', '" . $crow['address_line_2'] . "', '" . $crow['city'] . "', '" . $crow['state'] . "', '" . $crow['country'] . "', '" . $crow['zip'] . "', '" . $crow['email'] . "', '" . $crow['password'] . "', '" . $crow['due_date_no_of_days'] . "', '" . $crow['due_date_no_of_first_proof'] . "', '" . $crow['due_date_no_of_revises'] . "', '" . $crow['due_date_no_of_final'] . "', '" . $crow['ftp_host_name'] . "', '" . $crow['ftp_user_name'] . "', '" . $crow['ftp_password'] . "', '" . $crow['ftp_access_dir'] . "', '" . $crow['delivery_options'] . "', '" . $crow['phone'] . "', '" . $crow['url'] . "', '" . $crow['created_by'] . "', '" . $crow['created_dt'] . "', '" . $crow['modified_by'] . "', '" . $crow['modified_dt'] . "', '" . $crow['ctime'] . "', '" . $crow['ckey'] . "')";

    $conn->query($cust_insert_query) or die($conn->error);
	$cust_id = $conn->insert_id;
}


/*------------------End Customer Details---------------------------------------------------------------*/

	//// Journal name ///	
	$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
	$steps = $db->query($query) or ($db->error);
	$jninfo = $steps->fetch_assoc();
	$journal_title = $jninfo['j_name'];
    $journal_title;	
	
	// Checking the AWS DB for existing adm_journals name values // 
/// Get the journal name ///
 $query = "SELECT j_id FROM `adm_journals` WHERE j_id = '" . $jninfo['j_id'] . "';";
$journal = $conn->query($query) or die($conn->error);

$journal_total_count = $journal->num_rows;
if ($journal_total_count > 0) {
    $journal_info = $journal->fetch_assoc();
    // update query journal
    $journal_update_query = "UPDATE `adm_journals` SET  `j_name`='" . $jninfo['j_name'] . "', `j_code`='" . $jninfo['j_code'] . "', `ftp_receive`='" . $jninfo['ftp_receive'] . "', `ftp_receive_host`='" . $jninfo['ftp_receive_host'] . "', `ftp_receive_username`='" . $jninfo['ftp_receive_username'] . "', `ftp_receive_password`='" . $jninfo['ftp_receive_password'] . "', `ftp_receive_dir`='" . $jninfo['ftp_receive_dir'] . "', `ftp_receive_targetfolder`='" . $jninfo['ftp_receive_targetfolder'] . "', `ftp_send`='" . $jninfo['ftp_send'] . "', `ftp_send_host`='" . $jninfo['ftp_send_host'] . "', `ftp_send_user`='" . $jninfo['ftp_send_user'] . "', `ftp_send_password`='" . $jninfo['ftp_send_password'] . "', `ftp_send_dir`='" . $jninfo['ftp_send_dir'] . "', `ftp_send_targetfolder`='" . $jninfo['ftp_send_targetfolder'] . "',  `j_email_receive`='" . $jninfo['j_email_receive'] . "', `j_email_send`='" . $jninfo['j_email_send'] . "', `j_from_email`='" . $jninfo['j_from_email'] . "', `j_from_email_pwd`='" . $jninfo['j_from_email_pwd'] . "', `j_to_email`='" . $jninfo['j_to_email'] . "', `j_cc_email`='" . $jninfo['j_cc_email'] . "', `j_bcc_email`='" . $jninfo['j_bcc_email'] . "', `j_fp_wf`='" . $jninfo['j_fp_wf'] . "', `j_fin_wf`='" . $jninfo['j_fin_wf'] . "', `j_rev_wf`='" . $jninfo['j_rev_wf'] . "', `j_issue_wf`='" . $jninfo['j_issue_wf'] . "', `j_fin_issue_wf`='" . $jninfo['j_fin_issue_wf'] . "', `skip_dept`='" . $jninfo['skip_dept'] . "', `created_by`='" . $jninfo['created_by'] . "', `created_on`='" . $jninfo['created_on'] . "', `tvl_work_departments`='" . $jninfo['tvl_work_departments'] . "', `j_hid`='" . $jninfo['j_hid'] . "', `vendor_work_departments`='" . $jninfo['vendor_work_departments'] . "', `vendor_work_id`='" . $jninfo['vendor_work_id'] . "', `j_due_fduration`='" . $jninfo['j_due_fduration'] . "', `j_due_rduration`='" . $jninfo['j_due_rduration'] . "', `j_due_fiduration`='" . $jninfo['j_due_fiduration'] . "', `j_due_isduration`='" . $jninfo['j_due_isduration'] . "', `j_due_isfduration`='" . $jninfo['j_due_isfduration'] . "', `j_reminder_1`='" . $jninfo['j_reminder_1'] . "', `j_reminder_2`='" . $jninfo['j_reminder_2'] . "', `j_reminder_3`='" . $jninfo['j_reminder_3'] . "', `reminder_mail_to`='" . $jninfo['reminder_mail_to'] . "' WHERE j_id = '" . $jninfo['j_id'] . "'";

    $conn->query($journal_update_query) or die($conn->error);
	$j_id = $journal_info['j_id'];
	
} else {

    // insert query jounal 
     $journal_insert_query = "INSERT INTO `adm_journals`(j_id,`j_cust_id`, `j_name`, `j_code`, `ftp_receive`, `ftp_receive_host`, `ftp_receive_username`, `ftp_receive_password`, `ftp_receive_dir`, `ftp_receive_targetfolder`, `ftp_send`, `ftp_send_host`, `ftp_send_user`, `ftp_send_password`, `ftp_send_dir`, `ftp_send_targetfolder`, `j_email_receive`, `j_email_send`, `j_from_email`,`j_from_email_pwd`, `j_to_email`, `j_cc_email`, `j_bcc_email`, `j_fp_wf`, `j_fin_wf`, `j_rev_wf`, `j_issue_wf`, `j_fin_issue_wf`, `skip_dept`, `created_by`, `created_on`, `tvl_work_departments`,`j_hid`,  `vendor_work_departments`, `vendor_work_id`, `j_due_fduration`, `j_due_rduration`, `j_due_fiduration`, `j_due_isduration`, `j_due_isfduration`, `j_reminder_1`, `j_reminder_2`, `j_reminder_3`, `reminder_mail_to`,j_abbreviation, j_platform,j_division,jt_id,reply_to,reply_name) VALUES( '" . $jninfo['j_id'] . "','" . $jninfo['j_cust_id'] . "', '" . $jninfo['j_name'] . "', '" . $jninfo['j_code'] . "', '" . $jninfo['ftp_receive'] . "', '" . $jninfo['ftp_receive_host'] . "', '" . $jninfo['ftp_receive_username'] . "', '" . $jninfo['ftp_receive_password'] . "', '" . $jninfo['ftp_receive_dir'] . "', '" . $jninfo['ftp_receive_targetfolder'] . "', '" . $jninfo['ftp_send'] . "', '" . $jninfo['ftp_send_host'] . "', '" . $jninfo['ftp_send_user'] . "', '" . $jninfo['ftp_send_password'] . "', '" . $jninfo['ftp_send_dir'] . "', '" . $jninfo['ftp_send_targetfolder'] . "','" . $jninfo['j_email_receive'] . "', '" . $jninfo['j_email_send'] . "', '" . $jninfo['j_from_email'] . "', '" . $jninfo['j_from_email_pwd'] . "','" . $jninfo['j_to_email'] . "', '" . $jninfo['j_cc_email'] . "', '" . $jninfo['j_bcc_email'] . "','" . $jninfo['j_fp_wf'] . "', '" . $jninfo['j_fin_wf'] . "', '" . $jninfo['j_rev_wf'] . "', '" . $jninfo['j_issue_wf'] . "', '" . $jninfo['j_fin_issue_wf'] . "', '" . $jninfo['skip_dept'] . "', '" . $jninfo['created_by'] . "', '" . $jninfo['created_on'] . "', '" . $jninfo['tvl_work_departments'] . "', '" . $jninfo['j_hid'] . "', '" . $jninfo['vendor_work_departments'] . "', '" . $jninfo['vendor_work_id'] . "', '" . $jninfo['j_due_fduration'] . "', '" . $jninfo['j_due_rduration'] . "', '" . $jninfo['j_due_fiduration'] . "', '" . $jninfo['j_due_isduration'] . "', '" . $jninfo['j_due_isfduration'] . "', '" . $jninfo['j_reminder_1'] . "', '" . $jninfo['j_reminder_2'] . "', '" . $jninfo['j_reminder_3'] . "', '" . $jninfo['reminder_mail_to'] . "', '" . $jninfo['j_abbreviation'] . "', '" . $jninfo['j_platform'] . "', '" . $jninfo['j_division'] . "', '" . $jninfo['jt_id'] . "', '" . $jninfo['reply_to'] . "', '" . $jninfo['reply_name'] . "'  )";
	
    $conn->query($journal_insert_query) or die($conn->error);
	$j_id = $conn->insert_id;
}

/*------------------End Journal Details---------------------------------------------------------------*/

			
		
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $post['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$folder=$post['folder'];
		  		 
 
		// ==================================CODE FOR FILE MOVING BEGIN =====================================================
		 $work_d=$post['work_dir'];
	     $volume_folder=$post['volume_folder'];
	     $issueorgroup_folder=$post['issueorgroup_folder'];
	     $pub_id=$post['pub_id'];
		 $stage=$post['stage'];
		
         
		 
		 
$status = "";	
$value = "";


	$folder_check = "$work_d/$volume_folder/$issueorgroup_folder/$pub_id";
if(!file_exists($folder_check))
{
	 echo "Folder Is Empty -". $pub_id. "<br>";
	//header("Location:completed_projects.php?msg= <b>File Not Found In Production Folder</b>, Please Check IT!");
	//exit;
} 	

	// echo "Not Used";			 
		  //======================SOURCE FOLDER========================
	 
          $src="$work_d/$volume_folder/$issueorgroup_folder/$pub_id/$stage";
         	
		//======================DESTINATION FOLDER========================
		$dst=CLONE_WF_DIR."/$production/$journal_folder/$Cn/$jn/$volume_folder/$issueorgroup_folder/$pub_id/$stage";
		
	   if (!(is_dir($dst))) {
	if (!mkdir($dst, 0777, true)) {
    die('Failed to create folders...$jn');
	}
	} 		
		// ==================================CODE FOR FILE MOVING  ENDED=====================================================
	custom_copy($src, $dst);
	
	$src_zip = CLONE_WF_DIR."/$production/$journal_folder/$Cn/$jn/$volume_folder/$issueorgroup_folder/$pub_id/";
	$des_zip = CLONE_WF_DIR."/$production/$journal_folder/$Cn/$jn/$volume_folder/$issueorgroup_folder/$pub_id.zip";
				
				// Get real path for our folder
 $rootPath = realpath($src_zip);
 
// Initialize archive object
$zip = new ZipArchive();
$zip->open($des_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

foreach ($files as $name => $file)
{
    // Get real and relative path for current file
    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($rootPath) + 1);

    if (!$file->isDir())
    {
        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }else {
        if($relativePath !== false)
            $zip->addEmptyDir($relativePath);
    }
}

// Zip archive will be created only after closing object
$zip->close();		
deleteAll($src_zip);	
	
	
	// ==================================START CODE FOR FILE DELETE ===================================================== 
   // Function call 
 $dst_file=CLONE_WF_DIR."/$production/$journal_folder/$Cn/$jn/$volume_folder/$issueorgroup_folder/$pub_id.zip";
        
		if ($files = file_exists($dst_file))
				 {
                    
				 $directory="$work_d/$volume_folder/$issueorgroup_folder/$pub_id/";
                 // echo $directory;
                $directory = escapeshellarg($directory);
                exec("rmdir /s /q $directory");
							 
                     
				$del="$work_d/$volume_folder/$issueorgroup_folder/$pub_id";
							
				    deleteAll($del);
					
	         	 $download_dir = str_replace("production","downloads",$work_d);	
				 $downloads="$download_dir/$volume_folder/$issueorgroup_folder/$pub_id";
					deleteAll($downloads);  
					
				 }
  //=====================================END CODE FOR FILE DELETE ==========================================
	


 //====================CONDITION TO CHECK FOLDER IS EMPTY OR SQL OPERATIONS FAILED=======================			 
  
  
  $open = "$work_d/$volume_folder/$issueorgroup_folder/$pub_id";
                 if ($files = glob($open . "/*"))
				 { 
					//header("location:completed_projects.php?msg=Folder Movements UnSuccessfull || SQL Operation Failed");
                   // exit;	
					echo "Folder Movements UnSuccessfull || SQL Operation Failed -". $pub_id. "<br>";				   
                 } 
				 else 
				 {
                    // echo "SUCCESSFUL FOLDER OPERATIONS";
			
				
				
	// ========================================= PHP CODING TO MOVE DATABASE RECORD =============================================================					
					
		$work_dir = CLONE_WF_DIR."/$production/$journal_folder/$Cn/$jn/";
		
		$query001 = "SELECT COUNT(*) AS anyName FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'workflow' AND table_name = 'inw_inward_dtl';";
		$esec = $db->query($query001);
		$data_orginal = $esec -> fetch_assoc();
		
		$query002 = "SELECT COUNT(*) AS anyName FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'workflow_archive' AND table_name = 'inw_inward_dtl';";
		$esec = $conn->query($query002);
		$data_archive = $esec -> fetch_assoc();
        if($data_orginal === $data_archive)
		{
			// echo "Ok";
			$query1 = mysqli_query($db,"INSERT INTO workflow_archive.inw_inward_dtl SELECT * FROM workflow.inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query2 = mysqli_query($db,"INSERT INTO workflow_archive.article_mail SELECT * FROM workflow.article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query3 = mysqli_query($db,"INSERT INTO workflow_archive.customer_xml_version SELECT * FROM workflow.customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query4 = mysqli_query($db,"INSERT INTO workflow_archive.inw_dispatch_history SELECT * FROM workflow.inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query5 = mysqli_query($db,"INSERT INTO workflow_archive.inw_mailto_client SELECT * FROM workflow.inw_mailto_client WHERE inw_id = '".$post['id']."';");
			$query6 = mysqli_query($db,"INSERT INTO workflow_archive.inw_revisions_dtl SELECT * FROM workflow.inw_revisions_dtl WHERE inw_id = '".$post['id']."';");
			$query7 = mysqli_query($db,"INSERT INTO workflow_archive.inw_transactions SELECT * FROM workflow.inw_transactions WHERE project_id = '".$post['id']."';");
		
		 $count = 0;
	                if($query1)
	                {
							
	                     	$query11 =mysqli_query($conn, "UPDATE inw_inward_dtl SET work_dir='".$work_dir."',folder = 'idle',status = 'Archived', movement_by='".$_SESSION['user_id']."', movement_on=NOW() WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
							// echo "Successfully Transferred";
							$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
							
				    }
					
				    else
					{
						
						$select=mysqli_query($db,"SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."'");
						$post111= $select->fetch_assoc();
						
					    $query123 =mysqli_query($conn,"UPDATE inw_inward_dtl SET `id` ='".$post111['id']."',`auto_id`='".$post111['auto_id']."',`cust_id`='".$post111['cust_id']."', `j_id`='".$post111['j_id']."',`job_id` = '".$post111['job_id']."',`journal_title` = '".$post111['journal_title']."',`article_title` = '".$post111['article_title']."', `recv_dt`='".$post111['recv_dt']."',`accpt_dt`= '".$post111['accpt_dt']."',`due_dt`= '".$post111['due_dt']."',`pub_id`='".$post111['pub_id']."',`doi`= '".$post111['doi']."',`pub_dt`='".$post111['pub_dt']."',`fig` = '".$post111['fig']."',`tab`= '".$post111['tab']."', `location_id`= '".$post111['location_id']."',`manuscript_count`='".$post111['manuscript_count']."',`corresponding_author`='".$post111['corresponding_author']."', `corresponding_mail`='".$post111['corresponding_mail']."', `activity`='".$post111['activity']."',`stage`='".$post111['stage']."',`status`='".$post111['status']."', `folder`='".$post111['folder']."',`allocate_to`='".$post111['allocate_to']."',`allocate_time` = '".$post111['allocate_time']."',`created_by`='".$post111['created_by']."', `created_dt` = '".$post111['created_dt']."',`modified_by`='".$post111['modified_by']."',`approved_by`='".$post111['approved_by']."',`approved_on`='".$post111['approved_on']."',`department_id`='".$post111['department_id']."',`sub_department_id`='".$post111['sub_department_id']."',`assign_department_id`='".$post111['assign_department_id']."',`wf_id`='".$post111['wf_id']."',`instructions`='".$post111['instructions']."',`issueorgroup_folder`='".$post111['issueorgroup_folder']."',`highpriority`='".$post111['highpriority']."',`graphics`='".$post111['graphics']."',`inw_type`='".$post111['inw_type']."',`complexity`='".$post111['complexity']."',`work_dir`='".$work_dir."',`fig_complete_status`='".$post111['fig_complete_status']."',`volume_folder`='".$post111['volume_folder']."',`notes`='".$post111['notes']."',img_disk_count = '".$post111['img_disk_count']."', img_redraw_count = '".$post111['img_redraw_count']."',img_relabel_count ='".$post111['img_relabel_count']."',img_color_figures = '".$post111['img_color_figures']."',reminder_mail= '".$post111['reminder_mail']."',	pdf_status='".$post111['pdf_status']."' , movement_by = '".$_SESSION['user_id']."', movement_on = NOW() WHERE `pub_id` = '".$post['pub_id']."' AND `j_id`='".$post['j_id']."' AND `cust_id`='".$post['cust_id']."' ");	
						
						
						
						$query11 =mysqli_query($conn, "UPDATE inw_inward_dtl SET work_dir='".$work_dir."',folder = 'idle',status = 'Archived' WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
						 // Check Whther Data Is Completely Tranferred.
						
						
					 if($query2)
					 {
						 $check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}	
					 }
					 else
					 {
						 $select=mysqli_query($db,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						$post2= $select->fetch_assoc();
						
						$query2 =mysqli_query($conn, "UPDATE `article_mail` SET id='".$post2['id']."', cust_id='".$post2['cust_id']."', j_id='".$post2['j_id']."',stage='".$post2['stage']."', a_id='".$post2['a_id']."',subject= '".$post2['subject']."', message='".$post2['message']."', created_on= NOW() WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
			    
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					 }
						
						

						
					if($query3)
					 {
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}	
					}
					else
					{
						$select=mysqli_query($db,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `customer_xml_version` SET cxv_id='".$post2['cxv_id']."',cust_id='".$post2['cust_id']."',j_id='".$post2['j_id']."',a_id='".$post2['a_id']."',stage='".$post2['stage']."',version='".$post2['version']."',created_on= NOW(), status='".$post2['status']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						
						
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
					 
						
						
				   if($query4)
					{
						
					$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
							
						}	
					}
					else
					{
						$select=mysqli_query($db,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE`inw_dispatch_history` SET idh_id='".$post3['idh_id']."', cust_id='".$post3['cust_id']."', j_id='".$post3['j_id']."', a_id='".$post3['a_id']."',from_mail='".$post3['from_mail']."',to_mail= '".$post3['to_mail']."',mail_text= '".$post3['mail_text']."', sent_by='".$post3['sent_by']."',stage='".$post3['stage']."',dispatch_type='".$post3['dispatch_type']."', sent_date='".$post3['sent_date']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
			    
					    }
					}
						
                        
						
					if($query5)
					{
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
						
					}
					else
					{
						$select=mysqli_query($db,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_mailto_client` SET id='".$post5['id']."', inw_id='".$post5['inw_id']."',mail_to='".$post5['mail_to']."',subject= '".$post5['subject']."',message = '".$post5['message']."',created_by='".$_SESSION['user_id']."', created_on = NOW() WHERE inw_id = '".$post['id']."' ");
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
					}
						
						
				    if($query6)
				    {
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
						
					}
					else
					{
						$select=mysqli_query($db,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_revisions_dtl` SET r_id='".$post6['r_id']."',inw_id='".$post6['inw_id']."',revision_count='".$post6['revision_count']."', received_date='".$post6['received_date']."',due_date='".$post6['due_date']."', correction_pages='".$post6['correction_pages']."',comments='".$post6['comments']."',file_name='".$post6['file_name']."',created_by='".$_SESSION['user_id']."',created_on=NOW(),type='".$post6['type']."' WHERE inw_id = '".$post['id']."'");
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
					}
					
					
					if($query7)
				    {
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:completed_projects.php?msg=Successfully Archived"); 
							$count++;
						}	
					}
					else
					{
						
						$select=mysqli_query($db,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_transactions` SET  id = '".$post4['id']."', project_id = '".$post4['project_id']."',dept='".$post4['dept']."',stage = '".$post4['stage']."',current_stage = '".$post4['current_stage']."',current_status='".$post4['current_status']."',previous_stage= '".$post4['previous_stage']."',previous_id='".$post4['previous_id']."',process_user='".$post4['process_user']."',start_time='".$post4['start_time']."',end_time='".$post4['end_time']."',completion_status='".$post4['completion_status']."',comments='".$post4['comments']."',assign_department='".$post4['assign_department']."',pages_completed='".$post4['pages_completed']."',file_ref='".$post4['file_ref']."',cloud_backup='".$post4['cloud_backup']."',cloud_path='".$post4['cloud_path']."',cloud_entrytime='".$post4['cloud_entrytime']."',cloud_update='".$post4['cloud_update']."',created_by='".$_SESSION['user_id']."', created_on=NOW());");
						
						
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:completed_projects.php?msg=Successfully Archived"); 
							$count++;
						}
					}
					
					if($count == 7)
					{
						// echo "True";
						  // deleteAll($maindir);
						//header("location:completed_projects.php?msg=Successfully Archived"); 
						//exit;
						echo "Successfully Archived - ". $pub_id. "<br>";	
					}
					else
					{
						// echo "False";
						  // deleteAll($maindir);
						//header("location:completed_projects.php?msg=Successfull Folder Movements|| DB Movements Modified"); 
						//exit;
						echo "Successfull Folder Movements|| DB Movements Modified - ". $pub_id. "<br>";	
					}
			
		}
				 
		else
		{
			// echo "No";
			$count = 0;
			
			$query1 =mysqli_query($conn, "INSERT INTO `inw_inward_dtl` (id,`cust_id`,j_id, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`, `pub_id`, `doi`, `pub_dt`, `fig`, `tab`, `location_id`,`created_by`, `created_dt`,stage,wf_id,department_id,manuscript_count,corresponding_author,corresponding_mail,status,instructions,highpriority,graphics,inw_type,complexity,work_dir,approved_by,approved_on,volume_folder,issueorgroup_folder,notes,img_disk_count,img_redraw_count,img_relabel_count,img_color_figures,reminder_mail,pdf_status,movement_by,movement_on) VALUES ('".$post['id']."','".$post['cust_id']."','".$post['j_id']."', '".$post['job_id']."', '".$journal_title."', '".$post['article_title']."', '".$post['recv_dt']."', '".$post['accpt_dt']."', '".$post['due_dt']."', '".$post['pub_id']."', '".$post['doi']."', '".$post['pub_dt']."', '".$post['fig']."', '".$post['tab']."', '".$post['location_id']."', '".$post['created_by']."', '".$post['created_dt']."','".$post['stage']."','".$post['wf_id']."','".$post['department_id']."','".$post['manuscript_count']."','".$post['corresponding_author']."','".$post['corresponding_mail']."','".$dept_res['dept_name']."','".addslashes($post['instructions'])."','".$post['highpriority']."','".$post['graphics']."','".$post['inw_type']."','".$post['complexity']."','".$work_dir."','".$post['approved_by']."','".$post['approved_on']."','".$post['volume_folder']."','".$post['group_folder_name']."','".$post['notes']."','".$post['img_disk_count']."','".$post['img_redraw_count']."','".$post['img_relabel_count']."','".$post['img_color_figures']."','".$post['reminder_mail']."','".$post['pdf_status']."','".$_SESSION['user_id']."', NOW());");	
			
			         if($query1)
	                     {
	                     	$query11 =mysqli_query($conn, "UPDATE inw_inward_dtl SET folder = 'idle',status = 'Archived' WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
							// echo "Successfully Transferred";
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						    $count++; 
						}
						 }
						 // Check Whther Data Is Completely Tranferred.
						
					
						
						
						 else
					   {
						
						$select=mysqli_query($db,"SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."'");
						$post21= $select->fetch_assoc();
						
						$query1 =mysqli_query($conn, "UPDATE `inw_inward_dtl` SET id = '".$post21['id']."', cust_id='".$post21['cust_id']."', j_id='".$post21['j_id']."', job_id = '".$post21['job_id']."', journal_title = '".$post21['journal_title']."', article_title ='".$post21['article_title']."', recv_dt='".$post21['recv_dt']."', accpt_dt= '".$post21['accpt_dt']."',due_dt= '".$post21['due_dt']."', pub_id='".$post21['pub_id']."',doi= '".$post21['doi']."', pub_dt= '".$post21['pub_dt']."', fig = '".$post21['fig']."',tab= '".$post21['tab']."', location_id= '".$post21['location_id']."', created_by= '".$_SESSION['user_id']."',created_dt= NOW(),stage='".$post21['stage']."',wf_id='".$post21['wf_id']."', department_id='".$dept_info[0]."', manuscript_count='".$post21['manuscript_count']."',corresponding_author='".$post21['corresponding_author']."', corresponding_mail='".$post21['corresponding_mail']."', dept_name='".$dept_res['dept_name']."',instructions='".addslashes($post21['instructions'])."',highpriority='".$post21['highpriority']."',graphics='".$post21['graphics']."', inw_type='".$post21['inw_type']."',complexity='".$post21['complexity']."',work_dir='".$work_dir."',approved_by='".$pos21['approved_by']."', approved_on= '".$post21['approved_on']."',volume_folder='".$post21['volume_folder']."',group_folder_name='".$post21['group_folder_name']."',notes='".$post21['notes']."',img_disk_count = '".$post21['img_disk_count']."', img_redraw_count = '".$post21['img_redraw_count']."',img_relabel_count ='".$post21['img_relabel_count']."',img_color_figures = '".$post21['img_color_figures']."',reminder_mail= '".$post21['reminder_mail']."',	pdf_status='".$post21['pdf_status']."' ,movement_by = '".$_SESSION['user_id']."', movement_on = NOW() WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
						
						
						//----------Query 2 TO Move ANd Delete
						
						$select=mysqli_query($db,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
						
				 $query6 =mysqli_query($conn, "INSERT INTO `article_mail` (id,`cust_id`,j_id,stage,a_id,subject,message,created_on) VALUES ('".$post2['id']."','".$post2['cust_id']."','".$post2['j_id']."', '".$post2['stage']."','".$post2['a_id']."', '".$post2['subject']."', '".$post2['message']."', NOW());");
			    
				if($query6)
				{
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						} 
				}
	            else
					 {
						 $select=mysqli_query($db,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						$post2= $select->fetch_assoc();
						
						$query2 =mysqli_query($conn, "UPDATE `article_mail` SET id='".$post2['id']."', cust_id='".$post2['cust_id']."', j_id='".$post2['j_id']."',stage='".$post2['stage']."', a_id='".$post2['a_id']."',subject= '".$post2['subject']."', message='".$post2['message']."', created_on= NOW() WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
			    
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					 }				
						
						
						
			//----------Query 3 TO Move ANd Delete
							
							
						$select=mysqli_query($db,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query5 =mysqli_query($conn, "INSERT INTO `customer_xml_version` (cxv_id,`cust_id`,j_id,a_id,stage,version,created_on,status) VALUES ('".$post2['id']."','".$post2['cust_id']."','".$post2['j_id']."','".$post2['a_id']."', '".$post2['stage']."','".$post2['version']."', NOW(), '".$post2['status']."');");
			    
				if($query5)
				{
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM customer_xml_version where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						} 
				}
                 	else
					{
						$select=mysqli_query($db,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `customer_xml_version` SET cxv_id='".$post2['cxv_id']."',cust_id='".$post2['cust_id']."',j_id='".$post2['j_id']."',a_id='".$post2['a_id']."',stage='".$post2['stage']."',version='".$post2['version']."',created_on= NOW(), status='".$post2['status']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						
						
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}				
						
						
						//----------Query 4 TO Move ANd Delete
						
						
				$select=mysqli_query($db,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query4 =mysqli_query($conn, "INSERT INTO `inw_dispatch_history` (idh_id,cust_id,j_id,a_id,from_mail,to_mail,mail_text,sent_by,stage,dispatch_type,sent_date) VALUES ('".$post3['idh_id']."','".$post3['cust_id']."','".$post3['j_id']."', '".$post3['a_id']."','".$post3['from_mail']."', '".$post3['to_mail']."', '".$post3['mail_text']."','".$post3['sent_by']."','".$post3['stage']."','".$post3['dispatch_type']."','".$post3['sent_date']."');");
			    
				if($query4)
				{
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_dispatch_history where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						    $count++;
						} 
				}
                			else
					{
						$select=mysqli_query($db,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE`inw_dispatch_history` SET idh_id='".$post3['idh_id']."', cust_id='".$post3['cust_id']."', j_id='".$post3['j_id']."', a_id='".$post3['a_id']."',from_mail='".$post3['from_mail']."',to_mail= '".$post3['to_mail']."',mail_text= '".$post3['mail_text']."', sent_by='".$post3['sent_by']."',stage='".$post3['stage']."',dispatch_type='".$post3['dispatch_type']."', sent_date='".$post3['sent_date']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
			    
					}
					}					
						
									//----------Query 5 TO Move ANd Delete
						
						
				$select=mysqli_query($db,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "INSERT INTO `inw_transactions` (id,`project_id`,dept,stage,current_stage,current_status,previous_stage,previous_id,process_user,start_time,end_time,completion_status,comments,assign_department,pages_completed,file_ref,cloud_path,cloud_backup, cloud_entrytime,cloud_update,created_by,created_dt) VALUES ('".$post4['id']."','".$post4['project_id']."','".$post4['dept']."', '".$post4['stage']."','".$post4['current_stage']."', '".$post4['current_status']."', '".$post4['previous_stage']."','".$post4['previous_id']."','".$post4['process_user']."','".$post4['start_time']."','".$post4['end_time']."','".$post4['completion_status']."',,'".$post4['comments']."','".$post4['assign_department']."','".$post4['pages_completed']."','".$post4['file_ref']."','".$post4['cloud_backup']."','".$post4['cloud_path']."','".$post4['cloud_backup']."','".$post4['cloud_entrytime']."','".$post4['cloud_update']."','".$_SESSION['user_id']."',NOW());");
			    
				if($query2)
				{
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM inw_transactions id = '".$post['id']."'");
						    $count++;
						}
				}
				else
					{
						
						$select=mysqli_query($db,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_transactions` SET  id = '".$post4['id']."', project_id = '".$post4['project_id']."',dept='".$post4['dept']."',stage = '".$post4['stage']."',current_stage = '".$post4['current_stage']."',current_status='".$post4['current_status']."',previous_stage= '".$post4['previous_stage']."',previous_id='".$post4['previous_id']."',process_user='".$post4['process_user']."',start_time='".$post4['start_time']."',end_time='".$post4['end_time']."',completion_status='".$post4['completion_status']."',comments='".$post4['comments']."',assign_department='".$post4['assign_department']."',pages_completed='".$post4['pages_completed']."',file_ref='".$post4['file_ref']."',cloud_backup='".$post4['cloud_backup']."',cloud_path='".$post4['cloud_path']."',cloud_entrytime='".$post4['cloud_entrytime']."',cloud_update='".$post4['cloud_update']."',created_by='".$_SESSION['user_id']."', created_on=NOW());");
						
						
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:completed_projects.php?msg=Successfully Archived"); 
							$count++;
						}
					}
						
						
						
						
					
									//----------Query 6 TO Move ANd Delete
						
						
				$select=mysqli_query($db,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query4 =mysqli_query($conn, "INSERT INTO ` inw_mailto_client` (id,`inw_id`,mail_to,subject,message,created_by,created_on) VALUES ('".$post5['id']."','".$post5['inw_id']."','".$post5['mail_to']."', '".$post5['subject']."','".$post5['message']."','".$_SESSION['user_id']."',NOW());");
			    
				if($query4)
				{
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM  inw_mailto_client inw_id = '".$post['id']."'");
						    $count++;
						}	

				}
                		else
					{
						$select=mysqli_query($db,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_mailto_client` SET id='".$post5['id']."', inw_id='".$post5['inw_id']."',mail_to='".$post5['mail_to']."',subject= '".$post5['subject']."',message = '".$post5['message']."',created_by='".$_SESSION['user_id']."', created_on = NOW() WHERE inw_id = '".$post['id']."' ");
						
						$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
					}				
				//----------Query 7 TO Move ANd Delete
						
						
				$select=mysqli_query($db,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query3 =mysqli_query($conn, "INSERT INTO ` inw_revisions_dtl` (r_id,`inw_id`,revision_count,received_date,due_date,correction_pages,comments,file_name,created_by,created_on,type) VALUES ('".$post6['r_id']."','".$post6['inw_id']."','".$post6['revision_count']."', '".$post6['received_date']."','".$post6['due_date']."','".$post6['correction_pages']."','".$post6['comments']."','".$post6['file_name']."','".$_SESSION['user_id']."',NOW(),'".$post6['type']."');");
			    
				if($query3)
				{
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($db,"DELETE FROM  inw_revisions_dtl inw_id = '".$post['id']."'");
							$count++;
						}
				}
                  	else
					{
						$select=mysqli_query($db,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query2 =mysqli_query($conn, "UPDATE `inw_revisions_dtl` SET r_id='".$post6['r_id']."',inw_id='".$post6['inw_id']."',revision_count='".$post6['revision_count']."', received_date='".$post6['received_date']."',due_date='".$post6['due_date']."', correction_pages='".$post6['correction_pages']."',comments='".$post6['comments']."',file_name='".$post6['file_name']."',created_by='".$_SESSION['user_id']."',created_on=NOW(),type='".$post6['type']."' WHERE inw_id = '".$post['id']."'");
				
				$check=mysqli_query($conn,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($db,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
					}	


            	if($count == 7)
					{
						// echo "True";
						  // deleteAll($maindir);
						//header("location:completed_projects.php?msg=Successfully Archived"); 
						//exit;
						echo "Successfully Archived - ". $pub_id. "<br>";	
					}
					else
					{
						// echo "False";
						  // deleteAll($maindir);
						//header("location:completed_projects.php?msg=Successfull Folder Movements|| DB Movements Modified"); 
						//exit;
						echo "Successfull Folder Movements|| DB Movements Modified - ". $pub_id. "<br>";	
					}						
						
						
		}
		/* 
	else
	{
		echo "Transaction Failed";
	} */
	               // $query3 = mysqli_query($db,"DELETE FROM inw_inward_dtl where `pub_id` = '".$post['pub_id']."'");
				   // header("location:completed_projects.php?msg=Successfully Archived"); 
                       
	    }	
//======================================================================================================================================= //===========END-------CONDITION TO CHECK FOLDER IS EMPTY OR SQL OPERATIONS FAILED==============================================	
	 }	 
 }
 else
 {
	 //echo "No Completed Status in Database";
	  //header("location:completed_projects.php?msg=All Files Are Moved"); 
	 // exit;
	 echo "All Files Are Moved - ". $pub_id. "<br>";	
	
 } 
	
	
//$file="G:/Venkatesh_M/Workflow_CHE/JOURNAL_TEST/$production/$journal_folder/";
  
     //----------------------------CODING ENDS==================FUNCTION CALLING========================
flock($fp, LOCK_UN);
}
 
else 
{
 header("location:completed_projects.php?msg=File Is Busy Please Wait || Archive On Hold"); 
 exit;
 /*  $delete=shell_exec('schtasks /delete /tn "PHP Cron-Revert" /f');
	 $delete1=shell_exec('schtasks /delete /tn "PHP Multi_Revert" /f'); */
}
fclose($fp); 	 
		
	//------------Code FOR Zip		
		
}	

  
  
function custom_copy($src, $dst) {  
   
    // open the source directory 
    $dir = opendir($src);  
   
    // Make the destination directory if not exist 
    @mkdir($dst);  
     
    // Loop through the files in source directory 
    foreach (scandir($src) as $file) {  
   
        if (( $file != '.' ) && ( $file != '..' )) {  
            if ( is_dir($src . '/' . $file) )  
            {  
   
                // Recursively calling custom copy function 
                // for sub directory  
                custom_copy($src . '/' . $file, $dst . '/' . $file);  
   
            }  
            else {  
                copy($src . '/' . $file, $dst . '/' . $file); 
				$dt = filemtime($src);
                 //if ($dt === FALSE) return FALSE;
                touch($dst, $dt);
								
							
            }  
        }  
    }  
    
    closedir($dir); 
	
}
  

  function deleteAll($del) { 
      
    // Check for files 
    if (is_file($del)) { 
          
        // If it is file then remove by 
        // using unlink function 
        return unlink($del); 
    } 
      
    // If it is a directory. 
    elseif (is_dir($del)) { 
          
        // Get the list of the files in this 
        // directory 
        $scan = glob(rtrim($del, '/').'/*'); 
          
        // Loop through the list of files 
        foreach($scan as $index=>$path) { 
              
            // Call recursive function 
            deleteAll($path); 
        } 
          
        // Remove the directory itself 
        return @rmdir($del); 
    } 
}
 

		
		function check_file($dir) {
    $result = false;
    if($dh = opendir($dir)) {
        while(!$result && ($file = readdir($dh)) !== false) {
            $result = $file !== "." && $file !== ".." && !is_dir($file);
        }

        closedir($dh);
    }

    return $result;
}

	
?>
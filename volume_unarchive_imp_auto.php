<?php
ini_set('max_execution_time', 0); // 0 = Unlimited
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("dbc.php");
include("scan_db.php");

  $query = "SELECT * FROM inw_vol_unarchive_dtl as unvi , inw_volume_issues as vi where unvi.status =0 and unvi.vi_id = vi.vi_id";
 $result = $conn->query($query);
		
	if ($result->num_rows > 0) 
	{
		while($row = $result->fetch_assoc()) 
		{
			
			//$query = "SELECT *, Vi.volume_number, Vi.issue_number FROM inw_issue_dispatch_history as idh , inw_volume_issues as vi  where idh.vi_id = '".$row['vi_id']."'  and idh.vi_id = vi.vi_id";
			//$query = "SELECT * FROM inw_volume_issues as vi where vi.vi_id = '".$row['vi_id']."'";
			$query = "SELECT * FROM inw_volume_issues as vi, inw_inward_dtl as id where vi.vi_id = '".$row['vi_id']."' AND id.`v_issue_id` = vi.vi_id";
			
			$result = $conn->query($query);
		
			if ($result->num_rows > 0) 
			{
				while($row1 = $result->fetch_assoc()) 
				{
					$sql1 = "UPDATE inw_vol_unarchive_dtl SET start_deta= NOW() WHERE un_id=".$row["un_id"]."";
					if ($conn->query($sql1) === TRUE) 
					{	
						echo $vol_f = "Vol".$row1['volume_number'].$row1['issue_number'];
						volume_archive($db,$conn,$row1['cust_id'],$row1['j_id'], $vol_f ,$row1['vi_id'],$row1['idh_id']);
						///// Updating the file End date and Status in  DB //// 
						 $sql1 = "UPDATE inw_vol_unarchive_dtl SET end_deta= NOW(), status='1' WHERE un_id=".$row["un_id"]."";
						if ($conn->query($sql1) === TRUE) 
						 {	
					
						}
					}
				}
			}
		
		}
	}
	
	

function volume_archive($db,$conn,$s_cust_id,$s_j_id,$s_v_folder,$vi_id,$idh_id)
{
 $maindir=CLONE_WF_DIR."/lock_file";
if (!(is_dir($maindir))) {
	if (!mkdir($maindir, 0777, true)) {
    die('Failed to create folders...$jn-----');
	}
	}

$fp = fopen("$maindir/lock_volume.txt", "c+");
if (flock($fp, LOCK_EX))
{

 $query1=mysqli_query($conn, "SELECT * FROM `inw_inward_dtl` WHERE cust_id = '".$s_cust_id."' AND j_id = '".$s_j_id."' AND volume_folder = '".$s_v_folder."' ");

 if (mysqli_num_rows($query1)>0)
 {

	 while($post=mysqli_fetch_array($query1))
	 {

		  $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$post['cust_id']."' ";
	$customers = $conn->query($query) or ($conn->error);
	$crow = $customers->fetch_assoc();

	//// Journal name ///	
	$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
	$steps = $conn->query($query) or ($conn->error);
	$jninfo = $steps->fetch_assoc();
	$journal_title = $jninfo['j_name'];
    $journal_title;	

	$query11 = "SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' ORDER BY vi_id ASC ";
    $customers11 = $conn->query($query11) or ($conn->error);
	$vol = $customers11 -> fetch_assoc();
		
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
/* $src1 = "$work_d/$volume_folder/$pub_id/";

 if (!(is_dir($src1))) {
	if (!mkdir($src1, 0777, true)) {
    die('Failed to create folders...$jn-----');
	}
	} */

	//$folder_check = "$work_d/$volume_folder";
	
	echo  $src = "$work_d$volume_folder/$pub_id".".zip";
	$Delivery_src = "$work_d$volume_folder/Delivery.zip";
	
if(!file_exists($src))
{
	 echo "File Not Exists -". $pub_id. "<br>";
	// header("Location:volume_archive.php?msg= <b>File Not Found In Production Folder</b>, Please Check IT!");
	// exit;
} 	
		//======================DESTINATION FOLDER========================
	
		 $dst=str_replace(CLONE_WF_DIR,WF_DIR,$work_d)."$volume_folder/$pub_id";
		 $Delivery_dst=str_replace(CLONE_WF_DIR,WF_DIR,$work_d)."$volume_folder/Delivery";
	   if (!(is_dir($dst))) {
	if (!mkdir($dst, 0777, true)) {
    die('Failed to create folders...$jn');
	}
	}   

			
		// ==================================CODE FOR FILE MOVING  ENDED=====================================================
	// custom_copy($src, $dst);
	echo $src;
	echo $dst;
	
	 //============================= Unzip the file into producction folder ===============
	 
	 $zip = new ZipArchive;
  
// Zip File Name
if ($zip->open($src) === TRUE) {
  
    // Unzip Path
    $zip->extractTo($dst);
    $zip->close();
    echo 'Unzipped Process Successful!';
} else {
    echo 'Unzipped Process failed';
}


///// Delivery Files Unzip ////
if (!(is_dir($Delivery_dst))) 
{
	if(file_exists($Delivery_src))
	{
		$zip = new ZipArchive;
  
		// Zip File Name
		if ($zip->open($Delivery_src) === TRUE) 
		{
  
			// Unzip Path
			$zip->extractTo($Delivery_dst);
			$zip->close();
			echo 'Unzipped Process Successful!';
		} 
		else 
		{
			echo 'Unzipped Process failed';
		}
	}
}

	//unlink($src);


	
	// ========================================= PHP CODING TO MOVE DATABASE RECORD =============================================================					
					$work_dir = str_replace(CLONE_WF_DIR,WF_DIR,$work_d);
	
		$query001 = "SELECT COUNT(*) AS anyName FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'workflow' AND table_name = 'inw_inward_dtl';";
		$esec = $conn->query($query001);
		$data_orginal = $esec -> fetch_assoc();
		
		$query002 = "SELECT COUNT(*) AS anyName FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = 'workflow_archive' AND table_name = 'inw_inward_dtl';";
		$esec = $db->query($query002);
		$data_archive = $esec -> fetch_assoc();
        if($data_orginal === $data_archive)
		{
			// echo "Ok";
			$query1 = mysqli_query($conn,"INSERT INTO workflow.inw_inward_dtl SELECT * FROM workflow_archive.inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query2 = mysqli_query($conn,"INSERT INTO workflow.article_mail SELECT * FROM workflow_archive.article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query3 = mysqli_query($conn,"INSERT INTO workflow.customer_xml_version SELECT * FROM workflow_archive.customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query4 = mysqli_query($conn,"INSERT INTO workflow.inw_dispatch_history SELECT * FROM workflow_archive.inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."';");
			$query5 = mysqli_query($conn,"INSERT INTO workflow.inw_mailto_client SELECT * FROM workflow_archive.inw_mailto_client WHERE inw_id = '".$post['id']."';");
			$query6 = mysqli_query($conn,"INSERT INTO workflow.inw_revisions_dtl SELECT * FROM workflow_archive.inw_revisions_dtl WHERE inw_id = '".$post['id']."';");
			$query7 = mysqli_query($conn,"INSERT INTO workflow.inw_transactions SELECT * FROM workflow_archive.inw_transactions WHERE project_id = '".$post['id']."';");
			$query8 = mysqli_query($conn,"INSERT INTO workflow.inw_volume_issues SELECT * FROM workflow_archive.inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'  ;");
			$query9 = mysqli_query($conn,"INSERT INTO workflow.inw_issue_dispatch_history SELECT * FROM workflow_archive.inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'  ;");
		
		 $count = 0;
	                if($query1)
	                {
							
	                     	$query11 =mysqli_query($db, "UPDATE inw_inward_dtl SET work_dir='".$work_dir."',folder = 'idle',status = 'Client Review', movement_by='".$_SESSION['user_id']."', movement_on=NOW() WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
							// echo "Successfully Transferred";
							$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
							
				    }
					
				    else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."'");
						$post111= $select->fetch_assoc();
						
					    $query123 =mysqli_query($db,"UPDATE inw_inward_dtl SET `id` ='".$post111['id']."',`auto_id`='".$post111['auto_id']."',`cust_id`='".$post111['cust_id']."', `j_id`='".$post111['j_id']."',`job_id` = '".$post111['job_id']."',`journal_title` = '".$post111['journal_title']."',`article_title` = '".$post111['article_title']."', `recv_dt`='".$post111['recv_dt']."',`accpt_dt`= '".$post111['accpt_dt']."',`due_dt`= '".$post111['due_dt']."',`pub_id`='".$post111['pub_id']."',`doi`= '".$post111['doi']."',`pub_dt`='".$post111['pub_dt']."',`fig` = '".$post111['fig']."',`tab`= '".$post111['tab']."', `location_id`= '".$post111['location_id']."',`manuscript_count`='".$post111['manuscript_count']."',`corresponding_author`='".$post111['corresponding_author']."', `corresponding_mail`='".$post111['corresponding_mail']."', `activity`='".$post111['activity']."',`stage`='".$post111['stage']."',`status`='".$post111['status']."', `folder`='".$post111['folder']."',`allocate_to`='".$post111['allocate_to']."',`allocate_time` = '".$post111['allocate_time']."',`created_by`='".$post111['created_by']."', `created_dt` = '".$post111['created_dt']."',`modified_by`='".$post111['modified_by']."',`approved_by`='".$post111['approved_by']."',`approved_on`='".$post111['approved_on']."',`department_id`='".$post111['department_id']."',`sub_department_id`='".$post111['sub_department_id']."',`assign_department_id`='".$post111['assign_department_id']."',`wf_id`='".$post111['wf_id']."',`instructions`='".$post111['instructions']."',`issueorgroup_folder`='".$post111['issueorgroup_folder']."',`highpriority`='".$post111['highpriority']."',`graphics`='".$post111['graphics']."',`inw_type`='".$post111['inw_type']."',`complexity`='".$post111['complexity']."',`work_dir`='".$work_dir."',`fig_complete_status`='".$post111['fig_complete_status']."',`volume_folder`='".$post111['volume_folder']."',`notes`='".$post111['notes']."',img_disk_count = '".$post111['img_disk_count']."', img_redraw_count = '".$post111['img_redraw_count']."',img_relabel_count ='".$post111['img_relabel_count']."',img_color_figures = '".$post111['img_color_figures']."',reminder_mail= '".$post111['reminder_mail']."',	pdf_status='".$post111['pdf_status']."' , movement_by = '".$_SESSION['user_id']."', movement_on = NOW() WHERE `pub_id` = '".$post['pub_id']."' AND `j_id`='".$post['j_id']."' AND `cust_id`='".$post['cust_id']."' ");	
						
						
						
						$query11 =mysqli_query($db, "UPDATE inw_inward_dtl SET work_dir='".$work_dir."',folder = 'idle',status = 'Client Review' WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
						 // Check Whther Data Is Completely Tranferred.
						
						
					 if($query2)
					 {
						 $check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}	
					 }
					 else
					 {
						 $select=mysqli_query($conn,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						$post2= $select->fetch_assoc();
						
						$query2 =mysqli_query($db, "UPDATE `article_mail` SET id='".$post2['id']."', cust_id='".$post2['cust_id']."', j_id='".$post2['j_id']."',stage='".$post2['stage']."', a_id='".$post2['a_id']."',subject= '".$post2['subject']."', message='".$post2['message']."', created_on= NOW() WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
			    
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					 }
						
						

						
					if($query3)
					 {
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}	
					}
					else
					{
						$select=mysqli_query($conn,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `customer_xml_version` SET cxv_id='".$post2['cxv_id']."',cust_id='".$post2['cust_id']."',j_id='".$post2['j_id']."',a_id='".$post2['a_id']."',stage='".$post2['stage']."',version='".$post2['version']."',created_on= NOW(), status='".$post2['status']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
					 
						
						
				   if($query4)
					{
						
					$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
							
						}	
					}
					else
					{
						$select=mysqli_query($conn,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE`inw_dispatch_history` SET idh_id='".$post3['idh_id']."', cust_id='".$post3['cust_id']."', j_id='".$post3['j_id']."', a_id='".$post3['a_id']."',from_mail='".$post3['from_mail']."',to_mail= '".$post3['to_mail']."',mail_text= '".$post3['mail_text']."', sent_by='".$post3['sent_by']."',stage='".$post3['stage']."',dispatch_type='".$post3['dispatch_type']."', sent_date='".$post3['sent_date']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
			    
					    }
					}
						
                        
						
					if($query5)
					{
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
						
					}
					else
					{
						$select=mysqli_query($conn,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_mailto_client` SET id='".$post5['id']."', inw_id='".$post5['inw_id']."',mail_to='".$post5['mail_to']."',subject= '".$post5['subject']."',message = '".$post5['message']."',created_by='".$_SESSION['user_id']."', created_on = NOW() WHERE inw_id = '".$post['id']."' ");
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
					}
						
						
				    if($query6)
				    {
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
						
					}
					else
					{
						$select=mysqli_query($conn,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_revisions_dtl` SET r_id='".$post6['r_id']."',inw_id='".$post6['inw_id']."',revision_count='".$post6['revision_count']."', received_date='".$post6['received_date']."',due_date='".$post6['due_date']."', correction_pages='".$post6['correction_pages']."',comments='".$post6['comments']."',file_name='".$post6['file_name']."',created_by='".$_SESSION['user_id']."',created_on=NOW(),type='".$post6['type']."' WHERE inw_id = '".$post['id']."'");
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
					}
					
					
					if($query7)
				    {
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}	
					}
					else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_transactions` SET  id = '".$post4['id']."', project_id = '".$post4['project_id']."',dept='".$post4['dept']."',stage = '".$post4['stage']."',current_stage = '".$post4['current_stage']."',current_status='".$post4['current_status']."',previous_stage= '".$post4['previous_stage']."',previous_id='".$post4['previous_id']."',process_user='".$post4['process_user']."',start_time='".$post4['start_time']."',end_time='".$post4['end_time']."',completion_status='".$post4['completion_status']."',comments='".$post4['comments']."',assign_department='".$post4['assign_department']."',pages_completed='".$post4['pages_completed']."',file_ref='".$post4['file_ref']."',cloud_backup='".$post4['cloud_backup']."',cloud_path='".$post4['cloud_path']."',cloud_entrytime='".$post4['cloud_entrytime']."',cloud_update='".$post4['cloud_update']."',created_by='".$_SESSION['user_id']."', created_on=NOW());");
						
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}
					}
					
					if($query8)
				    {
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}	
					}
					else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
				$post44=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_volume_issues` SET  vi_id = '".$post44['vi_id']."', cust_id = '".$post44['cust_id']."',j_id ='".$post44['j_id']."',volume_number = '".$post44['volume_number']."',issue_number = '".$post44['issue_number']."',issue_count='".$post44['issue_count']."',received_date= '".$post44['received_date']."',due_date='".$post44['due_date']."',correction_pages='".$post44['correction_pages']."',comments='".$post44['comments']."',created_by='".$post44['created_by']."',created_on='".$post44['created_on']."',work_dir='".$post44['work_dir']."',status='".$post44['status']."',stage='".$post44['stage']."',dispatch_dir='".$post44['dispatch_dir']."' ");
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_volume_issues  WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}
					}
					
					if($query9)
				    {
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}	
					}
					
					else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
					$post44=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_issue_dispatch_history` SET  idh_id = '".$post44['idh_id']."', cust_id = '".$post44['cust_id']."',j_id ='".$post44['j_id']."',a_id = '".$post44['a_id']."',vi_id = '".$post44['vi_id']."',from_mail='".$post44['from_mail']."',to_mail= '".$post44['to_mail']."',mail_text='".$post44['mail_text']."',sent_by='".$post44['sent_by']."',stage='".$post44['stage']."',dispatch_type='".$post44['dispatch_type']."',sent_date='".$post44['sent_date']."' ");
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_issue_dispatch_history  WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}
					}
					if($count == 9)
					{
						// echo "True";
						  deleteAll($maindir);
						// header("location:volume_archive.php?msg=Successfully Archived"); 
						$value = 1;
						// exit;
					}
					else
					{
						// echo "False";
						  deleteAll($maindir);
						// header("location:volume_archive.php?msg=Successfull Folder Movements|| DB Movements Modified"); 
						$value = 2;
						// exit;
					}
			
		}
				 
		else
		{
			
		echo "2222222222";
			// echo "No";
			$count = 0;
			
			$query1 =mysqli_query($db, "INSERT INTO `inw_inward_dtl` (id,`cust_id`,j_id, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`, `pub_id`, `doi`, `pub_dt`, `fig`, `tab`, `location_id`,`created_by`, `created_dt`,stage,wf_id,department_id,manuscript_count,corresponding_author,corresponding_mail,status,instructions,highpriority,graphics,inw_type,complexity,work_dir,approved_by,approved_on,volume_folder,issueorgroup_folder,notes,img_disk_count,img_redraw_count,img_relabel_count,img_color_figures,reminder_mail,pdf_status,movement_by,movement_on) VALUES ('".$post['id']."','".$post['cust_id']."','".$post['j_id']."', '".$post['job_id']."', '".$journal_title."', '".$post['article_title']."', '".$post['recv_dt']."', '".$post['accpt_dt']."', '".$post['due_dt']."', '".$post['pub_id']."', '".$post['doi']."', '".$post['pub_dt']."', '".$post['fig']."', '".$post['tab']."', '".$post['location_id']."', '".$post['created_by']."', '".$post['created_dt']."','".$post['stage']."','".$post['wf_id']."','".$post['department_id']."','".$post['manuscript_count']."','".$post['corresponding_author']."','".$post['corresponding_mail']."','".$dept_res['dept_name']."','".addslashes($post['instructions'])."','".$post['highpriority']."','".$post['graphics']."','".$post['inw_type']."','".$post['complexity']."','".$work_dir."','".$post['approved_by']."','".$post['approved_on']."','".$post['volume_folder']."','".$post['group_folder_name']."','".$post['notes']."','".$post['img_disk_count']."','".$post['img_redraw_count']."','".$post['img_relabel_count']."','".$post['img_color_figures']."','".$post['reminder_mail']."','".$post['pdf_status']."','".$_SESSION['user_id']."', NOW());");	
			
			         if($query1)
	                     {
	                     	$query11 =mysqli_query($db, "UPDATE inw_inward_dtl SET folder = 'idle',status = 'Client Review' WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
							// echo "Successfully Transferred";
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						    $count++; 
						}
						 }
						 // Check Whther Data Is Completely Tranferred.
						
						 else
					   {
						
						$select=mysqli_query($conn,"SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."'");
						$post21= $select->fetch_assoc();
						
						$query1 =mysqli_query($db, "UPDATE `inw_inward_dtl` SET id = '".$post21['id']."', cust_id='".$post21['cust_id']."', j_id='".$post21['j_id']."', job_id = '".$post21['job_id']."', journal_title = '".$post21['journal_title']."', article_title ='".$post21['article_title']."', recv_dt='".$post21['recv_dt']."', accpt_dt= '".$post21['accpt_dt']."',due_dt= '".$post21['due_dt']."', pub_id='".$post21['pub_id']."',doi= '".$post21['doi']."', pub_dt= '".$post21['pub_dt']."', fig = '".$post21['fig']."',tab= '".$post21['tab']."', location_id= '".$post21['location_id']."', created_by= '".$_SESSION['user_id']."',created_dt= NOW(),stage='".$post21['stage']."',wf_id='".$post21['wf_id']."', department_id='".$dept_info[0]."', manuscript_count='".$post21['manuscript_count']."',corresponding_author='".$post21['corresponding_author']."', corresponding_mail='".$post21['corresponding_mail']."', dept_name='".$dept_res['dept_name']."',instructions='".addslashes($post21['instructions'])."',highpriority='".$post21['highpriority']."',graphics='".$post21['graphics']."', inw_type='".$post21['inw_type']."',complexity='".$post21['complexity']."',work_dir='".$work_dir."',approved_by='".$pos21['approved_by']."', approved_on= '".$post21['approved_on']."',volume_folder='".$post21['volume_folder']."',group_folder_name='".$post21['group_folder_name']."',notes='".$post21['notes']."',img_disk_count = '".$post21['img_disk_count']."', img_redraw_count = '".$post21['img_redraw_count']."',img_relabel_count ='".$post21['img_relabel_count']."',img_color_figures = '".$post21['img_color_figures']."',reminder_mail= '".$post21['reminder_mail']."',	pdf_status='".$post21['pdf_status']."' ,movement_by = '".$_SESSION['user_id']."', movement_on = NOW() WHERE pub_id = '".$post['pub_id']."' AND j_id='".$post['j_id']."' ");	
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_inward_dtl WHERE pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_inward_dtl where pub_id = '".$post['pub_id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}
						
						
						//----------Query 2 TO Move ANd Delete
						
						$select=mysqli_query($conn,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
						
				 $query6 =mysqli_query($db, "INSERT INTO `article_mail` (id,`cust_id`,j_id,stage,a_id,subject,message,created_on) VALUES ('".$post2['id']."','".$post2['cust_id']."','".$post2['j_id']."', '".$post2['stage']."','".$post2['a_id']."', '".$post2['subject']."', '".$post2['message']."', NOW());");
			    
				if($query6)
				{
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						} 
				}
	            else
					 {
						 $select=mysqli_query($conn,"SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						$post2= $select->fetch_assoc();
						
						$query2 =mysqli_query($db, "UPDATE `article_mail` SET id='".$post2['id']."', cust_id='".$post2['cust_id']."', j_id='".$post2['j_id']."',stage='".$post2['stage']."', a_id='".$post2['a_id']."',subject= '".$post2['subject']."', message='".$post2['message']."', created_on= NOW() WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
			    
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM article_mail WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM article_mail where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					 }				
						
			//----------Query 3 TO Move ANd Delete
							
						$select=mysqli_query($conn,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query5 =mysqli_query($db, "INSERT INTO `customer_xml_version` (cxv_id,`cust_id`,j_id,a_id,stage,version,created_on,status) VALUES ('".$post2['id']."','".$post2['cust_id']."','".$post2['j_id']."','".$post2['a_id']."', '".$post2['stage']."','".$post2['version']."', NOW(), '".$post2['status']."');");
			    
				if($query5)
				{
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM customer_xml_version where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						} 
				}
                 	else
					{
						$select=mysqli_query($conn,"SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						$post2=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `customer_xml_version` SET cxv_id='".$post2['cxv_id']."',cust_id='".$post2['cust_id']."',j_id='".$post2['j_id']."',a_id='".$post2['a_id']."',stage='".$post2['stage']."',version='".$post2['version']."',created_on= NOW(), status='".$post2['status']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
						
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM customer_xml_version WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
						}
					}				
						
						
						//----------Query 4 TO Move ANd Delete
						
						
				$select=mysqli_query($conn,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query4 =mysqli_query($db, "INSERT INTO `inw_dispatch_history` (idh_id,`cust_id`,j_id,a_id,from_mail,to_mail,mail_text,sent_by,stage,dispatch_type,sent_date) VALUES ('".$post3['idh_id']."','".$post3['cust_id']."','".$post3['j_id']."', '".$post3['a_id']."','".$post3['from_mail']."', '".$post3['to_mail']."', '".$post3['mail_text']."','".$post3['sent_by']."','".$post3['stage']."','".$post3['dispatch_type']."','".$post3['sent_date']."');");
			    
				if($query4)
				{
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_dispatch_history where a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
						    $count++;
						} 
				}
                			else
					{
						$select=mysqli_query($conn,"SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
				$post3=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE`inw_dispatch_history` SET idh_id='".$post3['idh_id']."', cust_id='".$post3['cust_id']."', j_id='".$post3['j_id']."', a_id='".$post3['a_id']."',from_mail='".$post3['from_mail']."',to_mail= '".$post3['to_mail']."',mail_text= '".$post3['mail_text']."', sent_by='".$post3['sent_by']."',stage='".$post3['stage']."',dispatch_type='".$post3['dispatch_type']."', sent_date='".$post3['sent_date']."' WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."' ");
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."');");
					$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_dispatch_history WHERE a_id = '".$post['id']."' AND j_id = '".$post['j_id']."' AND cust_id = '".$post['cust_id']."'");
							$count++;
			    
					}
					}					
						
									//----------Query 5 TO Move ANd Delete
						
						
				$select=mysqli_query($conn,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "INSERT INTO `inw_transactions` (id,`project_id`,dept,stage,current_stage,current_status,previous_stage,previous_id,process_user,start_time,end_time,completion_status,comments,assign_department,pages_completed,file_ref,cloud_path,cloud_backup, cloud_entrytime,cloud_update,created_by,created_dt) VALUES ('".$post4['id']."','".$post4['project_id']."','".$post4['dept']."', '".$post4['stage']."','".$post4['current_stage']."', '".$post4['current_status']."', '".$post4['previous_stage']."','".$post4['previous_id']."','".$post4['process_user']."','".$post4['start_time']."','".$post4['end_time']."','".$post4['completion_status']."',,'".$post4['comments']."','".$post4['assign_department']."','".$post4['pages_completed']."','".$post4['file_ref']."','".$post4['cloud_backup']."','".$post4['cloud_path']."','".$post4['cloud_backup']."','".$post4['cloud_entrytime']."','".$post4['cloud_update']."','".$_SESSION['user_id']."',NOW());");
			    
				if($query2)
				{
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_transactions id = '".$post['id']."'");
						    $count++;
						}
				}
				else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."'");
				$post4=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_transactions` SET  id = '".$post4['id']."', project_id = '".$post4['project_id']."',dept='".$post4['dept']."',stage = '".$post4['stage']."',current_stage = '".$post4['current_stage']."',current_status='".$post4['current_status']."',previous_stage= '".$post4['previous_stage']."',previous_id='".$post4['previous_id']."',process_user='".$post4['process_user']."',start_time='".$post4['start_time']."',end_time='".$post4['end_time']."',completion_status='".$post4['completion_status']."',comments='".$post4['comments']."',assign_department='".$post4['assign_department']."',pages_completed='".$post4['pages_completed']."',file_ref='".$post4['file_ref']."',cloud_backup='".$post4['cloud_backup']."',cloud_path='".$post4['cloud_path']."',cloud_entrytime='".$post4['cloud_entrytime']."',cloud_update='".$post4['cloud_update']."',created_by='".$_SESSION['user_id']."', created_on=NOW());");
						
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_transactions WHERE project_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_transactions WHERE project_id = '".$post['id']."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}
					}
									//----------Query 6 TO Move ANd Delete
						
				$select=mysqli_query($conn,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query4 =mysqli_query($db, "INSERT INTO ` inw_mailto_client` (id,`inw_id`,mail_to,subject,message,created_by,created_on) VALUES ('".$post5['id']."','".$post5['inw_id']."','".$post5['mail_to']."', '".$post5['subject']."','".$post5['message']."','".$_SESSION['user_id']."',NOW());");
			    
				if($query4)
				{
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM  inw_mailto_client inw_id = '".$post['id']."'");
						    $count++;
						}	

				}
                		else
					{
						$select=mysqli_query($conn,"SELECT * FROM  inw_mailto_client WHERE inw_id = '".$post['id']."'");
				$post5=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_mailto_client` SET id='".$post5['id']."', inw_id='".$post5['inw_id']."',mail_to='".$post5['mail_to']."',subject= '".$post5['subject']."',message = '".$post5['message']."',created_by='".$_SESSION['user_id']."', created_on = NOW() WHERE inw_id = '".$post['id']."' ");
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_mailto_client WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_mailto_client WHERE inw_id = '".$post['id']."'");
							$count++;
						}	
					}				
				//----------Query 7 TO Move ANd Delete
						
						
				$select=mysqli_query($conn,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query3 =mysqli_query($db, "INSERT INTO ` inw_revisions_dtl` (r_id,`inw_id`,revision_count,received_date,due_date,correction_pages,comments,file_name,created_by,created_on,type) VALUES ('".$post6['r_id']."','".$post6['inw_id']."','".$post6['revision_count']."', '".$post6['received_date']."','".$post6['due_date']."','".$post6['correction_pages']."','".$post6['comments']."','".$post6['file_name']."','".$_SESSION['user_id']."',NOW(),'".$post6['type']."');");
			    
				if($query3)
				{
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM  inw_revisions_dtl inw_id = '".$post['id']."'");
							$count++;
						}
				}
                  	else
					{
						$select=mysqli_query($conn,"SELECT * FROM  inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
				$post6=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_revisions_dtl` SET r_id='".$post6['r_id']."',inw_id='".$post6['inw_id']."',revision_count='".$post6['revision_count']."', received_date='".$post6['received_date']."',due_date='".$post6['due_date']."', correction_pages='".$post6['correction_pages']."',comments='".$post6['comments']."',file_name='".$post6['file_name']."',created_by='".$_SESSION['user_id']."',created_on=NOW(),type='".$post6['type']."' WHERE inw_id = '".$post['id']."'");
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_revisions_dtl WHERE inw_id = '".$post['id']."'");
							$count++;
						}
					}	
					
					// Query 8 To Move DataBase
					
				 $select=mysqli_query($conn,"SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
				 $post22=$select->fetch_assoc();
						
				 $query6 =mysqli_query($db, "INSERT INTO `inw_volume_issues` (vi_id,`cust_id`,j_id,volume_number,issue_number,issue_count,received_date,due_date,correction_pages,comments,created_by,created_on,work_dir,status,stage,dispatch_dir) VALUES ('".$post22['vi_id']."','".$post22['cust_id']."','".$post22['j_id']."', '".$post22['volume_number']."','".$post22['issue_number']."', '".$post22['issue_count']."', '".$post22['received_date']."', '".$post22['due_date']."', '".$post22['correction_pages']."', '".$post22['comments']."', '".$post22['created_by']."', '".$post22['created_on']."', '".$post22['work_dir']."', '".$post22['status']."', '".$post22['stage']."', '".$post22['dispatch_dir']."'");
			    
				if($query6)
				{
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
							$count++;
						} 
				}
	            else
					 {
						 $select=mysqli_query($conn,"SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
						$post22= $select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_volume_issues` SET  vi_id = '".$post22['vi_id']."', cust_id = '".$post22['cust_id']."',j_id ='".$post22['j_id']."',volume_number = '".$post22['volume_number']."',issue_number = '".$post22['issue_number']."',issue_count='".$post22['issue_count']."',received_date= '".$post22['received_date']."',due_date='".$post22['due_date']."',correction_pages='".$post22['correction_pages']."',comments='".$post22['comments']."',created_by='".$post22['created_by']."',created_on='".$post22['created_on']."',work_dir='".$post22['work_dir']."',status='".$post22['status']."',stage='".$post22['stage']."',dispatch_dir='".$post22['dispatch_dir']."' ");
			    
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_volume_issues WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND vi_id = '".$vi_id."'");
							$count++;
						}
					 }				
						

				// Query 9 To Move DataBase
					
				 $select=mysqli_query($conn,"SELECT * FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
				 $post22=$select->fetch_assoc();
						
				 $query9 =mysqli_query($db, "INSERT INTO `inw_issue_dispatch_history` (idh_id,`cust_id`,j_id,a_id,vi_id,from_mail,to_mail,mail_text,sent_by,stage,dispatch_type,sent_date) VALUES ('".$post22['idh_id']."','".$post22['cust_id']."','".$post22['j_id']."', '".$post22['a_id']."','".$post22['vi_id']."', '".$post22['from_mail']."', '".$post22['to_mail']."', '".$post22['mail_text']."', '".$post22['sent_by']."', '".$post22['stage']."', '".$post22['dispatch_type']."', '".$post22['sent_date']."'");
				 
				 if($query9)
				{
				
				$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							$query33 = mysqli_query($conn,"DELETE FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
							$count++;
						} 
				}
	          else
					{
						
						$select=mysqli_query($conn,"SELECT * FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
					$post44=$select->fetch_assoc();
							
			    $query2 =mysqli_query($db, "UPDATE `inw_issue_dispatch_history` SET  idh_id = '".$post44['idh_id']."', cust_id = '".$post44['cust_id']."',j_id ='".$post44['j_id']."',a_id = '".$post44['a_id']."',vi_id = '".$post44['vi_id']."',from_mail='".$post44['from_mail']."',to_mail= '".$post44['to_mail']."',mail_text='".$post44['mail_text']."',sent_by='".$post44['sent_by']."',stage='".$post44['stage']."',dispatch_type='".$post44['dispatch_type']."',sent_date='".$post44['sent_date']."' ");
						
						
						$check=mysqli_query($db,"SELECT EXISTS(SELECT * FROM inw_issue_dispatch_history  WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."');");
						$check_data= $check->fetch_assoc();
						// print_r($check_data);
						$check_d=implode("",$check_data);
						if($check_d == 1)
						{
							// If Data Successfully Tranferred To The WOrkFLow CLone
							// echo 1;
							
							$query33 = mysqli_query($conn,"DELETE FROM inw_issue_dispatch_history WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND idh_id = '".$idh_id."'");
							// header("location:volume_archive.php?msg=Successfully Archived"); 
							$count++;
						}
					}
				 
            	if($count == 9)
					{
						// echo "True";
						global $value;
						$value = 1;
					}
					else
					{
					   // echo "False";
					   global $value;
                       $value = 2;		
					}						
						
						
		}

                       
	    
//===========CONDITION TO CHECK FOLDER IS EMPTY OR SQL OPERATIONS FAILED==============================================	
	 }
/* 
                     if($value == 1)
                     {
						 
                  	    header("location:volume_archive.php?msg=Successfully Archived"); 
						// echo "Successfully Archived";
					
                     }
                     else if ($value == 2)
                     {
	                      header("location:volume_archive.php?msg=Successfull Folder Movements|| DB Movements Modified");
						     // echo "Successfull Folder Movements|| DB Movements Modified";
						
                     } */
                     						 
 }
 else
 {
	 //echo "No Completed Status in Database";
	  header("location:volume_archive.php?msg=All Articles Are Moved"); 
	  // echo "All Articles Are Moved";
	  // exit;
	
 } 
	

     //----------------------------CODING ENDS==================FUNCTION CALLING========================
flock($fp, LOCK_UN);
}
 
else 
{
	
	// echo "File Is Busy Please Wait || Archive On Hold";
 header("location:volume_archive.php?msg=File Is Busy Please Wait || Archive On Hold"); 
 // exit;
 /*  $delete=shell_exec('schtasks /delete /tn "PHP Cron-Revert" /f');
	 $delete1=shell_exec('schtasks /delete /tn "PHP Multi_Revert" /f'); */
}
fclose($fp); 

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

	
		
		
		
?>
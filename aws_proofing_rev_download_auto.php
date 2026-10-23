<?php 
include ('dbc.php'); 
include ('awsdb.php'); 

// File path where you want to append to the file
$aws_log_filePath = "aws_download_log.txt";

// Open the file in append mode
$aws_log_file = fopen($aws_log_filePath, "a");


/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function rmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } 
		else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				rmove($f->getRealPath(), "$dest/$f");
				if(empty($f))
				{
					unlink($f);
				}
				// unlink($f->getRealPath());
			}
        }
    }
    //unlink($src);
}


//// TVL  Proof Articles Details ////


/////// Get the AWS proof Article Details /////
//$query = "SELECT * FROM inw_proof_dtl WHERE pub_id != '' AND EditingStatus='3' AND status ='2' and is_author_submitted = '1'  ORDER BY `id` DESC;";

$query = "SELECT inp.* FROM inw_proof_dtl as inp, inw_inward_dtl as inw WHERE inp.project_id = inw.id and inp.pub_id != '' AND inp.EditingStatus='3' AND inp.status ='2' and inp.is_author_submitted = '1' and inw.cust_id != 66 ORDER BY inp.id DESC;";

$proofs = $awsdb->query($query) or ($awsdb->error);
$aws_aca_cnt = $proofs->num_rows;

if( $aws_aca_cnt > 0 )
{	
	while ($proofsinfo = $proofs->fetch_assoc()) 
	{	
		
		if($proofsinfo['EditingStatus'] == '3' && $proofsinfo['status']=='2')
		{	
			fwrite($file, 'Started the download for AWS pub ID : '.$proofsinfo['pub_id']);		
			echo 'Started the download for AWS pub ID : '.$proofsinfo['pub_id'];	
			
			//// Get the worflow Steps ////
			$ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$proofsinfo['pub_id']."';";
			$ati_result = $db->query($ati_query) or ($db->error);
			$ati_toatl_count=$ati_result->num_rows;
			if($ati_toatl_count <= 0)
			{			
				/// Adding the Article Down load status in DB ////
				$insert_article_tracking=mysqli_query($db,"insert into article_tracking_info(`pub_id`) values('".$proofsinfo['pub_id']."')");
			}
			
			if($proofsinfo['work_mode'] == 1)
			{
				/// Updating the File download status in DB ///
				//$article_tracking_info_update_query = "UPDATE article_tracking_info SET is_files_downloaded = '1' WHERE pub_id = '".$proofsinfo['pub_id']."';";
				//$db->query($article_tracking_info_update_query) or die($db->error);
			}
		
			$ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$proofsinfo['pub_id']."';";
			$ati_result = $db->query($ati_query) or ($db->error);
			$ati_exist_status = $ati_result->fetch_assoc();
			//////////////////
			//////////////////
			
			
						
			//// Get the Article details form Workflow
			$wf_art_qry = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$proofsinfo['pub_id']."' ";
			$result = $db->query($wf_art_qry) or ($db->error);	
			$app_res = $result->fetch_assoc();
			//$proofsinfo['pub_id'],$proofsinfo['id']
			
			$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$app_res['cust_id']."' ";
			$customers = $db->query($query) or ($db->error);
			$crow = $customers->fetch_assoc();
			
			//// Journal name ///	
			$query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res['j_id']."';";
			$steps = $db->query($query) or ($db->error);
			$jninfo = $steps->fetch_assoc();
		
		
		
			
			if($ati_exist_status['is_files_downloaded'] != 1 && $proofsinfo['work_mode'] > 0)
			{
				echo '<br>Created the folder as REV1 in Downloads pub ID : '.$proofsinfo['pub_id'];	
			/// Created the folder as REV1 in Downloads 
			 $target_dir = WF_DIR."/downloads/JOURNALS/".$crow['cust_name']."/".$jninfo['j_code']."/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/REV1";  // Upload directory */
			
			if (!(is_dir($target_dir))) 
			{
				if (!mkdir($target_dir, 0777, true)) 
				{
					die('Failed to create folders...'.$target_dir);
				}
			}	
			
	//// Download the proof Files ////
	echo '<br>Download the proof Files pub ID : '.$proofsinfo['pub_id'];
	 $download_path = "/production/JOURNALS/".$crow['cust_name']."/".$jninfo['j_code']."/".$app_res['volume_folder']."/".$app_res['pub_id']."/FP/Proofing/";
	
	
	$download_path_upldir = $download_path."uploads";		
	
	$ftp_server = "52.23.135.119";
	$ftp_username = "tsp";
	$ftp_password = 'T$p@2023';	
	
	///////// Start download the full folder of uploads ////
	$file_paths = array();
	if($proofsinfo['work_mode'] == 1)
	{
		$remoteDirectory = $download_path."uploads/";
		$file_paths = [
			$download_path.'correction_status.txt',
		];
		$localDirectory = $target_dir;
		include ('ftpfolderdownload.php');
		include ('ftpfiledownloader.php');	
	}
	else if( $proofsinfo['work_mode'] == 2 ){
		//$remoteDirectory = $download_path;
		$file_paths = [
			$download_path.'correction_status.txt',
			$download_path.$app_res['pub_id'].'.html',
			$download_path.$app_res['pub_id'].'_TRACK.pdf',
			$download_path.$app_res['pub_id'].'_queriesQA.html',
		];
		$localDirectory = $target_dir;
		$file_upload_status = '';
		include ('ftpfiledownloader.php');
	}
	
	
	if($success)
	{
		/// Updating the File download status in DB ///
		echo '<br>Updating the File download status in DB pub ID : '.$proofsinfo['pub_id'];
		$article_tracking_info_update_query = "UPDATE article_tracking_info SET is_files_downloaded = '1' WHERE pub_id = '".$app_res['pub_id']."';";
		$db->query($article_tracking_info_update_query) or die($db->error);
		
	}
	
	
	
	
	if(empty($file_upload_status))
	{
		/// Updating the File download status in DB ///
		echo '<br>Updating the File download status in DB pub ID : '.$proofsinfo['pub_id'];
		$article_tracking_info_update_query = "UPDATE article_tracking_info SET is_files_downloaded = '1' WHERE pub_id = '".$app_res['pub_id']."';";
		$db->query($article_tracking_info_update_query) or die($db->error);
	}
			}
	
	////////////////////////////////////////////////////////////
	///////////// Adding the Articles Workflow /////////////////
	////////////////////////////////////////////////////////////
	echo '<br>Adding the Articles Workflow pub ID : '.$proofsinfo['pub_id'];
	$ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$proofsinfo['pub_id']."';";
	$ati_result = $db->query($ati_query) or ($db->error);
	$ati_exist_status = $ati_result->fetch_assoc();
	
	if($ati_exist_status['is_files_downloaded'] == 1 && $ati_exist_status['is_workflow_updated'] != 1 && $app_res['status'] == "Client Review" && $app_res['stage'] == "FP")
	{
	
			
			$dueDate = date('Y-m-d', strtotime('now + 1day'));
			$receivedDate = date('Y-m-d', strtotime('now'));
			
			/*
			* Checking the due date is sunday or not
			* If due date is sunday then add 1 day to due date 
			* If due date is second saturday then add 2 days to due date 
			*/
			echo '<br>Checking the due date is sunday or not pub ID : '.$proofsinfo['pub_id'];
			
			$timestamp = strtotime($dueDate);
			$weekday= date("l", $timestamp );
			$normalized_weekday = strtolower($weekday);
			
			$firstOfMonth = strtotime(date("Y-m-01", $timestamp));
			$week_number = intval(date("W", $timestamp)) - intval(date("W", $firstOfMonth)) + 1;
			
			
			if($normalized_weekday == "sunday")
			{
				$dueDate =  date('Y-m-d', strtotime("+1 day", strtotime($dueDate)));
			}
			else if($normalized_weekday == "saturday" && $week_number == "2")
			{
				$dueDate =  date('Y-m-d', strtotime("+2 day", strtotime($dueDate)));
			}
			
			
			//// Get the worflow Steps ////
			$query = "SELECT * FROM `adm_workflow_master` WHERE wf_name = '".$jninfo['j_rev_wf']."';";
			$steps = $db->query($query) or ($db->error);
			$deptinfo = $steps->fetch_assoc();
			$dept_info = explode(',',$deptinfo['wf_code']);
			
			/////// Get the department name /////
			$result = $db->query("SELECT * FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			$fn=$app_res['pub_id'];
			$artile_name= $fn;	
													
			$Cn= $crow['cust_name'];
			$jn= $jninfo['j_code'];	
			
			$artile_name= $artile_name;		
			$download_folder = 'downloads';
			$production_folder = 'production';
			$journal_folder = 'JOURNALS';
			$production = 'production';		
			
			$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
						
			/// Move the Previos stage production Files current stage production ///
			echo '<br>Move the Previos stage production Files current stage production pub ID : '.$proofsinfo['pub_id'];

			$production_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/FP/";
			$production_dest_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/REV1/";
			if(is_dir($production_dir))
			{
				rmove($production_dir, $production_dest_dir);
			}
			//// Crete the production Next Dept folder ///
			if (!(is_dir($production_dest_dir.$dept_res['dept_name']))) 
			{
				if (!mkdir($production_dest_dir.$dept_res['dept_name'], 0777, true)) 		{
					die('Failed to create folders...'.$production_dest_dir.$dept_res['dept_name']);
				}
			}					

			$query = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$app_res['pub_id']."'"; 
			$inw_inward_dtls = $db->query($query) or ($db->error);
			$inw_inward_dtl = $inw_inward_dtls->fetch_assoc();			
			
			/// Adding author queres in DB ////
			if($proofsinfo['work_mode'] == 2)
			{
				
				$select_aq=mysqli_query($awsdb,"select * from  authorquery where articlekey='".$proofsinfo['id']."'");
				while($res_aq=mysqli_fetch_array($select_aq))
				{
					$insert_aq=mysqli_query($db,"insert into authorquery(`project_id`,`id`,`articlekey`,`query_id`,`question`,`answer`,`status`,`querystatus`,`Querytype`) values('".$proofsinfo['project_id']."','".$res_aq['id']."','".$res_aq['articlekey']."','".$res_aq['query_id']."','".$res_aq['question']."','".$res_aq['answer']."','".$res_aq['status']."','".$res_aq['querystatus']."','".$res_aq['Querytype']."')");
				}
			}
			if($inw_inward_dtl['status'] == "Client Review" && $inw_inward_dtl['stage'] == "FP")
			{
				$insert_query = "INSERT INTO `inw_revisions_dtl` (`inw_id`,revision_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`, `type`) VALUES ('".$inw_inward_dtl['id']."','1','".$receivedDate."', '".$dueDate."', '".$inw_inward_dtl['page_count']."', '', '".$production_dest_dir."','1', NOW(),'REV');";	
	
				//echo $insert_query; exit;
				
			$chk_rev_cnt_query = "SELECT inw_id FROM `inw_revisions_dtl` WHERE `inw_id` = '".$app_ress['id']."' AND type = 'REV' AND revision_count = 1;";
			$chk_rev_cnt_result = $db->query($chk_rev_cnt_query) or ($db->error);
			$chk_rev_toatl_count = $chk_rev_cnt_result->num_rows;
			if($chk_rev_toatl_count == 0)
			{
				$db->query($insert_query) or die($db->error);
			}			
				
				
				/// update the stage and department and status in inw details table /////
				echo '<br>update the stage and department and status in inw details table pub ID : '.$proofsinfo['pub_id'];
	
				$prj_update_query="UPDATE inw_inward_dtl SET stage = 'REV1', wf_id = '".$deptinfo['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = '".$inw_inward_dtl['id']."' ";
				$db->query($prj_update_query) or die($db->error);
				
				
				/// Updating the File download status in DB ///
		$article_tracking_info_update_query = "UPDATE article_tracking_info SET is_workflow_updated = '1' WHERE pub_id = '".$app_res['pub_id']."';";
		$db->query($article_tracking_info_update_query) or die($db->error);	
			}
			
	}
	
	///// Updating the AWS Article status to 2 as completed 
	///////////////////////
	echo '<br>Updating the AWS Article status to 2 as completed pub ID : '.$proofsinfo['pub_id'];
	$ati_query = "SELECT * FROM `article_tracking_info` WHERE pub_id = '".$proofsinfo['pub_id']."';";
	$ati_result = $db->query($ati_query) or ($db->error);
	$ati_exist_status = $ati_result->fetch_assoc();
	
	if($ati_exist_status['is_files_downloaded'] == 1 && ( $ati_exist_status['is_workflow_updated'] == 1 || $inw_inward_dtl['stage'] != "FP"))
	{
		$ap_update_query = "UPDATE inw_proof_dtl SET is_author_submitted = '2' WHERE id = '".$proofsinfo['id']."';";
		
		$ap_update_proofs = $awsdb->query($ap_update_query) or ($awsdb->error);
	}
		
	
	
	}
	}
} 


			
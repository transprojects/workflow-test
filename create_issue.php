<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php';?>
<?php
/* received / due date -> yyyy-mm-dd. The date range picker posts dd-mm-yyyy; the old picker posted mm/dd/yyyy. Returns '' when it is neither. */
function ci_date_ymd($d)
{
	$d = trim(html_entity_decode((string)$d));
	if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $d, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) { return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]); }
	if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $d, $m) && checkdate((int)$m[1], (int)$m[2], (int)$m[3])) { return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]); }
	return '';
}
?>
    <?php
/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
 
function rev_rmove($src, $dest){

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
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				rmove($f->getRealPath(), "$dest/$f");
				//unlink($f->getRealPath());
				if(empty($f))
				{
					//unlink($f);
				}
			}
        }
    }
    //unlink($src);
}
 
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
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) {
            rmove($f->getRealPath(), "$dest/$f");
            //rmdir($f->getRealPath());
        }
    }
    //rmdir($src);
}

function frcopy($src, $dest){

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
        if($f->isFile()) {
            copy($f->getRealPath(), "$dest/" . $f->getFilename());
			///// Keep the same file time //////
			$dt = filemtime($f->getRealPath());
			if ($dt != FALSE)
			{ 
				touch("$dest/" . $f->getFilename(), $dt);
			}
						
        } else if(!$f->isDot() && $f->isDir()) {
            frcopy($f->getRealPath(), "$dest/$f");
        }
    }
}

if(isset($_POST['re_assign']) &&  $_POST['re_assign'] == 'Assign' && $_POST['new_volume_number']!='')
{
	if(!empty($_POST['article_ids'])) 
	{
		foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
		}
		
				
		$to_id_array = explode('|',$post['new_volume_number']);
		$to_vid = $to_id_array[0];
		$to_volume_number = $to_id_array[1];
		$to_issue_number = $to_id_array[2];
		
		/// Get the Existing Stage of the Assigned Volume and issue ////
		$result = $db->query("SELECT * FROM `inw_volume_issues` WHERE `vi_id` = ".$to_vid." "); 		
		$app_vi_res = $result->fetch_assoc();
				
		
		$insert_query = "INSERT INTO `inw_vlume_issue_tracker` (`from_vid`, `to_vid`, `article_ids`,type,`created_by`, `created_on`) VALUES ('".$to_vid."', '".$to_vid."', '".implode(',',$_POST['article_ids'])."', 'Re-Assigned','".$_COOKIE['user_id']."', NOW());";
		$db->query($insert_query) or idr_fail('create_issue.php', '', 'The issue could not be saved: '.$db->error);
		
	//// Get the workflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
	$toatl_count=$result->num_rows;
	$dept_res = array();
	if($toatl_count>0)
	{
		$dept_res = $result->fetch_assoc();
	}
	
		
		foreach ($_POST['article_ids'] as $aid) 
		{
			$id = filter($aid);
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id." "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			if($toatl_count>0)
			{
				$app_res = $result->fetch_assoc();
				
				/// This is added for group folder moving to issue
				$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
				
				$newvolume_folder = VOLUME_PREFIX.$to_volume_number.$to_issue_number;
				
				/// Productoin articles moving to new vloume and issue folder
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				
				
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				/// Downloads articles moving to new vloume and issue folder
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				 $fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				 $dest = $fdest.$app_vi_res['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
				mkdir($fdest, 0777, true);
				mkdir($dest, 0777, true);
				rmove($fsrc,$fdest);
				

				$prj_update_query="UPDATE inw_inward_dtl SET `volume_folder` ='".$newvolume_folder."',`stage` ='".$app_vi_res['stage']."',`v_issue_id` ='".$to_vid."',`prev_stage` ='".$app_res['stage']."',`prev_workflow` ='".$app_res['wf_id']."',`prev_dept_id` ='".$app_res['department_id']."',`prev_status` ='".$app_res['status']."',wf_id = '".$post['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			
				
			
			//// Create a Folder under ISSCUR1 production ///
			$app_res['work_dir'] = str_replace('downloads','production',$app_res['work_dir']);
			$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/";
			$dest = $fdest.$app_vi_res['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			
			/// Move the Previos stage production Files current stage production ///
			$production_dir = $fdest.$app_res['stage']."/";		
			$production_dest_dir = $fdest.$app_vi_res['stage']."/";	
					
			//frcopy($production_dir, $production_dest_dir);
			rev_rmove($production_dir, $production_dest_dir);
			
				/// Move the Previos stage production Files current stage downloads ///
			
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/";
				
				$download_dir = $fdest.$app_res['stage']."/";		
		    	$download_dest_dir = $fdest.$app_vi_res['stage']."/";	
				rev_rmove($download_dir, $download_dest_dir);
			
			/////// Get the department name /////
		if($app_res['fig']>0)
		{
			//// Graphics Dept ID is : 9 ////
			
			$gresult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
			$gtoatl_count=$gresult->num_rows;
			$gdept_res = array();
			if($gtoatl_count>0)
			{
				$gdept_res = $gresult->fetch_assoc();
			}
			
			$dest = $fdest.$app_vi_res['stage']."/".$gdept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			$dest = $fdest.$app_vi_res['stage']."/".$gdept_res['dept_name']."/Print";   // PAG Folder 
			mkdir($dest, 0777, true);
			$dest = $fdest.$app_vi_res['stage']."/".$gdept_res['dept_name']."/Web";   // XML Folder 
			mkdir($dest, 0777, true);			
			//rcopy($src,$dest);
		
		}
		
			
				
			}

		}
		//exit;
	 }
	 
	header("location:manage_issues.php?msg=Successfully added"); 
	 exit();
}
if(isset($_POST['create_issue']) &&  $_POST['create_issue'] == 'Create') 
{
	if(!empty($_POST['article_ids'])) 
	{
		foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
	}	
	
	$post['recv_dt'] = ci_date_ymd($post['recv_dt']);
	$post['due_dt'] = ci_date_ymd($post['due_dt']);
	if ($post['recv_dt'] == '' || $post['due_dt'] == '')
	{
		/* nothing has been created yet - go back instead of saving a blank / wrong date */
		header("location:create_issue.php?ermsg=".urlencode("Please choose a valid received date and due date."));
		exit();
	}
	
	//// Get the worflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
	$toatl_count=$result->num_rows;
	$dept_res = array();
	if($toatl_count>0)
	{
		$dept_res = $result->fetch_assoc();
	}
	
	/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$post['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$post['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
			
	$newvolume_folder = VOLUME_PREFIX.$_POST['volume_number'].$_POST['issue_number'];
	
	$dl_destination_dir = WF_DIR."/downloads/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$newvolume_folder."/ISSCOR1/misc/";
	mkdir($dl_destination_dir, 0777, true);
	
	$work_dir = WF_DIR."/production/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$newvolume_folder;	
	mkdir($work_dir, 0777, true);
		
	//// Delivery DIR /////
	mkdir($work_dir."/Delivery/ISSCOR1/", 0777, true);
	
	if(empty($post['instructions'])){
		$post['instructions'] = 'NULL';
	}	
	if(empty($post['issue_count'])){
		$post['issue_count'] = '0';
	}
	
		
		$insert_query = "INSERT INTO `inw_volume_issues` (`cust_id`, `j_id`, `volume_number`,issue_number,issue_count,`received_date`, `due_date`, `correction_pages`, `comments`,`created_by`, `created_on`,work_dir,dispatch_dir,stage,starting_page_number,automation_process,automation_status,hlink_month,hlink_year, status) VALUES ('".$post['cust_id']."', '".$post['j_id']."', '".$post['volume_number']."', '".$post['issue_number']."','".$post['issue_count']."','".$post['recv_dt']."', '".$post['due_dt']."', '".$post['correction_pages']."', '".addslashes($post['instructions'])."','".$_COOKIE['user_id']."', NOW(),'".$work_dir."','".$work_dir."/Delivery/ISSCOR1/"."','ISSCOR1','".$post['starting_page_number']."','5','Waiting for Sequence number update','".$post['hlink_month']."','".$post['hlink_year']."', '');";
		
		$db->query($insert_query) or idr_fail('create_issue.php', '', 'The issue could not be saved: '.$db->error);
		
		$volume_issue_id = $db->insert_id;

		/// First issue-correction round (ISSCOR1) is recorded with the issue, so the job card shows its tab
		$db->query("INSERT INTO `inw_issue_corrections` (`vi_id`,`type`,`correction_count`,`received_date`,`due_date`,`correction_pages`,`comments`,`file_name`,`created_by`,`created_on`) VALUES ('".$volume_issue_id."','ISSCOR','1','".$post['recv_dt']."','".$post['due_dt']."','".(int)$post['correction_pages']."','".($post['instructions'] == 'NULL' ? '' : addslashes($post['instructions']))."',NULL,'".$_COOKIE['user_id']."',NOW())") or idr_fail('create_issue.php', '', 'The issue was saved but its ISSCOR1 record could not be created: '.$db->error);


		////// Upload Files to Server ///////////
		if(isset($_FILES['attachment']))
		{
			//// Check for zip file /////		
		$errors= array();
		$file_name = $_FILES['attachment']['name'];
		$file_size =$_FILES['attachment']['size'];
		$file_tmp =$_FILES['attachment']['tmp_name'];
		$file_type=$_FILES['attachment']['type'];
		
		if (strrpos($file_name, '.zip') === strlen($file_name)-strlen('.zip'))
		{
			$zip = new ZipArchive;
			$res = $zip->open($file_tmp);	
			//var_dump($res);		
			if ($res === TRUE)
			{
				$zip->extractTo($dl_destination_dir);
				$zip->close();  
			}
		}
		else
		{
			$Path     = $dl_destination_dir;
			move_uploaded_file($file_tmp, $Path.$file_name);				
		}				
	}
		////// Upload File to server END Here////
		
		foreach ($_POST['article_ids'] as $aid) 
		{
			$id = filter($aid);
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id." "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			if($toatl_count>0)
			{
				$app_res = $result->fetch_assoc();	
				
				/// This is added for group folder moving to issue 				
				$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];		
				
				//// Articles are in Delivery stage will move it to the Issue and volume folder //////
				if($app_res['status'] == 'Client Review')
				{
					
				/// Productoin articles moving to new vloume and issue folder
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				/// Downloads articles moving to new vloume and issue folder
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				$prj_update_query="UPDATE inw_inward_dtl SET `volume_folder` ='".$newvolume_folder."',`v_issue_id` ='". $volume_issue_id."',`prev_stage` ='".$app_res['stage']."',`prev_workflow` ='".$app_res['wf_id']."',`prev_dept_id` ='".$app_res['department_id']."',`prev_status` ='".$app_res['status']."',`stage` ='ISSCOR1',wf_id = '".$post['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			//// Create a Folder under Downloads ISSCUR1 ///			
			$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/";
			$dest = $fdest."ISSCOR1/";   // destination folder or file 
			if(!is_dir($dest))
			{
				mkdir($dest, 0777, true);
			}
			
			//// Create a Folder under Production ISSCUR1 ///
			$app_res['work_dir'] = str_replace('downloads','production',$app_res['work_dir']);
			$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/";
			$dest = $fdest."ISSCOR1/".$dept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			
			/// Move the Previos stage production Files current stage production ///
			$production_dir = $fdest.$app_res['stage']."/";		
			$production_dest_dir = $fdest."ISSCOR1/";
			/// We are moving the latest stage file to isscor1 folder//////			
			//frcopy($production_dir, $production_dest_dir);
			rev_rmove($production_dir, $production_dest_dir);
		
			/////// Get the department name /////
		if($app_res['fig']>0)
		{
			//// Graphics Dept ID is : 9 ////
			
			$gresult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
			$gtoatl_count=$gresult->num_rows;
			$gdept_res = array();
			if($gtoatl_count>0)
			{
				$gdept_res = $gresult->fetch_assoc();
			}
			
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/Print";   // PAG Folder 
			mkdir($dest, 0777, true);
			$dest = $fdest."ISSCOR1/".$gdept_res['dept_name']."/Web";   // XML Folder 
			mkdir($dest, 0777, true);			
			//rcopy($src,$dest);
		
		}
				
				}
				else
				{
					//// Here we updated the Temp stages to move the article automatically issue once complete all the stages //////
					/*$prj_update_query="UPDATE inw_inward_dtl SET `temp_volume_folder` ='".$newvolume_folder."',`temp_v_issue_id` ='". $volume_issue_id."',`v_issue_id` ='". $volume_issue_id."',`prev_stage` ='ISSCOR1',`prev_workflow` ='".$post['wf_id']."',`prev_dept_id` ='".$dept_info[0]."',`prev_status` ='".$dept_res['dept_name']."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);*/
			
				}
					
				
			}

		}
	 }
	 
	header("location:manage_issues.php?msg=Successfully added"); 
	 exit();
}

?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>
    
    <!-- Data Table CSS -->
    <style>
.overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.5);
	z-index: 1000;
	display: none;
}
.loader-container {
	position: fixed;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	z-index: 1001;
	display: none;
}
.loader {
	border: 4px solid #f3f3f3;
	border-top: 4px solid #3498db;
	border-radius: 50%;
	width: 40px;
	height: 40px;
	animation: spin 2s linear infinite;
}
 @keyframes spin {
 0% {
transform: rotate(0deg);
}
 100% {
transform: rotate(360deg);
}
}

.dataTables_wrapper .dataTables_filter{
        margin-bottom : 10px !important;
      }
    a {
        font-size : 0.925em !important;
    }
</style>
<style>
/* ---- Create Issue: same look as the other issue pages ---- */
.ci-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:20px;background:#eaf2fa;color:#1b6ca8;font-weight:600;}
.ci-chip b{display:inline-block;min-width:26px;padding:1px 9px;border-radius:20px;background:#e8742a;color:#fff;text-align:center;}
.ci-count{display:flex;justify-content:center;margin:14px 0 4px;}
.ci-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--c,#6b7785);}
/* the two forms under the table */
html,body{overflow-x:hidden;}
#validation-form .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;margin:0 !important;width:100% !important;max-width:none !important;}
#validation-form .addarticleforminner{background:#fff !important;border:1px solid #e4e9ef !important;border-radius:16px !important;box-shadow:0 4px 18px rgba(20,48,74,.07) !important;margin:0 0 16px !important;width:100% !important;max-width:none !important;padding:18px 22px !important;}
#validation-form .addarticleforminner>.container{padding:0 !important;max-width:none !important;}
#validation-form .formcont1heading{background:transparent !important;border:0 !important;padding:0 !important;width:auto !important;height:auto !important;display:flex;align-items:center;gap:8px;margin:0 0 14px !important;position:static !important;left:auto !important;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
#validation-form .formcont1heading:before{content:"";width:4px;height:16px;border-radius:3px;background:#e8742a;}
#validation-form label,#validation-form .control-label{font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;margin:0 0 4px !important;}
#validation-form .form-control,#validation-form .form-select,#validation-form textarea{height:38px;border:1px solid #d5dde6;border-radius:9px;font-size:13.5px;box-shadow:none;}
#validation-form textarea{height:auto;min-height:84px;padding:8px 12px;}
#validation-form .form-control:focus,#validation-form textarea:focus{border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);outline:0;}
#validation-form .input-group{flex-wrap:nowrap;}
#validation-form .input-group-addon{display:none;}
#validation-form .dvr-field{display:block !important;}
#validation-form .dvr-field .dvr{display:block;}
#validation-form .dvr-field label{display:block;margin:0 0 4px !important;}
#validation-form .col-sm-4.control-label{width:auto !important;max-width:none !important;flex:none !important;padding:0 !important;text-align:left;}
#validation-form .form-actions{margin:16px 0 0 !important;padding:0 !important;border:0 !important;background:transparent !important;}
#validation-form #create_issue,#validation-form #re_assign{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 22px;border-radius:10px;border:0;font-weight:700;font-size:13.5px;color:#fff;box-shadow:0 4px 12px rgba(232,116,42,.35);background:linear-gradient(135deg,#e8742a,#f08a45);transition:transform .15s,box-shadow .2s;}
#validation-form #re_assign{background:linear-gradient(135deg,#14304a,#1f4b73);box-shadow:0 4px 12px rgba(20,48,74,.3);}
#validation-form #create_issue:hover,#validation-form #re_assign:hover{transform:translateY(-2px);}
#validation-form label.error,#validation-form .help-block{color:#d64545;font-size:12px;text-transform:none;letter-spacing:0;}
#validation-form .d-flex.justify-content-start.align-items-center{gap:10px;}
</style>
    </head>
    
    <body>
    <div id="loader" class="loader-container">
      <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Book articles into a volume and issue</small><h1>Create Issue</h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>
    <div class="row">
          <div class="span12">
            <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   

        } ?>
          </div>
        </div>
    <div class="af-page"><div class="af-wrap">
        <form class="form-horizontal formouter" id="get_records" method="post" enctype="multipart/form-data">
          <div class="af-bar">
            <div class="af-field" style="min-width:240px;">
              <label>Customer <span style="color:#d64545">*</span></label>
              <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>
                  <option value="">Select customer</option>
                  <?php
					$sql_customers = $auth->showCustomerlist();
					foreach($sql_customers as $res_customers)
					{						
					if($_POST['cust_id']!="")
					{
					?>
                  <option <?php if ($res_customers['id']== $_POST['cust_id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
                  <?php
					}
					else
					{
					?>
                  <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
                  <?php
					}
					}
					?>
                </select>
            </div>
            <div class="af-field" style="min-width:240px;">
              <label>Journal <span style="color:#d64545">*</span></label>
              <?php
		$query_journals = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` where j_cust_id ='".$_POST['cust_id']."' ORDER BY `j_name` ASC ";
		$sql_journals = $db->query($query_journals) or ($db->error);		
		
		?>
                      <select id="j_id" name="j_id" class="form-select" data-placeholder="Choose">
                        <option value="">-Select-</option>
                        <?php while($res_journals = $sql_journals->fetch_assoc()){ ?>
                        <option value="<?php echo $res_journals['j_id']; ?>" <?php if($_POST['j_id'] == $res_journals['j_id'] ){?> selected <?php } ?>><?php echo $res_journals['j_code']; ?></option>
                        <?php } ?>
                      </select>
            </div>
            <div class="af-actions" style="margin-left:0;">
              <button type="submit" value="Get Records" name="get_records_btn" id="get_records_btn" class="af-btn primary"><i class="fa fa-search"></i> Get Records</button>
              <a class="af-btn" href="create_issue.php" title="Reset"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
        </form>
      </div></div>

    <div class="container-fluid">
    <?php 
					if($_POST['j_id'] >0)
					{
						
					?>
              <form class="form-horizontal" id="validation-form" method="post" action="create_issue.php" enctype="multipart/form-data">
                <input type="hidden" name="cust_id" value="<?php echo $_POST['cust_id'];?>">
                <input type="hidden" name="j_id" value="<?php echo $_POST['j_id'];?>">
				 <div class="ci-count"><span class="ci-chip"><i class="fa fa-check-square-o"></i> <b id="count-checked-checkboxes">0</b> articles selected</span></div>
				<div class="af-page"><div class="af-card">
	   			<div class="table-responsive">
       			<table id="dynamic-table" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
                  <thead>
                    <tr>
                      <th class="center"> </th>
                      <th>Customer</th>
                      <th>Journal</th>
                      <th>Article</th>
                      <th>Due Date</th>
                      <th>Created On</th>
                      <th class="hidden-480">Manuscript</th>
                      <th class="hidden-480">Fig</th>
                      <th class="hidden-480">Table</th>
                      <th class="hidden-480">Stage</th>
                      <th class="hidden-480">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
					//echo $_COOKIE['user_level'];
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				 $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_POST['cust_id']." and wd.j_id = ".$_POST['j_id']." and wd.v_issue_id <=0 and wd.status = 'Client Review' ORDER BY `id` DESC ";
			}			
		}
		else
		{
			  $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_POST['cust_id']." and wd.j_id = ".$_POST['j_id']." and wd.v_issue_id <=0 and wd.status = 'Client Review' ORDER BY `id` DESC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';   // no due-date / priority highlight while booking an issue
		?>
                    <tr>
                      <td class="center"><label class="pos-rel">
                          <input type="checkbox" class="common-checkbox" name="article_ids[]" value="<?php echo $row['id']; ?>" />
                          <span class="lbl"></span> </label></td>
                      <td><span class="af-person"><span class="af-av"><?php echo strtoupper(substr(trim($row['cust_name']), 0, 1)); ?></span><?php echo $row['cust_name']; ?></span></td>
                      <td><span class="af-chip-j"><?php echo $row['j_code']; ?></span></td>
                      <td class="hidden-480"><span class="af-art"><?php echo $row['pub_id']; ?></span></td>
                      <td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                      <td><?php echo (date('d-m-Y',strtotime($row['created_dt']))); ?></td>
                      <td><?php echo $row['manuscript_count']; ?></td>
                      <td><?php echo $row['fig']; ?></td>
                      <td class="hidden-480"><?php echo $row['tab']; ?></td>
                      <td class="hidden-480"><span class="af-stage" style="--c:#1b6ca8"><?php echo $row['stage']; ?></span></td>
                      <td class="hidden-480"><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span></td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
				</div>
				</div></div>
				<div class="af-page" style="padding-top:0;">
                <div class="row">
				<div class="col-12 col-lg-7">
				<div class="container addarticleformouter">
				<div class="container addarticleforminner m-5">
        		  <div class="container mt-4">
    		    <div class="formcont1heading">Create New Volume and Issue</div>
            	<div class="row mt-1 g-4 addspacing">
                            <div class="col-12 col-sm-6 col-lg-6">
                              <label class="mb-2" for="name">Enter Volume Number:</label>
                              
                                <div class="clearfix">
                                  <input type="number" id="volume_number" name="volume_number" minlength="2" maxlength="5"  class="form-control required" />
                                </div>
                              </div>
                          
                           
                            <div class="col-12 col-sm-6 col-lg-6">
                              <label class="mb-2" for="name">Enter Issue Number:</label>
                              
                                <div class="clearfix">
                                  <input type="text" id="issue_number" name="issue_number" minlength="2" maxlength="10" class="form-control required" />
                                </div>
                              </div>
                            
						 
                            <div class="col-12 col-sm-6 col-lg-6">
                              <label class="mb-2" for="name">Hyperlink Month:</label>
                              
                                <div class="clearfix">								
                                 <select name="hlink_month" id="hlink_month" class="form-control required">
									<option selected value="January">January</option>
									<option value="February">February</option>
									<option value="March">March</option>
									<option value="April">April</option>
									<option value="May">May</option>
									<option value="June">June</option>
									<option value="July">July</option>
									<option value="August">August</option>
									<option value="September">September</option>
									<option value="October">October</option>
									<option value="November">November</option>
									<option value="December">December</option>
                                </select>
                                </div>
                              </div>
                        
						 
                            <div class="col-12 col-sm-6 col-lg-6">
                              <label class="mb-2" for="name">Hyperlink Year:</label>
                              
                                <div class="clearfix">
                                  <input type="number" id="hlink_year" name="hlink_year" minlength="4" maxlength="5" class="form-control required" />
                                </div>
                              </div>
                          
                           <div class="col-12 col-sm-6 col-lg-6 dvr-field">
                            <label class="mb-2">Received Date &rarr; Due Date:</label>
                            <div class="dvr" data-placeholder="Select received and due dates" data-from-name="recv_dt" data-to-name="due_dt"></div>
                          </div>
                          <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
                          
                           <div class="col-12 col-sm-6 col-lg-6">
  							 <label class="mb-2" for="name">Issue Starting Page Number:</label>
                            
                              <div class="input-group">
                                <input type="number" id="starting_page_number" name="starting_page_number" class="required form-control" value="" />
                              </div>
                            </div>
						  
                           <div class="col-12 col-sm-6 col-lg-6">
                            <label class="mb-2" for="name">Correction Pages:</label>
                            
                              <div class="input-group">
                                <input type="number" id="correction_pages" name="correction_pages" class="required form-control" value="" />
                              </div>
                            </div>
                          
                           <div class="col-12 col-sm-6 col-lg-6">
                            <label class="mb-2" for="name">Stage:</label>
                              <div class="clearfix">
                                <select name="stage" class="form-control required">
                                  <option value="ISSUE" selected>Issue</option>
                                </select>
                              </div>
                            </div>
                          
                           <div class="col-12 col-sm-6 col-lg-6">
                            <label class="mb-2" for="email">Choose Workflow:</label>
                              <div class="clearfix">
                                <?php
									$j_cust_id = filter($_POST['j_id']);
									$query = "SELECT j_issue_wf FROM `adm_journals` WHERE j_id = '".$j_cust_id."'";
									$customers = $db->query($query) or ($db->error);
									$jrow = $customers->fetch_assoc();				
									$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_name IN ('".$jrow['j_issue_wf']."') ORDER BY `wf_code` ASC ";
									$customers = $db->query($query) or ($db->error);		
									?>
                                <select id="wf_id" name="wf_id" class="form-control required" data-placeholder="Choose">
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['wf_id']; ?>"><?php echo $row['wf_name']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                          
                           <div class="col-12 col-sm-6 col-lg-6">
                            <label class="mb-2" for="name">Instructions:</label>
                              <div class="clearfix">
                                <textarea name="instructions" id="instructions" rows="5" cols="100" style="width: 100%"></textarea>
                              </div>
                            </div>
                        
                            <div class="col-12 col-sm-6 col-lg-6">
                            <label class="col-sm-4 control-label ">Attachment:</label>
                           
                              <div id="form-attachments">
                                <input class="form-control" type="file" name="attachment" accept="" />
                              </div>
                          </div>
                          
                        </div>
						<div class="form-actions center mb-4 d-flex justify-content-center">
						  <button type="submit" id="create_issue" value="Create" name="create_issue" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Create Issue</button>
						</div>
                      </div>
                    </div>
                  </div>
				  </div>
				
    			 
				  <div class="col-12 col-lg-5">
				  <div class="container addarticleformouter">
				<div class="container addarticleforminner m-5">
                      <div class="formcont1heading mt-4" style="position: relative; left: 12px">
                        <div class="">Assign Existing Volume and Issue</div>
                      </div>
                      <div >
                        <div class="d-flex justify-content-start align-items-center mt-3 p-2" style="width: 100%">
                              <label class="" for="name">Select Volume Number:</label>
                                <div class="clearfix ps-2" style="width: 50%">
                                  <?php
									$query = "SELECT `vi_id`,`volume_number`,issue_number FROM `inw_volume_issues` WHERE cust_id = '".$_POST['cust_id']."' AND j_id = '".$_POST['j_id']."'  ORDER BY `volume_number` ASC ";
									$volume_issue = $db->query($query) or ($db->error);								
									?>
                                  <select id="new_volume_number" name="new_volume_number" class="form-control required" data-placeholder="Choose">
                                    <option value="">-Select-</option>
                                    <?php while($row = $volume_issue->fetch_assoc()){ 
								  		$vol_issue_array[]=$row['volume_number'].$row['issue_number'];
								  	?>
                                    <option value="<?php echo $row['vi_id']; ?>|<?php echo $row['volume_number']; ?>|<?php echo $row['issue_number']; ?>" <?php if($_POST['volume_number'] ==$row['vi_id'] ){?> selected <?php } ?>><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></option>
                                    <?php } ?>
                                  </select>
                                </div>
							</div>
                          
                          <div class="form-actions center d-flex justify-content-center p-4" style="margin-bottom: 0px !important;">
                            <button type="submit" id="re_assign" value="Assign" name="re_assign" class="btn btn-sm btn-warning"><i class="fa fa-exchange"></i> Assign to Issue</button>
						  </div>
                  </div>
				</div>
				</div>
				</div>
				  </div>
				</div>
				</form>
              <?php } ?>
             
              
    <?php echo scripts(); ?> 
    <?php idr_error_script(); idr_error_popup('Cannot create the issue'); ?>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
</div>
    <!-- Date picker functionality --> 
    <script>
      		// Handle changes in the "countries" select element

$(document).ready(function(){
    $('#cust_id').on('change',function(){
        var j_cust_id = $(this).val();
        if(j_cust_id){
            $.ajax({
                type:'POST',
                url:'get_journals.php',
                data:'j_cust_id='+j_cust_id,
                success:function(html){
                    $('#j_id').html(html);                    
                }
            }); 
        }else{
            $('#j_id').html('<option value="">Select customer first</option>');
            
        }
    });

     $('#j_id').on('change',function(){
        var j_id = $(this).val();
        if(j_id){
            $.ajax({
                url:'<?php echo $_COOKIE['set_url'];?>/get_workflow.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                  //  $('#wf_id').html(html);   
                    populateSelecte_wf("wf_id",data);                 
                }
            });  
			
			$.ajax({
                url:'<?php echo $_COOKIE['set_url'];?>/get_group_folder.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                   // $('#group_folder_name').html(html);     
                    populateSelecte_gf("group_folder_name",data);                     
                }
            }); 
			
			$.ajax({
                type:'POST',
                url:'<?php echo $_COOKIE['set_url'];?>/get_journal_duration.php',
                data:'j_id='+j_id,
                success:function(html){
                    $('#journal_duration').html(html);   
                   // populateSelecte_jd("journal_duration",data);               
				    $('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				});					
                }
            }); 
			
			
        }else{
            $('#wf_id').html('<option value="">Select customer first</option>');
            
        }
    });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }
function populateSelecte_wf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.wf_name).val(item.wf_id));
            });
        }
    </script> 
    
    <!-- inline scripts related to this page --> 
<script type="text/javascript">
jQuery(function($) {
	
	$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
				//show datepicker when clicking on the icon
				.next().on((window.ace && ace.click_event) || 'click', function(){
					$(this).prev().focus();
				});
				

$('.common-checkbox').change(function() {
	var countCheckedCheckboxes = $('.common-checkbox:checked').length;      
        $('#count-checked-checkboxes').text(countCheckedCheckboxes);
    });

		
				
$('#create_issue').on('click', function(e) {
	if (!$('input[name=recv_dt]').val() || !$('input[name=due_dt]').val()) { idrError('Please choose the received date and the due date.', 'Dates required'); e.preventDefault(); return; }
	
	        var cnt = $("input[name='article_ids[]']:checked").length;
	        if (cnt < 1)
	        {
	            idrError('Please tick at least one article first.', 'No article selected');
	            e.preventDefault(); return;
	        }
			var viarr = [<?php foreach($vol_issue_array as $vival){ echo "'".$vival."',";} ?>];
			var new_v_i = $('#volume_number').val()+$('#issue_number').val();
			//alert(new_v_i);
			//alert(jQuery.inArray( new_v_i, viarr ));
			if(jQuery.inArray( new_v_i, viarr ) >= 0 && new_v_i!='')
			{
				idrError('This volume and issue already exists. Please try another number, or choose it from "Assign Existing Volume and Issue".', 'Volume / issue already created');
	            e.preventDefault();
			}
			
			$('#new_volume_number').removeClass('required');
			$('#volume_number').addClass('required');
			$('#issue_number').addClass('required');
			$('#recv_dt').addClass('required');
			$('#due_dt').addClass('required');
			$('#correction_pages').addClass('required');
			$('#hlink_year').addClass('required');
			$('#starting_page_number').addClass('required');
	        $('#validation-form').validate({
				rules:{
					volume_number:{
						minlength:2,
						maxlength:3
					},
					issue_number:{
						minlength:2,
						maxlength:5
					}
				}
			});	
	    });
	$('#re_assign').on('click', function(e) {
	        var cnt = $("input[name='article_ids[]']:checked").length;
	        if (cnt < 1)
	        {
	            idrError('Please tick at least one article first.', 'No article selected');
	            e.preventDefault();
	        }
			$('#new_volume_number').addClass('required');
			$('#volume_number').removeClass('required');
			$('#issue_number').removeClass('required');
			$('#recv_dt').removeClass('required');
			$('#due_dt').removeClass('required');
			$('#correction_pages').removeClass('required');
			$('#hlink_year').removeClass('required');
			$('#starting_page_number').removeClass('required');
	        $('#validation-form').validate();	
			
	    });		
		
		$('#get_records_btn').on('click', function(e) {$('#get_records').validate({errorClass: 'help-block'});	});	
								
			});
			jQuery(function($) {
				//initiate dataTables plugin
							$('#dynamic-table').DataTable( {
					dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
					//bAutoWidth: false,
					pageLength : 10,

					buttons: [
           	 {
            	    extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],
	
					// dom: 'Bfrtip',
         	

			    });
			
				
				
						
			})
			



		

		</script>
     
     
    
    </body>
    </html>
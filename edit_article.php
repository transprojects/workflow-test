<?php include "includes/includes.php";?>
<?php include_once "includes/change_log_mail.php";?>
<?php 

/* $mail_from = "techsupport@transforma.in";
$mail_pass = "Tr@n$!t2018"; */

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];
/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
 
 
 function nfilter($data) {
	$data = trim(htmlentities(strip_tags($data)));
	
	
	
	
	return $data;
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
            unlink($f->getRealPath());
        }
    }
    unlink($src);
}
function delete_directory($dirname) {
         if (is_dir($dirname))
           $dir_handle = opendir($dirname);
     if (!$dir_handle)
          return false;
     while($file = readdir($dir_handle)) {
           if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                     unlink($dirname."/".$file);
                else
                     delete_directory($dirname.'/'.$file);
           }
     }
     closedir($dir_handle);
     @rmdir($dirname);
     return true;
}
?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = nfilter($value);
	}

	$post['recv_dt'] = convert_mdy_ymd($post['recv_dt']);
	$post['accpt_dt'] = convert_mdy_ymd($post['accpt_dt']);
	$post['due_dt'] = convert_mdy_ymd($post['due_dt']);
	$post['due_dt_qr'] = convert_mdy_ymd($post['due_dt_qr']);
	$post['pub_dt'] = convert_mdy_ymd($post['pub_dt']);
	
	
	//// Same Stage and change the Department ///////
	if($_POST['dept_id'] > 0)
	{
		/////// Get the department name /////
		$result = $db->query("SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = ".$_POST['dept_id'].";"); 	
		$toatl_count=$result->num_rows;
		$dept_res = array();
		if($toatl_count>0)
		{
			$dept_res = $result->fetch_assoc();
		}
	}
	else
	{
		//// Get the worflow Steps ////
		$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
		$steps = $db->query($query) or ($db->error);
		$deptinfo = $steps->fetch_assoc();
		$dept_info = explode(',',$deptinfo['wf_code']);
		/////// Get the department name /////
		$result = $db->query("SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
		$toatl_count=$result->num_rows;
		$dept_res = array();
		if($toatl_count>0)
		{
			$dept_res = $result->fetch_assoc();
		}	
		
	}
	/// Get the Customer Details /////	
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$_POST['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
	//// Journal name ///	
	$query = "SELECT j_name,j_code,tvl_work_departments,j_hid FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
	$steps = $db->query($query) or ($db->error);
	$jninfo = $steps->fetch_assoc();
	$journal_title = $jninfo['j_name']; 
	
	//// Here we will get the department's to work in TVL //
	$tvl_dept_info = explode(',',$jninfo['tvl_work_departments']);	
		
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $_POST['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	
	/// Create the DIR
	$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	//$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
	//mkdir($dest, 0777, true);
			
	$destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME.$post['group_folder_name']."/$artile_name/".$post['stage']."/";
	
	$destination_artilce_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME.$post['group_folder_name']."/$artile_name/";
	
	
	if (!(is_dir($destination_dir))) {
	if (!mkdir($destination_dir, 0777, true)) {
    die('Failed to create folders...$jn');
	}
	}
	
	///// If the Groupfolder name is changed, We will copy the old files to New folder /////
	//// Upload the latest files to in this folder /////
	if($post['old_group_folder_name'] != $post['group_folder_name'])
	{
		$old_source_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME.$post['old_group_folder_name']."/$artile_name/";
		//echo $old_source_dir.'<br>'.$destination_dir;
		rmove($old_source_dir,$destination_artilce_dir);		
		delete_directory($old_source_dir);
		
		///// Production start here ///
		 $pold_source_dir = str_replace('downloads','production',$old_source_dir);
		 $pdestination_dir = str_replace('downloads','production',$destination_artilce_dir);
		rmove($pold_source_dir,$pdestination_dir);		
		delete_directory($pold_source_dir);
		
		
	}
	
	
	if(isset($_FILES['attachment']) && $_FILES['attachment']['name']!='')
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
				$zip->extractTo($destination_dir);
				$zip->close();  
			}
		}
		else
		{
			$Path     = $destination_dir;
			move_uploaded_file($file_tmp, $Path.$file_name);				
		}				
	}
		
	
	$ea_tx_id = 0;
	$update_dept_status = '';
	//echo $_POST['is_update_dept'];
	if($_POST['is_update_dept']>0)
	{
	$update_dept_status = ",wf_id = '".$post['wf_id']."',department_id = '".$dept_info[0]."',
	status = '".$dept_res['dept_name']."'";
	
	//// Insert the transanctions ///// 
		
		$insert=mysqli_query($db,"insert into inw_transactions(`project_id`, `stage`, `current_stage`, `created_by`, `created_dt`, 
    `assign_department`, `dept`, `current_status`, `completion_status`, `comments`, `process_user`, start_time, end_time) values ('".$post['id']."','".$post['stage']."','".$post['stage']."','".$_COOKIE['user_id']."',NOW(),'".$_POST['dept_id']."','".$_POST['old_department_id']."','Editing','6','Edit Article','".$_COOKIE['user_id']."',NOW(),NOW())");
	$ea_tx_id = (int)$db->insert_id;   /* the Job Card links its "View changes" pop-up to this row */
	
	}
	
	if($_POST['dept_id'] > 0)
	{
		$update_dept_status = ",wf_id = '".$post['wf_id']."',department_id = '".$_POST['dept_id']."',
	status = '".$dept_res['dept_name']."'";
	
		//// Insert the transanctions ///// 
		
		$insert=mysqli_query($db,"insert into inw_transactions(`project_id`, `stage`, `current_stage`, `created_by`, `created_dt`, 
    `assign_department`, `dept`, `current_status`, `completion_status`, `comments`, `process_user`, start_time, end_time) values ('".$post['id']."','".$post['stage']."','".$post['stage']."','".$_COOKIE['user_id']."',NOW(),'".$_POST['dept_id']."','".$_POST['old_department_id']."','Editing','6','Edit Article','".$_COOKIE['user_id']."',NOW(),NOW())");
	$ea_tx_id = (int)$db->insert_id;   /* the Job Card links its "View changes" pop-up to this row */
	
	}
	
	$issueorgroup_folder = '';
	if($post['old_group_folder_name'] != $post['group_folder_name'])
	{
		echo $issueorgroup_folder = ",issueorgroup_folder = '".$post['group_folder_name']."' ";
	}

	//// Worklocation assigning ////
	$worklocation = 'CHN';
	if(in_array($dept_res['dept_code'], $tvl_dept_info))
	{	
		$worklocation = "TVL";
	}
	
	$update_due_rev_date = '';
	if($post['rev_id'] > 0)
	{
		$db->query("UPDATE inw_revisions_dtl SET `received_date` = '".$post['recv_dt']."',
	`due_date` = '".$post['due_dt']."' WHERE id = ".$post['rev_id'].";") ;
	}
	elseif($post['stage']=='FP')
	{
		$update_due_rev_date = ",`recv_dt` = '".$post['recv_dt']."',
	`due_dt` = '".$post['due_dt']."'";
	}
	
	
	if(isset($post['highpriority']))
	{
		$highpriority=$post['highpriority'];
	}
	else
	{
		$highpriority=0;
	}
	if(isset($post['is_conversion']))
	{
		$is_conversion=$post['is_conversion'];
	}
	else
	{
		$is_conversion='no';
	}
	if(isset($post['late_dispatch']))
	{
		$late_dispatch = $post['late_dispatch'];
	}
	else
	{
		$late_dispatch = 'no';
	}
	
	if(isset($post['test_article']))
	{
		$test_article=$post['test_article'];
	}
	else
	{
		$test_article=0;
	}

	    $insert_query = "UPDATE inw_inward_dtl SET 
	`cust_id` = '".$post['cust_id']."',`at_id` = '".$post['at_id']."',
	j_id = '".$post['j_id']."',
	`journal_title` ='".$journal_title."', 
	`article_title` = '".$post['article_title']."',	
	`accpt_dt`= '".$post['accpt_dt']."', 	
	`pub_id` = '".$post['pub_id']."',
	`doi` = '".$post['doi']."', 
	`pub_dt` = '".$post['pub_dt']."', 
	`fig` = '".$post['fig']."',
	`tab` = '".$post['tab']."', 
	`location_id` = '".$post['location_id']."',
	stage = '".$post['stage']."',	
	work_dir = '".$work_dir."',
	manuscript_count = '".$post['manuscript_count']."',
	corresponding_author = '".$post['corresponding_author']."',
	notes = '".$post['notes']."',
	instructions = '".$post['instructions']."',
	graphics = '".$post['graphics']."',
	worklocation = '".$worklocation."',	
	highpriority = '".$highpriority."',	
	is_conversion = '".$is_conversion."',	
	late_dispatch = '".$late_dispatch."',
	test_article = '".$test_article."',
	img_color_figures = '".$post['img_color_figures']."',
	img_relabel_count = '".$post['img_relabel_count']."',
	img_redraw_count = '".$post['img_redraw_count']."',
	img_disk_count = '".$post['img_disk_count']."',
	anxillary_fig = '".$post['anxillary_fig']."',
	page_count = '".$post['page_count']."',
  complexity = '".$post['complexity']."',
  inw_type = '".$post['inw_type']."',
  corresponding_other_author= '".$post['corresponding_other_author']."',
  target_due_dt='".date('Y-m-d',strtotime($post['target_due_dt']))."',
  corresponding_mail = '".$post['corresponding_mail']."' ".$update_dept_status." ".$issueorgroup_folder." ".$update_due_rev_date." WHERE id = ".$post['id']."  ;";

		/// Snapshot the existing values (before change) for the change-log email ///
		$log_art_id = (int) $post['id'];
		$res_old_art = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$log_art_id);
		$old_art = ($res_old_art && $res_old_art->num_rows > 0) ? $res_old_art->fetch_assoc() : array();

		$db->query($insert_query) or die($db->error);
		
		//// Insert the instructions ///// 
		
		$insert=mysqli_query($db,"insert into inw_instructions_dtl(`inw_id`,`stage`,`instructions`,`created_by`,`created_dt`) values ('".$post['id']."','".$post['stage']."','".addslashes($post['instructions'])."','".$_COOKIE['user_id']."',NOW())");
		
		
		
		//exit;
		/// IF Figures in Article we create the Graphics Folder ////
		
		/////// Get the department name /////
		if($post['fig']>0)
		{
			//// Graphics Dept ID is : 9 ////
			
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/Print";   // PAG Folder 
			mkdir($dest, 0777, true);
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/Web";   // XML Folder 
			mkdir($dest, 0777, true);				
			//rcopy($src,$dest);
		
		}
			
						$deapart_name=mysqli_fetch_array(mysqli_query($db,"select *  FROM adm_dept_master WHERE dept_code = '".$_POST['dept_id']."'"));
						
						
		if($post['at_status']=='Query' || $post['at_status']=='Hold')
		{
					mysqli_query($db,"SET sql_mode = ''");

		$user_jour=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$jninfo['j_hid']."'"));
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['user_id']."'"));
			if($user_jour['user_email']!="")
			{
				$from_name=$user_jour['full_name'];
				$to=$user_jour['user_email'];
			}
			else
			{
				$from_name='Bosco';
				$to='bosco@transforma.in';
			}
			
			
			
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','".$post['at_status']."')");	
			
			
		



			$user_working_status = '<span class="label label-sm label bg-secondary text-white">'.$post['at_status'].'</span>';
			
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',`status` ='".$post['at_status']."',`aq_status` = NULL  WHERE id = ".$post['id']." ");
		}
		else if($post['at_status']=='Remarks')
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `remarks` ='".$post['message_query']."' WHERE id = ".$post['id']." ");
		
		$insert=mysqli_query($db,"insert into inw_transactions(`project_id`, `stage`, `current_stage`, `created_by`, `created_dt`,`current_status`,`completion_status`, `comments`, `process_user`, start_time, end_time, `dept`) values ('".$post['id']."','".$post['stage']."','".$post['stage']."','".$_COOKIE['user_id']."',NOW(),'Editing','1','Edited Article for Remarks - ".$post['message_query']."','".$_COOKIE['user_id']."',NOW(),NOW(),'16')");
	
		}
		else if($post['at_status']=='Query Reply')
		{
		 $user_working_status = '<span class="label label-sm label-success">'.$deapart_name['dept_name'].'</span>';
			
		if($_POST['stage']=='FP')
		{
		  $due_dt=",due_dt ='".$post['due_dt_qr']."',target_due_dt='".$post['due_dt_qr']."'";
		}
		if($_POST['stage']!='FP')
		{
		  $due_date=",due_date ='".$post['due_dt_qr']."',target_due_dt='".$post['due_dt_qr']."'";
		}


		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',`status` ='".$deapart_name['dept_name']."',`aq_status` ='".$post['at_status']."' ".$due_dt."  WHERE id = ".$post['id']." ");
		
		$prj_update_query_reply=mysqli_query($db,"UPDATE query SET reply_message = '".mysqli_real_escape_string($db, $post['message_query'])."',`reply_date` ='".date('Y-m-d H:i:s')."',`replied_ip` ='".$_SERVER['SERVER_ADDR']."'  WHERE aid = ".$post['id']." order by qid limit 1 ");
		
		$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','".$post['at_status']."')");	

		}
		elseif($post['at_status']=='Client Review')
		{
		 $user_working_status = '<span class="label label-sm label-success">Client Review</span>';
		 
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET department_id='0',status='Client Review',user_working_status = '".$user_working_status."' WHERE id = ".$post['id']." ");
		
		$insert=mysqli_query($db,"insert into inw_transactions(`project_id`, `stage`, `current_stage`, `created_by`, `created_dt`, 
    `assign_department`, `dept`, `current_status`, `completion_status`, `comments`, `process_user`, start_time, end_time) values ('".$post['id']."','".$post['stage']."','".$post['stage']."','".$_COOKIE['user_id']."',NOW(),'16','16','Editing','6','Article Status changed to Client Review using the Edit Article page.','".$_COOKIE['user_id']."',NOW(),NOW())");
		}
		else
		{
		 $user_working_status = '<span class="label label-sm label-success">'.$deapart_name['dept_name'].'</span>';
		 
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."' WHERE id = ".$post['id']." ");

		}
		
		////// Inserting the Query values to transactions table ////
		
		if(!empty($post['at_status'])){
			
		//$insert_tran_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`, end_time,comments ) VALUES ('".$post['id']."','16', '".$post['stage']."', '".$post['stage']."', '".$post['at_status']."', '', '', '".$_COOKIE['user_id']."', NOW(), '1', '".$_COOKIE['user_id']."',NOW(), NOW(), 'Edited Article for Remarks ".$post['message_query']."');";	
		//$db->query($insert_tran_query) or ($db->error);
		//$transaction_id = $db->insert_id;			
		}
		

		if($post['stage']!='FP' && $post['at_status']=='Query Reply')
		{
			$prj_update_querys=mysqli_query($db,"UPDATE inw_revisions_dtl SET  `due_date` ='".$post['due_dt_qr']."' WHERE inw_id = ".$post['id']." order by r_id desc limit 1 ");
		}
		else
		{
		    $prj_update_querys=mysqli_query($db,"UPDATE inw_revisions_dtl SET  `due_date` ='".$post['due_dt']."' WHERE inw_id = ".$post['id']." order by r_id desc limit 1 ");
		}
		
		
		if($post['dept_id']!="")
		{
					$update_query="UPDATE inw_transactions SET current_status ='Undo',`completion_status` ='Undo',`comments` ='Undo',`assign_department` ='". $post['dept_id']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."',process_user='".$_COOKIE['user_id']."' WHERE id = ".$post['tr_id']." ";

		}
		
		/// Email the change log (text attachment); a mail failure must not block the save ///
		try {
			$new_art = array(
				'cust_id' => $post['cust_id'], 'at_id' => $post['at_id'], 'j_id' => $post['j_id'],
				'journal_title' => $journal_title, 'article_title' => $post['article_title'],
				'accpt_dt' => $post['accpt_dt'], 'pub_id' => $post['pub_id'], 'doi' => $post['doi'],
				'pub_dt' => $post['pub_dt'], 'fig' => $post['fig'], 'tab' => $post['tab'],
				'location_id' => $post['location_id'], 'stage' => $post['stage'],
				'manuscript_count' => $post['manuscript_count'],
				'corresponding_author' => $post['corresponding_author'], 'notes' => $post['notes'],
				'instructions' => $post['instructions'], 'graphics' => $post['graphics'],
				'worklocation' => $worklocation, 'highpriority' => $highpriority,
				'is_conversion' => $is_conversion, 'late_dispatch' => $late_dispatch,
				'test_article' => $test_article, 'img_color_figures' => $post['img_color_figures'],
				'img_relabel_count' => $post['img_relabel_count'], 'img_redraw_count' => $post['img_redraw_count'],
				'img_disk_count' => $post['img_disk_count'], 'anxillary_fig' => $post['anxillary_fig'],
				'page_count' => $post['page_count'], 'complexity' => $post['complexity'],
				'inw_type' => $post['inw_type'],
				'corresponding_other_author' => $post['corresponding_other_author'],
				'target_due_dt' => date('Y-m-d', strtotime($post['target_due_dt'])),
				'corresponding_mail' => $post['corresponding_mail']
			);
			if ($_POST['is_update_dept'] > 0 || $_POST['dept_id'] > 0) {
				$new_art['wf_id'] = $post['wf_id'];
				$new_art['department_id'] = ($_POST['dept_id'] > 0) ? $_POST['dept_id'] : $dept_info[0];
				$new_art['status'] = $dept_res['dept_name'];
			}
			if ($post['old_group_folder_name'] != $post['group_folder_name']) {
				$new_art['issueorgroup_folder'] = $post['group_folder_name'];
			}
			if ($post['rev_id'] <= 0 && $post['stage'] == 'FP') {
				$new_art['recv_dt'] = $post['recv_dt'];
				$new_art['due_dt'] = $post['due_dt'];
			}
			$art_log = "[Article changes]\r\n";
			$art_changes = 0;
			foreach ($new_art as $f => $new) {
				$old = isset($old_art[$f]) ? (string) $old_art[$f] : '';
				if ($old !== (string) $new) {
					$art_log .= "  $f: '$old' -> '$new'\r\n";
					$art_changes++;
				}
			}
			if ($art_changes == 0) {
				$art_log .= "  (no change)\r\n";
			}
			if (!empty($post['at_status'])) {
				$art_log .= "\r\n[Status / query action]\r\n  Status set to: '".$post['at_status']."'\r\n";
				if (!empty($post['message_query'])) {
					$art_log .= "  Message: '".$post['message_query']."'\r\n";
				}
				$art_changes++;
			}
			/* keep a readable copy of what changed for the Job Card (a failure here must not block the save) */
			try {
				require_once __DIR__.'/edit_log_lib.php';
				$el_changes = el_diff($db, $old_art, $new_art);
				if (!empty($post['at_status'])) {
					$el_changes[] = array('label' => 'Article status', 'from' => '', 'to' => $post['at_status']);
					if (!empty($post['message_query'])) { $el_changes[] = array('label' => 'Query message', 'from' => '', 'to' => $post['message_query']); }
				}
				el_record($db, $log_art_id, $ea_tx_id, $post['stage'], $el_changes);
			} catch (Throwable $e) { error_log("edit_article edit-log failed: " . $e->getMessage()); }
			$art_ref = $log_art_id . (!empty($old_art['pub_id']) ? " / " . $old_art['pub_id'] : '');
			send_change_log_mail('Article', $art_ref, $art_log, $art_changes);
		} catch (Exception $e) {
			error_log("edit_article change-log mail failed: " . $e->getMessage());
		}

		header("location:".$_COOKIE['set_url']."/addedarticles?msg=Successfully added");
		die();	
}

/////////////// Update completed ///////////////
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
      </style>
<style id="ea-theme">
  /* ===== Edit Article - same look as Start Work (design layer only; fields, names and scripts are untouched) ===== */
  body{background:#f3f6fa !important;}
  .container-fluid:has(> .row > .col-8 > .articleheading){display:none;}   /* the old title bar is replaced by the header box below */

  /* header box */
  .ea-hero{width:min(1180px,calc(100% - 28px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,48,74,.08);animation:eaRise .45s ease;}
  .ea-hero > * + *{border-top:1px solid #e8eef4;}
  .ea-bar{display:flex;align-items:center;gap:14px;padding:11px 20px;}
  .ea-back{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:#14304a !important;text-decoration:none !important;transition:all .2s;cursor:pointer;}
  .ea-back:hover{background:#e8742a;border-color:#e8742a;color:#fff !important;transform:translateX(-2px);box-shadow:0 4px 10px rgba(232,116,42,.35);}
  .ea-title{margin:0;font-size:19px;font-weight:700;color:#14304a;line-height:1.2;} .ea-crumb{font-size:12px;color:#6b7785;}
  .ea-sp{flex:1;}
  .ea-req{display:flex;align-items:center;gap:10px;padding:6px 14px;border-radius:999px;background:#f4f8fc;border:1px solid #d6e4f1;font-size:12.5px;font-weight:700;color:#14304a;white-space:nowrap;}
  .ea-req .track{width:90px;height:7px;border-radius:7px;background:#e3ebf3;overflow:hidden;} .ea-req .fill{display:block;height:100%;width:0;border-radius:7px;background:linear-gradient(90deg,#e8742a,#1b6ca8);transition:width .5s ease;}
  .ea-req.done{background:#e8f7ef;border-color:#bfe6cf;color:#177a49;} .ea-req.done .fill{background:#1e9e61;}
  .ea-sum{padding:12px 20px;background:linear-gradient(120deg,#f4f9fe 0%,#e8f1fa 100%);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 18px;}
  .ea-sum small{display:block;font-size:10px;letter-spacing:.7px;text-transform:uppercase;color:#6b7785;}
  .ea-sum-id{font-size:18px;font-weight:700;color:#14304a;line-height:1.25;word-break:break-all;} .ea-sum-sub{font-size:12.5px;color:#5b6b7b;}
  .ea-chips{display:flex;flex-wrap:wrap;gap:8px;}
  .ea-chip{min-width:92px;padding:6px 11px;border-radius:8px;background:#fff;border:1px solid #dbe6f1;animation:eaPop .4s cubic-bezier(.2,.9,.3,1.3) backwards;}
  .ea-chip:nth-child(1){animation-delay:.08s;} .ea-chip:nth-child(2){animation-delay:.14s;} .ea-chip:nth-child(3){animation-delay:.2s;} .ea-chip:nth-child(4){animation-delay:.26s;} .ea-chip:nth-child(5){animation-delay:.32s;} .ea-chip:nth-child(6){animation-delay:.38s;}
  .ea-chip b{display:block;font-size:12.5px;font-weight:700;color:#14304a;}
  .ea-chip.hot{border-color:#f6d3bb;background:#fff7f1;} .ea-chip.hot b{color:#d2631d;} .ea-chip.red{border-color:#f3c4c4;background:#fdeaea;} .ea-chip.red b{color:#c0392b;}

  /* section cards, numbered */
  .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;max-width:none !important;width:100% !important;}
  .addarticleforminner{width:min(1180px,calc(100% - 28px)) !important;max-width:1180px !important;margin:16px auto 30px !important;padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;counter-reset:eastep;}
  #add_article > .container,#add_article > .content_right{background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:20px 26px 24px !important;margin:0 0 16px !important;max-width:none !important;width:100%;box-shadow:0 4px 18px rgba(20,48,74,.06);animation:eaRise .5s ease backwards;}
  #add_article > .container:nth-of-type(1){animation-delay:.05s;} #add_article > .container:nth-of-type(2){animation-delay:.12s;} #add_article > .container:nth-of-type(3){animation-delay:.19s;} #add_article > .content_right{animation-delay:.26s;}
  #add_article > .container.meta_nxtbtn,#add_article > .meta_nxtbtn{background:transparent;border:0;box-shadow:none;padding:0 !important;}
  .formcont1heading{position:relative;display:flex !important;align-items:center;gap:12px;background:none !important;color:#14304a !important;font-size:16px !important;font-weight:700 !important;padding:2px 0 12px !important;margin:0 0 14px !important;border:0 !important;border-bottom:1px solid #e4e9ef !important;clip-path:none !important;height:auto !important;line-height:1.4 !important;counter-increment:eastep;}
  .formcont1heading:before{content:counter(eastep);flex:0 0 auto;width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#e8742a,#1b6ca8);color:#fff;font-size:13px;font-weight:700;box-shadow:0 3px 8px rgba(27,108,168,.3);}
  .formcont1heading:after{display:none !important;}
  .formcont1heading:before{position:static !important;left:auto !important;top:auto !important;margin:0 !important;transform:none !important;}
  /* the four switches sit in one wrapping row */
  .addspacing .d-flex.align-items-end{flex-wrap:wrap;gap:12px 6px;} .addspacing .d-flex.align-items-end > div{width:auto !important;max-width:none !important;flex:0 0 auto !important;display:flex;align-items:center;}

  /* fields */
  .addspacing > [class*="col-"]{animation:eaRise .45s ease backwards;}
  .addspacing > [class*="col-"]:nth-child(1){animation-delay:.1s;} .addspacing > [class*="col-"]:nth-child(2){animation-delay:.14s;} .addspacing > [class*="col-"]:nth-child(3){animation-delay:.18s;} .addspacing > [class*="col-"]:nth-child(4){animation-delay:.22s;}
  .addspacing > [class*="col-"]:nth-child(5){animation-delay:.26s;} .addspacing > [class*="col-"]:nth-child(6){animation-delay:.3s;} .addspacing > [class*="col-"]:nth-child(n+7){animation-delay:.34s;}
  #add_article label{display:block;font-size:12px !important;font-weight:700 !important;letter-spacing:.3px;text-transform:uppercase;color:#5b6b7b !important;margin:0 0 6px !important;transition:color .2s;}
  #add_article label span[style*="red"]{color:#d64545 !important;}
  #add_article .addspacing > [class*="col-"]:focus-within > label{color:#1b6ca8 !important;}
  #add_article input[type=text],#add_article input[type=number],#add_article input[type=email],#add_article select,#add_article .form-select,#add_article .form-control{height:44px;width:100%;border:1px solid #cfd8e3 !important;border-radius:10px !important;background-color:#fbfcfe !important;padding:8px 13px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;transition:border-color .2s,box-shadow .2s,background .2s;}
  #add_article textarea{width:100%;min-height:96px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:11px 13px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;resize:vertical;transition:border-color .2s,box-shadow .2s,background .2s;}
  #add_article input:focus,#add_article select:focus,#add_article textarea:focus{border-color:#1b6ca8 !important;background:#fff !important;box-shadow:0 0 0 4px rgba(27,108,168,.14) !important;outline:0;}
  #add_article input.error,#add_article select.error,#add_article textarea.error{border-color:#d64545 !important;background:#fff8f8 !important;animation:eaShake .35s;}
  #add_article input.valid:not(:focus):not([type=checkbox]),#add_article select.valid:not(:focus){border-color:#b9e0cb !important;}
  #add_article label.error{display:block;color:#d64545 !important;font-size:12px !important;font-weight:600 !important;text-transform:none !important;letter-spacing:0;margin:5px 0 0 !important;animation:eaRise .25s ease;}
  #add_article input[readonly],#add_article input[disabled],#add_article select[disabled]{background:#f1f4f8 !important;color:#7b8a9a !important;}

  /* checkboxes become switches */
  #add_article input[type=checkbox]{appearance:none;-webkit-appearance:none;width:40px !important;height:22px !important;border-radius:22px;border:0 !important;background:#cfd8e3 !important;position:relative;cursor:pointer;vertical-align:middle;transition:background .25s;flex:0 0 auto;padding:0 !important;}
  #add_article input[type=checkbox]:before{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .25s cubic-bezier(.3,1.4,.5,1);}
  #add_article input[type=checkbox]:checked{background:#1e9e61 !important;} #add_article input[type=checkbox]:checked:before{transform:translateX(18px);}
  #add_article input[type=checkbox]:focus-visible{box-shadow:0 0 0 4px rgba(27,108,168,.2) !important;}
  #add_article input[type=checkbox] + label{display:inline-block !important;text-transform:none !important;font-size:13.5px !important;font-weight:600 !important;color:#33475b !important;letter-spacing:0;margin:0 14px 0 8px !important;cursor:pointer;vertical-align:middle;}

  /* upload */
  .content_right .dropboxdiv{padding:0;} .content_right .dropzone{border:2px dashed #b9c8d8 !important;border-radius:14px !important;background:#fbfcfe !important;min-height:150px;transition:border-color .2s,background .2s,box-shadow .2s;}
  .content_right .dropzone:hover,.content_right .dropzone.dz-drag-hover{border-color:#1b6ca8 !important;background:#eef6fc !important;box-shadow:0 0 0 5px rgba(27,108,168,.1);}
  .content_right .dropzone .dz-message{color:#5b6b7b;font-weight:600;}
  .uploadinfobox{background:#f8fafc;border:1px solid #e1e8ef;border-radius:12px;transition:transform .2s,box-shadow .2s;} .uploadinfobox:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(20,48,74,.1);}
  .fileinfopara{font-weight:700;color:#5b6b7b;font-size:12.5px;text-transform:uppercase;letter-spacing:.4px;margin:0;}

  /* buttons */
  .meta_nxtbtn .row{justify-content:flex-end;gap:12px;margin:0;} .meta_nxtbtn .form_btn{flex:0 0 auto;width:auto;padding:0;}
  .btn-next,.btn-next-cancel{height:46px;min-width:140px;padding:0 28px !important;border-radius:10px !important;font-weight:700;font-size:14.5px;cursor:pointer;transition:transform .15s,box-shadow .2s,filter .2s,background .2s;}
  .btn-next{border:0 !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;box-shadow:0 6px 16px rgba(27,108,168,.32);}
  .btn-next:hover{transform:translateY(-2px);filter:brightness(1.1);box-shadow:0 12px 24px rgba(27,108,168,.4);} .btn-next:active{transform:scale(.98);} .btn-next[disabled]{opacity:.65;cursor:wait;transform:none;}
  .btn-next-cancel{border:1px solid #cfd8e3 !important;background:#fff !important;color:#33475b !important;} .btn-next-cancel:hover{background:#f1f5f9 !important;transform:translateY(-1px);}
  .btn-next-cancel a{color:inherit !important;text-decoration:none !important;display:block;line-height:44px;margin:0 -28px;padding:0 28px;}

  /* loader */
  #ea-overlay{position:fixed;inset:0;z-index:99998;display:none;align-items:center;justify-content:center;flex-direction:column;background:rgba(255,255,255,.86);backdrop-filter:blur(2px);}
  #ea-overlay.on{display:flex;animation:eaFade .2s ease;}
  #ea-overlay .ring{width:58px;height:58px;border-radius:50%;border:5px solid #e9eef4;border-top-color:#e8742a;border-right-color:#1b6ca8;animation:eaSpin .85s linear infinite;margin-bottom:14px;}
  #ea-overlay .t{font-size:17px;font-weight:700;color:#14304a;} #ea-overlay .s{color:#6b7785;margin-top:3px;}
  #ea-bar{position:fixed;top:0;left:0;height:4px;width:0;z-index:99999;opacity:0;background:linear-gradient(90deg,#e8742a,#f5a35c 60%,#1b6ca8);box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;} #ea-bar.on{opacity:1;}
  @media(max-width:640px){#add_article > .container,#add_article > .content_right{padding:16px 14px 18px !important;} .ea-req .track{display:none;}}
  @keyframes eaRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}} @keyframes eaPop{from{opacity:0;transform:scale(.8);}to{opacity:1;transform:none;}}
  @keyframes eaFade{from{opacity:0;}to{opacity:1;}} @keyframes eaSpin{to{transform:rotate(360deg);}} @keyframes eaShake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(5px);}}
  /* stage -> workflow -> department hints, review dialog, refresh */
  .ea-refresh{flex:0 0 auto;width:36px;height:36px;border-radius:50%;border:1px solid #d9e2ec;background:#fff;color:#14304a !important;display:inline-flex;align-items:center;justify-content:center;text-decoration:none !important;cursor:pointer;transition:all .2s;}
  .ea-refresh:hover{background:#14304a;border-color:#14304a;color:#fff !important;box-shadow:0 4px 10px rgba(20,48,74,.3);} .ea-refresh:hover i{transform:rotate(180deg);} .ea-refresh i{transition:transform .4s ease;}
  .ea-hint{margin-top:6px;padding:6px 10px;border-radius:8px;font-size:12px;line-height:1.4;color:#177a49;background:#eaf7f0;border:1px solid #c8e8d6;animation:eaRise .3s ease;} .ea-hint.warn{color:#8a6d1a;background:#fff8e6;border-color:#f1dfa6;} .ea-hint i{margin-right:5px;}
  .ea-flash{animation:eaGlow 1.3s ease !important;} @keyframes eaGlow{0%{box-shadow:0 0 0 0 rgba(232,116,42,.6);border-color:#e8742a;}100%{box-shadow:0 0 0 12px rgba(232,116,42,0);}}
  #ea-dlg{position:fixed;inset:0;z-index:99990;display:none;align-items:center;justify-content:center;background:rgba(15,32,52,.5);backdrop-filter:blur(3px);padding:16px;}
  #ea-dlg.on{display:flex;animation:eaFade .2s ease;}
  #ea-dlg .box{width:min(560px,100%);max-height:90vh;display:flex;flex-direction:column;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 28px 70px rgba(15,35,60,.38);animation:eaPopBox .45s cubic-bezier(.2,1.2,.3,1) backwards;}
  #ea-dlg .box:before{content:"";display:block;height:5px;flex:0 0 auto;background:linear-gradient(90deg,#1b6ca8,#7fb3de);} #ea-dlg.info .box:before{background:linear-gradient(90deg,#e8932a,#f5c98a);}
  #ea-dlg .in{padding:22px 26px 20px;text-align:center;overflow:auto;}
  #ea-dlg .ic{width:56px;height:56px;margin:0 auto 10px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e6f1fa;color:#1b6ca8;font:700 28px/1 'Open Sans',Arial,sans-serif;animation:eaPop .5s cubic-bezier(.2,1.4,.3,1) .1s backwards;} #ea-dlg.info .ic{background:#fff3e0;color:#e8932a;}
  #ea-dlg h3{margin:0 0 4px;font-size:20px;font-weight:700;color:#1c3550;} #ea-dlg p{margin:0;color:#51606f;font-size:13.5px;line-height:1.5;}
  #ea-dlg ul{list-style:none;margin:14px 0 0;padding:0;text-align:left;max-height:46vh;overflow:auto;}
  #ea-dlg li{margin:0 0 7px;padding:9px 12px 9px 14px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid #1b6ca8;border-radius:10px;animation:eaRise .35s ease backwards;}
  #ea-dlg li b{display:block;font-size:11.5px;letter-spacing:.4px;text-transform:uppercase;color:#6b7785;margin-bottom:3px;}
  #ea-dlg li .cmp{display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13.5px;} #ea-dlg li .old{color:#9a6a6a;text-decoration:line-through;text-decoration-color:rgba(192,57,43,.5);word-break:break-word;} #ea-dlg li .new{color:#177a49;font-weight:700;word-break:break-word;} #ea-dlg li i{color:#9aa8b8;}
  #ea-dlg .btns{display:flex;justify-content:center;gap:10px;margin-top:18px;}
  .ea-dbtn{min-width:130px;height:44px;padding:0 24px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;border:1px solid #cfd8e3;background:#fff;color:#33475b;transition:all .15s;} .ea-dbtn:hover{background:#f1f5f9;transform:translateY(-1px);}
  .ea-dbtn.primary{border:0;color:#fff;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 5px 14px rgba(27,108,168,.3);} .ea-dbtn.primary:hover{filter:brightness(1.1);}
  @keyframes eaPopBox{from{opacity:0;transform:translateY(18px) scale(.93);}to{opacity:1;transform:none;}}
</style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <!-- form section  -->
      <div class="container-fluid mt-4">

<div div class="row pt-4 d-flex align-items-center flex-wrap">
    <div class="col-8 col-lg-10">
      <p class="articleheading ms-4">Edit Article Information</p>
    </div>

    <div class="col-4 col-lg-2">
      <div class="row d-flex justify-content-end pe-2">
        <div class="col-3 articlebtns ">
          <a href="javascript:history.back();">
            <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
  </div> 
</div>
      
<?php 
$update_articles = $auth->selectupdateArticle($_REQUEST['aid']);

if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT r_id FROM inw_revisions_dtl WHERE inw_id = ".$_REQUEST['aid']." ");
	if($result->num_rows > 0)
	{
		$revision_count=$result->num_rows;
		/// Get the revision details
		$rev_result = $db->query("SELECT * FROM `inw_revisions_dtl` WHERE `inw_id` = ".$_REQUEST['aid']." ORDER BY `inw_revisions_dtl`.`r_id` DESC limit 1;");
		$rev_info = $rev_result->fetch_assoc();		
		 
	}
	else
	{
		$revision_count=$result->num_rows + 1;
	}
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	 $toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$update_articles[0] = $result->fetch_assoc();
		
		if($update_articles[0]['stage'] != 'FP')
		{
			$update_articles[0]['recv_dt'] = $rev_info['received_date'];
			$update_articles[0]['due_dt'] = $rev_info['due_date'];
		}
		
		
	

}
}

?>

<?php
/* Edit Article header box: article summary + live "required fields" counter (design only) */
$ea_a = $update_articles[0];
$ea_cn = ''; $ea_jn = '';
$ea_r = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = ".(int)$ea_a['cust_id']); if ($ea_r && ($ea_x = $ea_r->fetch_assoc())) { $ea_cn = $ea_x['cust_name']; }
$ea_r = $db->query("SELECT j_code FROM adm_journals WHERE j_id = ".(int)$ea_a['j_id']);            if ($ea_r && ($ea_x = $ea_r->fetch_assoc())) { $ea_jn = $ea_x['j_code']; }
$ea_d = function ($d) { return ($d && $d !== '0000-00-00' && $d !== '0000-00-00 00:00:00' && strtotime($d)) ? date('d-m-Y', strtotime($d)) : '-'; };
$ea_due = ($ea_a['stage'] === 'FP' && !empty($ea_a['target_due_dt']) && $ea_a['target_due_dt'] !== '0000-00-00') ? $ea_a['target_due_dt'] : $ea_a['due_dt'];
$ea_h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
      <div class="ea-hero">
        <div class="ea-bar">
          <a class="ea-back" href="javascript:history.back();" title="Back"><i class="fa fa-arrow-left"></i></a>
          <div><h1 class="ea-title">Edit Article Information</h1><div class="ea-crumb">Article &rsaquo; Edit</div></div>
          <span class="ea-sp"></span>
          <a class="ea-refresh" id="ea-refresh" href="#" title="Refresh - reload the saved values"><i class="fa fa-refresh"></i></a>
          <span class="ea-req" id="ea-req" title="Required fields filled in"><span id="ea-req-t">Required 0 / 0</span><span class="track"><span class="fill" id="ea-req-f"></span></span></span>
        </div>
        <div class="ea-sum">
          <div><small>Article ID</small><div class="ea-sum-id">#<?php echo $ea_h($ea_a['pub_id']); ?></div><div class="ea-sum-sub"><?php echo $ea_h($ea_cn); ?> &middot; <?php echo $ea_h($ea_jn); ?></div></div>
          <div class="ea-chips">
            <div class="ea-chip"><small>Stage</small><b><?php echo $ea_h($ea_a['stage']); ?></b></div>
            <div class="ea-chip"><small>Department</small><b><?php echo $ea_h($ea_a['status']); ?></b></div>
            <div class="ea-chip"><small>Received</small><b><?php echo $ea_h($ea_d($ea_a['recv_dt'])); ?></b></div>
            <div class="ea-chip hot"><small>Due date</small><b><?php echo $ea_h($ea_d($ea_due)); ?></b></div>
            <div class="ea-chip"><small>Pages</small><b><?php echo (int)$ea_a['page_count']; ?></b></div>
            <?php if (!empty($ea_a['highpriority']) && (int)$ea_a['highpriority'] === 1) { ?><div class="ea-chip red"><small>Priority</small><b>High</b></div><?php } ?>
          </div>
        </div>
      </div>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Article Basic Information</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Customer <span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>
                    <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if($update_articles[0]['cust_id']!="")
													{
										    	?>
										       <option <?php if ($res_customers['id']== $update_articles[0]['cust_id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Journal <span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
                                                if($update_articles[0]['j_id']!="")
													{
										    	?>
										       <option <?php if ($res_journals['j_id']== $update_articles[0]['j_id']){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>
				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Group Folder </label>
                  <?php
		$query = "SELECT folder_name FROM `inw_journal_folders` WHERE cust_id=".$update_articles[0]['cust_id']." AND j_id=".$update_articles[0]['j_id']." AND is_group_folder = 1 ORDER BY `jf_id` ASC ";
		$folder_rec = $db->query($query) or ($db->error);		
		?>
                                
                                <select id="group_folder_name" name="group_folder_name" class="form-select" data-placeholder="Choose">
                                  <option value="">-Select-</option> 
                                   <?php while($row = $folder_rec->fetch_assoc()){ ?>
                                  <option value="/<?php echo $row['folder_name']; ?>" <?php if($pinfo['issueorgroup_folder'] == "/".$row['folder_name']){?> selected <?php } ?>><?php echo $row['folder_name']; ?></option>
                                  <?php } ?>
                                 
                                </select>
                </div>
                <!--<input type="hidden" name="group_folder_name" id="group_folder_name" value="<?php echo $update_articles[0]['issueorgroup_folder']; ?>" />-->
<div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Article Type</label>
				     <?php
		$query = "SELECT * from inw_article_type ORDER BY `at_title` ASC ";
		$article_type = $db->query($query) or ($db->error);		
		
		?>
                  <select class="form-select journal_drop" data-placeholder="Select Article Type" name="at_id" id="at_id">
                  <option value="">Select Article Type</option>
   <?php while($row_at = $article_type->fetch_assoc()){ ?>
                                  <option value="<?php echo $row_at['at_id']; ?>" <?php if ($row_at['at_id']== $update_articles[0]['at_id']){?>selected<?php }?>><?php echo $row_at['at_title']; ?></option>
                                  <?php } ?>
                    
                </select>
                </div>
          
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Article ID</label>
                 <input type="text" class="form-control" name="pub_id" id="pub_id" placeholder="Enter Article ID" value="<?php echo $update_articles[0]['pub_id']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Article Title <span style="color: red">*</span></label>
              <input type="text" class="form-control" name="article_title" id="article_title"  value="<?php echo $update_articles[0]['article_title']; ?>" placeholder="Enter Article Title" required>
              </input>
                </div>
      <div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Received Date</label>
    <div class="d-flex">
        <input type="text" 
               class="form-control monthlyinputdate" 
               name="recv_dt" 
               id="recv_dt" 
               required 
               placeholder="Select Received Date" 
               value="<?php echo !empty($update_articles[0]['recv_dt']) ? date('d-m-Y', strtotime($update_articles[0]['recv_dt'])) : ''; ?>" 
               readonly />
    </div>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Due Date</label>
    <div class="d-flex" id="journal_duration">
        <input type="text" 
               class="form-control monthlyinputdate" 
               name="due_dt" 
               id="due_dt" 
               required 
               placeholder="Select Due Date" 
               value="<?php echo !empty($update_articles[0]['due_dt']) ? date('d-m-Y', strtotime($update_articles[0]['due_dt'])) : ''; ?>" 
               readonly />
    </div>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Target Due Date (CE) <span style="color: red">*</span></label>
    <div class="d-flex">
        <input type="text" 
               class="form-control monthlyinputdate" 
               name="target_due_dt" 
               id="target_due_dt" 
               placeholder="Select Target Due Date" 
               value="<?php echo !empty($update_articles[0]['target_due_dt']) ? date('d-m-Y', strtotime($update_articles[0]['target_due_dt'])) : ''; ?>" 
               required />
    </div>
</div>

					<div class="col-12 col-sm-6 col-lg-4 d-flex align-items-end">
						<div class="col-6 col-lg-6 ">
							<input type="checkbox" name="test_article" id="test_article" <?php if ($update_articles[0]['test_article']==1){?>checked<?php }?> value="1"><label class="ms-2">Is Test Article</label>
						</div>
						 <div class="col-6 col-lg-6 ">
							<input type="checkbox" name="highpriority" id="highpriority" <?php if ($update_articles[0]['highpriority']==1){?>checked<?php }?> value="1"><label class="ms-2">Is High Priority</label>
						</div>
						 <div class="col-6 col-lg-6 ">
							<input type="checkbox" name="is_conversion" id="is_conversion" <?php if ($update_articles[0]['is_conversion']=='yes'){?>checked<?php }?> value="yes"><label class="ms-2">Is Conversion Article</label>
						</div>
						<div class="col-12 col-lg-12">
							<input type="checkbox" name="late_dispatch" id="late_dispatch" <?php if ($update_articles[0]['late_dispatch'] == 'yes'){ ?> checked <?php } ?> value="yes"><label class="ms-2">Client Approved Late Dispatch</label>
						</div>
					</div>
                </div>
            </div>
            <!-- heading 2 -->
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Article Flow</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Manuscript Count </label>
                  <input type="number" class="form-control" name="manuscript_count" id="manuscript_count" required  placeholder="Enter Manuscript Count" value="<?php echo $update_articles[0]['manuscript_count']; ?>">
              </input>
                </div>
				
				<div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Proof Page Count:</label>
                                <input type="text" id="page_count" name="page_count" class="form-control" value="<?php if($update_articles[0]['page_count']!="") { echo $update_articles[0]['page_count'];}else{ echo "0";} ?>" />
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Fig Count </label>
                  <input type="number" class="form-control" name="fig" id="fig" placeholder="Enter Fig Count" value="<?php echo $update_articles[0]['fig']; ?>" required>

              </input>
                </div>
				
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Anxillary fig Count:</label>
                            
                              
                                <input type="text" id="anxillary_fig" name="anxillary_fig" class="form-control" value="<?php if($update_articles[0]['anxillary_fig']!="") { echo $update_articles[0]['anxillary_fig'];}else{ echo "0";} ?>" />
                             
                          </div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Disk Fig Count:</label>
                            
                              
                                <input type="text" id="img_disk_count" name="img_disk_count" class="form-control" value="<?php if($update_articles[0]['img_disk_count']!="") { echo $update_articles[0]['img_disk_count'];}else{ echo "0";} ?>" />
                             
                          </div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Redraw Fig Count:</label>
                            
                              
                                <input type="text" id="img_redraw_count" name="img_redraw_count" class="form-control" value="<?php if($update_articles[0]['img_redraw_count']!="") { echo $update_articles[0]['img_redraw_count'];}else{ echo "0";} ?>" />
                            
                          </div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Relabel Fig Count:</label>
                            
                              
                                <input type="text" id="img_relabel_count" name="img_relabel_count" class="form-control" value="<?php if($update_articles[0]['img_relabel_count']!="") { echo $update_articles[0]['img_relabel_count'];}else{ echo "0";} ?>" />
                             
                          </div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2">Color Figures Count:</label>
                            
                              
                                <input type="text" id="img_color_figures" name="img_color_figures" class="form-control" value="<?php if($update_articles[0]['fig']!="") { echo $update_articles[0]['fig'];}else{ echo "0";} ?>" />
                            
                          </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Table Count </label>
                  <input type="number" class="form-control" name="tab" id="tab" placeholder="Enter Table Count" value="<?php echo $update_articles[0]['tab']; ?>" required>

                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Stage <span style="color: red">*</span></label>
                  <?php if($update_articles[0]['stage'] == 'FP') { ?>
                              <select class="form-select" aria-label="search-input" id="stage" name="stage" required>
                              <option value="">-Select-</option>
                              <?php foreach($project_status_types as $id=>$val){?>
                               <option value="<?php echo $id; ?>"<?php if ($id==$update_articles[0]['stage']){?>selected<?php }?>><?php echo $val; ?></option>
                              <?php } ?>                              
                              </select>
                              <?php } else { ?>
                              <input type="hidden" name="stage" value="<?php echo $update_articles[0]['stage'] ?>"><?php echo $update_articles[0]['stage']; ?>
                              <?php } ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Workflow <span style="color: red">*</span></label>
              <select class="form-select" aria-label="search-input" id="wf_id" name="wf_id" required>
              <?php 
              $showjournal= $auth->showjournals($update_articles[0]['j_id']);
              $j_wp_fp=$showjournal[0]['j_fp_wf'];
              $j_rev_wf=$showjournal[0]['j_rev_wf'];
              $j_fin_wf=$showjournal[0]['j_fin_wf'];
              $j_jstage_wf=isset($showjournal[0]['j_jstage_wf']) ? $showjournal[0]['j_jstage_wf'] : '';
             // $showArticleworkflow= $auth->getJournalWorkflowWithCustomerId($update_articles[0]['cust_id']);
              $showArticleworkflow= $auth->getJournalWorkflowWithCustomerId($update_articles[0]['cust_id']);
            //  $showArticleworkflow= $auth->getArticleWorkflow($update_articles[0]['j_id']);

              foreach($showArticleworkflow as $res_wf)
              {
                  
                      
                  ?>
                 <option <?php if ($res_wf['wf_id']== $update_articles[0]['wf_id']){?>selected<?php }?> value="<?php echo $res_wf['wf_id'] ?>" style="font-weight:500;"><?php echo $res_wf['wf_name'] ?> </option>
							
                      <?php
                      
              }
                 ?>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Input Type - <?php echo $update_articles[0]['inw_type'];?> <span style="color: red">*</span></label>
              <select class="form-select" aria-label="search-input" id="multiple-select-field" name="inw_type" id="inw_type" required>
              <?php
											$sql_inwardtype = $auth->getAllinwardtype();
											foreach($sql_inwardtype as $res_inwardtype)
											{
												
                                                if($update_articles[0]['inw_type']!="")
                                                {
                                            ?>
                                           <option <?php if ($res_inwardtype['type_name']== $update_articles[0]['inw_type']){?>selected<?php }?> value="<?php echo $res_inwardtype['type_name'] ?>" style="font-weight:500;"><?php echo $res_inwardtype['type_name'] ?> </option>

													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_inwardtype['type_name'] ?>"style="font-weight:500;"><?php echo $res_inwardtype['type_name'] ?> </option>
													<?php
													}
											}
											?>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Complexity <span style="color: red">*</span></label>
               <select class="form-select" aria-label="search-input" id="multiple-select-field" name="complexity" id="complexity" required>
               <?php foreach($complexity_types as $val){?>
                                  <option value="<?php echo $val; ?>" <?php if($val==$update_articles[0]['complexity']){?> selected <?php }?>><?php echo $val; ?></option>
               <?php } ?>
                  </select>
                </div>
               
                </div>
            </div>
            <!-- heading 3 -->
            <div class="container mt-4">
              <div class="formcont1heading">Author Details</div>
              <div class="row mt-2 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Corresponding Author </label>
                  <input type="text" class="form-control" name="corresponding_author" id="corresponding_author" value="<?php echo $update_articles[0]['corresponding_author']; ?>" placeholder="Enter Corresponding Author">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Corresponding Mail </label>
                  <input type="text" class="form-control" name="corresponding_mail" id="corresponding_mail" value="<?php echo $update_articles[0]['corresponding_mail']; ?>" placeholder="Enter Corresponding Author Mail">
                </div>
				
				  <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Co-Author Mails </label>
                  <input type="text" class="form-control" name="corresponding_other_author" id="corresponding_other_author" value="<?php echo $update_articles[0]['corresponding_other_author']; ?>" placeholder="Enter Co-Author Mails">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">E Location ID</label>
                  <input type="text" class="form-control" name="location_id" id="location_id" value="<?php echo $update_articles[0]['location_id']; ?>" placeholder="Enter E Location ID">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">DOI</label>
                 <input type="text" class="form-control" name="doi" id="doi" placeholder="Enter DOI" value="<?php echo $update_articles[0]['doi']; ?>">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">PUB Date</label>
                <div class="d-flex">
                <input type="text" class="form-control monthlyinputdate" name="pub_dt" id="pub_dt" placeholder="Select PUB Date" value="0000-00-00">
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Accepted Date <span style="color: red">*</span></label>
              <div class="d-flex">
              <input type="text" class="form-control monthlyinputdate" name="accpt_dt" id="accpt_dt" placeholder="Select Accepted Date" value="<?php echo date('d-m-Y',strtotime($update_articles[0]['accpt_dt'])); ?>" required>
                </div>
                </div>
				
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Instructions </label>
              <textarea type="text" class="form-control" name="instructions" id="instructions"><?php echo $update_articles[0]['instructions']; ?></textarea>

                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Notes</label>
              <textarea type="text" class="form-control" name="notes" id="notes"><?php echo $update_articles[0]['notes']; ?></textarea>

                </div>
				
				  <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Department to Assign in the same stage <span style="color: red">*</span></label>              
              <?php
	//// Get the worflow Steps ////
	 $query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$update_articles[0]['wf_id']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();	
	
	$query = "SELECT * FROM `adm_dept_master` WHERE `dept_code` IN (".$deptinfo['wf_code'].",9) ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                              <select id="dept_id" name="dept_id" class="form-select" aria-label="search-input" data-placeholder="Choose">
                                <option value="">-Select-</option>                                
                                <?php while($row = $customers->fetch_assoc()){ ?>
                                <option value="<?php echo $row['dept_code']; ?>"><?php echo $row['dept_name']; ?></option>
                                <?php } ?>
								
								</select>
                </div>
				
				  <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Article Status <span style="color: red">*</span></label>
              <select id="at_status" name="at_status" class="form-select" aria-label="search-input" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <option value="Query" <?php if($app_res['at_status'] =='Query'){?> selected <?php } ?>>Query</option>
                                  <option value="Query Reply" <?php if($app_res['at_status'] =='Query Reply'){?> selected <?php } ?>>Query Reply</option>
                                  <option value="Hold" <?php if($app_res['at_status'] =='Hold'){?> selected <?php } ?>>Hold</option>
                                  <option value="Remarks" <?php if($app_res['at_status'] =='Remarks'){?> selected <?php } ?>>Remarks</option>
                                  <option value="Client Review" <?php if($update_articles[0]['status'] =='Client Review'){?> selected <?php } ?>>Client Review</option>
                                </select>
                </div>
				
				
                </div>
				
				 <div class="col-12 col-sm-12 col-lg-12" id="query_div" style="display:none;">
              <label class="mb-2">Query </label>
              <textarea name="message_query" class="form-control" id="message" class="required"></textarea>

                </div>
				
					 <div class="col-12 col-sm-4 col-lg-4" id="duedt_div" style="display:none;">
              <label class="mb-2">Due Date After Query Reply  </label>
              <input type="text" name="due_dt_qr" class="form-control monthlyinputdate" id="due_dt_qr" ></textarea>

                </div>
				
            </div>
            <input type="hidden" name="aid" id="aid" value="<?php echo $_REQUEST['aid'];?>">
            <input type="hidden" name="id" id="id" value="<?php echo $_REQUEST['aid'];?>">
			<input type="hidden" name="rev_id" value="<?php echo $rev_info['id']; ?>" />							
            <input type="hidden" name="action" value="update">
             <input type="hidden" name="old_group_folder_name" id="old_group_folder_name" value="<?php echo $update_articles[0]['issueorgroup_folder']; ?>" />
             <input type="hidden" name="old_department_id" value="<?php echo $update_articles[0]['department_id']; ?>">
			 
			 
			    <!-- upload section -->
    <div class="content_right">
          <div class="row d-flex justify-content-center">
            <div class="col-10 col-md-8 dropboxdiv">
              <div
                class="row justify-content-center align-items-center d-flex flex-column"
                style="height: 100%"
              >
                <div class="col-10">
                  <div id="dropzone" class="col-12 dropboxinner">
                    <div id="dropzonewidget" class="dropzone"></div>
     
                   </div>
                 
                </div>
              </div>
            </div>
         
            <div class="col-3 col-md-3 col-lg-3 d-none d-md-block uploadinfodiv">
              <div class="row g-4">
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon.svg" />
                    <p class="fileinfopara ms-3">Total Files</p>
                  </div>
                  <div class="fileno">
                    <p id="file_count">0</p>
                  </div>
                </div>
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon1.svg" />
                    <p class="fileinfopara ms-3">Total Size</p>
                  </div>
                  <div class="fileno">
                    <p style="color: #ff6d02" id="totalFileSize">0 KB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
	  
	  
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $_REQUEST['aid'];?>" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
                    </div>
                </div>
            </div>
      </form>
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
<div id="ea-bar"></div>
<div id="ea-overlay"><div class="ring"></div><div class="t">Updating article&hellip;</div><div class="s">Please wait, do not close this tab</div></div>
<div id="ea-dlg"><div class="box"><div class="in"><div class="ic" id="ea-dic">?</div><h3 id="ea-dt"></h3><p id="ea-dsub"></p><ul id="ea-dlist"></ul><div class="btns" id="ea-db"></div></div></div></div>
<?php
/* data for the stage -> workflow -> department cascade (design layer: nothing is posted differently) */
$ea_dn = array();
$ea_q = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
while ($ea_q && ($ea_x = $ea_q->fetch_assoc())) { $ea_dn[(int)$ea_x['dept_code']] = $ea_x['dept_name']; }
$ea_wf = array();
if (!empty($showArticleworkflow) && is_array($showArticleworkflow)) {
	foreach ($showArticleworkflow as $ea_w) {
		$ea_codes = array();
		foreach (explode(',', (string)$ea_w['wf_code']) as $ea_c) { $ea_c = (int)trim($ea_c); if ($ea_c) { $ea_codes[] = $ea_c; } }
		$ea_all = $ea_codes; $ea_all[] = 9; $ea_all = array_unique($ea_all);          /* same list the department drop-down is built from: the workflow's departments + Graphics */
		$ea_list = array();
		foreach ($ea_all as $ea_c) { if (isset($ea_dn[$ea_c])) { $ea_list[] = array('code' => $ea_c, 'name' => $ea_dn[$ea_c]); } }
		usort($ea_list, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
		$ea_wf[(string)$ea_w['wf_id']] = array('name' => $ea_w['wf_name'], 'first' => $ea_codes ? $ea_codes[0] : 0, 'depts' => $ea_list);
	}
}
$ea_stage_wf = array('FP' => (string)$j_wp_fp, 'REV' => (string)$j_rev_wf, 'FIN' => (string)$j_fin_wf, 'JSTAGE' => (string)$j_jstage_wf);
?>
<script>
/* Edit Article: design-layer behaviour only (the page's own validation and submit are untouched) */
(function ($) {
  var WF = <?php echo json_encode($ea_wf); ?>, STAGE_WF = <?php echo json_encode($ea_stage_wf); ?>, PUB = <?php echo json_encode($ea_a['pub_id']); ?>;
  $(function () {
    var $f = $('#add_article'), $bar = $('#ea-bar'), timer = null, val = 0, confirmed = false, snap = null;
    if (!$f.length) { return; }

    /* Cancel was a submit button wrapped around a link: make it a plain button that just goes to the link */
    $f.find('.btn-next-cancel').attr('type', 'button').on('click', function (e) {
      var a = $(this).find('a').attr('href'); if (a && !$(e.target).is('a')) { location.href = a; }
    });
    $f.find('input[type=checkbox]').each(function () { var $l = $(this).next('label'); if ($l.length && !$l.attr('for') && this.id) { $l.attr('for', this.id); } });
    $f.find('.content_right').prepend('<div class="formcont1heading">Upload Files</div>');

    /* ---------- required counter ---------- */
    function req() {
      var $r = $f.find('[required]').not('[type=hidden]').not(':disabled'), done = 0, tot = $r.length;
      $r.each(function () { if ($.trim($(this).val()) !== '') { done++; } });
      $('#ea-req-t').text('Required ' + done + ' / ' + tot); $('#ea-req-f').css('width', (tot ? Math.round(done * 100 / tot) : 100) + '%');
      $('#ea-req').toggleClass('done', done === tot);
    }
    $f.on('input change keyup', req); setTimeout(req, 300); setTimeout(req, 1200);

    /* ---------- stage -> workflow -> department ---------- */
    var $wfSel = $('#wf_id'), $deptSel = $('#dept_id'), $stage = $('#stage'), initStage = $stage.val(), initWf = $wfSel.val();   /* the saved values, to come back to */
    function hint($after, id) { var $h = $('#' + id); if (!$h.length) { $h = $('<div class="ea-hint" id="' + id + '"></div>').insertAfter($after); } return $h; }
    function flash($el) { $el.removeClass('ea-flash'); void $el[0].offsetWidth; $el.addClass('ea-flash'); setTimeout(function () { $el.removeClass('ea-flash'); }, 1400); }
    function rebuildDept(wfId, byUser) {
      var w = WF[wfId]; if (!w || !$deptSel.length) { return; }
      var keep = $deptSel.val(), html = '<option value="">-Select-</option>';
      $.each(w.depts, function (i, d) { html += '<option value="' + d.code + '">' + $('<div>').text(d.name).html() + '</option>'; });
      $deptSel.html(html);
      if (byUser && w.first) {
        $deptSel.val(String(w.first)); flash($deptSel);
        var nm = ''; $.each(w.depts, function (i, d) { if (d.code === w.first) { nm = d.name; } });
        hint($deptSel, 'ea-dept-hint').html('<i class="fa fa-magic"></i> Set to <b></b> &mdash; the first step of <b></b>. Change it if the article should go elsewhere.').find('b').eq(0).text(nm).end().eq(1).text(w.name);
      } else if (keep && $deptSel.find('option[value="' + keep + '"]').length) { $deptSel.val(keep); }
      $deptSel.trigger('change'); req();
    }
    function setWorkflow(id) {
      $wfSel.val(String(id)); flash($wfSel); $wfSel.trigger('change');
    }
    $wfSel.on('change.ea', function () { rebuildDept($(this).val(), true); });
    $stage.on('change.ea', function () {
      var st = $(this).val(); if (!st) { return; }
      if (st === initStage && initWf) {   /* back to the saved stage: the saved workflow, no department change */
        $wfSel.val(String(initWf)); flash($wfSel); rebuildDept(initWf, false); $deptSel.val(''); $('#ea-dept-hint').remove();
        hint($wfSel, 'ea-wf-hint').removeClass('warn').html('<i class="fa fa-undo"></i> Back to the saved stage &mdash; the saved workflow is restored.'); return;
      }
      var name = STAGE_WF[st], id = null;
      if (name) { $.each(WF, function (k, w) { if (w.name === name) { id = k; return false; } }); }
      var $h = hint($wfSel, 'ea-wf-hint');
      if (id) { setWorkflow(id); $h.removeClass('warn').html('<i class="fa fa-magic"></i> Workflow set to <b></b> for the <b></b> stage.').find('b').eq(0).text(name).end().eq(1).text($stage.find('option:selected').text()); }
      else { $h.addClass('warn').html('<i class="fa fa-info-circle"></i> This journal has no workflow set for this stage &mdash; please choose the workflow yourself.'); flash($wfSel); }
    });

    /* ---------- what changed ---------- */
    function norm(s) { return $.trim(String(s == null ? '' : s).replace(/\s+/g, ' ')); }
    function labelOf($el) {
      var t = $el.is(':checkbox') ? $el.next('label').text() : $el.closest('[class*="col-"]').find('label').not('.error').first().text();
      t = norm(t.replace(/\*/g, '').replace(/:\s*$/, '')).split(' - ')[0];
      return t || $el.attr('name');
    }
    function valueOf($el) { return $el.is(':checkbox') ? ($el.is(':checked') ? 'Yes' : 'No') : ($el.is('select') ? norm($el.find('option:selected').text()) : norm($el.val())); }
    function fields() { return $f.find('input,select,textarea').filter(function () { return this.name && this.type !== 'hidden' && this.type !== 'file' && this.type !== 'submit' && this.type !== 'button'; }); }
    function take() { var o = {}, seen = {}; fields().each(function () { var n = this.name, i = seen[n] = (seen[n] || 0) + 1, k = n + '#' + i; o[k] = { label: labelOf($(this)), val: valueOf($(this)) }; }); return o; }
    setTimeout(function () { snap = take(); }, 1500);
    function changes() {
      var now = take(), out = [];
      if (!snap) { snap = now; }
      $.each(now, function (k, c) { var o = snap[k]; if (!o) { if (c.val !== '') { out.push({ label: c.label, from: '', to: c.val }); } } else if (o.val !== c.val) { out.push({ label: c.label, from: o.val, to: c.val }); } });
      try { var dz = Dropzone.forElement('#dropzonewidget'); if (dz && dz.files && dz.files.length) { out.push({ label: 'Files to upload', from: '', to: dz.files.length + ' file' + (dz.files.length > 1 ? 's' : '') }); } } catch (er) {}
      return out;
    }

    /* ---------- dialog ---------- */
    function short(s) { s = String(s); return s.length > 90 ? s.slice(0, 90) + '…' : s; }
    function dlg(o) {
      $('#ea-dlg').toggleClass('info', !!o.info).addClass('on'); $('#ea-dic').text(o.info ? 'i' : '?'); $('#ea-dt').text(o.title); $('#ea-dsub').text(o.sub || '');
      var $l = $('#ea-dlist').empty();
      $.each(o.list || [], function (i, c) {
        $l.append($('<li></li>').css('animation-delay', (i * 0.05) + 's').append($('<b></b>').text(c.label))
          .append($('<span class="cmp"></span>').append($('<span class="old"></span>').text(c.from === '' ? '(empty)' : short(c.from))).append('<i class="fa fa-long-arrow-right"></i>').append($('<span class="new"></span>').text(c.to === '' ? '(empty)' : short(c.to)))));
      });
      var $b = $('#ea-db').empty();
      $.each(o.buttons, function (i, b) { $('<button type="button" class="ea-dbtn' + (b.primary ? ' primary' : '') + '"></button>').text(b.text).on('click', function () { $('#ea-dlg').removeClass('on'); if (b.cb) { b.cb(); } }).appendTo($b); });
    }

    /* ---------- refresh ---------- */
    $('#ea-refresh').on('click', function (e) {
      e.preventDefault(); var $i = $(this).find('i'), go = function () { $i.addClass('fa-spin'); location.reload(); }, c = changes();
      if (!c.length) { go(); return; }
      dlg({ title: 'Discard your changes?', sub: 'Refreshing reloads the saved values. These edits will be lost:', list: c, buttons: [{ text: 'Keep editing' }, { text: 'Discard & refresh', primary: true, cb: go }] });
    });

    /* ---------- submit: validate -> show what changed -> confirm ---------- */
    $f.on('invalid-form.validate', function (e, v) {
      var el = v && v.errorList && v.errorList[0] && v.errorList[0].element; if (!el) { return; }
      $('html,body').animate({ scrollTop: Math.max(0, $(el).offset().top - 150) }, 300);
    });
    $f.on('submit', function (e) {
      var ok = true; try { ok = $f.valid(); } catch (er) { ok = true; }
      if (!ok) { return; }
      if (!confirmed) {
        e.preventDefault(); e.stopImmediatePropagation();
        var c = changes();
        if (!c.length) { dlg({ info: true, title: 'Nothing has changed', sub: 'There is nothing to update for ' + PUB + ' yet.', buttons: [{ text: 'OK', primary: true }] }); return false; }
        dlg({ title: 'Review your changes', sub: 'You are about to update ' + PUB + '. ' + c.length + ' change' + (c.length > 1 ? 's' : '') + ':', list: c,
              buttons: [{ text: 'Cancel' }, { text: 'Confirm & update', primary: true, cb: function () { confirmed = true; $f.find('.btn-next')[0].click(); } }] });
        return false;
      }
      $f.find('.btn-next').prop('disabled', true); $('#ea-overlay').addClass('on'); clearInterval(timer); $bar.addClass('on'); val = 6; $bar.css('width', val + '%');
      timer = setInterval(function () { val = val + (92 - val) * 0.05; $bar.css('width', val + '%'); }, 220);
    });
  });
})(jQuery);
</script>

 <!-- Date picker functionality -->
 <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });
      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
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
        function populateSelecte_gf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.folder_name).val(item.folder_name));
            });
        }
        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }


	$('#at_status').on('change',function()
	{
        var status = $(this).val();
       
		 if(status == 'Hold')
		{
			$('#query_div').show();
			$('#duedt_div').hide();
		}
		else if(status == 'Query')
		{
			$('#query_div').show();
			$('#duedt_div').hide();
		}
		else if(status == 'Query Reply')
		{
			$('#query_div').show();
			$('#duedt_div').show();
		}
		else if(status == 'Remarks')
		{
			$('#query_div').show();
			$('#duedt_div').hide();
		}
		else
		{
            $('#query_div').hide();
            
        }
		
    });
    </script>

<script>  
 //	$.mask.definitions['~']='[+-]';

$(document).ready(function() {
    $("#add_article").validate({
        rules: {

          cust_id: "required",
          j_id: "required",
          pub_id: "required",
          artitle_title: "required",
          due_dt: "required",
          recv_dt: "required",
          manuscript_count: "required",
          fig: "required",
          tab: "required",
          wf_id: "required",
          inw_type: "required",
          complexity: "required",
          accpt_dt: "required",
          pub_dt: "required"

           
        },
        messages: {

           cust_id: "Please Select Customer",
          j_id: "Please Select Journal",
          pub_id: "Please enter Pub ID",
          artitle_title: "Please enter Article title",
          due_dt: "Please enter Due date",
          recv_dt: "Please enter Received Date",
          manuscript_count: "Please enter Manuscript Count",
          fig: "Please enter Figure Count",
          tab: "Please enter Table Count",
          wf_id: "Please Select Workflow",
          inw_type: "Please Select Inward Type",
          complexity: "Please Select Complexity",
          accpt_dt: "Please enter Accept Date",
          pub_dt: "Please enter Publication Date"
           
        },

        /* submitHandler: function(form) {
            $.ajax({
                url: "<?php echo $_COOKIE['set_url'];?>/update_article.php",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                   


                    swal({
  title:"Your article is Updated Now",
   type: "success",
		showCancelButton: false,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Ok!',
    confirmButtonText: 'CLOSE',
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Updated!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';

    } else {
      swal("Updated!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';
    }
	});


                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Error message
                    alert("Error submitting form: " + errorThrown);
                    $('.content_right').hide();
                    $('.btn-next').show();
                    $('.btn-save').hide();
                    $(".btn-cancel").hide();
                     $(".btn-worklfow-cancel").hide();

                }
            });
          } */
		  
        });
    
});  

  </script> 

     <script>
	$(document).ready(function(){
    $(".pdf").change(function () {
		var astatus = $("input[type='radio']:checked").val();
		//alert(astatus);
		if(astatus=='yes')
		{
			$(".pdf_show").show();
			$(".btn-save").hide();
		    $("#stage").val('REV2');
		}
		else
		{
		$(".pdf_show").hide();
		$(".btn-save").show();
		$("#stage").val('FIN1');
		}
     });


     $('.doi_number').keyup(function(e) {
     var txtVal = $(this).val();
     var split_doi = txtVal.split('.');
     var volume_no = split_doi[3];
     var issue_no = split_doi[4];
      $('#volume_no').val(volume_no);
      $('#issue_no').val(issue_no);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+txtVal+suffix);
  }); 
  
   $('#volume_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var issue_no = $('#issue_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]+suffix);
  }); 
  
  $('#issue_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var volume_no = $('#volume_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]+suffix);
  }); 

	
  
	// Corresponding Author Inputs//
	
	var counter_c = 2;
	$('.del_cauthor').hide();
	$('#del_cauthor_tag').hide();
	$('.add_cauthor').click(function(){
$('#author_ctools').before('<div class="metaauthorinputs" id="fac'+counter_c+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_cname[]" aria-label="name" id="author_cname'+counter_c+'" value="" /></div><div class="metaauthormail"><label>Email ID <span style="color: red">*</span></label><input type="email" placeholder="Author Email ID" name="author_cemail[] "aria-label="name" class="article_einput" id="author_cemail'+counter_c+'" value="" /></div></div>');
		$('.del_cauthor').fadeIn(0);
		$('.del_cauthor_tag').fadeIn(0);
		$('#total_show_cauthor').text("Count :"+counter_c);
		$('#total_cauthor').val(counter_c);

	counter_c++;
	



	});
	$('.del_cauthor').click(function(){
		if(counter_c== 3){
			$('.del_cauthor').hide();
			$('#del_cauthor_tag').hide();
		}   

		counter_c--;
		$('#fac'+counter_c).remove();
		$('#total_cauthor').val(counter_c-1);
		$('#total_show_cauthor').text("Count :"+(counter_c-1));



	});


// Corresponding Author Inputs//

//  Author Orcid Inputs//

	var counter_a = 1;
	$('.del_author').hide();
	$('#del_author_tag').hide();
	$('.add_author').click(function(){
$('#author_tools').before('<div class="metaauthorinputs" id="fa'+counter_a+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_name[]" aria-label="name" id="author_name'+counter_a+'" value="" /></div><div class="metaauthormail"><label>Orcid ID </label><input type="text" placeholder="Orcid ID" name="orcid_id[] "aria-label="name" id="orcid_id'+counter_a+'" value="" /></div></div>');
		$('.del_author').fadeIn(0);
		$('.del_author_tag').fadeIn(0);
		$('#total_show_author').text("Count :"+counter_a);
		$('#total_author').val(counter_a);
		//$('.corr_id_val').val('no');
		$('#orcid_id'+counter_a).mask('****-****-****-****');

	counter_a++;



	});
	$('.del_author').click(function(){
		if(counter_a==2){
			$('.del_author').hide();
			$('#del_author_tag').hide();
		}   

		counter_a--;
		$('#fa'+counter_a).remove();
		$('#total_author').val(counter_a-1);
		$('#total_show_author').text("Count :"+(counter_a-1));
		$('#orcid_id'+counter_a).mask('****-****-****-****');



	});





});

				$('#message').richText();//this will create the default editor will all buttons			

</script>

<script type='text/javascript'>
           // var hiddenValue1 =$("#jid").html();
           // var hiddenValue2 =$("#pubid").html();
           // var hiddenValue3 =$("#j_id").val();
           // console.log('hiddenValue:'+hiddenValue3);
        Dropzone.options.dropzonewidget = {


             maxFiles: 20, // Max number of files
            // acceptedFiles: ".pdf",
             type: 'POST',
             url: '<?php echo $_COOKIE['set_url'];?>/upload-edit-articles-data-file.php',
             paramName: 'file',
             
             params: {
              j_id: $("#j_id").val(),  // Additional argument 2
              stage: $("#stage").val(),  // Additional argument 2
              pub_id: $("#pub_id").val(),  // Additional argument 2
              group_folder_name: $("#group_folder_name").val(),  // Additional argument 2
              request:1 // Additional argument 4
                // Add more arguments as needed
            },
            init: function () {

              var myDropzone = this;

                 var totalSize = 0;
                var totalSizeElement = document.getElementById('totalFileSize');

                this.on("addedfile", function (file) {

                  totalSize += file.size;
                  updateTotalSize();

                    var removeButton = Dropzone.createElement('<button class="btn-remove" style="background-color:red;border-radius:15px;cursor:pointer;">Remove</button>');
                    var _this = this;

                    // Event listener for the "Remove" button click
                    removeButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        _this.removeFile(file);


                        var name = file.name;    
                
                $.ajax({
                    type: 'POST',
                    url: '<?php echo $_COOKIE['set_url'];?>/upload-edit-articles-data-file.php',
                    data: {name: name,request: 2},
                    sucess: function(data){
                        console.log('success: ' + data);
                    }
                });
                var _ref;
                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
              //  updateTotalSize();

                    });

                    file.previewElement.appendChild(removeButton);

                    updateUploadCount();
                    updateTotalSize();


                });

                this.on("removedfile", function (file) {

                    totalSize -= file.size;
                    updateUploadCount();
                    updateTotalSize();

                });

                this.on("totaluploadprogress", function (progress) {
                    // Use progress.loaded to track the total uploaded size
                });

                this.on("success", function (file, response) {
                    console.log(response);
                });
                
                 this.on("queuecomplete", function () {
                // All files have been processed, display an alert
               // alert("All files have been uploaded.");
                  $(".btn-save").show();
            });

                function updateTotalSize() {
                    totalSizeElement.textContent = formatBytes(totalSize);
                }

                // Function to format bytes into human-readable format
                function formatBytes(bytes) {
                    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    if (bytes === 0) return '0 Byte';
                    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                }

              

// Add an event listener to the remove button
/* 
document.getElementById('removeButton').addEventListener('click', function () {
    if (myDropzone.files.length > 0) {
        // Remove the first file from the Dropzone instance
        myDropzone.removeFile(myDropzone.files[0]);
    }
});
*/

            }
        };

       


        function updateUploadCount() {
            var dropzoneInstance = Dropzone.forElement("#dropzonewidget");
            var uploadedFilesCount = dropzoneInstance.files.length;
            var countElement = document.getElementById("file_count");

            if (uploadedFilesCount === 0) {
                countElement.textContent = "0";
                	$(".btn-save").hide();
            } else if (uploadedFilesCount === 1) {
                countElement.textContent = "1";
                //	$(".btn-save").show();
            } else {
                countElement.textContent = uploadedFilesCount;
                //$(".btn-save").show();

            }
        }

        $('#j_id').change(function() {
	$('#previous').show();
    $('#pub_id').val($('#j_id option:selected').data('id'));
    $('.previous_aid').text($('#j_id option:selected').data('previous'));
    console.log($('#j_id option:selected').data('id'));
    console.log($('#j_id option:selected').data('previous'));
    var jid=$('#j_id').val();
    console.log(jid);
    //$('#j_id').val(jid);
    $('#j_id').attr('value',jid);



	
});

$(".btn-uploadworklfow").on("click", function() {
// Show overlay and loader
     
        
  var count=$("#file_count").text();
//alert(count);

 // alert('ajax initiated');
	if(count>0)
	{
	$("#overlay").show();
    $("#loader").show();
  var customerid = $('#cust_id').val();
  var jid = $('#j_id').val();
  var pubid = $('#pub_id').val();
// Initiate the AJAX request

// Fetch connectedsource based on the selected connectedsource and populate the "name" select element
$.ajax({
    url: "<?php echo $_COOKIE['set_url'];?>/upload_article_to_workflow.php",
    dataType: "json",
    data: { cust_id: customerid, j_id: jid, pub_id: pubid },
    success: function(data) {
    // Hide the loader
       // Hide overlay and loader
       $("#overlay").hide();
       $("#loader").hide();
     // alert('ajax success');
     resetForm();
	},
  complete:function(response){
 


swal({
  title:"Your article is Submitted",
   type: "success",
		showCancelButton: false,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Ok!',
    confirmButtonText: 'CLOSE',
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';

    } else {
      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';
    }
	});

}, 
    error: function() {
                // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");
            }
});
}
else
{
   swal({
   title:"Please Upload Doc or any file to Proceed",
 type: "info",  
confirmButtonColor: '#DD6B55',
confirmButtonText: 'CLOSE',
});

 // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");

}

	});

  function resetForm() 
{
	$("#articlesubmission")[0].reset();

}
        </script>
  </body>
</html>

<?php include "includes/includes.php";?>
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
		
	
	$update_dept_status = '';
	//echo $_POST['is_update_dept'];
	if($_POST['is_update_dept']>0)
	{
	$update_dept_status = ",wf_id = '".$post['wf_id']."',department_id = '".$dept_info[0]."',
	status = '".$dept_res['dept_name']."'";
	}
	
	if($_POST['dept_id'] > 0)
	{
		$update_dept_status = ",wf_id = '".$post['wf_id']."',department_id = '".$_POST['dept_id']."',
	status = '".$dept_res['dept_name']."'";
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
	else
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
	test_article = '".$test_article."',
	img_color_figures = '".$post['img_color_figures']."',
	img_relabel_count = '".$post['img_relabel_count']."',
	img_redraw_count = '".$post['img_redraw_count']."',
	img_disk_count = '".$post['img_disk_count']."',
	anxillary_fig = '".$post['anxillary_fig']."',
	page_count = '".$post['page_count']."',
  complexity = '".$post['complexity']."',
  inw_type = '".$post['inw_type']."',

	corresponding_mail = '".$post['corresponding_mail']."' ".$update_dept_status." ".$issueorgroup_folder." ".$update_due_rev_date." WHERE id = ".$post['id']."  ;";

		$db->query($insert_query) or die($db->error);
		
		//// Insert the instructions ///// 
		
		$insert=mysqli_query($db,"insert into inw_instructions_dtl(`inw_id`,`stage`,`instructions`,`created_by`,`created_dt`) values ('".$post['id']."','".$post['stage']."','".addslashes($post['instructions'])."','".$_SESSION['user_id']."',NOW())");
		
		
		
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
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_SESSION['user_id']."'"));
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
			
			
			
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_SESSION['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','".$post['at_status']."')");	
			
			
		



			$user_working_status = '<span class="label label-sm label bg-secondary text-white">'.$post['at_status'].'</span>';
			
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',`status` ='".$post['at_status']."' WHERE id = ".$post['id']." ");
		}
		else if($post['at_status']=='Remarks')
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `remarks` ='".$post['message_query']."' WHERE id = ".$post['id']." ");
		}
		else if($post['at_status']=='Query Reply')
		{
		 $user_working_status = '<span class="label label-sm label-success">'.$deapart_name['dept_name'].'</span>';


		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',`status` ='".$deapart_name['dept_name']."',`aq_status` ='".$post['at_status']."' WHERE id = ".$post['id']." ");
		
		$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_SESSION['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','".$post['at_status']."')");	

		}
		else
		{
		 $user_working_status = '<span class="label label-sm label-success">'.$deapart_name['dept_name'].'</span>';
		 
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."' WHERE id = ".$post['id']." ");

		}

		if($post['stage']!='FP')
		{
			$prj_update_querys=mysqli_query($db,"UPDATE inw_revisions_dtl SET  `due_date` ='".$post['due_dt']."' WHERE inw_id = ".$post['id']." order by r_id desc limit 1 ");
		}
		
		if($post['dept_id']!="")
		{
					$update_query="UPDATE inw_transactions SET current_status ='Undo',`completion_status` ='Undo',`comments` ='Undo',`assign_department` ='". $post['dept_id']."',`sub_user_id` ='". $_SESSION['sub_user_id']."',`process_ulogin_type` ='". $_SESSION['user_type']."',process_user='".$_SESSION['user_id']."' WHERE id = ".$post['tr_id']." ";

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
                  <input type="text" class="form-control monthlyinputdate" name="recv_dt" id="recv_dt" required placeholder="Select Received Date" value="<?php echo date('d-m-Y',strtotime($update_articles[0]['recv_dt'])); ?>" readonly/>
                </div>
              </Input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Due Date</label>
             <div class="d-flex" id="journal_duration">
                  <input type="text" class="form-control monthlyinputdate" name="due_dt" id="due_dt" required placeholder="Select Due Date" value="<?php echo date('d-m-Y',strtotime($update_articles[0]['due_dt'])); ?>" readonly/>
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-end">
                <div class="col-6 col-lg-6 ">
                    <input type="checkbox" name="test_article" id="test_article" <?php if ($update_articles[0]['test_article']==1){?>checked<?php }?> value="1"><label class="ms-2">Is Test Article</label>
                </div>
                 <div class="col-6 col-lg-6 ">
                    <input type="checkbox" name="highpriority" id="highpriority" <?php if ($update_articles[0]['highpriority']==1){?>checked<?php }?> value="1"><label class="ms-2">Is High Priority</label>
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
								<?php if($update_articles[0]['stage'] == 'FP' && $update_articles[0]['cust_id']=='33' ) { ?>
								<option value="169" <?php if ($update_articles[0]['wf_id']==169){?>selected<?php }?> style="font-weight:500;">Peerj-Tex-Clean</option>
								<?php } ?>
              <?php 
              $showjournal= $auth->showjournals($update_articles[0]['j_id']);

              $j_wp_fp=$showjournal[0]['j_fp_wf'];
              $j_rev_wf=$showjournal[0]['j_rev_wf'];
              $j_fin_wf=$showjournal[0]['j_fin_wf'];
              
              $showArticleworkflow= $auth->getJournalWorkflow($j_wp_fp,$j_rev_wf,$j_fin_wf);
            //  $showArticleworkflow= $auth->getArticleWorkflow($update_articles[0]['j_id']);

              foreach($showArticleworkflow as $res_wf)
              {
                  
                      if($update_articles[0]['wf_id']!="")
                      {
                  ?>
                 <option <?php if ($res_wf['wf_id']== $update_articles[0]['wf_id']){?>selected<?php }?> value="<?php echo $res_wf['wf_id'] ?>"style="font-weight:500;"><?php echo $res_wf['wf_name'] ?> </option>
							
                      <?php
                      }
                      else
                      {
                      ?>
                       <option value="<?php echo $res_wf['id'] ?>"style="font-weight:500;"><?php echo $res_wf['wf_name'] ?> </option>
								<?php if($update_articles[0]['stage'] == 'FP' && $update_articles[0]['cust_id']=='33' ) { ?>
								<option value="169"  style="font-weight:500;">Peerj-Tex-Clean</option>
								<?php } ?>
                      <?php
                      }
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
                                <option value="<?php echo $row['dept_code']; ?>" <?php if($update_articles[0]['department_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
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
                                </select>
                </div>
				
				
                </div>
				
				 <div class="col-12 col-sm-12 col-lg-12" id="query_div" style="display:none;">
              <label class="mb-2">Query </label>
              <textarea name="message_query" class="form-control" id="message" class="required"></textarea>

                </div>
            </div>
            <input type="hidden" name="aid" id="aid" value="<?php echo $_REQUEST['aid'];?>">
            <input type="hidden" name="id" id="id" value="<?php echo $_REQUEST['aid'];?>">
			<input type="hidden" name="rev_id" value="<?php echo $rev_info['id']; ?>" />							
            <input type="hidden" name="action" value="update">
             <input type="hidden" name="old_group_folder_name" id="old_group_folder_name" value="<?php echo $update_articles[0]['issueorgroup_folder']; ?>" />
			 
			 
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
		}
		else if(status == 'Query')
		{
			$('#query_div').show();
		}
		else if(status == 'Query Reply')
		{
			$('#query_div').show();
		}
		else if(status == 'Remarks')
		{
			$('#query_div').show();
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

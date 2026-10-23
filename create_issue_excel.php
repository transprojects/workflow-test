<?php 
include "includes/includes.php";
include_once 'includes/issue_drafts.php';
include("simplexlsx-master/src/SimpleXLSX.php");

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
				unlink($f->getRealPath());
				if(empty($f))
				{
					unlink($f);
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
            rmdir($f->getRealPath());
        }
    }
    rmdir($src);
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

if(isset($_REQUEST['create_issue'])) 
{
	
mysqli_query($db,"SET sql_mode = ''");
$cust_id=$_REQUEST['cust_id'];
$j_id=$_REQUEST['j_id'];
$upload ="uploads/";
if(!file_exists($upload)){
mkdir($upload);
}

$excel_file=$_FILES['excel_file']['name'];
if($excel_file!="")
{
$string = $excel_file;
$pos=strrpos($string,'.');
$mainext=substr($string,($pos+1));
$excel_file='issue_file'.'_'.time().'.'.$mainext;
move_uploaded_file($_FILES['excel_file']['tmp_name'],$upload.$excel_file);
}
else
{
$excel_file="";
}

$inputFileName="uploads/".$excel_file.""; 


	
		foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
		}
		
	
	$post['recv_dt'] = convert_mdy_ymd($post['recv_dt']);	
	$post['due_dt'] = convert_mdy_ymd($post['due_dt']);	
	
	//// Get the worflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '37';";
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
		
	
		
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$cust_id.";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$j_id.";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
	$newvolume_folder = VOLUME_PREFIX.$_POST['volume_number'].$_POST['issue_number'];
	
	$dl_destination_dir = WF_DIR."/downloads/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$newvolume_folder."/ISSCOR1/misc/";
	mkdir($dl_destination_dir, 0777, true);
	
	$work_dir = WF_DIR."/production/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$newvolume_folder;	
	mkdir($work_dir, 0777, true);
		
	//// Delivery DIR /////
	mkdir($work_dir."/Delivery/ISSCOR1/", 0777, true);
		
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
		
				$insert_query = "INSERT INTO `inw_volume_issues` (`cust_id`, `j_id`,`volume_number`,issue_number,issue_count,`received_date`, `due_date`, `correction_pages`, `comments`,`created_by`, `created_on`,work_dir,dispatch_dir,stage) VALUES ('0', '0','".$post['volume_number']."', '".$post['issue_number']."','".$post['issue_count']."','".$post['recv_dt']."', '".$post['due_dt']."', '".$post['correction_pages']."', '".addslashes($post['instructions'])."','".$_COOKIE['user_id']."', NOW(),'".$work_dir."','".$work_dir."/Delivery/ISSCOR1/"."','ISSCOR1');";

		
		$db->query($insert_query) or idr_fail('create_issue_excel.php', '', 'The issue could not be saved: '.$db->error);
		
		$volume_issue_id = $db->insert_id;

		/// First issue-correction round (ISSCOR1) is recorded with the issue, so the job card shows its tab
		$db->query("INSERT INTO `inw_issue_corrections` (`vi_id`,`type`,`correction_count`,`received_date`,`due_date`,`correction_pages`,`comments`,`file_name`,`created_by`,`created_on`) VALUES ('".$volume_issue_id."','ISSCOR','1','".$post['recv_dt']."','".$post['due_dt']."','".(int)$post['correction_pages']."','".addslashes($post['instructions'])."',NULL,'".$_COOKIE['user_id']."',NOW())") or idr_fail('create_issue_excel.php', '', 'The issue was saved but its ISSCOR1 record could not be created: '.$db->error);

			$prj_update_vi="UPDATE inw_volume_issues SET `cust_id` ='".$cust_id."',`j_id` ='". $j_id."' WHERE vi_id = ".$volume_issue_id." ";
					//echo $prj_update_vi; exit;
						$db->query($prj_update_vi);
			
				
		


		$insert_ld=mysqli_query($db,"insert into issue_logs(`issue_id`) values ('".$volume_issue_id."')");
		$issueid_log=mysqli_insert_id($db);

					/// Get the Client Name ////
	
		
		if ( $xlsx = SimpleXLSX::parse($inputFileName)) {
		$i=0;
		$c=0;
		$e=0;
	foreach( $xlsx->rows() as $aid )
		{
			
			if($aid == "")
		{
			continue;
		}
			
		if($aid['2']!="" && $aid['2']!='Article ID')
		{
		
			//echo "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$aid['2']."'";exit;
			
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".trim($aid['2'])."' and v_issue_id <=0 and cust_id='".$cust_id."' and j_id='".$j_id."' "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			$app_res = $result->fetch_assoc();	

			if($toatl_count>0)
			{
				
				$id = $app_res['id'];
				
				
		
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
		
		$insert_ld=mysqli_query($db,"insert into issue_log_details(`is_id`,`article_id`,`is_status`,`is_remark`) values ('".$issueid_log."','".$app_res['pub_id']."','C','In client review')");
				$c++;
				}
				else
				{
					$insert_ld=mysqli_query($db,"insert into issue_log_details(`is_id`,`article_id`,`article_oid`,`is_status`,`is_remark`) values ('".$issueid_log."','".$aid['2']."','".$app_res['pub_id']."','E','".$app_res['status']."')");
					$e++;
			
				}
					
				
			}
			else
			{
			$insert_ld=mysqli_query($db,"insert into issue_log_details(`is_id`,`article_id`,`article_oid`,`is_status`,`is_remark`) values ('".$issueid_log."','".$aid['2']."','".$app_res['pub_id']."','E','Not in Database or Already assigned')");
					$e++;
			}
			$i++;

			}
		  
		}
		
				$iss_update_vi=mysqli_query($db,"UPDATE issue_logs SET  `total_cnt` ='".$i."', `success_cnt` ='".$c."',`error_cnt` ='". $e."' ,`datetime`='".date('Y-m-d H:i:s')."',`staff` ='".$_COOKIE['user_id']."' WHERE id = '".$issueid_log."'");
					
		//exit;
	}
	 
	 
	header("location:issue_logs.php?is_id=".$issueid_log.""); 
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>

    <!-- Data Table CSS --->
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
    <style>
      /* Create Issue from Excel: same field / button look as the other issue pages (ids, names and scripts are unchanged) */
      #add_article .formcont1heading{display:flex;align-items:center;gap:8px;margin:0 0 14px !important;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;background:transparent !important;border:0 !important;padding:0 !important;width:auto !important;height:auto !important;}
      #add_article .formcont1heading:before{content:"";width:4px;height:16px;border-radius:3px;background:#e8742a;}
      #add_article label:not(.error){font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;margin:0 0 4px !important;}
      #add_article .form-control,#add_article .form-select,#add_article textarea{height:38px;border:1px solid #d5dde6;border-radius:9px;font-size:13.5px;box-shadow:none;}
      #add_article textarea{height:auto;min-height:84px;padding:8px 12px;}
      #add_article .form-control:focus,#add_article .form-select:focus,#add_article textarea:focus{border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);outline:0;}
      #add_article label.error{display:block;margin-top:4px;color:#d64545;font-size:12px;font-weight:600;text-transform:none;letter-spacing:0;}
      #add_article .container,#add_article .container-fluid{max-width:none;padding-left:0;padding-right:0;}
      #add_article .separatetitle:after{display:none !important;}
      #add_article .meta_nxtbtn{margin:18px 0 0 !important;}
      #add_article .meta_nxtbtn .row{justify-content:flex-end;gap:10px;margin:0;}
      #add_article .meta_nxtbtn .col{flex:0 0 auto;width:auto;padding:0;}
      #add_article .btn-next-cancel{height:38px;padding:0 16px;border-radius:9px;border:1px solid #e4e9ef;background:#fff;color:#14304a;font-weight:600;font-size:13.5px;}
      #add_article .btn-next-cancel a{color:#14304a !important;}
      #add_article .btn-next{height:40px;padding:0 24px;border:0;border-radius:10px;font-weight:700;font-size:13.5px;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);box-shadow:0 4px 12px rgba(232,116,42,.35);}
      .ie-note{display:flex;gap:12px;align-items:flex-start;margin:0 0 14px;padding:12px 14px;border-radius:12px;background:#e8f2fb;border:1px solid #c5daef;color:#17405f;font-size:13px;}
      .ie-note i{margin-top:2px;color:#1b6ca8;font-size:16px;}
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
          <div><small>Issues</small><h1>Create New Volume and Issue</h1></div>
          <a class="af-add" href="manageissues" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
      <div class="af-page"><div class="af-card">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add New</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Customer <span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>
                    <option value="">Select Customer</option>
                    <?php
                    $cust_rs = $db->query("SELECT id, cust_name FROM `adm_customer_master` ORDER BY `cust_name` ASC");
                    while($cust_rs && ($cr = $cust_rs->fetch_assoc())){ ?>
                    <option value="<?php echo (int)$cr['id']; ?>"><?php echo htmlspecialchars($cr['cust_name']); ?></option>
                    <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Journal <span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Enter Volume Number<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="volume_number" id="volume_number" placeholder="Enter volume number" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Enter Issue Number<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="issue_number" id="issue_number" placeholder="Enter issue number" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Received Date <span style="color: red">*</span></label>
              <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate" name="recv_dt" id="recv_dt" required placeholder="Select Received Date" value="<?php echo date('d-m-Y'); ?>" readonly/>
                </div>
              </Input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Due Date<span style="color: red">*</span></label>
             <div class="d-flex" id="journal_duration">
                  <input type="text" class="form-control monthlyinputdate1" name="due_dt" id="due_dt" required placeholder="Select Due Date" value="<?php echo date('d-m-Y'); ?>" readonly/>
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Corrections Pages<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="correction_page" id="correction_pages" placeholder="Enter corrections page" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Stage<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="stage" id="stage" required>
                  <option value="">Select stage</option>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Workflow<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="wf_id" id="wf_id" required>
                  <option value="">Select workflow</option>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Instructions </label>
              <textarea type="text" class="form-control" name="instructions" id="instructions"></textarea>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-end">
                </div>
                </div>
            </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/add-article" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button id="create_issue" value="Create" name="create_issue" type="submit" class="btn-next" >Create</button>
                    </div>
                </div>
            </div>
      </form>

      <!-- upload section -->
    <div class="content_right" style="display:none;" >
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
                  <div class="droppedfile mt-4">
                    <p style="font-size: 1.25em">Uploaded</p>
                    <div class="col-12 fileinfo mt-2">
                      <p class="">Document Name.pdf</p>
                      <button type="button">
                        <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/trash.svg" />
                      </button>
                    </div>
                    <div class="fileupdtn">
                       <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/add-article" style="text-decoration:none;">Cancel</a></button>
                      <button type="button" class="filebtn2 btn-save btn-uploadworklfow">Upload File</button>
                    </div>
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
        </div>
      </div>
      <?php echo scripts(); ?>	
      <?php idr_error_script(); idr_error_popup('Cannot create the issue'); ?>

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
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
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
             url: "get_journal.php",
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
                url:'get_workflow.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                  //  $('#wf_id').html(html);   
                    populateSelecte_wf("wf_id",data);                 
                }
            });  
			
			$.ajax({
                url:'get_group_folder.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                   // $('#group_folder_name').html(html);     
                    populateSelecte_gf("group_folder_name",data);                     
                }
            }); 
			
			$.ajax({
                type:'POST',
                url:'get_journal_duration.php',
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

    </script>

<script>  
 //	$.mask.definitions['~']='[+-]';

$(document).ready(function() {
    $("#add_article").validate({
        rules: {

          cust_id: "required",
          j_id: "required",
          volume_number: "required",
          issue_number: "required",
          due_dt: "required",
          recv_dt: "required",
          correction_page: "required",
          stage: "required",
          wf_id: "required",
          instructions: "required"
           
        },
        messages: {

          cust_id: "Please select customer",
          j_id: "Please select journal",
          volume_number: "Please enter volume number",
          issue_number: "Please enter issue number",
          due_dt: "Please enter Due date",
          recv_dt: "Please enter Received Date",
          correction_page: "Please enter correction page",
          stage: "Please enter stage",
          wf_id: "Please select workflow",
          instructions: "Please write instructions"
           
        },

        submitHandler: function(form) {
            $.ajax({
                url: "upload_article.php",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                    // Success message
                   // alert("Form submitted successfully!");
                    // Reset the form
                   // $("#articlesubmission")[0].reset();
                      $("#pub_id").prop("readonly", true);
                      $("#cust_id").prop("disabled", true);
                      $("#j_id").prop("disabled", true);
                      $("#recv_dt").prop("disabled", true);
                      $('.btn-next').hide();
                      $('.meta_nxtbtn').remove();
                      $('.content_right').show();
                      $('.btn-save').hide();
                      $(".btn-worklfow-cancel").show();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Error message
                    idrError("The issue could not be created: " + (errorThrown || textStatus || "server error"), "Cannot create the issue");
                    $('.content_right').hide();
                    $('.btn-next').show();
                    $('.btn-save').hide();
                    $(".btn-cancel").hide();
                     $(".btn-worklfow-cancel").hide();

                }
            });
          }
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
             url: 'upload-new-articles-data-file.php',
             paramName: 'file',
             
             params: {
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
                    url: 'upload-new-articles-data-file.php',
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
    url: "upload_article_to_workflow.php",
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

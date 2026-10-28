<?php include "includes/includes-sw2.php";?>
<?php 
require_once __DIR__ . '/includes/vp-files.php';   /* required-files check for the file movement (same rules as the main Start Work v2) */
vp_files_endpoint($db);
include_once("smtpmail/phpmailer/class.phpmailer.php");

session_start();




//$mail_from = "inward@transforma.in";
//$mail_pass = "Zot60242";
$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];


$filecopy_err = array();
$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	$app_res = $result->fetch_assoc();
	
	
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$app_res['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$app_res['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();
		
		$Cn= $crow['cust_name'];
		$J_name = $jninfo['j_code']; 	

// Define the folder to be zipped
$folderPath = WF_DIR."/downloads/JOURNALS/".$Cn."/".$J_name."/".$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage'];  

// Create a zip archive
$zip = new ZipArchive();
$zip_path1="downloads/".$_COOKIE['user_id']."/";
$zip_path2="downloads/".$_COOKIE['user_id']."/".date('d-m-Y')."/";
// Check if the directory doesn't exist
if (!is_dir($zip_path1)) {
    // Create the directory
    if (mkdir($zip_path1)) {
      //  echo "Directory '$zip_path1' created successfully.";
    } else {
       // echo "Error creating directory '$zip_path1'.";
    }
} else {
  //  echo "Directory '$zip_path1' already exists.";
}
if (!is_dir($zip_path2)) {
    // Create the directory
    if (mkdir($zip_path2)) {
       // echo "Directory '$$zip_path2' created successfully.";
    } else {
       // echo "Error creating directory '$$zip_path2'.";
    }
} else {
   // echo "Directory '$$zip_path2' already exists.";
}

//$zipFileName =$zip_path2.$app_res['pub_id']."_".date('dmYHis').".zip";
$zipFileName =$zip_path2.$app_res['pub_id'].".zip";
unlink($zipFileName);

if (is_dir($folderPath) && $zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
    // Add files to the zip file
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($folderPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Close zip
    $zip->close();

    // Provide download link
    $downloadLink = $zipFileName;

} else {
    $downloadLink = '';
}
// No files to zip (folder missing/empty) -> no zip is created; the link then shows a popup instead of a 404
if ($downloadLink !== '' && !is_file($downloadLink)) { $downloadLink = ''; }



if(isset($_POST['download'])){
    

	 $target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$J_name."/".$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage'];  
	$filename =$target_dir. "/".$app_res['pub_id']. ".zip";
	
	$rootPath = realpath($target_dir);

	
$zip_folder_path= $target_dir. "/".$app_res['pub_id']. ".zip";

	// Create a new ZipArchive instance
$zip = new ZipArchive();

// Open the zip archive for writing
if ($zip->open($zip_folder_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    // Get all files in the folder
	//echo $target_dirs=$target_dir;
       // Open the source folder
	   $dir = opendir($target_dir);

	   // Iterate through the files in the folder
	   while (($file = readdir($dir)) !== false) {
		   if ($file != '.' && $file != '..') {
			   $filePath = $target_dir . '/' . $file;
   
			   // Add the file to the zip archive
			   $zip->addFile($filePath, $file);
		   }
	   }
   
	   // Close the source folder
	   closedir($dir);
   
	   // Close the zip archive
	   $zip->close();
}

// Zip archive will be created only after closing object
$zip->close();

    if (file_exists($zip_folder_path)) {
		
		
		$filename = $zip_folder_path;
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($zip_folder_path).'"');
        header('Content-Length: ' . filesize($zip_folder_path));
        flush();
        readfile($zip_folder_path);
        // delete file
       unlink($zip_folder_path);
	   
	   
	   
	   
	   @$body ="Dear Team,
<br/>
<br/>
Please note that ".$_COOKIE['user_name']." have downloaded  the article ".basename($zip_folder_path).". and started the process.
<br/><br/>

Thank you.
<br/><br/>
Best regards,
<br/>
Transforma";
@$subject = "Article ".basename($zip_folder_path)." inprogress by ".$_COOKIE['user_name']." ";	

		$mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $mail_pass;
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject =$subject;
        $mail->Body = $body;
		$to="marimuthu@transforma.in";
		$cc="";
		$bcc="";
		
		//$cc="pm@transforma.in,inward@transforma.in,indesign@transforma.in,abidha@transforma.in,journalqc@transforma.in,tvlce@transforma.in,art@transforma.in";
		//$bcc="marimuthu@transforma.in,swamy@transforma.in";
		
//// Send mail to more than one user ///
        $mail_to = explode(',', $to);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


        $mail->AltBody = ""; // optional, comment out and test

        $mail_tocc = explode(',', $cc);
        foreach ($mail_tocc as $email) {
            if ($email != '') {
                $mail->AddCC($email);
            }
        }

        $mail_tobcc = explode(',', $bcc);
        foreach ($mail_tobcc as $email) {
            if ($email != '') {
                $mail->AddBCC($email);
            }
        }

		 $result=$mail->Send();
    

    }
}




/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET present_user_id = '".$_COOKIE['user_id']."' WHERE id = ".$_REQUEST['id']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
	}
	
	/////////// Here Update the Take Over Transaction record //////
	if(isset($_REQUEST['tid']))
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Take Over' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code,skip_dept FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///

$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';"); 	
	
	$transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
		$insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '1', '".$_COOKIE['user_id']."',NOW());";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
		
			$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$app_res['department_id']."'"));

		$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">Inprogress</span>';
	 	 $prj_update_query="UPDATE inw_inward_dtl SET  user_working_status = '".$user_working_status."',vendor_completed_date='".date('Y-m-d H:i:s')."',last_transaction_id='".$transaction_id."' WHERE id = ".$app_res['id']."; ";			
		$db->query($prj_update_query);			
		
		
	}
	
}

//echo $transaction_id;
?>

  <?php
	//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$app_res['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
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

    <!-- Data Table CSS -->
    <style>
th  {
  color: #336199 !important;
  background-color: #fff1f1 !important;
  border-top: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
}
 td {
  color: #336199 !important;
  background-color: #fff !important;
  border-top: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
}
/* td:last-child{
	border-right:  1px solid #dedef3 !important;
} */
table.dataTable tbody td:not(:last-child):after {
    content: "";
    height: 15px;
    width: 0.5px;
    border: none !important;
    position: absolute;
    right: 0;
    top: 20px;
}
.startnav-clock{
	right: 0 !important;
	-webkit-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	-moz-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	background-color: #ec5f19db !important;
	color: #fff !important;
	border-radius: 22px !important;
	font-weight: 600 !important;

}
  </style>
  </head>	

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>


      <!-- Page body starts -->
      
      <div class="container-fluid" style="justify-content: space-between;position:sticky;top:120px;background:#fff;padding-bottom:5px;z-index:200;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
           <div class="col-8 col-lg-4">
            <p class="articleheading ms-4">Article Details</p>
			
			
          </div>

		   <div class="col-12 d-none d-lg-flex col-lg-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
				</div>
			</div>
		    </div>
		  
		  <div class="col-4 col-lg-4">
      <div class="row d-flex justify-content-end pe-2">
        <div class="col-3 articlebtns ">
          <!--<a href="javascript:history.back();">-->
          <a href="<?php echo $_COOKIE['set_url'];?>/allocatedarticles">
            <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
        </div>
		<div class="col-12 d-flex d-lg-none col-lg-4 mt-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
				</div>
			</div>
		    </div>
      </div>

            <!-- Table section -->
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
        <div class="row">
        <div class="col-md-12">
                      <div class="" id="widget-box-2">
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
													<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-comments"></i>
														Article Information
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<tbody>
																<tr>
																	<td style="width: 50%" class="">Customer Name</td>
																	<td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
																</tr>
                                <tr>
																	<td class="">Journal</td>
																	<td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
																</tr>
                                <tr>
																	<td class="">Stage</td>
																	<td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
																</tr>
                                <tr>
																	<td class="">Article Title</td>
																	<td class="hidden-480"><?php echo $app_res['pub_id']; ?></td>
																</tr>
                                <tr>
																	<td class="">Received Date</td>
																	<td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></td>
																</tr>
                                <tr>
																	<td class="">Due Date</td>
																	<td class="hidden-480"> <?php
						  if($app_res['stage']=='FP')
						  {
						  echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
						  }
						  else
						  {
						  echo (date('d-m-Y',strtotime($app_res['due_dt'])));
						  }
						  ?></td>
																</tr>
															</tbody>
														</table>
													</div>
												</div>
											</div>
        </div>
      <div class="col-md-12">
                      <div class="" id="widget-box-2" >
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
														<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-table"></i>
														File Download
													</h5>

													
												</div>

											<div class="widget-body" style="padding-top: 20px; margin: 20px">
												<form name="download" method="post" action="">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th>Download Zipped Folder</th>
																</tr>
															</thead>

															<tbody>
																<tr>
																	<td class="hidden-480">
                                                                  <!--  <input type='submit' name='download' value='Download' class="ace-icon fa fa-cloud-download bigger-120" />-->
    <?php if ($downloadLink): ?>
        <p><a href="<?php echo $downloadLink; ?>"><img src="downloads.png" alt="Download Icon" width="20"> Download Zip File</a></p>
    <?php else: ?>
        <p><a href="#" onclick="alert('Download file is not available. No files were found for this article.'); return false;"><img src="downloads.png" alt="Download Icon" width="20"> Download Zip File</a></p>
    <?php endif; ?>
																	</td>
																</tr>

															</tbody>
														</table>
													</div>
												</form>
											</div>
											</div>
                     
                      </div>
					  
					  
					  <div class="col-md-12">
                      <div class="" id="widget-box-2" >
												
                      </div>

											
					
					  </div>
                      </div>
                    </div>
                </div>
        <!-- form section  -->
        <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">

       <div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
														<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-table"></i>
														File Upload
													</h5>

													
		</div>
			  <div class="p-5 pb-0 d-flex flex-column gap-2"> 
			<form action="do_upload_offline_process.php" method="post" class="dropzone well dz-clickable" id="dropzone" style="padding: 110px 0px;">
                <input type="hidden" id="vcust_id" name="vcust_id" value="<?php echo $Cn;?>">
                <input type="hidden" id="vj_id" name="vj_id" value="<?php echo $J_name;?>">
                <input type="hidden" id="folder_name" name="folder_name" value="<?php echo $app_res['pub_id']; ?>">
                <input type="hidden" id="volume_folder_name" name="volume_folder_name" value="<?php echo $app_res['volume_folder']; ?>">
                <input type="hidden" id="stage" name="stage" value="<?php echo $app_res['stage']; ?>">
                <div class="dz-default dz-message"><span><span class="bigger-150 bolder"><i class="ace-icon fa fa-caret-right red"></i> Drop files</span> to upload <span class="smaller-80 grey"></span> <br>
                  <i class="upload-icon ace-icon fa fa-cloud-upload blue fa-1x"></i></span></div>
              </form>
<?php
/* files this user uploaded to production/.../CE, shown so the vendor can cross-check */
$ce_dir = WF_DIR."/production/JOURNALS/".$Cn."/".$J_name."/".$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/CE/";
$ce_files = array();
/* only the files uploaded by the logged-in user (log is written by do_upload_offline_process.php) */
$ul_file = __DIR__.'/uploadlogs/'.md5($ce_dir).'.json';
$ul = is_file($ul_file) ? json_decode(file_get_contents($ul_file), true) : array();
if (is_array($ul)) { foreach ($ul as $ul_row) { if ((string)$ul_row['user'] === (string)$_COOKIE['user_id'] && is_file($ce_dir.$ul_row['file'])) { $ce_files[$ul_row['file']] = $ul_row['file']; } } }
$ce_files = array_values($ce_files);
?>
              <div id="ceFiles" class="mt-3">
                <div style="font-weight:600; margin-bottom:6px;">Your uploaded files (<?php echo count($ce_files); ?>)</div>
                <?php if ($ce_files): ?>
                <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                  <thead><tr><th>File name</th><th style="width:90px;">Size</th><th style="width:160px;">Uploaded on</th><th style="width:80px;">Action</th></tr></thead>
                  <tbody>
                  <?php foreach ($ce_files as $ce_f): ?>
                    <tr><td><?php echo htmlspecialchars($ce_f); ?></td><td><?php echo round(filesize($ce_dir.$ce_f)/1024, 1); ?> KB</td><td><?php echo date('d-m-Y H:i', filemtime($ce_dir.$ce_f)); ?></td><td><button type="button" class="btn btn-sm btn-outline-danger ce-del" data-name="<?php echo htmlspecialchars($ce_f, ENT_QUOTES); ?>">Delete</button></td></tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
                <?php else: ?>
                <div class="text-muted" style="font-size:13px;">You have not uploaded any files yet.</div>
                <?php endif; ?>
              </div>
	   </div>
            <div class="container mt-4">
			
			 <div class="col-md-6">		
			
					</div>		
					
					
					
       		 <form class="form-horizontal" id="validation-form" method="post">

              <div class="formcont1heading">Work Status and comments to Team</div>
			  <div class="p-5 pb-0 d-flex flex-column gap-2"> 
            
 
					  <?php 
          
					/* Adding this for 94 XML editor 
					Swamy - 09-08-2022 
					Adding the records in DB
					Provide the link to PAG User */
					$copy_files = true;
					$temp_dept = $app_res['status'];
					if(strpos($app_res['status'], "PAG") !== false)
					{
						$app_res['status'] = "PAG";
					}
					if(strpos($app_res['status'], "CE") !== false)
					{
						$app_res['status'] = "CE";
					}
					if(strpos($app_res['status'], "CE-ELX") !== false)
					{
						$app_res['status'] = "CE-ELX";
					}
					//// Checking in Transactions table to stop the copy files to Edit Latex Server ////
					$transaction_query = "select count(id) cnt from `inw_transactions` where dept='".$app_res['department_id']."' and `project_id` = '".$app_res['id']."' and `stage` = '".$app_res['stage']."' ";
					$transaction_res = $db->query($transaction_query); 					
					$trans_res_row = $transaction_res->fetch_assoc();
					//print_r($trans_res_row);
					if($trans_res_row['cnt'] > 1)
					{
						$copy_files = false;
					}
					
	
					  if(($customer_info['cust_name'] == "SIF-Editlatex" || $customer_info['cust_name'] == "SIF" || $customer_info['cust_name'] =="Transforma") && $app_res['stage'] != "FIN1" && $app_res['status'] == "PAG" && ($journal_info['j_code'] =="EPL" || $journal_info['j_code'] =="NCC")){
						include("94_edit_latex_url.php");
					  } 
					  // Added on 19-10-2022 for JSIAM Customer
					  if($customer_info['cust_name'] =="IAP" && $app_res['stage'] != "FIN1" && ($app_res['status'] == "PAG" || $app_res['status'] == "CE" || $app_res['status'] == "CE-ELX") && $journal_info['j_code'] =="JSIAM")
					  {
						if($app_res['status'] == "PAG" && $app_res['stage'] == "FP")
						{
							$copy_files = false;
						}
						include("94_edit_latex_url.php");
					  } 
					  $app_res['status'] = $temp_dept;
					  ?>
					  
            <?php 
						   $html_read = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['pub_id']."_report.html";   // destination folder or file 
						  ?>
						  
						  	  

                     
                        
                             <input type="hidden" name="action" value="update">
                              <input type="hidden" name="tr_id" value="<?php echo $transaction_id; ?>" />
                              <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                              <input type="hidden" name="wf_id" value="<?php echo $app_res['wf_id']; ?>" />
                              <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                              <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">
                              <input type="hidden" name="department_id" value="<?php echo $app_res['department_id']; ?>" />
                              <input type="hidden" name="dept_name" value="<?php echo $pdept_res['dept_name']; ?>" />
                              <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
                              <input type="hidden" name="v_issue_id" value="<?php echo $app_res['v_issue_id']; ?>" />
                              <input type="hidden" name="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
                            
							<?php 
					  if(($app_res['department_id'] == 1 || $app_res['department_id'] == 2 || $app_res['department_id'] == 32 || $app_res['department_id'] == 29 || $app_res['department_id'] == 5) && ($app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 66 || $app_res['cust_id'] == 68 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 43 || $app_res['cust_id'] == 76 || $app_res['cust_id'] == 57 || $app_res['cust_id'] == 58 || $app_res['cust_id'] == 64 || $app_res['cust_id'] == 82 || $app_res['cust_id'] == 84  || $app_res['cust_id'] == 69))
					  {
					 $ceditor_ip = "103.66.12.3:8030";

					  ?>
                      
					 <div class="form_btn nxtbtn">
					 
					    <a class="btn btn-danger" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_id']; ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> CE Editor</a>
					    </div>
					    <?php 
					  }
					  ?>
							
							 

                    <div class="col-12 col-sm-6 col-lg-4">
                    <label class="mb-2" >Choose Status :<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Status" name="status" id="status">
				                                    <option value="">-Select Status-</option>

                                <?php foreach($project_status_array as $id=>$val){?>
                                     <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                                 <?php } ?>
                                 </select>
                </div>

                
                       

                       
                       
                        
                        <div class="space-2"></div>
                         
                          <div class="row" id="checklist_table" style="display:none;">
                           <h3 class="formcont1heading" style="position: relative; left: -37px;">Checklist Form</h3>
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12 pt-3">
                                  <table  id="simple-table" class="table  table-bordered table-hover">
                                    <thead>
                                      <tr>
                                        <th>Details</th>
                                        <th width="10%">Yes</th>
                                        <th width="10%">NA</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php
		$query = "SELECT * FROM `jbm_checklist` WHERE (`stage` = '".$app_res['stage']."' OR `stage` = 'BOTH') AND `dept_id` = '".$app_res['department_id']."' ORDER BY `display_order` ASC ";
		$qry_result = $db->query($query) or ($db->error);		
		while($row = $qry_result->fetch_assoc()){ ?>
		
                                      <tr>
                                        <td><?php echo $row['description']; ?></td>
                                        
<td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="Yes" checked="checked"/>
                                            </td><td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="NA"/>
                                            </td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
						  
                 <div class="col-12 col-sm-4 col-lg-4">
                  <label class="mb-2">Word Count <span style="color: red">*</span></label>
                  <input name="vendor_wc" class="form-control" placeholder="Word Count" value="" required type="text">
                </div>
				
                <div class="col-12 col-sm-12 col-lg-12">
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="comments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

              

                <div class="container-fluid mt-5 mb-5">
				
				<div class="container-fluid mt-5 mb-5 d-flex justify-content-center">
                <div class="row d-flex gap-3 justify-content-center">
                    <div class="col-12 col-lg-5 form_btn cancelbtn d-flex justify-content-md-end justify-content-center">
                        <button type="reset">Cancel</button>
                    </div>
                    <div class="col-12 col-lg-5 form_btn nxtbtn d-flex justify-content-center">
                              <button id="btn_submit" type="submit" value="Save" name="btn_submit" class="btn btn-info">Save</button>
                    </div>
                </div>
          	  </div>

            </div>
			
			</div>
        
		</form>
		</div>
		
			
            </div>
			</div>
			
			
			<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>
					
     <!-- Footer Section -->
     <?php echo footer(); ?>
	 </div>



<!-- Footer Section -->
<?php echo scripts(); ?>
<?php include_once __DIR__ . '/includes/vp-sw-ui.php'; vp_sw_theme(array(
  'id' => (string)$app_res['pub_id'], 'client' => (string)$customer_info['cust_name'], 'journal' => (string)$journal_info['j_code'],
  'stage' => (string)$app_res['stage'], 'dept' => (string)$app_res['status'],
  'recv' => (!empty($app_res['recv_dt']) && $app_res['recv_dt'] != '0000-00-00') ? date('d-m-Y', strtotime($app_res['recv_dt'])) : '',
  'due' => ($app_res['stage'] == 'FP' && !empty($app_res['target_due_dt'])) ? date('d-m-Y', strtotime($app_res['target_due_dt'])) : date('d-m-Y', strtotime($app_res['due_dt'])),
  'back' => $_COOKIE['set_url'] . '/allocatedarticles',
  'old' => 'start_work.php?id=' . urlencode($_REQUEST['id']) . (isset($_REQUEST['tid']) ? '&tid=' . urlencode($_REQUEST['tid']) : ''))); ?>	


   <!-- inline scripts related to this page --> 
<script type="text/javascript">
$.validator.setDefaults({
		submitHandler: function(form) {
			var a_id = $("input[name='id']").val();
			var dept_name = $("input[name='dept_name']").val();
			var status = $( "#status option:selected" ).text();
			if(dept_name == "PAG" && status == "Completed")
			{
				$.ajax({
					type:'POST',
					url:'get_graphics_status.php',
					data:'a_id='+a_id,
					success:function(result){
						if(result == 1)
						{
							form.submit();
						}
						else
						{
						
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-warning"> <strong> Warning! </strong>'+ result +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});					
							return false;
						}         
					}
				});  
				return false;
			}
			else
			{
				form.submit();
			}
		}
	});

	
			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});				
			})
			
		$(document).ready(function(){	
			///////////// Get workflow from Journal //////////
	
	$('#status').on('change',function()
	{
        var status = $(this).val();
        if(status == 6)
		{
			$('#assign_department_div').show();
		}
		else
		{
            $('#assign_department_div').hide();
            
        }
		if(status == 1)
		{
			$('#checklist_table').show();
		}
		else
		{
            $('#checklist_table').hide();
            
        }
		
    });
	
			
				
	
	})
	
		</script>
		

<script type="text/javascript">
			jQuery(function($){
			
			try {
			  Dropzone.autoDiscover = false;
			
			  var myDropzone = new Dropzone('#dropzone', {
			    previewTemplate: $('#preview-template').html(),
			    
				thumbnailHeight: 120,
			    thumbnailWidth: 120,
			    maxFilesize: 0.5,
				
				addRemoveLinks : true,
				dictRemoveFile: 'Remove',
				
				dictDefaultMessage :
				'<span class="bigger-150 bolder"><i class="ace-icon fa fa-caret-right red"></i> Drop files</span> to upload \
				 <br /> \
				<i class="upload-icon ace-icon fa fa-cloud-upload blue fa-3x"></i>'
			,
				
			    thumbnail: function(file, dataUrl) {
			      if (file.previewElement) {
			        $(file.previewElement).removeClass("dz-file-preview");
			        var images = $(file.previewElement).find("[data-dz-thumbnail]").each(function() {
						var thumbnailElement = this;
						thumbnailElement.alt = file.name;
						thumbnailElement.src = dataUrl;
					});
			        setTimeout(function() { $(file.previewElement).addClass("dz-image-preview"); }, 1);
			      }
			    }
			
			  });
			
			
			  //simulating upload progress
			  var minSteps = 6,
			      maxSteps = 60,
			      timeBetweenSteps = 100,
			      bytesPerStep = 100000;
			
			  myDropzone.uploadFiles = function(files) {
			    var self = this;
			
			    for (var i = 0; i < files.length; i++) {
			      var file = files[i];
			          totalSteps = Math.round(Math.min(maxSteps, Math.max(minSteps, file.size / bytesPerStep)));
			
			      for (var step = 0; step < totalSteps; step++) {
			        var duration = timeBetweenSteps * (step + 1);
			        setTimeout(function(file, totalSteps, step) {
			          return function() {
			            file.upload = {
			              progress: 100 * (step + 1) / totalSteps,
			              total: file.size,
			              bytesSent: (step + 1) * file.size / totalSteps
			            };
			
			            self.emit('uploadprogress', file, file.upload.progress, file.upload.bytesSent);
			            if (file.upload.progress == 100) {
			              file.status = Dropzone.SUCCESS;
			              self.emit("success", file, 'success', null);
			              self.emit("complete", file);
			              self.processQueue();
			            }
			          };
			        }(file, totalSteps, step), duration);
			      }
			    }
			   }
			
			   
			   //remove dropzone instance when leaving this page in ajax mode
			   $(document).one('ajaxloadstart.page', function(e) {
					try {
						myDropzone.destroy();
					} catch(e) {}
			   });
			
			} catch(e) {
			  // alert('Dropzone.js does not support older browsers!');
			}
			
			});
		</script> 
<script type='text/javascript'>
        Dropzone.autoDiscover = false;
        $(document).on('click', '.ce-del', function() {
            var name = $(this).data('name');
            if (!confirm('Delete "' + name + '"?')) { return; }
            $.ajax({
                type: 'POST',
                url: 'do_upload_offline_process.php',
                data: {name: name, vcust_id: $("#vcust_id").val(), vj_id: $("#vj_id").val(), folder_name: $("#folder_name").val(), stage: $("#stage").val(), volume_folder_name: $("#volume_folder_name").val(), request: 2},
                error: function(xhr) { alert(xhr.responseText || 'Could not delete the file.'); },
                complete: function() { $('#ceFiles').load(location.href + ' #ceFiles > *'); }
            });
        });
        $(".dropzone").dropzone({
            addRemoveLinks: true,
            init: function() { this.on('success', function() { $('#ceFiles').load(location.href + ' #ceFiles > *'); }); },
            removedfile: function(file) {
                var name = file.name;    
                
                $.ajax({
                    type: 'POST',
                    url: 'do_upload_offline_process.php',
                    data: {name: name,vcust_id:$("#vcust_id").val(),vj_id:$("#vj_id").val(),folder_name:$("#folder_name").val(),stage:$("#stage").val(),volume_folder_name:$("#volume_folder_name").val(),request: 2},
                    success: function(data){
                        console.log('success: ' + data);
                        $('#ceFiles').load(location.href + ' #ceFiles > *');
                    }
                });
                var _ref;
                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
            }
        });
        </script>
<?php
//echo "SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['id']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1";
$chk_transaction_time = mysqli_fetch_array(mysqli_query($db,"SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['id']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1")); 	
// Start time from PHP
$startTime = strtotime($chk_transaction_time['start_time']); // Example start time
//$startTime = strtotime("2024-03-23 08:57:19"); // Example start time
?>
 <script>
        $(document).ready(function(){
            // Start time from PHP
            var startTime = <?php echo json_encode($startTime * 1000); ?>;
            
            // Function to update the time
            function updateTime() {
                // Current time
                var endTime = new Date().getTime();

                // Calculate the time difference (in seconds)
                var timeDiff = Math.floor((endTime - startTime) / 1000);

                // Calculate days, hours, minutes, and seconds
                var days = Math.floor(timeDiff / (3600 * 24));
                var hours = Math.floor((timeDiff % (3600 * 24)) / 3600);
                var minutes = Math.floor((timeDiff % 3600) / 60);
                var seconds = timeDiff % 60;

                // Update spans with days, hours, minutes, and seconds
                $(".gtime-days").text(formatTime(days) + " days").toggle(days > 0);
                $(".gtime-hours").text(formatTime(hours) + "");
                $(".gtime-min").text(formatTime(minutes) + "");
                $(".gtime-sec").text(formatTime(seconds) + "");
				
				
            }

            // Update time every second
            setInterval(function() {
                updateTime();
            }, 1000);

            // Initial update
            updateTime();
        });

        // Function to format time to add leading zeros if needed
        function formatTime(time) {
            return time < 10 ? "0" + time : time;
        }
    </script>
  <?php 
  
  /** copy files
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
require_once __DIR__ . '/includes/file_move_log.php';   /* file movement log (inw_file_move_log) - same table as the main Start Work */
require_once __DIR__ . '/../includes/move_naming.php';
function rcopy($src, $dest, $accept_files_list, $suffix, $customer_id, $pub_id, $name_rules = array())
{
    if (!is_dir($src)) {
		fml_add('NOTE', 'FAILED', '', $src, $dest, 'The source folder does not exist or cannot be read, so nothing could be copied.');
		echo "<script>
		swal({
			title: 'Error',
			text: 'Source folder is not a directory',
			type: 'error',
			confirmButtonText: 'OK'
		});
		</script>";
		return false;
	}

    if (!is_dir($dest)) {
        error_clear_last();
        if (!@mkdir($dest, 0777, true)) {
            fml_add('NOTE', 'FAILED', '', $src, $dest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid'));
            return false;
        }
    }

    $dir = new DirectoryIterator($src);

    foreach ($dir as $file) {

        if ($file->isFile()) {

            $filename = $file->getFilename();
            $fileInfo = pathinfo($filename);

            $name = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? strtolower($fileInfo['extension']) : '';

            $fml_matched = false;
            foreach ($accept_files_list as $ext) {

                $ext = strtolower(trim($ext, '.'));

                if ($extension === $ext) {
                    $fml_matched = true;

                    // file naming rule configured for this extension (Move Files): skip files that do not match it
                    if (!empty($name_rules)) {
                        if (!mfn_file_allowed($filename, $name_rules, $pub_id)) {
                            fml_add('FILE', 'SKIPPED', $filename, $file->getRealPath(), $dest, 'Not copied: the file name does not match the naming rule for .' . $extension . ' (' . $name_rules[$extension] . ').', @filesize($file->getRealPath()));
                            continue; // skip unwanted files
                        }
                    }

                    $sfn = $file->getRealPath();

                    // Filename with suffix
                    if (!empty($suffix)) {
                        $new_filename = $name . "_" . strtolower($suffix) . "." . $extension;
                    } else {
                        $new_filename = $name . "." . $extension;
                    }

                    $dfn = rtrim($dest, "/") . "/" . $new_filename;

                    // 🚫 SKIP DUPLICATES FOR ALL (no counter, no overwrite)
                  /*   if (file_exists($dfn)) {
                        continue;
                    } */

                   /* source and destination are the same file: nothing to copy (copy() would fail without an error) */
                   $rs = @realpath($sfn); $rd = @realpath($dfn);
                   if ($rs !== false && $rd !== false && strcasecmp(str_replace(chr(92), '/', $rs), str_replace(chr(92), '/', $rd)) === 0) { fml_add('FILE', 'SKIPPED', $filename, $sfn, $dfn, 'Already in the destination folder (the source and destination are the same file).', @filesize($sfn)); continue; }
                   error_clear_last();
                   if (!@copy($sfn, $dfn)) {
						fml_add('FILE', 'FAILED', $filename, $sfn, $dfn, fml_why(), @filesize($sfn));
						echo "<script>
						swal({
							title: 'Copy Failed',
							text: 'Error copying file: $filename',
							type: 'error',
							confirmButtonText: 'OK'
						});
						</script>";
					} else {
                        $dt = filemtime($sfn);
                        if ($dt !== false) {
                            touch($dfn, $dt);
                        }
                        fml_add('FILE', 'MOVED', $new_filename, $sfn, $dfn, '', @filesize($dfn));
                    }
                }
            }
            if (!$fml_matched) { fml_add('FILE', 'SKIPPED', $filename, $file->getRealPath(), $dest, 'Not copied: the file type .' . ($extension !== '' ? $extension : '(none)') . ' is not selected for this destination (selected: ' . implode(', ', array_map(function ($x) { return '.' . trim($x, ' .'); }, $accept_files_list)) . ').', @filesize($file->getRealPath())); }
        } 
        else if (!$file->isDot() && $file->isDir()) {
            rcopy($file->getRealPath(), $dest, $accept_files_list, $suffix, $customer_id, $pub_id, $name_rules);
        }
    }
}
  
	 $allocate_check=mysqli_num_rows(mysqli_query($db,"select * from inw_inward_dtl WHERE id='".$_REQUEST['id']."' and allocate_to='".$_COOKIE['user_id']."'"));
	 if($allocate_check==0)
	 {
 					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Allocated Article Already Completed Successfully',
    type: 'danger',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/allocatedarticles';
});";
 echo "</script>";
 }

 
 
 
 if((isset($_REQUEST['btn_submit']) || (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['status']))) && !vp_files_blocked($db))
{
	
	/* a fatal error during the save would only reload the page with no message: show it instead */
	register_shutdown_function(function () {
		$e = error_get_last();
		if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true)) {
			$m = str_replace(array("'", chr(13), chr(10), chr(92)), array('', ' ', ' ', '/'), $e['message'] . ' (' . basename($e['file']) . ':' . $e['line'] . ')');
			error_log('start_work save fatal: ' . $m);
			echo "<script>swal({title: 'Save failed', text: '" . $m . "', type: 'error', confirmButtonText: 'OK'});</script>";
		}
	});
	$project_status_array =array(0=>'Inprogress',1=>'Completed',2=>'Pending',5=>'Need Clarification');

	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	
			 



	///////////////////////////////////////////////////////////////////////////////
	///// If Not Assign any department we will move the files to next department //
	///////////////////////////////////////////////////////////////////////////////
	
	//// Get the article details ////
	$query = "SELECT `cust_id`,`j_id` FROM `inw_inward_dtl` WHERE id = '".$post['id']."';";
	$articledetails = $db->query($query) or ($db->error);
	$artilce_info = $articledetails->fetch_assoc();
	
	//// Get the journal details ////
	$query = "SELECT `tvl_work_departments` FROM `adm_journals` WHERE j_id = '".$artilce_info['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	//// Here we will get the department's to work in TVL //
	$tvl_dept_info = explode(',',$journalinfo['tvl_work_departments']);	
	
	
		
	
		//// Get the workflow Steps ////
		$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
		$steps = $db->query($query) or ($db->error);
		$deptinfo = $steps->fetch_assoc();
		$dept_info = explode(',',$deptinfo['wf_code']);

		//// Re-fetch the article's current department from the DB instead of trusting
		//// the posted hidden field, which can be stale on a resubmit/double-submit and
		//// cause the next-department lookup below to skip a department ////
		$current_dept_result = $db->query("SELECT department_id FROM inw_inward_dtl WHERE id = ".intval($post['id'])." ");
		$current_dept_row = $current_dept_result->fetch_assoc();
		$current_department_id = $current_dept_row['department_id'];

		$dept_key = array_keys($dept_info, $current_department_id);

		$dept_key = $dept_key['0'];
		$dept_key = ($dept_key + 1);
		
		if($post['skip_dept'] != "" && $post['skip_dept'] > 0)
		{
			$department_id = $post['skip_dept'];
		}			
		else if($post['status'] == 1)
		{			
			$department_id = $dept_info[$dept_key];
		}
		else
		{
			$department_id = $post['department_id'];
		}
		
		/////// Get the department name /////
		$result = $db->query("SELECT dept_code,dept_name,accept_files FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
		$toatl_count=$result->num_rows;
		$dept_res = array();
		if($toatl_count>0)
		{
			$dept_res = $result->fetch_assoc();
		}			
			
		
		if($post['status'] == 1)
		{
	
				
	
	
		$fe_query = "SELECT mf_id FROM `inw_move_files` WHERE cust_id = '".$artilce_info['cust_id']."' AND j_id = '".$artilce_info['j_id']."' AND dept_id = '".$post['department_id']."';";
		$fe_result = $db->query($fe_query) or ($db->error);
		$fe_info = $fe_result->fetch_assoc();
	
		//// According to New configuration we are moving the files /////
		$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/";   // source folder or file
		
		$cur_stage = preg_replace('/[0-9]+/', '', $post['stage']);
		fml_ctx($db, array('inw_id' => (int)$post['id'], 'pub_id' => $post['pub_id'], 'cust_id' => (int)$artilce_info['cust_id'], 'stage' => $post['stage'], 'from_dept' => $post['dept_name'], 'to_dept' => '', 'src' => $src, 'counts' => array()));
		if (!$fe_info || empty($fe_info['mf_id'])) { fml_add('NOTE', 'SKIPPED', '', $src, '', 'No file movement is configured for this customer / journal / department, so no files were copied to the next department.'); }
		$fef_query = "SELECT move_files,move_name_rules,move_folders,dept_id FROM `inw_move_files_dept_wise` WHERE mf_id = '".$fe_info['mf_id']."' AND stage = '".$cur_stage."';";
		$fef_result = $db->query($fef_query) or ($db->error);
		if ($fe_info && !empty($fe_info['mf_id']) && $fef_result && $fef_result->num_rows == 0) { fml_add('NOTE', 'SKIPPED', '', $src, '', 'A file movement exists for this customer / journal / department, but it has no destination for stage ' . $cur_stage . ', so no files were copied to the next department.'); }
		while($fef_info = $fef_result->fetch_assoc())
		{
			/// Create the dept destination folder /////
			$dept_result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$fef_info['dept_id'].";");
			$dept_name = $dept_result->fetch_assoc();
			if (!$dept_name) { fml_ctx($db, array('to_dept' => '(unknown department ' . (int)$fef_info['dept_id'] . ')', 'types' => '')); fml_add('NOTE', 'FAILED', '', $src, '', 'The configured destination is not a department that exists, so nothing was copied.'); continue; }
			fml_ctx($db, array('to_dept' => $dept_name['dept_name'], 'types' => fml_types($fef_info['move_files'], $fef_info['move_folders'])));
			if ($fef_info['move_files'] == '' && $fef_info['move_folders'] == '') { fml_add('NOTE', 'SKIPPED', '', $src, '', 'This destination has no file types or folders selected in the file movement configuration.'); }
			elseif (!is_dir($src)) { fml_add('NOTE', 'FAILED', '', $src, '', 'The source folder does not exist, so nothing could be copied.'); }
			
			$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // 
			
			///// Moving Files ////////	
			if($fef_info['move_files']!='')
			{
				$accept_files_list = explode("|",$fef_info["move_files"]);
		$name_rules = mfn_parse_rules($fef_info["move_name_rules"]);   /* per-extension file naming rules; an extension without one moves every file */
				if(count($accept_files_list)>0)
				{
					if(!is_dir($dest))
					{
						///echo $dest.'<br>';
						error_clear_last();
						if (!@mkdir($dest, 0777, true)) { fml_add('NOTE', 'FAILED', '', $src, $dest, 'The destination folder could not be created: ' . fml_why('permission denied or the path is invalid')); }
					}
					rcopy($src, $dest, $accept_files_list, $suffix, $artilce_info['cust_id'], $post['pub_id'], $name_rules);
					if (is_dir($src) && fml_count('MOVED') + fml_count('SKIPPED') + fml_count('FAILED') == 0) { fml_add('NOTE', 'SKIPPED', '', $src, $dest, 'No file with the configured type(s) (' . implode(', ', array_map('trim', $accept_files_list)) . ') was found in the source folder.'); }
				}
				
			}
		}
	
	/// We update the page count when PRQC Team enters the DATA ////
		$page_count = '';
		if($post['page_count'] > 0)
		{
			$page_count = "page_count ='".$post['page_count']."',";
		}
		
		$worklocation = '';
		if(in_array($department_id, $tvl_dept_info))
		{

			$worklocation = "TVL";
		}
		
if($post['status'] == 1)
{
	
	$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>';
	if($dept_res['dept_name'] == 'Delivery')
	{
		$user_working_status = '';
		$graphic_working_status = ", graphic_working_status = ''";
	}
}
else if($post['status'] == 6)
{ 
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
else if($post['status'] == 7)
{
    $user_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
		
		 $prj_update_query="UPDATE inw_inward_dtl SET `allocate_to` = '0',vendor_completed_date='".date('Y-m-d H:i:s')."',vendor_wc='".$post['vendor_wc']."', `status` ='".$dept_res['dept_name']."', `department_id` ='".$dept_res['dept_code']."',user_working_status = '".$user_working_status."', last_transaction_id  = ".$post['tr_id'].",`assign_department_id` ='0', present_user_id = '0' ".$graphic_working_status." WHERE id = ".$post['id']." ";
			////// Update in Inward details table //////
			if(count($filecopy_err) == 0)
			{
				if (!$db->query($prj_update_query)) {
					/* do not report success when the article row was not updated */
					$upd_err = str_replace(array("'", chr(13), chr(10), chr(92)), array('', ' ', ' ', '/'), $db->error);
					error_log('start_work save failed: ' . $db->error . ' | ' . $prj_update_query);
					echo "<script>swal({title: 'Could not save the work status', text: '" . $upd_err . "', type: 'error', confirmButtonText: 'OK'});</script>";
					exit;
				}
				/* read the row back: if the saved values are not there, say so instead of reporting success */
				$chk_rs = $db->query("SELECT allocate_to, department_id, user_working_status FROM inw_inward_dtl WHERE id = " . intval($post['id']));
				$chk_row = $chk_rs ? $chk_rs->fetch_assoc() : null;
				if (!$chk_row || (string)$chk_row['allocate_to'] !== '0' || (string)$chk_row['user_working_status'] === '' && $post['status'] != 1 || ($post['status'] != 1 && stripos($chk_row['user_working_status'], $project_status_array[$post['status']]) === false && $post['status'] != 6 && $post['status'] != 7)) {
					$dbg = str_replace(array("'", chr(13), chr(10), chr(92)), array('', ' ', ' ', '/'), 'affected_rows=' . $db->affected_rows . ', id=' . intval($post['id']) . ', allocate_to=' . ($chk_row ? $chk_row['allocate_to'] : 'row not found') . ', status_label=' . ($chk_row ? strip_tags($chk_row['user_working_status']) : ''));
					error_log('start_work save not applied: ' . $dbg . ' | ' . $prj_update_query);
					echo "<script>swal({title: 'Status was not saved', text: '" . $dbg . "', type: 'error', confirmButtonText: 'OK'});</script>";
					exit;
				}
			}		 		
		}
		
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/allocatedarticles';
});";
 echo "</script>";

 


	$update_query=mysqli_query($db,"UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$_POST['status']]."',`completion_status` ='". $_POST['status']."' WHERE id = '".$post['tr_id']."' ");//
			//exit;

if($post['status'] == 1)
{
  @$body ="Dear Team,
<br/>
<br/>

I have finished editing and submitted the article ".$_REQUEST['pub_id'].". Please proceed accordingly.
<br/><br/>
Remarks : ".$_REQUEST['comments']."
<br/><br/>
Thank you.
<br/><br/>
Best regards,
<br/>
".$_COOKIE['user_name']."";
@$subject = "Article ".$_REQUEST['pub_id']." Completed by ".$_COOKIE['user_name']." ";	

		$mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $mail_pass;
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject =$subject;
        $mail->Body = $body;
		$to="tvlconversion@transforma.in,pc@transforma.in,coordinator@transforma.in,trans2@transforma.in,indesign@transforma.in,journalqc@transforma.in,art@transforma.in,jeyapriya@transforma.in";
		$cc="uma@transforma.in,inward@transforma.in,viji@transforma.in,ananthi@transforma.in,".$_COOKIE['user_email']."";
		$bcc="marimuthu@transforma.in";
		
		//$cc="pm@transforma.in,inward@transforma.in,indesign@transforma.in,abidha@transforma.in,journalqc@transforma.in,tvlce@transforma.in,art@transforma.in";
		//$bcc="marimuthu@transforma.in,swamy@transforma.in";
		
//// Send mail to more than one user ///
        $mail_to = explode(',', $to);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


        $mail->AltBody = ""; // optional, comment out and test

        $mail_tocc = explode(',', $cc);
        foreach ($mail_tocc as $email) {
            if ($email != '') {
                $mail->AddCC($email);
            }
        }

        $mail_tobcc = explode(',', $bcc);
        foreach ($mail_tobcc as $email) {
            if ($email != '') {
                $mail->AddBCC($email);
            }
        }

		 /* the notification mail must never stop the save: a mail server problem or a PHPMailer error is only logged */
		 try {
			 $mail->Timeout = 10;
			 $result = @$mail->Send();
			 if (!$result) { error_log('start_work Completed mail not sent: ' . $mail->ErrorInfo); }
		 } catch (Throwable $mail_ex) {
			 error_log('start_work Completed mail error: ' . $mail_ex->getMessage());
		 }
}


					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/allocatedarticles';
});";
 echo "</script>";
}
			
/////////////// Update completed ///////////////
?>


 
  </body>
</html>

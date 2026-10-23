<?php 
include ('includes/includes.php');
include_once 'includes/issue_drafts.php';

if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	$set_status = "`status` ='Client Review'";
	if($_POST['stage']=='FIN')
	{
		$set_status = "`status` ='Completed'";
	}
		
	$prj_update_query="UPDATE inw_volume_issues SET ".$set_status." WHERE vi_id = ".$post['id']." ";
		
	//echo $prj_update_query; exit;
	$db->query($prj_update_query);
	
///////////////////////////////////////////////////
/////// Upload the files to Server START HERE /////
///////////////////////////////////////////////////

// get FTP access parameters

if($_POST['optionRad']==1 || $_POST['optionRad']==2)
{

$current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);



$host = $_POST['FtpHost'];

$user = $_POST['FtpUser'];

$pass = $_POST['FtpPass'];

$destDir = $_POST['FtpPath'];

$workDir = "uploads"; // define this as per local system

// get temporary file name for the uploaded file

$tmpName = basename($_FILES['fileAttachment']['name']);

// copy uploaded file into current directory
if($_FILES['fileAttachment']['name'] !="")
{
move_uploaded_file($_FILES['fileAttachment']['tmp_name'], $workDir."/".$tmpName) or idr_fail('dispath_issues.php', '', 'The uploaded file could not be moved to the working directory.');
}

// open connection

$conn = ftp_connect($host) or idr_fail('dispath_issues.php', '', 'Cannot connect to the FTP host. Please check the connection settings and try again.');

// send access parameters

ftp_login($conn, $user, $pass) or idr_fail('dispath_issues.php', '', 'FTP login failed. Please check the user name and password for this customer.');


///// Create DIR ///
if($current_stage =='FP')
{
	$destDir .= "/Firstproofs/";
}
else if($current_stage =='REV')
{
	$destDir .= "/Revises/";	
}
else
{
	$destDir .= "/".$current_stage."/";	
}

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}

///// Create Month and Year DIR ///

$destDir .= date('Y')."/";		

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}

///// Create Month and DATE DIR ///

$destDir .= date('md');	

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}
////// END Create DIR ////
$email_ftp_path ='';

// perform file upload
if($_FILES['fileAttachment']['name'] !="")
{
	$upload = ftp_put($conn, $destDir."/".$_FILES['fileAttachment']['name'], $workDir."/".$tmpName, FTP_BINARY);
	
	$email_ftp_path .= "ftp://".$user."@".$host.$destDir."/".$_FILES['fileAttachment']['name']."<br>";	
	// delete local copy of uploaded file
	unlink($workDir."/".$tmpName) or idr_fail('dispath_issues.php', '', 'The file was sent, but the temporary uploaded copy could not be deleted - please delete it manually.');
}

foreach($_POST['attach_files'] as $file)
{
	$upload = ftp_put($conn, $destDir."/".$file, $_POST['work_dir']."/".$file, FTP_BINARY);
	
	$email_ftp_path .= "ftp://".$user."@".$host.$destDir."/".$file."<br>";
	
}

//var_dump($upload);

// check upload status

// display message

if (!$upload) {

    echo "Cannot upload to FTP";

} else {

    echo "Upload complete";

}


// close the FTP stream

ftp_close($conn);




}

//exit;
////////////////////////////////////////
////// End files upload to server //////
////////////////////////////////////////			
			
			
////// send mail to clinet with attachment ////
/* $smtp_host 		= "ssl://smtp.gmail.com";
$smtp_user 		= "trans.robatic.msg@gmail.com";
$smtp_password 	= "Technet16";
$smtp_port 		= "465";
$smtp_ssl 		= "true"; 

$smtp_host 		= "smtp.office365.com";
$smtp_port 		= "587";
$smtp_ssl 		= "false";
$smtp_user 		= $_POST['txtFrom'];
$smtp_password 	= $_POST['txtMpass']; */

if($_POST['optionRad']==1 || $_POST['optionRad']==3 || $_POST['optionRad']==4)
{
	
$mail_from 		= $_POST['txtFrom'] ;
$mail_from_name	= "";


$mail_to_name 	= "";

date_default_timezone_set('Asia/Kolkata');

$body = str_replace('ARTICLEID',$_POST['article_id'],$_POST['txtBody']);
$body = str_replace('CLIENTNAME',$_POST['cust_name'],$body);
$body = str_replace('FTPFILEPATH',$email_ftp_path,$body);

	$querysrc = $_POST['work_dir'];
	$querystr = "";
	$i = new DirectoryIterator($querysrc);
    foreach($i as $f) {
        if($f->isFile()) {
           
			$filename = $f->getFilename();
			if (strpos($filename, '_Query.txt') !== false) 
			{
					$file = fopen($querysrc.$filename,"r");
					while(! feof($file))
					{
						$querystr .= str_replace("\n","<br/>", fgets($file));
						
					}
					$querystr = "---------- Query ----------<br/>".$querystr."<br/><br/>---------------------------<br/>";
				break;
			}

		}
	}
	
	$body = str_replace('AUTHORQUERY',$querystr,$body);
//exit;




include("smtpmail/phpmailer/class.phpmailer.php");

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $_POST['txtMpass'];
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);
$mail->Subject = str_replace('ARTICLEID',$_POST['article_id'],$_POST['txtSub']);
$mail->Body = $body;

//// Send mail to more than one user ///
$mail_to 		= explode(',',$_POST['txtTo']);
foreach($mail_to as $email)
{
	if($email!='')
	{
		$mail->addAddress($email);
	}
}
/////////// End here send mail ////////


$mail->AltBody    = ""; // optional, comment out and test

$mail_tocc 		= explode(',',$_POST['txtCc']);
foreach($mail_tocc as $email)
{
	if($email!='')
	{
		$mail->AddCC($email);
	}
}

$mail_tobcc 		= explode(',',$_POST['txtBcc']);
foreach($mail_tobcc as $email)
{
	if($email!='')
	{
		$mail->AddBCC($email);
	}
}

		if($_POST['optionRad']==3 || $_POST['optionRad']==4)
		{
			// $mail->AddAttachment($_POST['work_dir']."Rouzies Onyemah final draft (2).pdf");      // attachment
			if($_FILES['fileAttachment']['name'] !="")
			{
				$mail->AddAttachment($_FILES['fileAttachment']['tmp_name'],$_FILES['fileAttachment']['name']);
			}
			//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
			foreach($_POST['attach_files'] as $file)
			{
				$mail->AddAttachment($_POST['work_dir'].$file);
			}
		}

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
   echo "Message sent!";
}
	//exit;
}

if($_POST['optionRad']==5)
{
	include ('linux_iap_client_portal_issue.php');

}

	header("location:dispath_issues.php?msg=Successfully completed"); 
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];
	}

/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
	/// Get Email Subject and Body ///
	$query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = ".$app_res['j_id']." AND cust_id=".$app_res['cust_id']." AND stage ='".preg_replace('/[0-9]+/', '', $app_res['stage'])."';";
	$d_mail = $db->query($query) or ($db->error);
	$d_mail_info = $d_mail->fetch_assoc();
	
	
}

//echo $transaction_id;
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    
    <!-- Data Table CSS- -->
    
    <!-- Data Table CSS --->
     <style>
      .tdstyle{
        width: 30%;
      }
      .ftpstyle{
        width: 100%;
      }
      .form-group{
        display: flex;
        width: 90%;
      }
      .form-control{
        width: 50%;
      }
      .form_outer{
        width: 95%;
        padding: 50px 20px;
        display: flex;
        justify-content: center;
        background-color: #fff3ed;

      }
      .form_inner{
        width: 95%;
        background-color: #fff;
        padding: 20px;
		position: relative;
						   
      }
     .divouter{
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px;
     }
     .radio_btn{
      margin: 30px 0;
      height: 30px;
     }
	 .control-label {
    color: #000 !important;
}
    </style>
    </head>
    
    <body>
    <!-- Header logo section -->
   

			  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>

 <div class="col-8 col-lg-10">
          <p class="articleheading ms-4">Working on <?php echo VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];?>
        
          <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?></p>
        </div>
    <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
		   
            <p class="articleheading ms-4">Issue Dispatch</p>
          </div>
		  
		  <div class="col-4 col-lg-2">
      <div class="row">
        <div class="col-3 articlebtns d-flex justify-content-end">
          <a href="javascript:history.back();">
            <div class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
        </div>
      </div>
	  <?php 
	  if(isset($_REQUEST['msg']))
	  {
	  ?>
	  
			<div style="width: 100%; display: flex; justify-content: center; ">
        <span style="width:fit-content; height: 30px; color: #fff; background:#ed424a; border-radius: 15px; display: flex; justify-content: center; align-items: center; padding: 5px 10px;"><?php echo $_REQUEST['msg'];?></span>
      </div>
	  <?php 
	  }
	  ?>
              
						 <div class="divouter mt-4">
        <div class="form_outer">
          <div class="form_inner d-flex flex-column align-items-center">
         <div class="container" id="widget-box-2">
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 class="widget-title bigger lighter" style="font-size: 1.275em">
              <i class="ace-icon fa fa-comments"></i>
              Issue Information
            </h3>            
          </div>

          <div class="widget-body pt-5">
            <div class="widget-main no-padding pt-4">
              <table class="table table-bordered table-hover">
                <tbody style="border: 1px solid #dedef3;">
                  <tr>
                    <td class="tdstyle">Customer Name</td>
                    <td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Journal</td>
                    <td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Stage</td>
                    <td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
                  </tr>
                  
                  <tr>
                    <td class="tdstyle">Received Date</td>
                    <td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['received_date']))); ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Due Date</td>
                    <td class="hidden-480"> <?php
if($app_res['stage']=='FP')
{
echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
}
else
{
echo (date('d-m-Y',strtotime($app_res['due_date'])));
}
?></td>
                  </tr>
				 
                </tbody>
              </table>
            </div>
          </div>
        </div
      </div>

  <div class="container">
           <form method="post" enctype="multipart/form-data">

      <div class="container radio_btn">
        <div class="form-group d-flex">
          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Choose Dispatch Method :</label>
          <div class="col-xs-12 col-sm-12 d-flex gap-3">
              <input id="optionRad_0" name="optionRad" value="1"  type="radio" checked="checked">
              <label for="optionRad_0">FTP upload and mail</label>
              </td>
              <td><input id="optionRad_1" name="optionRad" value="4"  type="radio">
                  <label for="optionRad_1">xProof upload &amp; mail</label></td>
              <td><input id="optionRad_2" name="optionRad" value="2" type="radio">
                  <label for="optionRad_2">Only FTP &amp; manual mail</label></td>
              <td><input id="optionRad_3" name="optionRad" value="3" type="radio">
                  <label for="optionRad_3">Attach file &amp; mail</label></td>
				<td><input id="optionRad_4" name="optionRad" value="5" type="radio">
                  <label for="optionRad_4">Xperttrack file</label></td>             
          </div>
		  
		  
      </div>
    </div>
					 <div id="div1"> 
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
              <h3 style="font-size: 1.275em"> FTP </h3>
          </div>
          <div class="container mt-4 mb-4 pt-5">
          <div class="row pt-5">
              <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Site URL</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpHost" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_host']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex ">
                      <label class="col-sm-3 control-label no-padding-right">Target DIR</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPath" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                          Note: No slash end of the path </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">User</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpUser" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Password</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPass" type="password"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
						<div  id="emaildiv">
                         <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 style="font-size: 1.275em"> E-Mail </h3>
        </div>
                      <div class="container mt-4 mb-4 pt-5">
        <div class="row pt-5">
            <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                          <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">From</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtFrom" type="email"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email']; ?>"/>
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">Password</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtMpass" type="password"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">To</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtTo" type="text"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_to_email']; ?>"/>Note- Add the EMail ids with comma seperated.
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">CC</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtCc" type="text"  class="col-sm-6 form-control form-control" value="<?php echo $journal_info['j_cc_email']; ?>"/>Note:- Add the EMail ids with comma seperated.
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">BCC</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtBcc" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['j_bcc_email']; ?>" />Note:- Add the EMail ids with comma seperated.
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">Subject</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $d_mail_info['subject']?>"/>
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">Message</label>
                             <div class="col-sm-9 d-flex gap-2">
                              <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $d_mail_info['message']?></textarea>
                              </div>
                            </div>
                            <div class="form-group d-flex">
                              <label class="col-sm-3 control-label no-padding-right">Attachment</label>
                             <div class="col-sm-9 d-flex gap-2">
                                <input name="fileAttachment" type="file"  class="col-sm-6 form-control " />
                              </div>
                            </div>
                          </div>
                          </div></div></div>
                          <div class="container mt-4 mb-4">
         <div class="form-group d-flex">
                          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Work DIR:</label>
                          <div class="col-xs-12 col-sm-9">
                            <div class="clearfix"> <a class="btn btn-primary" href="file:///<?php echo $app_res['dispatch_dir']; ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125"></i> View</a>  </div>
                          </div>
                        </div>
                        <div class="container mt-4 mb-4">
           <div class="form-group d-flex">
												<label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Check the files to send</label>
												<?php
								// Open the source directory to read in files
								$src = $app_res['dispatch_dir'];
								if(is_dir($src))
								{
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            $filename = $f->getFilename();
			?>
			<div class="checkbox col-xs-12 col-sm-9">
													<label>
													<?php
														if (strpos($filename, '.zip') !== false) 
														{
													?>
															<input name="attach_files[]" class="ace ace-checkbox-2" value="<?php echo  $filename; ?>" type="checkbox" checked="checked"/>
													<?php
														}
														else	
														{
													?>
															<input name="attach_files[]" class="ace ace-checkbox-2" value="<?php echo  $filename; ?>" type="checkbox" />
													<?php
														}
														
													?>
														
														<span class="lbl"> <?php echo  $filename; ?> </span>
													</label>
												</div>
												<?php
			
			
        } 
		
    }
	}
								?>
												
												</div>
									</div>
												
                         <div class="container mt-5 mb-5">
          <div class="form-group d-flex justify-content-center">
            <div class="col-xs-12 d-flex gap-3">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" /><input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />                         
                            
                            <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                            
                                                    
                           
                            <input type="hidden" name="work_dir" value="<?php echo $app_res['dispatch_dir']; ?>">  

								
                            
                            <a href="dispath_issues.php" class="btn btn-grey">Cancel</a>
                            <button type="submit" value="Save" class="btn btn-info">Save</button>
                          </div>
						   </div>
                        </div>
                      </form>
                   </div>
      </div>
      </div>
    </div>
</div>                                       
  <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
</div>

     



<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});		
				$('#txtBody').richText();//this will create the default editor will all buttons	
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
	
				/***************/
				$('.show-details-btn').on('click', function(e) {
					e.preventDefault();
					$( "#message-content" ).toggle( "slow");
					//add scrollbars to .message-body
						$('.message-content .message-body').ace_scroll({
							size: 150,
							mouseWheelLock: true,
							styleClass: 'scroll-visible'
						});
					$(this).find(ace.vars['.icon']).toggleClass('fa-angle-double-down').toggleClass('fa-angle-double-up');
				});
				/***************/
				
	
	})
	
		</script>
		<script type="text/javascript">
		$(function(){
			
			 <?php 
			 if ($customer_info['id'] == 57 || $customer_info['id'] == 42) 
			 {
			 ?>
              $("input[id$='optionRad_4']").click();
			  $("#div1").hide();
			  $("#emaildiv").show();
			 <?php 
			 }
			 ?>

			$("input[id$='optionRad_0']").click(function(){
			var re=$(this).val();
			$("#div1,#emaildiv").show();
		});
		
		
		$("input[id$='optionRad_1']").click(function(){
			var tog=$(this).val();
			$("#div1").hide();
			$("#emaildiv").show();
		});
		
		
		$("input[id$='optionRad_2']").click(function(){
			var mail=$(this).val();
			$("#emaildiv").hide();
			$("#div1").show();
		});
		
		$("input[id$='optionRad_3']").click(function(){
			var mail=$(this).val();
			$("#div1").hide();
			$("#emaildiv").show();
		});
		$("input[id$='optionRad_4']").click(function(){
			var tog=$(this).val();
			$("#div1").hide();
			$("#emaildiv").show();
		});
		});
		
		
		
		
		
		
		</script>
</body>
</html>

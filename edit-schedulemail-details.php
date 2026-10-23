<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	
	// Snapshot before change (for change-log email)
	$__old = change_log_snapshot('adm_schedule_mail_details', 'id', $post['id']);
	$update_query="UPDATE adm_schedule_mail_details SET `type` ='". $post['type']."', `from_email` ='". $post['from_email']."',from_email_pwd ='". $post['from_email_pwd']."',to_email ='". $post['to_email']."',`cc_email` ='". $post['cc_email']."',`bcc_email` ='". $post['bcc_email']."',`reply_to` ='". $post['reply_to']."',`reply_name` ='". $post['reply_name']."' WHERE id = ".$post['id']." ";	
	if($db->query($update_query))
	{
		log_row_change('Schedule Mail Details', $post['id'], $__old, array('type'=>$post['type'],'from_email'=>$post['from_email'],'from_email_pwd'=>$post['from_email_pwd'],'to_email'=>$post['to_email'],'cc_email'=>$post['cc_email'],'bcc_email'=>$post['bcc_email'],'reply_to'=>$post['reply_to'],'reply_name'=>$post['reply_name']), array('from_email_pwd'));
		header("location:".$_COOKIE['set_url']."/schedulemaildetails?msg=Successfully completed"); 
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM adm_schedule_mail_details WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}

/// Get department status if assigned to any workflow or not ////
$wf_status=0;
$sql = "SELECT wf_code FROM adm_workflow_master";
	$result = $db->query($sql);
	if($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			$wf_code =   explode(",",$row["wf_code"]);
			if (in_array($_REQUEST['id'], $wf_code))
			{
				$wf_status = 1;
				break;
			}
			else
			{
				$wf_status = 0;
			}
  		}
	}
	else
	{
		$wf_status = 0;
	}
	// echo $status;
	

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('edit-semail'); ?>	

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
  <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Edit Schedule Mail Details</p>
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

      <?php fu_hero(fu_ui_basic('Edit Schedule Mail Details', 'Edit Schedule Mail Details &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit-semail" id="edit-semail" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Edit Schedule Mail Details</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Type<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="type" id="type"  value="<?php echo $app_res['type']; ?>" placeholder="Enter type" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">From E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="from_email" id="from_email"  value="<?php echo $app_res['from_email']; ?>" placeholder="Enter e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">From E-Mail Password<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="from_email_pwd" id="from_email_pwd"  value="<?php echo $app_res['from_email_pwd']; ?>" placeholder="Enter e-mail password" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">To E-mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="to_email" id="to_email"  value="<?php echo $app_res['to_email']; ?>" placeholder="Enter to e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">CC E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="cc_email" id="cc_email"  value="<?php echo $app_res['cc_email']; ?>" placeholder="Enter cc e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">BCC E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="bcc_email" id="bcc_email"  value="<?php echo $app_res['bcc_email']; ?>" placeholder="Enter bcc e-mail" required>
              </input>
                </div>
                </div>
            </div>
            <div class="container mt-4">
              <div class="formcont1heading">Replay</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Reply To<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="reply_to" id="reply_to"  value="<?php echo $app_res['reply_to']; ?>" placeholder="Enter to replay" required>
              </input>
                </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/schedulemaildetails" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
                    </div>
                </div>
            </div>
        </div>
      </div>
	   </form>
	   </div>
	   </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     </div>
 <?php fu_foot('#edit-semail', null, array(), null, fu_ui_basic('Edit Schedule Mail Details', 'Edit Schedule Mail Details &rsaquo; Edit')); ?>
  </body>
</html>

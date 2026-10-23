<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	$insert_query = "INSERT INTO `adm_schedule_mail_details` (`type`, `from_email`, `from_email_pwd`,to_email, cc_email, bcc_email, reply_to, reply_name,`created_by`, `created_on`) VALUES ('".$post['type']."', '".$post['from_email']."', '".$post['from_email_pwd']."', '".$post['to_email']."', '".$post['cc_email']."', '".$post['bcc_email']."', '".$post['reply_to']."', '".$post['reply_name']."','".$_COOKIE['user_id']."', NOW());";	

		$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Schedule Mail Details', change_log_ref(array('type','from_email')), array_diff_key($_POST, array('action'=>1)), array('from_email_pwd'));
		header("location:".$_COOKIE['set_url']."/schedulemaildetails?msg=Successfully added"); 
		die();	
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
    <?php fu_head('add-smails'); ?>	

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
            <p class="articleheading ms-4">Add  Schedule Mail Details</p>
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


      <?php fu_hero(fu_ui_basic('Add  Schedule Mail Details', 'Add  Schedule Mail Details &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-smails" id="add-smails" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add Schedule Mail Details</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Type<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="type" id="type"  value="" placeholder="Enter type" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">From E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="from_email" id="from_email"  value="" placeholder="Enter e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">From E-Mail Password<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="from_email_pwd" id="from_email_pwd"  value="" placeholder="Enter e-mail password" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">To E-mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="to_email" id="to_email"  value="" placeholder="Enter to e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">CC E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="cc_email" id="cc_email"  value="" placeholder="Enter cc e-mail" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">BCC E-Mail<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="bcc_email" id="bcc_email"  value="" placeholder="Enter bcc e-mail" required>
              </input>
                </div>
                </div>
            </div>
            <div class="container mt-4">
              <div class="formcont1heading">Replay</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Reply To<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="reply_to" id="reply_to"  value="" placeholder="Enter to replay" required>
              </input>
                </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-2" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/schedulemaildetails" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="insert">

                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
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
 <?php fu_foot('#add-smails', null, array(), null, fu_ui_basic('Add  Schedule Mail Details', 'Add  Schedule Mail Details &rsaquo; Add')); ?>
  </body>
</html>

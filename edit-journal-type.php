<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php 


if(isset($_POST['action']) && $_POST['action']=='update')
{
	

	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	
	
$jt_pdf=$_FILES['jt_pdf']['name'];
	$upload ="uploads/journal-document-type/".$post['jt_name']."/";
	if(!is_dir($upload))
	{
		mkdir($upload, 0700);
	}

	if($jt_pdf!="")
	{
		$string = $jt_pdf;
		$string = preg_replace('~[/\\\\][\w-]*$~', '-', $string);
		$pos=strrpos($string,'.');
		$mainext=substr($string,($pos+1));
		$jt_pdf='jdt'.'_'.$_REQUEST['id'].'.'.$mainext;
		move_uploaded_file($_FILES['jt_pdf']['tmp_name'],$upload.$jt_pdf);
	}
	else
	{
		$s=mysqli_query($db,"select * from journal_type where jt_id='".$_REQUEST['id']."'")or die(mysqli_error());
		$n=mysqli_fetch_array($s);
			if($n['jt_pdf'] != "")
				$jt_pdf=$n['jt_pdf'];
			else
				$jt_pdf="";
	}

	//echo "UPDATE `journal_type` SET `jt_name`='". mysqli_real_escape_string($db,$post['jt_name'])."', jt_pdf='". $jt_pdf."' WHERE jt_id='".$_REQUEST['id']."'";exit;
	// Snapshot the existing values (before change) for the change-log email
	$log_jt_id = (int) $_REQUEST['id'];
	$res_old_jt = mysqli_query($db, "SELECT * FROM journal_type WHERE jt_id = ".$log_jt_id);
	$old_jt = ($res_old_jt && mysqli_num_rows($res_old_jt) > 0) ? mysqli_fetch_assoc($res_old_jt) : array();

	$update_query = mysqli_query($db,"UPDATE `journal_type` SET `jt_name`='". mysqli_real_escape_string($db,$post['jt_name'])."', jt_pdf='". $jt_pdf."' WHERE jt_id='".$_REQUEST['id']."'");	


		// Email the change log (text attachment); a mail failure must not block the save
		if ($update_query) {
			try {
				$new_jt = array('jt_name' => $post['jt_name'], 'jt_pdf' => $jt_pdf);
				$jt_log = "[Journal type changes]\r\n";
				$jt_changes = 0;
				foreach ($new_jt as $f => $new) {
					$old = isset($old_jt[$f]) ? (string) $old_jt[$f] : '';
					if ($old !== (string) $new) {
						$jt_log .= "  $f: '$old' -> '$new'\r\n";
						$jt_changes++;
					}
				}
				if ($jt_changes == 0) {
					$jt_log .= "  (no change)\r\n";
				}
				send_change_log_mail('Journal Type', $log_jt_id, $jt_log, $jt_changes);
			} catch (Exception $e) {
				error_log("edit-journal-type change-log mail failed: " . $e->getMessage());
			}
		}

		header("location:".$_COOKIE['set_url']."/journaltype?msg=Successfully added");
		die();	
			
	//print_r($_POST); exit;
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM journal_type WHERE jt_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}


	
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
    <?php fu_head('edit_journal_type'); ?>	

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
            <p class="articleheading ms-4">Edit Journal Type</p>
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

      <?php fu_hero(fu_ui_basic('Edit Journal Type', 'Edit Journal Type &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit_journal_type" id="edit_journal_type" enctype="multipart/form-data">
            <div class="container mt-4">
              <div class="formcont1heading">Edit Journal Type</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Title<span style="color: red">*</span></label>
                <input type="text" class="form-control" name="jt_name" id="jt_name"  value="<?php echo $app_res['jt_name'];?>" placeholder="Enter chechlist text" required>
              </input>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Upload file<span style="color: red">*</span></label>
                <input type="file" class="form-control" name="jt_pdf" id="jt_pdf"    required>
              </input>
                </div>
                </div>
            </div>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />

            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/journaltype" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
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
 
  <?php fu_foot('#edit_journal_type', null, array(), null, fu_ui_basic('Edit Journal Type', 'Edit Journal Type &rsaquo; Edit')); ?>
  </body>
</html>

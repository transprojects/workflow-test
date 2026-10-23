<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php 

if(isset($_POST['action']) && $_POST['action']=='insert')
{
	

	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	

	$query=mysqli_query($db,"select max(jt_id) as id from journal_type") or die(mysql_error());
	$result=mysqli_fetch_array($query);
	$ans=$result['id']+1;

		
	// current directory
	//echo getcwd() . "\n";
	
	
	 $upload ="uploads/journal-document-type/".$post['jt_name']."/";
	if(!is_dir($upload))
	{
		mkdir($upload, 0700);
	}	
	$jt_pdf=$_FILES['jt_pdf']['name'];
	if($jt_pdf!="")
	{
		$string = $jt_pdf;
		$string = preg_replace('~[/\\\\][\w-]*$~', '-', $string);
		$pos=strrpos($string,'.');
		$mainext=substr($string,($pos+1));
		$jt_pdf='jdt_'.'_'.$ans.'.'.$mainext;
		move_uploaded_file($_FILES['jt_pdf']['tmp_name'],$upload.$jt_pdf);
	}


	$insert_query = "INSERT INTO `journal_type` (`jt_name`, `jt_pdf`) VALUES ('". mysqli_real_escape_string($db,$post['jt_name'])."', '". $jt_pdf."');";	

		$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Journal Type', change_log_ref(array('jt_name')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/journaltype?msg=Successfully added"); 
		die();	
			
	//print_r($_POST); exit;
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
    <?php fu_head('add_journal_type'); ?>	

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
            <p class="articleheading ms-4">Add Journal Type</p>
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

      <?php fu_hero(fu_ui_basic('Add Journal Type', 'Add Journal Type &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_journal_type" id="add_journal_type" enctype="multipart/form-data">
            <div class="container mt-4">
              <div class="formcont1heading">Add New</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Title<span style="color: red">*</span></label>
                <input type="text" class="form-control" name="jt_name" id="jt_name"  value="" placeholder="Enter Journal Type" required>
              </input>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Upload file<span style="color: red">*</span></label>
                <input type="file" class="form-control" name="jt_pdf" id="jt_pdf">
              </input>
                </div>
                </div>
            </div>
            <input type="hidden" name="action" value="insert">
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
  <?php fu_foot('#add_journal_type', null, array(), null, fu_ui_basic('Add Journal Type', 'Add Journal Type &rsaquo; Add')); ?>
  </body>
</html>

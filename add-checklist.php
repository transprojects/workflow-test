<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
require_once __DIR__.'/includes/clone_prefill.php';
$clone_src = clone_row($db, 'jbm_checklist', 'cl_id');
$is_clone = isset($_REQUEST['clone']);
$clone_vals = $clone_src ? array_intersect_key($clone_src, array_flip(array('dept_id', 'stage', 'description', 'display_order'))) : null;
$page_title = $is_clone ? 'Clone Checklist' : 'Add Checklist';
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	

	$insert_query = "INSERT INTO `jbm_checklist` (`stage`, `description`, `dept_id`,display_order,`created_by`, `created_on`) VALUES ('".$post['stage']."', '".$post['description']."', '".$post['dept_id']."', '".$post['display_order']."','".$_COOKIE['user_id']."', NOW());";	

		if ($is_clone) {
				$dup = $db->query("SELECT cl_id FROM jbm_checklist WHERE dept_id = '".$db->real_escape_string($post['dept_id'])."' AND stage = '".$db->real_escape_string($post['stage'])."' AND description = '".$db->real_escape_string($post['description'])."' LIMIT 1");
				if ($dup && $dup->num_rows > 0) {
					echo "<script>alert('This checklist item already exists for the department and stage. Change one of them to save the clone.'); history.back();</script>";
					die();
				}
			}
			$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Checklist', change_log_ref(array('description')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/checklist?msg=Successfully added"); 
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
    <?php fu_head('add-checklist'); ?>	

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
            <p class="articleheading ms-4"><?php echo $page_title; ?></p>
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
	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
      <?php fu_hero(fu_ui_basic($page_title, $page_title.' &rsaquo; '.($is_clone ? 'Clone' : 'Add'))); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-checklist" id="add-checklist" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading"><?php echo $page_title; ?></div>
              <div class="row mt-1 g-4 ">
              <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Department<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="dept_id" id="dept_id" required>
                    <option value="">Select department</option>
                    <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>"><?php echo $row['dept_name']; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Stage<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="stage" id="stage" required>
                    <option value="">Select stage</option>
                                  <?php foreach($project_status_types as $st_id=>$st_name){ ?>
                                  <option value="<?php echo htmlspecialchars($st_id); ?>"><?php echo htmlspecialchars($st_id); ?></option>
                                  <?php } ?>
                                  <option value="BOTH">BOTH</option>
                </select>
                </div>
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Checklist text<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="description" id="description"  value="" placeholder="Enter chechlist text" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Checklist Order<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="display_order" id="display_order"  value="" placeholder="Enter order list" required>
              </input>
                </div>
                </div>
            </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/checklist" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="insert">

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
     
 
  <?php fu_foot('#add-checklist', null, array(), null, fu_ui_basic($page_title, $page_title.' &rsaquo; '.($is_clone ? 'Clone' : 'Add'))); ?>
<?php clone_prefill($clone_vals); ?>
  </body>
</html>

<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
/// Add New To-Do Task
if (isset($_POST['action']) && $_POST['action'] == 'insert') { 
	foreach ($_POST as $key => $value) {
		$post[$key] = trim($value);
	}	

	$role = $post['user_level'];
	$task_description = $post['task_description'];

	$insert_query = "INSERT INTO `todo_templates` 
		(`role`, `task_description`) 
		VALUES 
		('".$db->real_escape_string($role)."', '".$db->real_escape_string($task_description)."')";

	$db->query($insert_query) or die($db->error);

	// Change-log entry + email for the new record
	log_row_added('Todo Template', change_log_ref(array('title','task','description')), array_diff_key($_POST, array('action'=>1)), array());
	header("location:".$_COOKIE['set_url']."/todotemplates?msg=Template successfully added"); 
	die();	
}
$customers = $db->query("SELECT user_role_id, user_role_name FROM user_role_master");

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('add-task'); ?>	

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
  <div class="row pt-4 d-flex align-items-center flex-wrap">
    <div class="col-8 col-lg-10">
      <p class="articleheading ms-4">Add To-Do Template</p>
    </div>

    <div class="col-4 col-lg-2">
      <div class="row d-flex justify-content-end pe-2">
        <div class="col-3 articlebtns">
          <a href="javascript:history.back();">
            <div class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php fu_hero(fu_ui_basic('Add To-Do Template', 'Add To-Do Template &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
  <div class="container addarticleforminner m-5">
    <form action="" method="post" name="add-task" id="add-task" enctype="multipart/form-data">
      <div class="container mt-4">
        <div class="formcont1heading">Add To-Do Template</div>
        <div class="row mt-1 g-4 addspacing">

<div class="col-12 col-sm-6 col-lg-4">
	<label class="mb-2">Role<span style="color: red">*</span></label>
		<select class="form-select" name="user_level" id="user_level" required>
		<option value="">Select Role</option>
		<?php while($row = $customers->fetch_assoc()) { ?>
		<option value="<?php echo $row['user_role_id']; ?>">
		<?php echo $row['user_role_name']; ?>
		</option>
		<?php } ?>
		</select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
  <label class="mb-2">Template Description<span style="color: red">*</span></label>
  <input type="text" class="form-control" name="task_description" id="task_description" placeholder="Enter your Template" required>
</div>

        

        </div>
      </div>

      <div class="meta_nxtbtn container-fluid mt-5 mb-5">
        <div class="row">
          <div class="col form_btn cancelbtn d-flex justify-content-end">
            <button type="button" class="btn-next-cancel">
              <a href="<?php echo $_COOKIE['set_url'];?>/todotemplates" style="text-decoration:none;">Cancel</a>
            </button>
          </div>

          <input type="hidden" name="action" value="insert">

          <div class="col form_btn nxtbtn">
            <button type="submit" class="btn-next">Save</button>
          </div>
        </div>
      </div>
    </form>


        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 
  <?php fu_foot('#add-task', null, array(), null, fu_ui_basic('Add To-Do Template', 'Add To-Do Template &rsaquo; Add')); ?>
  </body>
</html>

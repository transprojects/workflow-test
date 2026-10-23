<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php
include_once "includes/change_log_mail.php";

if(isset($_POST['action']) && $_POST['action']=='update')
{
	//print_r($_POST);
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	
	/// Snapshot the existing values (before change) for the change-log email ///
	$log_wf_id = (int) $post['id'];
	$res_old = $db->query("SELECT * FROM adm_workflow_master WHERE wf_id = ".$log_wf_id);
	$old_wf = ($res_old && $res_old->num_rows > 0) ? $res_old->fetch_assoc() : array();

	 $update_query="UPDATE adm_workflow_master SET `wf_code` ='". implode(',', $post['departments'])."',`wf_name` ='". $post['wf_name']."',`w_cust_id`='".$post['w_cust_id']."' WHERE wf_id = ".$post['id']." ";
	
	if($db->query($update_query))
	{
		/// Email the change log (text attachment); a mail failure must not block the save ///
		try {
			$new_wf = array(
				'wf_name'   => $post['wf_name'],
				'w_cust_id' => $post['w_cust_id'],
				'wf_code'   => implode(',', $post['departments'])
			);
			$wf_labels = array('wf_name' => 'Workflow Name', 'w_cust_id' => 'Customer', 'wf_code' => 'Workflow steps (dept codes)');
			$wf_log = "[Workflow changes]\r\n";
			$wf_changes = 0;
			foreach ($wf_labels as $f => $label) {
				$old = isset($old_wf[$f]) ? $old_wf[$f] : '';
				if ((string) $old !== (string) $new_wf[$f]) {
					$wf_log .= "  $label ($f): '$old' -> '".$new_wf[$f]."'\r\n";
					$wf_changes++;
				}
			}
			if ($wf_changes == 0) {
				$wf_log .= "  (no change)\r\n";
			}
			send_change_log_mail('Workflow', $log_wf_id, $wf_log, $wf_changes);
		} catch (Exception $e) {
			error_log("edit-workflow change-log mail failed: " . $e->getMessage());
		}
		header("location:".$_COOKIE['set_url']."/workflow?msg=Successfully completed"); 
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
	$result = $db->query("SELECT * FROM adm_workflow_master WHERE wf_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}
$exist_departments = explode(',',$app_res['wf_code']);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('edit_workflow'); fu_multiselect_css(); ?>	

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
.ui-helper-hidden { display: block !important; }
.ui-state-default ui-element { display: block !important; }
.ui-icon { display: block; text-indent: -99999px; overflow: hidden; background-repeat: no-repeat; }

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
            <p class="articleheading ms-4">Edit Workflow</p>
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

      <?php fu_hero(fu_ui_basic('Edit Workflow', 'Edit Workflow &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit_workflow" id="edit_workflow" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading">Edit Workflow</div>
              <div class="row mt-1 g-4 ">
              <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="w_cust_id" id="w_cust_id" required>
                    <option value="">Select customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      
													?>
													 <option value="<?php echo $res_customers['id'] ?>" <?php if($app_res['w_cust_id'] == $res_customers['id']){?> selected <?php } ?> style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													
											}
											?>
                </select>
                </div>
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Workflow Name<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="wf_name" id="wf_name"  value="<?php echo $app_res['wf_name']; ?>" placeholder="Enter workflow name" required>
              </input>
                </div>


                <div class="space-2"></div>
                          <h3 class="lighter block green">Add the Workflow steps</h3>
                          <select id="departments" name="departments[]" class="multiselect col-xs-12 col-sm-12" multiple="multiple">    
				<?php
                foreach($exist_departments as $dept_id)
                {
                $query = "SELECT * FROM `adm_dept_master` WHERE parent_id = 0 AND dept_code = ".$dept_id.";";
                $customers = $db->query($query) or ($db->error);
                $row = $customers->fetch_assoc();
                ?>
                <option value="<?php echo $row['dept_code']; ?>" selected="selected"><?php echo $row['dept_name']; ?></option>
                <?php } ?>
                                  <?php 
								$query = "SELECT * FROM `adm_dept_master` WHERE parent_id = 0 AND dept_code NOT IN (".$app_res['wf_code'].");";
								$customers = $db->query($query) or ($db->error);
								  while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>"><?php echo $row['dept_name']; ?></option>
                                  <?php } ?>
                                </select>
                          <div class="hr hr-dotted"></div>


                </div>
            </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/workflow" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="update">
                     <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
                    </div>
                </div>
            </div>
      </form>
    
        </div>
      </div>
      <?php echo scripts(); ?>	
<script type="text/javascript">

$(document).ready(function() {
 	$.localise('ui-multiselect', {language: 'en', path: 'assets/js/locale/'});
			$(".multiselect").multiselect();
			//$('#switcher').themeswitcher();
});
	</script>
       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
 
  <?php fu_foot('#edit_workflow', null, array(), null, fu_ui_basic('Edit Workflow', 'Edit Workflow &rsaquo; Edit')); ?>
  </body>
</html>

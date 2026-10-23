<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php 

if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}

	$update_pwd = '';
	if($post['pwd']!='')
	{
		// stores sha1 of password
		$sha1pass = PwdHash($post['pwd']);
		$update_pwd = ",`pwd` ='". $sha1pass."'";
		
		$insert_password=mysqli_query($db,"INSERT INTO  tbl_user_password(user_id,password) values ('".$post['id']."','".$post['pwd']."')");

	}
	
	$department_platform = isset($post['department_platform']) && !empty($post['department_platform'])    ? "'" . $post['department_platform'] . "'": "NULL";

	 /// Snapshot the existing values (before change) for the change-log email ///
		$log_user_id = (int) $post['id'];
		$res_old_u = $db->query("SELECT * FROM users WHERE id = ".$log_user_id);
		$old_u = ($res_old_u && $res_old_u->num_rows > 0) ? $res_old_u->fetch_assoc() : array();

		$update_query="UPDATE users SET `full_name` ='". $post['full_name']."',user_level ='". $post['user_level']."',department_id ='". $post['department_id']."',`user_name` ='". $post['user_name']."',`user_email` ='". $post['user_email']."',`tel` ='". $post['tel']."',`approved` ='". $post['approved']."' ".$update_pwd.", worklocation='".$post['worklocation']."',  access_other_depts = '".implode(",",$_POST['access_other_depts'])."', cust_id='".$post['cust_id']."',  access_other_cust_id = '".implode(",",$_POST['access_other_cust_id'])."',  preferred_cust_id = '".implode(",",$_POST['preferred_cust_id'])."', cust_id='".$post['cust_id']."', fp_tcount='".$post['fp_tcount']."', fp_tcount_sv='".$post['fp_tcount_sv']."', fp_tcount_cf='".$post['fp_tcount_cf']."', fp_tcount_cv='".$post['fp_tcount_cv']."', rev_tcount='".$post['rev_tcount']."', rev_tcount_sv='".$post['rev_tcount_sv']."', rev_tcount_cf='".$post['rev_tcount_cf']."', rev_tcount_cv='".$post['rev_tcount_cv']."', fin_tcount='".$post['fin_tcount']."', fin_tcount_sv='".$post['fin_tcount_sv']."', fin_tcount_cf='".$post['fin_tcount_cf']."', fin_tcount_cv='".$post['fin_tcount_cv']."',  stage_access = '".implode(",",$_POST['stage_access'])."',  preferred_stage_access = '".implode(",",$_POST['preferred_stage_access'])."', substitute='".$post['substitute']."', sub_user_id='".$post['sub_user_id']."', shift_id='".$post['shift_id']."',folder_access='".$post['folder_access']."',department_platform='".$post['department_platform']."',preediting_server='".$post['preediting_server']."'  WHERE id = ".$post['id']." ";	
	//echo $update_query;exit;
	
	

	if($db->query($update_query))
	{
		/// Email the change log (text attachment); a mail failure must not block the save ///
		try {
			$new_u = $post;
			foreach (array('access_other_depts', 'access_other_cust_id', 'preferred_cust_id', 'stage_access', 'preferred_stage_access') as $arr_f) {
				$new_u[$arr_f] = isset($_POST[$arr_f]) && is_array($_POST[$arr_f]) ? implode(',', $_POST[$arr_f]) : '';
			}
			$skip_fields = array('action', 'id', 'pwd', 'pwd2');
			$u_log = "[User changes]\r\n";
			$u_changes = 0;
			foreach ($new_u as $f => $new) {
				if (in_array($f, $skip_fields) || !array_key_exists($f, $old_u)) {
					continue; // not a column of users
				}
				$old = (string) $old_u[$f];
				if ($old !== (string) $new) {
					$u_log .= "  $f: '$old' -> '$new'\r\n";
					$u_changes++;
				}
			}
			// Password: log that it changed, never the value
			if ($post['pwd'] != '') {
				$u_log .= "  pwd: (password changed - value hidden)\r\n";
				$u_changes++;
			}
			if ($u_changes == 0) {
				$u_log .= "  (no change)\r\n";
			}
			$u_ref = $log_user_id . (!empty($old_u['user_name']) ? " / " . $old_u['user_name'] : '');
			send_change_log_mail('User', $u_ref, $u_log, $u_changes);
		} catch (Exception $e) {
			error_log("edit-users change-log mail failed: " . $e->getMessage());
		}
		header("location:".$_COOKIE['set_url']."/employees?msg=Successfully completed"); 
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
	$result = $db->query("SELECT * FROM users WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}
$ex_pid=explode(",",$app_res['preferred_cust_id']);
$ex_cid=explode(",",$app_res['access_other_cust_id']);
$ex_did=explode(",",$app_res['access_other_depts']);
$ex_sid=explode(",",$app_res['stage_access']);
$ex_psid=explode(",",$app_res['preferred_stage_access']);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('edit-users'); ?>	

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
            <p class="articleheading ms-4">Edit Employee</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row">
              <div class="col-3 articlebtns d-flex justify-content-end">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php
		$query = "SELECT * FROM `user_role_master` ORDER BY `user_role_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		?>
      <?php fu_hero(fu_ui_basic('Edit Employee', 'Edit Employee &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit-users" id="edit-users" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Edit Employee</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Full Name<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Enter user name" value="<?php echo $app_res['full_name']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Phone<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="tel" id="tel" placeholder="Enter phone number" value="<?php echo $app_res['tel']; ?>" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Role<span style="color: red">*</span></label>
                  <select class="form-select " data-placeholder="Select Customer" name="user_level" id="user_level" onchange="if(this.value!=4){$('#workl').show();}else{$('#workl').hide();}" required>
                    <option value="">Select Role</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['user_role_id']; ?>" <?php if($app_res['user_level'] ==$row['user_role_id']){?> selected <?php } ?>><?php echo $row['user_role_name']; ?></option>
                                  <?php } ?>
                </select>
                </div>
                
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Default Customer<span style="color: red">*</span></label>
                  <select class="form-select" aria-label="search-input" id="cust_id" name="cust_id">
                  <option value="">Select customer</option>
                  <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
													 <option value="<?php echo $res_customers['id'] ?>" <?php if ($res_customers['id']==$app_res['cust_id']){?>selected<?php }?> style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													
											}
											?>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Other Customers<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Other Customer" name="access_other_cust_id[]" id="access_other_cust_id"  multiple="multiple">
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
											}
											?>
                </select>
                </div>
				
				  <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Preferred Customers<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Preferred Customer" name="preferred_cust_id[]" id="preferred_cust_id"  multiple="multiple">
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
										       <option <?php if (in_array($res_customers['id'], $ex_pid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
											}
											?>
                </select>
                </div>

             <div class="col-12 col-sm-6 col-lg-4">
  <label class="mb-2">Default department<span style="color: red">*</span></label>
  <select class="form-select" aria-label="search-input" id="department_id" name="department_id">
    <?php
      $sql_deparmtnet = $auth->getAlldepartment();
      foreach ($sql_deparmtnet as $res_department) {
    ?>
      <option 
        value="<?php echo $res_department['dept_code'] ?>" 
        <?php if ($res_department['dept_code'] == $app_res['department_id']) echo "selected"; ?>
        style="font-weight:500;">
        <?php echo $res_department['dept_name'] ?>
      </option>
    <?php } ?>
  </select>
</div>

<!-- Extra Platform dropdown (shown only if department_id == 10) -->
<div id="extra-dropdown" class="col-12 col-sm-6 col-lg-4 mt-3" style="display: none;">
  <label class="mb-2">Platform <span style="color: red">*</span></label>
  <select class="form-select" name="department_platform">
    <option value="3B2" <?php if($app_res['department_platform'] == '3B2') echo "selected"; ?>>3B2</option>
    <option value="INDESIGN" <?php if($app_res['department_platform'] == 'indesign') echo "selected"; ?>>InDesign</option>
    <option value="LATEX" <?php if($app_res['department_platform'] == 'LATEX') echo "selected"; ?>>LATEX</option>
  </select>
</div>

<div class="col-12 col-sm-6 col-lg-8">
  <label class="mb-2">Other Department<span style="color: red">*</span></label>
  <select class="form-select department_drop" data-placeholder="Select Other Department" name="access_other_depts[]" id="access_other_depts" multiple="multiple">
    <?php
      $sql_odeparmtnet = $auth->getAlldepartment();
      foreach ($sql_odeparmtnet as $res_odepartment) {
    ?>
      <option 
        value="<?php echo $res_odepartment['dept_code'] ?>"
        <?php if (in_array($res_odepartment['dept_code'], $ex_did)) echo "selected"; ?>
        style="font-weight:500;">
        <?php echo $res_odepartment['dept_name'] ?>
      </option>
    <?php } ?>
  </select>
</div>

                <div class="col-12 col-sm-6 col-lg-4" id="workl">
                  <label class="mb-2">Work Location<span style="color: red">*</span></label>
                  <select class="form-select" aria-label="search-input" id="worklocation" name="worklocation">
                  <option value="">Select location</option>
									  <?php foreach ($work_loactions as $key => $value) {
											
											if($app_res['worklocation'] == $key)
											{
										?>
											<option value="<?php echo $key ; ?>" selected><?php echo $value; ?></option>
										<?php
											}
											else
											{
										?>
												<option value="<?php echo $key ; ?>" ><?php echo $value; ?></option>
										<?php
											}
										?>
                                  
                                  <?php } ?>
                  </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Choose Stage</label>
                <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage_access[]" id="stage_access" multiple="multiple">
                  <?php foreach($project_status_types as $id=>$val){?>
                          <?php 
                          if($app_res['stage_access']!="")
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if (in_array($id, $ex_sid)){?>selected<?php }?> ><?php echo $val; ?></option>
                         <?php 
                          }
                          else
                          {
                          ?>
                         <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>

                          <?php 
                          }
                          ?>
                   <?php } ?>
                </select>
              </div>
			   <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Choose Preferred Stage</label>
                <select class="form-select stage_drop" data-placeholder="Select Stage" name="preferred_stage_access[]" id="preferred_stage_access" multiple="multiple">
                  <?php foreach($target_stage as $id=>$val){?>
                          <?php 
                          if($app_res['preferred_stage_access']!="")
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if (in_array($id, $ex_psid)){?>selected<?php }?> ><?php echo $val; ?></option>
                         <?php 
                          }
                          else
                          {
                          ?>
                         <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>

                          <?php 
                          }
                          ?>
                   <?php } ?>
                </select>
              </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Status<span style="color: red">*</span> </label>
                  <select id="approved" name="approved" class="form-select" aria-label="search-input">
                      <option value="">-Select Status-</option>
                      <option value="1" <?php if($app_res['approved'] == '1') {?> selected <?php } ?>>Active</option>
                      <option value="0" <?php if($app_res['approved'] == '0') {?> selected <?php } ?>>In Active</option>
                  </select>
                </div>
		</div>
  </div>
            <!-- heading 2 -->
            <div class="container mt-4 separatetitle">
               <div class="formcont1heading">Target Count Details</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FP Simple (Fixed)<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="fp_tcount" id="fp_tcount" placeholder="Enter Simple (Fixed)" value="<?php echo $app_res['fp_tcount']; ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FP Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="fp_tcount_sv" id="fp_tcount_sv" placeholder="Enter  Simple (Variable)" value="<?php echo $app_res['fp_tcount_sv']; ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FP Complex (Fixed) </label>
                  <input type="text" class="form-control" name="fp_tcount_cf" id="fp_tcount_cf" placeholder="Enter Complex (Fixed) " value="<?php echo $app_res['fp_tcount_cf']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">FP Complex (Variable)</label>
                 <input type="text" class="form-control" name="fp_tcount_cv" id="fp_tcount_cv" placeholder="Enter Complex (Variable) " value="<?php echo $app_res['fp_tcount_cv']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Simple (Fixed)<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="rev_tcount" id="rev_tcount" placeholder="Enter Simple (Fixed)" required value="<?php echo $app_res['rev_tcount']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="rev_tcount_sv" id="rev_tcount_sv" placeholder="Enter  Simple (Variable)" required value="<?php echo $app_res['rev_tcount_sv']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Complex (Fixed) </label>
                  <input type="text" class="form-control" name="rev_tcount_cf" id="rev_tcount_cf" placeholder="Enter Complex (Fixed) " value="<?php echo $app_res['rev_tcount_cf']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">REV Complex (Variable)</label>
                 <input type="text" class="form-control" name="rev_tcount_cv" id="rev_tcount_cv" placeholder="Enter Complex (Variable) " value="<?php echo $app_res['rev_tcount_cv']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Simple (Fixed)<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="fin_tcount" id="fin_tcount" placeholder="Enter Simple (Fixed)" required value="<?php echo $app_res['fin_tcount']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="fin_tcount_sv" id="fin_tcount_sv" placeholder="Enter  Simple (Variable)" required value="<?php echo $app_res['fin_tcount_sv']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Complex (Fixed) </label>
                  <input type="text" class="form-control" name="fin_tcount_cf" id="fin_tcount_cf" placeholder="Enter Complex (Fixed) " value="<?php echo $app_res['fin_tcount_cf']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">FINALS Complex (Variable)</label>
                 <input type="text" class="form-control" name="fin_tcount_cv" id="fin_tcount_cv" placeholder="Enter Complex (Variable) " value="<?php echo $app_res['fin_tcount_cv']; ?>">
                </input>
                </div>
                    </div>            
             </div>
             <div class="container mt-4">
               <div class="formcont1heading">Login Details</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Username<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="user_name" id="user_name" placeholder="Enter Username" value="<?php echo $app_res['user_name']; ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Email<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="user_email" id="user_email" placeholder="Enter Email ID" value="<?php echo $app_res['user_email']; ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Password</label>
                  <input type="text" class="form-control" name="pwd" id="pwd" placeholder="Enter password" value="">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Retype Password</label>
                 <input type="text" class="form-control" name="pwd2" id="pwd2" placeholder="Retype password" equalto="#pwd" value="">
                </input>
                </div>
                <?php
		
		$query = "SELECT * FROM `shift_masters` order by sm_id asc";	
		$users = $db->query($query) or ($db->error);		
		?>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Assign Shift</label>
                 <select id="shift_id" name="shift_id" class="form-control">
                                  <option value="">-Select-</option>
                                  <?php while($row = $users->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['sm_id']; ?>" <?php if ($app_res['shift_id'] == $row['sm_id']) { echo ' selected="selected"'; } ?>><?php echo $row['sm_title']; ?></option>
                                  <?php } ?>
                                </select>
                </div>
                
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Substitite<span style="color: red"></span> </label>
                  <select id="substitute" name="substitute" class="form-control">
                                  <option value="">-Select-</option>
                                  <option value="1" <?php if($app_res['substitute'] == '1') {?> selected <?php } ?>>-Enabled-</option>
                                  <option value="0" <?php if($app_res['substitute'] == '0') {?> selected <?php } ?>>-Disabled-</option>
                </select>
                </div>   
                <?php
		
		$query = "SELECT * FROM `users` where user_level =3  and approved='1' and id!='".$_REQUEST['id']."'  order by full_name asc";	
		$users = $db->query($query) or ($db->error);		
		?>
                <div class="col-12 col-sm-6 col-lg-4" id="user_behalf" style="display:none">
                  <label class="mb-2">On Behalf of User<span style="color: red"></span> </label>
                  <select id="sub_user_id" name="sub_user_id" class="form-control">
                  <?php while($row = $users->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if ($app_res['sub_user_id'] == $row['id']) { echo ' selected="selected"'; } ?>><?php echo $row['full_name']; ?></option>
                                  <?php } ?>
                </select>
                </div>   
				
					<div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Folder Access<span style="color: red"></span> </label>
                  <select id="folder_access" name="folder_access" class="form-control">
                                  <option value="">-Select-</option>
                                  <option value="DOMAIN" <?php if($app_res['folder_access'] == 'DOMAIN') {?> selected <?php } ?>>-DOMAIN ACCESS-</option>
                                  <option value="WEB" <?php if($app_res['folder_access'] == 'WEB') {?> selected <?php } ?>>-WEB ACCESS-</option>
                                  <option value="BOTH" <?php if($app_res['folder_access'] == 'BOTH') {?> selected <?php } ?>>-BOTH-</option>
                </select>
                </div>  
				<div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Pre Editing Tool Server Access<span style="color: red"></span> </label>
						 <select id="preediting_server" name="preediting_server" class="form-control">
							<option value="">-Select-</option>
							
							<optgroup label="Server I">
								<option value="10.10.10.109" <?php if($app_res['preediting_server'] == '10.10.10.109') {?> selected <?php } ?>>-10.10.10.109-</option>
								<option value="117.232.75.162:8020" <?php if($app_res['preediting_server'] == '117.232.75.162:8020') {?> selected <?php } ?>>-117.232.75.162:8020 - (10.10.10.109)</option>
								<option value="103.66.12.3:8030" <?php if($app_res['preediting_server'] == '103.66.12.3:8030') {?> selected <?php } ?>>-103.66.12.3:8030 - (10.10.10.109)</option>
							</optgroup>
							<optgroup label="Server II">
								<option value="10.10.10.94" <?php if($app_res['preediting_server'] == '10.10.10.94') {?> selected <?php } ?>>-10.10.10.94-</option>
								<option value="117.232.75.162:8094" <?php if($app_res['preediting_server'] == '117.232.75.162:8094') {?> selected <?php } ?>>-117.232.75.162:8094 - (10.10.10.94)</option>
								<option value="103.66.12.3:8094" <?php if($app_res['preediting_server'] == '103.66.12.3:8094') {?> selected <?php } ?>>-103.66.12.3:8094- - (10.10.10.94)</option>
							</optgroup>
							<optgroup label="Server III">
								<option value="10.10.10.127" <?php if($app_res['preediting_server'] == '10.10.10.127') {?> selected <?php } ?>>-10.10.10.127-</option>
								<option value="117.232.75.162:8127" <?php if($app_res['preediting_server'] == '117.232.75.162:8127') {?> selected <?php } ?>>-117.232.75.162:8127 - (10.10.10.127)</option>
								<option value="103.66.12.3:8127" <?php if($app_res['preediting_server'] == '103.66.12.3:8127') {?> selected <?php } ?>>-103.66.12.3:8127- - (10.10.10.127)</option>
							</optgroup>
						</select>
										</div>  
                    </div>            
             </div>
         
                
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/employees" style="text-decoration:none;">Cancel</a></button>
                        
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

       <!-- Footer Section -->
       <?php echo footer(); ?>
       <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2/select2.min.js"></script>
   
   <script>
     $(".customer_drop").select2({
       placeholder: "Select Customer",
     });
     $(".journal_drop").select2({
       placeholder: "Select Journal",
     });
     $(".stage_drop").select2({
       placeholder: "Select Stage",
     });
     $(".department_drop").select2({
       placeholder: "Select Department",
     });
     $(".platform_drop").select2({
       placeholder: "Select Platform",
     });

     $("#substitute").change(function() {
				var check = $("#substitute").val();
				
            if (check == 1) {
				
				//alert(check);
				$("#user_behalf").show();
            } else {
               $("#user_behalf").hide();

            }
        });
   </script>
   
   <!-- JavaScript to toggle extra dropdown -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const deptSelect = document.getElementById('department_id');
    const extraDropdown = document.getElementById('extra-dropdown');

    function toggleExtraDropdown() {
      if (deptSelect.value === '10') {
        extraDropdown.style.display = 'block';
      } else {
        extraDropdown.style.display = 'none';
      }
    }

    deptSelect.addEventListener('change', toggleExtraDropdown);
    toggleExtraDropdown(); // Run on page load for edit mode
  });
</script>
  <?php fu_foot('#edit-users', null, array(), null, fu_ui_basic('Edit Employee', 'Edit Employee &rsaquo; Edit')); ?>
  </body>
</html>

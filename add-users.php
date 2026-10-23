<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
$err = array();
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	// stores sha1 of password
	$sha1pass = PwdHash($post['pwd']);

$user_email = $post['user_email'];
$user_name = $post['user_name'];

$rs_duplicate = $db->query("select count(*) as total from users where user_email='$user_email' OR user_name='$user_name'") or die($db->error());
$exist_user = $rs_duplicate->fetch_assoc();

if ($exist_user['total'] > 0)
{
$err[] = "ERROR - The username/email already exists. Please try again with different username and email.";
}
$department_platform = isset($post['department_platform']) && !empty($post['department_platform'])   ? "'" . $post['department_platform'] . "'": "NULL";

//	echo "Count: ".$exist_user['total'];

	//echo "INSERT INTO `users` (`user_level`, `department_id`, `full_name`,user_name,user_email,pwd,approved,tel,`created_by`, `created_on`,`worklocation`, `access_other_depts`, `cust_id`,`access_other_cust_id`,`stage_access`,`fp_tcount`,`fp_tcount_sv`,`fp_tcount_cf`,`fp_tcount_cv`,`rev_tcount`,`rev_tcount_sv`,`rev_tcount_cf`,`rev_tcount_cv`,`fin_tcount`,`fin_tcount_sv`,`fin_tcount_cf`,`fin_tcount_cv`,`substitute`,`sub_user_id`,`shift_id`,`folder_access`,`department_platform`) VALUES ('".$post['user_level']."', '".$post['department_id']."', '".$post['full_name']."', '".$post['user_name']."','".$post['user_email']."','".$sha1pass."','".$post['approved']."','".$post['tel']."','".$_COOKIE['user_id']."', NOW(),'".$post['worklocation']."', '".implode(",",$_POST['access_other_depts'])."','".$post['cust_id']."', '".implode(",",$_POST['access_other_cust_id'])."', '".implode(",",$_POST['stage_access'])."','".$post['fp_tcount']."','".$post['fin_tcount_sv']."','".$post['fin_tcount_cf']."','".$post['fin_tcount_cv']."','".$post['rev_tcount']."','".$post['rev_tcount_sv']."','".$post['rev_tcount_cf']."','".$post['rev_tcount_cv']."','".$post['fin_tcount']."','".$post['fin_tcount_sv']."','".$post['fin_tcount_cf']."','".$post['fin_tcount_cv']."','".$post['substitute']."','".$post['sub_user_id']."','".$post['shift_id']."','".$post['folder_access']."',".$department_platform.");";exit;
	
	if(empty($err)) 
	{
		
	$insert_query = mysqli_query($db,"INSERT INTO `users` (`user_level`, `department_id`, `full_name`,user_name,user_email,pwd,approved,tel,`created_by`, `created_on`,`worklocation`, `access_other_depts`, `cust_id`,`access_other_cust_id`,`preferred_cust_id`,`stage_access`,`preferred_stage_access`,`fp_tcount`,`fp_tcount_sv`,`fp_tcount_cf`,`fp_tcount_cv`,`rev_tcount`,`rev_tcount_sv`,`rev_tcount_cf`,`rev_tcount_cv`,`fin_tcount`,`fin_tcount_sv`,`fin_tcount_cf`,`fin_tcount_cv`,`substitute`,`sub_user_id`,`shift_id`,`folder_access`,`department_platform`,`preediting_server`) VALUES ('".$post['user_level']."', '".$post['department_id']."', '".$post['full_name']."', '".$post['user_name']."','".$post['user_email']."','".$sha1pass."','".$post['approved']."','".$post['tel']."','".$_COOKIE['user_id']."', NOW(),'".$post['worklocation']."', '".implode(",",$_POST['access_other_depts'])."','".$post['cust_id']."', '".implode(",",$_POST['access_other_cust_id'])."', '".implode(",",$_POST['preferred_cust_id'])."', '".implode(",",$_POST['stage_access'])."', '".implode(",",$_POST['preferred_stage_access'])."','".$post['fp_tcount']."','".$post['fin_tcount_sv']."','".$post['fin_tcount_cf']."','".$post['fin_tcount_cv']."','".$post['rev_tcount']."','".$post['rev_tcount_sv']."','".$post['rev_tcount_cf']."','".$post['rev_tcount_cv']."','".$post['fin_tcount']."','".$post['fin_tcount_sv']."','".$post['fin_tcount_cf']."','".$post['fin_tcount_cv']."','".$post['substitute']."','".$post['sub_user_id']."','".$post['shift_id']."','".$post['folder_access']."',".$department_platform.",'".$post['preediting_server']."');");

	
	
	$insert_id=mysqli_insert_id($insert_query);;
	
	$insert_password=mysqli_query($db,"INSERT INTO  tbl_user_password(user_id,password) values ('".$insert_id."','".$post['pwd']."')");
	
	// Change-log entry + email for the new record
	log_row_added('User', change_log_ref(array('user_name','full_name')), array_diff_key($_POST, array('action'=>1)), array('pwd','pwd2'));
	header("location:".$_COOKIE['set_url']."/employees?msg=Successfully added"); 
	die();	
	}
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
    <?php fu_head('add-users'); ?>	

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
            <p class="articleheading ms-4">Add Employee</p>
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
		$query = "SELECT * FROM `user_role_master` ORDER BY `user_role_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		?>
      <?php fu_hero(fu_ui_basic('Add Employee', 'Add Employee &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-users" id="add-users" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add New Employee</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Full Name<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Enter user name" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Phone<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="tel" id="tel" placeholder="Enter phone number" required>
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
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
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
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
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
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_pid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>

             <div class="col-12 col-sm-6 col-lg-4">
  <label class="mb-2">Default department<span style="color: red">*</span></label>
  <select class="form-select" aria-label="search-input" id="department_id" name="department_id">
    <?php
      $sql_deparmtnet = $auth->getAlldepartment();
      foreach($sql_deparmtnet as $res_department) {
    ?>
      <option value="<?php echo $res_department['dept_code'] ?>" style="font-weight:500;">
        <?php echo $res_department['dept_name'] ?>
      </option>
    <?php 
      }
    ?>
  </select>
</div>

<!-- Extra dropdown shown only when department_id = 10 -->
<div id="extra-dropdown" class="col-12 col-sm-6 col-lg-4 mt-3" style="display: none;">
  <label class="mb-2">Platform <span style="color: red">*</span></label>
  <select class="form-select" name="department_platform">
			<option value="3B2" >3B2</option>
			<option value="INDESIGN">INDESIGN</option>
			<option value="LATEX">LATEX</option>
  </select>
</div>

<div class="col-12 col-sm-6 col-lg-8">
  <label class="mb-2">Other Department<span style="color: red">*</span></label>
  <select class="form-select department_drop" data-placeholder="Select Other Department" name="access_other_depts[]" id="access_other_depts" multiple="multiple">
    <?php
      $sql_odeparmtnet = $auth->getAlldepartment();
      foreach($sql_odeparmtnet as $res_odepartment) {
        if(isset($_REQUEST['access_other_cust_id'])) {
    ?>
        <option 
          <?php if (in_array($res_odepartment['dept_code'], $ex_cid)) { ?>selected<?php } ?>
          value="<?php echo $res_odepartment['dept_code'] ?>" style="font-weight:500;">
          <?php echo $res_odepartment['dept_name'] ?>
        </option>
    <?php
        } else {
    ?>
        <option value="<?php echo $res_odepartment['dept_code'] ?>" style="font-weight:500;">
          <?php echo $res_odepartment['dept_name'] ?>
        </option>
    <?php
        }
      }
    ?>
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
                          if(isset($_REQUEST['stage']))
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if (in_array($id, $ex_stage)){?>selected<?php }?> ><?php echo $val; ?></option>
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
                         <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                   <?php } ?>
                </select>
              </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Status<span style="color: red">*</span> </label>
                  <select id="approved" name="approved" class="form-select" aria-label="search-input">
                      <option value="">-Select Status-</option>
                      <option value="1">Active</option>
                      <option value="0">In Active</option>
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
                  <input type="text" class="form-control" name="fp_tcount" id="fp_tcount" placeholder="Enter Simple (Fixed)" value="0" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FP Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="fp_tcount_sv" id="fp_tcount_sv" placeholder="Enter  Simple (Variable)" value="0" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FP Complex (Fixed) </label>
                  <input type="text" class="form-control" name="fp_tcount_cf" id="fp_tcount_cf" placeholder="Enter Complex (Fixed) " value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">FP Complex (Variable)</label>
                 <input type="text" class="form-control" name="fp_tcount_cv" id="fp_tcount_cv" placeholder="Enter Complex (Variable) " value="0">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Simple (Fixed)<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="rev_tcount" id="rev_tcount" placeholder="Enter Simple (Fixed)" required value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="rev_tcount_sv" id="rev_tcount_sv" placeholder="Enter  Simple (Variable)" required value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">REV Complex (Fixed) </label>
                  <input type="text" class="form-control" name="rev_tcount_cf" id="rev_tcount_cf" placeholder="Enter Complex (Fixed) " value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">REV Complex (Variable)</label>
                 <input type="text" class="form-control" name="rev_tcount_cv" id="rev_tcount_cv" placeholder="Enter Complex (Variable) " value="0">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Simple (Fixed)<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="fin_tcount" id="fin_tcount" placeholder="Enter Simple (Fixed)" required value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Simple (Variable)<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="fin_tcount_sv" id="fin_tcount_sv" placeholder="Enter  Simple (Variable)" required value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">FINALS Complex (Fixed) </label>
                  <input type="text" class="form-control" name="fin_tcount_cf" id="fin_tcount_cf" placeholder="Enter Complex (Fixed) " value="0">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                 <label class="mb-2">FINALS Complex (Variable)</label>
                 <input type="text" class="form-control" name="fin_tcount_cv" id="fin_tcount_cv" placeholder="Enter Complex (Variable) " value="0">
                </input>
                </div>
                    </div>            
             </div>
             <div class="container mt-4">
               <div class="formcont1heading">Login Details</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Username<span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="user_name" id="user_name" placeholder="Enter Username" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Email<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="user_email" id="user_email" placeholder="Enter Email ID" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Password</label>
                  <input type="password" class="form-control" name="pwd" id="pwd" placeholder="Enter password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Retype Password</label>
                 <input type="password" class="form-control" name="pwd2" id="pwd2" placeholder="Retype password" equalto="#pwd">
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
                                  <option value="1" >-Enabled-</option>
                                  <option value="0" selected>-Disabled-</option>
                </select>
                </div>   
                <?php
		
		$query = "SELECT * FROM `users` where user_level =3  and approved='1'  order by full_name asc";	
		$users = $db->query($query) or ($db->error);		
		?>
                <div class="col-12 col-sm-6 col-lg-4" id="user_behalf" style="display:none">
                  <label class="mb-2">On Behalf of User<span style="color: red"></span> </label>
                  <select id="sub_user_id" name="sub_user_id" class="form-control">
                  <?php while($row = $users->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>"><?php echo $row['full_name']; ?></option>
                                  <?php } ?>
                </select>
                </div>   
                    </div>  

				<div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Folder Access<span style="color: red"></span> </label>
                  <select id="folder_access" name="folder_access" class="form-control">
                                  <option value="">-Select-</option>
                                  <option value="DOMAIN" selected >-DOMAIN ACCESS-</option>
                                  <option value="WEB" >-WEB ACCESS-</option>
                                  <option value="BOTH">-BOTH-</option>
                </select>
                </div>   	
				<div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Pre Editing Tool Server Access<span style="color: red"></span> </label>
                 <select id="preediting_server" name="preediting_server" class="form-control">
    <option value="">-Select-</option>
    
    <optgroup label="Server I">
        <option value="10.10.10.109" selected>-10.10.10.109-</option>
        <option value="117.232.75.162:8020">-117.232.75.162:8020 - (10.10.10.109)</option>
        <option value="103.66.12.3:8010">-103.66.12.3:8030 - (10.10.10.109)</option>
    </optgroup>
    
    <optgroup label="Server II">
        <option value="10.10.10.94">-10.10.10.94-</option>
        <option value="117.232.75.162:8094">-117.232.75.162:8094 - (10.10.10.94)</option>
        <option value="103.66.12.3:8094">-103.66.12.3:8094- - (10.10.10.94)</option>
    </optgroup>
	 <optgroup label="Server III">
        <option value="10.10.10.127">-10.10.10.127-</option>
        <option value="117.232.75.162:8127">-117.232.75.162:8127 - (10.10.10.127)</option>
        <option value="103.66.12.3:8127">-103.66.12.3:8127- - (10.10.10.127)</option>
    </optgroup>
</select>

                </div>   
				
             </div>
         
                
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/employees" style="text-decoration:none;">Cancel</a></button>
                        
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
       <script src="assets/js/select2/select2.min.js"></script>
   
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

    // Trigger on page load (in case of default value or edit mode)
    toggleExtraDropdown();
  });
</script>

  <?php fu_foot('#add-users', null, array(), null, fu_ui_basic('Add Employee', 'Add Employee &rsaquo; Add')); ?>
  </body>
</html>

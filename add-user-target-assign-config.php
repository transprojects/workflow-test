<?php include "includes/includes-sw2.php";
require_once __DIR__.'/includes/form_ui.php';
include_once "includes/change_log_mail.php";
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
	$department_id = $_POST['dept_id'];
    $cust_id = $_POST['cust_id'];
    $j_ids = implode(",",$_POST['j_ids']);
    $stage = $_POST['stage'];
    $per_day_limit = $_POST['per_day_limit'];
    $assign_order = $_POST['assign_order'];

    $sql = "INSERT INTO user_auto_assign_config (user_id, department_id,  cust_id, j_ids, stage, per_day_limit, assign_order)
            VALUES ('$user_id','$department_id', '$cust_id', '$j_ids', '$stage', '$per_day_limit', '$assign_order')";			

    if ($db->query($sql) === TRUE) {
        $message = "User configuration successfully created.";
        log_row_added('Employee Target Config', $user_id . ' / cust ' . $cust_id, array('user_id'=>$user_id,'department_id'=>$department_id,'cust_id'=>$cust_id,'j_ids'=>$j_ids,'stage'=>$stage,'per_day_limit'=>$per_day_limit,'assign_order'=>$assign_order));
        
    } else {
        $message =  "Error: " . $sql . "<br>" . $db->error;
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
    <?php fu_head('add_target_choose'); fu_card_css(); ?>
    
    <!-- Data Table CSS -->
    
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
          <p class="articleheading ms-4">Add Employee Target</p>
        </div>
        <div class="col-4 col-lg-2">
          <div class="row d-flex justify-content-end pe-2">
            <div class="col-3 articlebtns "> <a href="<?php echo $_COOKIE['set_url'];?>/employeetargetassignconfig">
              <div class="back_icon"><img
                                        src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
              </a> </div>
          </div>
        </div>
        <div class="row">
        <p><?php echo $message; ?></p>
        </div>
      </div>
    </div>
    <?php fu_hero(fu_ui_basic('Add Employee Target', 'Add Employee Target &rsaquo; Add')); ?>
      <style>
  .fu-card .container.mt-4,.fu-card > .container{max-width:none;padding:0;}
  .fu-card .formcont1heading{display:none;}
  .fu-card .col-md-12 > form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 22px;align-items:start;}
  .fu-card .col-md-12 > form > [class*="col-"]{width:100% !important;max-width:none !important;flex:none !important;padding:0 !important;margin:0 !important;}
  .fu-card .col-md-12 > form > .row{display:contents;}
  .fu-card .col-md-12 > form > .row > [class*="col-"]{width:100% !important;max-width:none !important;flex:none !important;padding:0 !important;margin:0 !important;}
  .fu-card .col-md-12 > form > .meta_nxtbtn{grid-column:1 / -1;margin:6px 0 0 !important;padding:0 !important;max-width:none;}
  .fu-card .meta_nxtbtn .row{display:flex;justify-content:flex-end;gap:12px;margin:0;} .fu-card .meta_nxtbtn .col{flex:0 0 auto;width:auto;padding:0;}
  .fu-card .select2-container{width:100% !important;}
  @media(max-width:900px){.fu-card .col-md-12 > form{grid-template-columns:1fr 1fr;}} @media(max-width:600px){.fu-card .col-md-12 > form{grid-template-columns:1fr;}}
</style>
<div class="fu-wrap"><div class="fu-card">
    <div class="container mt-4 ">
      <div class="formcont1heading">Add Target Config</div>
      <br />
      <div class="col-md-12">
        <form action="" method="post" name="add_target_choose" id="add_target_choose"
                            enctype="multipart/form-data">
          <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <label class="mb-2">Choose Customer</label>
            <select class="form-select customer_drop" data-placeholder="Select Customer"
                                    name="cust_id" id="cust_id" required>
              <option value="all">Select All</option>
              <?php
										  if($_COOKIE['user_level']=='3')
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
              <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?>
                                        value="<?php echo $res_customers['id'] ?>" style="font-weight:500;"> <?php echo $res_customers['cust_name'] ?> </option>
              <?php
													}
													else
													{
													?>
              <option value="<?php echo $res_customers['id'] ?>" style="font-weight:500;"> <?php echo $res_customers['cust_name'] ?> </option>
              <?php
													}
											}
											?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <label class="mb-2">Choose Journal</label>
            <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_ids[]"
                                    id="j_id" multiple="multiple">
              <option value="all">Select All</option>
              <?php
											 if($_COOKIE['user_level']=='3')
										  {
											$sql_journals = $auth->showJournallistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_journals = $auth->showJournallist('0',2);
										  }
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
              <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?>
                                        value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;"> <?php echo $res_journals['j_code'] ?> </option>
              <?php
													}
													else
													{
													?>
              <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;"> <?php echo $res_journals['j_code'] ?> </option>
              <?php
													}
											}
											?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <label class="mb-2">Choose Stage</label>
            <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage"
                                    id="stage">
              <option value="all">Select All</option>
              <?php foreach($project_status_types as $id=>$val){?>
              <?php 
                          if(isset($_REQUEST['stage']))
													{
                          ?>
              <option value="<?php echo $id; ?>"
                                        <?php if (in_array($id, $ex_stage)){?>selected<?php }?>><?php echo $val; ?> </option>
              <?php 
                          }
                          else
                          {
                          ?>
              <option value="<?php echo $id; ?>"><?php echo $val; ?></option>
              <?php 
                          }
                          ?>
              <?php } ?>
            </select>
          </div>
          <div class="row" style="width:100%">
            <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Department <span style="color: red">*</span></label>
              <select class="form-select department_drop" data-placeholder="Select Department"
                                        name="dept_id" id="dept_id" required>
                <option value="">Select Primary Department</option>
                <?php
											$sql_department = $auth->getAlldepartmentTarget();
											foreach($sql_department as $res_department)
											{
											?>
                <option value="<?php echo $res_department['dept_code'] ?>"
                                            style="font-weight:500;"
                                            <?php if ($_REQUEST['dept_id']==$res_department['dept_code']){?>selected<?php }?>> <?php echo $res_department['dept_name'] ?> </option>
                <?php
											}
											?>
              </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Employee <span style="color: red">*</span></label>
              <select class="form-select employee_drop" data-placeholder="Select Employee"
                                        name="user_id" id="user_id" required>
                <option value="">Select Employee</option>
                <?php
											if ($_REQUEST['dept_id']!="")
											{
											$sql_users = $auth->showUserlistdep($_REQUEST['dept_id']);
											}
											else
											{
											$sql_users = $auth->showUserlist();
											}
											foreach($sql_users as $res_users)
											{
											?>
                <option value="<?php echo $res_users['id'] ?>" style="font-weight:500;"
                                            <?php if ($_REQUEST['user_id']==$res_users['id']){?>selected<?php }?>> <?php echo $res_users['full_name'] ?> </option>
                <?php
											}
											?>
              </select>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <label class="mb-2">Assign preference order <span style="color: red">*</span></label>
            <input class="form-control" type="text" name="assign_order" value="" type="number" required pattern="^\d+$">
          </div>
          <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <label class="mb-2">Per day limit <span style="color: red">*</span></label>
            <input class="form-control" type="text" name="per_day_limit" value="" type="number" required pattern="^\d+$">
          </div>
          <div class="meta_nxtbtn container-fluid mt-5 mb-5">
            <div class="row">
              <div class="col form_btn cancelbtn d-flex justify-content-end">
                <button type="submit" class="btn-next-cancel">
                <a
                                                href="<?php echo $_COOKIE['set_url'];?>/employeetargetassignconfig"
                                                style="text-decoration:none;">Cancel</a>
                </button>
              </div>
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
    
    <!-- Date picker functionality --> 
    
    <script>
                function populateSelect(elementId, data) {
                    var selectElement = $("#" + elementId);
                    selectElement.empty();

                    $.each(data, function(index, item) {
                        selectElement.append($("<option>").text(item.full_name).val(item.id));
                    });
                }



                // Handle changes in the "countries" select element
                $("#dept_id").on("change", function() {
                    var department_id = $(this).val();

                    // Fetch connectedsource based on the selected connectedsource and populate the "name" select element
                    $.ajax({
                        url: "get_users.php",
                        dataType: "json",
                        data: {
                            dep_id: department_id
                        },
                        success: function(data) {
                            populateSelect("user_id", data);

                            // Reset the "cities" select element when the country changes
                            //$("#user_id").empty();
                        }
                    });
                });
                </script> 
    <script src="assets/js/select2/select2.min.js"></script> 
    <script>
                $(".employee_drop").select2({
                    placeholder: "Select Employee",
                });
                $(".customer_drop").select2({
                    placeholder: "Select Customers",
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
                $("#cust_id").on("change", function() {
                    var cust_id = $.trim($(this).val());

                    if ($(this).val() && $(this).val().indexOf('all') !== -1) {
                        // Clear all other selections
                        $('#cust_id').val([]);
                        // Select all options except the "Select All" option
                        $('#cust_id option').not(':first').prop('selected', true);
                    } else {
                        // If any other option is selected, unselect "Select All"
                        if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                            $('#cust_id option[value=all]').prop('selected', false);
                        }
                    }

                    // Fetch states based on the selected client name and populate the "name" select element
                    $.ajax({
                        url: "get_journal.php",
                        dataType: "json",
                        data: {
                            cust_id: cust_id
                        },
                        success: function(data) {
                            populateSelecte("j_id", data);

                        }
                    });
                });

                function populateSelecte(elementId, data) {
                    var selectElement = $("#" + elementId);
                    selectElement.empty();
                    selectElement.append($("<option>").text("Select All").val("all"));

                    $.each(data, function(index, item) {
                        selectElement.append($("<option>").text(item.j_code).val(item.j_id));
                    });
                }
                </script>
    <?php fu_foot('#add_target_choose', null, array(), null, fu_ui_basic('Add Employee Target', 'Add Employee Target &rsaquo; Add')); ?>
  </body>
    </html>
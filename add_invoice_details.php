<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
//// Adding extra stages for this page only 

/// Poject stages array
$project_status_types =array('FP'=>'FirstProof','REV1'=>'Revises - 1','REV2'=>'Revises - 2','REV3'=>'Revises -
3','REV4'=>'Revises - 4','REV5'=>'Revises - 5','FIN'=>'Final','ISSCOR'=>'Issue','ISSFIN'=>'Issue
Final','JSTAGE'=>'JSTAGE','Sample'=>'Sample');
/// Add New record
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'insert') {
   
    foreach ($_REQUEST as $key => $value) {
        $post[$key] = filter($value);
    }
    
    // Check if options are selected
    if (isset($_REQUEST['j_id']) && !empty($_REQUEST['j_id'])) {       
        $selectedJids = implode(",", $_REQUEST['j_id']);
    } else {
        $selectedJids = 0;
    }
    $vall = isset($_SESSION['user_id']) && $_SESSION['user_id'] ? $_SESSION['user_id'] : 0;
    $insert_query = "INSERT INTO `customer_invoice_config` (`cust_id`,`stage`,`division` , `created_by`, `created_on`, j_ids) VALUES (" . $post['cust_id'] . ", '" . $post['stage'] . "', '" . $post['division'] . "' , (" . $vall . "!=0 OR NULL), CURRENT_TIMESTAMP, (" .  $selectedJids . "!=0 OR NULL))";
    $db->query($insert_query) or die($db->error);

    // Change-log entry + email for the new record
    log_row_added('Customer Invoice Config', change_log_ref(array('cust_id')), array_diff_key($_POST, array('action'=>1)), array());
    header("location:invoice_details.php?msg=Successfully added");
    die();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update') {
    foreach ($_REQUEST as $key => $value) {
        $post[$key] = filter($value);
    }

    // Check if options are selected
    if (isset($_REQUEST['j_id']) && !empty($_REQUEST['j_id'])) {        
        $selectedJids = implode(",", $_REQUEST['j_id']);
       
    } else {
        $selectedJids = '';
    }

    echo $update_query = "UPDATE customer_invoice_config SET `cust_id` ='" . $post['cust_id'] . "',`stage` ='" . $post['stage'] . "',`division` ='" . $post['division'] . "', j_ids =  '" .  $selectedJids . "' WHERE cic_id = " . $post['id'] . " ";
    if ($db->query($update_query)) {
        header("location:invoice_details.php?msg=Successfully completed");
        die();
    } else {
        $err[] = "Please try again.";
    }
}
///////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
    $result = $db->query("SELECT * FROM customer_invoice_config WHERE cic_id = " . (int)$_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
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
    <?php fu_head('validation-form'); fu_card_css(); fu_horizontal_css(); ?>

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
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .formcont1heading {
        left: 12px
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


        <div class="container-fluid mt-4">

            <div div class="row pt-4 d-flex align-items-center flex-wrap">
                <div class="col-8 col-lg-10">
                    <p class="articleheading ms-sm-2 ms-lg-5">Add / Edit Customer Invoice Details</p>
                </div>

                <div class="col-4 col-lg-2">
                    <div class="row pe-sm-2 pe-lg-2" style="display: flex; justify-content: end">
                        <div class="col-3 articlebtns">
                            <a href="javascript:history.back();">
                                <div class="back_icon"><img
                                        src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="row">
          <div class="col-5 addarticleinfoicon">
            <div class="arinfoicon">
              <img
                class="articleuploadstatusimg"
                src="<?php echo $_COOKIE['set_url'];?>/assets/images/info-circle.svg"
              />
              <p class="arinfotext">Basic Information</p>
            </div>
          </div>
          <div class="col-2 dotteddiv">
            <span class="dottedline"></span>
          </div>
          <div class="col-5 addarticleinfoicon1">
            <div class="arinfoicon">    
              <img
                class="articleuploadstatusimg"
                src="<?php echo $_COOKIE['set_url'];?>/assets/images/info-upload.svg"
              />
              <p class="arinfotext">Upload Manuscript</p>
            </div>
          </div>
        </div>
      </div> -->

            </div>
<?php fu_hero(fu_ui_basic('Add / Edit Customer Invoice Details', 'Add / Edit Customer Invoice Details &rsaquo; Add')); ?>
      <div class="fu-wrap"><div class="fu-card">
                    <!-- <form> -->
                    <div class="container mt-4">
                        <div class="row mt-1 g-4 addspacing">
                            <div class="col-xs-12">
                                <!-- PAGE CONTENT BEGINS -->
                                <div id="fuelux-wizard-container">
                                    <div class="step-content pos-rel">
                                        <div class="step-pane active" data-step="1">
                                            <div class="formcont1heading mt-3 mb-3">Enter the Following Information
                                                :</div>
                                            <?php if (isset($_REQUEST['id'])) { ?>
                                            <form class="form-horizontal" id="validation-form" method="POST">

                                                <div class="form-group">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="email">Customer:</label>
                                                    <div class="col-xs-4 col-sm-4">
                                                        <div class="clearfix">
                                                            <?php
	$query = "SELECT id,cust_name FROM `adm_customer_master` ORDER BY `cust_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                                                            <select id="cust_id" name="cust_id"
                                                                class="col-xs-4 col-sm-4 form-select journal_drop required"
                                                                data-placeholder="Choose">
                                                                <option value="">-Select-</option>
                                                                <?php while($row = $customers->fetch_assoc()){ ?>
                                                                <option value="<?php echo $row['id']; ?>"
                                                                    <?php if($app_res['cust_id'] == $row['id']){?>
                                                                    selected <?php } ?>>
                                                                    <?php echo $row['cust_name']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="email">Choose Journal:</label>
                                                    <div class="col-xs-4 col-sm-4">
                                                        <div class="clearfix">
                                                            <?php
		$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_cust_id='".$app_res['cust_id']."'  ORDER BY `j_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                                            <select id="j_id" name="j_id[]"
                                                                class="col-xs-4 col-sm-4 form-select smenu_drop"
                                                                data-placeholder="Choose" multiple>
                                                                <option
                                                                    <?php if($app_res['j_ids'] == '' ||  is_null($app_res['j_ids'])){?>
                                                                    selected <?php } ?> value="">-All-</option>
                                                                <?php while($row = $customers->fetch_assoc()){
                                    // Check if the option is selected
									$selected = (in_array($row['j_id'], explode(',', $app_res['j_ids']))) ? 'selected' : '';
                                    ?>
                                                                <option value="<?php echo $row['j_id']; ?>"
                                                                    <?php echo $selected; ?>>
                                                                    <?php echo $row['j_code']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                            <div class="alert alert-warning" id="j_id1"
                                                                style="display:none;color: #a94442;
    font-family: 'Open Sans';font-size: 13px;background-color: #fff; border-color: #fff;position: absolute; visibility: visible; right: 500px; top: -15%; height: 100px; width: 160px; z-index: 200;">
                                                                Data's Already Exist! Try Other Combinations</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>


                                                <div class="form-group">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="name">Stage:</label>
                                                    <div class="col-xs-4 col-sm-4">
                                                        <div class="clearfix">
                                                            <select name="stage"
                                                                class="col-xs-4 col-sm-4  form-select journal_drop required">
                                                                <option value="">-Select-</option>
                                                                <?php foreach($project_status_types as $id=>$val){?>
                                                                <option value="<?php echo $id; ?>"
                                                                    <?php if($id==$app_res['stage']){?> selected
                                                                    <?php }?>><?php echo $val; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="name">Division:</label>
                                                    <div class="col-xs-4 col-sm-4">
                                                        <div class="clearfix">
                                                            <select name="division"
                                                                class="col-xs-4 col-sm-4 form-select journal_drop required">
                                                                <option value="">-Select-</option>
                                                                <?php foreach($division_array as $id=>$val){?>
                                                                <option value="<?php echo $val; ?>"
                                                                    <?php if($val==$app_res['division']){?> selected
                                                                    <?php }?>><?php echo $val; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>


                                                <div class="wizard-actions">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id"
                                                        value="<?php echo (int)$_REQUEST['id']; ?>" />
                                                    <a href="invoice_details.php" class="btn btn-grey"> <i
                                                            class="ace-icon fa fa-arrow-left"></i> Cancel </a>
                                                    <button type="submit" class="btn btn-info" value="Save"> Save <i
                                                            class="ace-icon fa fa-arrow-right icon-on-right"></i>
                                                    </button>
                                                </div>

                                            </form>
                                            <?php } else { ?>
                                            <form class="form-horizontal" id="validation-form" method="POST">
                                                <div class="col-12 col-sm-6 col-lg-4">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="email">Customer:</label>


                                                    <?php
	$query = "SELECT id,cust_name FROM `adm_customer_master` ORDER BY `cust_name` ASC";
	$customers = $db->query($query) or ($db->error);		
	?>
                                                    <select id="cust_id" name="cust_id"
                                                        class="col-xs-4 col-sm-4 form-select journal_drop required"
                                                        data-placeholder="Choose">
                                                        <option value="">-Select-</option>
                                                        <?php while($row = $customers->fetch_assoc()){ ?>
                                                        <option value="<?php echo $row['id']; ?>"
                                                            <?php if($app_res['cust_id'] == $row['id']){?> selected
                                                            <?php } ?>>
                                                            <?php echo $row['cust_name']; ?></option>
                                                        <?php } ?>
                                                    </select>

                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-4">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="email">Choose Journal:</label>


                                                    <?php
		$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` ORDER BY `j_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                                    <select multiple id="j_id" name="j_id[]"
                                                        class="col-xs-4 col-sm-4 form-select smenu_drop"
                                                        data-placeholder="Choose">
                                                        <option value="">-All-</option>
                                                        <?php while($row = $customers->fetch_assoc()){ ?>
                                                        <option value="<?php echo $row['j_id']; ?>">
                                                            <?php echo $row['j_code']; ?></option>
                                                        <?php } ?>
                                                    </select>

                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-4">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="name">Stage:</label>

                                                    <select name="stage"
                                                        class="col-xs-4 col-sm-4 form-select journal_drop required">
                                                        <option value="">-Select-</option>
                                                        <?php foreach($project_status_types as $id=>$val){?>
                                                        <option value="<?php echo $id; ?>"
                                                            <?php if($id==$app_res['stage']){?> selected <?php }?>>
                                                            <?php echo $val; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-4">
                                                    <label class="control-label col-xs-4 col-sm-4 no-padding-right"
                                                        for="name">Division:</label>

                                                    <select name="division"
                                                        class="col-xs-4 col-sm-4 form-select journal_drop required">
                                                        <option value="">-Select-</option>
                                                        <?php foreach($division_array as $id=>$val){?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php if($val==$app_res['division']){?> selected <?php }?>>
                                                            <?php echo $val; ?>
                                                        </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="meta_nxtbtn container-fluid mt-5 mb-5">
                                                    <div class="row">
                                                        <input type="hidden" name="action" value="insert">
                                                        <div class="col form_btn cancelbtn d-flex justify-content-end">
                                                            <button type="button" class="btn-next-cancel"> <a
                                                                    href="invoice_details.php" class="btn btn-grey">
                                                                    <i class="ace-icon fa fa-arrow-left"></i> Cancel
                                                                </a></button>
                                                        </div>
                                                        <div class="col form_btn nxtbtn">
                                                            <button type="submit" class="btn-next"> Save
                                                                <i class="ace-icon fa fa-arrow-right icon-on-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <!-- /.widget-main -->
                                </div>
                                <!-- PAGE CONTENT ENDS -->
                                <!-- /.col -->
                            </div>
                        </div>

                    </div>
                    <!-- </form> -->
                </div>
                <!-- upload section -->

            </div>
        </div>
    </div>
    <?php echo scripts(); ?>

    <!-- Footer Section -->
    <?php echo footer(); ?>

    <!-- Date picker functionality -->

    <script src="assets/js/select2/select2.min.js"></script>

    <script>
    jQuery(function($) {
        $('.date-picker').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'dd-mm-yyyy'
        })
        $(".mydatepicker").datepicker({
            format: "mm-yyyy",
            viewMode: "months",
            minViewMode: "months"
        });

        $('#validation-form').validate({
            errorClass: 'help-block'
        });
    })
    $(".customer_drop").select2({
        placeholder: "Select Customer",
    });
    $(".journal_drop").select2({
        placeholder: "Select",
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
    $(".smenu_drop").select2({
        placeholder: "Select Journal",
    });
    </script>
    <script type="text/javascript">
    jQuery(function($) {

        //documentation : http://docs.jquery.com/Plugins/Validation/validate

        $.validator.addMethod("regex", function(value, element, regexpr) {
            return regexpr.test(value);
        }, "<p>No Spaces Or Special Characters <b>(Only Hyphen and Underscore)</b> Allowed<p>");

        $.mask.definitions['~'] = '[+-]';
        $('#phone').mask('(999) 999-9999');

        jQuery.validator.addMethod("phone", function(value, element) {
            return this.optional(element) || /^\(\d{3}\) \d{3}\-\d{4}( x\d{1,6})?$/.test(value);
        }, "Enter a valid phone number.");

        // jQuery.validator.addMethod("cust_id", function(value, element) {
        // return $('input[name=cust_id]').val();
        // }, 'Please enter a valid Name.');

        $cust_id = $("#id").val();


        $('#modal-wizard-container').ace_wizard();
        $('#modal-wizard .wizard-actions .btn[data-dismiss=modal]').removeAttr('disabled');





        $(document).one('ajaxloadstart.page', function(e) {
            //in ajax mode, remove remaining elements before leaving page
            $('[class*=select2]').remove();
        });
    })
    $(document).ready(function() {
        $('#cust_id').on('change', function() {
            var j_cust_id = $(this).val();
            if (j_cust_id) {
                $.ajax({
                    type: 'POST',
                    url: 'get_journals_all.php',
                    data: 'j_cust_id=' + j_cust_id,
                    success: function(html) {
                        $('#j_id').html(html);
                    }
                });
            } else {
                $('#j_id').html('<option value="">Select customer first</option>');

            }
        });
    });
    </script>
    <script>
    $('#validation-form').submit(function() {
        var cust_name = $("input[name='cust_name']").val();
        var cust_id = $("input[name='cust_id']").val();


        var returnval = true;
        $.ajax({
            url: 'Ajax_customer.php',
            async: false,
            type: 'POST',
            data: {
                cust_name: cust_name,
                cust_id: cust_id
            },
            context: this,
            success: function(result) {
                if (result == 1) {
                    returnval = true;
                } else if (result == 0) {
                    $("#successMessage").show();
                    returnval = false;
                } else if (result == 2) {
                    returnval = true;
                }


            }
        });
        return returnval;

    });
    </script>
<?php fu_foot('#validation-form', null, array(), null, fu_ui_basic('Add / Edit Customer Invoice Details', 'Add / Edit Customer Invoice Details &rsaquo; Add')); ?>
  </body>

</html>
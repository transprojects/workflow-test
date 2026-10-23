<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php

/// Add New record
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    foreach ($_POST as $key => $value) {
        $post[$key] = filter($value);
    }
	$vall = isset($_SESSION['user_id']) && $_SESSION['user_id'] ? $_SESSION['user_id'] : 0;
    $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : 0;
   $insert_query = "INSERT INTO `company_details` (`invoice_name`,`company_name`,`address_line_1` ,`address_line_2`,`city` ,`state` ,`country` ,`zip` ,`phone` , `email` , `pan_number` , `cin_number` , `gstin_number` ,`beneficiary_name` ,`beneficiay_account_no`,`bank_name` ,`bank_swift_code` ,`bank_ifsc` ,correspondance_bank_details ,correspondance_bank_name ,correspondance_bank_swift_code,inv_type_of_project,inv_invoice_detail,inv_import_export_code_no,inv_satellite_station,inv_destination,inv_purchase_order_details,`created_by`, `created_on`) VALUES ('" . $post['invoice_name'] . "', '" . $post['company_name'] . "', '" . $post['address_line_1'] . "' ,'" . $post['address_line_2'] . "', '" . $post['city'] . "', '" . $post['state'] . "', '" . $post['country'] . "', '" . $post['zip'] . "', '" . $post['phone'] . "',  '" . $post['email'] . "',  '" . $post['pan_number'] . "',  '" . $post['cin_number'] . "',  '" . $post['gstin_number'] . "', '" . $post['beneficiary_name'] . "', '" . $post['beneficiay_account_no'] . "', ('" . $bank_name . "'!='0' OR NULL), '" . $post['bank_swift_code'] . "', '" . $post['bank_ifsc'] . "', '" . $post['correspondance_bank_details'] . "',  '" . $post['correspondance_bank_name'] . "',  '" . $post['correspondance_bank_swift_code'] . "', '" . $post['inv_type_of_project'] . "', '" . $post['inv_invoice_detail'] . "', '" . $post['inv_import_export_code_no'] . "', '" . $post['inv_satellite_station'] . "', '" . $post['inv_destination'] . "', '" . $post['inv_purchase_order_details'] . "', (" . $vall . "!=0 OR NULL), CURRENT_TIMESTAMP)";
   $db->query($insert_query) or die($db->error);

    // Change-log entry + email for the new record
    log_row_added('Company Details', change_log_ref(array('company_name','invoice_name')), array_diff_key($_POST, array('action'=>1)), array());
    header("location:company_details.php?msg=Successfully added");
    die();
}

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    foreach ($_POST as $key => $value) {
        $post[$key] = filter($value);
    }
    $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : 0;
     $update_query = "UPDATE company_details SET `invoice_name` ='" . $post['invoice_name'] . "',`company_name` ='" . $post['company_name'] . "',`address_line_1` ='" . $post['address_line_1'] . "',`address_line_2` ='" . $post['address_line_2'] . "',`city` ='" . $post['city'] . "',`state` ='" . $post['state'] . "',`country` ='" . $post['country'] . "',`zip` ='" . $post['zip'] . "',`phone` ='" . $post['phone'] . "', `email` ='" . $post['email'] . "', `pan_number` ='" . $post['pan_number'] . "', `cin_number` ='" . $post['cin_number'] . "', `gstin_number` ='" . $post['gstin_number'] . "',`beneficiary_name` ='" . $post['beneficiary_name'] . "',`beneficiay_account_no` ='" . $post['beneficiay_account_no'] . "',`bank_name` =('" . $bank_name . "'!='0' OR NULL),`bank_swift_code` ='" . $post['bank_swift_code'] . "',`bank_ifsc` ='" . $post['bank_ifsc'] . "',correspondance_bank_details ='" . $post['correspondance_bank_details'] . "',correspondance_bank_name = '" . $post['correspondance_bank_name'] . "',correspondance_bank_swift_code= '" . $post['correspondance_bank_swift_code'] . "',inv_type_of_project='" . $post['inv_type_of_project'] . "',inv_invoice_detail='" . $post['inv_invoice_detail'] . "',inv_import_export_code_no='" . $post['inv_import_export_code_no'] . "',inv_satellite_station='" . $post['inv_satellite_station'] . "',inv_destination='" . $post['inv_destination'] . "',inv_purchase_order_details='" . $post['inv_purchase_order_details'] . "' WHERE id = " . $_REQUEST['id'] . " ";
    
    if ($db->query($update_query)) {
        header("location:company_details.php?msg=Successfully completed");
        die();
    } else {
        $err[] = "Please try again.";
    }
}
///////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
    $result = $db->query("SELECT * FROM company_details WHERE id = " . (int)$_REQUEST['id'] . " ");
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
                    <p class="articleheading ms-sm-2 ms-lg-5">Add / Edit New Company Details</p>
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
<?php fu_hero(fu_ui_basic('Add / Edit New Company Details', 'Add / Edit New Company Details &rsaquo; Add')); ?>
      <div class="fu-wrap"><div class="fu-card">

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
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Invoice
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="invoice_name" name="invoice_name"
                                                                class="form-control"
                                                                value="<?php echo $app_res['invoice_name']; ?>" />
                                                            <div class="alert alert-warning" id="successMessage"
                                                                style="display:none;color: #a94442;
                                                                                 font-family: 'Open Sans';font-size: 13px;background-color: #fff; border-color: #fff;position: absolute; visibility: visible; right: 130px; top: -28%; height: 100px; width: 450px; z-index: 200;">
                                                                Name Already Exist</div>

                                                            <input type="hidden" id="company_id" name="company_id"
                                                                class="form-control"
                                                                value="<?php echo $app_res['id']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Company
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="company_name" name="company_name"
                                                                class="form-control"
                                                                value="<?php echo $app_res['company_name']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Address
                                                        Line 1:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="address_line_1" name="address_line_1"
                                                                class="form-control"
                                                                value="<?php echo $app_res['address_line_1']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Address
                                                        Line 2:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="address_line_2" name="address_line_2"
                                                                class="form-control"
                                                                value="<?php echo $app_res['address_line_2']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">City:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="city" name="city"
                                                                class="form-control"
                                                                value="<?php echo $app_res['city']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">State:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="state" name="state"
                                                                class="form-control"
                                                                value="<?php echo $app_res['state']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">Zip:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="zip" name="zip" class="form-control"
                                                                value="<?php echo $app_res['zip']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">Country:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="country" name="country"
                                                                class="form-control"
                                                                value="<?php echo $app_res['country']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="email">Email
                                                        Address:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="email" name="email" id="email"
                                                                class="form-control"
                                                                value="<?php echo $app_res['email']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>



                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="phone">Phone
                                                        Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="input-group"> <span class="input-group-addon">
                                                                <i class="ace-icon fa fa-phone"></i>
                                                            </span>
                                                            <input type="tel" id="phone" name="phone"
                                                                value="<?php echo $app_res['phone']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="hr hr-dotted"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="pan_number">PAN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="pan_number" class="form-control"
                                                                name="pan_number"
                                                                value="<?php echo $app_res['pan_number']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="cin_number">CIN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="cin_number" class="form-control"
                                                                name="cin_number"
                                                                value="<?php echo $app_res['cin_number']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="gstin_number">GSTIN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="gstin_number" class="form-control"
                                                                name="gstin_number"
                                                                value="<?php echo $app_res['gstin_number']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="hr hr-dotted"></div>
                                                <h3 class="lighter block green" style="margin-left: 227px;">
                                                    Bank Details:</h3>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="beneficiary_name">Beneficiary
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="beneficiary_name"
                                                                class="form-control" name="beneficiary_name"
                                                                value="<?php echo $app_res['beneficiary_name']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="beneficiay_account_no">Beneficiay
                                                        Account No:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="beneficiay_account_no"
                                                                class="form-control" name="beneficiay_account_no"
                                                                value="<?php echo $app_res['beneficiay_account_no']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="bank_swift_code">Bank Swift
                                                        Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="bank_swift_code" class="form-control"
                                                                name="bank_swift_code"
                                                                value="<?php echo $app_res['bank_swift_code']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="bank_ifsc">bank IFSC Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="bank_ifsc" class="form-control"
                                                                name="bank_ifsc"
                                                                value="<?php echo $app_res['bank_ifsc']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="hr hr-dotted"></div>
                                                <h3 class="lighter block green" style="margin-left: 227px;">
                                                    Correspondance Bank
                                                    Details:</h3>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_details">Correspondance
                                                        Bank Details:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_details"
                                                                class="form-control" name="correspondance_bank_details"
                                                                value="<?php echo $app_res['correspondance_bank_details']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_name">Correspondance
                                                        Beneficiary Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_name"
                                                                class="form-control" name="correspondance_bank_name"
                                                                value="<?php echo $app_res['correspondance_bank_name']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_swift_code">Correspondance
                                                        Bank Swift Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_swift_code"
                                                                class="form-control"
                                                                name="correspondance_bank_swift_code"
                                                                value="<?php echo $app_res['correspondance_bank_swift_code']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-2"></div>
                                                <div class="hr hr-dotted"></div>
                                                <h3 class="lighter block green" style="margin-left: 227px;">
                                                    Project Details:
                                                </h3>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_type_of_project">Type of
                                                        Project:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_type_of_project"
                                                                class="form-control" name="inv_type_of_project"
                                                                value="<?php echo $app_res['inv_type_of_project']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_invoice_detail">Invoice
                                                        Detail:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_invoice_detail"
                                                                class="form-control" name="inv_invoice_detail"
                                                                value="<?php echo $app_res['inv_invoice_detail']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_import_export_code_no">Import
                                                        Export Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_import_export_code_no"
                                                                class="form-control" name="inv_import_export_code_no"
                                                                value="<?php echo $app_res['inv_import_export_code_no']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_satellite_station">Satellite
                                                        Station:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_satellite_station"
                                                                class="form-control" name="inv_satellite_station"
                                                                value="<?php echo $app_res['inv_satellite_station']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_destination">Destination:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_destination" class="form-control"
                                                                name="inv_destination"
                                                                value="<?php echo $app_res['inv_destination']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_purchase_order_details">Purchase
                                                        Order Details:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_purchase_order_details"
                                                                class="form-control" name="inv_purchase_order_details"
                                                                value="<?php echo $app_res['inv_purchase_order_details']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>



                                                <div class="meta_nxtbtn container-fluid mt-5 mb-5">
                                                    <div class="row">
                                                        <input type="hidden" name="action" value="update">
                                                        <div class="col form_btn cancelbtn d-flex justify-content-end">
                                                            <button type="submit" class="btn-next-cancel"><a
                                                                    href="company_details.php" class="btn btn-grey">
                                                                    <i class="ace-icon fa fa-arrow-left"></i>
                                                                    Cancel </a></button>
                                                        </div>
                                                        <div class="col form_btn nxtbtn">
                                                            <button type="submit" class="btn-next"> Save
                                                                <i class="ace-icon fa fa-arrow-right icon-on-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </form>
                                            <?php } else { ?>
                                            <form class="form-horizontal" id="validation-form" method="POST">

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Invoice
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="invoice_name" name="invoice_name"
                                                                class="form-control" value="" />
                                                            <div class="alert alert-warning" id="successMessage"
                                                                style="display:none;color: #a94442;
                                                                                 font-family: 'Open Sans';font-size: 13px;background-color: #fff; border-color: #fff;position: absolute; visibility: visible; right: 130px; top: -28%; height: 100px; width: 450px; z-index: 200;">
                                                                Name Already Exist</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Company
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="company_name" name="company_name"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Address
                                                        Line 1:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="address_line_1" name="address_line_1"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="name">Address
                                                        Line 2:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="address_line_2" name="address_line_2"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">City:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="city" name="city"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">State:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="state" name="state"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">Zip:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="zip" name="zip" class="form-control"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="name">Country:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="country" name="country"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="email">Email
                                                        Address:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="email" name="email" id="email"
                                                                class="form-control" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>



                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 " for="phone">Phone
                                                        Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="input-group"> <span class="input-group-addon">
                                                                <i class="ace-icon fa fa-phone"></i>
                                                            </span>
                                                            <input type="tel" id="phone" name="phone" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="hr hr-dotted"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="pan_number">PAN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="pan_number" class="form-control"
                                                                name="pan_number" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="cin_number">CIN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="cin_number" class="form-control"
                                                                name="cin_number" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="gstin_number">GSTIN Number:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="gstin_number" class="form-control"
                                                                name="gstin_number" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="hr hr-dotted"></div>
                                                <div class="formcont1heading mt-3 mb-3">Bank Details :</div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="beneficiary_name">Beneficiary
                                                        Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="beneficiary_name"
                                                                class="form-control" name="beneficiary_name" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="beneficiay_account_no">Beneficiay
                                                        Account No:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="beneficiay_account_no"
                                                                class="form-control" name="beneficiay_account_no"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="bank_swift_code">Bank Swift
                                                        Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="bank_swift_code" class="form-control"
                                                                name="bank_swift_code" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="bank_ifsc">bank IFSC Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="bank_ifsc" class="form-control"
                                                                name="bank_ifsc" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="hr hr-dotted"></div>
                                                <div class="formcont1heading mt-3 mb-3">Correspondance
                                                    Bank Details:</div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_details">Correspondance
                                                        Bank Details:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_details"
                                                                class="form-control" name="correspondance_bank_details"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_name">Correspondance
                                                        Beneficiary Name:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_name"
                                                                class="form-control" name="correspondance_bank_name"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="correspondance_bank_swift_code">Correspondance
                                                        Bank Swift Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="correspondance_bank_swift_code"
                                                                class="form-control"
                                                                name="correspondance_bank_swift_code" value="" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-2"></div>
                                                <div class="hr hr-dotted"></div>
                                                <div class="formcont1heading mt-3 mb-3">Project Details:</div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_type_of_project">Type of
                                                        Project:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_type_of_project"
                                                                class="form-control" name="inv_type_of_project"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_invoice_detail">Invoice
                                                        Detail:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_invoice_detail"
                                                                class="form-control" name="inv_invoice_detail"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>

                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_import_export_code_no">Import
                                                        Export Code:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_import_export_code_no"
                                                                class="form-control" name="inv_import_export_code_no"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_satellite_station">Satellite
                                                        Station:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_satellite_station"
                                                                class="form-control" name="inv_satellite_station"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_destination">Destination:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_destination" class="form-control"
                                                                name="inv_destination" value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-2"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-xs-12 col-sm-3 "
                                                        for="inv_purchase_order_details">Purchase
                                                        Order Details:</label>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="clearfix">
                                                            <input type="text" id="inv_purchase_order_details"
                                                                class="form-control" name="inv_purchase_order_details"
                                                                value="" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="meta_nxtbtn container-fluid mt-5 mb-5">
                                                    <div class="row">
                                                        <input type="hidden" name="action" value="insert">
                                                        <div class="col form_btn cancelbtn d-flex justify-content-end">
                                                            <button type="submit" class="btn-next-cancel"><a
                                                                    href="company_details.php" class="btn btn-grey">
                                                                    <i class="ace-icon fa fa-arrow-left"></i>
                                                                    Cancel </a></button>
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

<?php fu_foot('#validation-form', null, array(), null, fu_ui_basic('Add / Edit New Company Details', 'Add / Edit New Company Details &rsaquo; Add')); ?>
  </body>

</html>
<?php include "includes/includes.php";?>
<?php
include_once "includes/change_log_mail.php";
require_once __DIR__.'/includes/form_ui.php';
$fu_errors = array();
if (isset($_POST['action']) && $_POST['action'] == 'update') {

    // Copy POST safely
    $post = [];
    foreach ($_POST as $key => $value) {
        $post[$key] = trim($value);
    }

    // Server-side validation: every problem comes back as a popup on the form (nothing is saved)
    $fu_errors = fu_validate($post, fu_rules_customer());
    if (!$fu_errors) {

    // Auto-fix integer fields (empty => 0)
    $int_fields = [
        'parent_cust_id', 'catg_id', 'zip', 'due_date_no_of_days',
        'due_date_no_of_first_proof', 'due_date_no_of_revises',
        'due_date_no_of_final', 'ftp_receive', 'ftp_send',
        'c_fp_wf','c_rev_wf','c_fin_wf'
    ];

    foreach ($int_fields as $f) {
        if (!isset($post[$f]) || $post[$f] === '') {
            $post[$f] = 0;
        }
    }

    // Password update only if entered
    $password_update = "";
    if (!empty($post['password'])) {
        $password_update = "`password` = '" . $post['password'] . "',";
    }

    // Upload directory
    $targetDir = "uploads/customer-spec/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $customerSpecFile = "";

    // File upload
    if (isset($_FILES['customer_spec']) && $_FILES['customer_spec']['error'] == 0) {

        $fileName = basename($_FILES['customer_spec']['name']);
        $fileTmpPath = $_FILES['customer_spec']['tmp_name'];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        // Rename file
        $newName = uniqid() . "." . $ext;
        $targetFile = $targetDir . $newName;

        if (move_uploaded_file($fileTmpPath, $targetFile)) {
            $customerSpecFile = $newName;
        }
    }

    // If no new upload → keep old file
    if ($customerSpecFile == "") {
        $customerSpecFile = $post['existing_customer_spec'] ?? '';
    }

    // Snapshot the existing values (before change) for the change-log email
    $log_cust_id = (int) $post['id'];
    $res_old = $db->query("SELECT * FROM adm_customer_master WHERE id = " . $log_cust_id);
    $old_c   = ($res_old && $res_old->num_rows > 0) ? $res_old->fetch_assoc() : [];

    // Build update query
    $update_query = "
        UPDATE adm_customer_master SET
            parent_cust_id = '".$post['parent_cust_id']."',
            cust_name = '".$post['cust_name']."',
            catg_id = '".$post['catg_id']."',
            address_line_1 = '".$post['address_line_1']."',
            address_line_2 = '".$post['address_line_2']."',
            city = '".$post['city']."',
            state = '".$post['state']."',
            country = '".$post['country']."',
            zip = '".$post['zip']."',
            phone = '".$post['phone']."',
            url = '".$post['url']."',
            email = '".$post['email']."',
            $password_update
            due_date_no_of_days = '".$post['due_date_no_of_days']."',
            due_date_no_of_first_proof = '".$post['due_date_no_of_first_proof']."',
            due_date_no_of_revises = '".$post['due_date_no_of_revises']."',
            due_date_no_of_final = '".$post['due_date_no_of_final']."',
            ftp_host_name = '".$post['ftp_host_name']."',
            ftp_user_name = '".$post['ftp_user_name']."',
            ftp_password = '".$post['ftp_password']."',
            ftp_access_dir = '".$post['ftp_access_dir']."',
            delivery_options = '".$post['delivery_options']."',
            ftp_receive = '".$post['ftp_receive']."',
            ftp_send = '".$post['ftp_send']."',
            ftp_send_host = '".$post['ftp_send_host']."',
            ftp_send_user = '".$post['ftp_send_user']."',
            ftp_send_password = '".$post['ftp_send_password']."',
            ftp_send_dir = '".$post['ftp_send_dir']."',
            ftp_send_targetfolder = '".$post['ftp_send_targetfolder']."',
            j_email_receive = '".$post['j_email_receive']."',
            j_email_send = '".$post['j_email_send']."',
            j_from_email = '".$post['j_from_email']."',
            j_from_email_pwd = '".$post['j_from_email_pwd']."',
            j_to_email = '".$post['j_to_email']."',
            j_cc_email = '".$post['j_cc_email']."',
            j_bcc_email = '".$post['j_bcc_email']."',
            reply_to = '".$post['reply_to']."',
            reply_name = '".$post['reply_name']."',
            modified_by = '".$_COOKIE['user_id']."',
            modified_dt = CURRENT_TIMESTAMP,
            c_fp_wf = '".$post['c_fp_wf']."',
            c_rev_wf = '".$post['c_rev_wf']."',
            c_fin_wf = '".$post['c_fin_wf']."',
            font_color = '".$post['font_color']."',
            customer_spec = '".$customerSpecFile."'
        WHERE id = ".$post['id']."
    ";

    // Execute update
    if ($db->query($update_query)) {
        // Email the change log (text attachment); a mail failure must not block the save
        try {
            $new_c = $post;
            $new_c['customer_spec'] = $customerSpecFile;
            if (empty($post['password'])) {
                unset($new_c['password']); // password is only updated when entered
            }
            // Password fields: log that they changed, never the values
            $secret_fields = ['password', 'ftp_password', 'ftp_send_password', 'j_from_email_pwd'];
            $skip_fields   = ['action', 'id'];
            $c_log = "[Customer changes]\r\n";
            $c_changes = 0;
            foreach ($new_c as $f => $new) {
                if (in_array($f, $skip_fields) || !array_key_exists($f, $old_c)) {
                    continue; // not a column of adm_customer_master
                }
                $old = (string) $old_c[$f];
                if ($old !== (string) $new) {
                    if (in_array($f, $secret_fields)) {
                        $c_log .= "  $f: (changed - value hidden)\r\n";
                    } else {
                        $c_log .= "  $f: '$old' -> '$new'\r\n";
                    }
                    $c_changes++;
                }
            }
            if ($c_changes == 0) {
                $c_log .= "  (no change)\r\n";
            }
            send_change_log_mail('Customer', $log_cust_id, $c_log, $c_changes);
        } catch (Exception $e) {
            error_log("edit-customer change-log mail failed: " . $e->getMessage());
        }
        header("Location: " . $_COOKIE['set_url'] . "/customers?msg=Successfully completed");
        exit();
    } else {
        $fu_errors[] = array('field' => '', 'label' => 'Database error:', 'msg' => $db->error);
    }
    }
}

///////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
  $result = $db->query("SELECT * FROM adm_customer_master WHERE id = " . $_REQUEST['id'] . " ");
  $row_cnt = 1;
  $toatl_count = $result->num_rows;
  if ($toatl_count > 0) {
      $app_res = $result->fetch_assoc();
  }
}
if ($fu_errors && isset($app_res)) { $app_res = fu_keep_posted($app_res, $_POST); }   /* keep what was typed */
?>
<?php
$cat_info = array();
$query = "SELECT cat_id,catg_name FROM `adm_cust_category_master` ORDER BY `catg_name` ASC";
$categories = $db->query($query) or ( $db->error);
while ($row = $categories->fetch_assoc()) {
$cat_info[] = $row;
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

<?php fu_head('edit-customer'); ?>
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
            <p class="articleheading ms-4">Edit Customers</p>
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

<?php fu_hero(fu_ui_customer('Edit Customer', 'Customer &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit-customer" id="edit-customer" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Edit Customer</div>
              <div class="row mt-1 g-4 addspacing">
			  
			   <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Parent Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="parent_cust_id" id="parent_cust_id" required>
                    <option value="">Select Parent Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
													 <option value="<?php echo $res_customers['id'] ?>" <?php if($app_res['parent_cust_id'] == $res_customers['id']){?> selected <?php } ?> style="font-weight:500;" data-parent="<?php echo $res_customers['parent_cust_id'] ?>"><?php echo $res_customers['cust_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				
				
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" placeholder="Enter Customer Name" value="<?php echo $app_res['cust_name']; ?>" required>
                </input>
                </div>
       
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Category<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="catg_id" id="catg_id" required>
                  <option value="">Select Category</option>

                  <?php foreach ($cat_info as $catg) { ?>
                                                                                    <option value="<?php echo $catg['cat_id']; ?>" <?php if ($app_res['catg_id'] == $catg['cat_id']) { ?> selected <?php } ?>><?php echo $catg['catg_name']; ?></option>
                                                                                <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Email Address</label>
                 <input type="text" class="form-control" name="email" id="email" value="<?php echo $app_res['email']; ?>" placeholder="Enter email ID" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Password <span style="color: red"></span></label>
              <input type="text" class="form-control" name="password" id="artitle_title" value="<?php echo $app_res['password']; ?>" placeholder="Enter password" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Confirm Password <span style="color: red"></span></label>
              <input type="text" class="form-control" name="password" id="artitle_title" value="<?php echo $app_res['password']; ?>" placeholder="Re-enter password" required>
              </input>
                </div>
                  </div>            
             </div>
                <!-- < -->
            <!-- heading 2 -->
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add Address</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Address Line 1</label>
                  <input type="text" class="form-control" name="address_line_1" id="address_line_1" value="<?php echo $app_res['address_line_1']; ?>"   placeholder="Enter address">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Address Line 2</label>
                  <input type="text" class="form-control" name="address_line_2" id="address_line_2"  value="<?php echo $app_res['address_line_2']; ?>"  placeholder="Enter address">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">City</label>
                  <input type="text" class="form-control" name="city" id="city"  value="<?php echo $app_res['city']; ?>"  placeholder="City name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">State</label>
                  <input type="text" class="form-control" name="state" id="state"  value="<?php echo $app_res['state']; ?>"  placeholder="State name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Country</label>
                  <input type="text" class="form-control" name="country" id="country"  value="<?php echo $app_res['country']; ?>"  placeholder="Country name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Zip</label>
                  <input type="text" class="form-control" name="zip" id="zip"  value="<?php echo $app_res['zip']; ?>"  placeholder="Zip code">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Phone Number</label>
                  <input type="text" class="form-control" name="phone" id="phone"  value="<?php echo $app_res['phone']; ?>"  placeholder="Enter phone number">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Company URL</label>
                  <input type="text" class="form-control" name="url" id="url"  value="<?php echo $app_res['url']; ?>"  placeholder="Enter url">
              </input>
                </div>
				   </div>            
             </div>
            <!-- heading 3 -->
            <div class="container mt-4 separatetitle">
               <div class="formcont1heading">FTP To Receive</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Host Name <span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="ftp_host_name" id="ftp_host_name" placeholder="Enter host name"  value="<?php echo $app_res['ftp_host_name']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_user_name" id="ftp_user_name" placeholder="Enter user name"  value="<?php echo $app_res['ftp_user_name']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_password" id="ftp_password" value="<?php echo $app_res['ftp_password']; ?>" placeholder="Enter password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_receive_dir" id="ftp_receive_dir" value="<?php echo $app_res['ftp_receive_dir']; ?>" placeholder="Enter dir">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_access_dir" id="ftp_access_dir" value="<?php echo $app_res['ftp_access_dir']; ?>" placeholder="Enter folder name">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">FTP to receive :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox" name="ftp_receive" id="ftp_receive" placeholder=""  value="1" <?php if ($app_res['ftp_receive'] == 1) { ?> checked <?php } ?> >
                </div>   
                    </div>            
             </div>
             <div class="container mt-4 separatetitle">
               <div class="formcont1heading">FTP To Send</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Host Name <span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="ftp_send_host" id="ftp_send_host" placeholder="Enter host name"  value="<?php echo $app_res['ftp_send_host']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_send_user" id="ftp_send_user" placeholder="Enter user name"  value="<?php echo $app_res['ftp_send_user']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_send_password" id="ftp_send_password" value="<?php echo $app_res['ftp_send_password']; ?>" placeholder="Enter ftp_send_password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_send_dir" id="ftp_send_dir" value="<?php echo $app_res['ftp_send_dir']; ?>" placeholder="Enter dir">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_send_targetfolder" id="ftp_send_targetfolder" value="<?php echo $app_res['ftp_send_targetfolder']; ?>" placeholder="Enter folder name">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">FTP to send :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="ftp_send" id="ftp_send" placeholder=""  value="1" <?php if ($app_res['ftp_send'] == 1) { ?> checked <?php } ?>>
                </div>   
                    </div>            
             </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">From E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail</label>
                 <input type="text" class="form-control" name="j_from_email" id="j_from_email" value="<?php echo $app_res['j_from_email']; ?>" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail Password</label>
                 <input type="text" class="form-control" name="j_from_email_pwd" id="j_from_email_pwd" value="<?php echo $app_res['j_from_email_pwd']; ?>" placeholder="Enter password">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">From E-mail :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="j_email_receive" id="j_email_receive" placeholder=""  value="1" <?php if ($app_res['j_email_receive'] == 1) { ?> checked <?php } ?> >
                </div>
                    </div>
            </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">To E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">To E-mail Password</label>
                 <input type="text" class="form-control" name="j_to_email" id="j_to_email " value="<?php echo $app_res['j_to_email']; ?>" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">CC E-mail</label>
                 <input type="text" class="form-control" name="j_cc_email" id="j_cc_email_pwd" value="<?php echo $app_res['j_cc_email']; ?>" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">BCC E-mail</label>
                 <input type="text" class="form-control" name="j_bcc_email" id="j_bcc_email_pwd" value="<?php echo $app_res['j_bcc_email']; ?>" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay To</label>
                 <input type="text" class="form-control" name="reply_to" id="reply_to" value="<?php echo $app_res['reply_to']; ?>" placeholder="Enter reply to">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay Name</label>
                 <input type="text" class="form-control" name="reply_name" id="reply_name" value="<?php echo $app_res['reply_name']; ?>" placeholder="Enter name">
                </input>
                </div>
              
                    </div>
            </div>
            <div class="container mt-4">
              <div class="formcont1heading">Article Instructions</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">No of due date</label>
                 <input type="number" class="form-control" name="due_date_no_of_days" id="due_date_no_of_days" value="<?php echo $app_res['due_date_no_of_days']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Article Delivery Options</label>
                 <input type="text" class="form-control" name="delivery_options" id="delivery_options" value="<?php echo $app_res['delivery_options']; ?>" placeholder="Enter Article Delivery Options">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Font Color</label>
                 <input type="text" class="form-control" name="font_color" id="font_color" value="<?php echo $app_res['font_color']; ?>" placeholder="Enter Font Color">
                </input>
                </div>
				<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Customer Specification</label>
    <input type="file" class="form-control" name="customer_spec" id="customer_spec">
</div>

<?php if (!empty($app_res['customer_spec'])): ?>
    <div class="col-12 mt-2">
        <a href="<?php echo $_COOKIE['set_url'];?>/uploads/customer-spec/<?php echo $app_res['customer_spec']; ?>" download class="btn btn-primary">
            Download Current Specification
        </a>
    </div>
<?php endif; ?>

                    </div>
            </div>
                
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/customers" style="text-decoration:none;">Cancel</a></button>
                        
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
<?php fu_foot('#edit-customer', fu_rules_customer(), $fu_errors, null, fu_ui_customer('Edit Customer', 'Customer &rsaquo; Edit')); ?>

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
 


  </body>
</html>

<?php include "includes/includes.php";?>
<?php include_once "includes/change_log_mail.php";?>
<?php
require_once __DIR__.'/includes/form_ui.php';
$fu_errors = array();
?>
<?php
/// Add New record
if (isset($_POST['action']) && $_POST['action'] == 'insert') {

    // Copy POST safely
    $post = [];
    foreach ($_POST as $key => $value) {
        $post[$key] = trim($value);
    }

    // Server-side validation: every problem comes back as a popup on the form (nothing is saved)
    $fu_errors = fu_validate($post, fu_rules_customer());
    if (!$fu_errors) {

    // Auto-fix integer fields (empty → 0)
    $int_fields = [
        'parent_cust_id', 'catg_id', 'zip',
        'due_date_no_of_days', 'due_date_no_of_first_proof',
        'due_date_no_of_revises', 'due_date_no_of_final'
    ];

    foreach ($int_fields as $f) {
        if (!isset($post[$f]) || $post[$f] === '') {
            $post[$f] = 0;
        }
    }

    // Handle File Upload
    $targetDir = "uploads/customer-spec/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $customerSpecFile = '';

    if (isset($_FILES['customer_spec']) && $_FILES['customer_spec']['error'] == 0) {

        $fileName = basename($_FILES['customer_spec']['name']);
        $fileTmpPath = $_FILES['customer_spec']['tmp_name'];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        $newFileName = uniqid() . "." . $ext;
        $targetFile = $targetDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $targetFile)) {
            $customerSpecFile = $newFileName;
        }
    }

    // Insert Query
    $insert_query = "
        INSERT INTO adm_customer_master 
        (
            parent_cust_id, cust_name, cust_id, catg_id, address_line_1, address_line_2,
            city, state, country, zip, phone, url, email, password,
            due_date_no_of_days, due_date_no_of_first_proof, due_date_no_of_revises,
            due_date_no_of_final, ftp_host_name, ftp_user_name, ftp_password,
            ftp_receive_dir, ftp_access_dir, delivery_options,
            ftp_send_host, ftp_send_user, ftp_send_password, ftp_send_dir,
            ftp_send_targetfolder, j_email_receive, j_email_send,
            j_from_email, j_from_email_pwd, j_to_email, j_cc_email,
            j_bcc_email, reply_to, reply_name, created_by, created_dt,
            font_color, customer_spec
        )
        VALUES 
        (
            '".$post['parent_cust_id']."', '".$post['cust_name']."', 
            0, '".$post['catg_id']."', '".$post['address_line_1']."', '".$post['address_line_2']."',
            '".$post['city']."', '".$post['state']."', '".$post['country']."',
            '".$post['zip']."', '".$post['phone']."', '".$post['url']."',
            '".$post['email']."', '".$post['password']."',
            '".$post['due_date_no_of_days']."', '".$post['due_date_no_of_first_proof']."',
            '".$post['due_date_no_of_revises']."', '".$post['due_date_no_of_final']."',
            '".$post['ftp_host_name']."', '".$post['ftp_user_name']."',
            '".$post['ftp_password']."', '".$post['ftp_receive_dir']."',
            '".$post['ftp_access_dir']."', '".$post['delivery_options']."',
            '".$post['ftp_send_host']."', '".$post['ftp_send_user']."',
            '".$post['ftp_send_password']."', '".$post['ftp_send_dir']."',
            '".$post['ftp_send_targetfolder']."',
            '".$post['j_email_receive']."', '".$post['j_email_send']."',
            '".$post['j_from_email']."', '".$post['j_from_email_pwd']."',
            '".$post['j_to_email']."', '".$post['j_cc_email']."',
            '".$post['j_bcc_email']."', '".$post['reply_to']."',
            '".$post['reply_name']."', '".$_COOKIE['user_id']."',
            CURRENT_TIMESTAMP, '".$post['font_color']."',
            '".$customerSpecFile."'
        )
    ";

    if (!$db->query($insert_query)) {
        $fu_errors[] = array('field' => '', 'label' => 'Database error:', 'msg' => $db->error);
    } else {

    // Change-log entry + email for the new record
    log_row_added('Customer', change_log_ref(array('cust_name')), array_diff_key($_POST, array('action'=>1)), array('password','ftp_password','ftp_send_password','j_from_email_pwd'));
    header("Location: ".$_COOKIE['set_url']."/customers?msg=Successfully Added");
    exit();
    }
    }
}


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

<?php fu_head('add-customer'); ?>
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
            <p class="articleheading ms-4">Add Customers</p>
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

<?php fu_hero(fu_ui_customer('Add Customer', 'Customer &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-customer" id="add-customer" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add Customer</div>
              <div class="row mt-1 g-4 addspacing">
			  
			   <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Parent Customer</label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="parent_cust_id" id="parent_cust_id" required>
                    <option value="">Select Parent Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				
				
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" value="" placeholder="Enter Customer Name" required>
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
                 <input type="text" class="form-control" name="email" id="email" value="-" placeholder="Enter email ID" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Password <span style="color: red"></span></label>
              <input type="text" class="form-control" name="password" id="artitle_title" value="-" placeholder="Enter password" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Confirm Password <span style="color: red"></span></label>
              <input type="text" class="form-control" name="password" id="artitle_title" value="-" placeholder="Re-enter password" required>
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
                  <input type="text" class="form-control" name="address_line_1" id="address_line_1" required  value="-" placeholder="Enter address">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Address Line 2</label>
                  <input type="text" class="form-control" name="address_line_2" id="address_line_2" required  value="-" placeholder="Enter address">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">City</label>
                  <input type="text" class="form-control" name="city" id="city" required  value="-" placeholder="City name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">State</label>
                  <input type="text" class="form-control" name="state" id="state" required  value="-" placeholder="State name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Country</label>
                  <input type="text" class="form-control" name="country" id="country" required  value="-" placeholder="Country name">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Zip</label>
                  <input type="text" class="form-control" name="zip" id="zip" required  value="-" placeholder="Zip code">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Phone Number</label>
                  <input type="text" class="form-control" name="phone" id="phone" required  value="-" placeholder="Enter phone number">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Company URL</label>
                  <input type="text" class="form-control" name="url" id="url" required  value="-" placeholder="Enter url">
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
                  <input type="text" class="form-control" name="ftp_host_name" id="ftp_host_name" value="-" placeholder="Enter host name" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_user_name" id="ftp_user_name" value="-" placeholder="Enter user name" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_password" id="ftp_password" value="-" placeholder="Enter password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_receive_dir" id="ftp_receive_dir" value="-" placeholder="Enter dir">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_access_dir" id="ftp_access_dir" value="-" placeholder="Enter folder name">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">FTP to receive :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="lbl" id="lbl" placeholder="">
                </div>   
                    </div>            
             </div>
             <div class="container mt-4 separatetitle">
               <div class="formcont1heading">FTP To Send</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Host Name <span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="ftp_host_name" id="ftp_host_name" value="-" placeholder="Enter host name" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_user_name" id="ftp_user_name" value="-" placeholder="Enter user name" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_password" id="ftp_password" value="-" placeholder="Enter password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_receive_dir" id="ftp_receive_dir" value="-" placeholder="Enter dir">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_access_dir" id="ftp_access_dir" value="-" placeholder="Enter folder name">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">FTP to send :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="lbl" id="lbl"  placeholder="">
                </div>   
                    </div>            
             </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">From E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail</label>
                 <input type="text" class="form-control" name="j_from_email" id="j_from_email" value="-" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail Password</label>
                 <input type="text" class="form-control" name="j_from_email_pwd" id="j_from_email_pwd" value="-" placeholder="Enter password">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">From E-mail :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="lbl" id="lbl">
                </div>
                    </div>
            </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">To E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">To E-mail Password</label>
                 <input type="text" class="form-control" name="j_to_email" id="j_to_email " value="-" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">CC E-mail</label>
                 <input type="text" class="form-control" name="j_cc_email" id="j_cc_email_pwd" value="-" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">BCC E-mail</label>
                 <input type="text" class="form-control" name="j_bcc_email" id="j_bcc_email_pwd" value="-" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay To</label>
                 <input type="text" class="form-control" name="reply_to" id="reply_to" value="-" placeholder="Enter reply to">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay Name</label>
                 <input type="text" class="form-control" name="reply_name" id="reply_name" value="-" placeholder="Enter name">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">To E-mail :&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="clearfix" id="clearfix" required>
                </div>
                    </div>
            </div>
            <div class="container mt-4">
              <div class="formcont1heading">Article Instructions</div>
              <div class="row mt-2 g-4">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">No of due date</label>
                 <input type="number" class="form-control" name="due_date_no_of_days" id="due_date_no_of_days" value="0">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Article Delivery Options</label>
                 <input type="text" class="form-control" name="delivery_options" id="delivery_options" value="-" placeholder="Enter email">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Font Color</label>
                 <input type="text" class="form-control" name="font_color" id="font_color" value="-" placeholder="Enter email">
                </input>
                </div>
                    </div>
            </div>
                
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/add-article" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="insert">

                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Next</button>
                    </div>
                </div>
            </div>
         </form>
      </div>
    </div>
   
      
      <?php echo scripts(); ?>	
<?php fu_foot('#add-customer', fu_rules_customer(), $fu_errors, $_POST, fu_ui_customer('Add Customer', 'Customer &rsaquo; Add')); ?>

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
   
  </body>
</html>

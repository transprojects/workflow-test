<?php include "includes/includes.php";?>
<?php
include_once "includes/change_log_mail.php";
require_once __DIR__.'/includes/form_ui.php';
$fu_errors = array();
if (isset($_POST['action']) && $_POST['action'] == 'update')
{
    // Copy POST safely
    $post = [];
    foreach ($_POST as $key => $value) {
        $post[$key] = trim($value);
    }

    // Server-side validation: every problem comes back as a popup on the form (nothing is saved)
    $fu_errors = fu_validate($post, fu_rules_journal(false));
    if (!$fu_errors) {

    // Auto-fix integer columns => empty = 0
    $int_fields = [
        'j_hid', 'j_parent_cust_id', 'j_cust_id', 'jt_id',
        'ftp_receive', 'ftp_send', 'j_due_fduration',
        'j_due_rduration', 'j_due_fiduration', 'j_due_isduration',
        'j_due_isfduration', 'j_due_jsduration', 'j_due_pmcduration', 'j_reminder_1', 'j_reminder_2',
        'j_reminder_3', 'j_proofing_system_server',
        'j_complexity', 'is_mathjournal'
    ];

    foreach ($int_fields as $f) {
        if (!isset($post[$f]) || $post[$f] === '') {
            $post[$f] = 0;
        }
    }

    // Platform mapping
    $j_platform_value = $platform_journal_array[$post['j_platform']] ?? $post['j_platform'];

    // Snapshot the existing values (before change) for the change-log email
    $log_j_id = (int) $post['id'];
    $res_old  = $db->query("SELECT * FROM adm_journals WHERE j_id = " . $log_j_id);
    $old_j    = ($res_old && $res_old->num_rows > 0) ? $res_old->fetch_assoc() : [];

    // Build update query
    $update_query = "
        UPDATE adm_journals SET
            j_hid = '".$post['j_hid']."',
            j_parent_cust_id = '".$post['j_parent_cust_id']."',
            j_cust_id = '".$post['j_cust_id']."',
            j_name = '".$post['j_name']."',
            j_code = '".$post['j_code']."',
            j_platform = '".$j_platform_value."',
            j_division = '".$post['j_division']."',
            jt_id = '".$post['jt_id']."',
            ftp_receive = '".$post['ftp_receive']."',
            ftp_receive_host = '".$post['ftp_receive_host']."',
            ftp_receive_username = '".$post['ftp_receive_username']."',
            ftp_receive_password = '".$post['ftp_receive_password']."',
            ftp_receive_dir = '".$post['ftp_receive_dir']."',
            ftp_receive_targetfolder = '".$post['ftp_receive_targetfolder']."',
            ftp_send = '".$post['ftp_send']."',
            ftp_send_host = '".$post['ftp_send_host']."',
            ftp_send_user = '".$post['ftp_send_user']."',
            ftp_send_password = '".$post['ftp_send_password']."',
            ftp_send_dir = '".$post['ftp_send_dir']."',
            ftp_send_targetfolder = '".$post['ftp_send_targetfolder']."',
            j_email_receive = '".$post['j_email_receive']."',
            j_email_send = '".$post['j_email_send']."',
            j_from_email = '".$post['j_from_email']."',
            j_to_email = '".$post['j_to_email']."',
            j_cc_email = '".$post['j_cc_email']."',
            reply_to = '".$post['reply_to']."',
            reply_name = '".$post['reply_name']."',
            j_fp_wf = '".$post['j_fp_wf']."',
            j_fin_wf = '".$post['j_fin_wf']."',
            j_rev_wf = '".$post['j_rev_wf']."',
            j_issue_wf = '".$post['j_issue_wf']."',
            j_from_email_pwd = '".$post['j_from_email_pwd']."',
            j_bcc_email = '".$post['j_bcc_email']."',
            j_fin_issue_wf = '".$post['j_fin_issue_wf']."',
            j_due_fduration = '".$post['j_due_fduration']."',
            j_due_rduration = '".$post['j_due_rduration']."',
            j_due_fiduration = '".$post['j_due_fiduration']."',
            j_due_isduration = '".$post['j_due_isduration']."',
            j_due_isfduration = '".$post['j_due_isfduration']."',
            j_jstage_wf = '".$post['j_jstage_wf']."',
            j_due_jsduration = '".$post['j_due_jsduration']."',
            j_pmc_wf = '".$post['j_pmc_wf']."',
            j_due_pmcduration = '".$post['j_due_pmcduration']."',
            j_reminder_1 = '".$post['j_reminder_1']."',
            j_reminder_2 = '".$post['j_reminder_2']."',
            j_reminder_3 = '".$post['j_reminder_3']."',
            reminder_mail_to = '".$post['reminder_mail_to']."',
            j_abbreviation = '".$post['j_abbreviation']."',
            j_proofing_system_server = '".$post['j_proofing_system_server']."',
            j_complexity = '".$post['j_complexity']."',
            is_mathjournal = '".$post['is_mathjournal']."'
        WHERE j_id = ".$post['id']."
    ";

    // Execute update
    if ($db->query($update_query)) {
        // Email the change log (text attachment); a mail failure must not block the save
        try {
            $new_j = $post;
            $new_j['j_platform'] = $j_platform_value;
            // Password fields: log that they changed, never the values
            $secret_fields = ['ftp_receive_password', 'ftp_send_password', 'j_from_email_pwd'];
            $skip_fields   = ['action', 'id'];
            $j_log = "[Journal changes]\r\n";
            $j_changes = 0;
            foreach ($new_j as $f => $new) {
                if (in_array($f, $skip_fields) || !array_key_exists($f, $old_j)) {
                    continue; // not a column of adm_journals
                }
                $old = (string) $old_j[$f];
                if ($old !== (string) $new) {
                    if (in_array($f, $secret_fields)) {
                        $j_log .= "  $f: (changed - value hidden)\r\n";
                    } else {
                        $j_log .= "  $f: '$old' -> '$new'\r\n";
                    }
                    $j_changes++;
                }
            }
            if ($j_changes == 0) {
                $j_log .= "  (no change)\r\n";
            }
            send_change_log_mail('Journal', $log_j_id, $j_log, $j_changes);
        } catch (Exception $e) {
            error_log("edit-journal change-log mail failed: " . $e->getMessage());
        }
        header("Location: ".$_COOKIE['set_url']."/managejournals?msg=Successfully completed");
        exit();
    } else {
        $fu_errors[] = array('field' => '', 'label' => 'Database error:', 'msg' => $db->error);
    }
    }
}

/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM adm_journals WHERE j_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}
if ($fu_errors && isset($app_res)) { $app_res = fu_keep_posted($app_res, $_POST); }   /* keep what was typed */

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

<?php fu_head('edit_journal'); ?>
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


      <!-- form section  -->
      <div class="container-fluid mt-4">

      <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Edit Journal</p>
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
<?php fu_hero(fu_ui_journal('Edit Journal', 'Journal &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">

          <form action="" method="post" name="edit_journal" id="edit_journal" enctype="multipart/form-data">


          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo $_REQUEST['id'];?>">



            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Edit Journals</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="j_cust_id" id="j_cust_id" required>
                    <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
													 <option value="<?php echo $res_customers['id'] ?>" <?php if($app_res['j_cust_id'] == $res_customers['id']){?> selected <?php } ?> style="font-weight:500;" data-parent="<?php echo $res_customers['parent_cust_id'] ?>"><?php echo $res_customers['cust_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				<input type="hidden" name="j_parent_cust_id" id="j_parent_cust_id" value="<?php echo $app_res['j_parent_cust_id']; ?>" />

                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Journal Name<span style= "color: red">*</span></label>
                 <input type="text" class="form-control" name="j_name" id="j_name" placeholder="Enter journal name" value="<?php echo $app_res['j_name']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Journal Abbreviation</label>
                 <input type="text" class="form-control" name="j_abbreviation" id="j_abbreviation" placeholder="Enter abbreviation" value="<?php echo $app_res['j_name']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Journal Code<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="j_code" id="j_code" placeholder="Enter code" value="<?php echo $app_res['j_code']; ?>">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Platform<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_platform" id="j_platform" >
                  <option value="">Select</option>

                               <?php foreach($platform_journal_array as $id=>$val){?>
                                <option value="<?php echo $val; ?>" <?php if($app_res['j_platform']==$val){?> selected <?php }?>> <?php echo $val; ?></option>
                                <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Division</label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_division" id="j_division">
                  <option value="">Select</option>

                  <?php foreach($division_array as $id=>$val){?>
                                <option <?php if($app_res['j_division']==$id){?> selected <?php }?> value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                <?php } ?>
                </select>
                </div> 
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Journal Type</label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="jt_id" id="jt_id">
                  <?php
	$query = "SELECT * FROM `journal_type` ORDER BY `jt_id` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['jt_id']; ?>" <?php if($app_res['jt_id'] == $row['jt_id']){?> selected <?php } ?>><?php echo $row['jt_name']; ?></option>
                                  <?php } ?>
                </select>
                </div> 
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Complexity </label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_complexity" id="j_complexity">
                  <option value="">Select</option>
                  <?php foreach($j_complexity_array as $id=>$val){?>
                                <option <?php if($app_res['j_complexity']==$id){?> selected <?php }?> value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                <?php } ?>
                </select>
                </div> 
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Proofing Server<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_proofing_system_server" id="j_proofing_system_server" required>
                  <option value="">Select</option>

                  <?php foreach($proofing_system_server as $id=>$val){?>
                                <option value="<?php echo $id; ?>" selected ><?php echo $val; ?></option>
                                <?php } ?>
                </select>
                </div> 
					</div>
				</div>
                <!-- < -->
            <!-- heading 2 -->
            
            <!-- heading 3 -->
            <div class="container mt-4 separatetitle">
               <div class="formcont1heading">FTP To Receive</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Host Name <span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="ftp_receive_host" id="ftp_receive_host" value="<?php if($app_res['ftp_receive_host']!=''){ echo  $app_res['ftp_receive_host']; }else{ echo "0";}?>" placeholder="Enter host name"  value="<?php echo $app_res['j_name']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_receive_username" id="ftp_receive_username" value="<?php if($app_res['ftp_receive_username']!=''){ echo  $app_res['ftp_receive_targetfolder']; }else{ echo "0";}?>" placeholder="Enter user name"  value="<?php echo $app_res['ftp_receive_username']; ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_receive_password" id="ftp_receive_password" value="<?php if($app_res['ftp_receive_password']!=''){ echo  $app_res['ftp_receive_password']; }else{ echo "0";}?>" placeholder="Enter password">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_receive_dir" id="ftp_receive_dir" value="<?php if($app_res['ftp_receive_dir']!=''){ echo  $app_res['ftp_receive_dir']; }else{ echo "0";}?>" placeholder="Enter dir">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_receive_targetfolder" id="ftp_receive_targetfolder" value="<?php if($app_res['ftp_receive_targetfolder']!=''){ echo  $app_res['ftp_receive_targetfolder']; }else{ echo "0";}?>" placeholder="Enter folder name">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center pt-5">
                  <label class="mb-2">FTP to receive :&nbsp<span style="color: red"></span> </label>
                  <input class="mb-2" type="checkbox"name="ftp_receive" id="ftp_receive" value="1" <?php if($app_res['ftp_receive'] == '1'){?> checked <?php } ?> checked>
                </div>   
                    </div>            
             </div>
             <div class="container mt-4 separatetitle">
               <div class="formcont1heading">FTP To Send</div>
                <div class="row mt-2 g-4 addspacing">
              
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Host Name <span style="color: red"></span> </label>
                  <input type="text" class="form-control" name="ftp_send_host" id="ftp_send_host" placeholder="Enter host name" value="<?php if($app_res['ftp_send_host']!=''){ echo  $app_res['ftp_send_host']; }else{ echo "0";}?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP User Name<span style="color: red"></span></label>
                  <input type="text" class="form-control" name="ftp_send_user" id="ftp_send_user" placeholder="Enter user name" value="<?php if($app_res['ftp_send_user']!=''){ echo  $app_res['ftp_send_user']; }else{ echo "0";}?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">FTP Password</label>
                  <input type="text" class="form-control" name="ftp_send_password" id="ftp_send_password" placeholder="Enter password" value="<?php if($app_res['ftp_send_password']!=''){ echo  $app_res['ftp_send_password']; }else{ echo "0";}?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Dir</label>
                 <input type="text" class="form-control" name="ftp_send_dir" id="ftp_send_dir" placeholder="Enter dir" value="<?php if($app_res['ftp_send_dir']!=''){ echo  $app_res['ftp_send_dir']; }else{ echo "0";}?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">FTP Target Folder</label>
                <input type="text" class="form-control" name="ftp_send_targetfolder" id="ftp_send_targetfolder" placeholder="Enter folder name"  value="<?php if($app_res['ftp_receive_targetfolder']!=''){ echo  $app_res['ftp_receive_targetfolder']; }else{ echo "0";}?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center pt-5">
                  <label class="mb-2">FTP to send :&nbsp<span style="color: red"></span> </label>
                  <input class="mb-2" type="checkbox"name="ftp_send" id="ftp_send" placeholder="" value="1" <?php if($app_res['ftp_send'] == '1'){?> checked <?php } ?> checked>
                </div>   
                    </div>            
             </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">From E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail</label>
                 <input type="text" class="form-control" name="j_from_email" id="j_from_email" placeholder="Enter email" value="<?php echo $app_res['j_from_email']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">From E-mail Password</label>
                 <input type="text" class="form-control" name="j_from_email_pwd" id="j_from_email_pwd" placeholder="Enter password" value="<?php echo $app_res['j_from_email_pwd']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center pt-5">
                  <label class="mb-2">From E-mail :&nbsp<span style="color: red"></span> </label>
                  <input class="mb-2" type="checkbox" name="j_email_receive" id="j_email_receive" placeholder="" value="1" <?php if($app_res['j_email_receive'] == '1'){?> checked <?php } ?> checked>
                </div>
                    </div>
            </div>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">To E-mail</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">To E-mail</label>
                 <input type="text" class="form-control" name="j_to_email" id="j_to_email " placeholder="Enter email" value="<?php echo $app_res['j_to_email']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">CC E-mail</label>
                 <input type="text" class="form-control" name="j_cc_email" id="j_cc_email_pwd" placeholder="Enter email" value="<?php echo $app_res['j_cc_email']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">BCC E-mail</label>
                 <input type="text" class="form-control" name="j_bcc_email" id="j_bcc_email_pwd" placeholder="Enter email" value="<?php echo $app_res['j_bcc_email']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay To</label>
                 <input type="text" class="form-control" name="reply_to" id="reply_to" placeholder="Enter reply to" value="<?php echo $app_res['reply_to']; ?>"> 
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Replay Name</label>
                 <input type="text" class="form-control" name="reply_name" id="reply_name" placeholder="Enter name" value="<?php echo $app_res['reply_name']; ?>">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center pt-5">
                  <label class="mb-2">To E-mail :&nbsp<span style="color: red"></span> </label>
                  <input class="mb-2" type="checkbox"name="j_email_send" id="j_email_send" placeholder="" value="1" <?php if($app_res['j_email_send'] == '1'){?> checked <?php } ?> checked>
                </div>
                    </div>
            </div>

            <?php
						  $wf_name = array();
	$query = "SELECT wf_name FROM `adm_workflow_master` ORDER BY `wf_name` ASC";
	$customers = $db->query($query) or ($db->error);
	while($row = $customers->fetch_assoc()){
		$wf_name[] = $row['wf_name'];
	}
	
							//  $full_name = array();
	
	
	?>
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Workflow</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2">First Proof Workflow<span style="color: red;"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_fp_wf" id="j_fp_wf" required>
                  <option value="">Select</option>
                                <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if($app_res['j_fp_wf'] == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">First Proof Duration in Days</label>
                 <input type="text" name="j_due_fduration" id="j_due_fduration" class="form-control" placeholder="Due Date"  value="<?php echo $app_res['j_due_fduration']; ?>"/>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >Revises Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_rev_wf" id="j_rev_wf"  required>
                  <option value="">Select</option>
                  <option value="">-Select-</option>
                                  <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if($app_res['j_rev_wf'] == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Revises Duration in Days</label>
                 <input type="text" name="j_due_rduration" id="j_due_rduration" class="form-control" placeholder="Due Date"  value="<?php echo $app_res['j_due_rduration']; ?>"/>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >Final Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_fin_wf" id="j_fin_wf" required>
                  <option value="">Select</option>
                       <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if($app_res['j_fin_wf'] == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>                 
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Final Workflow Duration in Days</label>
                 <input type="text" name="j_due_fiduration" id="j_due_fiduration" class="form-control" placeholder="Due Date" value="<?php echo $app_res['j_due_fiduration']; ?>" />
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >Issue Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_issue_wf" id="j_issue_wf" required>
                  <option value="">Select</option>
                  <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if($app_res['j_issue_wf'] == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>               
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Issue Duration in Days</label>
                 <input type="text" name="j_due_isduration" id="j_due_isduration" class="form-control" placeholder="Due Date" value="<?php echo $app_res['j_due_isduration']; ?>" />
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >Issue Final Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_fin_issue_wf" id="j_fin_issue_wf" required>
                  <option value="">Select</option>

                  <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if($app_res['j_fin_issue_wf'] == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Issue Final Duration in Days</label>
                 <input type="text" name="j_due_isfduration" id="j_due_isfduration" class="form-control" placeholder="Due Date" value="<?php echo $app_res['j_due_isfduration']; ?>" />
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >JSTAGE Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_jstage_wf" id="j_jstage_wf" >
                  <option value="">Select</option>

                  <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if(($app_res['j_jstage_wf'] ?? '') == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">JSTAGE Duration in Days</label>
                 <input type="text" name="j_due_jsduration" id="j_due_jsduration" class="form-control" placeholder="Due Date" value="<?php echo htmlspecialchars((string)($app_res['j_due_jsduration'] ?? '')); ?>" />
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                  <label class="mb-2" >PMC Workflow<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_pmc_wf" id="j_pmc_wf" >
                  <option value="">Select</option>

                  <?php foreach($wf_name as $wfname){  ?>
                                  <option value="<?php echo $wfname; ?>" <?php if(($app_res['j_pmc_wf'] ?? '') == $wfname){?> selected <?php } ?>><?php echo $wfname; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">PMC Duration in Days</label>
                 <input type="text" name="j_due_pmcduration" id="j_due_pmcduration" class="form-control" placeholder="Due Date" value="<?php echo htmlspecialchars((string)($app_res['j_due_pmcduration'] ?? '')); ?>" />
                </div>
            </div>
			</div>
				
            <div class="container mt-4">
              <div class="formcont1heading">Reminder</div>

              <div class="row mt-1 g-2">
               

                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2" >Reminder To<span style="color: red"></span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Reminder To" name="reminder_mail_to" id="reminder_mail_to">
                  <option value="A" <?php if($app_res['reminder_mail_to'] == 'A'){?> selected <?php } ?>>Author</option>
                  <option value="P" <?php if($app_res['reminder_mail_to'] =='P'){?> selected <?php } ?>>Publisher</option>

                
                </select>
                </div>

                
                
              </div>
                <div class="row mt-1 g-2">
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Reminder 1</label>
                 <input type="text" class="form-control" name="j_reminder_1" id="j_reminder_1" placeholder="In days" value="<?php echo $app_res['j_reminder_1']; ?>">
                </input>
                </div>
               <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Reminder 2</label>
                <input type="text" class="form-control" name="j_reminder_2" id="j_reminder_2" placeholder="In days" value="<?php echo $app_res['j_reminder_2']; ?>">
                </input>
               </div>
               <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Reminder 3</label>
                <input type="text" class="form-control" name="j_reminder_3" id="j_reminder_3" placeholder="In days" value="<?php echo $app_res['j_reminder_3']; ?>">
                </input>
               </div>

               <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Journal Head? <span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_hid" id="j_hid" required>
                  <option value="">Select</option>
                  <?php $users = "SELECT id,full_name,user_level FROM `users` where user_level='2' ORDER BY `full_name` ASC";
	$user_info = $db->query($users) or ($db->error);
	while($user_infom = $user_info->fetch_assoc()){
		?>
                                  <option value="<?php echo $user_infom['id']; ?>" <?php if($app_res['j_hid'] == $user_infom['id']){?> selected <?php } ?>><?php echo $user_infom['full_name']; ?></option>
                                  <?php } ?>
                </select>
                </div> 
               
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center pt-5">
                <label class="mb-2">Math Journal :&nbsp</label>
                <input class="mb-2" id="is_mathjournal" name="is_mathjournal" value="1" type="checkbox" class="ace" <?php if($app_res['is_mathjournal'] == '1'){?> checked <?php } ?> />
                </input>
               </div>


               </div>
               </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managejournals">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
            </div>
      </form>

      <!-- upload section -->
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
 <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

     $('#j_id').on('change',function(){
        var j_id = $(this).val();
        if(j_id){
            $.ajax({
                url:'get_workflow.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                  //  $('#wf_id').html(html);   
                    populateSelecte_wf("wf_id",data);                 
                }
            });  
			
			$.ajax({
                url:'get_group_folder.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                   // $('#group_folder_name').html(html);     
                    populateSelecte_gf("group_folder_name",data);                     
                }
            }); 
			
			$.ajax({
                type:'POST',
                url:'get_journal_duration.php',
                data:'j_id='+j_id,
                success:function(html){
                    $('#journal_duration').html(html);   
                   // populateSelecte_jd("journal_duration",data);               
				    $('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				});					
                }
            }); 
			
			
        }else{
            $('#wf_id').html('<option value="">Select customer first</option>');
            
        }
    });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }
        function populateSelecte_wf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.wf_name).val(item.wf_id));
            });
        }
        function populateSelecte_gf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.folder_name).val(item.folder_name));
            });
        }
        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>

<?php fu_foot('#edit_journal', fu_rules_journal(false), $fu_errors, null, fu_ui_journal('Edit Journal', 'Journal &rsaquo; Edit')); ?> 
  <script>
  function updateParentCustId() {
    var select = document.getElementById('j_cust_id');
    var selectedOption = select.options[select.selectedIndex];
    var parentId = selectedOption.getAttribute('data-parent');
    document.getElementById('j_parent_cust_id').value = parentId;
  }

  // Update on page load
  window.addEventListener('DOMContentLoaded', updateParentCustId);

  // Update on change
  document.getElementById('j_cust_id').addEventListener('change', updateParentCustId);
</script>

  </body>
</html>

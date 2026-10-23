<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
if (isset($_POST['action']) && $_POST['action'] == 'update') {
  foreach ($_POST as $key => $value) {
      $post[$key] = $value;
  }

  // Get existing files from the database
  $query = "SELECT uploads FROM inw_client_feedback WHERE f_id = ".$post['id'];
  $result = $db->query($query);
  $existing_files = json_decode($result->fetch_assoc()['uploads'], true) ?: [];

  // Handle new file uploads
  if (!empty($_FILES['attachments']['name'][0])) {
      $upload_dir = 'uploads/';
      foreach ($_FILES['attachments']['name'] as $index => $file_name) {
          $tmp_name = $_FILES['attachments']['tmp_name'][$index];
          $new_file_name = uniqid() . '_' . basename($file_name);
          $destination = $upload_dir . $new_file_name;

          if (move_uploaded_file($tmp_name, $destination)) {
              $existing_files[] = $destination;
          }
      }
  }

  // Update uploads column
  $updated_files = json_encode(array_values($existing_files));
$ap_message = "
<strong>RCA:</strong> " . nl2br(addslashes($post['rca'])) . "<br><br>
<strong>CA:</strong> " . nl2br(addslashes($post['ca'])) . "<br><br>
<strong>PA:</strong> " . nl2br(addslashes($post['pa'])) . "
";
  // Update the database
  // Snapshot before change (for change-log email)
  $__old = change_log_snapshot('inw_client_feedback', 'f_id', $post['id']);
  $update_query = "UPDATE inw_client_feedback SET
      `stage` = '" . $post['stage'] . "',
      `cust_id` = '" . $post['cust_id'] . "',
      `j_id` = '" . $post['j_id'] . "',
      `article` = '" . $post['article'] . "',
      `f_status` = '" . $post['f_status'] . "',
      `message` = '" . addslashes($post['message']) . "',
	  `rca` = '" . addslashes($post['rca']) . "',
      `ca` = '" . addslashes($post['ca']) . "',
      `pa` = '" . addslashes($post['pa']) . "',
	  `pa_dt` = " . (!empty($post['pa_dt']) ? "'" . $post['pa_dt'] . "'" : "NULL") . ",
      `pa_approved_by` = " . (!empty($post['pa_approved_by']) ? intval($post['pa_approved_by']) : "NULL") . ",
      `ap_dt` = NOW(),
      `uploads` = '" . $updated_files . "'
      WHERE f_id = " . $post['id'];

  if ($db->query($update_query)) {
  	log_row_change('Client Feedback', $post['id'], $__old, array('stage'=>$post['stage'],'cust_id'=>$post['cust_id'],'j_id'=>$post['j_id'],'article'=>$post['article'],'f_status'=>$post['f_status'],'message'=>addslashes($post['message']),'rca'=>addslashes($post['rca']),'ca'=>addslashes($post['ca']),'pa'=>addslashes($post['pa']),'pa_dt'=>$post['pa_dt'],'pa_approved_by'=>$post['pa_approved_by'],'uploads'=>$updated_files), array());
      header("Location: " . $_COOKIE['set_url'] . "/clientfeedback?msg=Successfully Updated");
      exit;
  } else {
      $err[] = "Please try again.";
  }
}


if (isset($_GET['del'])) {
  list($file_key, $f_id) = explode('-', $_GET['del']);

  $result = $db->query("SELECT uploads FROM inw_client_feedback WHERE f_id = '$f_id'");
  $data = $result->fetch_assoc();

  if ($data) {
      $uploaded_files = json_decode($data['uploads'], true) ?: [];

      if (isset($uploaded_files[$file_key])) {
          $file_path = $_SERVER['DOCUMENT_ROOT'] . '/workflow-new/' . $uploaded_files[$file_key];

          if (file_exists($file_path)) {
              if (unlink($file_path)) {

                  unset($uploaded_files[$file_key]);
                  $uploaded_files = array_values($uploaded_files);

                  $new_uploads = json_encode($uploaded_files);
                  $update_query = "UPDATE inw_client_feedback SET uploads = '$new_uploads' WHERE f_id = '$f_id'";
                  $db->query($update_query);

                  header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?msg=File deleted successfully");
                  exit;
              } else {
                  echo "<script>alert('Failed to delete file.');</script>";
              }
          } else {
              echo "<script>alert('File not found.');</script>";
          }
      }
  }
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_client_feedback WHERE f_id = ".intval($_REQUEST['id'])." ");
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}

	// Departments / users are stored comma-separated -> split for pre-selection.
	$dept_selected = (!empty($app_res['dept_id'])) ? array_map('trim', explode(',', $app_res['dept_id'])) : [];
	$user_selected = (!empty($app_res['u_id']))    ? array_map('trim', explode(',', $app_res['u_id']))    : [];

	$approvers = [];
$q = "SELECT id, full_name, user_email
      FROM users
      WHERE user_level IN (1,2,3)
      ORDER BY full_name ASC";

$res = $db->query($q);
while ($row = $res->fetch_assoc()) {
    $approvers[] = $row;
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
    <?php fu_head('edit-client-feedback'); ?>

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

.ui-widget-content {
  background-color:white !important;
}
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active, a.ui-button:active, .ui-button:active, .ui-button.ui-state-active:hover {
    border: 1px solid #003eff !important;
    background: #dd5c1c !important;
    font-weight: normal !important;
    color: #ffffff !important;
}
.ui-state-default .ui-icon {
     background-image:none !important;
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
            <p class="articleheading ms-4">Edit Client Feeback</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="<?php echo $_COOKIE['set_url'];?>/clientfeedback">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div>
  </div>


      <?php fu_hero(fu_ui_basic('Edit Client Feeback', 'Edit Client Feeback &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit-client-feedback" id="edit-client-feedback" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading">Edit Client Feeback</div>
              <div class="row mt-1 g-4 ">
              <!-- <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" placeholder="Enter Customer Name" required>
                </input>
                </div> -->
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="cust_id" id="cust_id" required>
                  <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
											<option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;" <?php if($app_res['cust_id'] ==$res_customers['id']){?> selected <?php } ?>><?php echo $res_customers['cust_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Journal<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" <?php if($app_res['j_id'] ==$res_journals['j_id']){?> selected <?php } ?>><?php echo $res_journals['j_code'] ?> </option>
													<?php
											}
											?>
                </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">

                  <label class="mb-2"> Stage </label>
                       <select class="form-select journal_drop" data-placeholder="Select Journal" name="stage" readonly >
                                  <option value="">-Select-</option>
                                  <?php foreach($project_status_types as $id=>$val){?>
                                  <option value="<?php echo $id; ?>" <?php if($app_res['stage'] ==$id){?> selected <?php } ?>><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                </div>
 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Department<span style="color: red">*</span></label>
                   <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);

    ?><select id="dep_id" name="dep_id[]" class="form-control" data-placeholder="Choose" multiple="multiple" readonly required style="height: 150px;">
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if(in_array($row['dept_code'], $dept_selected)){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
                </div>

				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Users<span style="color: red">*</span></label>
                <?php
// Load the users that are actually stored on this feedback (comma list),
// so every assigned user is shown and pre-selected.
$user_options = array();
if (!empty($app_res['u_id'])) {
    $uid_csv = $db->real_escape_string($app_res['u_id']);
    $users_r = $db->query("SELECT `id`,`full_name` FROM `users` WHERE approved='1' AND FIND_IN_SET(id,'".$uid_csv."') ORDER BY `full_name` ASC") or ($db->error);
    while($row_u = $users_r->fetch_assoc()) { $user_options[] = $row_u; }
}
?><select id="user_id" name="user_id[]" class="form-control" data-placeholder="Choose" multiple="multiple" readonly style="height: 150px;">
<?php
foreach($user_options as $row_u)
{
	?>
<option value="<?php echo $row_u['id'];?>" <?php if(in_array($row_u['id'], $user_selected)){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-6">
              <label class="mb-2">Article<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="article" id="article"  value="<?php echo $app_res['article'] ?>" placeholder="Enter Article" readonly required>
              </input>
                </div>
			<div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Received Date<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="recv_dt" id="recv_dt"  value="<?php echo $app_res['recv_dt']; ?>" placeholder="Enter Received Date" readonly required>
              </input>
                </div>
                <div class="col-12  col-lg-12">
              <label class="mb-2">Message<span style="color: red">*</span></label>
              <textarea name="message" id="message" class="form-control" rows="10" placeholder="Comments" readonly style=" min-height: 100px;"><?php echo $app_res['message'] ?></textarea>

              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-6">
    <label class="mb-2">Attachments</label>
    <input type="file" class="form-control" name="attachments[]" id="attachments" multiple>
</div>

<!-- Display Existing Files in a Table -->
<?php
 $uploaded_files = json_decode($app_res['uploads'], true) ?: [];
 if (!empty($uploaded_files)) {
     echo '<table class="table table-bordered mt-3">';
     echo '<thead>';
     echo '<tr><th>S.no</th><th>File Name</th><th>Action</th></tr>';
     echo '</thead>';
     echo '<tbody>';
     foreach ($uploaded_files as $key => $file) {
         $file_name = basename($file);
         echo '<tr>';
         echo '<td>' . ($key + 1) . '</td>';
         echo '<td><a href="/workflow-new/' . htmlspecialchars($file) . '" target="_blank">' . htmlspecialchars($file_name) . '</a></td>';
         echo '<td>
         <a href="?del=' . $key . '-' . $app_res['f_id'] . '"
            class="btn btn-danger btn-sm"
            onclick="return confirm(\'Are you sure you want to delete this file?\');">
            Delete
         </a>
       </td>';
         echo '</tr>';
     }
    echo '</tbody>';
    echo '</table>';
}
?>
				<?php /*?><div class="col-12  col-lg-12">
              <label class="mb-2">Action Plan<span style="color: red">*</span></label>
              <textarea name="ap_message" id="ap_message" class="form-control" rows="10" placeholder="Action Plan" style=" min-height: 100px;"><?php echo $app_res['ap_message'] ?></textarea>

              </input>
                </div><?php */?>
				 <div class="col-12 col-sm-12 col-lg-12">
              <label class="mb-2">RCA<span style="color: red">*</span></label>
              <textarea name="rca" id="rca" class="form-control"  placeholder="Enter Root Cause Analysis"  rows="6" cols="60"  style="width:100%;height:100px;"  required><?php echo $app_res['rca'] ?></textarea>
                </div>
				<div class="col-12 col-sm-12 col-lg-12">
              <label class="mb-2">CA<span style="color: red">*</span></label>
              <textarea type="text"  name="ca" id="ca" class="form-control" placeholder="Enter Corrective Action" rows="6" cols="60"  style="width:100%;height:100px;" required><?php echo $app_res['ca'] ?></textarea>
                </div>
				<div class="col-12 col-sm-12 col-lg-12">
              <label class="mb-2">PA<span style="color: red">*</span></label>
              <textarea type="text"  name="pa" id="pa" class="form-control"   placeholder="Enter Preventive Action " rows="6" cols="60"  style="width:100%;height:100px;" required><?php echo $app_res['pa'] ?>             </textarea>
                </div>
				<div class="row mt-3">

						<!-- PA Date -->
						<div class="col-12 col-md-6">
							<label class="mb-2">PA Date <span style="color: red">*</span></label>
							<input
								type="date"
								name="pa_dt"
								id="pa_dt"
								class="form-control"
								value="<?= !empty($app_res['pa_dt']) ? date('Y-m-d', strtotime($app_res['pa_dt'])) : '' ?>"
								required
							>
						</div>

						<!-- Approved By -->
						<div class="col-12 col-md-6">
							<label class="mb-2">Approved By <span style="color:red">*</span></label>
							<select name="pa_approved_by" id="pa_approved_by" class="form-control" required>
								<option value="">Select Approver</option>

								<?php foreach ($approvers as $u) { ?>
									<option value="<?= $u['id']; ?>"
										<?= (!empty($app_res['pa_approved_by']) && $app_res['pa_approved_by'] == $u['id']) ? 'selected' : '' ?>>
										<?= htmlspecialchars($u['full_name']); ?> (<?= htmlspecialchars($u['user_email']); ?>)
									</option>
								<?php } ?>

							</select>
						</div>


					</div>

				<div class="col-12 col-sm-6 col-lg-4">
					  <label class="mb-2">Status<span style="color: red">*</span></label>
					  <select class="form-select journal_drop" data-placeholder="Select Journal" name="f_status" id="f_status" required>
						  <option value="">Select Status</option>
						  <option value="Active" <?php if($app_res['f_status'] =="Active"){?> selected <?php } ?>>Active</option>
						  <option value="Inactive" <?php if($app_res['f_status'] =="Inactive"){?> selected <?php } ?>>Inactive</option>
					</select>
                </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/clientfeedback" style="text-decoration:none;">Cancel</a></button>

                    </div>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo intval($_REQUEST['id']); ?>" />

                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
                    </div>
                </div>
            </div>


                </div>
            </div>

    <!-- </head> -->

</form>
</div>
</div>
      <?php echo scripts(); ?>
      <?php if (isset($_GET['msg'])){ ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var msg = "<?php echo htmlspecialchars($_GET['msg']); ?>";
        if (msg) {
            swal("Saved", msg, "success");
        }
    });
</script><?php
}?>
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



    });

        function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }
        $('#message').richText();//this will create the default editor will all buttons

    </script>


<?php fu_foot('#edit-client-feedback', null, array(), null, fu_ui_basic('Edit Client Feeback', 'Edit Client Feeback &rsaquo; Edit')); ?>
  </body>
</html>

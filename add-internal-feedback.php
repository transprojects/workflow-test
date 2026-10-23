<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
include_once("smtpmail/phpmailer/class.phpmailer.php");
/// Add New record
if (isset($_POST['action']) && $_POST['action'] == 'insert') { 
  foreach ($_POST as $key => $value) {
      $post[$key] = $value;
  }	

  $article_details = mysqli_fetch_array(mysqli_query($db, "SELECT id FROM inw_inward_dtl WHERE pub_id LIKE '%" . $post['article'] . "%'"));

  $uploadedFiles = [];
  if (!empty($_FILES['attachments']['name'][0])) {
      $upload_dir = 'uploads/';
      foreach ($_FILES['attachments']['name'] as $index => $file_name) {
          $tmp_name = $_FILES['attachments']['tmp_name'][$index];
          $new_file_name = uniqid() . '_' . basename($file_name);
          $destination = $upload_dir . $new_file_name;

          if (move_uploaded_file($tmp_name, $destination)) {
              $uploadedFiles[] = $destination;
          }
      }
  }


  $uploads_json = json_encode($uploadedFiles);

  $insert_query = "INSERT INTO `inw_internal_feedback` 
      (`cust_id`, `j_id`, `dept_id`, `u_id`, `stage`, `article`, `inw_id`, `recv_dt`, `message`, `uploads`, `created_by`, `created_on`) 
      VALUES (
          '".$post['cust_id']."', 
          '".$post['j_id']."', 
          '".$post['dep_id']."', 
          '".$post['user_id']."', 
          '".$post['stage']."', 
          '".$post['article']."', 
          '".$article_details['id']."', 
          '".date('Y-m-d', strtotime($post['recv_dt']))."',
          '".addslashes($_POST['message'])."',
          '".$uploads_json."',
          '".$_COOKIE['user_id']."',
          NOW()
      )";	

      if ($db->query($insert_query)) {
          // Change-log entry + email for the new record (logged as soon as the insert succeeds)
          log_row_added('Internal Feedback', change_log_ref(array('article')), array_diff_key($_POST, array('action'=>1)), array());

        $journal_query = "SELECT
            es.from_email,
            es.from_password,
          COALESCE(NULLIF(CONCAT(ms.mail_to, ',', u.user_email), ','), ms.mail_to, u.user_email) AS to_mail,
            ms.mail_cc,
            ms.mail_bcc
        FROM
            `email_settings` es
        CROSS JOIN client_portal_mail_settings ms CROSS JOIN adm_journals aj 
        left JOIN users u ON
            u.id = aj.j_hid
        WHERE
            es.id = 1 AND ms.id = 10 AND aj.j_id =".$post['j_id']." limit 1";
                $journal_result = $db->query($journal_query);

                if ($journal_result->num_rows > 0) {
                    $journal_data = $journal_result->fetch_assoc();
                    $mail_from = $journal_data['from_email'];
                    $mail_pass = $journal_data['from_password'];
                    $sendto = $journal_data['to_mail'];
                    $mail_cc=$journal_data['mail_cc'];
                    $mail_bcc=$journal_data['mail_bcc'];
                } else {
                    die("Email credentials not found.");
                }

                function addEmailsToMailer($mail, $emailList, $type = 'to') {
                  $emails = array_map('trim', explode(',', $emailList));
                  $validRecipients = 0;
              
                  foreach ($emails as $email) {
                      if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                          if ($type === 'to') {
                              $mail->addAddress($email);
                          } elseif ($type === 'cc') {
                              $mail->addCC($email);
                          } elseif ($type === 'bcc') {
                              $mail->addBCC($email);
                          }
                          $validRecipients++;
                      } else {
                          echo "Invalid email: $email <br>"; // Debugging
                      }
                  }
              
                  return $validRecipients;
              }

          // Send Email after successful insertion
          // $sendto = $sendto;
          // $subject = "Client Feedback";
  
          $sendto = $sendto;
          $subject = "Journal workflow - Internal Feedback";
  
          $mail = new PHPMailer();
          $mail->IsSMTP();
          $mail->CharSet = 'UTF-8';
          $mail->Host = "outlook.office365.com";
          $mail->SMTPAuth = true;
          $mail->Port = 587;
          $mail->Username = $mail_from;
          $mail->Password =  $mail_pass;
          $mail->SMTPSecure = 'tls';
          $mail->From = $mail_from;
          $mail->FromName = $mail_from;
          $mail->isHTML(true);
          $mail->Subject = $subject;
  
          // Construct email body
          $body = "<p>Dear User,</p>";
          $body .= "<p>A new Feedback has been received for article no: <strong>" . $post['article'] . "</strong>
          , Customer: <strong>" . $post['cust_name'] . "</strong> 
          , Journal: <strong>" . $post['j_name'] . "</strong> 
          , Stage: <strong>" . $post['stage_name'] . "</strong>
          , Department: <strong>" . $post['dep_name'] . "</strong>
          , User: <strong>" . $post['user_name'] . "</strong> on ".$post['recv_dt'].".</p>";
          $body .= "<p>Message: " . nl2br($post['message']) . "</p>";
          if (!empty($uploadedFiles)) {
              $body .= "<p>Attached files:</p><ul>";
              foreach ($uploadedFiles as $file) {
                  $body .= "<li>" . basename($file) . "</li>";
                  $mail->addAttachment($file); // Attach files to email
              }
              $body .= "</ul>";
          }
          $body .= "<p>Best regards,<br>Support Team</p>";
  
          $mail->Body = $body;
          $validTo = addEmailsToMailer($mail, $sendto, 'to');
          $validCC = addEmailsToMailer($mail, $mail_cc, 'cc');
          $validBCC = addEmailsToMailer($mail, $mail_bcc, 'bcc');
          // Recipients
  
          // Send mail and check for errors
          if ($mail->Send()) {
              header("Location: " . $_COOKIE['set_url'] . "/internalfeedback?msg=Successfully added");
              exit();
          } else {
              die("Email sending failed: " . $mail->ErrorInfo);
          }
      } else {
          die("Database Error: " . $db->error);
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
    <?php fu_head('add-internal-feedback'); ?>	

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
            <p class="articleheading ms-4">Add Internal Feeback</p>
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

      <?php fu_hero(fu_ui_basic('Add Internal Feeback', 'Add Internal Feeback &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-internal-feedback" id="add-internal-feedback" enctype="multipart/form-data">
            <div class="container mt-4">
              <div class="formcont1heading">Add Internal Feeback</div>
              <div class="row mt-1 g-4">
              <!-- <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" placeholder="Enter Customer Name" required>
                </input>
                </div> -->
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <input type="hidden" name="cust_name" id="cust_name">
                  <select class="form-select journal_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>
                  <option value="">Select Customer</option>
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
                  <label class="mb-2">Journal<span style="color: red">*</span></label>
                  <input type="hidden" name="j_name" id="j_name">
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
											?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
												
											}
											?>
                </select>
                </div>
               
                <div class="col-12 col-sm-6 col-lg-4">
              <?php
	$query = "SELECT * FROM `adm_dept_master` ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2"> Stage </label>
                  <input type="hidden" name="stage_name" id="stage_name">
                       <select class="form-select journal_drop" data-placeholder="Select Journal" id="stage" name="stage" >
                                  <option value="">-Select-</option>
                                  <?php foreach($project_status_types as $id=>$val){?>
                                  <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                </div>
				
				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Department<span style="color: red">*</span></label>
                  <input type="hidden" name="dep_name" id="dep_name">
                   <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
                </div>
				
				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Users<span style="color: red">*</span></label>
                  <input type="hidden" name="user_name" id="user_name">
                <?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$_REQUEST['dep_id']."' and  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?><select id="user_id" name="user_id" class="form-control"  data-placeholder="Choose">
   <option value="">-Select-</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
	?>   
<option value="<?php echo $row_u['id'];?>" <?php if($_REQUEST['user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-6">
              <label class="mb-2">Article<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="article" id="article"  value="" placeholder="Enter Article" required>
              </input>
                </div>
				<div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Received Date<span style="color: red">*</span></label>
              <input type="text" class="form-control monthlyinputdate" name="recv_dt" id="recv_dt"  value="" placeholder="Enter Received Date" required>
              </input>
                </div>
                <div class="col-12  col-lg-12">
              <label class="mb-2">Message<span style="color: red">*</span></label>
              <textarea name="message" id="message" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>

              </input>
                </div>
				
    <!-- Multiple Attachments -->
    <div class="col-12 col-sm-6 col-lg-6">
                <label class="mb-2">Attachments</label>
                <input type="file" class="form-control" name="attachments[]" id="attachments" multiple>
            </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/clientfeedback" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="insert">

                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
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
         $("#cust_name").val($("#cust_id option:selected").text())
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

     $("#j_id").on("change", function() {
      $("#j_name").val($("#j_id option:selected").text())
     })
     $("#stage").on("change", function() {
      $("#stage_name").val($("#stage option:selected").text())
     })
     $("#dep_id").on("change", function() {
      $("#dep_name").val($("#dep_id option:selected").text())
     })
     $("#user_id").on("change", function() {
      $("#user_name").val($("#user_id option:selected").text())
     })
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
	<script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_ruser.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#user_id').html(html);                    
                }
            }); 
        }else{
            $('#user_id').html('<option value="">Select User</option>');
            
        }
    });

	
	});
	
			
		</script>


<?php fu_foot('#add-internal-feedback', null, array(), null, fu_ui_basic('Add Internal Feeback', 'Add Internal Feeback &rsaquo; Add')); ?>
  </body>
</html>

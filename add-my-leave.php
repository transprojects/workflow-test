<?php include "includes/includes.php";
include_once "includes/change_log_mail.php";
?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	$name = $db->real_escape_string($_POST['name']);
    $email = $db->real_escape_string($_POST['email']);
    $dept_id =$_COOKIE['department_id']; 
    $leave_type = $db->real_escape_string($_POST['lt_id']);
    $leave_on = date('Y-m-d',strtotime($_POST['leave_on']));
    $reasons = $db->real_escape_string($_POST['reasons']);
    $leave_session = $db->real_escape_string($_POST['leave_session']);
    $created_by =$_COOKIE['user_id']; 
    $user_id =$_COOKIE['user_id'];  
if ($_COOKIE['user_level'] == 3) {
    // For user_level 3, get from cookie
    $user_id = $_COOKIE['user_id'];
    $name = $_COOKIE['full_name'];
    $email = $_COOKIE['user_email'];
} else {
    // For others, get from POST values
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
}

    $created_at = date('Y-m-d H:i:s');

    // Insert ticket into database
    $sql = "INSERT INTO wf_user_leave (user_id,dept_id,name, email,leave_type,leave_session,leave_on,reasons,created_by, created_on) 
            VALUES ('$user_id', '$dept_id', '$name','$email', '$leave_type','$leave_session', '$leave_on', '$reasons', '$created_by', '$created_at')";

		echo  $sql;
    if ($db->query($sql) === TRUE) {
		// Change-log entry + email for the new record
		log_row_added('Leave', change_log_ref(array('leave_type','name')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/my-leave?msg=Leave created successfully"); 
    } else {
		header("location:".$_COOKIE['set_url']."/my-leave?msg=".$db->error.""); 
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

.formcont1heading{
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
            <p class="articleheading ms-sm-2 ms-lg-5">Apply My Leave</p>
          </div>

          <div class="col-4 col-lg-2">
            <div class="row pe-sm-2 pe-lg-2" style="display: flex; justify-content: end"> 
              <div class="col-3 articlebtns">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_tickets" id="add_tickets" enctype="multipart/form-data">
          <input type="hidden" name="action" value="insert">
            <div class="container mt-4">
			              <div class="row mt-1 g-4 addspacing">

		<?php if ($_COOKIE['user_level'] != 3): ?>
  <!-- User Dropdown -->
  <div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Select User<span style="color: red">*</span></label>
    <select class="form-control" id="user_selector" required>
      <option value="">Select User</option>
      <?php
      $user_result = $db->query("SELECT id, full_name, user_email FROM users ORDER BY full_name");
      while ($user = $user_result->fetch_assoc()) {
          $json = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
          echo "<option value='{$json}'>{$user['full_name']}</option>";
      }
      ?>
    </select>
  </div>
<?php endif; ?>

<!-- Common Name Field -->
<div class="col-12 col-sm-6 col-lg-4">
  <label class="mb-2">Name<span style="color: red">*</span></label>
  <input type="text" class="form-control" id="user_name" name="name" placeholder="Enter Name"
    value="<?php echo ($_COOKIE['user_level'] == 3) ? htmlspecialchars($_COOKIE['full_name']) : ''; ?>"
    <?php echo ($_COOKIE['user_level'] != 3) ? 'readonly' : ''; ?> required>
</div>
<input type="hidden" name="user_id" id="user_id">

<!-- Common Email Field -->
<div class="col-12 col-sm-6 col-lg-4">
  <label class="mb-2">Email<span style="color: red">*</span></label>
  <input type="email" class="form-control" id="user_email" name="email" placeholder="Enter Email"
    value="<?php echo ($_COOKIE['user_level'] == 3) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>"
    <?php echo ($_COOKIE['user_level'] != 3) ? 'readonly' : ''; ?> required>
</div>



<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Type Of Leave<span style="color: red">*</span></label>
    <select class="form-control" name="lt_id" required>
        <option value="">Select Type Of Leave</option>
        <?php
        $result = $db->query("SELECT * FROM wf_leave_type ORDER BY lt_id");
        while ($row = $result->fetch_assoc()) {
				echo "<option value='" . $row['lt_id'] . "'>" .$row['lt_title'] . "</option>";
              
        }
        ?>
    </select>
</div>
<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Leave Session<span style="color: red">*</span></label>
    <select class="form-control" name="leave_session" required>
        <option value="">Select Leave Session</option>
         <option value="1">FULL DAY</option>
         <option value="2">FIRST HALF DAY</option>
         <option value="3">SECOND HALF DAY</option>
    </select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Leave On<span style="color: red">*</span></label>
    <input type="text" class="form-control monthlyinputdate" name="leave_on" placeholder="Select Dste"  value="<?php echo date('d-m-Y'); ?>" readonly required>
</div>
<div class="col-12">
    <label class="mb-2">Reasons<span style="color: red">*</span></label>
    <textarea class="form-control" name="reasons" placeholder="Enter Reasons" required></textarea>
	
</div>
               
				</div>
    </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/my-leave" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
            </div>
      </div>
    </div>
      <!-- upload section -->
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->

 <script src="assets/js/select2/select2.min.js"></script>
<script>
document.getElementById('user_selector')?.addEventListener('change', function () {
    const selected = this.value;
    if (selected) {
        try {
            const user = JSON.parse(selected);
            document.getElementById('user_name').value = user.full_name || '';
            document.getElementById('user_email').value = user.user_email || '';
            document.getElementById('user_id').value = user.id || '';
        } catch (e) {
            console.error("Invalid user data", e);
        }
    } else {
        document.getElementById('user_name').value = '';
        document.getElementById('user_email').value = '';
        document.getElementById('user_id').value = '';
    }
});
</script>


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
   </script>
 <script>
    


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

        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      
    </script>

<script>  
 //	$.mask.definitions['~']='[+-]';

$(document).ready(function() {
    $("#add_department").validate({
        rules: {

          j_cust_id: "required",
          j_name: "required",
        

           
        },
        messages: {

          j_cust_id: "Select your customer ID",
          j_name: "Please enter Journal Name",
        
           
        },

      
    
});  

      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

});  

  </script> 
  </body>
</html>

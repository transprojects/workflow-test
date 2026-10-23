<?php include "includes/includes.php";
include_once "includes/change_log_mail.php";
include ('linux_db_myticket.php');

?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	$name = $mytickets_db->real_escape_string($_POST['name']);
    $email = $mytickets_db->real_escape_string($_POST['email']);
    $category_id = $mytickets_db->real_escape_string($_POST['category']);
    $priority = $mytickets_db->real_escape_string($_POST['priority']);
    $subject = $mytickets_db->real_escape_string($_POST['subject']);
    $status = $mytickets_db->real_escape_string($_POST['status']);
    $agent = $mytickets_db->real_escape_string($_POST['agent']);
    $description = $mytickets_db->real_escape_string($_POST['description']);
    $created_by = 1;  // Assuming current user ID is 1 for now
    $ticket_id = rand(1000000000, 1999999999);  // Generate random ticket ID
    $attachments = [];

    // Handle file uploads
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['tmp_name'] as $index => $tmp_name) {
            $filename = time() . '_' . basename($_FILES['attachments']['name'][$index]);
            $target_path = 'uploads/' . $filename;
            if (move_uploaded_file($tmp_name, $target_path)) {
                $attachments[] = $filename;
            }
        }
    }

    $attachments_json = json_encode($attachments);
    $created_at = date('Y-m-d H:i:s');

    // Insert ticket into database
    $sql = "INSERT INTO tickets (ticket_id, name, email, category_id, priority, subject, status, type, description, created_by, attachments, created_at,is_assign) 
            VALUES ('$ticket_id', '$name', '$email', '$category_id', '$priority', '$subject', '$status', 'Assigned', '$description', '$created_by', '$attachments_json', '$created_at','$agent')";

    if ($mytickets_db->query($sql) === TRUE) {
		// Change-log entry + email for the new record
		log_row_added('Ticket', change_log_ref(array('subject')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/my-tickets?msg=Ticket created successfully"); 
    } else {
		header("location:".$_COOKIE['set_url']."/my-tickets?msg=".$mytickets_db->error.""); 
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
            <p class="articleheading ms-sm-2 ms-lg-5">Add My Ticket</p>
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
<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Name<span style="color: red">*</span></label>
    <input type="text" class="form-control" name="name" placeholder="Enter Name" value="<?php echo $_COOKIE['full_name'];?>" required>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Email<span style="color: red">*</span></label>
    <input type="email" class="form-control" name="email" placeholder="Enter Email"  value="<?php echo $_COOKIE['user_email'];?>" required>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Category<span style="color: red">*</span></label>
    <select class="form-control" name="category" required>
        <option value="">Select Category</option>
        <?php
        $categories = [];
        $result = $mytickets_db->query("SELECT * FROM categories ORDER BY parent_id, id");
        while ($row = $result->fetch_assoc()) {
            $categories[$row['parent_id']][] = $row;
        }
        function renderCategories($categories, $parent_id = 0, $level = 0) {
            $prefix = str_repeat('&nbsp;', $level * 4);
            if (isset($categories[$parent_id])) {
                foreach ($categories[$parent_id] as $category) {
                    echo "<option value='" . $category['id'] . "'>" . $prefix . $category['name'] . "</option>";
                    renderCategories($categories, $category['id'], $level + 1);
                }
            }
        }
        renderCategories($categories);
        ?>
    </select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Users<span style="color: red">*</span></label>
    <select class="form-control" name="agent" required>
        <option value="">Select Users</option>
        <?php
        $users = [];
        $result = $mytickets_db->query("SELECT * FROM users ORDER BY type, id");
        while ($row = $result->fetch_assoc()) {
            $users[$row['type']][] = $row;
        }
        foreach ($users as $type => $userGroup) {
            echo "<optgroup label='" . ucfirst($type) . "'>";
            foreach ($userGroup as $user) {
                echo "<option value='" . $user['id'] . "'>" . $user['name'] . "</option>";
            }
            echo "</optgroup>";
        }
        ?>
    </select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Status<span style="color: red">*</span></label>
    <select class="form-control" name="status" required>
        <option value="">Select Status</option>
        <option value="New Ticket">New Ticket</option>
        <option value="In Progress">In Progress</option>
        <option value="On Hold">On Hold</option>
        <option value="Closed">Closed</option>
        <option value="Resolved">Resolved</option>
    </select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Subject<span style="color: red">*</span></label>
    <input type="text" class="form-control" name="subject" placeholder="Enter Subject" required>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Priority<span style="color: red">*</span></label>
    <select class="form-control" name="priority" required>
        <option value="">Select Priority</option>
        <option value="1">High</option>
        <option value="2">Medium</option>
        <option value="3">Low</option>
    </select>
</div>

<div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Attachments <small>(You can select multiple files)</small></label>
    <input type="file" class="form-control" name="attachments[]" multiple>
</div>

<div class="col-12">
    <label class="mb-2">Description<span style="color: red">*</span></label>
    <textarea class="form-control" name="description" placeholder="Enter Description" required></textarea>
	
</div>
               
				</div>
    </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/my-tickets" style="text-decoration:none;">Cancel</a></button>
                        
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
});  

  </script> 
  </body>
</html>

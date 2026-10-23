<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	

	$insert_query = "INSERT INTO `adm_dept_master` (`dept_name`, `dept_sn`, `parent_id`,stage_order,`created_by`, `created_dt`,accept_files) VALUES ('".$post['dept_name']."', '".$post['dept_sn']."', '".$post['parent_id']."', '".$post['stage_order']."','".$_COOKIE['user_id']."', NOW(),'".$post['accept_files']."');";	

		$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Department', change_log_ref(array('dept_name')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/managedepartments?msg=Successfully added"); 
		die();	
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
    <?php fu_head('add_department'); ?>	

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
            <p class="articleheading ms-sm-2 ms-lg-5">Add Department</p>
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
<?php fu_hero(fu_ui_basic('Add Department', 'Add Department &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_department" id="add_department" enctype="multipart/form-data">
          <input type="hidden" name="action" value="insert">
            <div class="container mt-4">
              <div class="row mt-1 g-4 addspacing">

            
              <div class="formcont1heading">Add Department</div>
              <div class="row mt-2 g-4 addspacing"> 
              <div class="col-12 col-sm-6 col-lg-4">
              <?php
	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2">Parent Department </label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="parent_id" id="parent_id">
                  <option value="">Select</option>

                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>" ><?php echo $row['dept_name']; ?></option>
                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Name<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="dept_name" id="dept_name" placeholder="Enter department name" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Shortname<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="dept_sn" id="dept_sn" placeholder="Enter department shortname" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Accepted Files<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="accept_files" id="accept_files" placeholder="Enter file name" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Order<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="stage_order" id="stage_order" placeholder="Enter department order" required>
                </input>
                </div>
				</div>
    </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managedepartments" style="text-decoration:none;">Cancel</a></button>
                        
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
  <?php fu_foot('#add_department', null, array(), null, fu_ui_basic('Add Department', 'Add Department &rsaquo; Add')); ?>
  </body>
</html>

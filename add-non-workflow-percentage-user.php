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

	$user_id=$_COOKIE['user_id'];
	$user_name=$_COOKIE['user_name'];
	
	//$cust=explode("-",$post['nwa_cust_id']);
	//$nwa_cust_id=$cust[0];
	//$nwa_cust_name=$cust[1];
	
	$insert_query = "INSERT INTO `non_workflow_user_percentage_article` (`user_id`,`user_name`,`nwa_cust_name`,`nwa_pub_id`, `nwa_date`, `nwa_page_count`,nwa_percentage, `nwa_cdatetime`,`nwa_comments`,`nwa_pt`) VALUES ('".$user_id."','".$user_name."','".$post['nwa_cust_name']."','".$post['nwa_pub_id']."','".date('Y-m-d',strtotime($post['nwa_date']))."', '".$post['nwa_page_count']."', '".$post['nwa_percentage']."', NOW(), '".$post['nwa_comments']."', '".$post['nwa_pt']."');";	

		$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Non Workflow Percentage (User)', change_log_ref(array('user_name','user_id')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/nonworkflowpercentageuser?msg=Successfully added"); 
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
            <p class="articleheading ms-sm-2 ms-lg-5">Add Non Workflow Percentage - User</p>
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
<?php fu_hero(fu_ui_basic('Add Non Workflow Percentage - User', 'Add Non Workflow Percentage - User &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_department" id="add_department" enctype="multipart/form-data">
          <input type="hidden" name="action" value="insert">
            <div class="container mt-4">
              <div class="row mt-1 g-4 addspacing">
              <?php /*?><div class="row mt-2 g-4 addspacing"> 
			    <?php
	//$query = "SELECT * FROM `users` WHERE `user_level` = 3 ORDER BY `id` ASC";
	$query = "SELECT * FROM `adm_customer_master` ORDER BY `id` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2">Select Customer </label>
                  <select class="form-select journal_drop" data-placeholder="Select Customer" name="nwa_cust_id" id="nwa_cust_id">
                  <option value="">Select</option>

                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']."-".$row['cust_name']; ?>" ><?php echo $row['cust_name']; ?></option>
                  <?php } ?>
                </select>
                </div>
				<?php */?>
				<div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cust Name</label>
                 <input type="text" class="form-control" name="nwa_cust_name" id="nwa_cust_name" placeholder="Enter Cust Name">
                </input>
                </div>
				<div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Pub ID<span style="color: red"></span></label>
                 <input type="text" class="form-control" name="nwa_pub_id" id="nwa_pub_id" placeholder="Enter Pub ID">
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Date<span style="color: red">*</span></label>
                 <input type="text" class="form-control monthlyinputdate" name="nwa_date" id="nwa_date" placeholder="Select Date" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Page Count<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="nwa_page_count" id="nwa_page_count" placeholder="Enter Page Count" value="0" required>
                </input>
                </div>
			<div class="col-12 col-sm-6 col-lg-4">
			  <label class="mb-2">Productive Time Taken <span style="color: red">*</span></label>
			  <div class="d-flex gap-2">
				<input type="number" class="form-control" name="nwa_pt_hours" id="nwa_pt_hours"
					   placeholder="Hours" min="0" max="24" required>
				<input type="number" class="form-control" name="nwa_pt_minutes" id="nwa_pt_minutes"
					   placeholder="Minutes" min="0" max="59" required>
			  </div>
			  <small id="pt_display" class="text-muted mt-1 d-block">Total: 00:00</small>
			  <!-- Hidden input to store combined time (optional) -->
			  <input type="hidden" name="nwa_pt" id="nwa_pt">
			</div>


                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Comments<span style="color: red">*</span></label>
                 <textarea  class="form-control" name="nwa_comments" id="nwa_comments" required></textarea>
                </input>
                </div>
               
				</div>
    </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/nonworkflowpercentageusers" style="text-decoration:none;">Cancel</a></button>
                        
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
  
  <script>
document.addEventListener('DOMContentLoaded', function () {
  const hoursInput = document.getElementById('nwa_pt_hours');
  const minutesInput = document.getElementById('nwa_pt_minutes');
  const display = document.getElementById('pt_display');
  const combinedInput = document.getElementById('nwa_pt');

  function updateProductiveTime() {
    const hrs = parseInt(hoursInput.value) || 0;
    const mins = parseInt(minutesInput.value) || 0;

    // Format with leading zeros
    const formatted = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;

    display.textContent = `Total: ${formatted}`;
    combinedInput.value = formatted; // Store in HH:MM format
  }

  hoursInput.addEventListener('input', updateProductiveTime);
  minutesInput.addEventListener('input', updateProductiveTime);
});
</script>

  <?php fu_foot('#add_department', null, array(), null, fu_ui_basic('Add Non Workflow Percentage - User', 'Add Non Workflow Percentage - User &rsaquo; Add')); ?>
  </body>
</html>

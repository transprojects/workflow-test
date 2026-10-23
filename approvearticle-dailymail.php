<?php include "includes/includes.php"; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS- -->

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['dept']))
{
	  $dept=implode(",",$_REQUEST['dept']);
 	  $ex_dept = explode(",",$dept);
}
else
{
	$ex_dept='';
}

if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['graphics_status']))
{
	  $graphics_status=implode(",",$_REQUEST['graphics_status']);
 	  $ex_graphics_status = explode(",",$graphics_status);
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}
//print_r($ex_dept);
	
?>
      <!-- Page body starts -->
   <style>
  .alert {
    max-width: 300px; /* Adjust the maximum width as needed */
    padding: 10px; /* Add some padding for better spacing */
    margin: 10px auto; /* Center the div horizontally */
  }
  .hide {
  display: none;
}
.close {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 5px;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }

  .close:hover {
    color: #000;
  }
  
   <!--.blink {
    animation: blink-animation 1s infinite;
  }
  -->

@keyframes colorChange {
    0% {
      color: white; /* Text color */
      background-color: red; /* Background color */
      border-color: red; /* Border color */
    }
    100% {
      color: black; /* Text color */
      background-color: yellow; /* Background color */
      border-color: yellow; /* Border color */
    }
  }

  .blink {
    display: inline-block;
    padding: 5px 10px;
    text-align: center;
    border-radius: 5px; /* Optional: rounded corners */
  }
  </style>   
      <div class="container-fluid" style="justify-content: space-between;">
	  
	   <?php
  if(!empty($_REQUEST['msg']))  {
    echo '<div id="successMessage" class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      '. $_REQUEST['msg'] .'</div>';   
  }
  ?> 
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
              <div class="col-md-9">
          <p class="articleheading ms-md-4">Scheduled Article Dispatch Mail - Approval Panel</p>
        </div>
          <div class="col-4 col-lg-2">
            <div class="row">
               
              <!--<div class="col-2 articlebtns">
                <a href="<?php echo $_COOKIE['set_url'];?>/add-article">
                  <button type="button">+ Add XML</button>
                </a>
              </div>-->
            </div>
          </div>
        </div>
      </div>
		<br/><br/>
      <?php /*?><div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id"  multiple="multiple">
												    <option value="all">Select All</option>

										  <?php
										   if($_COOKIE['user_level']=='1')
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
											}
											?>
                </select>
              </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Journal</label>
                <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id"  multiple="multiple">
												    <option value="all">Select All</option>

											<?php
										    if($_COOKIE['user_level']=='1')
										  {
											$sql_journals = $auth->showJournallist('0',2);
										  }
										  else
										  {
											$sql_journals = $auth->showJournallistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
											}
											?>
                </select>
              </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Stage</label>
                <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage[]" id="stage" multiple="multiple">
												    <option value="all">Select All</option>

                 <?php
// Get allowed stages from cookie only if user_level is 3
$allowed_stages = [];
if (isset($_COOKIE['user_level']) && $_COOKIE['user_level'] == '3' && isset($_COOKIE['stage_access'])) {
    $allowed_stages = explode(',', $_COOKIE['stage_access']);
    $allowed_stages = array_map('trim', $allowed_stages); // remove any extra spaces
}

foreach ($project_status_types as $id => $val) {
    // If user_level == 3, show only cookie stages; else show all
    if (!empty($allowed_stages) && !in_array($id, $allowed_stages)) {
        continue;
    }
    ?>
    <option value="<?php echo $id; ?>" 
        <?php if (isset($_REQUEST['stage']) && in_array($id, $ex_stage)) echo 'selected'; ?>>
        <?php echo $val; ?>
    </option>
    <?php
}
?>

                </select>
              </div>
			      <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Department</label>
                <select class="form-select department_drop" data-placeholder="Select Department" name="dept[]" id="dept" multiple="multiple">
					<option value="all">Select All</option>
                   <?php 
$sql_department = $auth->getAlldepartment('0', 2);

// Selected dept(s) from request


// Build allowed dept list only if user_level == 3
$allowed_departments = [];
if (isset($_COOKIE['user_level']) && $_COOKIE['user_level'] == '3') {
    if (!empty($_COOKIE['department_id'])) {
        $allowed_departments = array_merge($allowed_departments, explode(',', $_COOKIE['department_id']));
    }
    if (!empty($_COOKIE['odepartment_id'])) {
        $allowed_departments = array_merge($allowed_departments, explode(',', $_COOKIE['odepartment_id']));
    }
    $allowed_departments = array_map('trim', $allowed_departments);
    $allowed_departments = array_filter($allowed_departments);
}

foreach ($sql_department as $res_department) {

    // If user_level == 3 and dept not in allowed list, skip
    if (!empty($allowed_departments) && !in_array($res_department['dept_code'], $allowed_departments)) {
        continue;
    }

    // Decide if selected
    $selected = (!empty($ex_dept) && in_array($res_department['dept_code'], $ex_dept)) ? 'selected' : '';
    ?>
    <option value="<?php echo htmlspecialchars($res_department['dept_code']); ?>" style="font-weight:500;" <?php echo $selected; ?>>
        <?php echo htmlspecialchars($res_department['dept_name']); ?>
    </option>
    <?php
}
?>

                </select>
              </div>
            
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
              <label class="mb-2">Article ID</label>
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo $_REQUEST['article_id'];?>">
              </input>
             </div> 
	
             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="btn-wip" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="currentwork"><img src="./assets/images/refresh.svg"></a>
             </div>
			   
             </div>
              </form>
			  
			   
   
              </div><?php */?>

      <!-- Table section -->

   <div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
	   <div class="container-fluid table-responsive" style="overflow: auto">
  <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
          <button id="approveBtn" type="button" class="btn btn-success" style="border-radius: 8px; padding: 8px 20px;">
            <i class="fa fa-check-circle me-1"></i> Approve Today’s Dispatches
          </button>
        </div>  
   <table id="approve-dispatch" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
		<thead>
        <tr>
            <th>Customer</th>
            <th>Journal</th>
            <th>Article Code</th>
            <th>Stage</th>
            <th>Status</th>
            <th>Scheduled/Unscheduled</th>
            <th>Scheduled On</th>
          </tr>
        </thead>
      </table>
    </div>
 </div>

    <?php echo footer(); ?>
    <?php echo scripts(); ?>

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
	  $(".graphics_status_drop").select2({
        placeholder: "Select Graphics Stage",
      });
	  
    </script>
	<script>
$(document).ready(function() {
  const todays = new Date().toISOString().slice(0,10);

  // Example: set today’s date (you can change logic as needed)
  var today = new Date();
  today.setDate(today.getDate()); // one day minus
  var formattedDate = today.toISOString().split('T')[0];

  var table = $('#approve-dispatch').DataTable({
    processing: true,
    serverSide: true,
    paging: true,
    pageLength: 10,
    order: [[6, 'desc']], // default sort by created_dt
    ajax: {
      url: "get/json-approve-dispatch-smail.php",
      type: "POST",
      data: function(d) {
        d.today = formattedDate; // send the date dynamically
      }
    },
    columns: [
      { data: "cust_name", title: "Customer Name" },
      { data: "j_code", title: "Journal Code" },
      { data: "pub_id", title: "Publication ID" },
      { data: "stage", title: "Stage" },
      { data: "status", title: "Status" },
      { data: "action_btn", title: "Action", orderable: false, searchable: false },
      { data: "created_dt", title: "Created Date" }
    ],
    columnDefs: [
      { targets: [5], orderable: false, searchable: false } // redundant safeguard
    ]
  });

 // ✅ Schedule/Unschedule logic
// ✅ Schedule/Unschedule logic
$(document).on('click', '.schedule-btn', function() {
    let btn = $(this);

    // Prevent rapid multiple clicks
    if (btn.hasClass('processing')) return;
    btn.addClass('processing');

    let isUnscheduled = btn.text().trim() === 'Unschedule Today';
    let actionType = isUnscheduled ? 'unschedule' : 'schedule';

    // Spinner feedback
    btn.html('<i class="fa fa-spinner fa-spin"></i> ' +
             (isUnscheduled ? 'Unscheduling...' : 'Scheduling...'))
       .prop('disabled', true);

    $.ajax({
        url: 'ajax_schedule_today.php',
        type: 'POST',
        data: {
            action: actionType,
            article_id: btn.data('article-id'),
            cust_name: btn.data('cust'),
            j_code: btn.data('jcode'),
            cust_id: btn.data('custid'),
            j_id: btn.data('jid'),
            pub_id: btn.data('pub-id'),
            stage: btn.data('stage'),
            status: btn.data('status'),
            recv_dt: btn.data('received-date'),
            due_dt: btn.data('due-date'),
            page_count: btn.data('page-count')
        },
        success: function(response) {
            response = response.trim();

            if (response === 'scheduled' || response === 'unscheduled') {

                // ✅ Reload DataTable
                if ($.fn.DataTable.isDataTable('#approve-dispatch')) {
                    $('#approve-dispatch').DataTable().ajax.reload(null, false);
                }

                // ✅ Optional user feedback
                let msg = (response === 'scheduled')
                    ? 'Article scheduled successfully!'
                    : 'Article unscheduled successfully!';
                swal("Success", msg, "success");
            } else {
                swal("Error", response, "error");
            }

            // Reset button state
            btn.prop('disabled', false).removeClass('processing');
        },
        error: function() {
            swal("Error", "Error while scheduling! Please try again.", "error");
            btn.prop('disabled', false).removeClass('processing');
        }
    });
});


  // ✅ Approve button logic

$('#approveBtn').click(function(e) {
  e.preventDefault(); // Stop default form behavior

  swal({
    title: "Approve All?",
    text: "This will approve all dispatches scheduled for today.",
    type: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Approve",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#d33"
  }, function(isConfirm) {

    if (isConfirm) {
      // Optional: Disable button while approving
      $('#approveBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Approving...');

      $.ajax({
        url: "approve_dispatch_smail.php",
        type: "POST",
        data: { today: todays },
        success: function(res) {
          $('#approveBtn').prop('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Approve Today’s Dispatches');
          if (res.trim() === "success") {
            swal("Approved!", "Today's dispatches have been approved.", "success");
            table.ajax.reload(null, false);
          } else {
            swal("Error!", "Something went wrong. Try again.", "error");
          }
        },
        error: function() {
          $('#approveBtn').prop('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Approve Today’s Dispatches');
          swal("Error!", "Server not responding.", "error");
        }
      });

    } else {
      swal("Cancelled", "Approval process aborted. No changes made.", "info");
    }
  });
});


});
</script>
  </div>
</body>
</html>

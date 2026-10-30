<?php include "includes/includes.php";
session_start();
?>
<?php
setcookie("todo_popup_shown", "", time() - 3600); // Force-clear cookie

// Always show the modal HTML – JS will decide whether to display or not
$show_todo_modal = true;
?>
<?php if ($show_todo_modal): ?>
<!-- Todo Popup Modal -->
<div id="todoPopup" style="display:none; position:fixed; top:15%; left:50%; transform:translateX(-50%);
     background:white; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:9999; border-radius:8px;">
  <h5>Today's Pending Tasks</h5>
  <ul id="todoList"></ul>
  <button onclick="submitCompletedTasks()">Mark as Completed</button>
  <button onclick="document.getElementById('todoPopup').style.display='none'">Close</button>

  <div id="loadingOverlay" style="position: fixed;top: 0; left: 0; width: 100%; height: 100%;
       background: rgba(255, 255, 255, 0.8);z-index: 9999;display: none;justify-content: center;align-items: center;">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
      <span class="visually-hidden">Loading...</span>
    </div>
  </div>
</div>

<script>
function fetchTodosAndShowPopup() {
  fetch('get_user_todos.php')
    .then(response => response.json())
    .then(data => {
      if (data.length === 0) return; // No tasks, nothing to show

      const list = document.getElementById('todoList');
      list.innerHTML = '';

      data.forEach(task => {
        const li = document.createElement('li');
        li.innerHTML = `
          <input type="checkbox" name="todo[]" value="${task.id}"> ${task.task_description}
        `;
        list.appendChild(li);
      });

      document.getElementById('todoPopup').style.display = 'block';
    });
}

function submitCompletedTasks() {
  const checked = Array.from(document.querySelectorAll('input[name="todo[]"]:checked'))
                      .map(cb => cb.value);

  if (checked.length === 0) {
    alert("Please select at least one task.");
    return;
  }

  // ✅ Fix: Send each value correctly
  const params = new URLSearchParams();
  checked.forEach(id => params.append('completed_tasks[]', id));

  fetch('submit_completed_tasks.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params
  })
  .then(res => res.text())
  .then(msg => {
    alert("Tasks marked as completed.");
    document.getElementById('todoPopup').style.display = 'none';

    // Show loading animation
    document.getElementById('loadingOverlay').style.display = 'flex';

    // Reload page after short delay
    setTimeout(() => location.reload(), 1500);
  });
}

// Automatically run after 10 seconds
setTimeout(fetchTodosAndShowPopup, 10000);
</script>


<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>

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
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Dispatch</small><h1>Dispatch Article - Auto Upload/Failed Emails</h1></div>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="post">
          <div class="af-bar">
<div class="af-field search">
              <label class="mb-2">Article ID</label>
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo htmlspecialchars((string)(isset($_REQUEST['article_id']) ? $_REQUEST['article_id'] : '')); ?>">
              </input>
             </div>

            <div class="af-actions">
              <button type="button" class="af-btn" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count">0</span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" name="btn-wip" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="emailfailedlist" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel" id="afPanel">
            <div class="af-grid">
<div class="af-field ">
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
<div class="af-field ">
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
<div class="af-field ">
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
            </div>
          </div>
          <div class="af-chips" id="afChips"></div>
        </form>
      </div></div>

      <!-- Table section -->

       <div class="af-page"><div class="af-card">
	   <div class="table-responsive">
	   
       <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
		<thead>
			<tr>
				<th>Action</th>
				<th>Customer</th>
				<th>Journal</th>
				<th>Article ID</th>
				<th>Stage</th>
				<th>Status</th>
			</tr>
		</thead>
		</table>
		
		</div></div>
      	</div>



  
  <!-- Modal -->
 




     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    </script>
   <script>
$(document).ready(function () {

    let table = $('#articles').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "fetch_failed_uploads.php",
            type: "POST",
            data: function (d) {
                d.cust_id = $('#cust_id').val();
                d.j_id = $('#j_id').val();
                d.stage = $('#stage').val();
                d.article_id = $('#article_id').val();
            }
        },
        columns: [
            { data: 'action' },
            { data: 'customer' },
            { data: 'journal' },
            { data: 'article_id' },
            { data: 'stage' },
            { data: 'status_html' }
        ]
    });

    $('form[name="articles"]').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

});
</script>
<script>
$(document).on('click', '.retry-upload', function () {

    let id = $(this).data('id');
    let button = $(this);

    Swal.fire({
        title: 'Are you sure?',
        text: "Retry this upload?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, retry it!'
    }).then((result) => {

        if (result.isConfirmed) {

            // 🔒 Disable button to prevent double click
            button.prop('disabled', true);

            $.ajax({
                url: 'retry_upload.php',
                type: 'POST',
                data: { id: id },
                success: function (res) {

                    let response;

                    try {
                        response = JSON.parse(res);
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Invalid server response'
                        });
                        button.prop('disabled', false);
                        return;
                    }

                    // ✅ SUCCESS
                    if (response.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Queued!',
                            text: response.msg,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }

                    // ⚠️ WARNING
                    else if (response.status === "warning") {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Notice',
                            text: response.msg
                        });
                        button.prop('disabled', false);
                    }

                    // ❌ ERROR
                    else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.msg || 'Something went wrong'
                        });
                        button.prop('disabled', false);
                    }

                    $('#articles').DataTable().ajax.reload();
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Unable to process request'
                    });
                    button.prop('disabled', false);
                }
            });

        }

    });

});
</script>
 <script>
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
      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());
	
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#cust_id').val([]);
            // Select all options except the "Select All" option
            $('#cust_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#cust_id option[value=all]').prop('selected', false);
            }
        }

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
         var j_id=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#j_id').val([]);
            // Select all options except the "Select All" option
            $('#j_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#j_id option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	
	 	 	 $("#stage").on("change", function() {
         var stage=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#stage').val([]);
            // Select all options except the "Select All" option
            $('#stage option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#stage option[value=all]').prop('selected', false);
            }
        }
        
     });
	 


});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
			selectElement.append($("<option>").text("Select All").val("all"));

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

/* Send Mail: re-send the client notification of an upload whose mail failed */
$(document).on('click', '.send-mail', function () {
    let button = $(this), id = button.data('id');
    Swal.fire({ title: 'Send the mail?', text: 'The client notification for this article will be sent again.', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, send it' }).then((result) => {
        if (!result.isConfirmed) { return; }
        button.prop('disabled', true);
        $.ajax({
            url: 'send_failed_mail.php', type: 'POST', dataType: 'json', data: { id: id },
            success: function (response) {
                if (response.status === 'success') { Swal.fire({ icon: 'success', title: 'Mail sent', text: response.msg, timer: 2000, showConfirmButton: false }); }
                else { Swal.fire({ icon: response.status === 'warning' ? 'warning' : 'error', title: response.status === 'warning' ? 'Notice' : 'Mail failed', text: response.msg }); button.prop('disabled', false); }
                $('#articles').DataTable().ajax.reload();
            },
            error: function () { Swal.fire({ icon: 'error', title: 'Server Error', text: 'Unable to process request' }); button.prop('disabled', false); }
        });
    });
});
</script>

  <?php af_foot(); ?>
  </body>
</html>

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
      '. htmlspecialchars($_REQUEST['msg']) .'</div>';   
  }
  ?> 
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Work in progress</small><h1>Current Articles</h1></div>
            <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/add-article"><i class="fa fa-plus"></i> Add Manual</a>
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
            
             <div class="af-field date dvr-field">
              <label class="mb-2">Date range</label>
              <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo htmlspecialchars($from_dt); ?>" data-to="<?php echo htmlspecialchars($to_dt); ?>"></div>
             </div>
             <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
            
			 <?php 
			 if($_COOKIE['department_id']==10 || $_COOKIE['department_id']==14 )
			 {
			?>
			<div class="af-field check">
              <label class="mb-2" for="allocated">Show allocated</label>
              <input type="checkbox" <?php if($_REQUEST['allocated']!=""){?>checked <?php }?> name="allocated" id="allocated" value="1" style="margin-bottom:5px;">
              </input>
             </div> 
			<?php 
			 }
			 ?>
             
            <div class="af-actions">
              <button type="button" class="af-btn" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count">0</span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" name="btn-wip" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="currentwork" title="Reset filters"><i class="fa fa-refresh"></i></a>
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
              
			      <div class="af-field ">
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
              
             <div class="af-field ">
                <label class="mb-2">Choose Graphics Stage</label>
                <select class="form-select graphics_status_drop" data-placeholder="Select Graphics Stage" name="graphics_status[]" id="graphics_status" multiple="multiple">
								    <option value="all">Select All</option>
				 <?php 
                          if(isset($_REQUEST['graphics_status']))
													{
                          ?>
                    <option value="1" <?php if (in_array('1', $ex_graphics_status)){?>selected<?php }?>>-Yes-</option>
                    <option value="0" <?php if (in_array('0', $ex_graphics_status)){?>selected<?php }?>>-No-</option>
					<?php 
                          }
                          else
                          {
                          ?>
                          <option value="1" >-Yes-</option>
						  <option value="0" >-No-</option>

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

       <div class="af-page">
         <div class="af-tabs" id="afTabs" role="tablist" aria-label="Priority">
           <button type="button" class="af-tab active" data-tab="all"><i class="fa fa-th-list"></i> All <b>0</b></button>
           <button type="button" class="af-tab high" data-tab="high"><i class="fa fa-flag"></i> High priority <b>0</b></button>
           <button type="button" class="af-tab overdue" data-tab="overdue"><i class="fa fa-exclamation-triangle"></i> Overdue <b>0</b></button>
           <button type="button" class="af-tab today" data-tab="today"><i class="fa fa-clock-o"></i> Due today <b>0</b></button>
           <button type="button" class="af-tab d1" data-tab="d1"><i class="fa fa-calendar-o"></i> Due in 1 day <b>0</b></button>
           <button type="button" class="af-tab d2" data-tab="d2"><i class="fa fa-calendar-o"></i> Due in 2 days <b>0</b></button>
           <button type="button" class="af-tab d3" data-tab="d3"><i class="fa fa-calendar-o"></i> Due in 3 days <b>0</b></button>
           <button type="button" class="af-tab d4" data-tab="d4"><i class="fa fa-calendar-check-o"></i> Due in 4+ days <b>0</b></button>
           <button type="button" class="af-tab query" data-tab="query"><i class="fa fa-question-circle"></i> Query <b>0</b></button>
           <span class="af-tab-hint">Listed by due date, earliest first &middot; High priority = flagged articles &middot; Query = articles waiting on a query</span>
         </div>
       </div>
       <div class="af-page"><div class="af-card">
	   <div class="table-responsive">
	   
       <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
		<thead>
        <tr>
              <th data-sortable="true" scope="col" class="thcolor" >Action</th>
              <th data-sortable="true" scope="col" class="thcolor">File Type</th>
              <th data-sortable="true" scope="col" class="thcolor">File Name</th>
              <th data-sortable="true" scope="col" class="thcolor">Stage</th>
              <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Status</th>
              <th data-sortable="true" scope="col" class="thcolor">Graphics</th>
			  <th data-sortable="true" scope="col" class="thcolor">MS Count</th>
              <th class="thcolor">Instruction</th>
              <th class="thcolor">Startwork</th>
    	</tr>
		</thead>
		</table>
		
		</div>
      	</div></div>



  
  <!-- Modal -->
 

  <!-- Button trigger modal -->
<!-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
  Launch demo modal
</button> -->

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Article Transactions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopuptransactions"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>


     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   
       <!-- Initialize DataTables -->
       <script>
		<?php
    //echo ;exit;
			if(isset($_REQUEST['page']))
				$page = $_REQUEST['page']*10;
			else
				$page = 0;
			
			if(isset($_REQUEST['search']))
				$search = $_REQUEST['search'];
			else
				$search = '';
			if (isset($_REQUEST['cust_id']))
				$cust_id = implode(",", $_REQUEST['cust_id']);
			else if (isset($_COOKIE['user_level']) && $_COOKIE['user_level'] == 14)
				$cust_id = $_COOKIE['cust_id'];
			else
				$cust_id = '';


      if(isset($_REQUEST['j_id']))
				 $j_id = implode(",",$_REQUEST['j_id']);
			else
				$j_id = '';
     if(isset($_REQUEST['stage']))
			 {
$stage = $_REQUEST['stage'];
$stage = array_filter($stage, function($value) {
    return $value !== "all";
});
$stage = implode(",", $stage);
			 }

			else
			{
				$stage = '';
			}
			if(isset($_REQUEST['dept']))
			 {
$dept = $_REQUEST['dept'];
$dept = array_filter($dept, function($value) {
    return $value !== "all";
});
$dept = implode(",", $dept);
			 }

			else
			{
				$dept = '';
			}
			 if(isset($_REQUEST['graphics_status']))
			 {
$graphics_status = $_REQUEST['graphics_status'];
$graphics_status = array_filter($graphics_status, function($value) {
    return $value !== "all";
});
$graphics_status = implode(",", $graphics_status);
			 }

			else
			{
				$graphics_status = '';
			}
      if(isset($_REQUEST['article_id']))
				$article_id = $_REQUEST['article_id'];
			else
				$article_id = '';
		if(isset($_REQUEST['allocated']))
				$allocated = $_REQUEST['allocated'];
			else
				$allocated = '';
      if(isset($_REQUEST['from_dt']))
      $from_dt = $_REQUEST['from_dt'];
			else
				$from_dt = '';
      if(isset($_REQUEST['to_dt']))
      $to_dt = $_REQUEST['to_dt'];
      else
      $to_dt = '';
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var afTab = "all";
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#articles').DataTable({
				
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-articles-new.php",
				"sSortDir_0": "desc",
				
				
				
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": <?php echo json_encode($cust_id); ?>},
               { "name": "j_id", "value": <?php echo json_encode($j_id); ?>},
			   { "name": "stage", "value": <?php echo json_encode($stage); ?>},
			   { "name": "dept", "value": <?php echo json_encode($dept); ?>},
			   { "name": "graphics_status", "value": <?php echo json_encode($graphics_status); ?>},
			   { "name": "article_id", "value": <?php echo json_encode($article_id); ?>},
               { "name": "from_dt", "value": <?php echo json_encode($from_dt); ?>},
			   { "name": "to_dt", "value": <?php echo json_encode($to_dt); ?>},               
			   { "name": "allocated", "value": <?php echo json_encode($allocated); ?>},               
               { "name": "sSearch", "value": <?php echo json_encode($search); ?>},
               { "name": "ptab", "value": afTab }
                );
						tFlag = false;
					}
          else
          {
            aoData.push(
							 { "name": "cust_id", "value": <?php echo json_encode($cust_id); ?>},
							 { "name": "j_id", "value": <?php echo json_encode($j_id); ?>},
							 { "name": "stage", "value": <?php echo json_encode($stage); ?>},
							 { "name": "dept", "value": <?php echo json_encode($dept); ?>},
							 { "name": "graphics_status", "value": <?php echo json_encode($graphics_status); ?>},
							 { "name": "article_id", "value": <?php echo json_encode($article_id); ?>},
							 { "name": "from_dt", "value": <?php echo json_encode($from_dt); ?>},
							 { "name": "to_dt", "value": <?php echo json_encode($to_dt); ?>},
							 { "name": "allocated", "value": <?php echo json_encode($allocated); ?>},
							 { "name": "ptab", "value": afTab }
						);
          }
				},
				
				

       
				//"aaSorting": [[4, 'asc']],
			"aaSorting": [],   // due date order (earliest first) comes from the server; clicking a header still sorts by that column
			
			"fnSortData": function (aoData, iColumn, bDescending, oSettings) {
                if (iColumn == 0) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                }else if (iColumn == 1) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                } else {
                    // Default sorting for other columns
                    return oSettings.oApi.fnSortData(aoData, iColumn, bDescending, oSettings);
                }
            },
			
			 "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
      var cls = {1: 'af-row-high', 2: 'af-row-overdue', 3: 'af-row-today'}[aData[14]] || '';
      $(nRow).removeClass('af-row-high af-row-overdue af-row-today').addClass(cls);
    },
				 
				"columns": [
					{ "data": "0" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },
					{ "data": "6" },
					{ "data": "7" },
					{ "data": "1" },
					{ "data": "8" },
					{ "data": "9" },
					{"data": "10"}
				], 
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					// 		{
					// 	 "targets": -1,
					// 	"data": null,
					// 	'render': function (data, type, row) {
					// 	return '<button data-delete="' + row[10] +'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>'

					// 	}
					// },
				
					{ "orderable": false, "targets": 0 },
					{ "orderable": false, "targets": 7 },
					{ "orderable": false, "targets": 9 },
					{ "orderable": false, "targets": 10 }
					//{ "visible": false, "targets": 12 }
				],
				dom: 'Blfr<"table-wrapper"t>ip',
         buttons: [
            {
                extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],

       

        
			});

			// Auto-refresh the table every 30 seconds
           // setInterval(function() {
          //      table.ajax.reload(null, false); // User paging is not reset on reload
          //  }, 30000);
			
			/* priority tabs: counts come back with every response, a click reloads the table on that tab */
			$('#articles').on('xhr.dt', function (e, settings, json) {
				if (!json || !json.tabCounts) { return; }
				$('#afTabs .af-tab').each(function () {
					var n = json.tabCounts[$(this).data('tab')] || 0;
					$(this).find('b').text(n.toLocaleString()).toggleClass('zero', n === 0);
				});
			});
			$('#afTabs').on('click', '.af-tab', function () {
				afTab = $(this).data('tab');
				$('#afTabs .af-tab').removeClass('active'); $(this).addClass('active');
				table.page(0).draw(false);
			});
			table.on('page.dt', function() {
			  $('html, body').animate({
				scrollTop: $(".dataTables_wrapper").offset().top-500
			  }, '1000');
			  $('thead tr th:first-child').focus().blur();
			});
			<?php
			if($search!='')
			{
			?>
			$('#articles').on( 'init.dt', function () {
				$('input[type=search]').val(<?php echo json_encode($search); ?>);
				table.search( <?php echo json_encode($search); ?> );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#articles').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#articles').on('click', 'button', function () {
     //$(".delete-article").on("click", function() {

				var del = $(this).attr('data-delete');
        swal({
		title: "Are you sure?",
	    text: "You will not be able to recover this record!",
         type: "warning",
		showCancelButton: true,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Proceed!',
		cancelButtonText: "No, cancel please!",
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Deleted!", "Loading Please wait", "success");
     // window.top.location="view_users.php?del="+del;
     window.top.location="articles?del="+del;

    } else {
      swal("Cancelled", "Article not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
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

    <!-- Date picker functionality -->
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
	 
	 	 $("#graphics_status").on("change", function() {
         var graphics_status=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#graphics_status').val([]);
            // Select all options except the "Select All" option
            $('#graphics_status option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#graphics_status option[value=all]').prop('selected', false);
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
	 
	 	  $("#dept").on("change", function() {
         var dept=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#dept').val([]);
            // Select all options except the "Select All" option
            $('#dept option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#dept option[value=all]').prop('selected', false);
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


			
function check_work_status(id, tid, stage, fs, btnId) {

    let btn = $("#" + btnId);

    // Disable and show spinner immediately
    btn.prop("disabled", true).addClass("disabled");
    showSpinner(btn);

    $.ajax({
        type:'POST',
        url:'get_article_work_status_test.php',
        data:{ id:id, stage:stage, fstart:fs },

        success:function(result) {

            // ================================
            // FORCE START
            // ================================
            if(fs == 1) {
                showDialog(
                    "<strong>You are force starting this file!</strong>",
                    "Force Start",
                    function() {
                        openNewTab('<?php echo $_COOKIE['set_url'];?>/forcestartworknew/'+id+'/1');

                        // Reset button
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);

                        showWorkStarted();
                    },
                    function() { 
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);
                    }
                );
                return;
            }

            // ================================
            // NORMAL START
            // ================================
            if(result == 0) {

                openNewTab('<?php echo $_COOKIE['set_url'];?>/startworknew/'+id+'/new');

                // Reset button back to normal
                btn.prop("disabled", false).removeClass("disabled");
                hideSpinner(btn);

                showWorkStarted();
                return;
            }

            // ================================
            // VALIDATION FAILED
            // ================================
            if(result.includes("Inprogress") || result.includes("Pending")) {
                showDialog(
                    result,
                    "Close",
                    null,
                    function() { 
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);
                    }
                );
                return;
            }

            // ================================
            // TAKE OVER
            // ================================
            showDialog(
                result,
                "Take Over",
                function() {

                    openNewTab('<?php echo $_COOKIE['set_url'];?>/startworknew/'+id+'/'+tid);

                    btn.prop("disabled", false).removeClass("disabled");
                    hideSpinner(btn);

                    showWorkStarted();
                },
                function() { 
                    btn.prop("disabled", false).removeClass("disabled");
                    hideSpinner(btn);
                }
            );
        },

        error:function() {
            btn.prop("disabled", false).removeClass("disabled");
            hideSpinner(btn);
        }
    });

    return false;
}



// ========================================================
// Spinner Functions
// ========================================================
function showSpinner(btn) {
    btn.data("original-html", btn.html());
    btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
}

function hideSpinner(btn) {
    let oldHtml = btn.data("original-html");
    if (oldHtml) btn.html(oldHtml);
}


// ========================================================
// Bootbox Dialog Wrapper
// ========================================================
function showDialog(message, primaryBtn, primaryAction = null, onClose = null) {

    let buttons = {};

    if (primaryAction) {
        buttons["primary"] = {
            label: primaryBtn,
            className: "btn-sm btn-danger",
            callback: primaryAction
        };
    }

    buttons["close"] = {
        label: "Close",
        className: "btn-sm",
        callback: function () {
            if (onClose) onClose();
        }
    };

    var d = describeWorkMessage(message);
    bootbox.dialog({
        message: d.html,
        className: d.cls,
        buttons: buttons
    });
}

/* The start-work check answers with a plain sentence; this turns it into a title, a short line and (for "already started by") a user card. */
function describeWorkMessage(m) {
    var esc = function (x) { return $('<div>').text(x).html(); };
    var t = $('<div>').html(m).text().replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
    var who = /already started by (.+?)\.?$/i.exec(t);
    if (/currently in/i.test(t) && who) {
        var n = who[1], ini = $.map(n.replace(/[^A-Za-z ]/g, ' ').split(/\s+/).filter(Boolean).slice(0, 2), function (w) { return w.charAt(0).toUpperCase(); }).join('') || '?';
        return { cls: 'bootbox-sm bb-bad', html: '<span class="bb-title">Work already in progress</span><span class="bb-sub">Another user has this article open.</span>'
            + '<div class="bb-who"><span class="bb-av">' + esc(ini) + '</span><span class="bb-wt"><small>Started by</small><b>' + esc(n) + '</b></span><span class="bb-chip">In progress</span></div>'
            + '<span class="bb-sub" style="margin-top:14px;">Take over to continue the work yourself, or close to leave it with them.</span>' };
    }
    if (/force starting/i.test(t)) { return { cls: 'bootbox-sm bb-warn', html: '<span class="bb-title">Force start this file?</span><span class="bb-sub">' + esc(t) + '</span>' }; }
    if (/high priority/i.test(t)) { return { cls: 'bootbox-sm bb-warn', html: '<span class="bb-title">High priority first</span><span class="bb-sub">' + esc(t) + '</span>' }; }
    if (/inprogress/i.test(t)) { return { cls: 'bootbox-sm bb-warn', html: '<span class="bb-title">Finish your current work first</span><span class="bb-sub">' + esc(t) + '</span>' }; }
    return { cls: 'bootbox-sm bb-warn', html: '<span class="bb-title">Please check</span><span class="bb-sub">' + esc(t) + '</span>' };
}


// ========================================================
// Work Started Notification
// ========================================================
function showWorkStarted() {
    bootbox.alert({
        message: '<span class="bb-title">Work started successfully!</span><span class="bb-sub">The list will refresh when you press OK.</span>',
        className: "bootbox-sm bb-ok",
        callback: function () {
            location.reload(); // Reloads the current page
        }
    });
}



/* function openNewTab(url) {
    let newTab = window.open(url, '_blank');
    if (newTab) newTab.focus();
} */

function openNewTab(url) {
    let newTab = window.open(url, '_blank');

    if (!newTab || newTab.closed || typeof newTab.closed === "undefined") {

        swal({
            title: "Popup Blocked",
            text: "Your browser has blocked the popup.\n\nPlease allow popups for this website and try again.\n\nChrome: Click the popup blocked icon in the address bar and select 'Always allow popups from this site'.",
            type: "warning",
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "OK"
        });

        return false;
    }

    newTab.focus();
    return true;
}	
			function timepopup(id)
			{		
       // alert('yrdyd');		
				var url = "<?php echo $_COOKIE['set_url'];?>/project_comments.php?id="+id;
				$("#ppopup").load(url);
			}
			function transactionspopup(id)
			{		
       // alert('yrdyd');		
				var url = "<?php echo $_COOKIE['set_url'];?>/article_transactions.php?id="+id;
				$("#ppopuptransactions").load(url);
			}	
    </script>
	
	<script>
  setTimeout(function() {
    var successMessage = document.getElementById('successMessage');
    if (successMessage) {
      successMessage.classList.add('hide');
    }
  }, 5000); // Hide after 5 seconds (5000 milliseconds)
</script>
  <?php af_foot(); ?>
  </body>
</html>

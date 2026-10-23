<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include_once "includes/change_log_mail.php";?>

<?php 
	if(isset($_REQUEST['del']))
{	
	// Snapshot before delete (for change-log email)
	$__old = change_log_snapshot('non_workflow_user_percentage', 'nw_id', $_REQUEST['del']);
	$db->query("delete from  non_workflow_user_percentage where nw_id='".$_REQUEST['del']."'");
	log_row_deleted('Non Workflow Percentage', $_REQUEST['del'], $__old);
	header("location:".$_COOKIE['set_url']."/nonworkflowpercentageusers?msg=Successfully deleted");
	die();	
	
}

	if(isset($_REQUEST['btn-reset']))
{	
	$db->query("delete from  non_workflow_user_percentage where user_id='".$_REQUEST['user_id']."' and nw_date='".date('Y-m-d',strtotime($_REQUEST['from_dt']))."'");
	// Bulk reset: log the delete (rows removed for this user / date range)
	send_change_log_mail('Non Workflow Percentage', 'user ' . $_REQUEST['user_id'], "[Bulk delete - reset]\r\n  user_id: '" . $_REQUEST['user_id'] . "'\r\n  from_dt: '" . $_REQUEST['from_dt'] . "'\r\n  to_dt: '" . (isset($_REQUEST['to_dt']) ? $_REQUEST['to_dt'] : '') . "'\r\n", 1, 'DELETE');
	
	//echo "update non_workflow_user_percentage_article set nwa_status='Pending' , nw_id = NULL  where user_id='".$_REQUEST['user_id']."' and nwa_date='".date('Y-m-d',strtotime($_REQUEST['from_dt'])."'";exit;
	$db->query("update non_workflow_user_percentage_article set nwa_status='Pending' , nw_id = NULL  where user_id='".$_REQUEST['user_id']."' and nwa_date='".date('Y-m-d',strtotime($_REQUEST['from_dt']))."'");

	header("location:".$_COOKIE['set_url']."/nonworkflowpercentageusers?user_id=".$_REQUEST['user_id']."&from_dt=".$_REQUEST['from_dt']."&to_dt=".$_REQUEST['to_dt']."&to_dt=".$_REQUEST['to_dt']."&dep_id=".$_REQUEST['dep_id']."&msg=Successfully deleted");
	die();	
	
}

if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
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
	$from_dt=date('d-m-Y');
}
if(isset($_REQUEST['user_id']))
{
	  $user_id=$_REQUEST['user_id'];
}
else
{
	$user_id='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt=date('d-m-Y');
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
    <?php lu_css(); ?>	

    <!-- Data Table CSS- -->
	<style>
	/* Make the checkbox bigger */
.approval-checkbox {
  width: 30px;
  height: 30px;
  cursor: pointer;
  accent-color: green; /* Works in modern browsers */
}

/* Optional: for older browser support */
.approval-checkbox:checked {
  background-color: green;
  border-color: green;
}

/* Make the tick mark bold and visible */
.approval-checkbox:checked::before {
  content: '✔';
  display: block;
  color: white;
  font-size: 20px;
  text-align: center;
  line-height: 30px;
  font-weight: bold;
}

	</style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    
      <!-- Page body starts -->

      
      <?php lu_hero('Non Workflow Articles', 'Non Workflow Articles &rsaquo; All', $_COOKIE['set_url'].'/addnonworkflowpercentageusers', '+ Add Non Workflow Articles'); ?>
<div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Non Workflow Articles</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addnonworkflowpercentageusers">
                  <button type="button">+ Add Non Workflow Articles</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
           
<?php 
if($_COOKIE['user_level']!='3' && $_COOKIE['user_level']!='4')
{
?>
  <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Department</label>
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
			   <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Users</label>
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
			  <?php 
}
?>			  
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
             <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text"class="form-control monthlyinputdate" name="from_dt" placeholder="From Date" value="<?php echo $from_dt;?>" readonly/>
                </div>
             </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
               <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate1" name="to_dt" placeholder="To Date" value="<?php echo $to_dt;?>" readonly/>
                </div>
             </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
              <label class="mb-2">Article ID</label>
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo $_REQUEST['article_id'];?>">
              </input>
             </div> 
			<div class="col-12 col-sm-6 col-md-3 col-lg-2">
			  <label class="mb-2">Status</label>
			  <select class="form-control" name="nwa_status" id="nwa_status">
				<option value="">All</option>
				<option value="Approved" <?php if ($_REQUEST['nwa_status'] == 'Approved') echo 'selected'; ?>>Approved</option>
				<option value="Pending" <?php if ($_REQUEST['nwa_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
			  </select>
</div>
             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="btn-wip" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="nonworkflowpercentageusers"><img src="./assets/images/refresh.svg"></a>
			  <?php 
if (!empty($_REQUEST['user_id'])) {
?>
    <button type="submit" name="btn-reset" style="background-color:red; color:white;" class="resultbtn ms-4">
        Reset Approval
    </button>	
<?php 
}
?>


             </div>
             </div>
              </form>
              </div>
    
      <!-- Table section -->

       <?php lu_card_open(); ?>
       <div class="table-responsive custom-container">
       <table id="non-workflow" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap !important">
      
                                          <thead>
											<tr>
												 <th>Employee Name</th>
												<th>Customer Name</th>
												<th>Pub ID</th>
												 <th><i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>  Created On</th>
												 <th>Page Count</th>
												 <th>Percentage</th> <!-- New -->
												 <th>Time Taken</th>
												 <th>Comments</th>
												 <th>Approve</th>     <!-- New -->
                     						    <th>Action</th>
											</tr>
										</thead>
				</table>
	
<?php 
if($_COOKIE['user_level']!=3)
{
?>	
<style>
  .nw-sum{display:flex;flex-wrap:wrap;gap:10px;margin:14px 0 4px;}
  .nw-sum span.c{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:12px;background:#f4f8fc;border:1px solid #d6e4f1;font-size:12.5px;font-weight:600;color:#5b6b7b;}
  .nw-sum span.c b{font-size:15px;color:#14304a;font-variant-numeric:tabular-nums;}
  .nw-approve{display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;margin-top:14px;padding:16px 18px;border:1px solid #e4e9ef;border-radius:14px;background:#fbfcfe;}
  .nw-approve .f{flex:1 1 320px;} .nw-approve label{display:block;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.35px;color:#5b6b7b;margin:0 0 6px;}
  .nw-approve textarea{width:100%;height:64px !important;border:1px solid #cfd8e3;border-radius:10px;background:#fff;padding:9px 12px;font-size:14px;resize:vertical;}
  .nw-approve textarea:focus{border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);outline:0;}
  #submitApproval{height:42px;padding:0 24px;border:0;border-radius:10px;font-weight:700;color:#fff !important;background:linear-gradient(135deg,#e8742a,#f08a45);box-shadow:0 6px 16px rgba(232,116,42,.35);}
  .percent-input{width:90px !important;height:38px !important;text-align:center;border:1px solid #cfd8e3 !important;border-radius:9px !important;background:#fbfcfe !important;padding:0 8px !important;}
  .approval-checkbox{width:20px;height:20px;border-radius:6px;accent-color:#1b6ca8;cursor:pointer;}
  #non_workflow_table td div[style*="overflow-y"],.lu-card td div[style*="overflow-y"]{max-height:66px !important;scrollbar-width:thin;padding-right:6px;line-height:1.4;}
  .lu-card .badge.bg-success{background:#e7f6ee !important;color:#1f9d6a !important;border-radius:999px;padding:5px 12px;font-weight:700;}
</style>
<div id="summary" class="nw-sum">
  <span class="c">Total Hrs <b id="totalHrs">00:00:00</b></span>
  <span class="c">Total Page Count <b id="totalPages">0</b></span>
  <span class="c">Total Percentage <b><span id="totalPercentage">0</span>%</b></span>
</div>
<div class="nw-approve">
  <div class="f"><label for="approval_comments">Comments</label>
  <textarea name="approval_comments" id="approval_comments" placeholder="Enter your comments here..."></textarea></div>
  <button id="submitApproval" class="btn btn-primary" type="button"><i class="fa fa-check"></i> Submit Approval</button>
</div>
<?php lu_close(); ?>







<?php 
}
?>
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
       
			if(isset($_REQUEST['dep_id']))
				$dep_id = $_REQUEST['dep_id'];
			else
				$dep_id = '';
			if(isset($_REQUEST['article_id']))
				$article_id = $_REQUEST['article_id'];
			else
				$article_id = '';
			 if(isset($_REQUEST['nwa_status']))
				$nwa_status = $_REQUEST['nwa_status'];
			else
				$nwa_status = '';
		
      if(isset($_REQUEST['from_dt']))
      $from_dt = $_REQUEST['from_dt'];
			else
				$from_dt = '';
      if(isset($_REQUEST['to_dt']))
      $to_dt = $_REQUEST['to_dt'];
      else
      $to_dt = '';
    if(isset($_REQUEST['user_id']))
      $user_id = $_REQUEST['user_id'];
      else
      $user_id = '';
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#non-workflow').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-non-workflow-users.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[5, 'desc']],
								
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
			   { "name": "dep_id", "value": '<?php echo $dep_id ?>'},
			   { "name": "article_id", "value": '<?php echo $article_id ?>'},
               { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
			   { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},               
			   { "name": "user_id", "value": '<?php echo $user_id; ?>'},               
			   { "name": "nwa_status", "value": '<?php echo $nwa_status; ?>'},               
               { "name": "sSearch", "value": '<?php echo $search ?>'}
                );
						tFlag = false;
					}
          else
          {
            aoData.push(
							 { "name": "dep_id", "value": '<?php echo $dep_id ?>'},
							 { "name": "article_id", "value": '<?php echo $article_id ?>'},
							 { "name": "nwa_status", "value": '<?php echo $nwa_status ?>'},
							 { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},
							 { "name": "user_id", "value": '<?php echo $user_id; ?>'}
						);
          }
				},
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },// Percentage
					{ "data": "6" },// Percentage
					{ "data": "7" },// Comments
					{ "data": "8", "orderable": false, "searchable": false }, // Approval
					{ "data": "9", "orderable": false, "searchable": false }  // Delete
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					//{ "orderable": false, "targets": 4 }
					//{ "orderable": false, "targets": 9 }
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


			
			table.on('xhr.dt', function (e, s, json) { if (json) { $('#lu-count').text(json.iTotalDisplayRecords); } });

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
			$('#non-workflow').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#non-workflow').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#non-workflow').on('click', 'button[data-delete]', function () {
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

      swal({title: "Deleting...", text: "Please wait", type: "info", showConfirmButton: false});
     // window.top.location="nonworkflowpercentageusers?del="+del;
     window.top.location="nonworkflowpercentageusers?del="+del;

    } else {
      swal("Cancelled", "User Non Workflow percentage not Deleted", "error");
    }
	});
			});

      
		});	
		
/* 		      $(function () {
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
      }); */
	  
$(function () {
    $(".monthlyinputdate, .monthlyinputdate1").datepicker({
        uiLibrary: "bootstrap5",
        dateFormat: "dd-mm-yy",
        autoclose: true,
        todayHighlight: true
    });

    function syncDates(date) {
        $(".monthlyinputdate").val(date);
        $(".monthlyinputdate1").val(date);

        $(".monthlyinputdate, .monthlyinputdate1").datepicker("destroy").datepicker({
            uiLibrary: "bootstrap5",
            dateFormat: "dd-mm-yy",
            autoclose: true,
            todayHighlight: true,
            defaultDate: date
        }).val(date);
    }

    $(".monthlyinputdate").on("change", function () {
        let selectedDate = $(this).val();
        syncDates(selectedDate);
    });

    $(".monthlyinputdate1").on("change", function () {
        let selectedDate = $(this).val();
        syncDates(selectedDate);
    });
});


	  
// Helper functions for time calculation
function timeToSeconds(timeStr) {
  let [hh, mm, ss] = timeStr.split(':').map(Number);
  return (hh * 3600) + (mm * 60) + ss;
}

function secondsToTime(totalSeconds) {
  let hours = Math.floor(totalSeconds / 3600);
  let minutes = Math.floor((totalSeconds % 3600) / 60);
  let seconds = totalSeconds % 60;
  return [hours, minutes, seconds].map(unit => String(unit).padStart(2, '0')).join(':');
}

// Global variables
let totalHrs = "00:00:00";
let totalPageCount = 0;
let totalPercentage = 0;
let dataToSend = [];

// Recalculate totals whenever checkbox or percentage changes
function recalculateTotals() {
  let totalSeconds = 0;
  totalPageCount = 0;
  totalPercentage = 0;
  dataToSend = [];

  $('#non-workflow tbody tr').each(function () {
    let row = $(this);
    let checkbox = row.find('input.approval-checkbox');
    let percentageInput = row.find('input.percent-input');

    let approved = checkbox.is(':checked');
    let percentage = parseFloat(percentageInput.val()) || 0;
    let pageCount = parseInt(row.find('td').eq(4).text()) || 0;
    let pt = row.find('td').eq(6).text().trim() || "00:00:00";
    let id = row.find('button[data-delete]').data('delete');

    if (approved) {
      if (percentage <0) {
        percentageInput.addClass('is-invalid');
        checkbox.prop('checked', false);
        alert("Approved item must have a percentage greater than 0.");
        return;
      } else {
        percentageInput.removeClass('is-invalid');
        totalSeconds += timeToSeconds(pt);
        totalPageCount += pageCount;
        totalPercentage += percentage;

        dataToSend.push({
          id: id,
          production_time: pt,
          page_count: pageCount,
          percentage: percentage,
          approved: 1
        });
      }
    }
  });

  totalHrs = secondsToTime(totalSeconds);
  $('#totalHrs').text(totalHrs);
  $('#totalPages').text(totalPageCount);
  $('#totalPercentage').text(totalPercentage.toFixed(2));
}

// Auto-check approval checkbox when percentage is typed
$(document).on('input', '.percent-input', function () {
  let row = $(this).closest('tr');
  let checkbox = row.find('.approval-checkbox');
  let percentage = parseFloat($(this).val());

  if (percentage > 0) {
    checkbox.prop('checked', true);
  } else {
    checkbox.prop('checked', false);
  }

  recalculateTotals();
});

// Trigger recalc when checkbox is clicked
$(document).on('change', '.approval-checkbox', function () {
  recalculateTotals();
});
function sumTime(time1, time2) {
    const [h1, m1, s1] = time1.split(':').map(Number);
    const [h2, m2, s2] = time2.split(':').map(Number);

    let seconds = s1 + s2;
    let minutes = m1 + m2 + Math.floor(seconds / 60);
    let hours = h1 + h2 + Math.floor(minutes / 60);

    seconds %= 60;
    minutes %= 60;

    return [
        String(hours).padStart(2, '0'),
        String(minutes).padStart(2, '0'),
        String(seconds).padStart(2, '0')
    ].join(':');
}

// Submit approval
$('#submitApproval').on('click', function () {
  let approvalcomments = $('#approval_comments').val() || "no comments";

  let usersDataMap = {};

  $('#non-workflow tbody tr').each(function () {
    let row = $(this);
    let approved = row.find('input.approval-checkbox').is(':checked');

    if (!approved) return;

    let id = row.find('button[data-delete]').data('delete');
    let percentage = parseFloat(row.find('input.percent-input').val()) || 0;
    let pageCount = parseInt(row.find('td').eq(4).text()) || 0;
    let pt = row.find('td').eq(6).text() || "00:00:00";
    let user_id = row.find('span[data-user-id]').data('user-id');
    let user_name = row.find('td').eq(0).text().trim();

    if (!usersDataMap[user_id]) {
      usersDataMap[user_id] = {
        user_id: user_id,
        user_name: user_name,
        total_page_count: 0,
        total_percentage: 0,
        total_hrs: "00:00:00",
        approval_comments: approvalcomments,
        nw_date: '<?php echo date('Y-m-d', strtotime($_REQUEST['from_dt'])); ?>',
        records: []
      };
    }

    usersDataMap[user_id].records.push({
      id: id,
      production_time: pt,
      page_count: pageCount,
      percentage: percentage,
      approved: 1
    });

    // Update user-level totals
    usersDataMap[user_id].total_page_count += pageCount;
    usersDataMap[user_id].total_percentage += percentage;

    // Add production time
    usersDataMap[user_id].total_hrs = sumTime(usersDataMap[user_id].total_hrs, pt);
  });

  let finalUsersData = Object.values(usersDataMap);

  if (finalUsersData.length === 0) {
    alert('No approved rows selected.');
    return;
  }

  $.ajax({
    url: 'submit-nonworkflow-approval.php',
    type: 'POST',
    data: { users: finalUsersData },
    success: function (response) {
      let res;
      try {
        res = JSON.parse(response);
      } catch (e) {
        alert('Unexpected server response.');
        return;
      }

      if (res.status === 'success') {
        alert('Submitted successfully');
        table.ajax.reload();
      } else {
        alert('Error: ' + (res.message || 'Submission failed'));
      }
    },
    error: function () {
      alert('AJAX request failed');
    }
  });
});

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
            $('#user_id').html('<option value="">Select User first</option>');
            
        }
    });

	
	});		

		</script>
    
   
  
  </body>
</html>

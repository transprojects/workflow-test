<?php include "includes/includes.php";
include ('includes/paginate.php'); 
?>
<?php
function getHoursMinutes($seconds, $format = '%02d:%02d') {
    if (empty($seconds) || !is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = $minutes % 60;

    return sprintf($format, $hours, $remainMinutes);
}

extract($_POST);

// Initialize filters
$search = $search1 = $search2 = $search3 = $search4 = $search_cd = $search_cm = $search_cy = $ccc = "";

if(isset($_REQUEST['wgetaction']))
{
// Filter: Customer ID
if (!empty($_REQUEST['cust_id']) && $_REQUEST['cust_id'] !== 'all') {
    $ccc .= " AND wd.cust_id = '{$_REQUEST['cust_id']}' ";
}

// Filter: Job ID
if (!empty($_REQUEST['j_id']) && $_REQUEST['j_id'] !== 'all') {
    $ccc .= " AND j.j_id = '{$_REQUEST['j_id']}' ";
}

// Filter: Stage
if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] !== 'all') {
    $search2 .= " AND wd.stage LIKE '%{$_REQUEST['stage']}%' ";
}


// Filter: Platform
if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== 'all') {
    $ccc .= " AND j.j_platform = '{$_REQUEST['dp_platform']}' ";
}


// Handle Date Filters
$dateWise = $_REQUEST['date_wise'] ?? '';



    if ($dateWise === "d" && !empty($_REQUEST['from_dt'])) {
        $frm = date('Y-m-d', strtotime($_REQUEST['from_dt']));
        $to = date('Y-m-d', strtotime($_REQUEST['to_dt']));
        $search .= " AND DATE_FORMAT(wd.sent_date,'%Y-%m-%d') BETWEEN '$frm' AND '$to' ";
    } elseif ($dateWise === "m" && !empty($_REQUEST['mfrom_dt'])) {
        $search .= " AND DATE_FORMAT(wd.sent_date,'%m-%Y') = '{$_REQUEST['mfrom_dt']}' ";
    } elseif ($dateWise === "y" && !empty($_REQUEST['yfrom_dt'])) {
        $search .= " AND DATE_FORMAT(wd.sent_date,'%Y') = '{$_REQUEST['yfrom_dt']}' ";
    } else {
        $search3 .= " AND wd.sent_date >= '2020-03-01' ";
        $search4 .= " AND wd.sent_date >= '2020-03-01' ";
    }

    if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== "all") {
        $search .= " AND j.j_platform = '{$_REQUEST['dp_platform']}' ";
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

<style>
/* Pagination styles */
.pagination {
    display: inline-block;
    padding-left: 0;
    margin: 20px 0;
    border-radius: 4px;
    float: right; /* Align pagination to the right */
}

.pagination > li {
    display: inline;
}

.pagination > li > a,
.pagination > li > span {
    position: relative;
    float: left;
    padding: 6px 12px;
    margin-left: -1px;
    line-height: 1.42857143;
    color: #F26722; /* Orange color */
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #ddd;
}

.pagination > li:first-child > a,
.pagination > li:first-child > span {
    margin-left: 0;
    border-bottom-left-radius: 4px;
    border-top-left-radius: 4px;
}

.pagination > li:last-child > a,
.pagination > li:last-child > span {
    border-bottom-right-radius: 4px;
    border-top-right-radius: 4px;
}

.pagination > li > a:hover,
.pagination > li > span:hover,
.pagination > li > a:focus,
.pagination > li > span:focus {
    background-color: #eee;
}

.pagination > .active > a,
.pagination > .active > span,
.pagination > .active > a:hover,
.pagination > .active > span:hover,
.pagination > .active > a:focus,
.pagination > .active > span:focus {
    z-index: 2;
    color: #fff;
    background-color: #F26722; /* Orange color */
    border-color: #F26722; /* Orange color */
    cursor: default;
}

/* Disabled styles */
.pagination > .disabled > span,
.pagination > .disabled > a,
.pagination > .disabled > a:hover,
.pagination > .disabled > a:focus,

/* 
.datepicker-month-year-only .ui-datepicker-calendar {
    display: none;
}
 */
    #loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            text-align: center;
            z-index: 9999;
        }

        #loader img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
		.table-container-fixed {
			max-height: 500px; /* Adjust height as needed */
			overflow-y: auto;
			border: 1px solid #dee2e6;
		}

		.table-sticky thead th {
			position: sticky;
			top: 0;
			background-color: #343a40;
			color: #fff;
			z-index: 1;
			text-align: center;
		}
		.table-sticky td, .table-sticky th {
			white-space: nowrap;
			vertical-align: middle;
		}
</style>

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="containter-fluid">
        <div class="container-fluid mt-4">
          <p class="reportsectionheading ms-4">Delivery Performance Report</p>
        </div>
		<form action="#" id="validation-form" method="post" autocomplete="off">
        <div class="container d-flex justify-content-center">
          <div
            class="col-12 col-sm-8 d-flex justify-content-center flex-column mt-4 monthly1outer"
          >
            <div class="d-flex justify-content-center">
              <div class="radio d-flex col-2 mt-4">
                <input type="radio"   name="date_wise" <?php if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') { ?> checked <?php } ?> id="daywise" value="d" />
                <label class="ms-2">Day</label>
              </div>
              <div class="radio d-flex col-2 mt-4">
                <input type="radio" name="date_wise" <?php if ($_REQUEST['date_wise'] == 'm') { ?> checked <?php } ?> id="monthwise" value="m" />
                <label class="ms-2">Month</label>
              </div>
            </div>
            <!-- <div class="container"> -->
            <div class="row mt-4 m-5">
              <div class="col-12 col-lg-4">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id">
												    <option value="all" <?php if ($_REQUEST['cust_id'] == 'all') { ?> selected <?php } ?> >Select All</option>

										  <?php
										  if($_COOKIE['user_level']=='3')
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if ($_REQUEST['cust_id'] == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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

              <div class="col-12 col-lg-4">
                  <label class="mb-2">Choose Journal</label>
                  <select class="form-select" data-placeholder="Select Journal" name="j_id" id="j_id">
                  <option value="all" <?php if ($_REQUEST['j_id'] == 'all') { ?> selected <?php } ?> >Select All</option>
                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if ($_REQUEST['j_id'] == $res_journals['j_id']) { ?> selected <?php } ?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
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
                <div class="col-12 col-lg-4">
                  <label class="mb-2">Stage</label>
                  <select class="form-select customer_drop" data-placeholder="Select Stage" name="stage" id="stage">
                  <option value="all" <?php if ($_REQUEST['stage'] == 'all') { ?> selected <?php } ?> >Select All</option>
                               <?php foreach($report_stage_types as $id=>$val){
                                if(isset($_REQUEST['stage']))
													{
										    	?>
										       <option <?php if ($_REQUEST['stage'] == $id) { ?> selected <?php } ?> value="<?php echo $id ?>"style="font-weight:500;"><?php echo $val ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $id ?>"style="font-weight:500;"><?php echo $val ?> </option>
													<?php
													}
                                   } ?>
                </select>
                </div>
			<div class="col-12 col-lg-4">
                  <label class="mb-2">Choose Platform</label>
                  <select class="form-select customer_drop" data-placeholder="Select Stage" name="dp_platform" id="dp_platform">
                  <option value="all" <?php if ($_REQUEST['stage'] == 'all') { ?> selected <?php } ?> >Select All</option>
                            <?php foreach ($platform_journal_array as $id => $val) { ?>
            <option <?php if (
               $_REQUEST["dp_platform"] == $val
               ) { ?>selected<?php } ?> value="<?php echo $val; ?>"><?php echo $val; ?></option>
            <?php } ?>
                </select>
             </div>
	
			    <div class="col-12 col-lg-8" id="day_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
                <div class="row">
              <div class="col-12 col-lg-6">
                <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control date-picker monthlyinputdate" id="from_dt" name="from_dt" style="border-color: #dee2e6 !important"  value="<?php
                                                                                    if ($_REQUEST['from_dt'] != '') {
                                                                                        echo $_REQUEST['from_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('-5 days'));
                                                                                    }
                                                                                    ?>" readonly />
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input
                    type="text" id="to_dt" name="to_dt" class="form-control monthlyinputdate1" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['to_dt'] != '') {
                                                                                        echo $_REQUEST['to_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('+2 days'));
                                                                                    }
                                                                                    ?>" 
                  />
                </div>
              </div>
                </div>
			  </div>

			   <div class="col-12 col-lg-8" id="month_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
                                                                       
			<div class="col-12 col-lg-6">
                <label class="mb-2">Month</label>
                <div class="d-flex">
                  <input type="text" id="mfrom_dt" name="mfrom_dt"  placeholder="Select Month"  class="form-control monthlyinputdate2" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['mfrom_dt'] != '') {
                                                                                        echo $_REQUEST['mfrom_dt'];
                                                                                    } else {
                                                                                        echo date('m-Y');
                                                                                    }
                                                                                    ?>" />
                </div>
              </div>
                 </div>
            </div>
            <!-- </div> -->
            <div class="col-12 mt-1 mb-4 d-flex justify-content-center">
			<input type="hidden" name="filter" id="filter" value="1">
              <button class="resultbtn" type="submit"  name="wgetaction" >Get Result</button>
              <button class="refreshbtn ms-4">
               <a href="deliveryperformance"> <img src="./assets/images/refresh.svg" /></a>
              </button>
            </div>
          </div>
        </div>
	</form>


        <!-- Table section -->
		<?php
		if(isset($_REQUEST['filter']))
		{
		?>
	             <div class="container table-responsive custom-container p-4 " style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
            <div class="container-fluid table-responsive" style="overflow: auto">
			
			<?php 
			    // ------------------------------
// 1. Main Dispatch History Query
// ------------------------------
$query = "
    SELECT 
        wd.*, 
        c.cust_name, 
        j.j_code,
        wdd.pub_id,
        wdd.article_title,
        wdd.recv_dt,
        wdd.due_dt,
        wdd.created_dt,
        wdd.page_count,
        wdd.cust_id,
        wdd.inw_type
    FROM inw_dispatch_history AS wd
    JOIN inw_inward_dtl AS wdd ON wd.a_id = wdd.id
    JOIN adm_customer_master AS c ON wd.cust_id = c.id
    JOIN adm_journals AS j ON wd.j_id = j.j_id
    WHERE wdd.article_title NOT LIKE '%_TOC'
    $ccc $search $search2 $search4
";

$cnt_query = "
    SELECT COUNT(wd.a_id) AS num
    FROM inw_dispatch_history AS wd
    JOIN inw_inward_dtl AS wdd ON wd.a_id = wdd.id
    JOIN adm_customer_master AS c ON wd.cust_id = c.id
    JOIN adm_journals AS j ON wd.j_id = j.j_id
    WHERE wdd.article_title NOT LIKE '%_TOC'
    $ccc $search $search2 $search4
";

$cnt_result = $db->query($cnt_query) or die($db->error);
$page_count  = $cnt_result->fetch_assoc();
$total_pages = $page_count['num'];

// Get dispatch history articles
$articles = $db->query($query) or die($db->error);

// ------------------------------
// 2. Preload Inward Details
// ------------------------------
$article_ids = [];
$dispatchData = [];
while ($row = $articles->fetch_assoc()) {
    $article_ids[] = $row['a_id'];
    $dispatchData[$row['a_id']][] = $row; // In case you have multiple dispatches per article
}

// Reset pointer on articles array for later looping if needed
$articles->data_seek(0);

// Build comma-separated list for queries
$id_list = implode(",", array_map('intval', $article_ids));

// Fetch all matching inward records in one query
$inward_result = $db->query("SELECT * FROM inw_inward_dtl WHERE id IN ($id_list)") or die($db->error);
$inward_data = [];
while ($row = $inward_result->fetch_assoc()) {
    $inward_data[$row['id']] = $row;
}

// ------------------------------
// 3. Preload Transactions Aggregates
// ------------------------------
// Calculate both overall total time and pre-editing (dept22) time.
$trans_sql = "
    SELECT 
        t.project_id,
        SUM(TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)) AS total_time_all,
        SUM(
            CASE
                WHEN t.dept = 22 AND d.cust_id != '33' THEN TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)
                WHEN t.dept = 24 AND d.cust_id = '33' AND d.inw_type = 'Latex' THEN TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)
                WHEN t.dept = 1 AND d.cust_id = '33' AND d.inw_type != 'Latex' THEN TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)
                WHEN t.dept = 1 AND d.cust_id != '33' AND d.inw_type != 'Latex' THEN TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)
                WHEN t.dept = 2 AND d.cust_id = '7' THEN TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)
                ELSE 0
            END
        ) AS total_time_dept22
    FROM inw_transactions AS t
    JOIN inw_inward_dtl AS d ON d.id = t.project_id
    WHERE t.project_id IN ($id_list)
    GROUP BY t.project_id
";

$trans_result = $db->query($trans_sql) or die($db->error);
$trans_time_data = [];
while ($row = $trans_result->fetch_assoc()) {
    $trans_time_data[$row['project_id']] = $row;
}

// ------------------------------
// 4. Preload Revisions Data for REV and FIN stages
// ------------------------------
$revision_sql = "
    SELECT *
    FROM inw_revisions_dtl 
    WHERE inw_id IN ($id_list)
";
$revision_result = $db->query($revision_sql) or die($db->error);
$revision_data = [];  // Structure: $revision_data[inw_id][type][revision_count] = row
while ($row = $revision_result->fetch_assoc()) {
    $revision_data[$row['inw_id']][$row['type']][$row['revision_count']] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Performance Report</title>
    <style>
        /* Add CSS styling as needed */
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; }
        .thcolor { background-color: #f0f0f0; }
        .page-header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Delivery Performance Report <b style="color:red;font-size:14px;"> - Total count: <?php echo $total_pages; ?></b></h1>
    </div>

	  <table class="table table-bordered table-sticky display nowrap" id="dispatchTable" style="width:100%">
            <thead class="table-dark">
                <tr class="text-center align-middle">
                <th>Client</th>
                <th>Journal Name</th>
                <th>FileName</th>
                <th>Stage</th>
                <th>Received Date</th>
                <th>Due Date</th>
                <th>Dispatched Date</th>
                <th class="thcolor">Pre Editing Time Taken</th>
                <th class="thcolor">Total Time Taken</th>
                <th>Page Count</th>
                <th>Schedule</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Loop through each dispatch record using the preloaded data
            foreach ($dispatchData as $a_id => $dispatch_list) {
                foreach ($dispatch_list as $row_history) {
                    // Use a prepared inward record
                    $query_disp = $inward_data[$a_id];
                    
                    // Retrieve preloaded transaction aggregates
                    $total_time_all   = isset($trans_time_data[$a_id]['total_time_all']) ? $trans_time_data[$a_id]['total_time_all'] : 0;
                    $total_time_dept22= isset($trans_time_data[$a_id]['total_time_dept22']) ? $trans_time_data[$a_id]['total_time_dept22'] : 0;
                    
                    // Determine stage type (e.g. "FP", "REV", "FIN", "ISSCOR")
                    $stage = $row_history['stage'];
                    $stage_clean = preg_replace(["/REV([0-9]+)/", "/ISSCOR([0-9]+)/"], ["REV", "ISSCOR"], $stage);
                    
                    // Determine dates and page count based on stage
                    $due_dt = $recv_dt = '';
                    $page_count = $query_disp['page_count'];
                    
                    // For Final Proof stage
                    if ($stage == "FP") {
                        $recv_dt = $query_disp['recv_dt'];
                        $due_dt  = $query_disp['due_dt'];
                    }
                    // For Revision or Final stage
                    elseif (preg_match("/^(REV|FIN)/", $stage, $matches)) {
                        $stage_type = $matches[1]; // either REV or FIN
                        $rev_num = trim(str_replace($stage_type, "", $stage));
                        $revision = isset($revision_data[$a_id][$stage_type][$rev_num]) ? $revision_data[$a_id][$stage_type][$rev_num] : null;
                        if ($revision) {
                            $recv_dt = $revision['received_date'];
                            $due_dt  = $revision['due_date'];
                        }
                    }
                    // For ISSCOR or any other type, default to inward dates
                    else {
                        $recv_dt = $query_disp['created_dt'];
                        $due_dt  = $query_disp['due_dt'];
                    }
                    
                    // Format dates for display
                    $recv_dt_fmt = $recv_dt ? date('d M Y', strtotime($recv_dt)) : '';
                    $due_dt_fmt  = $due_dt  ? date('d M Y', strtotime($due_dt))  : '';
                    $send_dt     = $row_history['sent_date'];
                    $send_dt_fmt = date('d M Y', strtotime($send_dt));
                    
                    // Determine schedule status based on dispatched date vs due date
                    $send_time = strtotime($send_dt);
                    $due_time  = strtotime($due_dt);
                    if ($send_time == $due_time) {
                        $schedule = "On Time";
                    } elseif ($send_time > $due_time) {
                        $schedule = "Delay";
                    } else {
                        $schedule = "Ahead";
                    }
                    ?>
                    <tr>
                        <td><?php echo $row_history['cust_name']; ?></td>
                        <td><?php echo $row_history['j_code']; ?></td>
                        <td><?php echo $query_disp['pub_id']; ?></td>
                        <td><?php echo $row_history['stage']; ?></td>
                        <td><?php echo $recv_dt_fmt; ?></td>
                        <td><?php echo $due_dt_fmt; ?></td>
                        <td><?php echo $send_dt_fmt; ?></td>
                        <td><?php echo getHoursMinutes($total_time_dept22); ?></td>
                        <td><?php echo getHoursMinutes($total_time_all); ?></td>
                        <td><?php echo $page_count; ?></td>
                        <td><?php echo $schedule; ?></td>
                    </tr>
                    <?php
                } // end foreach dispatch record
            } // end foreach article
            ?>
        </tbody>
    </table>   
														
			<div id="article_list">
			</div>			
	
          
        </div>
            </div>
			<?php 
		}
		?>
        <!-- Table section starts-->
	<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
        
      </div>
      <!-- Table section ends -->
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 




 
    



<!-- Footer Section -->
<?php echo scripts(); ?>	

<!-- jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

<!-- jQuery UI (Datepicker) -->
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->

    <!-- Date picker functionality -->
	

<script>
$(document).ready(function () {
    $('#dispatchTable').DataTable({
        dom: 'Bfrtip',  // Show buttons (B), filter (f), table (t), info (i), pagination (p)
        buttons: [
            {
                extend: 'collection',
                text: 'Export Options',
                buttons: [
                    'excelHtml5',
                    'pdfHtml5',
                    'csvHtml5',
                    'print'
                ]
            }
        ],
			pageLength: 50,
            responsive: true
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
		
   $(".monthlyinputdate2").datepicker({
        uiLibrary: "bootstrap5",
        dateFormat: "mm-yy", // Format to display
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true, // Add this to show the "Done" button
        beforeShow: function(input, inst) {
            // Remove the days
            var datepicker = $(this).datepicker('widget');
            datepicker.find('.ui-datepicker-calendar').remove();
        },
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, month, 1));
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
          function populateSelecte(elementId, data) {
               var selectElement = $("#" + elementId);
               selectElement.empty();
         selectElement.append($("<option>").text("Select All").val("all"));
         
               $.each(data, function(index, item) {
                   selectElement.append($("<option>").text(item.j_code).val(item.j_id));
               });
           }
        
		 
		
            $("#yearwise").click(function () {
                $("#year_div").show();
                $("#yfrom_dt").val(<?php echo date('Y');?>);
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").hide();
                $("#from_dt").removeAttr('value');
                $("#to_dt").removeAttr('value');				
            });
            $("#monthwise").click(function () {
				$("#month_div").show();
                $("#year_div").hide();
				$("#day_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#from_dt").removeAttr('value');				
                $("#to_dt").removeAttr('value');				
				var vals=(date.getMonth()+1)+"-"+date.getFullYear();
                $("#mfrom_dt").val(vals);
        
            });
            $("#daywise").click(function () {
                $("#year_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").show();
				var vals=date.getDate()+"-"+(date.getMonth()+1)+"-"+date.getFullYear();
				$("#from_dt").attr("value");
				$("#to_dt").attr("value");
                $("#from_dt").val(vals);
                $("#to_dt").val(vals);
            });
			
			 $(".fydatepicker").datepicker({
            format: "yyyy",
            viewMode: "years",
            minViewMode: "years"
        });
        $(".mydatepicker").datepicker({
            format: "mm-yyyy",
            viewMode: "months",
            minViewMode: "months"
        });
       });
    </script>

 

<?php include "includes/includes.php";
include ('includes/paginate.php'); 
?>
<?php
//echo $_SESSION['cust_id'];
if(isset($_REQUEST['id']) && $_REQUEST['id']!='' && $_REQUEST['action']=='delete')
{	
	$db->query("delete from inw_inward_dtl where id='".$_REQUEST['id']."'");
	header("location:added_articles.php?msg=Successfully deleted");
	die();	
	
}
function differenceInHours($startdate,$enddate){
	/*$starttimestamp = strtotime($startdate);
	$endtimestamp = strtotime($enddate);
	$difference = abs($endtimestamp - $starttimestamp)/3600;
	return round($difference,2);*/
	
	$datetime1 = new DateTime($startdate);
	$datetime2 = new DateTime($enddate);
	$interval = $datetime1->diff($datetime2);
	echo $interval->format('%h')." Hours ".$interval->format('%i')." Minutes ".$interval->format('%s')." Seconds";
}

if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	

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
          <p class="reportsectionheading ms-4">Consildated Target Report</p>
        </div>
		<form action="#" id="validation-form" method="post" autocomplete="off">
        <div class="container d-flex justify-content-center">
          <div
            class="col-12 col-sm-12 d-flex justify-content-center flex-column mt-4 monthly1outer"
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
			 

			    <div class="col-12 col-lg-6" id="day_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
            <div class="row">
              <div class="col-12 col-lg-4">
                <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control date-picker monthlyinputdate" id="from_dt" name="from_dt" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['from_dt'] != '') {
                                                                                        echo $_REQUEST['from_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('-1 days'));
                                                                                    }
                                                                                    ?>" readonly />
                </div>
              </div>
              <div class="col-12 col-lg-4">
                <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input
                    type="text" id="to_dt" name="to_dt" class="form-control monthlyinputdate1" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['to_dt'] != '') {
                                                                                        echo $_REQUEST['to_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('-1 days'));
                                                                                    }
                                                                                    ?>"/>
                </div>
              </div>
              </div>
			    </div>

			   <div class="col-12 col-lg-6" id="month_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
                                                                       
				<div class="col-12 col-lg-4">
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
              <button class="resultbtn" type="submit"  name="getaction" >Get Result</button>
              <div class="refreshbtn ms-4 p-2">
               <a href="consolidatedtargetreport"> <img src="./assets/images/refresh.svg" /></a>
              </div>
            </div>
          </div>
        </div>
	</form>


        <!-- Table section -->
		<?php 
				//echo $var;
if($_POST['from_dt']!="" || $_POST['mfrom_dt']!="" || $_POST['user_id']!="")
{
?>		
	
     <div class="container-fluid mt-4">
    <div class="table-responsive">
        <table class="table table-bordered display nowrap" id="monthlytable" style="width:100%">
            <thead class="table-dark">
                <tr class="text-center align-middle">
				<th style="position:sticky; left:0;z-index:100;">S.no</th>
				<th style="position:sticky; left:50px;z-index:100;">Employee Name</th>
				<?php if (rs_target_enabled('FP')) { ?><th>#FP</th><?php } ?>
				<?php if (rs_target_enabled('REV')) { ?><th>#REV</th><?php } ?>
				<th>#CORRECTIONS</th>
				<?php if (rs_target_enabled('FIN')) { ?><th>#FINALS</th><?php } ?>
<?php if (rs_target_enabled('PMC')) { ?><th>#PMC</th><?php } ?>
				<?php if (rs_target_enabled('ISS')) { ?><th>#ISSUE</th><?php } ?>
<?php if (rs_target_enabled('JSTAGE')) { ?><th>#JSTAGE</th><?php } ?>
				<th>#NON-WORKFLOW</th>
				<th>#NON-WORKFLOW PRODUCTION TIME</th>
				<th>#COMPLEXITY</th>
				<th>#TOTAL PAGE COUNT</th>
				<th>#TOTAL PRODUCTION TIME</th>
				<th>#TOTAL WORKING TIME</th>
				<th>#WORKING DAYS</th>
				<th>#ADJUSTED WORKING DAYS</th>
				<th>#FEEDBACK COUNT</th>
				<th>#ACHIEVED TARGET %</th>
</tr>
            </thead>
            <tbody>

       <?php
$sno = 1;

$start_date = '';
$end_date = '';
$interval_days = 1; // Avoid divide by zero

if (!empty($_REQUEST['from_dt']) && $_REQUEST['date_wise'] == 'd') {
    $start_date = date('Y-m-d', strtotime($_REQUEST['from_dt']));
    $end_date = date('Y-m-d', strtotime($_REQUEST['to_dt']));
    $interval_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
} elseif (!empty($_REQUEST['mfrom_dt']) && $_REQUEST['date_wise'] == 'm') {
    $monthYear = date('Y-m', strtotime("01-" . $_REQUEST['mfrom_dt']));
    $start_date = date('Y-m-01', strtotime($monthYear));
    $end_date = date('Y-m-t', strtotime($monthYear));
    $interval_days = date('t', strtotime($start_date));
}
	
	$user_condition= "and u.department_id = '" . $_COOKIE['department_id'] . "' and uta.user_id= '" . $_COOKIE['user_id'] . "'  ";	
	
// Get all users in the department, and left join analytics


$query = mysqli_query($db, "
SELECT *
FROM (
    SELECT 
        u.id AS user_id,
        u.full_name AS user_name,

        -- Workflow totals
        COALESCE(uta.total_fp, 0) AS total_fp,
        COALESCE(uta.total_rev, 0) AS total_rev,
        COALESCE(uta.total_corr, 0) AS total_corr,
        COALESCE(uta.total_finals, 0) AS total_finals,
        COALESCE(uta.total_issue, 0) AS total_issue,
        COALESCE(uta.total_jstage, 0) AS total_jstage,
        COALESCE(uta.total_pmc, 0) AS total_pmc,
        COALESCE(uta.total_worked_pages, 0) AS total_worked_pages,

        -- Combined Production Time
        SEC_TO_TIME(COALESCE(uta.total_production_seconds, 0) + COALESCE(nwp.total_nw_pt_seconds, 0)) AS total_production_time,

        -- Total Working Time
        SEC_TO_TIME(COALESCE(uta.total_working_seconds, 0)) AS total_working_time,

        -- Average % (workflow + non-workflow)
        ROUND((COALESCE(uta.total_percentage, 0) + COALESCE(nwp.total_nw_percentage, 0)) / NULLIF(COALESCE(wd.total_working_days, 0), 0), 2) AS avg_percentage,

        -- Non-workflow % only
        ROUND(COALESCE(nwp.total_nw_percentage, 0) / NULLIF(COALESCE(wd.total_working_days, 0), 0), 2) AS non_workflow,

        -- Workflow % only
        COALESCE(uta.total_percentage, 0) AS total_workflow_percentage,

        -- Non-workflow production time
        SEC_TO_TIME(COALESCE(nwp.total_nw_pt_seconds, 0)) AS total_nw_pt_time,

        -- File complexity count
        COALESCE(fcu.file_complexity_count, 0) AS file_complexity_count,

        -- Internal Feedback Count
        COALESCE(feedbacks.feedback_count, 0) AS internal_feedback_count,

        -- Working Days
        COALESCE(wd.total_working_days, 0) AS total_working_days,
        COALESCE(wd.adjusted_working_days, 0) AS adjusted_working_days

    FROM users u

    -- Workflow Aggregation
    LEFT JOIN (
        SELECT 
            user_id,
            SUM(total_fp) AS total_fp,
            SUM(total_rev) AS total_rev,
            SUM(total_corr) AS total_corr,
            SUM(total_finals) AS total_finals,
            SUM(total_issue) AS total_issue,
            SUM(total_jstage) AS total_jstage,
            COALESCE(SUM(total_pmc), 0) AS total_pmc,
            SUM(total_worked_pages) AS total_worked_pages,
            SUM(TIME_TO_SEC(total_production_time)) AS total_production_seconds,
            SUM(TIME_TO_SEC(total_working_time)) AS total_working_seconds,
            SUM(total_percentage) AS total_percentage
        FROM user_target_analytics
        WHERE from_date >= '$start_date' AND to_date <= '$end_date' AND report = 1
        GROUP BY user_id
    ) uta ON uta.user_id = u.id

    -- Non-workflow Aggregation
    LEFT JOIN (
        SELECT 
            user_id, 
            SUM(nw_percentage) AS total_nw_percentage,
            SUM(TIME_TO_SEC(nw_pt)) AS total_nw_pt_seconds
        FROM non_workflow_user_percentage
        WHERE nw_date >= '$start_date' AND nw_date <= '$end_date'
        GROUP BY user_id
    ) nwp ON nwp.user_id = u.id

    -- Adjusted Working Days Calculation
    LEFT JOIN (
        SELECT 
            combined.user_id,
            COUNT(DISTINCT combined.work_date) AS total_working_days,
            SUM(
                CASE 
                    WHEN combined.total_seconds >= TIME_TO_SEC('07:00:00') THEN 1
                    WHEN combined.total_seconds > 0 THEN 0.5
                    ELSE 0
                END
            ) AS adjusted_working_days
        FROM (
            -- Workflow days + optional non-workflow
            SELECT 
                w.user_id, 
                w.work_date,
                w.total_wf_seconds + COALESCE(n.total_nw_seconds, 0) AS total_seconds
            FROM (
                SELECT user_id, from_date AS work_date, SUM(TIME_TO_SEC(total_production_time)) AS total_wf_seconds
                FROM user_target_analytics
                WHERE from_date >= '$start_date' AND to_date <= '$end_date' AND report = 1
                GROUP BY user_id, from_date
            ) w
            LEFT JOIN (
                SELECT user_id, nw_date, SUM(TIME_TO_SEC(nw_pt)) AS total_nw_seconds
                FROM non_workflow_user_percentage
                WHERE nw_date >= '$start_date' AND nw_date <= '$end_date'
                GROUP BY user_id, nw_date
            ) n ON w.user_id = n.user_id AND w.work_date = n.nw_date

            UNION

            -- Non-workflow-only days
            SELECT 
                n.user_id,
                n.nw_date AS work_date,
                COALESCE(w.total_wf_seconds, 0) + n.total_nw_seconds AS total_seconds
            FROM (
                SELECT user_id, nw_date, SUM(TIME_TO_SEC(nw_pt)) AS total_nw_seconds
                FROM non_workflow_user_percentage
                WHERE nw_date >= '$start_date' AND nw_date <= '$end_date'
                GROUP BY user_id, nw_date
            ) n
            LEFT JOIN (
                SELECT user_id, from_date AS work_date, SUM(TIME_TO_SEC(total_production_time)) AS total_wf_seconds
                FROM user_target_analytics
                WHERE from_date >= '$start_date' AND to_date <= '$end_date' AND report = 1
                GROUP BY user_id, from_date
            ) w ON w.user_id = n.user_id AND w.work_date = n.nw_date
            WHERE w.user_id IS NULL
        ) AS combined
        GROUP BY combined.user_id
    ) wd ON wd.user_id = u.id

    -- File Complexity Count
    LEFT JOIN (
        SELECT user_id, COUNT(*) AS file_complexity_count
        FROM wf_file_complexity_users
        WHERE fcu_date >= '$start_date' AND fcu_date <= '$end_date'
          AND approval_status = 'Approved'
          AND file_status = 'complex'
        GROUP BY user_id
    ) fcu ON fcu.user_id = u.id

    -- Internal Feedback Count
    LEFT JOIN (
        SELECT u_id, COUNT(*) AS feedback_count
        FROM inw_internal_feedback
        WHERE created_on >= '$start_date' AND created_on <= '$end_date'
        GROUP BY u_id
    ) feedbacks ON feedbacks.u_id = u.id

    WHERE 1=1 $user_condition
    GROUP BY u.id

) main_query
WHERE main_query.total_workflow_percentage >= 0
ORDER BY main_query.avg_percentage DESC;
");


$sno = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $avg = $row['avg_percentage'];
    $bgColor = '';

    if ($avg < 74) {
        $bgColor = 'background-color: #f8d7da; color: #721c24;';
    } elseif ($avg < 94) {
        $bgColor = 'background-color: #fff3cd; color: #856404;';
    } else {
        $bgColor = 'background-color: #d4edda; color: #155724;';
    }
	$adjustedDays = $row['adjusted_working_days'];

$adjustedBg = '';
if ($adjustedDays >= 20) {
    $adjustedBg = 'background-color: #d4edda; color: #155724;'; // Green
} elseif ($adjustedDays >= 10) {
    $adjustedBg = 'background-color: #fff3cd; color: #856404;'; // Yellow
} elseif ($adjustedDays > 0) {
    $adjustedBg = 'background-color: #f8d7da; color: #721c24;'; // Red
}
?>
   <tr>
    <td style="position:sticky; left:0;"><?php echo $sno++; ?></td>
    <td style="position:sticky; left:50px;"><?php echo $row['user_name']; ?></td>
    <?php if (rs_target_enabled('FP')) { ?><td><?php echo $row['total_fp']; ?></td><?php } ?>
    <?php if (rs_target_enabled('REV')) { ?><td><?php echo $row['total_rev']; ?></td><?php } ?>
    <td><?php echo $row['total_corr']; ?></td>
    <?php if (rs_target_enabled('FIN')) { ?><td><?php echo $row['total_finals']; ?></td><?php } ?>
<?php if (rs_target_enabled('PMC')) { ?><td><?php echo $row['total_pmc']; ?></td><?php } ?>
    <?php if (rs_target_enabled('ISS')) { ?><td><?php echo $row['total_issue']; ?></td><?php } ?>
        <?php if (rs_target_enabled('JSTAGE')) { ?><td><?php echo $row['total_jstage']; ?></td><?php } ?>
    <td><?php echo $row['non_workflow']; ?>%</td>
    <td><?php echo $row['total_nw_pt_time']; ?></td>

    <td style="background-color: <?php echo ($row['file_complexity_count'] > 0) ? '#f8d7da; color: #721c24;' : 'inherit'; ?>;">
        <?php echo $row['file_complexity_count']; ?>
    </td>

    <td><?php echo $row['total_worked_pages']; ?></td>
    <td><?php echo $row['total_production_time']; ?></td>
    <td><?php echo $row['total_working_time']; ?></td>

    <td><?php echo $row['total_working_days']; ?></td>

    <!-- ✅ Highlighted adjusted days -->
    <td style="<?php echo $adjustedBg; ?>">
        <?php echo $row['adjusted_working_days']; ?>
    </td>
<td style="<?php echo ($row['internal_feedback_count'] > 0) ? 'background-color: #f8d7da; color: #721c24;' : ''; ?>">
    <?php echo $row['internal_feedback_count']; ?>
</td>
    <td style="<?php echo $bgColor; ?>">
        <?php echo $avg; ?>%
    </td>
</tr>
<?php
}
?>

      </tbody>
        </table>
    </div>
</div>
  
<?php 
}
?>
 
    <div id="article_list"></div>
    <div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
</div>
   
        <!-- Table section starts-->

        
      <!-- Table section ends -->
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

 


    



<!-- Footer Section -->
<?php echo scripts(); ?>	


<script>
$(document).ready(function () {
    $('#monthlytable').DataTable({
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




    <!-- Date picker functionality -->
    <script>
        $(function () {
var date = new Date(); // Today's date
date.setDate(date.getDate() - 1); // For yesterday if you want to disable from today onward
var today = new Date(); // If you want to allow today and disable only future dates

var fromdate;
$(".monthlyinputdate").datepicker({
    uiLibrary: "bootstrap5",
    dateFormat: "dd-mm-yy",
    autoclose: true,
    maxDate: date, // Disable today and future dates
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
    maxDate: date, // Disable today and future dates
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

    </script>

 <script>
 
           function loaddata(due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }

            function loaddata1(start_date,due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, sdt: start_date, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }

            function loaddata2(due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }
 $(document).ready(function () {
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
	
	<script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_consolidated_users.php',
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const deptSelect = document.getElementById('dep_id');
    const extraDropdown = document.getElementById('extra-dropdown');

    function toggleExtraDropdown() {
      if (deptSelect.value === '10') {
        extraDropdown.style.display = 'block';
      } else {
        extraDropdown.style.display = 'none';
      }
    }

    deptSelect.addEventListener('change', toggleExtraDropdown);

    // Trigger on page load (in case of default value or edit mode)
    toggleExtraDropdown();
  });

</script>
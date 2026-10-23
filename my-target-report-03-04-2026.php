<?php include "includes/includes.php";



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
	
$_SESSION['cuser_id'] = $_POST['user_id'];
$_SESSION['fcust_id'] = $_POST['cust_id'];
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['j_id'] = $_POST['j_id'];
$_SESSION['pub_id'] = $_POST['pub_id'];
$_SESSION['from_dt'] = $_POST['from_dt'];
$_SESSION['to_dt'] = $_POST['to_dt'];
$_SESSION['dep_id']=$_REQUEST['dep_id'];

$var = '';
if($_SESSION['fcust_id']!=''){
	
     $var.= "and c.id = '".$_SESSION['fcust_id']."' ";
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and wd.stage LIKE '%".$_SESSION['stage_id']."%' ";
}
if($_SESSION['j_id']!=''){
	
     $var.= "and j.j_id = '".$_SESSION['j_id']."' ";
}
if($_SESSION['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_SESSION['pub_id']."%' ";
}
if($_SESSION['cuser_id']!=''){
	
     $var.= "and inw.process_user= '".$_SESSION['cuser_id']."' ";
}

	$_SESSION['varname'] = $var;


	
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
          <p class="reportsectionheading ms-4">My Target Report</p>
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
              <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Department</label>
                 <?php
$query = "SELECT dept_name, dept_code           FROM `adm_dept_master`           WHERE FIND_IN_SET(dept_code, '{$_COOKIE['department_id']}')              OR FIND_IN_SET(dept_code, '{$_COOKIE['odepartment_id']}') 
          ORDER BY `dept_name` ASC";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
		
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
              <button class="refreshbtn ms-4">
               <a href="employeetargetreport"> <img src="./assets/images/refresh.svg" /></a>
              </button>
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
                  <tr>
                <th class="center">S.no</th>
                <th>Employee Name</th>
                <th>#FP</th>
                <th>#REV</th>
                <th>#CORRECTIONS</th>
                <th>#FINALS</th>
                <th>#ISSUE</th>
                <th>#NON-WORKFLOW</th>
                <th>#COMPLEXITY</th>
                <th>#TOTAL PAGE COUNT</th>
                <th>#ACHIEVED TARGET %</th>
            </tr>
            </thead>
        <tbody>

          <?php
$sno = 1;

$start_date = '';
$end_date = '';
$interval_days = 1; // avoid divide by zero error

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

$query = mysqli_query($db, "
   SELECT 
    uta.user_id,
    um.full_name AS user_name,

    SUM(uta.total_fp) AS total_fp,
    SUM(uta.total_rev) AS total_rev,
    SUM(uta.total_corr) AS total_corr,
    SUM(uta.total_finals) AS total_finals,
    SUM(uta.total_issue) AS total_issue,
    SUM(uta.total_worked_pages) AS total_worked_pages,

    -- Average percentage (workflow + non-workflow divided by working days)
    ROUND((
        COALESCE(SUM(uta.total_percentage), 0) + COALESCE(nwp.total_nw_percentage, 0)
    ) / NULLIF(COALESCE(wd.working_days, 0), 0), 2) AS avg_percentage,

    -- Non-workflow only percentage
    ROUND(COALESCE(nwp.total_nw_percentage, 0) / NULLIF(COALESCE(wd.working_days, 0), 0), 2) AS non_workflow,

    -- Workflow-only total percentage
    COALESCE(SUM(uta.total_percentage), 0) AS total_workflow_percentage,

    -- Total non-workflow production time
    SEC_TO_TIME(COALESCE(nwp.total_nw_pt_seconds, 0)) AS total_nw_pt_time,

    -- File complexity count
    COALESCE(fcu.file_complexity_count, 0) AS file_complexity_count

FROM user_target_analytics uta
JOIN users um ON um.id = uta.user_id

-- ✅ File complexity join
LEFT JOIN (
    SELECT user_id, COUNT(*) AS file_complexity_count
    FROM wf_file_complexity_users
    WHERE fcu_date >= '$start_date'
      AND fcu_date <= '$end_date'
      AND approval_status = 'Approved'
      AND file_status = 'complex'
    GROUP BY user_id
) fcu ON fcu.user_id = uta.user_id

-- ✅ Non-workflow join
LEFT JOIN (
    SELECT 
        user_id, 
        SUM(nw_percentage) AS total_nw_percentage, 
        SUM(TIME_TO_SEC(nw_pt)) AS total_nw_pt_seconds
    FROM non_workflow_user_percentage
    WHERE nw_date >= '$start_date' AND nw_date <= '$end_date'
    GROUP BY user_id
) nwp ON nwp.user_id = uta.user_id

-- ✅ Working days join
LEFT JOIN (
    SELECT 
        user_id, 
        COUNT(DISTINCT from_date) AS working_days
    FROM user_target_analytics
    WHERE from_date >= '$start_date' AND to_date <= '$end_date'
      AND total_percentage >= 0
    GROUP BY user_id
) wd ON wd.user_id = uta.user_id

WHERE uta.dept_id = '" . $_REQUEST['dep_id'] . "'
  AND uta.user_id = '" . $_COOKIE['user_id'] . "'
  AND uta.from_date >= '$start_date'
  AND uta.to_date <= '$end_date'

GROUP BY uta.user_id
ORDER BY avg_percentage DESC
");


while ($row = mysqli_fetch_assoc($query)) {
    $non_workflow = $row['non_workflow']; // ✅ Use real value from query
    $complexity = $row['file_complexity_count'];
?>
    <tr>
        <td><?php echo $sno++; ?></td>
        <td><?php echo $row['user_name']; ?></td>
        <td><?php echo $row['total_fp']; ?></td>
        <td><?php echo $row['total_rev']; ?></td>
        <td><?php echo $row['total_corr']; ?></td>
        <td><?php echo $row['total_finals']; ?></td>
        <td><?php echo $row['total_issue']; ?></td>
        <td><?php echo $non_workflow; ?></td>
		<td style="background-color: <?php echo ($complexity > 0) ? ' #f8d7da; color: #721c24;' : 'inherit'; ?>;"><?php echo $complexity; ?></td>
        <td><?php echo $row['total_worked_pages']; ?></td>
        <td><?php echo $row['avg_percentage']; ?>%</td>
    </tr>
<?php
}
?>

          </tbody>
        </table>
    </div>
</div>
  

    <div id="article_list"></div>
    <div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
</div>

<?php }?>
 
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

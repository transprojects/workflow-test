<?php include "includes/includes.php";
include "includes/modern-ui.php";
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
    <?php mu_head(); ?>
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.pct{display:inline-block;min-width:62px;padding:4px 10px;border-radius:20px;text-align:center;font-weight:700}
.pct.low{background:#fde8e8;color:#c0392b}.pct.mid{background:#fdf1d9;color:#b87800}.pct.high{background:#e3f6ec;color:#1e9e61}
.flag{display:inline-block;min-width:30px;padding:3px 9px;border-radius:20px;text-align:center;font-weight:700;background:#fde8e8;color:#c0392b}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Department Wise Target Report</h1>
            <p>Achieved target % per employee, from the daily target analytics</p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:220px;"><label for="dep_id">Department</label>
                <select name="dep_id" id="dep_id" required>
<?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);
?>
                  <option value="">-Select-</option>
<?php while($row = $customers->fetch_assoc()){ ?>
                  <option value="<?php echo $row['dept_code']; ?>" <?php if(($_REQUEST['dep_id'] ?? '') ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
<?php } ?>
                </select></div>
              <div id="extra-dropdown1" class="mu-field" style="display:none;min-width:180px;">
                <label for="department_platform">Platform <span style="color:#d64545">*</span></label>
                <select name="department_platform" id="department_platform">
                  <option value="">Select Platform</option>
                  <option value="3B2" <?php if(($_REQUEST['department_platform'] ?? '') =="3B2"){?> selected <?php } ?>>3B2</option>
                  <option value="INDESIGN" <?php if(($_REQUEST['department_platform'] ?? '') =="INDESIGN"){?> selected <?php } ?>>INDESIGN</option>
                  <option value="LATEX" <?php if(($_REQUEST['department_platform'] ?? '') =="LATEX"){?> selected <?php } ?>>LATEX</option>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo ($_REQUEST['from_dt'] ?? '') != '' ? $_REQUEST['from_dt'] : date('d-m-Y', strtotime('-1 days')); ?>" data-to="<?php echo ($_REQUEST['to_dt'] ?? '') != '' ? $_REQUEST['to_dt'] : date('d-m-Y', strtotime('-1 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="getaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="employeetargetreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


        <!-- Table section -->
		<?php 
				//echo $var;
if($_POST['from_dt']!="" || $_POST['mfrom_dt']!="" || $_POST['user_id']!="")
{
?>		
	   <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Employees <span class="mu-right">Achieved target by department</span></h3>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table nowrap" id="monthlytable" style="width:100%">
<thead>
<tr>
                <th class="center">S.no</th>
                <th>Employee Name</th>
                <?php if (rs_target_enabled('FP')) { ?><th>#FP</th><?php } ?>
                <?php if (rs_target_enabled('REV')) { ?><th>#REV</th><?php } ?>
                <th>#CORRECTIONS</th>
                <?php if (rs_target_enabled('FIN')) { ?><th>#FINALS</th><?php } ?>
<?php if (rs_target_enabled('PMC')) { ?><th>#PMC</th><?php } ?>
                <?php if (rs_target_enabled('ISS')) { ?><th>#ISSUE</th><?php } ?>
<?php if (rs_target_enabled('JSTAGE')) { ?><th>#JSTAGE</th><?php } ?>
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

$query = mysqli_query($db, "SELECT 
    d.user_id,
    um.full_name AS user_name,

    -- ✅ Stage-wise counts (NOW CORRECT)
    SUM(CASE WHEN d.stage = 'FP' THEN d.completed ELSE 0 END) AS total_fp,
    SUM(CASE WHEN d.stage = 'REV' THEN d.completed ELSE 0 END) AS total_rev,
    SUM(CASE WHEN d.stage = 'CORR' THEN d.completed ELSE 0 END) AS total_corr,
    SUM(CASE WHEN d.stage = 'FIN' THEN d.completed ELSE 0 END) AS total_finals,
    SUM(CASE WHEN d.stage = 'ISS' THEN d.completed ELSE 0 END) AS total_issue,
    SUM(CASE WHEN d.stage = 'JSTAGE' THEN d.completed ELSE 0 END) AS total_jstage,
    SUM(CASE WHEN d.stage = 'PMC' THEN d.completed ELSE 0 END) AS total_pmc,

    -- ✅ Totals
    SUM(d.completed) AS total_completed,
    SUM(d.target) AS total_target,

    -- ✅ Workflow %
    ROUND(
        COALESCE(uta_sum.total_percentage, 0) 
        / NULLIF(COALESCE(wd.working_days, 0), 0),
        2
    ) AS total_workflow_percentage,

    -- ✅ Final %
    ROUND(
        (
            COALESCE(uta_sum.total_percentage, 0) 
            + COALESCE(nwp.total_nw_percentage, 0)
        )
        / NULLIF(COALESCE(wd.working_days, 0), 0),
        2
    ) AS avg_percentage,

    -- ✅ Non-workflow %
    ROUND(
        COALESCE(nwp.total_nw_percentage, 0) 
        / NULLIF(COALESCE(wd.working_days, 0), 0),
        2
    ) AS non_workflow,

    -- ✅ Non-workflow time
    SEC_TO_TIME(COALESCE(nwp.total_nw_pt_seconds, 0)) AS total_nw_pt_time,

    -- ✅ File complexity
    COALESCE(fcu.file_complexity_count, 0) AS file_complexity_count

FROM user_target_analytics_details d

-- ✅ IMPORTANT JOIN BACK (FIXES HUGE COUNTS)
INNER JOIN user_target_analytics uta 
    ON uta.id = d.master_id
    AND uta.dept_id = '" . $_REQUEST['dep_id'] . "'
    AND uta.from_date >= '$start_date'
    AND uta.to_date <= '$end_date'

INNER JOIN users um 
    ON um.id = d.user_id

-- ✅ Percentage (no duplication)
LEFT JOIN (
    SELECT user_id, SUM(total_percentage) AS total_percentage
    FROM user_target_analytics
    WHERE from_date >= '$start_date'
      AND to_date <= '$end_date'
      AND dept_id = '" . $_REQUEST['dep_id'] . "'
    GROUP BY user_id
) uta_sum ON uta_sum.user_id = d.user_id

-- ✅ File complexity
LEFT JOIN (
    SELECT user_id, COUNT(*) AS file_complexity_count
    FROM wf_file_complexity_users
    WHERE fcu_date >= '$start_date'
      AND fcu_date <= '$end_date'
      AND approval_status = 'Approved'
      AND file_status = 'complex'
    GROUP BY user_id
) fcu ON fcu.user_id = d.user_id

-- ✅ Non-workflow
LEFT JOIN (
    SELECT 
        user_id, 
        SUM(nw_percentage) AS total_nw_percentage, 
        SUM(TIME_TO_SEC(nw_pt)) AS total_nw_pt_seconds
    FROM non_workflow_user_percentage
    WHERE nw_date >= '$start_date' 
      AND nw_date <= '$end_date'
    GROUP BY user_id
) nwp ON nwp.user_id = d.user_id

-- ✅ Working days
LEFT JOIN (
    SELECT 
        user_id, 
        COUNT(DISTINCT from_date) AS working_days
    FROM user_target_analytics
    WHERE from_date >= '$start_date' 
      AND to_date <= '$end_date'
      AND dept_id = '" . $_REQUEST['dep_id'] . "'
    GROUP BY user_id
) wd ON wd.user_id = d.user_id

WHERE um.approved != 0

GROUP BY d.user_id
ORDER BY avg_percentage DESC
");

while ($row = mysqli_fetch_assoc($query)) {
    $non_workflow = $row['non_workflow']; // ✅ Use real value from query
    $complexity = $row['file_complexity_count'];
?>
    <tr>
        <td><?php echo $sno++; ?></td>
        <td><b><?php echo $row['user_name']; ?></b></td>
        <?php if (rs_target_enabled('FP')) { ?><td><?php echo $row['total_fp']; ?></td><?php } ?>
        <?php if (rs_target_enabled('REV')) { ?><td><?php echo $row['total_rev']; ?></td><?php } ?>
        <td><?php echo $row['total_corr']; ?></td>
        <?php if (rs_target_enabled('FIN')) { ?><td><?php echo $row['total_finals']; ?></td><?php } ?>
<?php if (rs_target_enabled('PMC')) { ?><td><?php echo $row['total_pmc']; ?></td><?php } ?>
        <?php if (rs_target_enabled('ISS')) { ?><td><?php echo $row['total_issue']; ?></td><?php } ?>
        <?php if (rs_target_enabled('JSTAGE')) { ?><td><?php echo $row['total_jstage']; ?></td><?php } ?>
        <td><?php echo $non_workflow; ?>%</td> <!-- ✅ Output real non-workflow % -->
        <td><?php echo $complexity > 0 ? '<span class="flag">'.(int)$complexity.'</span>' : '<span class="mu-zero">0</span>'; ?></td>
        <td><?php echo $row['total_worked_pages']; ?></td>
        <?php $pc = (float)$row['avg_percentage']; $pcls = $pc < 74 ? 'low' : ($pc < 94 ? 'mid' : 'high'); ?>
        <td data-order="<?php echo $pc; ?>"><span class="pct <?php echo $pcls; ?>"><?php echo $row['avg_percentage']; ?>%</span></td>
    </tr>
<?php
}
?>

          </tbody>
</table>
</div>
</section>
<div id="article_list"></div>
<div id="loader"><img src="loader.gif" alt="Loading..."></div>
<?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-bullseye"></i>Choose the period and department, then press <b>Get Result</b>.</div>
<?php } ?>
      </div>
   
        <!-- Table section starts-->

        
     
      <!-- Table section ends -->
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>
    </div>



 
    



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
<script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
  </body>
</html>

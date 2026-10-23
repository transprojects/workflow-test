<?php include "includes/includes.php";
include "includes/modern-ui.php";
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
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-table th{text-align:center}.mu-table td{text-align:center}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Article Correction Report</h1>
            <p>Corrections assigned between PAG and PRQC &middot; live and archive</p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:200px;"><label for="cust_id">Customer</label>
                <select class="customer_drop" name="cust_id" id="cust_id" >
                  <option value="all" <?php if (($_REQUEST['cust_id'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php
if($_COOKIE['user_level']=='1') { $sql_customers = $auth->showCustomerlist(); }
else { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if (($_REQUEST['cust_id'] ?? '') == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:200px;"><label for="j_id">Journal</label>
                <select name="j_id" id="j_id" >
                  <option value="all" <?php if (($_REQUEST['j_id'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php
$sql_journals = $auth->showJournallist('0',2);
foreach($sql_journals as $res_journals) { ?>
                  <option <?php if (($_REQUEST['j_id'] ?? '') == $res_journals['j_id']) { ?> selected <?php } ?> value="<?php echo $res_journals['j_id'] ?>"><?php echo $res_journals['j_code'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:180px;"><label for="stage">Stage</label>
                <select class="customer_drop" name="stage" id="stage" >
                  <option value="all" <?php if (($_REQUEST['stage'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php foreach($report_stage_types as $id=>$val){ ?>
                  <option <?php if (($_REQUEST['stage'] ?? '') == $id) { ?> selected <?php } ?> value="<?php echo $id ?>"><?php echo $val ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo ($_REQUEST['from_dt'] ?? '') != '' ? $_REQUEST['from_dt'] : date('d-m-Y', strtotime('-5 days')); ?>" data-to="<?php echo ($_REQUEST['to_dt'] ?? '') != '' ? $_REQUEST['to_dt'] : date('d-m-Y', strtotime('+2 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="articlecorrectionsreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


      <!-- Table section -->
<?php if (isset($_REQUEST['filter'])) { ?>

<section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Assignments <span class="mu-right"><?php echo (int)($total_records ?? 0); ?> records</span></h3>

<?php
// ------------------------------------
// Pagination Setup
// ------------------------------------
$limit = 50;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

// ------------------------------------
// BASE FILTER (STRICT & CORRECT)
// ------------------------------------
$where_base = "
WHERE t.current_status = 'Assign'
  AND t.dept IN (10,14)
  AND t.assign_department IN (10,14)
";

// ------------------------------------
// OPTIONAL FILTERS
// ------------------------------------
$where_extra = "";

// Stage
if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] !== 'all') {
    $stage = $db->real_escape_string($_REQUEST['stage']);
    $where_extra .= " AND t.current_stage LIKE '%$stage%' ";
}

// Customer
if (!empty($_REQUEST['cust_id']) && $_REQUEST['cust_id'] !== 'all') {
    $where_extra .= " AND d.cust_id = '".intval($_REQUEST['cust_id'])."' ";
}

// Journal
if (!empty($_REQUEST['j_id']) && $_REQUEST['j_id'] !== 'all') {
    $where_extra .= " AND d.j_id = '".intval($_REQUEST['j_id'])."' ";
}

// ------------------------------------
// DATE FILTER (FIXED – WORKS FOR DAY & MONTH)
// ------------------------------------
$where_date = "";
if ($_REQUEST['date_wise'] === 'd' && !empty($_REQUEST['from_dt'])) {
    $from = date('Y-m-d', strtotime($_REQUEST['from_dt']));
    $to   = date('Y-m-d', strtotime($_REQUEST['to_dt']));
    $where_date .= " AND DATE(t.start_time) BETWEEN '$from' AND '$to' ";
}

elseif ($_REQUEST['date_wise'] === 'm' && !empty($_REQUEST['mfrom_dt'])) {

    // Expected format: mm-YYYY (example: 01-2026)
    list($month, $year) = explode('-', $_REQUEST['mfrom_dt']);

    $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
    $year  = (int)$year;

    $where_date .= "
        AND MONTH(t.start_time) = '$month'
        AND YEAR(t.start_time)  = '$year'
    ";
}


// ------------------------------------
// COUNT QUERY (MATCHES MAIN QUERY 100%)
// ------------------------------------
$count_sql = "
SELECT COUNT(*) AS total FROM (
    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        'Live' AS source
    FROM workflow.inw_transactions t
    JOIN workflow.inw_inward_dtl d ON d.id = t.project_id
    $where_base
    $where_extra
    $where_date

    UNION ALL

    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        'Archive' AS source
    FROM workflow_archive.inw_transactions t
    JOIN workflow_archive.inw_inward_dtl d ON d.id = t.project_id
    $where_base
    $where_extra
    $where_date
) cnt
";

$totalRes = $db->query($count_sql);
$total_records = (int)($totalRes->fetch_assoc()['total'] ?? 0);

// ------------------------------------
// MAIN DATA QUERY (WITH LIMIT)
// ------------------------------------
 $assign_sql = "
SELECT
    x.project_id AS article_id,
    x.current_stage,
    x.dept AS from_dept,
    x.assign_department AS to_dept,
    x.process_user,
    u.full_name AS process_user_name,
    x.pub_id,
    SUM(CASE WHEN x.dept = 10 THEN 1 ELSE 0 END) AS pag_count,
    SUM(CASE WHEN x.dept = 14 THEN 1 ELSE 0 END) AS prqc_count,
    x.source
FROM (
    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        d.pub_id,
        'Live' AS source
    FROM workflow.inw_transactions t
    JOIN workflow.inw_inward_dtl d ON d.id = t.project_id
    $where_base
    $where_extra
    $where_date

    UNION ALL

    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        d.pub_id,
        'Archive' AS source
    FROM workflow_archive.inw_transactions t
    JOIN workflow_archive.inw_inward_dtl d ON d.id = t.project_id
    $where_base
    $where_extra
    $where_date
) x
LEFT JOIN users u ON u.id = x.process_user
GROUP BY
    x.project_id,
    x.current_stage,
    x.assign_department,
    x.dept,
    x.process_user,
    u.full_name,
    x.source
ORDER BY
    x.project_id,
    x.current_stage
LIMIT $start, $limit
";

$result = $db->query($assign_sql) or die($db->error);

// ------------------------------------
// Department Labels
// ------------------------------------
$deptMap = [
    10 => 'PAG',
    14 => 'PRQC'
];
?>

<?php $deptCls = array(); ?>
<div style="margin-bottom:14px;">
  <a href="download-excel-assignment-report.php?<?= http_build_query($_REQUEST) ?>" class="mu-btn"><i class="fa fa-file-excel-o"></i> Download Excel</a>
</div>

<?php if (!$result || $result->num_rows == 0) { ?>
<div class="mu-empty"><i class="fa fa-inbox"></i>No corrections found for these filters.</div>
<?php } else { ?>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table" style="width:100%">
<thead>
<tr>
    <th>S.No</th><th>Article ID</th><th>Stage</th><th>Assigned From</th><th>Assigned To</th>
    <th>PAG Count</th><th>PRQC Count</th><th>Assigned By</th><th>Source</th>
</tr>
</thead>
<tbody>
<?php
$sno = $start + 1;
while ($row = $result->fetch_assoc()) {
?>
<tr>
    <td><?= $sno++ ?></td>
    <td><b><?= htmlspecialchars($row['pub_id']) ?></b></td>
    <td><?= htmlspecialchars($row['current_stage']) ?></td>
    <td><?= $deptMap[$row['from_dept']] ?? 'Unknown' ?></td>
    <td><?= $deptMap[$row['to_dept']] ?? 'Unknown' ?></td>
    <td><?= $row['pag_count'] ? $row['pag_count'] : '<span class="mu-zero">0</span>' ?></td>
    <td><?= $row['prqc_count'] ? $row['prqc_count'] : '<span class="mu-zero">0</span>' ?></td>
    <td><?= htmlspecialchars($row['process_user_name'] ?: $row['process_user']) ?></td>
    <td><span class="mu-pill <?= $row['source']=='Live' ? 'ok' : 'bad' ?>"><?= $row['source'] ?></span></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
<?php } ?>

<!-- PAGINATION -->
<?php
echo paginates($total_records, $limit, $page, "?".http_build_query($_REQUEST));
?>

</section>

<?php } ?>


	 

        <!-- Table section starts-->
	<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
        
      </div>
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
        dom: 'Bfrtip',
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
        responsive: true,
        
        // ✅ Proper way to define date sorting columns
        columnDefs: [
            {
                targets: [4, 5, 6], // Adjust these indices if needed
                type: 'date'
            }
        ]
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
<script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
  </body>
</html>

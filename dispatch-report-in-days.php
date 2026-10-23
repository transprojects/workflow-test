<?php include "includes/includes.php";
include ('includes/paginate.php'); 
include "includes/modern-ui.php";


// Fetch filters
$filterCustomer = $_REQUEST['customer'] ?? '';
$filterJournal  = $_REQUEST['journal'] ?? '';
$filterStage    = $_REQUEST['stage'] ?? '';
$filterFromDate = $_REQUEST['from_date'] ?? '';
$filterToDate   = $_REQUEST['to_date'] ?? '';
$filterMonth    = $_REQUEST['mfrom_dt'] ?? '';

// Build WHERE conditions
$where = [];

if ($filterCustomer && $filterCustomer != 'all') $where[] = "c.id = '".intval($filterCustomer)."'";
if ($filterJournal && $filterJournal != 'all')  $where[] = "j.j_id = '".intval($filterJournal)."'";

if ($filterStage) {
    if ($filterStage == 'FP') $where[] = "d.stage = 'FP'";
    else $where[] = "r.type = '".$db->real_escape_string($filterStage)."'";
}

// Convert day-wise dates to YYYY-MM-DD
if ($filterFromDate && $filterToDate) {
    $fromParts = explode('-', $filterFromDate); // dd-mm-yyyy
    $toParts   = explode('-', $filterToDate);

    $from = "{$fromParts[2]}-{$fromParts[1]}-{$fromParts[0]}";
    $to   = "{$toParts[2]}-{$toParts[1]}-{$toParts[0]}";

    $where[] = "(
        (d.stage = 'FP' AND DATE(i.recv_dt) BETWEEN '{$from}' AND '{$to}')
        OR 
        (d.stage != 'FP' AND DATE(r.received_date) BETWEEN '{$from}' AND '{$to}')
    )";
}
$dateWise = $_REQUEST['date_wise'] ?? '';
 if ($dateWise === "m" && !empty($_REQUEST['mfrom_dt'])) {
// Month-wise filter
if ($filterMonth) {
    $monthParts = explode('-', $filterMonth); // mm-yyyy
    $month = intval($monthParts[0]);
    $year  = intval($monthParts[1]);
    $where[] = "MONTH(CASE WHEN d.stage='FP' THEN i.recv_dt ELSE r.received_date END) = {$month} AND YEAR(CASE WHEN d.stage='FP' THEN i.recv_dt ELSE r.received_date END) = {$year}";
}
				}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch data
 $sql = "SELECT 
    c.cust_name,
    j.j_code,
    CASE 
        WHEN d.stage = 'FP' THEN 'First Proof'
        WHEN r.type = 'REV' THEN CONCAT('REV', r.revision_count)
        WHEN r.type = 'FIN' THEN CONCAT('FIN', r.revision_count)
        WHEN r.type = 'PMC' THEN CONCAT('PMC', r.revision_count)
        WHEN r.type = 'DRAFT' THEN CONCAT('DRAFT', r.revision_count)
        ELSE d.stage
    END AS stage_name,
    CASE
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) BETWEEN 0 AND 1 THEN 'Day 1'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 2 THEN 'Day 2'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 3 THEN 'Day 3'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 4 THEN 'Day 4'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 5 THEN 'Day 5'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 6 THEN 'Day 6'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 7 THEN 'Day 7'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 8 THEN 'Day 8'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 9 THEN 'Day 9'
        WHEN DATEDIFF(d.sent_date, CASE WHEN d.stage = 'FP' THEN i.recv_dt ELSE r.received_date END) = 10 THEN 'Day 10'
        ELSE '>10 Days'
    END AS dispatch_days_bucket,
    COUNT(*) AS dispatch_count
FROM inw_dispatch_history d
JOIN inw_inward_dtl i ON d.a_id = i.id
LEFT JOIN inw_revisions_dtl r ON d.a_id = r.inw_id AND d.stage != 'FP'
JOIN adm_customer_master c ON i.cust_id = c.id
JOIN adm_journals j ON i.j_id = j.j_id
$whereSql
GROUP BY c.cust_name, j.j_code, stage_name, dispatch_days_bucket
ORDER BY c.cust_name, j.j_code, stage_name";

$result = $db->query($sql);
if (!$result) die("SQL Error: " . $db->error);

// Group data
$data = [];
while ($row = $result->fetch_assoc()) {
    $stage    = $row['stage_name'];
    $customer = $row['cust_name'];
    $journal  = $row['j_code'];
    $bucket   = $row['dispatch_days_bucket'];
    $count    = $row['dispatch_count'];
    $data[$stage][$customer][$journal][$bucket] = $count;
}

$dayBuckets = ['Day 1','Day 2','Day 3','Day 4','Day 5','Day 6','Day 7','Day 8','Day 9','Day 10','>10 Days'];

// Render function
function renderStageTable($stageGroup, $data, $dayBuckets) {
    $tableId = strtolower($stageGroup) . "_table";
    $hasData = false;

    // Check if this stageGroup has any data
    foreach ($data as $stage => $customers) {
        if ($stageGroup == 'FP' && $stage == 'First Proof') $hasData = true;
        if ($stageGroup == 'REV' && strpos($stage, 'REV') === 0) $hasData = true;
        if ($stageGroup == 'FIN' && strpos($stage, 'FIN') === 0) $hasData = true;
        if ($stageGroup == 'PMC' && strpos($stage, 'PMC') === 0) $hasData = true;
        if ($stageGroup == 'DRAFT' && strpos($stage, 'DRAFT') === 0) $hasData = true;
    }

    if (!$hasData) return;

    echo "<section class='mu-panel'><h3><i class='fa fa-table'></i> {$stageGroup} <span class='mu-right'>Dispatch turnaround (days)</span></h3>";
    echo "<div class='mu-table-wrap' style='border:0'><table id='{$tableId}' class='mu-table nowrap' style='width:100%'>";
    echo "<thead><tr><th>Customer</th><th>Journal</th><th>Stage</th>";
    foreach ($dayBuckets as $bucket) echo "<th class='c'>{$bucket}</th>";
    echo "<th class='c'>Total</th></tr></thead><tbody>";

    // Data rows
    foreach ($data as $stage => $customers) {
        if ($stageGroup == 'FP' && $stage != 'First Proof') continue;
        if ($stageGroup == 'REV' && strpos($stage, 'REV') !== 0) continue;
        if ($stageGroup == 'FIN' && strpos($stage, 'FIN') !== 0) continue;
        if ($stageGroup == 'PMC' && strpos($stage, 'PMC') !== 0) continue;
        if ($stageGroup == 'DRAFT' && strpos($stage, 'DRAFT') !== 0) continue;

        foreach ($customers as $cust => $journals) {
            foreach ($journals as $journal => $buckets) {
                $rowTotal = 0;
                echo "<tr><td><b>{$cust}</b></td><td>{$journal}</td><td>{$stage}</td>";
                foreach ($dayBuckets as $bucket) {
                    $val = $buckets[$bucket] ?? 0;
                    echo "<td class='c'>" . ($val ? $val : "<span class='mu-zero'>0</span>") . "</td>";
                    $rowTotal += $val;
                }
                echo "<td class='c'><b>{$rowTotal}</b></td>";
                echo "</tr>";
            }
        }
    }

    // Totals row (filtered by stageGroup)
    $totals = array_fill_keys($dayBuckets, 0);
    $grandTotal = 0;

    foreach ($data as $stage => $customers) {
        if ($stageGroup == 'FP' && $stage != 'First Proof') continue;
        if ($stageGroup == 'REV' && strpos($stage, 'REV') !== 0) continue;
        if ($stageGroup == 'FIN' && strpos($stage, 'FIN') !== 0) continue;
        if ($stageGroup == 'PMC' && strpos($stage, 'PMC') !== 0) continue;
        if ($stageGroup == 'DRAFT' && strpos($stage, 'DRAFT') !== 0) continue;

        foreach ($customers as $cust => $journals) {
            foreach ($journals as $journal => $buckets) {
                foreach ($dayBuckets as $bucket) {
                    $totals[$bucket] += $buckets[$bucket] ?? 0;
                    $grandTotal += $buckets[$bucket] ?? 0;
                }
            }
        }
    }

    echo "</tbody><tfoot><tr><td colspan='3'>Total</td>";
    foreach ($dayBuckets as $bucket) echo "<td class='c'>{$totals[$bucket]}</td>";
    echo "<td class='c'>{$grandTotal}</td></tr></tfoot></table></div></section>";
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
    <style>.mu-table td,.mu-table th{white-space:nowrap;}</style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Delivery Daywise Report</h1>
            <p>Articles dispatched within 1 to 10+ days of receipt, by customer, journal and stage</p></div>
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
                <select class="customer_drop" name="customer" id="cust_id">
                  <option value="" <?php if ($filterCustomer == 'all') { ?> selected <?php } ?>>Select All</option>
<?php
if($_COOKIE['user_level']=='1') { $sql_customers = $auth->showCustomerlist(); }
else { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if ($filterCustomer == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:200px;"><label for="j_id">Journal</label>
                <select name="journal" id="j_id">
                  <option value="" <?php if ($filterJournal == '') { ?> selected <?php } ?>>Select All</option>
<?php
$sql_journals = $auth->showJournallist('0',2);
foreach($sql_journals as $res_journals) { ?>
                  <option <?php if ($filterJournal == $res_journals['j_id']) { ?> selected <?php } ?> value="<?php echo $res_journals['j_id'] ?>"><?php echo $res_journals['j_code'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:180px;"><label for="stage">Stage</label>
                <select class="customer_drop" name="stage" id="stage">
                  <option value="" <?php if ($filterStage == '') { ?> selected <?php } ?>>Select All</option>
<?php foreach($report_stage_types as $id=>$val){ ?>
                  <option <?php if ($filterStage == $id) { ?> selected <?php } ?> value="<?php echo $id ?>"><?php echo $val ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="from_date" data-to-name="to_date" data-from="<?= htmlspecialchars($filterFromDate) ?>" data-to="<?= htmlspecialchars($filterToDate) ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?= htmlspecialchars($filterMonth) ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="delivery-report-daywise" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


		

        <!-- Table section -->
		<?php
		if(isset($_REQUEST['filter']))
		{
		?>
	             <?php
renderStageTable('FP', $data, $dayBuckets);
renderStageTable('REV', $data, $dayBuckets);
renderStageTable('FIN', $data, $dayBuckets);
renderStageTable('PMC', $data, $dayBuckets);
renderStageTable('DRAFT', $data, $dayBuckets);
if (!$data) { ?><div class="mu-empty mu-reveal"><i class="fa fa-inbox"></i>No dispatches match these filters.</div><?php }
?>
			<?php 
		}
		?>
	
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
<?php
$tables = ['FP_table','REV_table','FIN_table','PMC_table','DRAFT_table'];
?>
<?php foreach ($tables as $tid) { ?>
//if ($('#<?= $tid ?>').length && ! $.fn.DataTable.isDataTable('#<?= $tid ?>')) {
    $('#<?= $tid ?>').DataTable({
        dom: 'Bfrtip',
        buttons: ['excelHtml5','pdfHtml5','csvHtml5','print'],
        pageLength: 15,
        responsive: true
    });
//}
<?php } ?>
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

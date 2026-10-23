<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php');
?>
<?php
/*
|--------------------------------------------------------------------------
| Freelancer / Vendor Production Report
|--------------------------------------------------------------------------
| The report is generated STRICTLY on the freelancer completed date
| (vendor_completed_date), so an article is counted only once — in the month
| it was completed — regardless of when it was received.
*/

// Default reporting window (used on first load / when nothing is submitted).
$start_date = date('Y-m-d', strtotime('-5 days'));
$end_date   = date('Y-m-d', strtotime('+2 days'));

// Selected ids (kept so the multi-selects stay populated after submit).
$ex_cid = [];
$ex_vid = [];

$search = "";

// --------------------------------------------------------------------------
// Customer filter — ignore empty and the "Select All" (value = 'all') option.
// --------------------------------------------------------------------------
if (isset($_REQUEST['cust_id']) && is_array($_REQUEST['cust_id'])) {
    $ex_cid = array_values(array_filter($_REQUEST['cust_id'], function ($v) {
        return $v !== '' && $v !== null;
    }));

    if (!empty($ex_cid) && !in_array('all', $ex_cid, true)) {
        $cust_list = implode(",", array_map('intval', $ex_cid));
        if ($cust_list !== '') {
            $search .= ' AND wd.cust_id IN (' . $cust_list . ')';
        }
    }
}

// --------------------------------------------------------------------------
// Vendor (freelancer) filter — ignore empty and "Select All".
// --------------------------------------------------------------------------
if (isset($_REQUEST['vendor_id']) && is_array($_REQUEST['vendor_id'])) {
    $ex_vid = array_values(array_filter($_REQUEST['vendor_id'], function ($v) {
        return $v !== '' && $v !== null;
    }));

    if (!empty($ex_vid) && !in_array('all', $ex_vid, true)) {
        $vendor_list = implode(",", array_map('intval', $ex_vid));
        if ($vendor_list !== '') {
            $search .= ' AND wd.vendor_id IN (' . $vendor_list . ')';
        }
    }
}

// --------------------------------------------------------------------------
// Resolve the reporting date range (freelancer COMPLETED date based).
// --------------------------------------------------------------------------
if (isset($_REQUEST['wgetaction'])) {

    $day = date('d');

    if (isset($_REQUEST['date_wise']) && $_REQUEST['date_wise'] === 'm') {

        // Month-wise: mfrom_dt comes in as "MM-YYYY".
        $explode_month = explode("-", $_REQUEST['mfrom_dt'] ?? '');
        $month = isset($explode_month[0]) && $explode_month[0] !== '' ? $explode_month[0] : date('m');
        $year  = isset($explode_month[1]) && $explode_month[1] !== '' ? $explode_month[1] : date('Y');

        $start_date = date("Y-m-01", strtotime("$year-$month-01"));

        // For the current month, stop at today; otherwise the month end.
        if ($month == date('m') && $year == date('Y')) {
            $end_date = date("Y-m-d", strtotime("$year-$month-$day"));
        } else {
            $end_date = date("Y-m-t", strtotime("$year-$month-01"));
        }

    } else {

        // Day-wise (default).
        if (!empty($_REQUEST['wfrom_dt'])) {
            $start_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        }
        if (!empty($_REQUEST['wto_dt'])) {
            $end_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
        }
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
    <?php mu_head(); ?>
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-field .select2-container{min-width:220px;max-width:340px}</style>

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->


     <?php include("includes/top-header.php");?>

      <!-- Header logo section -->

      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Freelancer Report</h1>
            <p>Articles completed by freelancers, counted on the freelancer completed date</p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:220px;"><label for="vendor_id">Vendor</label>
                <select class="vendor_drop" name="vendor_id[]" id="vendor_id" multiple="multiple">
                  <option value="all">Select All</option>
<?php
$sql_vendor = $auth->showVendorlist();
foreach($sql_vendor as $res_vendor) { ?>
                  <option <?php if (in_array($res_vendor['id'], $ex_vid)){?>selected<?php }?> value="<?php echo $res_vendor['id'] ?>"><?php echo $res_vendor['full_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:220px;"><label for="cust_id">Customer</label>
                <select class="customer_drop" name="cust_id[]" id="cust_id" multiple="multiple">
                  <option value="all">Select All</option>
<?php
if($_COOKIE['user_level']=='3') { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
else { $sql_customers = $auth->showCustomerlist(); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="wfrom_dt" data-to-name="wto_dt" data-from="<?php echo ($_REQUEST['wfrom_dt'] ?? '') != '' ? $_REQUEST['wfrom_dt'] : date('d-m-Y', strtotime('-5 days')); ?>" data-to="<?php echo ($_REQUEST['wto_dt'] ?? '') != '' ? $_REQUEST['wto_dt'] : date('d-m-Y', strtotime('+2 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="vendorreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>

<br/>
        <!-- Table section -->
<?php

               $sql_vendor_articles = mysqli_query($db, "
                    SELECT *
                    FROM `inw_inward_dtl` as wd
                    INNER JOIN adm_customer_master ON wd.cust_id = adm_customer_master.id
                    INNER JOIN adm_journals ON wd.j_id = adm_journals.j_id
                    INNER JOIN users ON wd.vendor_id = users.id
                    WHERE wd.vendor_completed_date IS NOT NULL
                      AND wd.vendor_completed_date <> '0000-00-00 00:00:00'
                      AND DATE(wd.vendor_completed_date) >= '" . date('Y-m-d', strtotime($start_date)) . "'
                      AND DATE(wd.vendor_completed_date) <= '" . date('Y-m-d', strtotime($end_date)) . "'
                      $search
                    ORDER BY wd.vendor_completed_date ASC
               ");
			   $num_articles=mysqli_num_rows($sql_vendor_articles);
			   if($num_articles>0)
			   {

?>
         <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Completed articles <span class="mu-right"><?php echo (int)$num_articles; ?> articles</span></h3>
<div class="mu-table-wrap" style="border:0;">
<table id="articles" class="mu-table" style="width:100%">




				<thead>
				<tr>
				<th data-sortable="true" scope="col">S.no</th>
				<th data-sortable="true" scope="col">Assigned date</th>
				<th data-sortable="true" scope="col">File</th>
				<th data-sortable="true" scope="col">Client</th>
				<th data-sortable="true" scope="col">Vendor</th>
				<th data-sortable="true" scope="col">Return date</th>
				<th data-sortable="true" scope="col">Word count</th>
				<th data-sortable="true" scope="col">Proof count</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$k=1;
				while($res_vendor_articles=mysqli_fetch_array($sql_vendor_articles))
				{
				?>
				<tr>
				<td><?php echo $k;?></td>
				<td><?php echo date('d-m-Y H:i:s',strtotime($res_vendor_articles['vendor_assign_date']));?></td>
				<td><b><?php echo $res_vendor_articles['pub_id'];?></b></td>
				<td><?php echo $res_vendor_articles['cust_name'];?></td>
				<td><?php echo $res_vendor_articles['full_name'];?></td>
				<td><?php echo date('d-m-Y H:i:s',strtotime($res_vendor_articles['vendor_completed_date']));?></td>
				<td><?php echo $res_vendor_articles['vendor_wc'];?></td>
				<td><?php echo $res_vendor_articles['page_count'];?></td>
				</tr>
				<?php
				$k++;
				}
				?>
				</tbody>
</table>
</div>
</section>
<?php } elseif (isset($_REQUEST['wgetaction'])) { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-inbox"></i>No freelancer-completed articles in this period.</div>
<?php } ?>
        </div>
      <!-- Footer Section -->



	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>










<!-- Footer Section -->
<?php echo scripts(); ?>


<!-- jQuery -->

<!-- jQuery UI (Datepicker) -->

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
                    url: "artilce_dlist_on_date.php", // Replace with your PHP script URL
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
                    url: "artilce_dlist_on_date_all.php", // Replace with your PHP script URL
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
                    url: "artilce_dlist_on_date_all.php", // Replace with your PHP script URL
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
<script src="assets/js/select2/select2.min.js"></script>


    <script>
      $(".customer_drop").select2({
        placeholder: "Select Customer",
      });
      $(".vendor_drop").select2({
        placeholder: "Select Vendor",
      });


    </script>

	<script>
$(document).ready(function() {
        $('#articles').DataTable({

 dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
       //table.row(':eq(0)', { page: 'current' }).select();
	    pageLength: 10,

		buttons: [
						{extend: 'copy', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'csv',title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'pdf', title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'print', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }}
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
});
</script>
	  <script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
</body>
</html>

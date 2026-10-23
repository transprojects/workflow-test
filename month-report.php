<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php'); 
?>
<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
if(isset($_REQUEST['wgetaction']))
{
	$search = "";
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != 'all') {
		
		$cust_id=implode(",",$_REQUEST['cust_id']);
		$search .= ' and FIND_IN_SET(wd.cust_id,"'.$cust_id.'")';
	}
	else
	{
		$search .= '';
	}

	if ($_REQUEST['vendor_id'] != "" && $_REQUEST['vendor_id'] != 'all') {
		
		$vendor_id=implode(",",$_REQUEST['vendor_id']);
		$search .= ' and FIND_IN_SET(wd.vendor_id,"'.$vendor_id.'")';
	}
	else
	{
		$search .= '';
	}

	

}
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['vendor_id']))
{
	 $vendor_id=implode(",",$_REQUEST['vendor_id']);
 	 $ex_vid = explode(",",$vendor_id);
}
else
{
	$ex_jid='';
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
          <div><small>Reports</small><h1>Customer Monthly Report</h1>
            <p>Articles by customer and stage for the selected period</p></div>
        </div>
<?php
$selectedCustomers = isset($_POST['cust_id']) ? $_POST['cust_id'] : [];
$selectedStages = isset($_POST['stage']) ? $_POST['stage'] : [];
?>
        <form id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field" style="min-width:240px;"><label for="cust_id">Customer</label>
                <select class="customer_drop" name="cust_id[]" id="cust_id" multiple="multiple">
        <option value="all" <?php echo in_array('all', $selectedCustomers) ? 'selected' : ''; ?>>Select All</option>
        <option value="Peerj" <?php echo in_array('Peerj', $selectedCustomers) ? 'selected' : ''; ?>>Peerj</option>
        <option value="TSP" <?php echo in_array('TSP', $selectedCustomers) ? 'selected' : ''; ?>>TSP</option>
        <option value="SIF" <?php echo in_array('SIF', $selectedCustomers) ? 'selected' : ''; ?>>SIF</option>
        <option value="EOS" <?php echo in_array('EOS', $selectedCustomers) ? 'selected' : ''; ?>>EOS</option>
        <option value="ASP" <?php echo in_array('ASP', $selectedCustomers) ? 'selected' : ''; ?>>ASP</option>
        <option value="IAP" <?php echo in_array('IAP', $selectedCustomers) ? 'selected' : ''; ?>>IAP</option>
        <option value="IAP Japanese journal" <?php echo in_array('IAP Japanese journal', $selectedCustomers) ? 'selected' : ''; ?>>IAP Japanese journal</option>
        <option value="NOW-Publisher" <?php echo in_array('NOW-Publisher', $selectedCustomers) ? 'selected' : ''; ?>>NOW-Publisher</option>
        <option value="Gleampub" <?php echo in_array('Gleampub', $selectedCustomers) ? 'selected' : ''; ?>>Gleampub</option>
        <option value="AKA" <?php echo in_array('AKA', $selectedCustomers) ? 'selected' : ''; ?>>AKA</option>
        <option value="Sunrise" <?php echo in_array('Sunrise', $selectedCustomers) ? 'selected' : ''; ?>>Sunrise</option>
        <option value="RSSDI" <?php echo in_array('RSSDI', $selectedCustomers) ? 'selected' : ''; ?>>RSSDI</option>
        <option value="Charlsworth" <?php echo in_array('Charlsworth', $selectedCustomers) ? 'selected' : ''; ?>>Charlsworth</option>
    
                </select></div>
              <div class="mu-field" style="min-width:200px;"><label for="Stage">Stage</label>
                <select class="customer_drop" name="stage[]" id="Stage" multiple="multiple">
        <?php foreach($report_stage_types as $st_id => $st_name) { ?>
        <option value="<?php echo htmlspecialchars($st_id); ?>" <?php echo in_array($st_id, $selectedStages) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st_name); ?></option>
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
                <a class="mu-btn" href="monthlyreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>

<br/>
        <!-- Table section -->
         <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Customer totals</h3>
<div class="mu-table-wrap" style="border:0;">
<table id="articles" class="mu-table" style="width:100%">

		    
														
															
				<thead>
				<tr>
				<th data-sortable="true" scope="col">Customer</th>
				<th data-sortable="true" scope="col">Stage</th>
				<th data-sortable="true" scope="col">Total</th>
				</tr>
				</thead>	
				<tbody>				
				 <?php

    // Query to fetch customer data
	//$customers_array = array('Peerj','TSP','SIF','EOS','ASP','IAP','IAP Japanese journal','NOW-Publisher','Gleampub','AKA','Sunrise','RSSDI','Charlsworth');
	
	//$customers_str = implode(',', $customers_array);
    if(isset($_POST['cust_id'])){
        $ended_date = $_POST['wto_dt'];
        $ended_date = DateTime::createFromFormat('d-m-Y', $ended_date)->format('Y-m-d');
        $started_date = $_POST['wfrom_dt'];
        $started_date = DateTime::createFromFormat('d-m-Y', $started_date)->format('Y-m-d');
        // var_dump($_POST['wto_dt'],$ended_date,$_POST['wfrom_dt'],$started_date);
    if (isset($_POST['stage'])) {
        $stage = is_array($_POST['stage']) ? $_POST['stage'] : explode(',', $_POST['stage']);
    }else{
        $stage=array();
    }
    if(in_array('all', $_POST['cust_id'])){
    $inCondition="'Peerj','TSP','SIF','EOS','ASP','IAP','IAP Japanese journal','NOW-Publisher','Gleampub','AKA','Sunrise','RSSDI','Charlsworth'";
    }else{
    $inCondition = "'" . implode("','",$_POST['cust_id']) . "'";
    }
    $query = "SELECT cust_name, cust_id, id FROM `adm_customer_master` where cust_name IN (".$inCondition.") order by cust_name ASC";
    // var_dump($query);
    $customers = $db->query($query) or die($db->error);
	
    
	

    // Loop through each customer
    while ($crow = $customers->fetch_assoc()) {
        $cust_name = $crow['cust_name'];
        $cust_id = $crow['cust_id'];
        $id = $crow['id'];
        
        $search = "AND cust_id='" . $id . "'";

        // var_dump($stage);
        if($stage && in_array('FP', $stage))
        {
        ?>
                    <tr>
                        <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">FP</th>

                        <?php
                        // Query for the "FP" stage count
                        $fp_query = "
                        SELECT 
                            SUM(it.unique_pages) AS total_pages
                        FROM 
                            inw_dispatch_history AS idh
                        INNER JOIN 
                            (SELECT project_id, current_stage, MAX(pages_completed) AS unique_pages
                             FROM inw_transactions
                             WHERE dept = '14' AND current_status = 'Completed' AND current_stage = 'FP'
                             GROUP BY project_id, current_stage) AS it
                        ON it.project_id = idh.a_id
                            WHERE DATE_FORMAT(idh.sent_date, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($started_date)) . "' 
                            AND DATE_FORMAT(idh.sent_date, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($ended_date)) . "' 
                            AND idh.stage = 'FP'
                            $search;
                        ";
                        
                        $fp_query_rs = mysqli_query($db, $fp_query);
                        $fp_query_total_arr = mysqli_fetch_assoc($fp_query_rs);
                        $fp_query_total = $fp_query_total_arr['total_pages'];
                        
                                                ?>
                                                <td><?php echo $fp_query_total; ?></td>
                    </tr>
                    <?php } ?>
<?php if($stage && in_array('REV', $stage))
{?>
                    <tr>
                    <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">REV</th>

                        <?php
						
                        $rev_query = "
                        WITH latest_idh AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY a_id ORDER BY sent_date DESC) AS rn
                                FROM inw_dispatch_history
                                WHERE stage LIKE 'REV%' 
                            ) AS idh
                            WHERE idh.rn = 1  -- Pick the latest entry per `a_id`
                        ), latest_it AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY id DESC) AS rn
                                FROM inw_transactions
                                WHERE dept = '14' 
                                  AND current_status = 'Completed' 
                                  AND current_stage LIKE 'REV%'
                            ) AS it
                            WHERE it.rn = 1  -- Pick the latest `REV` stage per `project_id`
                        )
                        SELECT 
                            SUM(it.unique_pages) AS grand_total_pages  -- Compute grand total here
                        FROM 
                            latest_idh AS idh
                        INNER JOIN 
                            (SELECT project_id, current_stage, MAX(pages_completed) AS unique_pages
                             FROM latest_it
                             GROUP BY project_id, current_stage) AS it
                        ON it.project_id = idh.a_id
                        WHERE 
                            DATE(idh.sent_date) BETWEEN '" . date('Y-m-d', strtotime($started_date)) . "' 
                            AND '" . date('Y-m-d', strtotime($ended_date)) . "' 
                            $search;
                        ";
                        
                        $fp_query_rs = mysqli_query($db, $rev_query);
                        $rev_query_total_arr = $fp_query_rs->fetch_assoc();
                        $rev_query_total = $rev_query_total_arr['grand_total_pages'];  // Fetching grand total sum
                        
                        ?>
                                                <td><?php echo $rev_query_total; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if($stage && in_array('FIN', $stage))
                    {?>
                    <tr>
                    <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">FIN</th>

                        <?php
                        // Query for the "ISS" stage count
                        $fin_query = "
                        WITH latest_idh AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY a_id ORDER BY sent_date DESC) AS rn
                                FROM inw_dispatch_history
                                WHERE stage LIKE 'FIN%' 
                            ) AS idh
                            WHERE idh.rn = 1  -- Pick the latest entry per `a_id`
                        ), latest_it AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY id DESC) AS rn
                                FROM inw_transactions
                                WHERE dept = '14' 
                                  AND current_status = 'Completed' 
                                  AND current_stage LIKE 'FIN%'
                            ) AS it
                            WHERE it.rn = 1  -- Pick the latest `FIN` stage per `project_id`
                        )
                        SELECT 
                            SUM(it.unique_pages) AS grand_total_pages  -- Compute grand total here
                        FROM 
                            latest_idh AS idh
                        INNER JOIN 
                            (SELECT project_id, current_stage, MAX(pages_completed) AS unique_pages
                             FROM latest_it
                             GROUP BY project_id, current_stage) AS it
                        ON it.project_id = idh.a_id
                        WHERE 
                            DATE(idh.sent_date) BETWEEN '" . date('Y-m-d', strtotime($started_date)) . "' 
                            AND '" . date('Y-m-d', strtotime($ended_date)) . "' 
                            $search;
                        ";
                        
                        $fin_query_rs = mysqli_query($db, $fin_query);
                        $fin_query_total_arr = $fin_query_rs->fetch_assoc();
                        $fin_query_total = $fin_query_total_arr['grand_total_pages'];  // Fetching grand total sum
                        
                        ?>
                                               
                                                <td><?php echo $fin_query_total; ?></td>
                    </tr>

                    <?php } ?>
                    <?php if($stage && in_array('JSTAGE', $stage))
                    {?>
                    <tr>
                    <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">JSTAGE</th>

                        <?php
                        // Query for the "JSTAGE" stage count
                        $jstage_query = "
                        WITH latest_idh AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY a_id ORDER BY sent_date DESC) AS rn
                                FROM inw_dispatch_history
                                WHERE stage LIKE 'JSTAGE%' 
                            ) AS idh
                            WHERE idh.rn = 1  -- Pick the latest entry per `a_id`
                        ), latest_it AS (
                            SELECT *
                            FROM (
                                SELECT *, 
                                       ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY id DESC) AS rn
                                FROM inw_transactions
                                WHERE dept = '14' 
                                  AND current_status = 'Completed' 
                                  AND current_stage LIKE 'JSTAGE%'
                            ) AS it
                            WHERE it.rn = 1  -- Pick the latest `JSTAGE` stage per `project_id`
                        )
                        SELECT 
                            SUM(it.unique_pages) AS grand_total_pages  -- Compute grand total here
                        FROM 
                            latest_idh AS idh
                        INNER JOIN 
                            (SELECT project_id, current_stage, MAX(pages_completed) AS unique_pages
                             FROM latest_it
                             GROUP BY project_id, current_stage) AS it
                        ON it.project_id = idh.a_id
                        WHERE 
                            DATE(idh.sent_date) BETWEEN '" . date('Y-m-d', strtotime($started_date)) . "' 
                            AND '" . date('Y-m-d', strtotime($ended_date)) . "' 
                            $search;
                        ";
                        
                        $jstage_query_rs = mysqli_query($db, $jstage_query);
                        $jstage_query_total_arr = $jstage_query_rs->fetch_assoc();
                        $jstage_query_total = $jstage_query_total_arr['grand_total_pages'];  // Fetching grand total sum
                        
                        ?>
                                               
                                                <td><?php echo $jstage_query_total; ?></td>
                    </tr>

                    <?php } ?>
                    <?php if($stage && in_array('ISSCOR', $stage))
                    {?>
                    <tr>
                    <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">ISS</th>

                        <?php
                        // Query for the "ISS" stage count
                        $issue_query = "
                        WITH latest_idh AS (
                         SELECT *
                         FROM (
                             SELECT *, 
                                    ROW_NUMBER() OVER (PARTITION BY a_id ORDER BY sent_date DESC) AS rn
                             FROM inw_issue_dispatch_history
                             WHERE stage LIKE 'ISSCOR%' 
                         ) AS idh
                         WHERE idh.rn = 1  -- Pick the latest entry per `a_id`
                     ), latest_it AS (
                         SELECT *
                         FROM (
                             SELECT *, 
                                    ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY id DESC) AS rn
                             FROM inw_transactions
                             WHERE dept = '14' 
                               AND current_status = 'Completed' 
                               AND current_stage LIKE 'ISSCOR%'
                         ) AS it
                         WHERE it.rn = 1  -- Pick the latest `ISSCOR` stage per `project_id`
                     )
                     SELECT 
                         SUM(it.unique_pages) AS grand_total_pages  -- Compute grand total here
                     FROM 
                         latest_idh AS idh
                     INNER JOIN 
                         (SELECT project_id, current_stage, MAX(pages_completed) AS unique_pages
                          FROM latest_it
                          GROUP BY project_id, current_stage) AS it
                     ON it.project_id = idh.a_id
                     WHERE 
                         DATE(idh.sent_date) BETWEEN '" . date('Y-m-d', strtotime($started_date)) . "' 
                         AND '" . date('Y-m-d', strtotime($ended_date)) . "' 
                         $search;
                     ";
                                             $issue_query_rs = mysqli_query($db, $issue_query);
                      
                                             $issue_query_total_arr = $issue_query_rs->fetch_assoc();
                                             $issue_query_total = $issue_query_total_arr['grand_total_pages'];
                                             ?>
                     <td><?php echo $issue_query_total; ?></td>
                    </tr>
      <?php } ?>
                    <!-- <tr class="totalrow">
                    <th></th>
                        <th>TOTAL</th>
                        <td>
                            <?php 
                            // echo $fp_query_total + $rev_query_total + $issue_query_total; 
                            ?>
                        </td>
                    </tr> -->
                
            
        

        <?php
    } }
?>
				</tbody>
</table>
</div>
</section>
      </div>
     
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

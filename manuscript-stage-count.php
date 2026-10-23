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


// ------------------------------------
// 1. Setup: databases and filters
// ------------------------------------
$databases = ['workflow'];



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
        <!--  <p class="reportsectionheading ms-4">Department File Flow Report (Live/Archive)</p>-->
          <p class="reportsectionheading ms-4">Manuscript Stage Count Report</p>
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
												   <?php /*?> <option value="all" <?php if ($_REQUEST['cust_id'] == 'all') { ?> selected <?php } ?> >Select All</option><?php */?>

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
               <a href="manuscriptstagecount"> <img src="./assets/images/refresh.svg" /></a>
              </button>
            </div>
          </div>
        </div>
	</form>


<?php
if (isset($_REQUEST['filter'])) {

    $databases   = ['workflow']; 
    $cust_id     = $_REQUEST['cust_id'] ?? '';
    $j_id        = $_REQUEST['j_id'] ?? '';
    $dp_platform = $_REQUEST['dp_platform'] ?? '';
    $dateWise    = $_REQUEST['date_wise'] ?? '';
    $from_dt     = $_REQUEST['from_dt'] ?? '';
    $to_dt       = $_REQUEST['to_dt'] ?? '';
    $mfrom_dt    = $_REQUEST['mfrom_dt'] ?? '';
    $yfrom_dt    = $_REQUEST['yfrom_dt'] ?? '';
    $stage_filter = strtoupper(trim($_REQUEST['stage'] ?? '')); // FP, REV, FIN, PMC, etc.

    // Departments and stage mapping
    $dept_names = [22=>"Pre-Editing/Pre-Clean/Tex-Pre-Clean",1=>"CE",9=>"Art",10=>"PAG",14=>"PRQC",18=>"Proofing",5=>"XML",17=>"Epub"];
    $stage_map  = ['FP'=>[22,1,9,10,14,18],'REV'=>[9,10,14,5,17],'PMC'=>[9,10,14,5,17],'FIN'=>[9,10,14,5,17],'JSTAGE'=>[9,10,14,5,17]];

    // Fixed stage order
    $fixed_stage_order = ['FP','REV','FIN','PMC','JSTAGE'];

    // Determine stages to fetch
    if($stage_filter && in_array($stage_filter, $fixed_stage_order)){
        $stage_order = [$stage_filter]; // Only selected stage
    } else {
        $stage_order = $fixed_stage_order; // All stages
    }

    // Fetch articles for selected stages
    $dispatchData = [];
    $article_ids   = [];

    foreach ($stage_order as $stage) {
        foreach($databases as $dbName){
            $where = "wdd.article_title NOT LIKE '%_TOC' AND wdd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold','With Drawn')";
            if($cust_id && $cust_id!=='all') $where .= " AND wdd.cust_id='".intval($cust_id)."'";
            if($j_id && $j_id!=='all') $where .= " AND wdd.j_id='".intval($j_id)."'";

            if($stage === 'FP') {
                $query = "SELECT wdd.id, wdd.pub_id, wdd.article_title, wdd.recv_dt, wdd.due_dt, wdd.department_id,
                                c.cust_name, j.j_code, 'Live' AS source
                          FROM {$dbName}.inw_inward_dtl wdd
                          JOIN {$dbName}.adm_customer_master c ON wdd.cust_id=c.id
                          JOIN {$dbName}.adm_journals j ON wdd.j_id=j.j_id
                          WHERE wdd.stage='FP' AND $where";
            } else {
                $query = "SELECT ird.inw_id AS id, ird.revision_count, ird.received_date, ird.due_date, wdd.department_id,
                                 wdd.pub_id, wdd.article_title, c.cust_name, j.j_code, 'Live' AS source
                          FROM {$dbName}.inw_revisions_dtl ird
                          JOIN {$dbName}.inw_inward_dtl wdd ON ird.inw_id = wdd.id
                          JOIN {$dbName}.adm_customer_master c ON wdd.cust_id=c.id
                          JOIN {$dbName}.adm_journals j ON wdd.j_id=j.j_id
                          WHERE ird.type='".addslashes($stage)."' AND $where";
            }

            $result = $db->query($query) or die($db->error);
            while($row = $result->fetch_assoc()){
                $dispatchData[$stage][$row['id']] = $row;
                $article_ids[$row['id']] = $row['id'];
            }
        }
    }

    // Build stage-wise department counts
    $stage_dept_counts = [];
    foreach ($stage_order as $stage) {
        foreach ($dispatchData[$stage] ?? [] as $id => $article) {
            $active_depts = $stage_map[$stage] ?? array_keys($dept_names);

            foreach ($active_depts as $dept_id) {
                if (isset($article['department_id']) && $article['department_id'] == $dept_id) {
                    $stage_dept_counts[$stage][$dept_id] = ($stage_dept_counts[$stage][$dept_id] ?? 0) + 1;
                }
            }
        }
    }

    // Render table
    ?>
    <div class="container table-responsive custom-container p-4" style="box-shadow:0 0 16px rgba(164,164,171,.25);border-radius:24px;">
        <table class="table table-bordered table-sticky display nowrap" id="dispatchTable" style="width:100%">
            <thead class="table-dark">
                <tr class="text-center align-middle">
                    <th>Stage</th>
                    <?php 
                    // Determine active columns
                    if($stage_filter && in_array($stage_filter, $fixed_stage_order)){
                        $active_depts = $stage_map[$stage_filter]; // Only show relevant depts
                    } else {
                        $active_depts = array_keys($dept_names); // Show all
                    }
                    foreach($active_depts as $dept_id): 
                    ?>
                        <th><?php echo htmlspecialchars($dept_names[$dept_id]); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($stage_dept_counts)): ?>
                    <tr><td colspan="<?php echo count($active_depts)+1; ?>" class="text-center">No stagewise counts found for selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach($stage_order as $stage): 
                        $row_depts = ($stage_filter && in_array($stage_filter, $fixed_stage_order)) ? $stage_map[$stage] : array_keys($dept_names);
                    ?>
                        <tr class="text-center">
                            <td><?php echo htmlspecialchars($stage); ?></td>
                            <?php foreach($row_depts as $dept_id): ?>
                                <td><?php echo $stage_dept_counts[$stage][$dept_id] ?? 0; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php } ?>










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

   </body>
</html>


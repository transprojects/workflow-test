<?php include "includes/includes.php";
include ('includes/paginate.php'); 
?>
<?php
if(isset($_REQUEST['wgetaction']))
{
$search = "";
if ($_REQUEST['wcust_id'] != "" && $_REQUEST['wcust_id'] != 'all') {
    $search .= "and cust_id='" . $_REQUEST['wcust_id'] . "' ";
}

if ($_REQUEST['wj_id'] != "" && $_REQUEST['wj_id'] != 'all') {
    $search .= "and j_id='" . $_REQUEST['wj_id'] . "' ";
}

}
$fds=date('Y-m-d', strtotime('-5 days'));
$tds=date('Y-m-d');
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
          <p class="reportsectionheading ms-4">Inflow Data Accuracy Report</p>
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
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="wcust_id" id="wcust_id">
												    <option value="all" <?php if ($_REQUEST['wcust_id'] == 'all') { ?> selected <?php } ?> >Select All</option>

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
												
									      	if(isset($_REQUEST['wcust_id']))
													{
										    	?>
										       <option <?php if ($_REQUEST['wcust_id'] == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
                <div class="d-flex" >
                  <input type="text" class="form-control date-picker monthlyinputdate" id="from_dt" name="wfrom_dt" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['wfrom_dt'] != '') {
                                                                                        echo $_REQUEST['wfrom_dt'];
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
                    type="text" id="wto_dt" name="wto_dt" class="form-control monthlyinputdate1" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['wto_dt'] != '') {
                                                                                        echo $_REQUEST['wto_dt'];
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
               <a href="inflowreport"> <img src="./assets/images/refresh.svg" /></a>
              </button>
            </div>
          </div>
        </div>
	</form>


        <!-- Table section -->
		
		<?php

if(isset($_REQUEST['filter']))
{
$day = date('d');        // Get the current day	

        $r = 1;
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $start_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $start_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));

    $end_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {

$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$start_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
    if($month==date('m'))
    {
        $end_date = date("Y-m-d", strtotime("$year-$month-$day"));
    }
    else
    {
        $end_date = date("Y-m-t", strtotime("$year-$month-01"));
    }

//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;

}

else
{
$start_date =$fds;
$end_date =$tds;	
}
		?>
        <div class="container-fluid mt-4 table-responsive">
		
		<table class="table table-bordered monthlytable">
														
													    
															
                                                            <thead>
															
                                                              
																
																 <tr>
                <th rowspan="2" style="vertical-align: middle">Stage</th>
                <th colspan="32" style="color: #f26722; font-weight: 600; text-align: left"                >
                  <?php echo date('M',strtotime($start_date));?>
                  <span style="color: #222; margin-left: 40%"
                    ><?php echo date('d-M-Y',strtotime($start_date));?> TO <?php echo date('d-M-Y',strtotime($end_date));?></span
                  >
                </th>
              </tr>
															
                                                             <tr class="violetclr">

<?php 


$rev_id_count=0;

while (strtotime($start_date) <= strtotime($end_date))
{
?>
<th style="width:45px;color:#F26722;font-size:12px;"> <?php echo date('d',strtotime($start_date));?></th>
<?php 

 $start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));
}
?>
																	 <th style="color:#0000FF;">TOTAL</th>

																	
                                                                </tr>
                                                            </thead>
                                                                 <tbody>
		<tr>
    <th style="color: #4648b8">FP</th>
    <?php
    // ----------------- DATE SETUP ------------------
    if ($_REQUEST['date_wise'] == "d" && !empty($_REQUEST['wfrom_dt'])) {
        $sstart_date = $tsstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date = $tsend_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        $send_date = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
        $tsstart_date = $sstart_date;
        $tsend_date = $send_date;
    } else {
        $sstart_date = $tsstart_date = date('Y-m-d', strtotime($fds));
        $send_date = $tsend_date = date('Y-m-d', strtotime($tds));
    }

    // ----------------- TOTAL QUERY ------------------
    $fp_query_total_sql = "
        SELECT COUNT(*) AS total 
        FROM inw_inward_dtl 
        WHERE DATE_FORMAT(created_dt, '%Y-%m-%d') BETWEEN '$sstart_date' AND '$send_date'
        $search
    ";
    $fp_query_total = (int)($db->query($fp_query_total_sql)->fetch_assoc()['total'] ?? 0);

    // ----------------- DAILY COUNT LOOP ------------------
    $current_date = $sstart_date;

    while (strtotime($current_date) <= strtotime($send_date)) {
        $fp_query = "
            SELECT COUNT(id) AS stage_count 
            FROM inw_inward_dtl 
            WHERE DATE_FORMAT(created_dt, '%Y-%m-%d') = '$current_date'
            $search
        ";
        $fp_row = $db->query($fp_query)->fetch_assoc();
        $count = (int)$fp_row['stage_count'];
        
        $color = ($current_date < date('Y-m-d') && $count > 0) ? 'red' : '#000';
        ?>
        <td>
            <a onclick="loaddata('<?php echo  $current_date ?>','<?php echo  $_REQUEST['wcust_id'] ?>','FP');" style="cursor:pointer;">
                <b style="color:<?php echo  $color ?>;"><?php echo  $count ?></b>
            </a>
        </td>
        <?php
        $current_date = date("Y-m-d", strtotime("+1 day", strtotime($current_date)));
    }
    ?>
    <td>
        <a onclick="loaddata1('<?php echo  $tsstart_date ?>','<?php echo  $tsend_date ?>','<?php echo  $_REQUEST['wcust_id'] ?>','FPALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo  $fp_query_total ?></b>
        </a>
    </td>
</tr>

				 
				<tr>
    <th style="color: #4648b8">REV</th>

    <?php
    // Date range handling
    if ($_REQUEST['date_wise'] == "d") {
        if ($_REQUEST['wfrom_dt'] != "") {
            $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
            $send_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
        }
    } elseif ($_REQUEST['date_wise'] == "m") {
        $explode_month = explode("-", $_REQUEST['mfrom_dt']);
        $year = $explode_month[1];
        $month = $explode_month[0];

        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

        if ($month == date('m')) {
            $send_date = date("Y-m-d"); // today
        } else {
            $send_date = date("Y-m-t", strtotime("$year-$month-01"));
        }
    } else {
        $sstart_date = $fds;
        $send_date = $tds;
    }

    // Final range variables
    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date = date('Y-m-d', strtotime($send_date));

    // Fetch revision counts grouped by received date
    $rev_query = "
        SELECT 
            DATE_FORMAT(r.received_date,'%Y-%m-%d') AS rev_date,
            COUNT(DISTINCT wd.id) AS rev_count
        FROM 
            inw_inward_dtl AS wd
        LEFT JOIN 
            inw_revisions_dtl AS r 
            ON wd.id = r.inw_id 
            AND r.r_id = (
                SELECT r_id FROM inw_revisions_dtl 
                WHERE inw_id = wd.id 
                ORDER BY r_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            wd.j_id = j.j_id 
            AND wd.stage LIKE 'REV%' 
            AND r.received_date BETWEEN '$tsstart_date' AND '$tsend_date'
            AND r.created_on > '2025-01-01'
            $search
        GROUP BY 
            rev_date
        ORDER BY 
            rev_date ASC
    ";

    $rev_data = [];
    $rev_result = $db->query($rev_query);
    while ($row = $rev_result->fetch_assoc()) {
        $rev_data[$row['rev_date']] = $row['rev_count'];
    }

    // Loop through each day in the range
    $current = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($current <= $end) {
        $date_str = date('Y-m-d', $current);
        $count = isset($rev_data[$date_str]) ? $rev_data[$date_str] : 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $date_str; ?>','<?php echo $_REQUEST['wcust_id']; ?>','REV');" style="cursor:pointer;">
                <?php if ($date_str < date('Y-m-d') && $count > 0) { ?>
                    <b style="color:red;"><?php echo $count; ?></b>
                <?php } else { ?>
                    <span style="color:#000;"><?php echo $count; ?></span>
                <?php } ?>
            </a>
        </td>
        <?php
        $current = strtotime('+1 day', $current);
    }

    // Total revision query
    $rev_query_total = $db->query("
        SELECT COUNT(DISTINCT wd.id) AS total_rev
        FROM 
            inw_inward_dtl AS wd
        LEFT JOIN 
            inw_revisions_dtl AS r 
            ON wd.id = r.inw_id 
            AND r.r_id = (
                SELECT r_id FROM inw_revisions_dtl 
                WHERE inw_id = wd.id 
                ORDER BY r_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            wd.j_id = j.j_id 
            AND wd.stage LIKE 'REV%' 
            AND r.received_date BETWEEN '$tsstart_date' AND '$tsend_date'
            AND r.created_on > '2025-01-01'
            $search
    ") or die($db->error);

    $rev_total_row = $rev_query_total->fetch_assoc();
    $rev_query_total_count = $rev_total_row['total_rev'];
    ?>

    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date; ?>','<?php echo $tsend_date; ?>','<?php echo $_REQUEST['wcust_id']; ?>','REVALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $rev_query_total_count; ?></b>
        </a>
    </td>
</tr>

				 
				 <tr>
    <th style="color: #4648b8">FIN</th>

    <?php
    // Handle date filters
    if ($_REQUEST['date_wise'] == "d") {
        if ($_REQUEST['wfrom_dt'] != "") {
            $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
            $send_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
        }
    } elseif ($_REQUEST['date_wise'] == "m") {
        $explode_month = explode("-", $_REQUEST['mfrom_dt']);
        $year = $explode_month[1];
        $month = $explode_month[0];

        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

        if ($month == date('m')) {
            $send_date = date("Y-m-d"); // today
        } else {
            $send_date = date("Y-m-t", strtotime("$year-$month-01"));
        }
    } else {
        $sstart_date = $fds;
        $send_date = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date = date('Y-m-d', strtotime($send_date));

    // Query: Get FIN counts grouped by received_date
    $fin_grouped_query = "
        SELECT 
            DATE_FORMAT(r.received_date,'%Y-%m-%d') AS fin_date,
            COUNT(DISTINCT wd.id) AS fin_count
        FROM 
            inw_inward_dtl AS wd
        LEFT JOIN 
            inw_revisions_dtl AS r 
            ON wd.id = r.inw_id 
            AND r.r_id = (
                SELECT r_id FROM inw_revisions_dtl 
                WHERE inw_id = wd.id 
                ORDER BY r_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            wd.j_id = j.j_id 
           
            AND wd.stage LIKE 'FIN%' 
            AND r.received_date BETWEEN '$tsstart_date' AND '$tsend_date'
            AND r.created_on > '2025-01-01'
            $search
        GROUP BY 
            fin_date
        ORDER BY 
            fin_date ASC
    ";

    $fin_data = [];
    $fin_result = $db->query($fin_grouped_query);
    while ($row = $fin_result->fetch_assoc()) {
        $fin_data[$row['fin_date']] = $row['fin_count'];
    }

    // Render daily cells
    $current = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($current <= $end) {
        $date_str = date('Y-m-d', $current);
        $count = isset($fin_data[$date_str]) ? $fin_data[$date_str] : 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $date_str; ?>','<?php echo $_REQUEST['wcust_id']; ?>','FIN');" style="cursor:pointer;">
                <?php if ($date_str < date('Y-m-d') && $count > 0) { ?>
                    <b style="color:red;"><?php echo $count; ?></b>
                <?php } else { ?>
                    <span style="color:#000;"><?php echo $count; ?></span>
                <?php } ?>
            </a>
        </td>
        <?php
        $current = strtotime('+1 day', $current);
    }

    // Total FIN count
    $fin_total_query = "
        SELECT COUNT(DISTINCT wd.id) AS total_fin
        FROM 
            inw_inward_dtl AS wd
        LEFT JOIN 
            inw_revisions_dtl AS r 
            ON wd.id = r.inw_id 
            AND r.r_id = (
                SELECT r_id FROM inw_revisions_dtl 
                WHERE inw_id = wd.id 
                ORDER BY r_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            wd.j_id = j.j_id 
            AND wd.stage LIKE 'FIN%' 
            AND r.received_date BETWEEN '$tsstart_date' AND '$tsend_date'
            AND r.created_on > '2025-01-01'
            $search
    ";

    $fin_total_result = $db->query($fin_total_query) or die($db->error);
    $fin_total_row = $fin_total_result->fetch_assoc();
    $fin_total_count = $fin_total_row['total_fin'];
    ?>

    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date; ?>','<?php echo $tsend_date; ?>','<?php echo $_REQUEST['wcust_id']; ?>','FINALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $fin_total_count; ?></b>
        </a>
    </td>
</tr>


              <tr>
    <th style="color: #4648b8">ISS</th>

    <?php
    // Handle date filters
    if ($_REQUEST['date_wise'] == "d" && $_REQUEST['wfrom_dt'] != "") {
        $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        $explode_month = explode("-", $_REQUEST['mfrom_dt']);
        $year = $explode_month[1];
        $month = $explode_month[0];

        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        if ($month == date('m')) {
            $send_date = date("Y-m-d");
        } else {
            $send_date = date("Y-m-t", strtotime("$year-$month-01"));
        }
    } else {
        $sstart_date = $fds;
        $send_date = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date = date('Y-m-d', strtotime($send_date));

    // Optimized: Grouped issue counts per date
    $issue_grouped_query = "
        SELECT 
            DATE_FORMAT(vi.received_date, '%Y-%m-%d') AS issue_date,
            COUNT(DISTINCT vi.vi_id) AS issue_count
        FROM 
            inw_volume_issues AS vi
        LEFT JOIN 
            inw_issue_corrections AS r 
            ON vi.vi_id = r.vi_id 
            AND r.ic_id = (
                SELECT ic_id FROM inw_issue_corrections 
                WHERE vi_id = r.vi_id 
                ORDER BY ic_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            vi.cust_id = j.j_cust_id 
            AND vi.j_id = j.j_id 
            AND DATE_FORMAT(vi.received_date, '%Y-%m-%d') BETWEEN '$tsstart_date' AND '$tsend_date'
            $search
        GROUP BY 
            issue_date
        ORDER BY 
            issue_date ASC
    ";

    $issue_data = [];
    $result = $db->query($issue_grouped_query);
    while ($row = $result->fetch_assoc()) {
        $issue_data[$row['issue_date']] = $row['issue_count'];
    }

    // Render daily counts
    $current = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($current <= $end) {
        $date_str = date('Y-m-d', $current);
        $count = isset($issue_data[$date_str]) ? $issue_data[$date_str] : 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $date_str; ?>','<?php echo $_REQUEST['wcust_id']; ?>','ISSUE');" style="cursor:pointer;">
                <?php if ($date_str < date('Y-m-d') && $count > 0) { ?>
                    <b style="color:red;"><?php echo $count; ?></b>
                <?php } else { ?>
                    <span style="color:#000;"><?php echo $count; ?></span>
                <?php } ?>
            </a>
        </td>
        <?php
        $current = strtotime('+1 day', $current);
    }

    // Optimized: Total issue count
    $issue_total_query = "
        SELECT COUNT(DISTINCT vi.vi_id) AS total_issue
        FROM 
            inw_volume_issues AS vi
        LEFT JOIN 
            inw_issue_corrections AS r 
            ON vi.vi_id = r.vi_id 
            AND r.ic_id = (
                SELECT ic_id FROM inw_issue_corrections 
                WHERE vi_id = r.vi_id 
                ORDER BY ic_id DESC LIMIT 1
            ),
            adm_journals AS j
        WHERE 
            vi.cust_id = j.j_cust_id 
            AND vi.j_id = j.j_id 
            AND DATE_FORMAT(vi.received_date, '%Y-%m-%d') BETWEEN '$tsstart_date' AND '$tsend_date'
            $search
    ";

    $issue_total_result = $db->query($issue_total_query) or die($db->error);
    $issue_total_row = $issue_total_result->fetch_assoc();
    $issue_total_count = $issue_total_row['total_issue'];
    ?>

    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date; ?>','<?php echo $tsend_date; ?>','<?php echo $_REQUEST['wcust_id']; ?>','ISSUEALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $issue_total_count; ?></b>
        </a>
    </td>
</tr>

			<tr class="totalrow">
    <th style="color: #4648b8"><b style="color:#0000FF;">TOTAL</b></th>
<?php
// ----------- DATE RANGE SETUP ------------
if ($_REQUEST['date_wise'] == "d" && !empty($_REQUEST['wfrom_dt'])) {
    $tsstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
    $tsend_date = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
} elseif ($_REQUEST['date_wise'] == "m") {
    list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
    $tsstart_date = date("Y-m-01", strtotime("$year-$month-01"));
    $tsend_date = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
} else {
    $tsstart_date = date('Y-m-d', strtotime($fds));
    $tsend_date = date('Y-m-d', strtotime($tds));
}

// ----------- PRE-FETCH COUNTS PER DATE ------------
// Function to safely add search condition to query
$search_condition = $search ?? ''; // use your actual $search condition

// FP counts grouped by date
$fp_counts = [];
$fp_sql = "
    SELECT DATE_FORMAT(created_dt, '%Y-%m-%d') AS dt, COUNT(id) AS cnt 
    FROM inw_inward_dtl 
    WHERE created_dt BETWEEN '$tsstart_date 00:00:00' AND '$tsend_date 23:59:59'
     
      $search
    GROUP BY dt";
$fp_result = $db->query($fp_sql);
while ($row = $fp_result->fetch_assoc()) {
    $fp_counts[$row['dt']] = (int)$row['cnt'];
}

// REV counts grouped by received_date
$rev_counts = [];
$rev_sql = "
    SELECT DATE_FORMAT(r.received_date, '%Y-%m-%d') AS dt, COUNT(DISTINCT r.inw_id) AS cnt
    FROM inw_inward_dtl wd
    LEFT JOIN inw_revisions_dtl r ON wd.id = r.inw_id AND r.r_id = (
        SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.id ORDER BY r_id DESC LIMIT 1
    )
    INNER JOIN adm_journals j ON wd.j_id = j.j_id
    WHERE wd.stage LIKE 'REV%'
      AND r.received_date BETWEEN '$tsstart_date 00:00:00' AND '$tsend_date 23:59:59'
      AND r.created_on > '2025-01-01'
      $search
    GROUP BY dt";
$rev_result = $db->query($rev_sql);
while ($row = $rev_result->fetch_assoc()) {
    $rev_counts[$row['dt']] = (int)$row['cnt'];
}

// FIN counts grouped by received_date
$fin_counts = [];
$fin_sql = "
    SELECT DATE_FORMAT(r.received_date, '%Y-%m-%d') AS dt, COUNT(DISTINCT r.inw_id) AS cnt
    FROM inw_inward_dtl wd
    LEFT JOIN inw_revisions_dtl r ON wd.id = r.inw_id AND r.r_id = (
        SELECT r_id FROM inw_revisions_dtl WHERE inw_id = wd.id ORDER BY r_id DESC LIMIT 1
    )
    INNER JOIN adm_journals j ON wd.j_id = j.j_id
    WHERE wd.stage LIKE 'FIN%'
      AND r.received_date BETWEEN '$tsstart_date 00:00:00' AND '$tsend_date 23:59:59'
      AND r.created_on > '2025-01-01'
      $search
    GROUP BY dt";
$fin_result = $db->query($fin_sql);
while ($row = $fin_result->fetch_assoc()) {
    $fin_counts[$row['dt']] = (int)$row['cnt'];
}

// ISSUE counts grouped by vi.received_date
$issue_counts = [];
$issue_sql = "
    SELECT DATE_FORMAT(vi.received_date, '%Y-%m-%d') AS dt, COUNT(DISTINCT vi.vi_id) AS cnt
    FROM inw_volume_issues vi
    LEFT JOIN inw_issue_corrections r ON vi.vi_id = r.vi_id AND r.ic_id = (
        SELECT ic_id FROM inw_issue_corrections WHERE vi_id = r.vi_id ORDER BY ic_id DESC LIMIT 1
    ),
    adm_journals j
    WHERE vi.cust_id = j.j_cust_id
      AND vi.j_id = j.j_id
      AND vi.received_date BETWEEN '$tsstart_date 00:00:00' AND '$tsend_date 23:59:59'
      $search
    GROUP BY dt";
$issue_result = $db->query($issue_sql);
while ($row = $issue_result->fetch_assoc()) {
    $issue_counts[$row['dt']] = (int)$row['cnt'];
}

// ----------- LOOP THROUGH DATES AND OUTPUT DAILY TOTALS ------------
$current_date = $tsstart_date;
$total_all = 0;

while (strtotime($current_date) <= strtotime($tsend_date)) {
    $fp = $fp_counts[$current_date] ?? 0;
    $rev = $rev_counts[$current_date] ?? 0;
    $fin = $fin_counts[$current_date] ?? 0;
    $issue = $issue_counts[$current_date] ?? 0;

    $daily_total = $fp + $rev + $fin + $issue;
    $total_all += $daily_total;
    ?>
    <td>
        <a onclick="loaddata2('<?php echo $current_date ?>','<?php echo $_REQUEST['wcust_id'] ?>','DALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $daily_total ?></b>
        </a>
    </td>
    <?php
    $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
}

// ----------- FINAL TOTAL OUTPUT ------------
?>
<td>
    <a onclick="loaddata1('<?php echo $tsstart_date ?>','<?php echo $tsend_date ?>','<?php echo $_REQUEST['wcust_id'] ?>','TALL');" style="cursor:pointer;">
        <b style="color:#0000FF;"><?php echo $total_all ?></b>
    </a>
</td>
</tr>


                <?php
              
           // } 
			  $r++;
			  }
		
		?>
		
		   </tbody>
                                                        </table>
														
		<div id="article_list"></div>			
<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
          
        </div>

        <!-- Table section starts-->

        
      </div>
      <!-- Table section ends -->
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 




 
    



<!-- Footer Section -->
<?php echo scripts(); ?>	

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery UI (Datepicker) -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

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
                    url: "artilce_list_on_inflow_date.php", // Replace with your PHP script URL
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
                    url: "artilce_list_on_date_inflow_all.php", // Replace with your PHP script URL
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
                    url: "artilce_list_on_date_inflow_all.php", // Replace with your PHP script URL
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

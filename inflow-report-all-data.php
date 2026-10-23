<?php include "includes/includes.php";
include "includes/modern-ui.php";
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
    <?php mu_head(); ?>
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-matrix{min-width:0}.mu-matrix th,.mu-matrix td{text-align:center}
.mu-matrix tbody th{background:#f4f7fb;text-align:left;text-transform:none;letter-spacing:0;font-size:13px}
.mu-matrix td a{display:inline-block;min-width:26px;padding:2px 6px;border-radius:14px;color:var(--info);text-decoration:none;transition:all .2s}
.mu-matrix td a:hover{background:var(--info);color:#fff}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Inflow Data Accuracy Report</h1>
            <p>Inflow entries per day, by customer, live and archive &nbsp;&middot;&nbsp; <a href="inflowreport" style="color:#fff;text-decoration:underline;">Open the new version</a></p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:240px;"><label for="wcust_id">Customer</label>
                <select class="customer_drop" name="wcust_id" id="wcust_id" >
<?php
if($_COOKIE['user_level']=='1') { $sql_customers = $auth->showCustomerlist(); }
else { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if (($_REQUEST['wcust_id'] ?? '') == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
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
                <a class="mu-btn" href="inflowreport-old" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
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
        <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Inflow data <span class="mu-right">Click a number to list the articles</span></h3>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table mu-matrix">
														
													    
															
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
    // ----------------- DATE RANGE HANDLING -----------------
    if ($_REQUEST['date_wise'] == "d" && $_REQUEST['wfrom_dt'] != "") {
        $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date   = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        $send_date   = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
    } else {
        $sstart_date = $fds;
        $send_date   = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date   = date('Y-m-d', strtotime($send_date));

    // ----------------- QUERY: LIVE + ARCHIVE -----------------
    $fp_query = "
        SELECT created_date, COUNT(*) AS cnt
        FROM (
            SELECT DATE_FORMAT(created_dt,'%Y-%m-%d') AS created_date
            FROM workflow.inw_inward_dtl 
            WHERE DATE_FORMAT(created_dt,'%Y-%m-%d') BETWEEN '$tsstart_date' AND '$tsend_date' $search

            UNION ALL

            SELECT DATE_FORMAT(created_dt,'%Y-%m-%d') AS created_date
            FROM workflow_archive.inw_inward_dtl 
            WHERE DATE_FORMAT(created_dt,'%Y-%m-%d') BETWEEN '$tsstart_date' AND '$tsend_date' $search
        ) AS combined
        GROUP BY created_date
        ORDER BY created_date
    ";

    $fp_data = [];
    $fp_total = 0;
    $fp_res = $db->query($fp_query);
    while ($row = $fp_res->fetch_assoc()) {
        $fp_data[$row['created_date']] = $row['cnt'];
        $fp_total += $row['cnt'];
    }

    // ----------------- DAILY LOOP -----------------
    $cur = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $c = $fp_data[$d] ?? 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $d ?>','<?php echo $_REQUEST['wcust_id'] ?>','FP');" style="cursor:pointer;">
                <b style="color:<?php echo ($d < date('Y-m-d') && $c > 0) ? 'red' : '#000' ?>"><?php echo $c ?></b>
            </a>
        </td>
        <?php
        $cur = strtotime('+1 day', $cur);
    }
    ?>
    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date ?>','<?php echo $tsend_date ?>','<?php echo $_REQUEST['wcust_id'] ?>','FPALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $fp_total ?></b>
        </a>
    </td>
</tr>


<tr>
    <th style="color: #4648b8">REV</th>
    <?php
    // ----------------- DATE RANGE -----------------
    if ($_REQUEST['date_wise'] == "d" && $_REQUEST['wfrom_dt'] != "") {
        $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date   = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        $send_date   = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
    } else {
        $sstart_date = $fds;
        $send_date   = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date   = date('Y-m-d', strtotime($send_date));

    // ----------------- QUERY: LIVE + ARCHIVE (optimized latest revision) -----------------
    $rev_query = "
        SELECT rev_date, COUNT(DISTINCT inw_id) AS cnt
        FROM (
            -- LIVE
            SELECT wd.id AS inw_id, DATE(r.received_date) AS rev_date
            FROM workflow.inw_inward_dtl wd
            INNER JOIN workflow.adm_journals j ON wd.j_id = j.j_id
            INNER JOIN (
                SELECT inw_id, MAX(r_id) AS latest_rid
                FROM workflow.inw_revisions_dtl
                GROUP BY inw_id
            ) lr ON wd.id = lr.inw_id
            INNER JOIN workflow.inw_revisions_dtl r ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
            WHERE wd.stage LIKE 'REV%'
              AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
              AND r.created_on > '2025-01-01'
              $search

            UNION ALL

            -- ARCHIVE
            SELECT wd.id AS inw_id, DATE(r.received_date) AS rev_date
            FROM workflow_archive.inw_inward_dtl wd
            INNER JOIN workflow_archive.adm_journals j ON wd.j_id = j.j_id
            INNER JOIN (
                SELECT inw_id, MAX(r_id) AS latest_rid
                FROM workflow_archive.inw_revisions_dtl
                GROUP BY inw_id
            ) lr ON wd.id = lr.inw_id
            INNER JOIN workflow_archive.inw_revisions_dtl r ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
            WHERE wd.stage LIKE 'REV%'
              AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
              AND r.created_on > '2025-01-01'
              $search
        ) combined
        GROUP BY rev_date
        ORDER BY rev_date
    ";

    $rev_data = [];
    $rev_total = 0;
    $rev_res = $db->query($rev_query) or die($db->error);
    while ($row = $rev_res->fetch_assoc()) {
        $rev_data[$row['rev_date']] = $row['cnt'];
        $rev_total += $row['cnt'];
    }

    // ----------------- LOOP -----------------
    $cur = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $c = $rev_data[$d] ?? 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $d ?>','<?php echo $_REQUEST['wcust_id'] ?>','REV');" style="cursor:pointer;">
                <?php if ($d < date('Y-m-d') && $c > 0) { ?>
                    <b style="color:red;"><?php echo $c ?></b>
                <?php } else { ?>
                    <span style="color:#000;"><?php echo $c ?></span>
                <?php } ?>
            </a>
        </td>
        <?php
        $cur = strtotime('+1 day', $cur);
    }
    ?>
    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date ?>','<?php echo $tsend_date ?>','<?php echo $_REQUEST['wcust_id'] ?>','REVALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $rev_total ?></b>
        </a>
    </td>
</tr>


 
	<tr>
    <th style="color: #4648b8">FIN</th>
    <?php
    // ----------------- DATE RANGE -----------------
    if ($_REQUEST['date_wise'] == "d" && $_REQUEST['wfrom_dt'] != "") {
        $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date   = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        $send_date   = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
    } else {
        $sstart_date = $fds;
        $send_date   = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date   = date('Y-m-d', strtotime($send_date));

    // ----------------- QUERY: LIVE + ARCHIVE (optimized latest revision) -----------------
    $fin_query = "
        SELECT fin_date, COUNT(DISTINCT inw_id) AS cnt
        FROM (
            -- LIVE
            SELECT wd.id AS inw_id, DATE(r.received_date) AS fin_date
            FROM workflow.inw_inward_dtl wd
            INNER JOIN workflow.adm_journals j ON wd.j_id = j.j_id
            INNER JOIN (
                SELECT inw_id, MAX(r_id) AS latest_rid
                FROM workflow.inw_revisions_dtl
                GROUP BY inw_id
            ) lr ON wd.id = lr.inw_id
            INNER JOIN workflow.inw_revisions_dtl r ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
            WHERE wd.stage LIKE 'FIN%'
              AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
              AND r.created_on > '2025-01-01'
              $search

            UNION ALL

            -- ARCHIVE
            SELECT wd.id AS inw_id, DATE(r.received_date) AS fin_date
            FROM workflow_archive.inw_inward_dtl wd
            INNER JOIN workflow_archive.adm_journals j ON wd.j_id = j.j_id
            INNER JOIN (
                SELECT inw_id, MAX(r_id) AS latest_rid
                FROM workflow_archive.inw_revisions_dtl
                GROUP BY inw_id
            ) lr ON wd.id = lr.inw_id
            INNER JOIN workflow_archive.inw_revisions_dtl r ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
            WHERE wd.stage LIKE 'FIN%'
              AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
              AND r.created_on > '2025-01-01'
              $search
        ) combined
        GROUP BY fin_date
        ORDER BY fin_date
    ";

    $fin_data = [];
    $fin_total = 0;
    $fin_res = $db->query($fin_query) or die($db->error);
    while ($row = $fin_res->fetch_assoc()) {
        $fin_data[$row['fin_date']] = $row['cnt'];
        $fin_total += $row['cnt'];
    }

    // ----------------- LOOP -----------------
    $cur = strtotime($tsstart_date);
    $end = strtotime($tsend_date);

    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $c = $fin_data[$d] ?? 0;
        ?>
        <td>
            <a onclick="loaddata('<?php echo $d ?>','<?php echo $_REQUEST['wcust_id'] ?>','FIN');" style="cursor:pointer;">
                <?php if ($d < date('Y-m-d') && $c > 0) { ?>
                    <b style="color:red;"><?php echo $c ?></b>
                <?php } else { ?>
                    <span style="color:#000;"><?php echo $c ?></span>
                <?php } ?>
            </a>
        </td>
        <?php
        $cur = strtotime('+1 day', $cur);
    }
    ?>
    <td>
        <a onclick="loaddata1('<?php echo $tsstart_date ?>','<?php echo $tsend_date ?>','<?php echo $_REQUEST['wcust_id'] ?>','FINALL');" style="cursor:pointer;">
            <b style="color:#0000FF;"><?php echo $fin_total ?></b>
        </a>
    </td>
</tr>
			

				 
			


            <tr>
    <th style="color: #4648b8">ISS</th>

    <?php
    // ----------------- DATE RANGE -----------------
    if ($_REQUEST['date_wise'] == "d" && $_REQUEST['wfrom_dt'] != "") {
        $sstart_date = date('Y-m-d', strtotime($_REQUEST['wfrom_dt']));
        $send_date   = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
    } elseif ($_REQUEST['date_wise'] == "m") {
        list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
        $sstart_date = date("Y-m-01", strtotime("$year-$month-01"));
        $send_date   = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
    } else {
        $sstart_date = $fds;
        $send_date   = $tds;
    }

    $tsstart_date = date('Y-m-d', strtotime($sstart_date));
    $tsend_date   = date('Y-m-d', strtotime($send_date));

    // ----------------- QUERY: DAILY COUNTS (optimized) -----------------
    $issue_grouped_query = "
        SELECT 
            DATE(vi.received_date) AS issue_date,
            COUNT(DISTINCT vi.vi_id) AS issue_count
        FROM inw_volume_issues vi
        INNER JOIN adm_journals j 
            ON vi.j_id = j.j_id AND vi.cust_id = j.j_cust_id
        LEFT JOIN (
            SELECT vi_id, MAX(ic_id) AS latest_icid
            FROM inw_issue_corrections
            GROUP BY vi_id
        ) lr ON vi.vi_id = lr.vi_id
        LEFT JOIN inw_issue_corrections r 
            ON r.vi_id = lr.vi_id AND r.ic_id = lr.latest_icid
        WHERE DATE(vi.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
        $search
        GROUP BY issue_date
        ORDER BY issue_date ASC
    ";

    $issue_data = [];
    $result = $db->query($issue_grouped_query) or die($db->error);
    while ($row = $result->fetch_assoc()) {
        $issue_data[$row['issue_date']] = $row['issue_count'];
    }

    // ----------------- LOOP: Daily cells -----------------
    $current = strtotime($tsstart_date);
    $end     = strtotime($tsend_date);

    while ($current <= $end) {
        $date_str = date('Y-m-d', $current);
        $count    = $issue_data[$date_str] ?? 0;
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

    // ----------------- QUERY: TOTAL (optimized) -----------------
    $issue_total_query = "
        SELECT COUNT(DISTINCT vi.vi_id) AS total_issue
        FROM inw_volume_issues vi
        INNER JOIN adm_journals j 
            ON vi.j_id = j.j_id AND vi.cust_id = j.j_cust_id
        LEFT JOIN (
            SELECT vi_id, MAX(ic_id) AS latest_icid
            FROM inw_issue_corrections
            GROUP BY vi_id
        ) lr ON vi.vi_id = lr.vi_id
        LEFT JOIN inw_issue_corrections r 
            ON r.vi_id = lr.vi_id AND r.ic_id = lr.latest_icid
        WHERE DATE(vi.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
        $search
    ";

    $issue_total_result = $db->query($issue_total_query) or die($db->error);
    $issue_total_row    = $issue_total_result->fetch_assoc();
    $issue_total_count  = $issue_total_row['total_issue'];
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
    $tsend_date   = date('Y-m-d', strtotime($_REQUEST['wto_dt']));
} elseif ($_REQUEST['date_wise'] == "m") {
    list($month, $year) = explode("-", $_REQUEST['mfrom_dt']);
    $tsstart_date = date("Y-m-01", strtotime("$year-$month-01"));
    $tsend_date   = ($month == date('m')) ? date("Y-m-d") : date("Y-m-t", strtotime("$year-$month-01"));
} else {
    $tsstart_date = date('Y-m-d', strtotime($fds));
    $tsend_date   = date('Y-m-d', strtotime($tds));
}

// ----------- FP COUNTS (workflow + archive) ------------
$fp_counts = [];
$fp_sql = "
    SELECT dt, COUNT(*) AS cnt
    FROM (
        SELECT DATE(created_dt) AS dt
        FROM workflow.inw_inward_dtl
        WHERE DATE(created_dt) BETWEEN '$tsstart_date' AND '$tsend_date' $search
        UNION ALL
        SELECT DATE(created_dt) AS dt
        FROM workflow_archive.inw_inward_dtl
        WHERE DATE(created_dt) BETWEEN '$tsstart_date' AND '$tsend_date' $search
    ) t
    GROUP BY dt
";
$res = $db->query($fp_sql) or die($db->error);
while ($row = $res->fetch_assoc()) {
    $fp_counts[$row['dt']] = (int)$row['cnt'];
}

// ----------- REV COUNTS (workflow + archive, optimized with MAX) ------------
$rev_counts = [];
$rev_sql = "
    SELECT dt, COUNT(DISTINCT inw_id) AS cnt
    FROM (
        SELECT DATE(r.received_date) AS dt, r.inw_id
        FROM workflow.inw_inward_dtl wd
        INNER JOIN (
            SELECT inw_id, MAX(r_id) AS latest_rid
            FROM workflow.inw_revisions_dtl
            GROUP BY inw_id
        ) lr ON wd.id = lr.inw_id
        INNER JOIN workflow.inw_revisions_dtl r 
            ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
        WHERE wd.stage LIKE 'REV%'
          AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
          AND r.created_on > '2025-01-01'
          $search
        UNION ALL
        SELECT DATE(r.received_date) AS dt, r.inw_id
        FROM workflow_archive.inw_inward_dtl wd
        INNER JOIN (
            SELECT inw_id, MAX(r_id) AS latest_rid
            FROM workflow_archive.inw_revisions_dtl
            GROUP BY inw_id
        ) lr ON wd.id = lr.inw_id
        INNER JOIN workflow_archive.inw_revisions_dtl r 
            ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
        WHERE wd.stage LIKE 'REV%'
          AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
          AND r.created_on > '2025-01-01'
          $search
    ) t
    GROUP BY dt
";
$res = $db->query($rev_sql) or die($db->error);
while ($row = $res->fetch_assoc()) {
    $rev_counts[$row['dt']] = (int)$row['cnt'];
}

// ----------- FIN COUNTS (workflow + archive, optimized with MAX) ------------
$fin_counts = [];
$fin_sql = "
    SELECT dt, COUNT(DISTINCT inw_id) AS cnt
    FROM (
        SELECT DATE(r.received_date) AS dt, r.inw_id
        FROM workflow.inw_inward_dtl wd
        INNER JOIN (
            SELECT inw_id, MAX(r_id) AS latest_rid
            FROM workflow.inw_revisions_dtl
            GROUP BY inw_id
        ) lr ON wd.id = lr.inw_id
        INNER JOIN workflow.inw_revisions_dtl r 
            ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
        WHERE wd.stage LIKE 'FIN%'
          AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
          AND r.created_on > '2025-01-01'
          $search
        UNION ALL
        SELECT DATE(r.received_date) AS dt, r.inw_id
        FROM workflow_archive.inw_inward_dtl wd
        INNER JOIN (
            SELECT inw_id, MAX(r_id) AS latest_rid
            FROM workflow_archive.inw_revisions_dtl
            GROUP BY inw_id
        ) lr ON wd.id = lr.inw_id
        INNER JOIN workflow_archive.inw_revisions_dtl r 
            ON r.inw_id = lr.inw_id AND r.r_id = lr.latest_rid
        WHERE wd.stage LIKE 'FIN%'
          AND DATE(r.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
          AND r.created_on > '2025-01-01'
          $search
    ) t
    GROUP BY dt
";
$res = $db->query($fin_sql) or die($db->error);
while ($row = $res->fetch_assoc()) {
    $fin_counts[$row['dt']] = (int)$row['cnt'];
}

// ----------- ISSUE COUNTS (workflow + archive, optimized with MAX) ------------
$issue_counts = [];
$issue_sql = "
    SELECT dt, COUNT(DISTINCT vi_id) AS cnt
    FROM (
        SELECT DATE(vi.received_date) AS dt, vi.vi_id
        FROM workflow.inw_volume_issues vi
        INNER JOIN adm_journals j ON vi.j_id = j.j_id AND vi.cust_id = j.j_cust_id
        LEFT JOIN (
            SELECT vi_id, MAX(ic_id) AS latest_icid
            FROM workflow.inw_issue_corrections
            GROUP BY vi_id
        ) lr ON vi.vi_id = lr.vi_id
        LEFT JOIN workflow.inw_issue_corrections r 
            ON r.vi_id = lr.vi_id AND r.ic_id = lr.latest_icid
        WHERE DATE(vi.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
        $search
        UNION ALL
        SELECT DATE(vi.received_date) AS dt, vi.vi_id
        FROM workflow_archive.inw_volume_issues vi
        INNER JOIN adm_journals j ON vi.j_id = j.j_id AND vi.cust_id = j.j_cust_id
        LEFT JOIN (
            SELECT vi_id, MAX(ic_id) AS latest_icid
            FROM workflow_archive.inw_issue_corrections
            GROUP BY vi_id
        ) lr ON vi.vi_id = lr.vi_id
        LEFT JOIN workflow_archive.inw_issue_corrections r 
            ON r.vi_id = lr.vi_id AND r.ic_id = lr.latest_icid
        WHERE DATE(vi.received_date) BETWEEN '$tsstart_date' AND '$tsend_date'
        $search
    ) t
    GROUP BY dt
";
$res = $db->query($issue_sql) or die($db->error);
while ($row = $res->fetch_assoc()) {
    $issue_counts[$row['dt']] = (int)$row['cnt'];
}

// ----------- LOOP THROUGH DATES AND OUTPUT DAILY TOTALS ------------
$current_date = $tsstart_date;
$total_all = 0;

while (strtotime($current_date) <= strtotime($tsend_date)) {
    $fp    = $fp_counts[$current_date] ?? 0;
    $rev   = $rev_counts[$current_date] ?? 0;
    $fin   = $fin_counts[$current_date] ?? 0;
    $issue = $issue_counts[$current_date] ?? 0;

    $daily_total = $fp + $rev + $fin + $issue;
    $total_all  += $daily_total;
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
		
		   <?php if(isset($_REQUEST['filter'])) { ?></tbody></table></div>
<div id="article_list"></div>			
<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
</section>
<?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-table"></i>Choose the period and customer, then press <b>Get Result</b>.</div>
<?php } ?>
      </div>
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
<script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
  </body>
</html>

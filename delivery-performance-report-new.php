<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php'); 
?>
<?php
function getHoursMinutes($seconds, $format = '%02d:%02d') {

    if (!is_numeric($seconds) || $seconds < 0) {
        return '00:00';
    }

    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = $minutes % 60;

    return sprintf($format, $hours, $remainMinutes);
}

extract($_POST);

// Initialize filters
$search = $search1 = $search2 = $search3 = $search4 = $search_cd = $search_cm = $search_cy = $ccc = "";

if(isset($_REQUEST['wgetaction']))
{
// Filter: Customer ID
if (!empty($_REQUEST['cust_id']) && $_REQUEST['cust_id'] !== 'all') {
    $ccc .= " AND wd.cust_id = '{$_REQUEST['cust_id']}' ";
}

// Filter: Job ID
if (!empty($_REQUEST['j_id']) && $_REQUEST['j_id'] !== 'all') {
    $ccc .= " AND wd.j_id = '{$_REQUEST['j_id']}' ";
}

// Filter: Stage
if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] !== 'all') {
    $search2 .= " AND wd.stage LIKE '%{$_REQUEST['stage']}%' ";
}


// Filter: Platform
if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== 'all') {
    $ccc .= " AND j.j_platform = '{$_REQUEST['dp_platform']}' ";
}


// Handle Date Filters
$dateWise = $_REQUEST['date_wise'] ?? '';



    if ($dateWise === "d" && !empty($_REQUEST['from_dt'])) {
        $frm = date('Y-m-d', strtotime($_REQUEST['from_dt']));
        $to = date('Y-m-d', strtotime($_REQUEST['to_dt']));
        $search .= " AND DATE_FORMAT(wd.sent_date,'%Y-%m-%d') BETWEEN '$frm' AND '$to' ";
    } elseif ($dateWise === "m" && !empty($_REQUEST['mfrom_dt'])) {
        $search .= " AND DATE_FORMAT(wd.sent_date,'%m-%Y') = '{$_REQUEST['mfrom_dt']}' ";
    } elseif ($dateWise === "y" && !empty($_REQUEST['yfrom_dt'])) {
        $search .= " AND DATE_FORMAT(wd.sent_date,'%Y') = '{$_REQUEST['yfrom_dt']}' ";
    } else {
        $search3 .= " AND wd.sent_date >= '2020-03-01' ";
        $search4 .= " AND wd.sent_date >= '2020-03-01' ";
    }

    if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== "all") {
        $search .= " AND j.j_platform = '{$_REQUEST['dp_platform']}' ";
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
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-total td{background:#eef3f8 !important;font-weight:700;color:var(--navy)}
.mu-chart{position:relative}
.mu-two{display:grid;grid-template-columns:minmax(300px,380px) 1fr;gap:16px}
@media(max-width:900px){.mu-two{grid-template-columns:1fr}}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Delivery Performance Report</h1>
            <p>On time, ahead and delayed dispatches against the due date, live and archive</p></div>
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
              <div class="mu-field" style="min-width:180px;"><label for="dp_platform">Platform</label>
                <select class="customer_drop" name="dp_platform" id="dp_platform" >
                  <option value="all" <?php if (($_REQUEST['dp_platform'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php foreach ($platform_journal_array as $id => $val) { ?>
                  <option <?php if (($_REQUEST["dp_platform"] ?? '') == $val) { ?>selected<?php } ?> value="<?php echo $val; ?>"><?php echo $val; ?></option>
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
                <a class="mu-btn" href="deliveryperformance" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


        <!-- Table section -->
		<?php
		if(isset($_REQUEST['filter']))
		{
		?>
	             <section class="mu-panel mu-reveal">
<h3><i class="fa fa-truck"></i> Dispatched articles <span class="mu-right">Live + archive</span></h3>
			
	<?php
	$scheduleSummary = [
    'On Time' => 0,
    'Ahead'   => 0,
    'Delay'   => 0,
    'Client Approved Delay' => 0
];

// ------------------------------------
// 1. Setup: databases and filters
// ------------------------------------
$databases = ['workflow', 'workflow_archive'];
$conditions = "$ccc $search $search2 $search4";

// ------------------------------------
// 2. Helper: Build Dispatch Query
// ------------------------------------
function buildDispatchQuery($dbName, $conditions, $label) {

    // Archive has priority → exclude Live if exists in Archive
    $excludeArchive = '';
    if ($dbName === 'workflow') {
        $excludeArchive = "
            AND wd.a_id NOT IN (
                SELECT DISTINCT a_id
                FROM workflow_archive.inw_dispatch_history
            )
        ";
    }

    return "
        SELECT 
            wd.a_id,
            wd.stage,
            wd.sent_date,
            wd.cust_id,
            wd.j_id,

            c.cust_name,
            j.j_code,

            wdd.pub_id,
            wdd.article_title,
            wdd.recv_dt,
            wdd.due_dt,
            wdd.created_dt,
            wdd.page_count,
            wdd.inw_type,
			wdd.late_dispatch,
            '{$label}' AS source

        FROM {$dbName}.inw_dispatch_history wd

        /* 🔑 keep only latest dispatch per article + stage */
        INNER JOIN (
            SELECT a_id, stage, MAX(sent_date) AS max_sent
            FROM {$dbName}.inw_dispatch_history
            GROUP BY a_id, stage
        ) latest
            ON latest.a_id = wd.a_id
           AND latest.stage = wd.stage
           AND latest.max_sent = wd.sent_date

        INNER JOIN {$dbName}.inw_inward_dtl wdd
            ON wdd.id = wd.a_id

        INNER JOIN {$dbName}.adm_customer_master c
            ON c.id = wd.cust_id

        INNER JOIN {$dbName}.adm_journals j
            ON j.j_id = wd.j_id

        WHERE wdd.article_title NOT LIKE '%_TOC'
        $conditions
        $excludeArchive
    ";
}




// ------------------------------------
// 3. Main Dispatch History Query
// ------------------------------------
$dispatch_sql_parts = [];

foreach (['workflow', 'workflow_archive'] as $dbName) {
    $label = ($dbName === 'workflow') ? 'Live' : 'Archive';
    $dispatch_sql_parts[] = buildDispatchQuery($dbName, $conditions, $label);
}

$query = implode(" UNION ALL ", $dispatch_sql_parts);

$articles_result = $db->query($query) or die($db->error);


// Group by Article ID
$dispatchData = [];
$article_ids  = [];

while ($row = $articles_result->fetch_assoc()) {

    // unique per article + stage
    $key = $row['a_id'] . '_' . $row['stage'];

    $dispatchData[$key] = $row;
    $article_ids[] = $row['a_id'];
}

// ------------------------------------
// 4. FINAL COUNT (from table data)
// ------------------------------------
$live_count = 0;
$archive_count = 0;

foreach ($dispatchData as $row) {
    if ($row['source'] === 'Live') {
        $live_count++;
    } elseif ($row['source'] === 'Archive') {
        $archive_count++;
    }
}

$total_pages = $live_count + $archive_count;



// ------------------------------------
// 4. Count Query
// ------------------------------------
function buildCountQuery($dbName, $conditions) {
    return "
        SELECT wd.a_id
        FROM {$dbName}.inw_dispatch_history wd
        JOIN {$dbName}.inw_inward_dtl wdd ON wd.a_id = wdd.id
        WHERE wdd.article_title NOT LIKE '%_TOC'
        $conditions
    ";
}
$count_parts = [];
foreach ($databases as $dbName) {
    $count_parts[] = buildCountQuery($dbName, $conditions);
}

$count_union_sql = implode(" UNION ALL ", [
    buildDispatchQuery('workflow', $conditions, 'Live'),
    buildDispatchQuery('workflow_archive', $conditions, 'Archive')
]);



// ------------------------------------
// 5. Generic Function: Cross DB UNION SELECT
// ------------------------------------
function unionSelect($table, $where = '', $databases = ['workflow', 'workflow_archive']) {
    $parts = [];
    foreach ($databases as $db) {
        $label = ($db === 'workflow') ? 'Live' : 'Archive';
        $parts[] = "SELECT *, '{$label}' AS source FROM {$db}.{$table}" . ($where ? " WHERE $where" : "");
    }
    return implode(" UNION ALL ", $parts);
}


// ------------------------------------
// 6. Fetch Inward Data
// ------------------------------------
$id_list = implode(",", array_map('intval', array_unique($article_ids)));
$inward_sql = unionSelect('inw_inward_dtl', "id IN ($id_list)", $databases);
$inward_result = $db->query($inward_sql) or die($db->error);
$inward_data = [];
while ($row = $inward_result->fetch_assoc()) {
      $inward_data[$row['id']] = $row; // Now includes 'source' field

}
$stage_filter = "";
if (!empty($_REQUEST['stage'])) {
    $stage = mysqli_real_escape_string($db, $_REQUEST['stage']);
    $stage_filter = " AND t.stage LIKE '%$stage%' ";
}


$transaction_sql = "
    SELECT 
        t.project_id,

        /* Overall Total Time */
        SUM(TIME_TO_SEC(TIMEDIFF(t.end_time, t.start_time))) AS total_time_all,

        /* Pre-Editing Time Logic */
        SUM(
            CASE
                WHEN t.dept = 22 AND d.cust_id != '33'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                WHEN t.dept = 1 AND d.cust_id = '33'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                WHEN t.dept = 24 AND d.cust_id = '33' AND d.inw_type = 'Latex'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                WHEN t.dept = 1 AND d.cust_id = '33' AND d.inw_type != 'Latex'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                WHEN t.dept = 1 AND d.cust_id != '33' AND d.inw_type != 'Latex'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                WHEN t.dept = 2 AND d.cust_id = '7'
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))

                ELSE 0
            END
        ) AS total_time_dept22,

        /* PAG Time */
        SUM(
            CASE 
                WHEN t.dept = 10
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))
                ELSE 0
            END
        ) AS total_time_dept10,

        /* PRQC Time */
        SUM(
            CASE 
                WHEN t.dept = 14
                    THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))
                ELSE 0
            END
        ) AS total_time_dept14,

        /* Production Time (Pre-Edit + PAG + PRQC) */
        SUM(
            CASE
                WHEN (
                    (t.dept = 22 AND d.cust_id != '33') OR
                    (t.dept = 1 AND d.cust_id = '33') OR
                    (t.dept = 24 AND d.cust_id = '33' AND d.inw_type = 'Latex') OR
                    (t.dept = 1 AND d.cust_id = '33' AND d.inw_type != 'Latex') OR
                    (t.dept = 1 AND d.cust_id != '33' AND d.inw_type != 'Latex') OR
                    (t.dept = 2 AND d.cust_id = '7') OR
                    (t.dept = 10) OR
                    (t.dept = 14)
                )
                THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))
                ELSE 0
            END
        ) AS total_time_selected_depts

    FROM (" . unionSelect('inw_transactions', "project_id IN ($id_list)", $databases) . ") t

    JOIN (" . unionSelect('inw_inward_dtl', "id IN ($id_list)", $databases) . ") d
        ON d.id = t.project_id

    WHERE 
        t.start_time IS NOT NULL
        AND t.end_time IS NOT NULL
        $stage_filter

    GROUP BY t.project_id
";
$trans_result = $db->query($transaction_sql) or die($db->error);

$trans_time_data = [];
while ($row = $trans_result->fetch_assoc()) {
    $trans_time_data[$row['project_id']] = $row;
}

// ------------------------------------
// 8. Revisions Data
// ------------------------------------
$revision_sql = unionSelect('inw_revisions_dtl', "inw_id IN ($id_list)", $databases);
$revision_result = $db->query($revision_sql) or die($db->error);

$revision_data = [];
while ($row = $revision_result->fetch_assoc()) {
    $revision_data[$row['inw_id']][$row['type']][$row['revision_count']] = $row;
}
?>



<div class="mu-stats" style="margin-bottom:14px;">
  <div class="mu-stat info"><i class="fa fa-file-text-o"></i><div><span>Total</span><b data-count="<?php echo (int)$total_pages; ?>">0</b></div></div>
  <div class="mu-stat ok"><i class="fa fa-database"></i><div><span>Live</span><b data-count="<?php echo (int)$live_count; ?>">0</b></div></div>
  <div class="mu-stat bad"><i class="fa fa-archive"></i><div><span>Archive</span><b data-count="<?php echo (int)$archive_count; ?>">0</b></div></div>
</div>

<button id="toggleTableBtn" type="button" class="mu-btn primary" style="margin-bottom:12px;">
    Show Table
</button>

<div id="tableWrapper" style="display:none;">
    
    <!-- ✅ ONLY ONE TABLE -->
    <table class="mu-table nowrap" id="dispatchTable" style="width:100%">
        <thead>
            <tr>
                <th>S.no</th>
                <th>Client</th>
                <th>Journal Name</th>
                <th>FileName</th>
                <th>Stage</th>
                <th>Received Date</th>
                <th>Due Date</th>
                <th>Dispatched Date</th>
                <th>Pre Editing/CE/Tex-Preclean Time Taken</th>
                <th>PAG Time Taken</th>
                <th>PRQC Time Taken</th>
                <th>Total Production Time Taken</th>
                <th>Overall Time Taken</th>
                <th>Page Count</th>
                <th>Schedule</th>
            </tr>
        </thead>
        <tbody>
            

          <?php
		 $sno = 0;
		 $dateSchedule = [];
foreach ($dispatchData as $row_history) {

    $sno++;
    $a_id = $row_history['a_id'];


        // Base inward record
        $query_disp = $inward_data[$a_id] ?? [];

        // Preloaded transaction times
		$total_time_all  = $trans_time_data[$a_id]['total_time_all'] ?? 0;
		$total_time_pag  = $trans_time_data[$a_id]['total_time_dept10'] ?? 0;
		$total_time_prqc = $trans_time_data[$a_id]['total_time_dept14'] ?? 0;
		$total_time_pre  = $trans_time_data[$a_id]['total_time_dept22'] ?? 0;
		$total_time_prod = $trans_time_data[$a_id]['total_time_selected_depts'] ?? 0;

        // Stage detection
        $stage = $row_history['stage'];
        $stage_clean = preg_replace(["/REV[0-9]+/", "/ISSCOR[0-9]+/"], ["REV", "ISSCOR"], $stage);

        // Dates logic
        $recv_dt = $due_dt = '';
        $page_count = $query_disp['page_count'] ?? 0;

        if ($stage === "FP") {
            $recv_dt = $query_disp['recv_dt'] ?? '';
            $due_dt  = $query_disp['due_dt'] ?? '';
        } elseif (preg_match("/^(REV|FIN|PMC)/", $stage, $matches)) {
            $type = $matches[1];
            $rev_num = trim(str_replace($type, "", $stage));
            $revision = $revision_data[$a_id][$type][$rev_num] ?? null;
            if ($revision) {
                $recv_dt = $revision['received_date'] ?? '';
                $due_dt  = $revision['due_date'] ?? '';
            }
        } else {
            $recv_dt = $query_disp['created_dt'] ?? '';
            $due_dt  = $query_disp['due_dt'] ?? '';
        }

        // Format dates
        $recv_dt_fmt = $recv_dt ? date('d M Y', strtotime($recv_dt)) : '';
        $due_dt_fmt  = $due_dt  ? date('d M Y', strtotime($due_dt))  : '';
        $send_dt     = $row_history['sent_date'] ?? '';
        $send_dt_fmt = $send_dt ? date('d M Y', strtotime($send_dt)) : '';

       // Status calc
			$schedule = '';
			if ($send_dt && $due_dt) {

				$send_date_only = date('Y-m-d', strtotime($send_dt));
				$due_date_only  = date('Y-m-d', strtotime($due_dt));

				$late_dispatch = $query_disp['late_dispatch'] ?? 'no';

				if ($send_date_only == $due_date_only) {
					$schedule = "On Time";
				}
				elseif ($send_date_only > $due_date_only) {

					if($late_dispatch == 'yes'){
						$schedule = "Client Approved Delay";
					}else{
						$schedule = "Delay";
					}

				}
				else {
					$schedule = "Ahead";
				}
			}
			if (!empty($schedule) && isset($scheduleSummary[$schedule])) {
				$scheduleSummary[$schedule]++;
			}
			
			if(!empty($send_dt)){

			$date_key = date('Y-m-d', strtotime($send_dt));

			if(!isset($dateSchedule[$date_key])){
				$dateSchedule[$date_key] = [
					'Ahead' => 0,
					'On Time' => 0,
					'Delay' => 0,
					'Client Approved Delay' => 0
				];
			}

			if($schedule){
				$dateSchedule[$date_key][$schedule]++;
			}

		}
        ?>
        <tr>
            <td><?php echo $sno; ?>.</td>
            <td><?php echo htmlspecialchars($row_history['cust_name']); ?></td>
            <td><?php echo htmlspecialchars($row_history['j_code']); ?></td>
          <td>
    <?php
        $pub_id = $query_disp['pub_id'] ?? '';
        $source = $query_disp['source'] ?? ($row_history['source'] ?? '');

        echo '<span style="color:#333; font-weight:bold;">' . htmlspecialchars($pub_id) . '</span>';

        if ($source === 'Live') {
            echo ' <span style="color:green;">(Live)</span>';
        } elseif ($source === 'Archive') {
            echo ' <span style="color:#c00;">(Archive)</span>';
        }
    ?>
</td>


            <td><?php echo htmlspecialchars($stage); ?></td>
            <td data-order="<?php echo date('Y-m-d', strtotime($recv_dt_fmt)); ?>"><?php echo $recv_dt_fmt; ?></td>
            <td data-order="<?php echo date('Y-m-d', strtotime($due_dt_fmt)); ?>"><?php echo $due_dt_fmt; ?></td>
            <td data-order="<?php echo date('Y-m-d', strtotime($send_dt_fmt)); ?>"><?php echo $send_dt_fmt; ?></td>
            <td><?php echo getHoursMinutes($total_time_pre); ?></td>
			<td><?php echo getHoursMinutes($total_time_pag); ?></td>
			<td><?php echo getHoursMinutes($total_time_prqc); ?></td>
            <td><?php echo getHoursMinutes($total_time_prod); ?></td>
            <td><?php echo getHoursMinutes($total_time_all); ?></td>
			<td><?php echo $page_count; ?></td>
			<td>
			<?php
			if($schedule=="Ahead") 
				echo "<span style='color:#2196F3;font-weight:bold;'>Ahead</span>";
			elseif($schedule=="On Time") 
				echo "<span style='color:#4CAF50;font-weight:bold;'>On Time</span>";
			elseif($schedule=="Client Approved Delay") 
				echo "<span style='color:#FF9800;font-weight:bold;'>Client Approved Delay</span>";
			elseif($schedule=="Delay") 
				echo "<span style='color:#F44336;font-weight:bold;'>Delay</span>";
			?>
			</td>

			</tr>
        </tr>
        <?php
		
    }
ksort($dateSchedule);

$chartDates = [];
$chartAhead = [];
$chartOnTime = [];
$chartDelay = [];
$chartClientDelay = [];

foreach($dateSchedule as $date=>$val){

    $chartDates[] = date('d M',strtotime($date));
    $chartAhead[] = $val['Ahead'];
    $chartOnTime[] = $val['On Time'];
    $chartDelay[] = $val['Delay'];
	$chartClientDelay[] = $val['Client Approved Delay'];
}
?>

        </tbody>
    </table>
 </div>
</section>
<?php 
$totalSchedule = array_sum($scheduleSummary);

$schedulePercent = [
    'On Time' => $totalSchedule ? round(($scheduleSummary['On Time'] / $totalSchedule) * 100, 2) : 0,
    'Ahead'   => $totalSchedule ? round(($scheduleSummary['Ahead'] / $totalSchedule) * 100, 2) : 0,
    'Delay'   => $totalSchedule ? round(($scheduleSummary['Delay'] / $totalSchedule) * 100, 2) : 0,
	'Client Approved Delay' => $totalSchedule ? round(($scheduleSummary['Client Approved Delay'] / $totalSchedule) * 100, 2) : 0,
];

?>
		<section class="mu-panel mu-reveal">
<h3><i class="fa fa-pie-chart"></i> Schedule summary</h3>
<div class="mu-two">
<div class="mu-table-wrap" style="border:0;align-self:start;">
<table class="mu-table" style="min-width:0">
    <thead>
        <tr>
            <th>Status</th>
            <th>Count</th>
            <th>Percentage (%)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="mu-pill ok">On Time</span></td>
            <td><?= $scheduleSummary['On Time']; ?></td>
            <td><?= $schedulePercent['On Time']; ?>%</td>
        </tr>
        <tr>
            <td><span class="mu-pill warn">Ahead</span></td>
            <td><?= $scheduleSummary['Ahead']; ?></td>
            <td><?= $schedulePercent['Ahead']; ?>%</td>
        </tr>
        <tr>
            <td><span class="mu-pill bad">Delay</span></td>
            <td><?= $scheduleSummary['Delay']; ?></td>
            <td><?= $schedulePercent['Delay']; ?>%</td>
        </tr>
		<tr>
			<td><span class="mu-pill info">Client Approved Delay</span></td>
			<td><?= $scheduleSummary['Client Approved Delay']; ?></td>
			<td><?= $schedulePercent['Client Approved Delay']; ?>%</td>
		</tr>
        <tr class="mu-total">
            <td>Total</td>
            <td><?= $totalSchedule; ?></td>
            <td>100%</td>
        </tr>
    </tbody>
</table>
</div>
<div class="mu-chart" style="max-width:340px;margin:0 auto;width:100%;"><canvas id="scheduleChart"></canvas></div>
</div>
</section>
<section class="mu-panel mu-reveal"><h3><i class="fa fa-line-chart"></i> Dispatch by date</h3>
<div class="mu-chart"><canvas id="dateScheduleChart"></canvas></div></section>

												
			<div id="article_list"></div>
<?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-truck"></i>Choose the period and filters, then press <b>Get Result</b>.</div>
<?php } ?>
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
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
$(document).ready(function () {

    let table;
    let tableInitialized = false;

    $('#toggleTableBtn').click(function () {

        $('#tableWrapper').slideToggle();

        if ($('#tableWrapper').is(':visible')) {

            $(this).text('Hide Table');

            if (!tableInitialized) {

                table = $('#dispatchTable').DataTable({
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
                    columnDefs: [
                        {
                            targets: [5, 6, 7],
                            type: 'date'
                        }
                    ]
                });

                tableInitialized = true;

            } else {
                // ✅ THIS FIXES "No data available"
                table.columns.adjust().draw();
            }

        } else {
            $(this).text('Show Table');
        }

    });

});
</script>
<script>

var ctx = document.getElementById('dateScheduleChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [

            {
                label: 'Ahead',
                data: <?= json_encode($chartAhead) ?>,
                backgroundColor: '#2196F3',   // Blue
                stack: 'Stack 0'
            },

            {
                label: 'On Time',
                data: <?= json_encode($chartOnTime) ?>,
                backgroundColor: '#4CAF50',   // Green
                stack: 'Stack 0'
            },

            {
                label: 'Client Approved Delay',
                data: <?= json_encode($chartClientDelay) ?>,
                backgroundColor: '#FF9800',   // Orange
                stack: 'Stack 0'
            },

            {
                label: 'Delay',
                data: <?= json_encode($chartDelay) ?>,
                backgroundColor: '#F44336',   // Red
                stack: 'Stack 0'
            }

        ]
    },

    options: {
        responsive:true,
        plugins:{
            title:{
                display:true,
                text:'Article Dispatch Schedule (Date Wise)'
            }
        },
        scales:{
            x:{
                stacked:true
            },
            y:{
                stacked:true,
                beginAtZero:true
            }
        }
    }
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

 

<script>
const pieCtx = document.getElementById('scheduleChart').getContext('2d');

new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: [
            'Ahead (<?= $schedulePercent['Ahead']; ?>%)',
            'On Time (<?= $schedulePercent['On Time']; ?>%)',
            'Delay (<?= $schedulePercent['Delay']; ?>%)',
			'Client Approved Delay (<?= $schedulePercent['Client Approved Delay']; ?>%)'
        ],
        datasets: [{
            data: [
                <?= $scheduleSummary['Ahead']; ?>,
                <?= $scheduleSummary['On Time']; ?>,
                <?= $scheduleSummary['Delay']; ?>,
				<?= $scheduleSummary['Client Approved Delay']; ?>
            ],
            backgroundColor: [
                '#8BC34A',  // Ahead
                '#FFEB3B',  // OnTime
                '#F44336',  // Delay
				'#03A9F4'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 10 },
        plugins: {
            title: {
                display: true,
                text: 'Dispatch Schedule Distribution (%)'
            },
            tooltip: {
                callbacks: {
                    label: function(pieCtx) {
                        const total = pieCtx.dataset.data.reduce((a,b)=>a+b,0);
                        const val = pieCtx.raw;
                        return `${val} (${((val/total)*100).toFixed(2)}%)`;
                    }
                }
            }
        }
    }
});
</script>
<script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
  </body>
</html>

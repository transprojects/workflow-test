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


// Initialize filters
$search = $search2 = $ccc = "";
$selectedStage = $_REQUEST['stage'] ?? 'all';
if(isset($_REQUEST['wgetaction']))
{
    // Customer
    if (!empty($_REQUEST['cust_id']) && $_REQUEST['cust_id'] !== 'all') {
        $ccc .= " AND wd.cust_id = '{$_REQUEST['cust_id']}' ";
    }

    // Journal
    if (!empty($_REQUEST['j_id']) && $_REQUEST['j_id'] !== 'all') {
        $ccc .= " AND wd.j_id = '{$_REQUEST['j_id']}' ";
    }

	// Stage (LIKE filter)
	if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] !== 'all') {
    $stageFilter = mysqli_real_escape_string($db, $_REQUEST['stage']);
		$search2 .= " AND wd.stage LIKE '%$stageFilter%' ";
	}

    // Platform
    if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== 'all') {
        $ccc .= " AND j.j_platform = '{$_REQUEST['dp_platform']}' ";
    }

    // ------------------------------------
    // Month Filter (INSIDE)
    // ------------------------------------
  $from_input = $_REQUEST['from_month'] ?? '';
$to_input   = $_REQUEST['to_month'] ?? '';

// Default last 3 months
if (empty($from_input) && empty($to_input)) {
    $from_input = date('m-Y', strtotime('-2 months'));
    $to_input   = date('m-Y');
}

// Auto swap
if ($from_input > $to_input) {
    [$from_input, $to_input] = [$to_input, $from_input];
}

// Convert safely
list($from_m, $from_y) = explode('-', $from_input);
list($to_m, $to_y)     = explode('-', $to_input);

$from_date = "$from_y-$from_m-01";
$to_date   = date("Y-m-t", strtotime("$to_y-$to_m-01"));

// Apply filter
$search .= " AND wd.sent_date BETWEEN '$from_date' AND '$to_date' ";
	
	// FINAL CONDITIONS
$conditions = "$ccc $search $search2";
//echo $conditions; exit;
}


// ------------------------------------
// Month Handling (Single Logic)
// ------------------------------------
$from_input = $_REQUEST['from_month'] ?? '';
$to_input   = $_REQUEST['to_month'] ?? '';

// Default: Last 3 months
if (empty($from_input) && empty($to_input)) {
    $from_input = date('m-Y', strtotime('-2 months'));
    $to_input   = date('m-Y');
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
.mu-chart{position:relative;min-height:300px}
.mu-two{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px}
.mu-two .mu-panel{margin-bottom:0}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Delivery Performance Report - Monthly</h1>
            <p>Month-wise on time, ahead and delayed dispatches, live and archive</p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
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
                <select name="stage" id="stage" >
                  <option value="all">Select All</option>
<?php foreach($report_stage_types as $id=>$val){ ?>
                  <option value="<?= $id ?>" <?= (($_REQUEST['stage'] ?? '') == $id) ? 'selected' : '' ?>><?= $val ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:180px;"><label for="dp_platform">Platform</label>
                <select name="dp_platform" id="dp_platform" >
                  <option value="all">Select All</option>
<?php foreach ($platform_journal_array as $val) { ?>
                  <option value="<?= $val ?>" <?= (($_REQUEST["dp_platform"] ?? '') == $val) ? 'selected' : '' ?>><?= $val ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field"><label for="from_month">From month</label>
                <input type="text" class="monthpicker" name="from_month" id="from_month" value="<?= $from_input ?>" placeholder="MM-YYYY"></div>
              <div class="mu-field"><label for="to_month">To month</label>
                <input type="text" class="monthpicker" name="to_month" id="to_month" value="<?= $to_input ?>" placeholder="MM-YYYY"></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="dpmonthly" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>

        <!-- Table section -->
		<?php
		if(isset($_REQUEST['filter']))
		{
		?>
	             <div class="mu-results">
			
<?php

$scheduleSummary = [
    'On Time' => 0,
    'Ahead'   => 0,
    'Delay'   => 0,
    'Client Approved Delay' => 0
];

// ------------------------------------
// 1. Setup
// ------------------------------------
$databases = ['workflow', 'workflow_archive'];
$conditions = "$ccc $search $search2 $search4";
//echo $conditions; exit;
// ------------------------------------
// 2. Dispatch Query Builder
// ------------------------------------
function buildDispatchQuery($dbName, $conditions, $label) {

    $excludeArchive = '';
    if ($dbName === 'workflow') {
        $excludeArchive = "
            AND NOT EXISTS (
                SELECT 1 
                FROM workflow_archive.inw_dispatch_history wa
                WHERE wa.a_id = wd.a_id
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
// 3. Main Query
// ------------------------------------
$dispatch_sql_parts = [];

foreach ($databases as $dbName) {
    $label = ($dbName === 'workflow') ? 'Live' : 'Archive';
    $dispatch_sql_parts[] = buildDispatchQuery($dbName, $conditions, $label);
}

$query = implode(" UNION ALL ", $dispatch_sql_parts);
$articles_result = $db->query($query) or die($db->error);

// ------------------------------------
// 4. Process Data
// ------------------------------------
$dispatchData = [];
$article_ids  = [];

while ($row = $articles_result->fetch_assoc()) {
    $key = $row['a_id'] . '_' . $row['stage'];
    $dispatchData[$key] = $row;
    $article_ids[] = $row['a_id'];
}

// ------------------------------------
// 5. Counts
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
// 6. Safe ID List
// ------------------------------------
$id_array = array_unique($article_ids);

if (empty($id_array)) {
    $id_list = "0";
} else {
    $id_list = implode(",", array_map('intval', $id_array));
}

// ------------------------------------
// 7. UNION Helper
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
// 8. Inward Data
// ------------------------------------
$inward_data = [];

if (!empty($article_ids)) {
    $inward_sql = unionSelect('inw_inward_dtl', "id IN ($id_list)", $databases);
    $inward_result = $db->query($inward_sql) or die($db->error);

    while ($row = $inward_result->fetch_assoc()) {
        $inward_data[$row['id']] = $row;
    }
}

// ------------------------------------
// 9. Stage Filter
// ------------------------------------
$stage_filter = "";
if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] != 'all') {
    $stage = mysqli_real_escape_string($db, $_REQUEST['stage']);
    $stage_filter = " AND t.stage LIKE '%$stage%' ";
}

// ------------------------------------
// 10. Transaction Data
// ------------------------------------
$trans_time_data = [];

if (!empty($article_ids)) {

    $transaction_sql = "
        SELECT 
            t.project_id,

            SUM(TIME_TO_SEC(TIMEDIFF(t.end_time, t.start_time))) AS total_time_all,

            SUM(CASE WHEN t.dept IN (1,2,10,14,22,24)
                THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time))
                ELSE 0 END
            ) AS total_time_selected_depts,

            SUM(CASE WHEN t.dept = 10
                THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time)) ELSE 0 END
            ) AS total_time_dept10,

            SUM(CASE WHEN t.dept = 14
                THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time)) ELSE 0 END
            ) AS total_time_dept14,

            SUM(CASE WHEN t.dept IN (1,2,22,24)
                THEN TIME_TO_SEC(TIMEDIFF(t.end_time,t.start_time)) ELSE 0 END
            ) AS total_time_dept22

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

    while ($row = $trans_result->fetch_assoc()) {
        $trans_time_data[$row['project_id']] = $row;
    }
}

// ------------------------------------
// 11. Revision Data
// ------------------------------------
$revision_data = [];

if (!empty($article_ids)) {
    $revision_sql = unionSelect('inw_revisions_dtl', "inw_id IN ($id_list)", $databases);
    $revision_result = $db->query($revision_sql) or die($db->error);

    while ($row = $revision_result->fetch_assoc()) {
        $revision_data[$row['inw_id']][$row['type']][$row['revision_count']] = $row;
    }
}

?>

<!-- Summary Header -->
<div class="text-center mb-4">
    <h5>
        Total: <span class="text-dark"><?= $total_pages ?></span> |
        Live: <span class="text-success"><?= $live_count ?></span> |
        Archive: <span class="text-danger"><?= $archive_count ?></span>
    </h5>
</div>

	<?php
$stageLabels = [
    'FP' => 'First Proof',
    'REV' => 'Revises',
    'FIN' => 'Final',
    'PMC' => 'PMC'
];
$scheduleSummary = [
    'On Time' => 0,
    'Ahead' => 0,
    'Delay' => 0,
    'Client Approved Delay' => 0
];

$dateSchedule = [];

foreach ($dispatchData as $row_history) {

    $a_id = $row_history['a_id'];
    $query_disp = $inward_data[$a_id] ?? [];

    // -----------------------------
    // ✅ STAGE NORMALIZATION
    // -----------------------------
	   // -----------------------------
	// ✅ STAGE NORMALIZATION + FILTER
	// -----------------------------

	$stage_raw = $row_history['stage'];

	// Normalize stage (REV1 → REV, etc.)
	$stage = $stage_raw;

	if (strpos($stage, 'REV') === 0) $stage = 'REV';
	if (strpos($stage, 'FIN') === 0) $stage = 'FIN';
	if (strpos($stage, 'PMC') === 0) $stage = 'PMC';

	// Allowed stages
	$allowedStages = ['FP','REV','FIN','PMC'];

	// Skip unrelated stages
	if (!in_array($stage, $allowedStages)) continue;

	// Apply filter ONLY if specific stage selected
	if ($selectedStage !== 'all' && $stage !== $selectedStage) continue;

    // -----------------------------
    // ✅ DATE FETCH
    // -----------------------------
    $recv_dt = $due_dt = '';

    if ($stage == "FP") {

        $recv_dt = $query_disp['recv_dt'] ?? '';
        $due_dt  = $query_disp['due_dt'] ?? '';

    } elseif (preg_match("/^(REV|FIN|PMC)/", $stage, $matches)) {

        $type = $matches[1];
        $rev_num = trim(str_replace($type, "", $stage_raw));

        $revision = $revision_data[$a_id][$type][$rev_num] ?? null;

        if ($revision) {
            $recv_dt = $revision['received_date'] ?? '';
            $due_dt  = $revision['due_date'] ?? '';
        }
    }

    $send_dt = $row_history['sent_date'] ?? '';

    // -----------------------------
    // ✅ SCHEDULE CALCULATION
    // -----------------------------
    $schedule = '';

    if ($send_dt && $due_dt) {

        $send_date_only = date('Y-m-d', strtotime($send_dt));
        $due_date_only  = date('Y-m-d', strtotime($due_dt));

        $late_dispatch = $query_disp['late_dispatch'] ?? 'no';

        if ($send_date_only == $due_date_only) {
            $schedule = "On Time";
        } elseif ($send_date_only > $due_date_only) {

            $schedule = ($late_dispatch == 'yes') 
                ? "Client Approved Delay" 
                : "Delay";

        } else {
            $schedule = "Ahead";
        }
    }

    // -----------------------------
    // ✅ SUMMARY COUNT FIX (IMPORTANT)
    // -----------------------------
    if (!empty($schedule)) {

        if (!isset($scheduleSummary[$schedule])) {
            $scheduleSummary[$schedule] = 0;
        }

        $scheduleSummary[$schedule]++;
    }

    // -----------------------------
    // ✅ STAGE + MONTH AGGREGATION
    // -----------------------------
    if (!empty($schedule) && $send_dt) {

        $month_key = date('M Y', strtotime($send_dt));

        if (!isset($dateSchedule[$stage][$month_key])) {
            $dateSchedule[$stage][$month_key] = [
                'Ahead' => 0,
                'On Time' => 0,
                'Delay' => 0,
                'Client Approved Delay' => 0
            ];
        }

        $dateSchedule[$stage][$month_key][$schedule]++;
    }
}

# -----------------------------
# ✅ TOTAL + PERCENTAGE
# -----------------------------
$totalSchedule = array_sum($scheduleSummary);

$schedulePercent = [];

foreach ($scheduleSummary as $status => $count) {
    $schedulePercent[$status] = ($totalSchedule > 0) 
        ? round(($count / $totalSchedule) * 100, 2) 
        : 0;
}

# -----------------------------
# ✅ CHART PREP FUNCTION
# -----------------------------
function prepareChartData($stageData) {

    if(empty($stageData)){
        return [
            'labels'=>[],
            'ahead'=>[],
            'ontime'=>[],
            'delay'=>[],
            'client'=>[]
        ];
    }

    uksort($stageData, function($a, $b) {
        return strtotime($a) - strtotime($b);
    });

    $labels = $ahead = $ontime = $delay = $client = [];

    foreach ($stageData as $month => $val) {
        $labels[] = $month;
        $ahead[] = $val['Ahead'];
        $ontime[] = $val['On Time'];
        $delay[] = $val['Delay'];
        $client[] = $val['Client Approved Delay'];
    }

    return compact('labels','ahead','ontime','delay','client');
}

# -----------------------------
# ✅ STAGE DATA
# -----------------------------
$chartFP  = prepareChartData($dateSchedule['FP'] ?? []);
$chartREV = prepareChartData($dateSchedule['REV'] ?? []);
$chartFIN = prepareChartData($dateSchedule['FIN'] ?? []);
$chartPMC = prepareChartData($dateSchedule['PMC'] ?? []);

# -----------------------------
# ✅ OVERALL DATA
# -----------------------------
$overallSchedule = [];

foreach ($dateSchedule as $stage => $months) {
    foreach ($months as $month => $val) {

        if (!isset($overallSchedule[$month])) {
            $overallSchedule[$month] = [
                'Ahead' => 0,
                'On Time' => 0,
                'Delay' => 0,
                'Client Approved Delay' => 0
            ];
        }

        foreach ($val as $k => $v) {
            $overallSchedule[$month][$k] += $v;
        }
    }
}

$chartOVERALL = prepareChartData($overallSchedule);

# -----------------------------
# ✅ PERFORMANCE %
# -----------------------------
function calculatePerformance($data) {

    $performance = [];

    for ($i = 0; $i < count($data['labels']); $i++) {

        $total = $data['ahead'][$i] + $data['ontime'][$i] + $data['delay'][$i] + $data['client'][$i];

        $perc = ($total > 0) 
            ? round((($data['ahead'][$i] + $data['ontime'][$i]) / $total) * 100) 
            : 0;

        $performance[] = $perc;
    }

    return $performance;
}

$chartOVERALL['performance'] = calculatePerformance($chartOVERALL);

?>

<?php if($totalSchedule > 0) { ?>
<section class="mu-panel mu-reveal">
<h3><i class="fa fa-pie-chart"></i> Schedule summary
  <span class="mu-right"><button type="button" onclick="downloadExcelWithChart()" class="mu-btn"><i class="fa fa-file-excel-o"></i> Export Excel with Chart</button></span></h3>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table" style="max-width:560px;min-width:0">
    <thead>
        <tr>
            <th>Status</th>
            <th>Count</th>
            <th>Percentage (%)</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach($scheduleSummary as $status => $count): ?>

        <tr>
            <td><span class="mu-pill <?= $status=='On Time' ? 'ok' : ($status=='Ahead' ? 'warn' : ($status=='Delay' ? 'bad' : 'info')) ?>"><?= $status ?></span></td>

            <td><?= $count ?></td>
            <td><?= $schedulePercent[$status] ?>%</td>
        </tr>

        <?php endforeach; ?>

        <tr class="mu-total">
            <td>Total</td>
            <td><?= $totalSchedule ?></td>
            <td>100%</td>
        </tr>

    </tbody>
</table>
</div>
</section>

<form method="post" action="export_delivery_chart_excel.php" id="excelForm">
    <input type="hidden" name="chart_image" id="chart_image">
</form>
<div>

<?php if($selectedStage == 'all') { ?>

    <!-- OVERALL -->
    <section class="mu-panel mu-reveal"><h3><i class="fa fa-bar-chart"></i> Overall</h3><div class="mu-chart" style="min-height:350px;"><canvas id="chartOverall"></canvas></div></section>

    <!-- STAGE GRID -->
    <div class="mu-two">

        <?php
        $stages = ['FP','REV','FIN','PMC'];
        foreach($stages as $st){
        ?>

        <section class="mu-panel mu-reveal"><h3><i class="fa fa-bar-chart"></i> <?= $stageLabels[$st] ?? $st ?></h3><div class="mu-chart" style="min-height:300px;"><canvas id="chart<?= $st ?>"></canvas></div></section>

        <?php } ?>

    </div>

<?php } else { ?>

    <!-- SINGLE STAGE -->
    <section class="mu-panel mu-reveal"><h3><i class="fa fa-bar-chart"></i> <?= $stageLabels[$selectedStage] ?? $selectedStage ?></h3><div class="mu-chart" style="min-height:350px;"><canvas id="chart<?= $selectedStage ?>"></canvas></div></section>

<?php } ?>

</div>  

<?php } else { ?>

<div class="mu-empty mu-reveal"><i class="fa fa-inbox"></i>No data available for the selected filters.</div>

<?php } ?>
</div>
<?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-bar-chart"></i>Choose the months and filters, then press <b>Get Result</b>.</div>
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
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>	

<script>

if (typeof ChartDataLabels !== 'undefined') {
    Chart.register(ChartDataLabels);
}


function renderChart(canvasId, chartData) {

    if(!chartData || !chartData.labels || chartData.labels.length === 0){
        console.warn(canvasId + " → No data");
        return;
    }

    new Chart(document.getElementById(canvasId), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Ahead', data: chartData.ahead, backgroundColor: '#2196F3', stack:'s'},
                { label: 'On Time', data: chartData.ontime, backgroundColor: '#4CAF50', stack:'s'},
                { label: 'Client Approved Delay', data: chartData.client, backgroundColor: '#FF9800', stack:'s'},
                { label: 'Delay', data: chartData.delay, backgroundColor: '#F44336', stack:'s'}
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            }
        }
    });
}
function renderOverallChart(canvasId, chartData) {

    if(chartData.labels.length === 0) return;

    new Chart(document.getElementById(canvasId), {
        data: {
            labels: chartData.labels,
            datasets: [

                {
                    type: 'bar',
                    label: 'Ahead',
                    data: chartData.ahead,
                    backgroundColor: '#2196F3',
                    stack: 's'
                },
                {
                    type: 'bar',
                    label: 'On Time',
                    data: chartData.ontime,
                    backgroundColor: '#4CAF50',
                    stack: 's'
                },
                {
                    type: 'bar',
                    label: 'Client Approved Delay',
                    data: chartData.client,
                    backgroundColor: '#FF9800',
                    stack: 's'
                },
                {
                    type: 'bar',
                    label: 'Delay',
                    data: chartData.delay,
                    backgroundColor: '#F44336',
                    stack: 's'
                },

                // ✅ PERFORMANCE LINE
                {
					type: 'line',
					label: 'On Time %',
					data: chartData.performance,
					borderColor: '#000',
					yAxisID: 'y1',
					tension: 0.3,
					pointRadius: 5,

					datalabels: {
						align: 'top',
						anchor: 'end',
						formatter: function(value){
							return value + '%';
						}
					}
				}
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                title: {
                    display: true,
                    text: 'OVERALL Dispatch + On Time %'
                }
            },

            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true },

                y1: {
                    position: 'right',
                    beginAtZero: true,
                    max: 110,
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}
</script>


    <script>
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
   
       });
    </script>

<script>
$('.monthpicker').datepicker({
    dateFormat: 'mm-yy',
    changeMonth: true,
    changeYear: true,
    showButtonPanel: true,

    onClose: function(dateText, inst) {
        var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        $(this).datepicker('setDate', new Date(year, month, 1));
    },

    beforeShow: function(input, inst) {
        $('#ui-datepicker-div').addClass('month_year_datepicker');

        setTimeout(function(){
            $('.ui-datepicker-calendar').hide();
        }, 0);
    }
});
</script>

<script>
$('#validation-form').on('submit', function(){

    let from = $('#from_month').val();
    let to   = $('#to_month').val();

    if(from && to){
        let f = new Date(from.split('-')[1], from.split('-')[0]-1);
        let t = new Date(to.split('-')[1], to.split('-')[0]-1);

        if(f > t){
            // Auto swap visually also
            $('#from_month').val(to);
            $('#to_month').val(from);
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
function downloadExcelWithChart(){

    let canvas;
    let selectedStage = "<?= $selectedStage ?>";

    if(selectedStage === 'all'){
        canvas = document.getElementById('chartOverall');
    } else {
        canvas = document.getElementById('chart' + selectedStage);
    }

    if(!canvas){
        alert("Chart not available");
        return;
    }

    const imgData = canvas.toDataURL("image/png");

    document.getElementById('chart_image').value = imgData;

    document.getElementById('excelForm').submit();
}
</script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    let selectedStage = "<?= $selectedStage ?>";

    let stageData = {
        FP: <?= json_encode($chartFP) ?>,
        REV: <?= json_encode($chartREV) ?>,
        FIN: <?= json_encode($chartFIN) ?>,
        PMC: <?= json_encode($chartPMC) ?>
    };

    if(selectedStage === 'all'){

        renderOverallChart('chartOverall', <?= json_encode($chartOVERALL) ?>);

        renderChart('chartFP', stageData.FP);
        renderChart('chartREV', stageData.REV);
        renderChart('chartFIN', stageData.FIN);
        renderChart('chartPMC', stageData.PMC);

    } else {

        if(stageData[selectedStage]){
            renderChart('chart' + selectedStage, stageData[selectedStage]);
        }

    }

});
</script>
<script>
window.onload = function(){
    let loader = document.getElementById('loader');
    if(loader) loader.style.display = 'none';
}
</script>
<?php mu_foot(); ?>
</body>
</html>

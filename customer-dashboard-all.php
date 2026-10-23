<?php include "includes/includes.php";
session_start();
function differenceInHours($startdate, $enddate) {

    $difference = abs(strtotime($startdate) - strtotime($enddate));
    $hours = floor(($difference) / (60 * 60));
    $minuts = floor(($difference - $hours * 60 * 60) / 60);
    $seconds = floor(($difference - $hours * 60 * 60 - $minuts * 60));
    echo $hours . ":" . $minuts . ":" . $seconds;
}


// Date Range (Replace with POST input or default values)
// Get POST data

$cust_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 33;
$from_date = isset($_POST['from_date']) && !empty($_POST['from_date'])
    ? date('Y-m-d', strtotime($_POST['from_date']))
    : date('Y-m-01'); // First day of current month

$to_date = isset($_POST['to_date']) && !empty($_POST['to_date'])
    ? date('Y-m-d', strtotime($_POST['to_date']))
    : date('Y-m-d'); // Today's date



$cust_name = '';
if ($cust_id > 0) {
    $custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
    $custResult = $db->query($custQuery);
    if ($custResult && $custResult->num_rows > 0) {
        $custRow = $custResult->fetch_assoc();
        $cust_name = $custRow['cust_name'];
    }
}
// DB Connections
$dbPrimary = new mysqli('localhost', 'root', 'root@123', 'workflow');
$dbArchive = new mysqli('localhost', 'root', 'root@123', 'workflow_archive');

if ($dbPrimary->connect_error || $dbArchive->connect_error) {
    die("DB Connection failed: " . ($dbPrimary->connect_error ?: $dbArchive->connect_error));
}



// Inputs (adjust defaults or get from request)
$cust_id = isset($_REQUEST['cust_id']) ? intval($_REQUEST['cust_id']) : 33;
$from_date = isset($_REQUEST['from_date']) ? date('Y-m-d', strtotime($_REQUEST['from_date'])) : date('Y-m-01');
$to_date = isset($_REQUEST['to_date']) ? date('Y-m-d', strtotime($_REQUEST['to_date'])) : date('Y-m-d');

// Get customer name (optional)
$cust_name = '';
$custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
$custResult = $dbPrimary->query($custQuery);
if ($custResult && $custResult->num_rows > 0) {
    $custRow = $custResult->fetch_assoc();
    $cust_name = $custRow['cust_name'];
}

// Define delivery-related queries
function getDeliveryQueries($from, $to, $cust_id) {
    return [
        'first_proof' => "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    d.dept_name,
    i.created_dt,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver'
        ELSE '>72 hrs to deliver'
    END AS delivery_category
FROM inw_inward_dtl i
JOIN adm_journals j ON j.j_id = i.j_id
JOIN adm_workflow_master w ON w.wf_name = j.j_fp_wf
JOIN inw_transactions t ON t.project_id = i.id

-- Compute resolved dept only once in derived table
JOIN (
    SELECT w.wf_id, w.wf_code,
        (
          SELECT dept_id FROM (
              SELECT 
                  SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
              FROM (
                  SELECT a.N + b.N * 10 + 1 AS n
                  FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) b
              ) n
              WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
          ) AS depts
          WHERE dept_id != '15'
          ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
          LIMIT 1
        ) AS resolved_dept
    FROM adm_workflow_master w
) wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = wf_resolved.resolved_dept
  AND i.created_dt BETWEEN '$from' AND '$to'
  AND i.cust_id = '$cust_id'",

        'revised_proof' => "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    r.received_date,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN '<24 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN '24 to 48 hrs to deliver'
        ELSE '>48 hrs to deliver'
    END AS delivery_category,
    d.dept_name
FROM inw_revisions_dtl r
JOIN inw_inward_dtl i ON i.id = r.inw_id

JOIN adm_journals j ON j.j_id = i.j_id
JOIN adm_workflow_master w ON w.wf_name = j.j_rev_wf
JOIN inw_transactions t ON t.project_id = r.inw_id

-- Compute resolved department once
JOIN (
    SELECT w.wf_id, w.wf_code,
        (
          SELECT dept_id FROM (
              SELECT 
                  SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
              FROM (
                  SELECT a.N + b.N * 10 + 1 AS n
                  FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) b
              ) n
              WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
          ) AS depts
          WHERE dept_id != '15'
          ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
          LIMIT 1
        ) AS resolved_dept
    FROM adm_workflow_master w
) wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = wf_resolved.resolved_dept
  AND r.received_date BETWEEN '$from' AND '$to'
  AND i.cust_id = '$cust_id'"
    ];
}

// Fetch results with deduplication by ARTICLEID + current_stage (for first and revised proofs)
function fetchMergedResults($db1, $db2, $query, $limit = 2500) {
    $dataMap = [];

    $res1 = $db1->query($query . " LIMIT $limit");
    if (!$res1) {
        echo "Primary DB query error: " . $db1->error . "<br>";
    } else {
        while ($row = $res1->fetch_assoc()) {
            $key = ($row['ARTICLEID'] ?? '') . '_' . ($row['current_stage'] ?? '');
            if ($key) {
                $dataMap[$key] = $row;
            }
        }
    }

    $res2 = $db2->query($query . " LIMIT $limit");
    if (!$res2) {
        echo "Archive DB query error: " . $db2->error . "<br>";
    } else {
        while ($row = $res2->fetch_assoc()) {
            $key = ($row['ARTICLEID'] ?? '') . '_' . ($row['current_stage'] ?? '');
            if ($key && !isset($dataMap[$key])) {
                $dataMap[$key] = $row;
            }
        }
    }

    return array_values($dataMap);
}

// Fetch only from primary DB (for QMS feedback and CAPA)
function fetchSingleResult($db, $query, $limit = 2500) {
    $res = $db->query($query . " LIMIT $limit");
    if (!$res) {
        echo "Single DB query error: " . $db->error . "<br>";
        return [];
    }
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Summarize delivery categories
function summarize($data, $key) {
    $summary = ['total' => 0, 'lt_48' => 0, 'btw_48_72' => 0, 'gt_72' => 0];
    foreach ($data as $row) {
        $summary['total']++;
        if (strpos($row[$key], '<48') !== false || strpos($row[$key], '<24') !== false) {
            $summary['lt_48']++;
        } elseif (strpos($row[$key], '48 to 72') !== false || strpos($row[$key], '24 to 48') !== false) {
            $summary['btw_48_72']++;
        } elseif (strpos($row[$key], '>72') !== false || strpos($row[$key], '>48') !== false) {
            $summary['gt_72']++;
        }
    }
    return $summary;
}

// --- Main execution ---
$queries = getDeliveryQueries($from_date, $to_date, $cust_id);

// Fetch and merge first proof data from both DBs
$firstProofData = fetchMergedResults($dbPrimary, $dbArchive, $queries['first_proof']);

// Fetch and merge revised proof data from both DBs
$revisedProofData = fetchMergedResults($dbPrimary, $dbArchive, $queries['revised_proof']);

// Summarize delivery categories
/* $firstStats = summarize($firstProofData, 'delivery_category');
$revStats = summarize($revisedProofData, 'delivery_category'); */



// Fetch separately for workflow DB
$firstProofDataWorkflow = fetchSingleResult($dbPrimary, $queries['first_proof']);
$revisedProofDataWorkflow = fetchSingleResult($dbPrimary, $queries['revised_proof']);

// Fetch separately for archive DB
$firstProofDataArchive = fetchSingleResult($dbArchive, $queries['first_proof']);
$revisedProofDataArchive = fetchSingleResult($dbArchive, $queries['revised_proof']);


// Summarize each source separately
$firstStatsWorkflow = summarize($firstProofDataWorkflow, 'delivery_category');
$firstStatsWorkflowArchive = summarize($firstProofDataArchive, 'delivery_category');

$revStatsWorkflow = summarize($revisedProofDataWorkflow, 'delivery_category');
$revStatsWorkflowArchive = summarize($revisedProofDataArchive, 'delivery_category');



// If you still want combined stats
$firstStats = [
    'total' => $firstStatsWorkflow['total'] + $firstStatsWorkflowArchive['total'],
    'lt_48' => $firstStatsWorkflow['lt_48'] + $firstStatsWorkflowArchive['lt_48'],
    'btw_48_72' => $firstStatsWorkflow['btw_48_72'] + $firstStatsWorkflowArchive['btw_48_72'],
    'gt_72' => $firstStatsWorkflow['gt_72'] + $firstStatsWorkflowArchive['gt_72'],
];

$revStats = [
    'total' => $revStatsWorkflow['total'] + $revStatsWorkflowArchive['total'],
    'lt_48' => $revStatsWorkflow['lt_48'] + $revStatsWorkflowArchive['lt_48'],
    'btw_48_72' => $revStatsWorkflow['btw_48_72'] + $revStatsWorkflowArchive['btw_48_72'],
    'gt_72' => $revStatsWorkflow['gt_72'] + $revStatsWorkflowArchive['gt_72'],
];

// QMS (Feedback)
$qmsQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.message, f.created_on
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.created_on DESC
";
$qmsResult = $db->query($qmsQuery);

// CAPA (Action Taken)
$capaQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.ap_message, f.ap_dt
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.ap_message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.ap_dt) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.ap_dt DESC
";
$capaResult = $db->query($capaQuery);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <link href="styles.css" rel="stylesheet" />
    <link href="customer-dashboard.css" rel="stylesheet" />
    <!-- Data Table CSS- -->
<style>
    .proof-count, .revised-count {
        text-align: center;
        font-weight: bold;
        color: red;
        animation: blinker 1s linear infinite;
    }

    @keyframes blinker {
        50% { opacity: 0; }
    }
	/* Make all rows and cells white background */
#proof-dt tbody tr td,
#revised-dt tbody tr td,
#proof-dt thead tr th,
#revised-dt thead tr th {
    background-color: #ffffff !important;
    color: #000000;
}
</style>

  </head>
  <body>
      <div class="container-fluid">
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 


        <div class="container-fluid peerj">
            <div class="peerj-section1">
                <div class="peerj-section1-nav justify-content-between d-flex">
                    <div class="d-flex peerj-nav-right">
						<h4>Delivery Status <?php if (!empty($cust_name)) echo "- " . htmlspecialchars($cust_name); ?></h4>
                        <div class="d-flex delivery-items gap-3">
                            <p id="first-proof" class=" peerj1-clicked" onclick="handleClick(this)">First Proof</p>
                            <p id="revised" onclick="handleClick(this)">Revises</p>
                        </div>
                    </div>
<?php if (!empty($from_date) && !empty($to_date)): ?>
  <div style="text-align: center; font-weight: bold;margin-top:26px;margin-right: 20%;">
    Showing Data: 
    <span style="color: #2a9d8f;">
      <?php echo date('d-m-Y', strtotime($from_date)); ?>
    </span> 
    to 
    <span style="color: #2a9d8f;">
      <?php echo date('d-m-Y', strtotime($to_date)); ?>
    </span>
  </div>
<?php endif; ?>
<!-- Download Button 
<div class="col-auto d-flex align-items-center">
  <button type="button" class="btn btn-success" id="downloadBtn">Download Excel</button>
</div> -->
<div class="col-auto d-flex align-items-center">
  <button type="button" class="btn btn-success" id="downloadBtnall">Download Excel</button>
  <button type="button" class="btn btn-danger ms-2" id="downloadBtnpdf">Download PDF</button>
  <button type="button" class="btn btn-info ms-2" id="downloadBtnpdfall">Download Detailed PDF</button>
</div>

 <input type="hidden" name="sfrom_date" id="sfrom_date" value="<?php echo date('Y-m-d', strtotime($from_date)); ?>">
    <input type="hidden" name="sto_date" id="sto_date" value="<?php echo date('Y-m-d', strtotime($to_date)); ?>">
    <input type="hidden" name="scust_id" id="scust_id" value="<?php echo $cust_id;?>"> <!-- Update dynamically -->
	
	
	
                  <div class="peerj-nav-date">
  <p id="dateDisplay">Filter</p>
  <button id="openDatepicker" class="peerj-datebtn">
    <img src="assets/images/date-piceker-icon.svg" alt="">
  </button>

  <div id="dateRangeInputs" class="mt-3">
    <form method="POST" id="filterForm" action="customer-dashboard-all.php">
      <div class="row g-3 align-items-center">
        
        <!-- Customer Dropdown -->
     <div class="row g-3 perrj-date">
		<div class="col-auto">
          <label for="cust_id" class="col-form-label">Customer:</label>
        </div>
        <div class="col-auto">
          <select class="form-select" name="cust_id" id="cust_id" required>
            <option value="">Select Customer</option>
            <?php
            $sql_customers = $auth->showCustomerlist();
             foreach($sql_customers as $res_customers) {
        $selected = ($res_customers['id'] == $cust_id) ? 'selected' : '';
        echo "<option value='{$res_customers['id']}' $selected>{$res_customers['cust_name']}</option>";
      }
            ?>
          </select>
        </div>
	
  <!-- From Date -->
  <div class="col-auto d-flex align-items-center">
    <label for="fromDate" class="col-form-label me-2">From:</label>
    <input type="text" name="from_date" id="fromDate" class="form-control" placeholder="Start Date" autocomplete="off" value="<?php echo htmlspecialchars($from_date); ?>" required style="min-width: 150px;" />
  </div>

  <!-- To Date -->
  <div class="col-auto d-flex align-items-center">
    <label for="toDate" class="col-form-label me-2">To:</label>
    <input type="text" name="to_date" id="toDate" class="form-control" placeholder="End Date" autocomplete="off" value="<?php echo htmlspecialchars($to_date); ?>" required style="min-width: 150px;" />
  </div>

  <!-- Submit Button -->
  <div class="col-auto d-flex align-items-center">
    <button type="submit" class="btn btn-primary">Submit</button>
  </div>
</div>

      </div>
    </form>
  </div>
</div>

                </div>
<!-- HTML Rendering -->
<!-- First Proof Section -->
<div class="peerj-section1-cards no-wrap" id="firstProofSection">
   <div class="peerj-dash-card1 peerj-dash-card">
    <div class="d-flex gap-4 peerj-card-head">
        <img src="assets/images/peerj/icon1.svg" alt="icon">
        <p><?php echo htmlspecialchars($firstStatsWorkflow['total'] + $firstStatsWorkflowArchive['total']); ?></p>
    </div>
    <p>Total Firstproof</p>

    <!-- 👇 Separate section for workflow and archive breakdown -->
    <div style="margin-top: 10px; font-size: 13px; color: #555;">
        <p>• Workflow: <?php echo htmlspecialchars($firstStatsWorkflow['total']); ?></p>
        <p>• Archive: <?php echo htmlspecialchars($firstStatsWorkflowArchive['total']); ?></p>
    </div>
</div>

    <div class="peerj-dash-card2 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon2.svg" alt="icon">
            <p><?php echo htmlspecialchars($firstStats['lt_48']); ?></p>
        </div>
        <p>Within 48 h</p>
    </div>
    <div class="peerj-dash-card3 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon3.svg" alt="icon">
            <p><?php echo htmlspecialchars($firstStats['btw_48_72']); ?></p>
        </div>
        <p>48 - 72 h</p>
    </div>
    <div class="peerj-dash-card4 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon4.svg" alt="icon">
            <p><?php echo htmlspecialchars($firstStats['gt_72']); ?></p>
        </div>
        <p>Above 72 h</p>
    </div>
</div>

<!-- Revised Proof Section -->
<div class="peerj-section1-cards no-wrap" id="revisedSection" style="display:none">
    <div class="peerj-dash-card1 peerj-dash-card">
    <div class="d-flex gap-4 peerj-card-head">
        <img src="assets/images/peerj/icon1.svg" alt="icon">
        <p><?php echo htmlspecialchars($revStatsWorkflow['total'] + $revStatsWorkflowArchive['total']); ?></p>
    </div>
    <p>Total Revises</p>

    <!-- 👇 Separate section for workflow and archive breakdown -->
    <div style="margin-top: 10px; font-size: 13px; color: #555;">
        <p>• Workflow: <?php echo htmlspecialchars($revStatsWorkflow['total']); ?></p>
        <p>• Archive: <?php echo htmlspecialchars($revStatsWorkflowArchive['total']); ?></p>
    </div>
</div>
    <div class="peerj-dash-card2 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon2.svg" alt="icon">
            <p><?php echo htmlspecialchars($revStats['lt_48']); ?></p>
        </div>
        <p>Within 24 h</p>
    </div>
    <div class="peerj-dash-card3 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon3.svg" alt="icon">
            <p><?php echo htmlspecialchars($revStats['btw_48_72']); ?></p>
        </div>
        <p>24 - 48 h</p>
    </div>
    <div class="peerj-dash-card4 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon4.svg" alt="icon">
            <p><?php echo htmlspecialchars($revStats['gt_72']); ?></p>
        </div>
        <p>Above 48 h</p>
    </div>
</div>
            </div>
            <div class="peerj-section2">
                <div class="peerj-section2-left ">
				<div class="peerj-graph">
				<h4>Review Statistics</h4>
				<!--<canvas id="chart-container"></canvas>-->
				<div id="chart-container"></div>

					<!--<div class="peerj-graph-foot">
					<p><span style="background-color: rgba(54, 162, 235, 0.7);"></span>First Proof</p>
					<p><span style="background-color: rgba(255, 99, 132, 0.7);"></span>Revises</p>
					</div>-->
				</div>

                   <div class="peerj-happenings">
    <h4 class="peerj-common-head">Happenings</h4>
    <div class="peerj-happenings-content">
        <?php
        // Fetch happenings from the database
        $stmt = $db->prepare("SELECT h_title, h_cdate FROM wf_happenings ORDER BY h_cdate DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          //  while ($row = $result->fetch_assoc()) {
               // $formattedDate = date("d-m-Y", strtotime($row['h_cdate']));
               // echo '<p><span></span>' . htmlspecialchars($row['h_title']) . ' ON ' . $formattedDate . '</p>';
             //   echo '<p><span></span>' . htmlspecialchars($row['h_title']) . '</p>';
          //  }
			$colors = ['#3771F2', '#F25C37', '#37F29E', '#F2D237', '#8E37F2']; // define some colors

while ($row = $result->fetch_assoc()) {
    $randomColor = $colors[array_rand($colors)];
    echo '<p><span style="background-color:' . $randomColor . '; display:inline-block; width:14px; height:14px; border-radius:3px; margin-right:8px; vertical-align:middle;"></span>' . htmlspecialchars($row['h_title']) . '</p>';
}
        } else {
            echo '<p><span></span>No recent happenings.</p>';
        }

        $stmt->close();
        ?>
    </div>
</div>

                </div>
<div class="peerj-section2-right" id="rfirstproof" style="display: block;">
    <div class="peerj-table-nav">
        <div class="d-flex peerj-table-items gap-3">
            <p id="qms-tab" class="peerj1-clicked" onclick="showMainTab('qms')">QMS</p>
            <p id="capa-tab" onclick="showMainTab('capa')">CAPA</p>
        </div>

        <div class="peerj-table-wrapper">
            <!-- QMS TABLE -->
            <div class="peerj-table" id="qms-table">
                <table>
                    <colgroup>
                        <col width="10%" /><col width="10%" /><col width="20%" /><col width="10%" /><col width="30%" /><col width="20%" />
                    </colgroup>
                    <tr class="peerj-table-head">
                        <th>F.No</th>
                        <th>Jr Name</th>
                        <th>Article ID</th>
                        <th>Stage</th>
                        <th>Feedback</th>
                        <th>Date</th>
                    </tr>
                    <?php $i = 1; while($row = $qmsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo  $row['f_id'] ?></td>
                            <td><?php echo  htmlspecialchars($row['j_code']) ?></td>
                            <td><?php echo  htmlspecialchars($row['article']) ?></td>
                            <td><?php echo  htmlspecialchars($row['stage']) ?></td>
							<td><div class="feedback-content"><?php echo $row['message']; ?></div></td>
                            <td><?php echo  date('d-m-Y',strtotime($row['created_on'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- CAPA TABLE -->
            <div class="peerj-table" id="capa-table" style="display:none;">
                <table>
                    <colgroup>
                        <col width="10%" /><col width="10%" /><col width="20%" /><col width="10%" /><col width="30%" /><col width="20%" />
                    </colgroup>
                    <tr class="peerj-table-head">
                        <th>F.No</th>
                        <th>Jr Name</th>
                        <th>Article ID</th>
                        <th>Stage</th>
                        <th>Action Taken</th>
                        <th>Date</th>
                    </tr>
                    <?php $i = 1; while($row = $capaResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo  $row['f_id'] ?></td>
                            <td><?php echo  htmlspecialchars($row['j_code']) ?></td>
                            <td><?php echo  htmlspecialchars($row['article']) ?></td>
                            <td><?php echo  htmlspecialchars($row['stage']) ?></td>
							<td><div class="feedback-content"><?php echo $row['ap_message']; ?></div></td>
                            <td><?php echo  date('d-m-Y',strtotime($row['ap_dt'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
function fetchMergedResultsa($db1, $db2, $query) {
    $merged = [];

    $res1 = $db1->query($query);
    if ($res1) {
        while ($row = $res1->fetch_assoc()) {
            $merged[] = $row;
        }
    }

    $res2 = $db2->query($query);
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $merged[] = $row;
        }
    }

    return $merged;
}

$queries = [
    'first_proof' => "
        SELECT j.j_code, i.pub_id, d.sent_date
        FROM inw_dispatch_history d
        JOIN inw_inward_dtl i ON d.a_id = i.id
        JOIN adm_journals j ON i.j_id = j.j_id
        WHERE d.sent_date >= '$from_date' AND d.sent_date <= '$to_date'
		and i.created_dt BETWEEN '$from_date' AND '$to_date'
        AND d.stage='FP' AND i.cust_id = $cust_id group by a_id
    ",
    'revised_proof' => "
        SELECT j.j_code, i.pub_id, r.received_date
        FROM inw_revisions_dtl r
        JOIN inw_inward_dtl i ON r.inw_id = i.id
        JOIN adm_journals j ON i.j_id = j.j_id
        WHERE r.received_date >= '$from_date' AND r.received_date <= '$to_date'
        AND i.stage='REV1' AND i.cust_id = $cust_id group by inw_id
    "
];

$proofResult = fetchMergedResultsa($dbPrimary, $dbArchive, $queries['first_proof']);
$revisedResult = fetchMergedResultsa($dbPrimary, $dbArchive, $queries['revised_proof']);

$proofCount = count($proofResult);
$revisedCount = count($revisedResult);
?> 
<div class="peerj-section2-right" id="rrevisedproof" style="display: none;">
    <div class="peerj-table-nav">
        <div class="d-flex peerj-table-items gap-3">
            <p id="proof-tab" class="peerj1-clicked" onclick="showProofTab('proof')">Proof Sent</p>
            <p id="revised-tab" onclick="showProofTab('revised')">Revised Received</p>
        </div>

        <div class="peerj-table-wrapper">
            <!-- PROOF SENT TABLE -->
<div id="proof-table">
<div class="proof-count">Total Proof Sent: <?php echo $proofCount; ?></div>

	<div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
		<div class="container-fluid table-responsive" style="overflow: auto">
			<table id="proof-dt" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
				<thead>
				<tr>
				<th data-sortable="true" scope="col" class="thcolor" >#</th>
				<th data-sortable="true" scope="col" class="thcolor" >Journal Name</th>
				<th data-sortable="true" scope="col" class="thcolor" >Article ID</th>
				<th data-sortable="true" scope="col" class="thcolor" >Sent Date</th>
				</tr>
				</thead>
				<tbody>
				<?php $i = 1; foreach ($proofResult as $row): ?>
				<tr>
				<td><?php echo $i++; ?></td>
				<td><?php echo htmlspecialchars($row['j_code']); ?></td>
				<td><?php echo htmlspecialchars($row['pub_id']); ?></td>
				<td><?php echo htmlspecialchars($row['sent_date']); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

		</div> 
	</div>
</div>
            <!-- REVISED RECEIVED TABLE -->
<div id="revised-table" style="display:none;">
	<div class="revised-count">Total Revised Articles Received: <?php echo $revisedCount; ?></div>
		<div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
			<div class="container-fluid table-responsive" style="overflow: auto">
				<table id="revised-dt" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
					<thead>
					<tr>
					<th data-sortable="true" scope="col" class="thcolor" >#</th>
					<th data-sortable="true" scope="col" class="thcolor" >Journal Name</th>
					<th data-sortable="true" scope="col" class="thcolor" >Article ID</th>
					<th data-sortable="true" scope="col" class="thcolor" >Received Date</th>
					</tr>
					</thead>
					<tbody>
					<?php $i = 1; foreach ($revisedResult as $row): ?>
					<tr>
					<td><?php echo $i++; ?></td>
					<td><?php echo htmlspecialchars($row['j_code']); ?></td>
					<td><?php echo htmlspecialchars($row['pub_id']); ?></td>
					<td><?php echo htmlspecialchars($row['received_date']); ?></td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>


			</div> 
		</div>
</div>

</div>



            </div>
        </div>




    </div>
	<div id="excelLoader" style="display:none; position:fixed; top:50%; left:50%; 
    transform:translate(-50%, -50%); background:rgba(0,0,0,0.7); color:#fff; 
    padding:20px 40px; border-radius:8px; font-size:18px; z-index:9999; text-align:center;">
    <div class="spinner" style="margin-bottom:10px;">
        <div style="border: 4px solid #fff; border-top: 4px solid transparent; 
            border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
    </div>
    <div>In Progress...</div>
</div>

	<div id="pdfLoader" style="display:none; position:fixed; top:50%; left:50%; 
    transform:translate(-50%, -50%); background:rgba(0,0,0,0.7); color:#fff; 
    padding:20px 40px; border-radius:8px; font-size:18px; z-index:9999; text-align:center;">
    <div class="spinner" style="margin-bottom:10px;">
        <!-- Simple CSS spinner -->
        <div style="border: 4px solid #fff; border-top: 4px solid transparent; 
            border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
    </div>
    <div>In Progress...</div>
</div>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

		 <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
<script src="https://code.highcharts.com/highcharts.js"></script>

<script>
$(document).ready(function () {
    $('#proof-dt').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100, 150, 200, 500],
        dom: 'Bfltip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copy',
                    'csv',
                    'excel',
                    'pdf',
                    'print'
                ]
            }
        ],
        stripeClasses: [],
    });

    $('#revised-dt').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100, 150, 200, 500],
        dom: 'Bfltip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copy',
                    'csv',
                    'excel',
                    'pdf',
                    'print'
                ]
            }
        ],
        stripeClasses: [],
    });
});
</script>


<script>
  document.querySelectorAll('.peerj-happenings-content p span').forEach(span => {
    // Generate random color in hex
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    span.style.backgroundColor = randomColor;
    span.style.display = 'inline-block';
    span.style.width = '14px';
    span.style.height = '14px';
    span.style.borderRadius = '3px';
    span.style.marginRight = '8px';
    span.style.verticalAlign = 'middle';
  });
</script>

<script>
function handleClick(element) {
    document.querySelectorAll('.delivery-items p').forEach(p => p.classList.remove('peerj1-clicked'));
    element.classList.add('peerj1-clicked');

    const id = element.id;
    document.getElementById('firstProofSection').style.display = id === 'first-proof' ? 'grid' : 'none';
    document.getElementById('revisedSection').style.display = id === 'revised' ? 'grid' : 'none';
	document.getElementById('rfirstproof').style.display = id === 'first-proof' ? 'grid' : 'none';
    document.getElementById('rrevisedproof').style.display = id === 'revised' ? 'grid' : 'none';}
</script>

<script>
function showMainTab(tab) {
  // For QMS and CAPA tabs
  const tabs = ['qms', 'capa'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('peerj1-clicked');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('peerj1-clicked');
  document.getElementById(tab + '-table').style.display = 'block';
}

function showProofTab(tab) {
  // For Proof Sent / Revised Received tabs
  const tabs = ['proof', 'revised'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('peerj1-clicked');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('peerj1-clicked');
  document.getElementById(tab + '-table').style.display = 'block';
}

</script>


    <script>
        $(document).ready(function () {
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0];  // Format: yyyy-mm-dd

    // Set the default date on page load
  //  $('#dateDisplay').text(`${formattedToday}`).show();
    $('#dateDisplay').text(`Filter`).show();

    // Show the date inputs when button clicked
    $('#openDatepicker').click(function () {
        $('#dateRangeInputs').toggle();
    });

    // Initialize From Date picker
    $('#fromDate').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        endDate: today // Cannot select future dates
    }).on('changeDate', function (e) {
        let selectedFromDate = e.date;

        // The minimum date for To Date can now be the same as From Date
        $('#toDate').datepicker('setStartDate', selectedFromDate);
        $('#toDate').datepicker('setEndDate', today); // Cannot select beyond today

        // Reset To Date if it's outside the new range
        if ($('#toDate').val()) {
            let toDate = new Date($('#toDate').val());
            if (toDate < selectedFromDate || toDate > today) {
                $('#toDate').val('');
            }
        }

        checkDateSelection();
    });

    // Initialize To Date picker
    $('#toDate').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        startDate: today, // Initially set to today
        endDate: today     // Cannot select beyond today
    }).on('changeDate', function () {
        checkDateSelection();
    });

    function checkDateSelection() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (fromDate && toDate) {
            $('#dateRangeInputs').hide();
            if (fromDate === toDate) {
                $('#dateDisplay').text(`${toDate}`).show();
            } else {
                $('#dateDisplay').text(`${fromDate} to ${toDate}`).show();
            }
        }
    }
});
    </script>
    <script>
        const handleClick = (element) => {
            const paragraphs = document.querySelectorAll(".delivery-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
        const handleTableClick = (element) => {
            const paragraphs = document.querySelectorAll(".peerj-table-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
    </script>

    <!-- Review Statics graph -->

<script>
Highcharts.chart('chart-container', {
    chart: {
        type: 'column'
    },
    title: {
        text: null
    },
    xAxis: {
        categories: ['Ahead', 'On Time', 'Delay'],
        title: {
            text: 'Delivery Time',
            style: {
                fontSize: '16px',
                fontWeight: 'bold'
            }
        },
        labels: {
            style: {
                fontSize: '14px'
            }
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'Number of Proofs',
            style: {
                fontSize: '16px',
                fontWeight: 'bold'
            }
        },
        labels: {
            style: {
                fontSize: '14px'
            }
        }
    },
    legend: {
        align: 'center',
        verticalAlign: 'top',
        layout: 'horizontal',
        itemStyle: {
            fontSize: '14px',
            fontWeight: 'normal'
        }
    },
    plotOptions: {
        column: {
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    color: '#000000',
                    textOutline: 'none' // optional: makes label cleaner
                }
            }
        }
    },
    series: [{
        name: 'First Proof',
        color: 'rgba(54, 162, 235, 0.7)',
        data: [
            <?php echo  $firstStats['lt_48'] ?>,
            <?php echo  $firstStats['btw_48_72'] ?>,
            <?php echo  $firstStats['gt_72'] ?>
        ]
    }, {
        name: 'Revises',
        color: 'rgba(255, 99, 132, 0.7)',
        data: [
            <?php echo  $revStats['lt_48'] ?>,
            <?php echo  $revStats['btw_48_72'] ?>,
            <?php echo  $revStats['gt_72'] ?>
        ]
    }]
});

</script>


   
     <!-- Toogle show hide functionality -->
    <script>
      const showbtn = $(".showmorebtn");
      const report = $(".report");
      var state = "false";

      showbtn.on("click", function () {
        report[0].classList.toggle("hide");
        state = !state;
        if (state) {
          showbtn[0].innerHTML = "Show More";
        } else {
          showbtn[0].innerHTML = "Show Less";
        }
      });
    </script>
	
	
	
    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",

          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: date,
          onSelect: function (dateText, inst) {
            fromdate = dateText;
            console.log(fromdate);
          },
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          iconsLibrary: "materialicons",
          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: new Date(), //For disabling date from today
        });
      });
	  
	   // DataTable
                var table = $('#myTable1').DataTable({
                    searching: true,
                    pageLength:5,
					lengthMenu : [[5,10, 25, 50, 100, 100000], [5,10, 25, 50, 100, "All"]],  
				});
    </script>

  <script>
$("#downloadBtn").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "download-report.php?" + formData;
    window.location.href = downloadUrl; // triggers download
});
/* $("#downloadBtnall").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "download-report-all.php?" + formData;
    window.location.href = downloadUrl; // triggers download
}); */
/* $("#downloadBtnpdf").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "customer_dashboard_export_pdf.php?" + formData;
    window.location.href = downloadUrl; // triggers download
}); */

$("#downloadBtnpdf").on("click", function () {
	const svgElement = document.querySelector('#chart-container svg');

  if (svgElement) {
	  
	   // Show loader
        $("#pdfLoader").show();
		
  const serializer = new XMLSerializer();
  let svgString = serializer.serializeToString(svgElement);
  const base64Svg = window.btoa(unescape(encodeURIComponent(svgString)));

        const formData = $("#filterForm").serializeArray();
        const data = { chartBase64: base64Svg, chartType: 'svg' };

        formData.forEach(field => {
            data[field.name] = field.value;
        });

        fetch('customer_dashboard_export_pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('PDF generation failed.');
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'customer_dashboard_report.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            // Hide loader after process finished
            $("#pdfLoader").hide();
        });

    } else {
        alert('SVG chart not found.');
    }
});


$("#downloadBtnall").on("click", function () {
    // Show loader
    $("#excelLoader").show();

    const formData = $("#filterForm").serialize();

    fetch("download-report-all.php?" + formData, {
        method: 'GET',
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Excel download failed.');
        }
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;

        // You can set a default filename here, or get it from server headers if needed
        a.download = 'customer_dashboard_report.xlsx'; 

        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        // Hide loader after download or error
        $("#excelLoader").hide();
    });
});

$("#downloadBtnpdfall").on("click", function () {
	const svgElement = document.querySelector('#chart-container svg');

  if (svgElement) {
	  
	   // Show loader
        $("#pdfLoader").show();
		
  const serializer = new XMLSerializer();
  let svgString = serializer.serializeToString(svgElement);
  const base64Svg = window.btoa(unescape(encodeURIComponent(svgString)));

        const formData = $("#filterForm").serializeArray();
        const data = { chartBase64: base64Svg, chartType: 'svg' };

        formData.forEach(field => {
            data[field.name] = field.value;
        });

        fetch('customer_dashboard_detail_export_pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('PDF generation failed.');
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'customer_dashboard_report.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            // Hide loader after process finished
            $("#pdfLoader").hide();
        });

    } else {
        alert('SVG chart not found.');
    }
});

</script>


  </body>
</html>


</body>

</html>
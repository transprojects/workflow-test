<?php include_once "includes/includes.php";
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
// credentials come from includes/conn.php ($dbhost / $dbuser / $dbpass / $dbname); only the archive schema name is fixed
$dbPrimary = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$dbArchive = new mysqli($dbhost, $dbuser, $dbpass, 'workflow_archive');

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
/* wf_id -> department that completes the workflow (last department in wf_code other than 15).
   Built once per database in PHP; the old SQL re-derived it for every workflow with a 100-row
   number generator inside every delivery query. */
function resolvedDeptSql($db) {
    $parts = [];
    $r = $db->query("SELECT wf_id, wf_code FROM adm_workflow_master");
    while ($r && ($w = $r->fetch_assoc())) {
        $d = array_values(array_filter(array_map('trim', explode(',', (string)$w['wf_code'])), function ($x) { return $x !== '' && $x !== '15'; }));
        if (!$d) { continue; }
        $parts[] = "SELECT " . (int)$w['wf_id'] . " AS wf_id, '" . $db->real_escape_string(end($d)) . "' AS resolved_dept";
    }
    return $parts ? implode(' UNION ALL ', $parts) : "SELECT 0 AS wf_id, '' AS resolved_dept";
}

function getDeliveryQueries($from, $to, $cust_id, $resolved) {
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

JOIN (".$resolved.") wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = wf_resolved.resolved_dept
  AND t.end_time >= '$from' AND t.end_time < DATE_ADD('$to', INTERVAL 1 DAY)
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

JOIN (".$resolved.") wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = wf_resolved.resolved_dept
  AND r.received_date >= '$from' AND r.received_date < DATE_ADD('$to', INTERVAL 1 DAY)
  AND i.cust_id = '$cust_id'"
    ];
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

/* "x% of total" line + thin bar under a delivery card */
function peerj_share($n, $total) {
    $pct = $total > 0 ? round($n / $total * 100) : 0;
    return '<div class="peerj-share"><span>' . $pct . '% of total</span><i><b style="width:' . $pct . '%"></b></i></div>';
}

// --- Main execution ---
$queriesPrimary = getDeliveryQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbPrimary));
$queriesArchive = getDeliveryQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbArchive));

$firstProofDataWorkflow   = fetchSingleResult($dbPrimary, $queriesPrimary['first_proof']);
$revisedProofDataWorkflow = fetchSingleResult($dbPrimary, $queriesPrimary['revised_proof']);
$firstProofDataArchive    = fetchSingleResult($dbArchive, $queriesArchive['first_proof']);
$revisedProofDataArchive  = fetchSingleResult($dbArchive, $queriesArchive['revised_proof']);

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
<script id="chart-data" type="application/json">
<?php
echo json_encode([
    'firstStats' => $firstStats,
    'revStats' => $revStats
]);
?>
</script>
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
<?php
/* ---------- Fresh dashboard UI (dv-*) ---------- */
function dv_kpis($id, $label, $stats, $wf, $ar, $b, $hidden = false) {
    $pct = function ($n) use ($stats) { return $stats['total'] > 0 ? round($n / $stats['total'] * 100) : 0; };
    $tot = (int)$stats['total'];
    $wfN = (int)$wf['total']; $arN = (int)$ar['total'];
    $wfP = $tot > 0 ? round($wfN / $tot * 100) : 0;
    ob_start(); ?>
<section class="dv-kpis" id="<?php echo $id; ?>"<?php echo $hidden ? ' style="display:none"' : ''; ?>>
    <article class="dv-card dv-k dv-k-total">
        <span class="dv-k-label"><?php echo $label; ?></span>
        <b class="dv-k-num" data-count="<?php echo $tot; ?>"><?php echo $tot; ?></b>
        <div class="dv-split" title="Workflow vs archive">
            <i style="flex:<?php echo max(0.0001, $wfN); ?>"></i><u style="flex:<?php echo max(0.0001, $arN); ?>"></u>
        </div>
        <div class="dv-split-lbl">
            <span><em class="wf"></em>Workflow <strong><?php echo $wfN; ?></strong></span>
            <span><em class="ar"></em>Archive <strong><?php echo $arN; ?></strong></span>
        </div>
    </article>
<?php
    $tiles = [
        ['good', 'fa-bolt',        $b[0], $stats['lt_48']],
        ['warn', 'fa-hourglass-half', $b[1], $stats['btw_48_72']],
        ['bad',  'fa-exclamation-triangle', $b[2], $stats['gt_72']],
    ];
    foreach ($tiles as $t): $p = $pct($t[3]); ?>
    <article class="dv-card dv-k dv-k-<?php echo $t[0]; ?>">
        <span class="dv-ring" style="--p:<?php echo $p; ?>"><b><?php echo $p; ?>%</b></span>
        <div class="dv-k-body">
            <span class="dv-k-label"><i class="fa <?php echo $t[1]; ?>"></i><?php echo $t[2]; ?></span>
            <b class="dv-k-num" data-count="<?php echo (int)$t[3]; ?>"><?php echo (int)$t[3]; ?></b>
            <span class="dv-k-sub"><?php echo $p; ?>% of total</span>
        </div>
    </article>
<?php endforeach; ?>
</section>
<?php return ob_get_clean();
}

$dvQmsCount  = $qmsResult  ? (int)$qmsResult->num_rows  : 0;
$dvCapaCount = $capaResult ? (int)$capaResult->num_rows : 0;
?>
<div class="dv">

    <!-- Toolbar -->
    <header class="dv-bar">
        <div class="dv-title">
            <span class="dv-eyebrow">Delivery status</span>
            <h1><?php echo !empty($cust_name) ? htmlspecialchars($cust_name) : 'Select a customer'; ?></h1>
            <span class="dv-range"><i class="fa fa-calendar-o"></i>
                <?php echo date('d M Y', strtotime($from_date)); ?> &ndash; <?php echo date('d M Y', strtotime($to_date)); ?></span>
        </div>

        <div class="dv-seg delivery-items" role="tablist" aria-label="Proof type">
            <button type="button" id="first-proof" class="is-active" onclick="handleClick(this)">First Proof</button>
            <button type="button" id="revised" onclick="handleClick(this)">Revises</button>
        </div>

        <div class="dv-actions">
            <div class="dv-dl" role="group" aria-label="Downloads">
                <button type="button" class="dv-btn xl" id="downloadBtnall" title="Delivery, QMS, CAPA and Happenings in one workbook"><i class="fa fa-file-excel-o"></i><span>Excel</span></button>
                <button type="button" class="dv-btn pdf" id="downloadBtnpdf" title="Summary report with the chart"><i class="fa fa-file-pdf-o"></i><span>PDF</span></button>
                <button type="button" class="dv-btn pdf2" id="downloadBtnpdfall" title="Summary plus the proof-sent and revised lists"><i class="fa fa-file-text-o"></i><span>Detailed</span></button>
            </div>

            <div class="dv-filter">
                <button type="button" id="openDatepicker" class="dv-btn ghost"><i class="fa fa-sliders"></i><span id="dateDisplay">Filter</span></button>

                <div id="dateRangeInputs">
                    <form method="POST" id="filterForm" action="delivery-dashboard.php" class="dv-pop">
                        <h6>Filter report</h6>
                        <label for="cust_id">Customer</label>
                        <select class="form-select" name="cust_id" id="cust_id" required>
                            <option value="">Select Customer</option>
                            <?php
                            $sql_customers = $auth->showCustomerlist();
                            foreach ($sql_customers as $res_customers) {
                                $selected = ($res_customers['id'] == $cust_id) ? 'selected' : '';
                                echo "<option value='{$res_customers['id']}' $selected>" . htmlspecialchars($res_customers['cust_name']) . "</option>";
                            }
                            ?>
                        </select>
                        <label>Date range</label>
                        <div class="dv-presets">
                            <button type="button" data-p="today">Today</button>
                            <button type="button" data-p="yesterday">Yesterday</button>
                            <button type="button" data-p="7">Last 7 days</button>
                            <button type="button" data-p="30">Last 30 days</button>
                            <button type="button" data-p="month">This month</button>
                            <button type="button" data-p="lastmonth">Last month</button>
                        </div>
                        <div class="dv-sel" id="dvSelTxt"></div>
                        <div class="dvc" id="dvCal"></div>
                        <input type="hidden" name="from_date" id="fromDate" value="<?php echo htmlspecialchars($from_date); ?>" />
                        <input type="hidden" name="to_date" id="toDate" value="<?php echo htmlspecialchars($to_date); ?>" />
                        <button type="submit" id="dvApply" class="dv-btn go">Apply filter</button>
                    </form>
                </div>
            </div>
        </div>

        <input type="hidden" name="sfrom_date" id="sfrom_date" value="<?php echo date('Y-m-d', strtotime($from_date)); ?>">
        <input type="hidden" name="sto_date" id="sto_date" value="<?php echo date('Y-m-d', strtotime($to_date)); ?>">
        <input type="hidden" name="scust_id" id="scust_id" value="<?php echo $cust_id; ?>">
    </header>

    <!-- KPI cards -->
    <?php
    echo dv_kpis('firstProofSection', 'Total First Proofs', $firstStats, $firstStatsWorkflow, $firstStatsWorkflowArchive, ['Within 48 h', '48 &ndash; 72 h', 'Above 72 h']);
    echo dv_kpis('revisedSection', 'Total Revises', $revStats, $revStatsWorkflow, $revStatsWorkflowArchive, ['Within 24 h', '24 &ndash; 48 h', 'Above 48 h'], true);
    ?>

    <div class="dv-main">
        <!-- Chart -->
        <section class="dv-card dv-chart">
            <div class="dv-card-head">
                <h2>Review statistics</h2>
                <span class="dv-hint">Proofs by delivery time</span>
            </div>
            <div id="chart-container"></div>
        </section>

        <div class="dv-side">
            <!-- QMS / CAPA (First Proof view) -->
            <section class="dv-card dv-panel" id="rfirstproof">
                <div class="dv-card-head">
                    <div class="dv-tabs">
                        <button type="button" id="qms-tab" class="is-active" onclick="showMainTab('qms')">QMS <em><?php echo $dvQmsCount; ?></em></button>
                        <button type="button" id="capa-tab" onclick="showMainTab('capa')">CAPA <em><?php echo $dvCapaCount; ?></em></button>
                    </div>
                </div>
                <div class="dv-scroll">
                    <div id="qms-table" class="dv-feed">
                        <?php if ($dvQmsCount === 0): ?>
                            <div class="dv-empty"><i class="fa fa-comments-o"></i><b>No customer feedback</b><span>Nothing recorded for this customer in the selected dates.</span></div>
                        <?php else: while ($row = $qmsResult->fetch_assoc()): ?>
                            <article class="dv-fb">
                                <header>
                                    <span class="dv-chip"><?php echo htmlspecialchars($row['j_code']); ?></span>
                                    <span class="dv-fb-art"><?php echo htmlspecialchars($row['article']); ?></span>
                                    <span class="dv-chip soft"><?php echo htmlspecialchars($row['stage']); ?></span>
                                    <time><?php echo date('d M Y', strtotime($row['created_on'])); ?></time>
                                </header>
                                <div class="feedback-content"><?php echo $row['message']; ?></div>
                                <footer>F.No <?php echo (int)$row['f_id']; ?></footer>
                            </article>
                        <?php endwhile; endif; ?>
                    </div>

                    <div id="capa-table" class="dv-feed" style="display:none;">
                        <?php if ($dvCapaCount === 0): ?>
                            <div class="dv-empty"><i class="fa fa-check-square-o"></i><b>No actions taken</b><span>No corrective actions logged in the selected dates.</span></div>
                        <?php else: while ($row = $capaResult->fetch_assoc()): ?>
                            <article class="dv-fb">
                                <header>
                                    <span class="dv-chip"><?php echo htmlspecialchars($row['j_code']); ?></span>
                                    <span class="dv-fb-art"><?php echo htmlspecialchars($row['article']); ?></span>
                                    <span class="dv-chip soft"><?php echo htmlspecialchars($row['stage']); ?></span>
                                    <time><?php echo date('d M Y', strtotime($row['ap_dt'])); ?></time>
                                </header>
                                <div class="feedback-content"><?php echo $row['ap_message']; ?></div>
                                <footer>F.No <?php echo (int)$row['f_id']; ?></footer>
                            </article>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
            </section>

            <!-- Proof sent / revised received (Revises view) -->
            <section class="dv-card dv-panel" id="rrevisedproof" style="display:none;">
                <div class="dv-card-head">
                    <div class="dv-tabs">
                        <button type="button" id="proof-tab" class="is-active" onclick="showProofTab('proof')">Proof sent <em><?php echo $proofCount; ?></em></button>
                        <button type="button" id="revised-tab" onclick="showProofTab('revised')">Revised received <em><?php echo $revisedCount; ?></em></button>
                    </div>
                </div>
                <div class="dv-scroll dv-tablewrap">
                    <div id="proof-table">
                        <table id="proof-dt" class="table custtable dv-table">
                            <thead><tr><th>#</th><th>Journal</th><th>Article ID</th><th>Sent date</th></tr></thead>
                            <tbody>
                            <?php $i = 1; foreach ($proofResult as $row): ?>
                                <tr><td><?php echo $i++; ?></td><td><?php echo htmlspecialchars($row['j_code']); ?></td><td><?php echo htmlspecialchars($row['pub_id']); ?></td><td><?php echo htmlspecialchars($row['sent_date']); ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="revised-table" style="display:none;">
                        <table id="revised-dt" class="table custtable dv-table">
                            <thead><tr><th>#</th><th>Journal</th><th>Article ID</th><th>Received date</th></tr></thead>
                            <tbody>
                            <?php $i = 1; foreach ($revisedResult as $row): ?>
                                <tr><td><?php echo $i++; ?></td><td><?php echo htmlspecialchars($row['j_code']); ?></td><td><?php echo htmlspecialchars($row['pub_id']); ?></td><td><?php echo htmlspecialchars($row['received_date']); ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Happenings -->
    <section class="dv-card dv-happen">
        <div class="dv-card-head"><h2>Happenings</h2><span class="dv-hint">Latest from the team</span></div>
        <ul class="dv-timeline">
            <?php
            $stmt = $db->prepare("SELECT h_title, h_cdate FROM wf_happenings ORDER BY h_cdate DESC LIMIT 10");
            $stmt->execute();
            $hres = $stmt->get_result();
            $dots = ['#1b6ca8', '#F56500', '#1e9e61', '#8e37f2', '#e0a100'];
            if ($hres->num_rows > 0) {
                $n = 0;
                while ($h = $hres->fetch_assoc()) {
                    $c = $dots[$n++ % count($dots)];
                    echo '<li style="--c:' . $c . '"><span>' . htmlspecialchars($h['h_title']) . '</span>'
                        . (!empty($h['h_cdate']) ? '<time>' . date('d M Y', strtotime($h['h_cdate'])) . '</time>' : '') . '</li>';
                }
            } else {
                echo '<li class="none">No recent happenings.</li>';
            }
            $stmt->close();
            ?>
        </ul>
    </section>
</div>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

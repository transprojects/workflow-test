<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');
error_reporting(E_ALL);

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// DB Connections
$dbPrimary = new mysqli('localhost', 'root', 'root@123', 'workflow');
$dbArchive = new mysqli('localhost', 'root', 'root@123', 'workflow_archive');

if ($dbPrimary->connect_error || $dbArchive->connect_error) {
    die("DB Connection failed: " . ($dbPrimary->connect_error ?: $dbArchive->connect_error));
}

// Inputs
$cust_id = isset($_REQUEST['cust_id']) ? intval($_REQUEST['cust_id']) : 33;
$from_date = isset($_REQUEST['from_date']) ? date('Y-m-d', strtotime($_REQUEST['from_date'])) : date('Y-m-01');
$to_date = isset($_REQUEST['to_date']) ? date('Y-m-d', strtotime($_REQUEST['to_date'])) : date('Y-m-d');

// Get customer name
$cust_name = '';
$custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
$custResult = $dbPrimary->query($custQuery);
if ($custResult && $custResult->num_rows > 0) {
    $custRow = $custResult->fetch_assoc();
    $cust_name = $custRow['cust_name'];
}

// Delivery Queries
function getDeliveryQueries($from, $to, $cust_id) {
    return [
         'first_proof' => "SELECT i.pub_id AS ARTICLEID, t.current_stage AS current_stage, i.created_dt AS received_date, t.end_time AS delivered_date, " .
            "CASE WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver' " .
            "WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver' " .
            "ELSE '>72 hrs to deliver' END AS delivery_category " .
            "FROM inw_inward_dtl i JOIN adm_workflow_master w ON w.wf_id = i.wf_id " .
            "JOIN inw_transactions t ON t.project_id = i.id " .
            "WHERE t.current_status = 'Completed' AND t.current_stage = 'FP' " .
            "AND i.created_dt BETWEEN '$from' AND '$to' AND i.cust_id = '$cust_id'",

        'revised_proof' => "SELECT i.pub_id AS ARTICLEID, t.current_stage AS current_stage, r.received_date AS received_date, t.end_time AS delivered_date, " .
            "CASE WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN '<24 hrs to deliver' " .
            "WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN '24 to 48 hrs to deliver' " .
            "ELSE '>48 hrs to deliver' END AS delivery_category " .
            "FROM inw_revisions_dtl r JOIN inw_inward_dtl i ON i.id = r.inw_id " .
            "JOIN adm_workflow_master w ON w.wf_id = i.wf_id " .
            "JOIN inw_transactions t ON t.project_id = r.inw_id " .
            "WHERE t.current_status = 'Completed' AND t.current_stage = CONCAT('REV', r.revision_count) " .
            "AND r.received_date BETWEEN '$from' AND '$to' AND i.cust_id = '$cust_id'",

        'qms_feedback' => "SELECT f.f_id AS feedback_id, j.j_code AS journal_code, f.article AS article_id, f.stage AS stage, f.message AS feedback_message, f.created_on AS created_date " .
            "FROM inw_client_feedback f JOIN adm_journals j ON j.j_id = f.j_id " .
            "WHERE f.message IS NOT NULL AND f.cust_id = $cust_id " .
            "AND DATE(f.created_on) BETWEEN '$from' AND '$to' ORDER BY f.created_on DESC",

        'capa_action' => "SELECT f.f_id AS feedback_id, j.j_code AS journal_code, f.article AS article_id, f.stage AS stage, f.ap_message AS capa_action_message, f.created_on AS created_date " .
            "FROM inw_client_feedback f JOIN adm_journals j ON j.j_id = f.j_id " .
            "WHERE f.ap_message IS NOT NULL AND f.cust_id = $cust_id " .
            "AND DATE(f.created_on) BETWEEN '$from' AND '$to' ORDER BY f.created_on DESC"
    ];
}

// Fetch results from both DBs (for first & revised proof queries)
function fetchMergedResults($db1, $db2, $query, $limit = 500) {
    $res1 = $db1->query($query . " LIMIT $limit");
    $res2 = $db2->query($query . " LIMIT $limit");
    $dataMap = [];
    if ($res1) while ($row = $res1->fetch_assoc()) {
        $key = $row['ARTICLEID'] . '_' . $row['current_stage'] . '_' . $row['received_date'];
        $row['Source'] = 'Primary';
        $dataMap[$key] = $row;
    }
    if ($res2) while ($row = $res2->fetch_assoc()) {
        $key = $row['ARTICLEID'] . '_' . $row['current_stage'] . '_' . $row['received_date'];
        if (!isset($dataMap[$key])) {
            $row['Source'] = 'Archive';
            $dataMap[$key] = $row;
        }
    }
    return array_values($dataMap);
}

// Fetch results from primary DB (for feedback and capa)
function fetchSingleResult($db, $query, $limit = 500) {
    $res = $db->query($query . " LIMIT $limit");
    $data = [];
    if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
    return $data;
}

// Summarize delivery category counts
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

// Add simple sheets with rows, highlighting delivery >72 or >48 hrs
function addSheet($spreadsheet, $title, $rows, $default = false) {
    static $index = 1;
    if ($default) {
        $sheet = $spreadsheet->getSheet(0);
        $sheet->setTitle(substr($title, 0, 31));
    } else {
        $sheet = $spreadsheet->createSheet($index++);
        $sheet->setTitle(substr($title, 0, 31));
    }

    if (count($rows) > 0) {
        $headers = array_keys($rows[0]);
        $sheet->fromArray($headers, NULL, 'A1');
        $columnCount = count($headers);
        $range = 'A1:' . Coordinate::stringFromColumnIndex($columnCount) . '1';

        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), NULL, "A$rowIndex");
            if (isset($row['delivery_category']) && (strpos($row['delivery_category'], '>72') !== false || strpos($row['delivery_category'], '>48') !== false)) {
                $fillRange = 'A' . $rowIndex . ':' . Coordinate::stringFromColumnIndex($columnCount) . $rowIndex;
                $sheet->getStyle($fillRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
            }
            $rowIndex++;
        }
        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
        }
    }
}

// Add grouped sheets by delivery category with sanitized sheet titles (fix for invalid chars)
function addGroupedCategorySheets($spreadsheet, $titlePrefix, $groupedData) {
    foreach ($groupedData as $category => $rows) {
        if (count($rows) === 0) continue;  // Skip empty categories
        $unsafeTitle = "$titlePrefix - $category";
        // Replace invalid characters with dash
        $cleanTitle = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $unsafeTitle);
        $sheetTitle = substr($cleanTitle, 0, 31);

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetTitle);

        // Add headers and data
        $headers = array_keys($rows[0]);
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), NULL, "A$rowIndex");
            $rowIndex++;
        }

        // Auto size columns
        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }
}

// Group data by delivery_category for adding grouped sheets
function groupByCategory($data, $categoryKey) {
    $grouped = [];
    foreach ($data as $row) {
        $cat = $row[$categoryKey] ?? 'Uncategorized';
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = $row;
    }
    return $grouped;
}

// Happenings data
$dataHappenings = [];
$stmt = $dbPrimary->prepare("SELECT h_title, h_cdate FROM wf_happenings ORDER BY h_cdate DESC LIMIT 10");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dataHappenings[] = [
            'EVENT TITLE' => $row['h_title'],
            'EVENT DATE' => date('d-m-Y', strtotime($row['h_cdate']))
        ];
    }
    $stmt->close();
}

// Fetch data
$queries = getDeliveryQueries($from_date, $to_date, $cust_id);
$dataFirstProof = fetchMergedResults($dbPrimary, $dbArchive, $queries['first_proof']);
$dataRevisedProof = fetchMergedResults($dbPrimary, $dbArchive, $queries['revised_proof']);
$dataQMS = fetchSingleResult($dbPrimary, $queries['qms_feedback'], 25);
$dataCAPA = fetchSingleResult($dbPrimary, $queries['capa_action'], 25);

$summaryFirst = summarize($dataFirstProof, 'delivery_category');
$summaryRev = summarize($dataRevisedProof, 'delivery_category');

// Prepare spreadsheet
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("Workflow")
    ->setTitle("Delivery and Feedback Report");

// Write Summary data on the default active sheet (index 0)
$sheetSummary = $spreadsheet->getActiveSheet();
$sheetSummary->setTitle('Summary');

$sheetSummary->fromArray(['Metric', 'First Proof', 'Revised Proof'], NULL, 'A1');
$sheetSummary->fromArray([
    ['Total', $summaryFirst['total'], $summaryRev['total']],
    ['Within 48/24 hrs', $summaryFirst['lt_48'], $summaryRev['lt_48']],
    ['48–72 / 24–48 hrs', $summaryFirst['btw_48_72'], $summaryRev['btw_48_72']],
    ['Above 72/48 hrs', $summaryFirst['gt_72'], $summaryRev['gt_72']],
], NULL, 'A2');
$sheetSummary->getStyle('A1:C1')->getFont()->setBold(true);
foreach (range('A', 'C') as $col) {
    $sheetSummary->getColumnDimension($col)->setAutoSize(true);
}

// Add First Proof sheets grouped by delivery category
addGroupedCategorySheets($spreadsheet, 'First Proof', groupByCategory($dataFirstProof, 'delivery_category'));

// Add Revised Proof sheets grouped by delivery category
addGroupedCategorySheets($spreadsheet, 'Revised Proof', groupByCategory($dataRevisedProof, 'delivery_category'));

// Add QMS Feedback sheet
addSheet($spreadsheet, 'QMS Feedback', $dataQMS);

// Add CAPA Action sheet
addSheet($spreadsheet, 'CAPA Action', $dataCAPA);

// Add Happenings sheet
addSheet($spreadsheet, 'Happenings', $dataHappenings);

// Set active sheet back to summary
$spreadsheet->setActiveSheetIndex(0);

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Delivery_Feedback_Report_' . date('Ymd') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

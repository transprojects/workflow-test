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
         'first_proof' => "SELECT i.pub_id AS ARTICLEID, t.current_stage AS `CURRENT STAGE`,  i.created_dt AS `RECEIVED DATE`,  t.end_time AS `DELIVERED DATE`,  " .
            "CASE WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver' " .
            "WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver' " .
            "ELSE '>72 hrs to deliver' 	END AS `DELIVERY CATEGORY` 
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
            END AS 'DELIVERY CATEGORY'
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
  AND i.cust_id = '$cust_id'",

        'qms_feedback' => "SELECT 
			f.f_id AS `FEEDBACK ID`, 
			j.j_code AS `JOURNAL CODE`, 
			f.article AS `ARTICLE ID`, 
			f.stage AS `STAGE`, 
			f.message AS `FEEDBACK MESSAGE`, 
			f.created_on AS `CREATED DATE`  " .
            "FROM inw_client_feedback f JOIN adm_journals j ON j.j_id = f.j_id " .
            "WHERE f.message IS NOT NULL AND f.cust_id = $cust_id " .
            "AND DATE(f.created_on) BETWEEN '$from' AND '$to' ORDER BY f.created_on DESC",

        'capa_action' => "SELECT f.f_id AS `FEEDBACK ID`, 
			j.j_code AS `JOURNAL CODE`, 
			f.article AS `ARTICLE ID`, 
			f.stage AS `STAGE`, 
			f.ap_message AS `CAPA ACTION MESSAGE`, 
			f.created_on AS `CREATED DATE`  " .
            "FROM inw_client_feedback f JOIN adm_journals j ON j.j_id = f.j_id " .
            "WHERE f.ap_message IS NOT NULL AND f.cust_id = $cust_id " .
            "AND DATE(f.created_on) BETWEEN '$from' AND '$to' ORDER BY f.created_on DESC"
    ];
}

function fetchMergedResults($db1, $db2, $query, $limit = 500) {
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

function fetchSingleResult($db, $query, $limit = 500) {
    $res = $db->query($query . " LIMIT $limit");
    $data = [];
    if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
    return $data;
}

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
            if (isset($row['DELIVERY CATEGORY']) && (strpos($row['DELIVERY CATEGORY'], '>72') !== false || strpos($row['DELIVERY CATEGORY'], '>48') !== false)) {
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

// Happenings
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

$queries = getDeliveryQueries($from_date, $to_date, $cust_id);
$dataFirstProof = fetchMergedResults($dbPrimary, $dbArchive, $queries['first_proof']);
$dataRevisedProof = fetchMergedResults($dbPrimary, $dbArchive, $queries['revised_proof']);
$dataQMS = fetchSingleResult($dbPrimary, $queries['qms_feedback'], 25);
$dataCAPA = fetchSingleResult($dbPrimary, $queries['capa_action'], 25);

$summaryFirst = summarize($dataFirstProof, 'DELIVERY CATEGORY');
$summaryRev = summarize($dataRevisedProof, 'DELIVERY CATEGORY');

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
    ['Above 72/48 hrs', $summaryFirst['gt_72'], $summaryRev['gt_72']]
], NULL, 'A2');

// Style header row bold and background color
$sheetSummary->getStyle('A1:C1')->getFont()->setBold(true);
$sheetSummary->getStyle('A1:C1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('D9E1F2');

// Set column widths (widen first column for visibility)
$sheetSummary->getColumnDimension('A')->setWidth(30);
$sheetSummary->getColumnDimension('B')->setAutoSize(true);
$sheetSummary->getColumnDimension('C')->setAutoSize(true);

// Add other sheets

addSheet($spreadsheet, 'First Proof Delivery', $dataFirstProof);
addSheet($spreadsheet, 'Revised Proof Delivery', $dataRevisedProof);
addSheet($spreadsheet, 'QMS Feedback', $dataQMS);
addSheet($spreadsheet, 'CAPA Action', $dataCAPA);
addSheet($spreadsheet, 'Happenings', $dataHappenings);

// Set active sheet to summary (index 0)
$spreadsheet->setActiveSheetIndex(0);

// Output file name
$filename = "Delivery_Feedback_Report_" . date('Ymd') . "_{$cust_name}.xlsx";

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

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

// Include your DB connection and functions (make sure $db or $conn is your mysqli connection)
include "includes/includes.php";

// Inputs
$cust_id = isset($_REQUEST['cust_id']) ? intval($_REQUEST['cust_id']) : 33;
$from_date = isset($_REQUEST['from_date']) && !empty($_REQUEST['from_date']) ? date('Y-m-d', strtotime($_REQUEST['from_date'])) : date('Y-m-01');
$to_date = isset($_REQUEST['to_date']) && !empty($_REQUEST['to_date']) ? date('Y-m-d', strtotime($_REQUEST['to_date'])) : date('Y-m-d');

// Get Customer Name (optional)
$cust_name = '';
if ($cust_id > 0) {
    $custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
    $custResult = $db->query($custQuery);
    if ($custResult && $custResult->num_rows > 0) {
        $custRow = $custResult->fetch_assoc();
        $cust_name = $custRow['cust_name'];
    }
}

// -- Summary Queries --
// Use $db or $conn as per your mysqli connection variable
// First Proof Summary
$firstProofSummaryQuery = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN 'lt_48'
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN 'btw_48_72'
        ELSE 'gt_72'
    END AS delivery_category
FROM inw_inward_dtl i
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = i.id
WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = (
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
  )
  AND i.created_dt BETWEEN '$from_date' AND '$to_date'
  AND i.cust_id = '$cust_id'";


$resultFirstSummary = $db->query($firstProofSummaryQuery);

$firstStats = ['total' => 0, 'lt_48' => 0, 'btw_48_72' => 0, 'gt_72' => 0];
if ($resultFirstSummary) {
    while ($row = $resultFirstSummary->fetch_assoc()) {
        $firstStats['total']++;
        if (isset($firstStats[$row['delivery_category']])) {
            $firstStats[$row['delivery_category']]++;
        }
    }
}



// Revised Proof Summary
$revisedProofSummaryQuery = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN 'lt_48'
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN 'btw_48_72'
        ELSE 'gt_72'
    END AS delivery_category
FROM inw_revisions_dtl r
JOIN inw_inward_dtl i ON i.id = r.inw_id
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = r.inw_id
WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = (
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
  )
  AND r.received_date BETWEEN '$from_date' AND '$to_date'
  AND i.cust_id = '$cust_id'";


$resultRevSummary = $db->query($revisedProofSummaryQuery);

$revStats = ['total' => 0, 'lt_48' => 0, 'btw_48_72' => 0, 'gt_72' => 0];
if ($resultRevSummary) {
    while ($row = $resultRevSummary->fetch_assoc()) {
        $revStats['total']++;
        if (isset($revStats[$row['delivery_category']])) {
            $revStats[$row['delivery_category']]++;
        }
    }
}



// -- Detailed Queries --
// Your original detailed queries here (with the complex dept logic)
// First Proof Query
$firstProofQuery = "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    i.created_dt,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver'
        ELSE '>72 hrs to deliver'
    END AS delivery_category
FROM inw_inward_dtl i
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = i.id
JOIN adm_dept_master d ON d.dept_code = (
    SELECT dept_id FROM (
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
        FROM (
            SELECT a.N + b.N * 10 + 1 AS n
            FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                 (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        ) n
        WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
    ) AS depts
    WHERE dept_id != '15'
    ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
    LIMIT 1
)
WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = (
      SELECT dept_id FROM (
          SELECT 
              SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
          FROM (
              SELECT a.N + b.N * 10 + 1 AS n
              FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                   (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
          ) n
          WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
      ) AS depts
      WHERE dept_id != '15'
      ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
      LIMIT 1
  )
  AND i.created_dt BETWEEN '$from_date' AND '$to_date'
  AND i.cust_id = '$cust_id'";

// Revised Proof Query
$revisedProofQuery = "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    r.received_date,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN '<24 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN '24 to 48 hrs to deliver'
        ELSE '>48 hrs to deliver'
    END AS delivery_category
FROM inw_revisions_dtl r
JOIN inw_inward_dtl i ON i.id = r.inw_id
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = r.inw_id
JOIN adm_dept_master d ON d.dept_code = (
    SELECT dept_id FROM (
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
        FROM (
            SELECT a.N + b.N * 10 + 1 AS n
            FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                 (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        ) n
        WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
    ) AS depts
    WHERE dept_id != '15'
    ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
    LIMIT 1
)
WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = (
      SELECT dept_id FROM (
          SELECT 
              SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
          FROM (
              SELECT a.N + b.N * 10 + 1 AS n
              FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                   (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
          ) n
          WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
      ) AS depts
      WHERE dept_id != '15'
      ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
      LIMIT 1
  )
  AND r.received_date BETWEEN '$from_date' AND '$to_date'
  AND i.cust_id = '$cust_id'";

// QMS Feedback Query
$qmsQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.message, f.created_on
             FROM inw_client_feedback f
             JOIN adm_journals j ON j.j_id = f.j_id
             WHERE f.message IS NOT NULL AND f.cust_id = $cust_id 
               AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
             ORDER BY f.created_on DESC";

// CAPA Action Query
$capaQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.ap_message, f.created_on
              FROM inw_client_feedback f
              JOIN adm_journals j ON j.j_id = f.j_id
              WHERE f.ap_message IS NOT NULL AND f.cust_id = $cust_id 
                AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
              ORDER BY f.created_on DESC";

// Run detailed queries
$fpResult = $db->query($firstProofQuery . " LIMIT 500");
$revResult = $db->query($revisedProofQuery . " LIMIT 500");
$qmsResult = $db->query($qmsQuery . " LIMIT 25");
$capaResult = $db->query($capaQuery . " LIMIT 25");

// Create Excel and add sheets
$spreadsheet = new Spreadsheet();

// Add summary sheet first
$sheetSummary = $spreadsheet->getActiveSheet();
$sheetSummary->setTitle('Summary');

// Headers for summary
$sheetSummary->fromArray(['Metric', 'First Proof', 'Revised Proof'], NULL, 'A1');

$summaryData = [
    ['Total', $firstStats['total'], $revStats['total']],
    ['Within 48 hrs / 24 hrs', $firstStats['lt_48'], $revStats['lt_48']],
    ['48 - 72 hrs / 24 - 48 hrs', $firstStats['btw_48_72'], $revStats['btw_48_72']],
    ['Above 72 hrs / 48 hrs', $firstStats['gt_72'], $revStats['gt_72']],
];

$sheetSummary->fromArray($summaryData, NULL, 'A2');

// Helper function to add detailed query results as sheets
function addSheet($spreadsheet, $title, $result) {
    static $sheetIndex = 1; // Start from 1 because 0 is summary
    $sheet = $spreadsheet->createSheet($sheetIndex);
    $sheet->setTitle(substr($title, 0, 31));
    if ($result && $result->num_rows > 0) {
        // Fetch fields names as headers
        $fields = array_keys($result->fetch_assoc());
        $result->data_seek(0);
        foreach ($fields as $col => $field) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $field);
        }
        $rowNum = 2;
        while ($row = $result->fetch_assoc()) {
            foreach (array_values($row) as $col => $val) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowNum, $val);
            }
            $rowNum++;
        }
    }
    $sheetIndex++;
}

addSheet($spreadsheet, 'First Proof', $fpResult);
addSheet($spreadsheet, 'Revised Proof', $revResult);
addSheet($spreadsheet, 'QMS Feedback', $qmsResult);
addSheet($spreadsheet, 'CAPA Action', $capaResult);

// Clear output buffer before sending file
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Delivery_Report.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Save to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

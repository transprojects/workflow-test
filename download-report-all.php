<?php
require 'vendor/autoload.php';
require_once 'includes/conn.php';   // DB credentials ($dbhost, $dbuser, $dbpass, $dbname)
// downloads are only for logged-in users (same cookie the rest of the app uses)
if (!isset($_COOKIE['user_id'])) { http_response_code(401); exit('Please log in.'); }
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// DB connections
// credentials come from includes/conn.php; only the archive schema name is fixed
$dbPrimary = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$dbArchive = new mysqli($dbhost, $dbuser, $dbpass, 'workflow_archive');
if ($dbPrimary->connect_error || $dbArchive->connect_error) {
    die("DB connection failed");
}

// Inputs
$cust_id = intval($_REQUEST['cust_id'] ?? 33);
// dates are parsed and re-formatted, so nothing the browser sends reaches the SQL as text
$from_ts = !empty($_REQUEST['from_date']) ? strtotime($_REQUEST['from_date']) : false;
$to_ts   = !empty($_REQUEST['to_date'])   ? strtotime($_REQUEST['to_date'])   : false;
$from_date = date('Y-m-d', $from_ts ? $from_ts : strtotime(date('Y-m-01')));
$to_date   = date('Y-m-d', $to_ts ? $to_ts : time());

$cust_name = '';
$cr = $dbPrimary->query("SELECT cust_name FROM adm_customer_master WHERE id = " . $cust_id);
if ($cr && ($crow = $cr->fetch_assoc())) { $cust_name = $crow['cust_name']; }

// Your queries function (replace placeholders with actual queries)
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

function getQueries($from, $to, $cust_id, $resolved) {
    return [
       'first_proof' => "SELECT i.pub_id AS ARTICLEID, t.current_stage AS `CURRENT STAGE`,  i.created_dt AS `RECEIVED DATE`,  t.end_time AS `DELIVERED DATE`, t.pages_completed as `PAGE COUNT`, " .
            "CASE WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver' " .
            "WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver' " .
            "ELSE '>72 hrs to deliver' 	END AS `DELIVERY CATEGORY` 
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
  AND i.cust_id = '$cust_id' ORDER BY i.created_dt asc",

    'revised_proof' => "SELECT 
            i.pub_id AS `ARTICLEID`,
            t.current_stage AS `CURRENT STAGE`,
            r.received_date AS `RECEIVED DATE`,
            t.end_time AS `DELIVERED DATE`,
			t.pages_completed AS `PAGE COUNT`,
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

JOIN (".$resolved.") wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = wf_resolved.resolved_dept
  AND r.received_date >= '$from' AND r.received_date < DATE_ADD('$to', INTERVAL 1 DAY)
  AND i.cust_id = '$cust_id' ORDER BY r.received_date asc",

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
			f.ap_dt AS `ACTION DATE`  " .
            "FROM inw_client_feedback f JOIN adm_journals j ON j.j_id = f.j_id " .
            "WHERE f.ap_message IS NOT NULL AND f.cust_id = $cust_id " .
            "AND DATE(f.ap_dt) BETWEEN '$from' AND '$to' ORDER BY f.ap_dt DESC"
    ];
}

// Fetch multiple rows function
function fetchAll($db, $query) {
    $result = $db->query($query);
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Summarize delivery category counts (optional, for first & revised proofs)
function summarize($data, $key) {
    $summary = ['total'=>0,'lt_48'=>0,'btw_48_72'=>0,'gt_72'=>0];
    foreach ($data as $row) {
        $summary['total']++;
        $val = $row[$key] ?? '';
        if (strpos($val, '<48') !== false || strpos($val, '<24') !== false) {
            $summary['lt_48']++;
        } elseif (strpos($val, '48 to 72') !== false || strpos($val, '24 to 48') !== false) {
            $summary['btw_48_72']++;
        } elseif (strpos($val, '>72') !== false || strpos($val, '>48') !== false) {
            $summary['gt_72']++;
        }
    }
    return $summary;
}

// Add data sheet helper
function addSheetWithDataAndSummary($spreadsheet, $title, $dataWorkflow, $dataArchive, $summaryWorkflow, $summaryArchive, $combinedSummary, $deliveryKey = null) {
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle(substr($title, 0, 31));

    // Combine data from both sources
    $allData = array_merge($dataWorkflow, $dataArchive);

    if (count($allData) > 0) {
        $headers = array_keys($allData[0]);
        $sheet->fromArray($headers, null, 'A1');

        // Style header row
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:$lastCol" . "1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Write data rows
        $rowNum = 2;
        foreach ($allData as $row) {
            $sheet->fromArray(array_values($row), null, "A$rowNum", true);   // true: keep 0 values (loose null compare would blank them)

            $cellRange = "A$rowNum:" . Coordinate::stringFromColumnIndex(count($headers)) . $rowNum;

            // Style data row
            $sheet->getStyle($cellRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            // Conditional highlight for late deliveries
            if ($deliveryKey !== null && isset($row[$deliveryKey]) &&
                (stripos($row[$deliveryKey], '>72') !== false || stripos($row[$deliveryKey], '>48') !== false)) {
                $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE'); // light red
            }

            $rowNum++;
        }

        // Auto size columns
        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
    } else {
        $sheet->setCellValue('A1', 'No data available');
    }

    // Add summary section
    if ($deliveryKey !== null && $summaryWorkflow && $summaryArchive && $combinedSummary) {
        $startSummaryRow = count($allData) + 4;
        $sheet->setCellValue("A$startSummaryRow", 'Summary');
        $sheet->getStyle("A$startSummaryRow")->getFont()->setBold(true);

        // Summary table headers
        $sheet->fromArray(['Category', 'Workflow DB', 'Archive DB', 'Combined'], null, "A" . ($startSummaryRow + 1));
        $sheet->getStyle("A" . ($startSummaryRow + 1) . ":D" . ($startSummaryRow + 1))->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $labels = ['Total', '<48 hrs (or <24 hrs)', '48–72 hrs (or 24–48 hrs)', '>72 hrs (or >48 hrs)'];
        $keys = ['total', 'lt_48', 'btw_48_72', 'gt_72'];

        for ($i = 0; $i < count($keys); $i++) {
            $row = $startSummaryRow + 2 + $i;
            $sheet->fromArray([
                $labels[$i],
                $summaryWorkflow[$keys[$i]] ?? 0,
                $summaryArchive[$keys[$i]] ?? 0,
                $combinedSummary[$keys[$i]] ?? 0
            ], null, "A$row", true);

            $sheet->getStyle("A$row:D$row")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }
    }
}





function addSummarySheet($spreadsheet, $firstStats, $revisedStats) {
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Summary');

    // Headers
    $headers = ['Proof Type', 'Total', '<48 hrs (or <24 hrs)', '48-72 hrs (or 24-48 hrs)', '>72 hrs (or >48 hrs)'];
    $sheet->fromArray($headers, null, 'A1');

    // Style header row
    $sheet->getStyle('A1:E1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // Data rows
    $data = [
        ['First Proof', $firstStats['total'], $firstStats['lt_48'], $firstStats['btw_48_72'], $firstStats['gt_72']],
        ['Revised Proof', $revisedStats['total'], $revisedStats['lt_48'], $revisedStats['btw_48_72'], $revisedStats['gt_72']]
    ];

    $sheet->fromArray($data, null, 'A2', true);

    // Style data rows
    foreach ([2, 3] as $row) {
        $sheet->getStyle("A$row:E$row")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
    }

    // Auto size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
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



// Main logic
$queries        = getQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbPrimary));
$queriesArchive = getQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbArchive));

// Fetch data for all types from workflow and archive DBs
$firstProofDataWorkflow = fetchAll($dbPrimary, $queries['first_proof']);
$revisedProofDataWorkflow = fetchAll($dbPrimary, $queries['revised_proof']);
$qmsDataWorkflow = fetchAll($dbPrimary, $queries['qms_feedback']);
$capaDataWorkflow = fetchAll($dbPrimary, $queries['capa_action']);
$happeningsDataWorkflow =$dataHappenings;

$firstProofDataArchive = fetchAll($dbArchive, $queriesArchive['first_proof']);
$revisedProofDataArchive = fetchAll($dbArchive, $queriesArchive['revised_proof']);
$capaDataArchive = []; // Archive not applicable
$qmsDataArchive = [];  // Archive not applicable
$happeningsDataArchive = []; // Archive not applicable


// Summarize proofs delivery categories
$firstStatsWorkflow = summarize($firstProofDataWorkflow, 'DELIVERY CATEGORY');
$firstStatsArchive = summarize($firstProofDataArchive, 'DELIVERY CATEGORY');
$revisedStatsWorkflow = summarize($revisedProofDataWorkflow, 'DELIVERY CATEGORY');
$revisedStatsArchive = summarize($revisedProofDataArchive, 'DELIVERY CATEGORY');

$firstStatsCombined = [
    'total' => $firstStatsWorkflow['total'] + $firstStatsArchive['total'],
    'lt_48' => $firstStatsWorkflow['lt_48'] + $firstStatsArchive['lt_48'],
    'btw_48_72' => $firstStatsWorkflow['btw_48_72'] + $firstStatsArchive['btw_48_72'],
    'gt_72' => $firstStatsWorkflow['gt_72'] + $firstStatsArchive['gt_72'],
];
$revisedStatsCombined = [
    'total' => $revisedStatsWorkflow['total'] + $revisedStatsArchive['total'],
    'lt_48' => $revisedStatsWorkflow['lt_48'] + $revisedStatsArchive['lt_48'],
    'btw_48_72' => $revisedStatsWorkflow['btw_48_72'] + $revisedStatsArchive['btw_48_72'],
    'gt_72' => $revisedStatsWorkflow['gt_72'] + $revisedStatsArchive['gt_72'],
];

// Create spreadsheet and remove default sheet
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

addSummarySheet($spreadsheet, $firstStatsCombined, $revisedStatsCombined);

// Add sheets
addSheetWithDataAndSummary($spreadsheet, 'First Proof Delivery',
    $firstProofDataWorkflow, $firstProofDataArchive,
    $firstStatsWorkflow, $firstStatsArchive, $firstStatsCombined, 'DELIVERY CATEGORY');

addSheetWithDataAndSummary($spreadsheet, 'Revised Proof Delivery',
    $revisedProofDataWorkflow, $revisedProofDataArchive,
    $revisedStatsWorkflow, $revisedStatsArchive, $revisedStatsCombined, 'DELIVERY CATEGORY');

// QMS Sheet (no delivery summary)
addSheetWithDataAndSummary($spreadsheet, 'QMS Feedback',
    $qmsDataWorkflow, $qmsDataArchive, null, null, null, null);

// CAPA Sheet (no delivery summary)
addSheetWithDataAndSummary($spreadsheet, 'CAPA Action',
    $capaDataWorkflow, $capaDataArchive, null, null, null, null);

// Happenings Sheet (no delivery summary)
addSheetWithDataAndSummary($spreadsheet, 'Happenings',
    $happeningsDataWorkflow, $happeningsDataArchive, null, null, null, null);

// Set active sheet to summary (index 0)
$spreadsheet->setActiveSheetIndex(0);

// Output file name
$safe_name = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $cust_name), '_');   // no quotes / slashes in the download name
$filename = "Delivery_Feedback_Report_" . date('Ymd') . ($safe_name !== '' ? "_{$safe_name}" : '') . ".xlsx";

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();

<?php 
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

include('dbc.php'); 

$dept_id = isset($_GET['dep_id']) ? (int)$_GET['dep_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

$conditions = ["1=1"];
if ($dept_id > 0) {
    $conditions[] = "uta.dept_id = $dept_id";
}
if ($user_id > 0 && $user_id != 'All') {
    $conditions[] = "uta.user_id = $user_id";
}
$whereClause = implode(" AND ", $conditions);

$sql = "
SELECT 
    uta.dept_id,
    d.dept_name AS `Department`,
    u.full_name AS `Employee Name`,

    CASE WHEN uta.dept_id = 1 THEN uta.average_pc ELSE NULL END AS `Firstproof Pages`,
    CASE WHEN uta.dept_id = 1 THEN uta.average_ppc ELSE NULL END AS `Preclean Pages`,

    CASE WHEN uta.dept_id != 1 THEN uta.average_pc ELSE NULL END AS `Firstproof Pages (Others)`,
    CASE WHEN uta.dept_id != 1 THEN uta.average_cpc ELSE NULL END AS `Corrections Pages`,
    CASE WHEN uta.dept_id != 1 THEN uta.average_rpc ELSE NULL END AS `Revisions Pages`,
    CASE WHEN uta.dept_id != 1 THEN uta.average_fpc ELSE NULL END AS `Finals Pages`,
    CASE WHEN uta.dept_id != 1 THEN uta.average_ipc ELSE NULL END AS `Issue Pages`,

    CASE 
        WHEN uta.dept_id = 1 THEN 
            CASE 
                WHEN (COALESCE(uta.average_pr_pc, 0) + COALESCE(uta.average_pr_ppc, 0)) = 0 THEN NULL
                ELSE ROUND(
                    (
                        IF(uta.average_pr_pc > 0, uta.average_pr_pc, 0) + 
                        IF(uta.average_pr_ppc > 0, uta.average_pr_ppc, 0)
                    ) / (
                        IF(uta.average_pr_pc > 0, 1, 0) + 
                        IF(uta.average_pr_ppc > 0, 1, 0)
                    ), 2
                )
            END
        ELSE
            CASE 
                WHEN (
                    COALESCE(uta.average_pr_pc, 0) + COALESCE(uta.average_pr_cpc, 0) +
                    COALESCE(uta.average_pr_rpc, 0) + COALESCE(uta.average_pr_fpc, 0) +
                    COALESCE(uta.average_pr_ipc, 0)
                ) = 0 THEN NULL
                ELSE ROUND(
                    (
                        IF(uta.average_pr_pc > 0, uta.average_pr_pc, 0) +
                        IF(uta.average_pr_cpc > 0, uta.average_pr_cpc, 0) +
                        IF(uta.average_pr_rpc > 0, uta.average_pr_rpc, 0) +
                        IF(uta.average_pr_fpc > 0, uta.average_pr_fpc, 0) +
                        IF(uta.average_pr_ipc > 0, uta.average_pr_ipc, 0)
                    ) / (
                        IF(uta.average_pr_pc > 0, 1, 0) +
                        IF(uta.average_pr_cpc > 0, 1, 0) +
                        IF(uta.average_pr_rpc > 0, 1, 0) +
                        IF(uta.average_pr_fpc > 0, 1, 0) +
                        IF(uta.average_pr_ipc > 0, 1, 0)
                    ), 2
                )
            END
    END AS `Preferred Collaboration Avg`,

    CASE 
        WHEN uta.dept_id = 1 THEN 
            CASE 
                WHEN (COALESCE(uta.average_pc, 0) + COALESCE(uta.average_ppc, 0)) = 0 THEN NULL
                ELSE ROUND(
                    (
                        IF(uta.average_pc > 0, uta.average_pc, 0) + 
                        IF(uta.average_ppc > 0, uta.average_ppc, 0)
                    ) / (
                        IF(uta.average_pc > 0, 1, 0) + 
                        IF(uta.average_ppc > 0, 1, 0)
                    ), 2
                )
            END
        ELSE
            CASE 
                WHEN (
                    COALESCE(uta.average_pc, 0) + COALESCE(uta.average_cpc, 0) +
                    COALESCE(uta.average_rpc, 0) + COALESCE(uta.average_fpc, 0) +
                    COALESCE(uta.average_ipc, 0)
                ) = 0 THEN NULL
                ELSE ROUND(
                    (
                        IF(uta.average_pc > 0, uta.average_pc, 0) +
                        IF(uta.average_cpc > 0, uta.average_cpc, 0) +
                        IF(uta.average_rpc > 0, uta.average_rpc, 0) +
                        IF(uta.average_fpc > 0, uta.average_fpc, 0) +
                        IF(uta.average_ipc > 0, uta.average_ipc, 0)
                    ) / (
                        IF(uta.average_pc > 0, 1, 0) +
                        IF(uta.average_cpc > 0, 1, 0) +
                        IF(uta.average_rpc > 0, 1, 0) +
                        IF(uta.average_fpc > 0, 1, 0) +
                        IF(uta.average_ipc > 0, 1, 0)
                    ), 2
                )
            END
    END AS `Other Collaboration Avg`

FROM user_target_assign uta
LEFT JOIN users u ON u.id = uta.user_id
LEFT JOIN adm_dept_master d ON d.dept_code = uta.dept_id
WHERE $whereClause
ORDER BY d.dept_name, u.full_name;
";

$result = $db->query($sql);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$dataRows = [];
$hasDept1 = false;

while ($data = $result->fetch_assoc()) {
    if ($data['dept_id'] == 1) {
        $hasDept1 = true;
    }
    $dataRows[] = $data;
}

// Define headers
if ($hasDept1) {
    $headers = [
        'Department',
        'Employee Name',
        'Firstproof Pages',
        'Preclean Pages',
        'Collaboration Pages',
        'Preferred Collaboration Avg'
    ];
} else {
    $headers = [
        'Department',
        'Employee Name',
        'Firstproof Pages (Others)',
        'Corrections Pages',
        'Revisions Pages',
        'Finals Pages',
        'Issue Pages',
        'Collaboration Pages',
        'Preferred Collaboration Avg'
    ];
}
$sheet->fromArray($headers, NULL, 'A1');

// Initialize totals
$totals = $counts = array_fill_keys([
    'Firstproof Pages', 'Preclean Pages', 'Firstproof Pages (Others)', 'Corrections Pages', 
    'Revisions Pages', 'Finals Pages', 'Issue Pages', 'Other Collaboration Avg', 'Preferred Collaboration Avg'
], 0);

$rowNum = 2;
foreach ($dataRows as $row) {
    if ($hasDept1) {
        $sheet->fromArray([
            $row['Department'],
            $row['Employee Name'],
            $row['Firstproof Pages'] ?? '',
            $row['Preclean Pages'] ?? '',
            $row['Other Collaboration Avg'] ?? '',
            $row['Preferred Collaboration Avg'] ?? ''
        ], NULL, 'A' . $rowNum);

        foreach (['Firstproof Pages', 'Preclean Pages', 'Other Collaboration Avg', 'Preferred Collaboration Avg'] as $col) {
            if (is_numeric($row[$col])) {
                $totals[$col] += $row[$col];
                $counts[$col]++;
            }
        }
    } else {
        $sheet->fromArray([
            $row['Department'],
            $row['Employee Name'],
            $row['Firstproof Pages (Others)'] ?? '',
            $row['Corrections Pages'] ?? '',
            $row['Revisions Pages'] ?? '',
            $row['Finals Pages'] ?? '',
            $row['Issue Pages'] ?? '',
            $row['Other Collaboration Avg'] ?? '',
            $row['Preferred Collaboration Avg'] ?? ''
        ], NULL, 'A' . $rowNum);

        foreach (['Firstproof Pages (Others)', 'Corrections Pages', 'Revisions Pages', 'Finals Pages', 'Issue Pages', 'Other Collaboration Avg', 'Preferred Collaboration Avg'] as $col) {
            if (is_numeric($row[$col])) {
                $totals[$col] += $row[$col];
                $counts[$col]++;
            }
        }
    }
    $rowNum++;
}

// Total row
$totalRow = ['','Total'];
if ($hasDept1) {
    $totalRow[] = $counts['Firstproof Pages'] > 0 ? round($totals['Firstproof Pages'], 2) : '';
    $totalRow[] = $counts['Preclean Pages'] > 0 ? round($totals['Preclean Pages'], 2) : '';
    $totalRow[] = $counts['Other Collaboration Avg'] > 0 ? round($totals['Other Collaboration Avg'],2) : '';
    $totalRow[] = $counts['Preferred Collaboration Avg'] > 0 ? round($totals['Preferred Collaboration Avg'],2) : '';
} else {
    $totalRow[] = $counts['Firstproof Pages (Others)'] > 0 ? round($totals['Firstproof Pages (Others)'], 2) : '';
    $totalRow[] = $counts['Corrections Pages'] > 0 ? round($totals['Corrections Pages'], 2) : '';
    $totalRow[] = $counts['Revisions Pages'] > 0 ? round($totals['Revisions Pages'], 2) : '';
    $totalRow[] = $counts['Finals Pages'] > 0 ? round($totals['Finals Pages'], 2) : '';
    $totalRow[] = $counts['Issue Pages'] > 0 ? round($totals['Issue Pages'], 2) : '';
    $totalRow[] = $counts['Other Collaboration Avg'] > 0 ? round($totals['Other Collaboration Avg'],2) : '';
    $totalRow[] = $counts['Preferred Collaboration Avg'] > 0 ? round($totals['Preferred Collaboration Avg'],2) : '';
}

// Round totalRow values to 2 decimal places (from index 2 onward)
foreach ($totalRow as $k => $val) {
    if ($k >= 2 && is_numeric($val)) {
        $totalRow[$k] = round((float)$val, 2);  // Keep as float
    }
}


$sheet->fromArray($totalRow, NULL, 'A' . $rowNum);

// Set decimal format + right alignment for total row
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
for ($col = 3; $col <= $highestColumnIndex; $col++) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

    // Apply to all rows from 2 to total row
    for ($r = 2; $r <= $rowNum; $r++) {
        $cell = $colLetter . $r;
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}



// ======= FORMATTING ======= //
$headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$totalRange = 'A' . $rowNum . ':' . $sheet->getHighestColumn() . $rowNum;
$sheet->getStyle($totalRange)->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Column widths & alignment
$colWidth = [
    'A' => 20, 'B' => 25, 'C' => 18, 'D' => 18, 'E' => 20, 'F' => 24, 
    'G' => 20, 'H' => 22, 'I' => 26, 'J' => 26,
];

foreach ($colWidth as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
    $sheet->getStyle($col . '2:' . $col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// =========================== //

// Output
$timestamp = date("Y-m-d_H-i-s");
$dept_name = "AllDepts";
if ($dept_id > 0) {
    $dept_res = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = $dept_id");
    if ($dept_row = $dept_res->fetch_assoc()) {
        $dept_name = preg_replace('/\s+/', '', $dept_row['dept_name']);
    }
}

$user_name = "AllUsers";
if ($user_id > 0 && $user_id != 'All') {
    $user_res = $db->query("SELECT full_name FROM users WHERE id = $user_id");
    if ($user_row = $user_res->fetch_assoc()) {
        $user_name = preg_replace('/\s+/', '', $user_row['full_name']);
    }
}

$filename = "UserTargetReport_{$dept_name}_{$user_name}_{$timestamp}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>

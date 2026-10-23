<?php 
ob_start();
include ('dbc.php'); 
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$start_date = $_REQUEST['from_dt'];
$send_date  = $_REQUEST['to_dt'];
$cust_id    = $_REQUEST['cust_id'];
$stage      = $_REQUEST['stage'];

$search = "";
$due_date = "";

// Customer Filter
if (!empty($cust_id) && $cust_id != "all") {
    $search .= " AND wd.cust_id='$cust_id' ";
}

// Stage & Date Filter
switch ($stage) {

    case "FPALL":
        $due_date .= " AND DATE(wd.created_dt) BETWEEN '" . date('Y-m-d', strtotime($start_date)) . "' 
                      AND '" . date('Y-m-d', strtotime($send_date)) . "'";
        break;

    case "REVALL":
        $search .= " AND wd.stage LIKE 'REV%'";
        $due_date .= " AND DATE(r.received_date) BETWEEN '" . date('Y-m-d', strtotime($start_date)) . "' 
                      AND '" . date('Y-m-d', strtotime($send_date)) . "'";
        break;

    case "FINALL":
        $search .= " AND wd.stage LIKE 'FIN%'";
        $due_date .= " AND DATE(r.received_date) BETWEEN '" . date('Y-m-d', strtotime($start_date)) . "' 
                      AND '" . date('Y-m-d', strtotime($send_date)) . "'";
        break;

    case "FP":
        $search .= " AND wd.stage='FP'";
        $due_date .= " AND DATE(wd.created_dt)='" . date('Y-m-d', strtotime($send_date)) . "'";
        break;

    case "REV":
        $search .= " AND wd.stage LIKE 'REV%'";
        $due_date .= " AND DATE(r.received_date)='" . date('Y-m-d', strtotime($send_date)) . "'";
        break;

    case "FIN":
        $search .= " AND wd.stage LIKE 'FIN%'";
        $due_date .= " AND DATE(r.received_date)='" . date('Y-m-d', strtotime($send_date)) . "'";
        break;
}

$query = "
SELECT
    c.cust_name,
    j.j_code,
    wd.pub_id,
    wd.volume_folder,
    wd.stage,
    wd.created_dt,
    wd.due_dt,
    wd.manuscript_count,
    wd.fig,
    wd.fig_complete_status,
    wd.id,
    r.received_date,
    r.due_date,
    r.correction_pages,
    r.revision_count

FROM inw_inward_dtl wd

LEFT JOIN inw_revisions_dtl r
ON (
    wd.id=r.inw_id
    AND r.r_id=(
        SELECT MAX(r_id)
        FROM inw_revisions_dtl
        WHERE inw_id=wd.id
    )
)

INNER JOIN adm_customer_master c
ON c.id=wd.cust_id

INNER JOIN adm_journals j
ON j.j_id=wd.j_id

WHERE 1
$search
$due_date

ORDER BY FIELD(wd.highpriority,1) DESC,wd.target_due_dt ASC
";

$result = $db->query($query);

?>

<?php 
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inflow Report');

// Heading
$sheet->fromArray([
    'S.No',
    'Client',
    'Journal',
    'File Name',
    'Stage',
    'Volume/Issue',
    'Received Date',
    'Due Date',
    'Graphics',
    'Figure Count',
    'Manuscript Count'
], NULL, 'A1');

// Header Style
$sheet->getStyle('A1:K1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F81BD']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
]);

$rowNo = 2;
$sno = 1;

while($row = $result->fetch_assoc())
{
    $stage_f = preg_replace("/FIN([0-9]+)/","FIN",$row['stage']);
    $stage_r = preg_replace("/REV([0-9]+)/","REV",$row['stage']);

    if($stage=="FPALL" || $stage=="FP")
    {
        $received_date = $row['created_dt'];
        $due_dt = $row['due_dt'];
        $manuscript_count = $row['manuscript_count'];
    }
    else if($stage_r=="REV" || $stage_f=="FIN")
    {
        $received_date = $row['received_date'];
        $due_dt = $row['due_date'];
        $manuscript_count = $row['correction_pages'];
    }
    else
    {
        $received_date = $row['created_dt'];
        $due_dt = $row['due_dt'];
        $manuscript_count = $row['manuscript_count'];
    }

    $sheet->setCellValue('A'.$rowNo,$sno++);
    $sheet->setCellValue('B'.$rowNo,$row['cust_name']);
    $sheet->setCellValue('C'.$rowNo,$row['j_code']);
    $sheet->setCellValue('D'.$rowNo,$row['pub_id']);
    $sheet->setCellValue('E'.$rowNo,$row['stage']);
    $sheet->setCellValue('F'.$rowNo,$row['volume_folder']);
    $sheet->setCellValue('G'.$rowNo,!empty($received_date)?date('d-M-Y',strtotime($received_date)):'');
    $sheet->setCellValue('H'.$rowNo,!empty($due_dt)?date('d-M-Y',strtotime($due_dt)):'');
    $sheet->setCellValue('I'.$rowNo,($row['fig_complete_status']==1)?'Yes':'No');
    $sheet->setCellValue('J'.$rowNo,$row['fig']);
    $sheet->setCellValue('K'.$rowNo,$manuscript_count);

    $rowNo++;
}

// Auto size columns
foreach(range('A','K') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Borders
$sheet->getStyle('A1:K'.($rowNo-1))->applyFromArray([
    'borders'=>[
        'allBorders'=>[
            'borderStyle'=>Border::BORDER_THIN
        ]
    ]
]);

// Freeze Header
$sheet->freezePane('A2');

// Auto Filter
$sheet->setAutoFilter('A1:K1');

// Output
$fileName = 'Inflow_Report_'.date('Ymd_His').'.xlsx';

// Clear any output
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('Cache-Control: max-age=0');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
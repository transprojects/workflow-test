<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', 'Dispatch Report');

// Insert Image
if(!empty($_POST['chart_image'])){

    $imageData = $_POST['chart_image'];
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = base64_decode($imageData);

    $file = 'chart.png';
    file_put_contents($file, $imageData);

    $drawing = new Drawing();
    $drawing->setName('Chart');
    $drawing->setPath($file);
    $drawing->setCoordinates('A3');
    $drawing->setHeight(300);
    $drawing->setWorksheet($sheet);
}

// Output
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Report.xlsx"');
$writer->save('php://output');
exit;
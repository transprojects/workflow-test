<?php 
include 'includes/includes.php';
require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Directory setup
$uploadDir = __DIR__ . '/uploads/';
$logDir = __DIR__ . '/logs/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file1 = $uploadDir . basename($_FILES['file1']['name']);
    $file2 = $uploadDir . basename($_FILES['file2']['name']);
    move_uploaded_file($_FILES['file1']['tmp_name'], $file1);
    move_uploaded_file($_FILES['file2']['tmp_name'], $file2);

    $results = comparePdfFiles($file1, $file2);
    $excelFile = generateExcelReport($results);
    $logFile = generateLogFile($results);
     $overallStatus = $results['text_comparison'];

    // Return paths for download
    echo json_encode([
        'excel' => $excelFile,
        'log' => $logFile
    ]);
	   // Redirect back with status in URL
   // Redirect back with comparison results
    $queryParams = http_build_query([
        'textComparison' => $results['text_comparison'],
        'metadataComparison' => $results['metadata_comparison'],
        'metadataDiff' => implode(" | ", $results['metadataDiff']),
        'diffPreview' => implode(" | ", array_slice($results['diffLines'], 0, 5)),
        'diffCount' => count($results['diffLines']),
        'logFile' => urlencode($logFile)
    ]);

    header("Location: compare-pdf.php?$queryParams");
    exit;
}

function comparePdfFiles($file1, $file2) {
    $parser = new Parser();

    // Extract text and metadata
    $pdf1 = $parser->parseFile($file1);
    $pdf2 = $parser->parseFile($file2);
    $text1 = $pdf1->getText();
    $text2 = $pdf2->getText();
    $meta1 = $pdf1->getDetails();
    $meta2 = $pdf2->getDetails();

    // Text comparison
    $textComparison = ($text1 === $text2) ? 'Identical' : 'Different';

    // Metadata comparison
    $metadataComparison = ($meta1 === $meta2) ? 'Identical' : 'Different';

    // Metadata differences
    $metadataDiff = [];
    foreach ($meta1 as $key => $value) {
        $meta2Val = $meta2[$key] ?? 'N/A';
        if ($value !== $meta2Val) {
            $metadataDiff[] = "$key: File 1 = $value, File 2 = $meta2Val";
        }
    }

    // Line-by-line differences
    $diffLines = generateTextDiff($text1, $text2);

    return [
        'text_comparison' => $textComparison,
        'metadata_comparison' => $metadataComparison,
        'metadataDiff' => $metadataDiff,
'diffLines' => array_map(function($d) {
    return "Line {$d['line_number']} - File 1: {$d['file1']} | File 2: {$d['file2']}";
}, $diffLines)
    ];
}

// Simple line-based diff generator
function generateTextDiff($text1, $text2) {
    $lines1 = explode("\n", trim($text1));
    $lines2 = explode("\n", trim($text2));

    $diff = [];
    $maxLines = max(count($lines1), count($lines2));

    for ($i = 0; $i < $maxLines; $i++) {
        $line1 = $lines1[$i] ?? '';
        $line2 = $lines2[$i] ?? '';

        if ($line1 !== $line2) {
            $diff[] = [
                'line_number' => $i + 1,
                'file1' => $line1,
                'file2' => $line2,
            ];
        }
    }
    return $diff;
}

// Generate Excel file
function generateExcelReport($results) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column widths for better readability
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(60);
    $sheet->getColumnDimension('C')->setWidth(60);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(30);

    // Set header styles
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFDDDDDD']
        ]
    ];
    $sheet->getStyle('A1:B3')->applyFromArray($headerStyle);
    $sheet->getStyle('D1:E1')->applyFromArray($headerStyle);

    // Basic summary
    $sheet->setCellValue('A1', 'Comparison Type');
    $sheet->setCellValue('B1', 'Result');
    $sheet->setCellValue('A2', 'Text');
    $sheet->setCellValue('B2', $results['text_comparison']);
    $sheet->setCellValue('A3', 'Metadata');
    $sheet->setCellValue('B3', $results['metadata_comparison']);

    // Metadata details
    $sheet->setCellValue('D1', 'Metadata File 1');
    $sheet->setCellValue('E1', 'Metadata File 2');
    $row = 2;
    foreach ($results['metadata1'] as $key => $val) {
        $sheet->setCellValueExplicit("D$row", $key, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("E$row", $results['metadata2'][$key] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $row++;
    }

    // Add detailed diff
    $startRow = max($row, 5) + 2;
    $sheet->getStyle("A$startRow:C$startRow")->applyFromArray($headerStyle);
    $sheet->setCellValue("A$startRow", 'Line Number');
    $sheet->setCellValue("B$startRow", 'File 1 Text');
    $sheet->setCellValue("C$startRow", 'File 2 Text');

    $r = $startRow + 1;
    foreach ($results['diff_lines'] as $diffLine) {
        $sheet->setCellValueExplicit("A$r", $diffLine['line_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B$r", $diffLine['file1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("C$r", $diffLine['file2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        // Apply conditional formatting for differences
        if ($diffLine['file1'] !== $diffLine['file2']) {
            $sheet->getStyle("B$r:C$r")->applyFromArray([
                'font' => ['color' => ['argb' => 'FFFF0000']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFEEEE']
                ]
            ]);
        }
        $r++;
    }

    // Freeze header rows for easy scrolling
    $sheet->freezePane("A" . ($startRow + 1));

    // Save Excel file
    $filePath = 'logs/pdf_comparison_report_' . date('Ymd_His') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    return $filePath;
}


// Generate text log file
function generateLogFile($results) {
    $logFileName = 'logs/comparison_log_' . date('Ymd_His') . '.txt';
    $logData = "PDF Comparison Log\n";
    $logData .= "=================\n";
    $logData .= "Text Comparison: " . $results['text_comparison'] . "\n";
    $logData .= "Metadata Comparison: " . $results['metadata_comparison'] . "\n\n";

    $logData .= "Metadata Details:\n";
    foreach ($results['metadata1'] as $key => $value) {
        $logData .= "File 1 - $key: $value\n";
        $logData .= "File 2 - $key: " . ($results['metadata2'][$key] ?? '') . "\n";
    }

    $logData .= "\nText Differences:\n";
    foreach ($results['diff_lines'] as $diffLine) {
        $logData .= "Line " . $diffLine['line_number'] . ":\n";
        $logData .= "File 1: " . $diffLine['file1'] . "\n";
        $logData .= "File 2: " . $diffLine['file2'] . "\n\n";
    }

    file_put_contents($logFileName, $logData);
    return $logFileName;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PDF Comparison</title>
  <?php include('includes/title.php'); ?>
  <?php echo styles(); ?>
  <style>
    .overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5); z-index: 1000; display: none;
    }
    .loader-container {
      position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
      z-index: 1001; display: none;
    }
    .loader {
      border: 4px solid #f3f3f3; border-top: 4px solid #3498db;
      border-radius: 50%; width: 40px; height: 40px;
      animation: spin 2s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .download-links {
      margin-top: 20px;
      display: none;
    }
    .download-links a {
      margin-right: 15px;
      font-weight: 600;
      text-decoration: none;
      color: white;
      background: #007bff;
      padding: 8px 15px;
      border-radius: 4px;
    }
    .download-links a:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div id="loader" class="loader-container">
    <div class="loader"></div>
  </div>
  <div id="overlay" class="overlay"></div>

  <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="row pt-4 d-flex align-items-center flex-wrap">
        <div class="col-8 col-lg-10">
          <p class="articleheading ms-4">PDF Comparison</p>
        </div>
        <div class="col-4 col-lg-2">
          <div class="row d-flex justify-content-end pe-2">
            <div class="col-3 articlebtns">
              <a href="javascript:history.back();">
                <div class="back_icon"><img src="<?php echo $_COOKIE['set_url']; ?>/assets/images/backarrow.svg"></div>
              </a>
            </div>
          </div>
        </div>
      </div> 
    </div>

    <div class="container addarticleformouter">
      <div class="container addarticleforminner m-5">
        <form id="pdf-compare" method="post" enctype="multipart/form-data" action="compare-pdf.php">
          <div class="container mt-4">
            <div class="formcont1heading">Upload PDF Files for Comparison</div>
            <div class="row mt-1 g-4">
              <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Select First PDF<span style="color: red">*</span></label>
                <input type="file" class="form-control" name="file1" accept=".pdf" required />
              </div>
              <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Select Second PDF<span style="color: red">*</span></label>
                <input type="file" class="form-control" name="file2" accept=".pdf" required />
              </div>
            </div>
          </div>
          <div class="meta_nxtbtn container-fluid mt-5 mb-5">
            <div class="row">
              <div class="col form_btn cancelbtn d-flex justify-content-end">
                <button type="button" class="btn-next-cancel" onclick="history.back();">Cancel</button>
              </div>
              <input type="hidden" name="action" value="compare" />
              <div class="col form_btn nxtbtn">
                <button type="submit" class="btn-next">Compare</button>
              </div>
            </div>
          </div>
        </form>
 <!-- Result Message -->
        <!-- Result Message -->
        <div id="result-message" class="mt-4" style="font-weight: bold; color: #333;">
          <?php if (isset($_GET['textComparison'])): ?>
            <h4>Overall Result:</h4>
            <?php if ($_GET['textComparison'] === 'Identical'): ?>
              ✅ The PDF files are <span style="color: green;">Identical</span>.
            <?php else: ?>
              ❌ The PDF files are <span style="color: red;">Different</span>.
              <p><strong>Metadata Differences:</strong> <?php echo urldecode($_GET['metadataDiff']) ?></p>
              <p><strong>Sample Differences:</strong> <?php echo urldecode($_GET['diffPreview']) ?></p>
              <p><strong>Total Differences:</strong> <?php echo $_GET['diffCount'] ?></p>
              <a href="logs/<?php echo $_GET['logFile'] ?>" download>📄 Download Full Log</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <?php echo scripts(); ?>
    <?php echo footer(); ?>
  </div>


</body>
</html>

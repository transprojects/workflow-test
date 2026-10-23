<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Font;

// File paths
$inputFileName = 'Input.xlsx';
$outputFileName = 'Output.xml';

// Load the Excel file
$spreadsheet = IOFactory::load($inputFileName);
$worksheet = $spreadsheet->getActiveSheet();

// Prepare the XML structure
$xml = new SimpleXMLElement('<data></data>');

// Iterate over the rows and columns
foreach ($worksheet->getRowIterator() as $row) {
    $rowElement = $xml->addChild('row');
    foreach ($row->getCellIterator() as $cell) {
        $value = $cell->getValue();
        $cellStyle = $worksheet->getStyle($cell->getCoordinate());
        
        // Check if the font is bold
        if ($cellStyle->getFont()->getBold()) {
            $cellElement = $rowElement->addChild('bold', htmlspecialchars($value));
        } else {
            $cellElement = $rowElement->addChild('normal', htmlspecialchars($value));
        }
    }
}

// Save the XML to a file
$xml->asXML($outputFileName);

echo "XML file generated successfully: $outputFileName";
?>

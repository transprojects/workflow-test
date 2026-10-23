<?php
include_once("xlsxwriter.class.php"); //include
$writer = new XLSXWriter();     //new writer
$sheet_name = 'sheets';   //sheetname
$header = array("string","string","string","string","string","string","string","string","string","string","string","string","string","string","string","string","string","string","string");  // header-made for 19 columns
$row1 = array("A"," ","B"," ","C"," ","D"," ","E"," ","F"," ","G"," ","H"," ","I"," ","J"); //column B1, D1, F1, H1, J1, L1, N1, P1, R1 made blank to match formatting
// $writer->writeSheetHeader($sheet_name, $header, $suppress_header_row = true);   //write header
$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 1, $end_col = 1);  //merge cells for A
$j = 2; //start column
for ($i=0; $i<9; $i++)
{
$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = $j, $end_row = 0, $end_col = $j+1);  //merge cells
$j+=2;
}
$writer->writeSheetRow($sheet_name, $row1);   //write data
$writer->writeToFile('C:/xampp/htdocs/workflowtest/xlsxwriter/test2.xlsx');  //save file
?>
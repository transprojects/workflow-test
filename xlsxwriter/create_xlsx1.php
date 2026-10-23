<?php

error_reporting(0);
include_once("xlsxwriter.class.php"); //include
$writer = new XLSXWriter();     //new writer
$sheet_name = 'sheets';   //sheetname
$header = array("string","string","string","string","string","string");  // header-made for six columns
$row1 = array("A"," ","B"," ","C"); //column B1, D1 made blank to match formatting

$rowstyle[] = array('fill'=>"#ff00cc");
$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 1, $end_col = 1);  //merge cells for A
$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 2, $end_row = 0, $end_col = 3);  //merge cells for B
$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 4, $end_row = 0, $end_col = 5);  //merge cells for C
$writer->writeSheetRow($sheet_name, $row1,$rowstyle);   //write data
$writer->writeToFile('C:/LAMP/Apache/htdocs/workflowtest/xlsxwriter/test1.xlsx');  //save file



?>
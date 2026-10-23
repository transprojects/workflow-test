<?php 
error_reporting(0);
$fname= time()."invoice.xlsx"; 
require_once('includes/includes.php');
include ('dbc.php');
include ('includes/paginate.php');




include_once("xlsxwriter/xlsxwriter.class.php"); //include
$writer = new XLSXWriter();  

if($_SESSION['search_qry']!="")
    {  


		if(substr( $_SESSION['stage'], 0, 3 ) === "ISS" )
		{
			$query = "SELECT dh.vi_id, dh.j_id,c.cust_name FROM inw_issue_dispatch_history as dh, adm_customer_master as c, adm_journals as j WHERE dh.cust_id = c.id and dh.j_id = j.j_id ";
			$order_by = " GROUP BY dh.vi_id ORDER BY dh.sent_date asc";	
			$query = $query .$_SESSION['search_qry'].$order_by;
			$inw_issue_dispatch_history_details = $db->query($query) or ($db->error);
			/* $total_pages_count = 0;
			$sno=1;
			$below_twenty = 0;
			$above_twenty = 0;	 */		
			while($row = $inw_issue_dispatch_history_details->fetch_assoc())
			{
				
				$query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = '".$row['vi_id']."' ";
				$inw_volume_issues = $db->query($query) or ($db->error);
				$inw_volume_issues = $inw_volume_issues->fetch_assoc();
		
				$volume_number = ltrim($inw_volume_issues['volume_number'], "0"); 
				$issue_number = ltrim($inw_volume_issues['issue_number'], "0"); 
				
				//// Journal name ///	
				$query = "SELECT j_name,j_code,j_hid FROM `adm_journals` WHERE j_id = '".$row['j_id']."';";
				$steps = $db->query($query) or ($db->error);
				$jninfo = $steps->fetch_assoc();
				$journal_name = $jninfo['j_code'];
				
				$query = "SELECT wd.auto_pdf_page_count, wd.pub_id,wd.recv_dt,dh.sent_date  FROM inw_issue_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j  WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) AND dh.vi_id='".$row['vi_id']."' GROUP BY dh.a_id";
				$articles = $db->query($query) or ($db->error);
				
				$below_twenty = array();
				$above_twenty = array();
				$above_fourty = array();
				$bsno=1;
				$asno=1;
				$afsno=1;
				while($article = $articles->fetch_assoc())
				{
					if ($article['auto_pdf_page_count'] <= 20)
					{
						$k = $bsno - 1;
						$below_twenty[$k][0] = $bsno;
						$below_twenty[$k][1] = $article['pub_id'];
						$below_twenty[$k][2] = $article['auto_pdf_page_count'];
						$below_twenty[$k][3] = (date('d-m-Y',strtotime($article['recv_dt'])));
						$below_twenty[$k][4] = (date('d-m-Y',strtotime($article['sent_date'])));
						
						$bsno++;  
					}
					if ($article['auto_pdf_page_count'] > 20 && $article['auto_pdf_page_count'] <= 40)
					{
						$k = $asno - 1;
						$above_twenty[$k][0] = $asno;
						$above_twenty[$k][1] = $article['pub_id'];
						$above_twenty[$k][2] = $article['auto_pdf_page_count'];
						$above_twenty[$k][3] = (date('d-m-Y',strtotime($article['recv_dt'])));
						$above_twenty[$k][4] = (date('d-m-Y',strtotime($article['sent_date'])));
						
						$asno++;  
					}
					if ($article['auto_pdf_page_count'] > 40)
					{
						$k = $afsno - 1;
						$above_fourty[$k][0] = $asno;
						$above_fourty[$k][1] = $article['pub_id'];
						$above_fourty[$k][2] = $article['auto_pdf_page_count'];
						$above_fourty[$k][3] = (date('d-m-Y',strtotime($article['recv_dt'])));
						$above_fourty[$k][4] = (date('d-m-Y',strtotime($article['sent_date'])));
						
						$afsno++;  
					}	 
				}
				
				
				$sheet_name = $journal_name." ".$volume_number."(".$issue_number.")";  //sheetname
			
				if(!empty($below_twenty))
				{
					$row1 = array($sheet_name . " upto 20"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A

					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($below_twenty as $below_twentys => $below_twentyData) {
						$writer->writeSheetRow($sheet_name, $below_twentyData);
						
					}
					
					$writer->writeSheetRow($sheet_name, array("","","","",""));
				}
				
				if(!empty($above_twenty))
				{
					$row1 = array($sheet_name . " > 20"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					if(!empty($below_twenty))
					{
						$end = sizeof($below_twenty) + 3;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
					}
					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($above_twenty as $above_twentys => $above_twentyData) {
						$writer->writeSheetRow($sheet_name, $above_twentyData);
						
					}
				}
				
				if(!empty($above_fourty))
				{
					$row1 = array($sheet_name . " > 40"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					if(!empty($below_twenty))
					{
						$end = sizeof($below_twenty) + sizeof($above_twenty) + 3;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
					}
					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($above_fourty as $above_fourtys => $above_fourtyData) {
						$writer->writeSheetRow($sheet_name, $above_fourtyData);
						
					}
				}
				
			}
		
		}
		else
		{
			
			$published="";
			
			if($_SESSION['published'] == "66")
			{
			$query = "SELECT wd.auto_pdf_page_count, wd.pub_id,wd.recv_dt,dh.sent_date,c.cust_name,wd.inw_type FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE  dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id  AND (j.is_mathjournal IS NULL OR j.is_mathjournal = '') and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) "; //// Non Math Articles
			
				if($_SESSION['published'] == "66")
				{
				  //$published=" and published='1' ";
				  $published=" ";
				}
			}
			else
			{
			$query = "SELECT wd.auto_pdf_page_count, wd.pub_id,wd.recv_dt,dh.sent_date,c.cust_name,wd.inw_type FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id  AND (j.is_mathjournal IS NULL OR j.is_mathjournal = '') and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) "; //// Non Math Articles	
			}
			
			
			
			$order_by = " GROUP BY wd.pub_id ORDER BY dh.sent_date asc";	
		
			 $query = $query. $published.$_SESSION['search_qry'].$order_by;  
			
			$customers = $db->query($query) or ($db->error);
			$total_pages_count = 1;
			$bsno=1;
			$asno=1;
			$afsno=1;
			$below_twenty = array();
			$above_twenty = array();
			$above_fourty = array();			
			while($row = $customers->fetch_assoc())
			{
				if ($row['auto_pdf_page_count'] <= 20)
				{
					$k = $bsno - 1;
					if ($_SESSION['check_page_count'] == '1') 
					{
						$below_twenty[$k][0] = $bsno;
					}
					else
					{
						$below_twenty[$k][0] = $total_pages_count;
					}
					
					$below_twenty[$k][1] = $row['pub_id'];
					$below_twenty[$k][2] = $row['auto_pdf_page_count'];
					$below_twenty[$k][3] = (date('d-m-Y',strtotime($row['recv_dt'])));
					$below_twenty[$k][4] = (date('d-m-Y',strtotime($row['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$below_twenty[$k][5] = $row['inw_type'];
					}
					
					$bsno++;  
					
				}
				if ($row['auto_pdf_page_count'] > 20 && $row['auto_pdf_page_count'] <= 40)
				{
					$k = $asno - 1;
					if ($_SESSION['check_page_count'] == '1') 
					{
						$above_twenty[$k][0] = $asno;
					}
					else
					{
						$above_twenty[$k][0] = $total_pages_count;
					}
					
					$above_twenty[$k][1] = $row['pub_id'];
					$above_twenty[$k][2] = $row['auto_pdf_page_count'];
					$above_twenty[$k][3] = (date('d-m-Y',strtotime($row['recv_dt'])));
					$above_twenty[$k][4] = (date('d-m-Y',strtotime($row['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$above_twenty[$k][5] = $row['inw_type'];
					}
					
					$asno++;  
				}
				
				if ($row['auto_pdf_page_count'] > 40)
				{
					$k = $afsno - 1;
					if ($_SESSION['check_page_count'] == '1') 
					{
						$above_fourty[$k][0] = $afsno;
					}
					else
					{
						$above_fourty[$k][0] = $total_pages_count;
					}
					
					$above_fourty[$k][1] = $row['pub_id'];
					$above_fourty[$k][2] = $row['auto_pdf_page_count'];
					$above_fourty[$k][3] = (date('d-m-Y',strtotime($row['recv_dt'])));
					$above_fourty[$k][4] = (date('d-m-Y',strtotime($row['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$above_fourty[$k][5] = $row['inw_type'];
					}
					
					$afsno++;  
				}
					$total_pages_count++;  
			}
			
			
			$query = "SELECT wd.auto_pdf_page_count, wd.pub_id,wd.recv_dt,dh.sent_date, wd.inw_type FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and j.is_mathjournal = 1 and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) "; /// Math Journals
			
			$order_by = " GROUP BY wd.pub_id ORDER BY dh.sent_date asc";	
		
			$query = $query .$_SESSION['search_qry'].$order_by;  
			
			$customers = $db->query($query) or ($db->error);
			
			$mtotal_pages_count = 1;
			$mbsno=1;
			$masno=1;
			$mafsno=1;
			$mbelow_twenty = array();
			$mabove_twenty = array();
			$mabove_fourty = array();			
			while($mrow = $customers->fetch_assoc())
			{
				if ($mrow['auto_pdf_page_count'] <= 20)
				{
					
					$k = $mbsno - 1;
					if ($_SESSION['check_page_count'] == '1') 
					{
						$mbelow_twenty[$k][0] = $mbsno;
					}
					else
					{
						$mbelow_twenty[$k][0] = $mtotal_pages_count;
					}
					
					$mbelow_twenty[$k][1] = $mrow['pub_id'];
					$mbelow_twenty[$k][2] = $mrow['auto_pdf_page_count'];
					$mbelow_twenty[$k][3] = (date('d-m-Y',strtotime($mrow['recv_dt'])));
					$mbelow_twenty[$k][4] = (date('d-m-Y',strtotime($mrow['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$mbelow_twenty[$k][5] = $row['inw_type'];
					}
					
					$mbsno++;  
					
				}
				if ($mrow['auto_pdf_page_count'] > 20 && $mrow['auto_pdf_page_count'] <= 40)
				{
					$k = $masno - 1;
					
					if ($_SESSION['check_page_count'] == '1') 
					{
						$mabove_twenty[$k][0] = $masno;
					}
					else
					{
						$mabove_twenty[$k][0] = $mtotal_pages_count;
					}
					
					$mabove_twenty[$k][1] = $mrow['pub_id'];
					$mabove_twenty[$k][2] = $mrow['auto_pdf_page_count'];
					$mabove_twenty[$k][3] = $mrow['recv_dt'];
					$mabove_twenty[$k][4] = $mrow['sent_date'];
					$mabove_twenty[$k][3] = (date('d-m-Y',strtotime($mrow['recv_dt'])));
					$mabove_twenty[$k][4] = (date('d-m-Y',strtotime($mrow['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$mabove_twenty[$k][5] = $row['inw_type'];
					}
					
					$masno++;  
				}
				
				if ($mrow['auto_pdf_page_count'] > 40)
				{
					$k = $mafsno - 1;
					
					if ($_SESSION['check_page_count'] == '1') 
					{
						$mabove_fourty[$k][0] = $mafsno;
					}
					else
					{
						$mabove_fourty[$k][0] = $mtotal_pages_count;
					}
					
					$mabove_fourty[$k][1] = $mrow['pub_id'];
					$mabove_fourty[$k][2] = $mrow['auto_pdf_page_count'];
					$mabove_fourty[$k][3] = $mrow['recv_dt'];
					$mabove_fourty[$k][4] = $mrow['sent_date'];
					$mabove_fourty[$k][3] = (date('d-m-Y',strtotime($mrow['recv_dt'])));
					$mabove_fourty[$k][4] = (date('d-m-Y',strtotime($mrow['sent_date'])));
					if ($_SESSION['check_page_source'] == '1') 
					{
						$mabove_fourty[$k][5] = $row['inw_type'];
					}
					
					$mafsno++;  
				}
				
					$mtotal_pages_count++;  
			}
			
			if ($_SESSION['check_page_count'] == '1') 
			{
				
				$sheet_name = "Article Details";  //sheetname
			
				if(!empty($below_twenty))
				{
					$row1 = array("Page Count upto 20"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A

					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($below_twenty as $below_twentys => $below_twentyData) {
						$writer->writeSheetRow($sheet_name, $below_twentyData);
						
					}
					
					$writer->writeSheetRow($sheet_name, array("","","","",""));
				}
				
				if(!empty($above_twenty))
				{
					$row1 = array( "Page Count > 20"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					if(!empty($below_twenty))
					{
						$end = sizeof($below_twenty) + 3;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
					}
					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($above_twenty as $above_twentys => $above_twentyData) {
						$writer->writeSheetRow($sheet_name, $above_twentyData);
						
					}
					$writer->writeSheetRow($sheet_name, array("","","","",""));
				}
				
				if(!empty($above_fourty))
				{
					$row1 = array( "Page Count > 40"); //column B1, D1 made blank to match formatting
					$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row2 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					$end = sizeof($below_twenty) + sizeof($above_twenty);
					
					if($end > 0)
					{
						$end = $end + 3;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
					}
					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					foreach ($above_fourty as $above_fourtys => $above_fourtyData) {
						$writer->writeSheetRow($sheet_name, $above_fourtyData);
						
					}
					$writer->writeSheetRow($sheet_name, array("","","","",""));
				}
				
								
				
				
				if(!empty($mbelow_twenty))
				{
					$row1 = array("Math Articles"); //column B1, D1 made blank to match formatting
					$row2 = array("Page Count upto 20"); //column B1, D1 made blank to match formatting
					$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					if(!empty($below_twenty) && !empty($above_twenty))
					{
						$end = sizeof($below_twenty) + sizeof($above_twenty) + sizeof($above_fourty) + 7;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
						$writer->markMergedCell($sheet_name, $start_row = ($end + 1), $start_col = 0, $end_row = ($end + 1), $end_col = 2);  //merge cells for A
					}
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
						
					}


					
					
					$writer->writeSheetRow($sheet_name, array("","","","",""));
					$writer->writeSheetRow($sheet_name, array("","","","",""));
					$writer->writeSheetRow($sheet_name, $row1);   //write data
					$writer->writeSheetRow($sheet_name, $row2);
					$writer->writeSheetRow($sheet_name, $row3);
					foreach ($mbelow_twenty as $mbelow_twentys => $mbelow_twentyData) {
						$writer->writeSheetRow($sheet_name, $mbelow_twentyData);
						
					}
					
					$writer->writeSheetRow($sheet_name, array("","","","",""));
				}
				
				if(!empty($mabove_twenty))
				{
					$row1 = array("Math Articles");
					$row2 = array( "Page Count > 20"); //column B1, D1 made blank to match formatting
					$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					$end = sizeof($below_twenty) + sizeof($above_twenty) + sizeof($above_fourty)  + sizeof($mbelow_twenty);
					
					if($end > 0)
					{
						 $end = $end + 4;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
						$writer->writeSheetRow($sheet_name, $row1);   //write data
					}
					
					$writer->writeSheetRow($sheet_name, $row2);   //write data
					$writer->writeSheetRow($sheet_name, $row3);
					foreach ($mabove_twenty as $mabove_twentys => $mabove_twentyData) {
						$writer->writeSheetRow($sheet_name, $mabove_twentyData);
						
					}
				}
				
				if(!empty($mabove_fourty))
				{
					$row1 = array("Math Articles");
					$row2 = array( "Page Count > 40"); //column B1, D1 made blank to match formatting
					$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					$rowstyle[] = array('fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff",'fill'=>"#0033ff");
					
					$end = sizeof($below_twenty) + sizeof($above_twenty) + sizeof($above_fourty)  + sizeof($mbelow_twenty) + sizeof($mabove_twenty);
					
					if($end > 0)
					{
						 $end = $end + 4;						
						
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
					}
					
					else
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
						$writer->writeSheetRow($sheet_name, $row1);   //write data
					}
					
					$writer->writeSheetRow($sheet_name, $row2);   //write data
					$writer->writeSheetRow($sheet_name, $row3);
					foreach ($mabove_fourty as $mabove_fourtys => $mabove_fourtyData) {
						$writer->writeSheetRow($sheet_name, $mabove_fourtyData);
						
					}
				}
				
				
				
		
			
			}
			else
			{
				
				$sheet_name = "Article Details";  //sheetname
			
				$non_math_articles = array_merge($below_twenty,$above_twenty,$above_fourty);
				
				$key_values = array_column($non_math_articles, 0); 
				array_multisort($key_values, SORT_ASC, $non_math_articles);

			
				if(!empty($non_math_articles))
				{
					$row1 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row1 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					
					$writer->writeSheetRow($sheet_name, $row1);
					foreach ($non_math_articles as $non_math_article => $non_math_articleData) {
						$writer->writeSheetRow($sheet_name, $non_math_articleData);
						
					}	
					$writer->writeSheetRow($sheet_name, array("","","","",""));
					$writer->writeSheetRow($sheet_name, array("","","","",""));
									
				}
				
				
				$math_articles = array_merge($mbelow_twenty,$mabove_twenty,$mabove_fourty);
				
				$key_values = array_column($math_articles, 0); 
				array_multisort($key_values, SORT_ASC, $math_articles);

				
				if(!empty($math_articles))
				{
					$row1 = array("Math Articles"); //column B1, D1 made blank to match formatting
					$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date"); //column B1, D1 made blank to match formatting
					if ($_SESSION['check_page_source'] == '1') 
					{
						$row3 = array("Sno","Article id","Page Count","Received Date","Sent Date","Type of input");
					}
					
					if(empty($non_math_articles))
					{
						$writer->markMergedCell($sheet_name, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 2);  //merge cells for A
						$writer->writeSheetRow($sheet_name, $row1);   //write data
						
					}
					else
					{
						$end = $total_pages_count + 2;
						$writer->markMergedCell($sheet_name, $start_row = $end, $start_col = 0, $end_row = $end, $end_col = 2);  //merge cells for A
						$writer->writeSheetRow($sheet_name, $row1);   //write data
					}
					
					
					
					$writer->writeSheetRow($sheet_name, $row3);
					foreach ($math_articles as $math_article => $math_articleData) {
						$writer->writeSheetRow($sheet_name, $math_articleData);
						
					}
					
					
				}
				
		
			}				
			
			
				
		}		
	}


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Disposition: attachment;filename="'.$fname.'"');
 header('Cache-Control: max-age=0');
 $writer->writeToStdOut();

 ?>

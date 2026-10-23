<?php 
include ('dbc.php');
 
set_time_limit(0);
//// Get the Volume issues Details  ////
	$volume_query = "SELECT * FROM `inw_volume_issues` WHERE (cust_id = '42' AND automation_process = '0') LIMIT 1;";
	$volume_issues = $db->query($volume_query) or ($db->error);
	$toatl_count=$volume_issues->num_rows;
	if($toatl_count>0)
	{
		$volume_issue_id = "";
		while($volume_issues_info = $volume_issues->fetch_assoc())
		{
			$volume_issue_id = $volume_issues_info['vi_id'];
			
			$volume_starting_page_number = $volume_issues_info['starting_page_number'];
			
			/// To check the staring page number must be a odd number else stop it.
			if($volume_starting_page_number % 2 == 0)
			{
				/// updateing the automation status ////
				$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='Starting page number must be a odd number, please check in workflow' WHERE vi_id = ".$volume_issue_id."";
				$db->query($prj_update_vi);
				exit;
			}
		
						
			/// updateing the automation status ////
			$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='1',`automation_status` ='Getting the pdf page count' WHERE vi_id = ".$volume_issue_id." ";
			$db->query($prj_update_vi);
			
			/// To get all article from this issue in inward table  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_issue_id."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($query) or ($db->error);
			$toatl_count=$articledetails->num_rows;
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					
					//// Get the customer details ////
					$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$article_info['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
					
					
					////Get the Journal details ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$article_info['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();
					
					$src_path = $article_info['work_dir'].$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/"; 
				
					$pdf_file = $src_path."PAG/".$article_info['pub_id'].".pdf";
					$latex_file = $src_path."PAG/".$article_info['pub_id'].".tex";
					if(file_exists($pdf_file))
					{
						$stream = fopen($pdf_file, "r");
						$content = fread ($stream, filesize($pdf_file));
						
						$count = 0;
						// Regular Expressions found by Googling (all linked to SO answers):
						$regex  = "/\/Count\s+(\d+)/";
						$regex2 = "/\/Page\W*(\d+)/";
						$regex3 = "/\/N\s+(\d+)/";
						
						if(preg_match_all($regex3, $content, $matches))
						$count = max($matches);
						
						//echo    $article_info['pub_id'] ."\t".$count['0'] . "<br/>";
						
						/// updateing the automation status ////
						$update_query="UPDATE inw_inward_dtl SET `automation_pdf_page_count` ='".$count['0']."' WHERE id = ".$article_info['id']." ";
						$db->query($update_query);
						
						
					}
					else
					{
						/// updateing the automation status ////
						$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='".$article_info['pub_id'].".pdf not available in PAG folder' WHERE vi_id = ".$volume_issue_id." ";
						$db->query($prj_update_vi);
						exit;
					}
				}
			}
		}
		$volume_query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = '".$volume_issue_id ."';";
		$volume_issues = $db->query($volume_query) or ($db->error);
		while($volume_issues_info = $volume_issues->fetch_assoc())
		{
			$volume_issue_id = $volume_issues_info['vi_id'];
			
			$volume_starting_page_number = $volume_issues_info['starting_page_number'];
			/// updateing the automation status ////
			$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='1',`automation_status` ='Updating the Latex files' WHERE vi_id = ".$volume_issue_id." ";
			$db->query($prj_update_vi);
			
			/// To get all article from this issue in inward table  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_issue_id."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($query) or ($db->error);
			$toatl_count=$articledetails->num_rows;
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					
					//// Get the customer details ////
					$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$article_info['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
					
					
					////Get the Journal details ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$article_info['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();
					
					$src_path = $article_info['work_dir'].$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/"; 
 
					$latex_file = $src_path."PAG/".$article_info['pub_id'].".tex";
					if(file_exists($latex_file))
					{
						 $tex_content = file_get_contents($latex_file, true); 
						 
						 $pagecount = $article_info['automation_pdf_page_count'];
						 $pagecount = $article_info['automation_pdf_page_count'];
						 $volume_number = $volume_issues_info['volume_number'];
						 $issue_number = $volume_issues_info['issue_number'];
						 
						 $last_page = $volume_starting_page_number +  $pagecount - 1;
						$RHPAGES =  $volume_starting_page_number."--".$last_page;
						
					$tex_content = preg_replace('/(%*)\\\\COLL(\\n|\\r\\n)/i','%\COLL$2',$tex_content);
					$tex_content = preg_replace('/\\\\documentclass(\{.*\})/i','\documentclass[finalproof]$1',$tex_content);
					$tex_content = preg_replace('/\\\\FIRSTPAGE\{.*\}/i','\FIRSTPAGE{'. $volume_starting_page_number .'}',$tex_content);
					$tex_content = preg_replace('/\\\\LASTPAGE\{.*\}/i','\LASTPAGE{'. $last_page .'}',$tex_content);
					$tex_content = preg_replace('/\\\\TOTALPAGES\{.*\}/i','\TOTALPAGES{'. $pagecount .'}',$tex_content);
					$tex_content = preg_replace('/\\\\VOLUMENO\{.*\}/i','\VOLUMENO{'.$volume_number.'}',$tex_content);
					$tex_content = preg_replace('/\\\\ISSUENO\{.*\}/i','\ISSUENO{'.$issue_number.'}',$tex_content);
					
					
						$file = fopen($latex_file,"w");
						fwrite($file,$tex_content);
						fclose($file);
						
						/// check the page count, if page count is odd then add one page additional
						if($pagecount % 2 == 0)
						{
							$volume_starting_page_number = $volume_starting_page_number + $pagecount;
						}
						else{
							$volume_starting_page_number = $volume_starting_page_number + $pagecount + 1;
						}
					}
					else
					{
						/// updateing the automation status ////
						$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='".$article_info['pub_id'].".tex not available in PAG folder' WHERE vi_id = ".$volume_issue_id."";
						$db->query($prj_update_vi);
						exit;
					}
				}
			}
		}
		
		$volume_issues = $db->query($volume_query) or ($db->error);
		while($volume_issues_info = $volume_issues->fetch_assoc())
		{
			$volume_issue_id = $volume_issues_info['vi_id'];
			
			$volume_starting_page_number = $volume_issues_info['starting_page_number'];
			/// updateing the automation status ////
			$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='1',`automation_status` ='PDF creation process running' WHERE vi_id = ".$volume_issue_id." ";
			$db->query($prj_update_vi);
			
			/// To get all article from this issue in inward table  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_issue_id."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($query) or ($db->error);
			$toatl_count=$articledetails->num_rows;
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					
					//// Get the customer details ////
					$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$article_info['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
					
					
					////Get the Journal details ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$article_info['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();
					
					$src_path = $article_info['work_dir'].$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/PAG/"; 
 
					$dir = $src_path;
					$filaname = $article_info['pub_id'];
					$latex_file = $dir.$filaname.".tex";
					$log_file = $dir.$filaname.".log";
					$pdf_file = $dir.$filaname.".pdf";
					$res_pdf_file = $dir.$filaname."-res.pdf";
					$dvi_file = $dir.$filaname.".dvi";
					$ps_file = $dir.$filaname.".ps";

					// if(file_exists($pdf_file))
					// {
						// unlink($pdf_file);
					// }
					if(file_exists($dvi_file))
					{
						unlink($dvi_file);
					}
					if(file_exists($ps_file))
					{
						unlink($ps_file);
					}				
					
					$latex_path = "";
					/* $step1 = "latex -interaction=batchmode ".$filaname." ".$filaname.".tex";
					$step2 = "latex -interaction=batchmode ".$filaname." ".$filaname.".tex";
					$step3 = "dvips ".$filaname.".dvi"; */
					// $step4 = "ps2pdf ".$filaname.".ps";
					
					 $step1 = "latex -interaction=batchmode ".$filaname." ".$dir.$filaname.".tex";
					$step2 = "latex -interaction=batchmode ".$filaname." ".$dir.$filaname.".tex";
					$step3 = "dvips ".$dir.$filaname.".dvi"; 
					
					/*$step1 = "latex ".$dir.$filaname.".tex";
					$step2 = "latex ".$dir.$filaname.".tex";
					$step3 = "dvips ".$dir.$filaname.".dvi"; */
					// $step4 = "ps2pdf ".$dir.$filaname.".ps";
					
					
					$run_content = $step1."\n".$step2."\n".$step3."\n".$step4."\n";
					
					$file = fopen($dir."run.bat","w");
					fwrite($file,$run_content);
					fclose($file);
					
					$out = array();
					chdir($dir);
					exec("run.bat", $out);
					
					// unlink("run.bat");
					
					$high_res_psfile = TSP_DISTILLER_HIGH_RES_PATH."in/".$filaname.".ps";
					$low_res_psfile = TSP_DISTILLER_LOW_RES_PATH."in/".$filaname.".ps";
					 $high_res_pdffile = TSP_DISTILLER_HIGH_RES_PATH."out/".$filaname.".pdf";
					 $low_res_pdffile = TSP_DISTILLER_LOW_RES_PATH."out/".$filaname.".pdf";
					if(file_exists($ps_file))
					{
						copy($ps_file,$high_res_psfile);
						copy($ps_file,$low_res_psfile);
						$high_res_status = false;
						$low_res_status = false;
						while(1)
						{
							if(!$high_res_status)
							{	
								if (file_exists($high_res_pdffile))
								{
									sleep(2);
									if (@copy($high_res_pdffile, $pdf_file)) 
									{
										unlink($high_res_pdffile);
										$high_res_status = true;
									}
									
								}
							}
							if(!$low_res_status)
							{	
								if (file_exists($low_res_pdffile))
								{
									sleep(2);
									if (@copy($low_res_pdffile, $res_pdf_file)) 
									{
										unlink($low_res_pdffile);
										$low_res_status = true;
									}
									
								}
							}
							if($low_res_status && $high_res_status)
							{
								break;	
							}
						}
					}
					
					
					$log_con = "";
					$type = true; 
					if(file_exists($log_file))
					{
						$log_con = file_get_contents($log_file);
					
						$needle   = "Undefined control sequence";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "Too many }'s";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "Missing $ inserted";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "Runaway argument?";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "LaTeX Error: File";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "LaTeX Error:";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
						$needle   = "Emergency stop";
						if( strpos( $log_con, $needle ) !== false) 
						{
							$type = false;
						}
					}
					
					if(file_exists($pdf_file))
					{
						if($type)
						{
							
						}
						else
						{
							$type = false;
						}
					}
					else
					{
						$type = false;
					}
					if($type)
					{
						/// updateing the automation status ////
						$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='1',`automation_status` ='".$article_info['v_issue_sequence_id']." article completed, ".($toatl_count - $article_info['v_issue_sequence_id'] )." articles in queue.' WHERE vi_id = ".$volume_issue_id."";
						$db->query($prj_update_vi);
					}
					else
					{
						/// updateing the automation status ////
						$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='".$article_info['pub_id'].".pdf creation Error, Please check in Log file.' WHERE vi_id = ".$volume_issue_id."";
						$db->query($prj_update_vi);
						exit;
						
					}
					
					
				}
			}
		}
			$volume_issues = $db->query($volume_query) or ($db->error);
		while($volume_issues_info = $volume_issues->fetch_assoc())
		{
			$volume_issue_id = $volume_issues_info['vi_id'];
			
			/// updateing the automation status ////
			$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='1',`automation_status` ='Moving the PDF files to Delivery folder' WHERE vi_id = ".$volume_issue_id." ";
			$db->query($prj_update_vi);
			
			/// To get all article from this issue in inward table  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_issue_id."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($query) or ($db->error);
			$toatl_count=$articledetails->num_rows;
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					$toatl_files_length = strlen((string)$toatl_count);
					
					$v_issue_sequence_id = str_pad($article_info['v_issue_sequence_id'], $toatl_files_length, '0', STR_PAD_LEFT);
					
					//// Get the customer details ////
					$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$article_info['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
					
					////Get the Journal details ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$article_info['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();
					
					$src_path = $article_info['work_dir'].$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/PAG/"; 
					$prqc_path = $article_info['work_dir'].$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/PRQC/"; 
					$dest_path = $article_info['work_dir'].$article_info['volume_folder']."/Delivery/".$article_info['stage']."/"; 
					$src_pdf = $src_path.$article_info['pub_id'].".pdf";
					$prqc_pdf = $prqc_path.$article_info['pub_id'].".pdf";
					$dest_pdf = $dest_path.$v_issue_sequence_id."_".$volume_issues_info['volume_number'].$jninfo['j_code'].$volume_issues_info['issue_number']."-".preg_replace('/(.*)(-)(.*)/i','$3',$article_info['pub_id']).".pdf";
					if(file_exists($src_pdf))
					{
						// If the destination directory does not exist create it
						if(!is_dir($prqc_path)) { 
							if(!mkdir($prqc_path)) {
								 /// updateing the automation status ////
								$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='PRQC Folder Creation Error' WHERE vi_id = ".$volume_issue_id."";
								$db->query($prj_update_vi);
							}    
						}
							// If the destination directory does not exist create it
						if(!is_dir($dest_path)) { 
							if(!mkdir($dest_path)) {
								/// updateing the automation status ////
								$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='2',`automation_status` ='Delivery Folder Creation Error' WHERE vi_id = ".$volume_issue_id."";
								$db->query($prj_update_vi);
							}    
						}
						copy($src_pdf,$prqc_pdf);
						// copy($src_pdf,$dest_pdf);
					}
					
					/// updateing the inw_inward_dtl table ////
					$prj_update_vi="UPDATE inw_inward_dtl SET `status` ='PRQC',`department_id` ='14' WHERE id = ".$article_info['id']."";
					$db->query($prj_update_vi);
					
					
				}
				/// updateing the automation status ////
				$prj_update_vi="UPDATE inw_volume_issues SET `automation_process` ='3',`automation_status` ='Process Completed' WHERE vi_id = ".$volume_issue_id."";
				$db->query($prj_update_vi);
			}
		}
	}
	
	

?>
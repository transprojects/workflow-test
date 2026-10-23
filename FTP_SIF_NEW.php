<?php
	/**
	*	FTP Downloader SIF Publisher
	* 	Created on 26-07-2019
	*/
	
	 set_time_limit(1000000);
	// session_start();
	//  require("config/config.php");
	include("config/workflow_dbc.php");
	
$myfile = fopen("sif_log.txt", "a") or die("Unable to open file!");	
	
	
	/* ///// Mail Configuration ///////////
	
	$mail_from = "trans.robatic.msg@gmail.com";
	$mail_pass = "Technet16";
	$mail_subject = "";
	$mail_body = "";
	$mailids_to = "";
	$mailids_cc = "";
	$mailids_bcc = "";
	
	include("smtpmail/phpmailer/class.phpmailer.php");

	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->CharSet = 'UTF-8';
	$mail->Host = "smtp.gmail.com";
	$mail->SMTPAuth= true;
	$mail->Port = 587;
	$mail->Username= $mail_from;
	$mail->Password= $mail_pass;
	$mail->SMTPSecure = 'tls';
	$mail->From = $mail_from;
	$mail->FromName= $mail_from;
	$mail->isHTML(true); */
	
									
									
									
									
	
	$perl = 'perl "'.$config['FTP_FILESIZE_SIF'].'"';
	$rs = exec($perl); 
	
	
	//exit;
	
	if(strlen($rs) == 0)
	{	
						

		$txt = "Found JSON text in log file\n";
		$txt .= "File name:".$config['LOGFILE_PATH_SIF']."\n";
		fwrite($myfile, $txt);
		echo $url = $config['LOGFILE_PATH_SIF']; // path to your JSON file
		$data = file_get_contents($url); // put the contents of the file into a variable
		$filedetials = json_decode($data); // decode the JSON feed

		foreach ($filedetials as $filedetial) 
		{
			
			$txt = "Entered in Loop to run all the SIF files\n";
		$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
			// print_r($filedetial);
			$pubname = $filedetial->pubname;
			$journalname = $filedetial->journalname;
			$ftype = $filedetial->ftype;
			$filename = $filedetial->filename;
			$filesize = $filedetial->filesize;
		
			/// Checking File is already exist or not in DB ///
		
			$sql = "SELECT * FROM trans_ftp_sif where filename='".$filename."' AND ftype ='".$ftype."'"; 
			$result = $mysqli->query($sql);
		
			if ($result->num_rows==0) 
			{
				$txt = "File already existin trans_ftp_sif workflow DB\n";
		//$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
				$filename = $filename;
				$datetime = date("Y-m-d H:i:s");
				$StartTime = date("Y-m-d H:i:s");
				$status = "0";
			
				echo $sql2 = "INSERT INTO trans_ftp_sif (ftype,filename, pubname, journalname, filesize, datetime,status,StartTime) VALUES('".$ftype."','".$filename."','". $pubname."','". $journalname."','". $filesize."','". $datetime."', '".$status."', '".$StartTime."')";
			
				if ($mysqli->query($sql2) === TRUE) 
				{
					$txt = "Inserted new record workflow DB\n";
		//$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
				}
				
			}
			else
			{
				
				$txt = "Already exit in the workflow DB\n";
		//$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
				$sql = "SELECT * FROM trans_ftp_sif where (filename='".$filename."' AND ftype ='".$ftype."') ORDER BY sno DESC LIMIT 1"; 
				$result = $mysqli->query($sql);
				
				
				while($row = $result->fetch_assoc()) 
				{
					if($row["status"] == "1")
					{
						$filename = $filename;
						$datetime = date("Y-m-d H:i:s");
						$StartTime = date("Y-m-d H:i:s");
						$status = "0";
					
						$sql2 = "INSERT INTO trans_ftp_sif (ftype,filename, pubname, journalname, filesize, datetime,status,StartTime) VALUES('".$ftype."','".$filename."','". $pubname."','". $journalname."','". $filesize."','". $datetime."', '".$status."', '".$StartTime."')";
					
						if ($mysqli->query($sql2) === TRUE) 
						{
							$txt = "INSERT INTO trans_ftp_sif workflow DB\n";
		//$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
						}
					}
					else if($row["filesize"] == $filesize && $row["status"] == "0")
					{
						//// Download Files from FTP using perl/////
						$txt = "Download Files from FTP using perl\n";
		//$txt .= "For each File name:".$filedetial->filename." Size: ".$filedetial->filesize."\n";
		fwrite($myfile, $txt);
						// echo "aaa";
						echo $perl = 'perl "'.$config['FTP_DOWNLOAD_SIF'].'" "'.$filename.'" "'.$journalname.'" "'.$ftype.'"';
						// $rs = exec($perl);
						$rs="";
						exec($perl, $out);
						foreach($out as $line) 
						{
							$rs.=$line;
						}
						
						$txt = "Download Files from FTP using perl end\n";
		$txt .= "For each File name:".$rs."\n";
		fwrite($myfile, $txt);
						if(strlen($rs) == 0)
						{
							
							$txt = "Update File status in DB\n";
		$txt .= "For each File name:".$rs."\n";
		fwrite($myfile, $txt);
							//// Update File status in DB ////
							$sql1 = "UPDATE trans_ftp_sif SET EndTime='".date("Y-m-d H:i:s")."', Status='1' WHERE sno=".$row["sno"]."";
							// $sql1 = "UPDATE trans_ftp_sif SET EndTime='".date("Y-m-d H:i:s")."', Status='0' WHERE sno=".$row["sno"]."";
							if ($mysqli->query($sql1) === TRUE) 
							{
								
								$txt = "To check it is a first time came or more then once\n";
		$txt .= "For each File name:".$rs."\n";
		fwrite($myfile, $txt);
								//// To check it is a first time came or more then once ////
								$sql = "SELECT * FROM trans_ftp_sif where (filename='".$filename."' AND ftype ='".$ftype."')"; 
								$result = $mysqli->query($sql);
								
								if ($result->num_rows == 1) 
								{
									//// Move file from Files forlder to AUTOMATION-SYSTEM folder ////			
									if($ftype == "FP")
									{
										// while(1)
										// {
											/* if(@copy($config['DOWNLOAD_PATH_SIF'].$filename , $config['AUTOMATION_PATH_SIF'].$journalname."\\".$filename ))
											{  
												if (file_exists($config['AUTOMATION_PATH_SIF'].$journalname."\\".$filename)) 
												{ */
													
													
													$inpath =$config['DOWNLOAD_PATH_SIF'];
													$file = $filename;
													
													$Cn= $pubname;													
													$jn= $journalname;
													$dir  = WF_DIR;
													$txt = "Checking the file exist in 109 downloads folder".$inpath.$file."\n";
													fwrite($myfile, $txt);
	
													if(is_file($inpath.$file)) // checking this is file or not
													{
														$info = new SplFileInfo($inpath.$file); // getting the file info
														$file_type = $info->getExtension();  // getting the file Extension
														if($file_type == 'bz2' || $file_type == 'tar' ) 
														{
															$txt = "File type is bz2 and tar we will insert in workflow\n";
															fwrite($myfile, $txt);
		
															
															$file_ary = explode("-",$file);
															$fn=$file_ary[0];
															$artile_name= $fn;	
															$download_folder = '/downloads/JOURNALS/';
															$Vol_folder = 'Vol00000';
															
															
															//// To Check if file already edists or not ////
															$query = "SELECT count(*)  as total  FROM `inw_inward_dtl` WHERE pub_id = '".$fn."';";
															$steps = $db->query($query) or ($db->error);
															$fileinfo = $steps->fetch_assoc();
															
															if($fileinfo['total'] == 0)
															{
																$eplToken = "";
																$eplProjectId = "";
																
																 $NewFilePath   = $dir.$download_folder.$Cn."/".$jn."/".$Vol_folder."/".$artile_name."/FP"; 
																		
																if (!(is_dir($NewFilePath))) 
																{
																	if (!mkdir($NewFilePath, 0777, true)) 
																	{
																		die("Failed to create folders...$NewFilePath");
																	}
																}
																try 
																{
																	$phar = new PharData($inpath.$file);
																	$phar->extractTo($NewFilePath); // extract all files
																	
																	
																} 
																catch (Exception $e) 
																{
																	echo $e;
																	// handle errors
																}
																
																/// Get the input type ////
																$type = "Latex"; /// default as Latex
																$manuscriptPath = $NewFilePath."/".$artile_name;
																if(is_dir($manuscriptPath))
																{
																	$mPath = opendir($manuscriptPath);
																	while (false !== ($filename = readdir($mPath))) 
																	{
																		$files[] = $filename;
																		if($filename == $artile_name.".doc" || $filename == $artile_name.".docx")
																		{
																			$type = "Word"; /// default as Word
																			break;
																		}
																	}
																}
																////// Get the article details from metaxml /////////
																$articleTitle = $artile_name;
																$figcount = 11111;
																$tabcount = 11111;
																if($type == "Latex")
																{
																	/// 17-11-2024 ////
																	echo "moving the ZIP packag to new Latex server";
																	$zipFilepath = $inpath.$file;
																	$fnArticleId = $fn;
																	 include("editlatex_dev.php");
																	/// end the code here
																   
																	
																	if(file_exists($manuscriptPath."/".$artile_name.".tex"))
																	{
																			$file = fopen($manuscriptPath."/".$artile_name.".tex","r");
																			$figcount_tex = 0;
																			$tabcount_tex = 0;
																			while(! feof($file))
																			{
																				$line = fgets($file);
																				if (preg_match('/^\\\\begin\{figure(\*)?\}/',$line,$match ))
																				{
																					$figcount_tex++;
																				}
																				/* if (preg_match('/^\\\\begin\{figure*\}/',$line,$match ))
																				{
																					$figcount_tex++;
																				} */																				
																				if (preg_match('/^\\\\begin\{table(\*)?\}/',$line,$match ))
																				{
																					$tabcount_tex++;
																				}
/* 
																				if (preg_match('/^\\\\begin\{table*\}/',$line,$match ))
																				{
																					$tabcount_tex++;
																				} */
																				if (preg_match('/^\\\\title\{(.*)\}/',$line,$match ))
																				{
																					$articleTitle = $match[1];
																				}
																			}
																			$figcount = $figcount_tex;
																			$tabcount = $tabcount_tex;
																	}
																}
																
																$receivedDate = date('Y-m-d', strtotime('now'));
																			
																$now = new DateTime();
																$begin = new DateTime('21:00');
																$end = new DateTime('23:59');
																$dueDate = "";
																if ($now >= $begin && $now <= $end)
																{
																	$dueDate = date('Y-m-d', strtotime('now + 3day'));
																}
																else
																{
																	$dueDate = date('Y-m-d', strtotime('now + 2day'));
																}
																
																/*
																* Checking the due date is sunday or not
																* If due date is sunday then add 1 day to due date 
																* If due date is second saturday then add 2 days to due date 
																*/
																
																$timestamp = strtotime($dueDate);
																$weekday= date("l", $timestamp );
																$normalized_weekday = strtolower($weekday);
																
																$firstOfMonth = strtotime(date("Y-m-01", $timestamp));
																$week_number = intval(date("W", $timestamp)) - intval(date("W", $firstOfMonth)) + 1;
													
													
																if($normalized_weekday == "sunday")
																{
																	$dueDate =  date('Y-m-d', strtotime("+1 day", strtotime($dueDate)));
																}
																else if($normalized_weekday == "saturday" && $week_number == "2")
																{
																	$dueDate =  date('Y-m-d', strtotime("+2 day", strtotime($dueDate)));
																}
																
																/// garphics status ////
																$graphics = 0;
																if($figcount > 0)
																{
																	$graphics = 1;
																}
																
																
																//// Get the worflow Steps ////
																//$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '19';";
																if($jn == 'EPL')
																{
																	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '136';";
																}
																else{
																	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '19';";
																}
																$steps = $db->query($query) or ($db->error);
																$deptinfo = $steps->fetch_assoc();
																$dept_info = explode(',',$deptinfo['wf_code']);
																
																
																
																/////// Get the department name /////
																$result = $db->query("SELECT dept_code,dept_name,accept_files FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
																$toatl_count=$result->num_rows;
																$dept_res = array();
																if($toatl_count>0)
																{
																	$dept_res = $result->fetch_assoc();
																}	
																
																/// Get the Customer Details /////
																
																$query = "SELECT id,cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '7' ";
																$customers = $db->query($query) or ($db->error);
																$crow = $customers->fetch_assoc();
																
																
																
																//// Journal name ///	
																if($jn == 'EPL')
																{
																	$query = "SELECT j_name,j_id,j_code FROM `adm_journals` WHERE j_id = '66';";
																}
																else
																{
																	$query = "SELECT j_name,j_id,j_code FROM `adm_journals` WHERE j_id = '67';";
																}
																
																$steps = $db->query($query) or ($db->error);
																$jninfo = $steps->fetch_assoc();
																$journal_title = $jninfo['j_name']; 
																$journal_id = $jninfo['j_id']; 
																
																$Cn= $crow['cust_name'];
																$jn= $jninfo['j_code'];	
																$artile_name= $artile_name;		
																$download_folder = 'downloads';
																$journal_folder = 'JOURNALS';
																$production = 'production';
																
															
																$WF_DIR_LIVE_SYSTEM_PATH = WF_DIR_LIVE_SYSTEM_PATH."/$production/$journal_folder/$Cn/$jn/";
																$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
																
																$destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/FP/";
																$automation_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/";
																
																
																// Get the manuscript count from pdf 
																$manuscript_count = 0; 
																$printPDF = $destination_dir."manuscripts/".$artile_name.'-mscript.pdf';
																if(file_exists($printPDF))
																{
																	$txt = "Before going to manuscript count in perl coding \n";
																	fwrite($myfile, $txt);
																	
																	$perl = 'perl "'.PDFPAGES_PERL.'"  "'.$printPDF.'"';
																	$manuscript_count = exec($perl); 
																	
																	$txt = "Afer  getting manuscript count in perl coding \n";
																	fwrite($myfile, $txt);
																}
																
																$total_fig_count = $figcount;
																$anxillary_fig = 0;
																$automation_status = 0;
																
																//complexity Regular
																//inw_type Word
																$articleTitle = addslashes($articleTitle);
																$journal_title = addslashes($journal_title);
																
																$insert_query = "INSERT INTO `inw_inward_dtl` (`cust_id`,j_id,`job_id`, `journal_title`, `article_title`, `recv_dt`, `due_dt`, `pub_id`, `fig`, `tab`,`created_by`, `created_dt`,stage,wf_id,department_id,work_dir,status,manuscript_count,approved_by,volume_folder,complexity,inw_type, graphics, total_fig_count, anxillary_fig, automation_status, editlatex_project_id, editlatex_token) VALUES ('".$crow['id']."','".$journal_id."', '".$fn."', '".$journal_title."', '".$articleTitle."', '".$receivedDate."', '".$dueDate."', '".$fn."', '".$figcount."', '".$tabcount."', '1', NOW(),'FP','".$deptinfo['wf_id']."','".$dept_info[0]."','".$WF_DIR_LIVE_SYSTEM_PATH."','".$dept_res['dept_name']."','".$manuscript_count."','1','".VOLUME_FOLDER_NAME."','Regular','".$type."', '".$graphics."', '".$total_fig_count."', '".$anxillary_fig."', '".$automation_status."', '".$eplToken."','".$eplProjectId."');";	
																$txt = $insert_query."\n";
																fwrite($myfile, $txt);
		
																$db->query($insert_query) or die($db->error);
																$db_article_id = $db->insert_id;
																$txt = "Artile Workflow ID:".$fn." : ".$db->insert_id."\n";
																fwrite($myfile, $txt);
																$current_dept = $dept_res['dept_name'];
																
																//// Once successfully created the Inward///
																/// We will create the folder structure ///
																/// Move the Files to Production Team ////
																
																$dest = $work_dir.VOLUME_FOLDER_NAME."/".$artile_name."/FP/".$dept_res['dept_name']."/";   // destination folder or file 
																mkdir($dest, 0777, true);
																
																/// IF Figures in Article we create the Graphics Folder ////
																
																/////// Get the department name /////
																
																$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
																$toatl_count=$result->num_rows;
																$dept_res = array();
																if($toatl_count>0)
																{
																	$dept_res = $result->fetch_assoc();
																}
																
																$dest = $work_dir.VOLUME_FOLDER_NAME."/".$artile_name."/FP/".$dept_res['dept_name']."/";   // destination folder or file 
																mkdir($dest, 0777, true);                              
																$dest = $work_dir.VOLUME_FOLDER_NAME."/".$artile_name."/FP/".$dept_res['dept_name']."/Print";   // PAG Folder 
																mkdir($dest, 0777, true);                              
																$dest = $work_dir.VOLUME_FOLDER_NAME."/".$artile_name."/FP/".$dept_res['dept_name']."/Web";   // XML Folder 
																mkdir($dest, 0777, true);	
																
																//// Get password of production@transforma.in mail id //////
																$result = $db->query("SELECT j_from_email_pwd FROM `adm_journals` where j_from_email = 'production@transforma.in' ORDER BY `adm_journals`.`j_from_email_pwd` ASC LIMIT 1"); 	
																$toatl_count=$result->num_rows;
																$dept_res = array();
																if($toatl_count>0)
																{
																	$pass_info = $result->fetch_assoc();
																	
																	/////  Read mail content from production@transforma.in //////////
																	$imapPath = '{outlook.office365.com:993/imap/ssl}INBOX/SIF/EPL';
																	$username = 'production@transforma.in'; /// mail id
																	$password = $pass_info['j_from_email_pwd']; /// password
																	
																	$inbox = imap_open($imapPath,$username,$password); ///authenticate the user credentials
																	$emails = imap_search($inbox,'SUBJECT "Files uploaded for article '.$artile_name.'"');
																	foreach($emails as $mail) 
																	{
																		////// Get the header information from mail /////////
																		$headerInfo = imap_headerinfo($inbox,$mail);
																		$fromsubject = $headerInfo->subject;
																		$fromdate = $headerInfo->date;
																		$fromaddr = $headerInfo->from[0]->mailbox . "@" . $headerInfo->from[0]->host;
																		
																		////// Get the body information from mail /////////
																		$bodyText = "";
																		$bodyText = imap_fetchbody($inbox, $mail,1.1);
																		if(!strlen($bodyText)>0)
																		{
																			$bodyText = imap_fetchbody($inbox, $mail,1);
																		}
																		$result=imap_fetch_overview($inbox, $mail);
																		$message_date=new DateTime($result[0]->date);//Put date result at DateTime constructor
																		$maildate = $message_date->format("Y-m-d");//Formating the DateTime value to MySQL DATETIME format
																		imap_clearflag_full($inbox, $mail, "\\Seen"); /// set mail as unread
																		
																		/////// Write mail content in downloads folder /////////
																		
																		$myfile = fopen($destination_dir.$artile_name."_mail.txt", "w") or die("Unable to open file!");
																		fwrite($myfile, $bodyText);
																		fclose($myfile);
																		break;
																	}
																	// colse the connection
																	imap_expunge($inbox);
																	imap_close($inbox);
																	
																}
																
																
	
															
																//// if its is first then send FP mail to others and do further process ////
											
																$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "5" "'.$journalname.'" "'.$artile_name.'"';
																$rs = exec($perl);
																
															}
														}
													}	
													
													
												
													//// Delete file from files folder
													// unlink($config['DOWNLOAD_PATH'].$filename);
													break;
												/* }
											} */
									//	}
									$txt = "================================================\n";
									fwrite($myfile, $txt);
									}
									else if($ftype == "REV")
									{
										$txt = "started the REV\n";
		$txt .= "For each File name:".$ftype."\n";
		fwrite($myfile, $txt);
										// while(1)
									//	{
											$txt = "While loop REV\n";
		$txt .= "For each File name:".$ftype."\n";
		fwrite($myfile, $txt);
											/* if(@copy($config['DOWNLOAD_PATH_SIF'].$filename , $config['AUTOMATION_PATH_SIF_REV'].$filename ))
											{  */ 
												/* if (file_exists($config['AUTOMATION_PATH_SIF_REV'].$filename)) 
												{ */
													/*
													* Initialize the recive date and due date
													*/
													
													$now = new DateTime();
													$begin = new DateTime('18:30');
													$end = new DateTime('23:59');
													$dueDate = "";
													$receivedDate = "";
													if ($now >= $begin && $now <= $end)
													{
														$dueDate = date('Y-m-d', strtotime('now + 3day'));
														$dueDate = date('Y-m-d', strtotime('now + 3day'));
														$receivedDate = date('Y-m-d', strtotime('now + 1day'));
													}
													else
													{
														$dueDate = date('Y-m-d', strtotime('now + 2day'));
														$receivedDate = date('Y-m-d', strtotime('now'));
													}
																
													/*
													* Checking the due date is sunday or not
													* If due date is sunday then add 1 day to due date 
													* If due date is second saturday then add 2 days to due date 
													*/
													
													$timestamp = strtotime($dueDate);
													$weekday= date("l", $timestamp );
													$normalized_weekday = strtolower($weekday);
													
													$firstOfMonth = strtotime(date("Y-m-01", $timestamp));
													$week_number = intval(date("W", $timestamp)) - intval(date("W", $firstOfMonth)) + 1;
													
													
													if($normalized_weekday == "sunday")
													{
														$dueDate =  date('Y-m-d', strtotime("+1 day", strtotime($dueDate)));
													}
													else if($normalized_weekday == "saturday" && $week_number == "2")
													{
														$dueDate =  date('Y-m-d', strtotime("+2 day", strtotime($dueDate)));
													}
													
													//// Get the worflow Steps ////
													$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '20';";
													$steps = $db->query($query) or ($db->error);
													$deptinfo = $steps->fetch_assoc();
													$dept_info = explode(',',$deptinfo['wf_code']);
													
													/////// Get the department name /////
													$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
													$toatl_count=$result->num_rows;
													$dept_res = array();
													if($toatl_count>0)
													{
														$dept_res = $result->fetch_assoc();
													}
														
														
													
																/// Get the Customer Details /////
																
																$query = "SELECT id,cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '7' ";
																$customers = $db->query($query) or ($db->error);
																$crow = $customers->fetch_assoc();
																
																
																
																//// Journal name ///	
																if($journalname == 'EPL')
																{
																	$query = "SELECT j_name,j_id,j_code FROM `adm_journals` WHERE j_id = '66';";
																}
																else
																{
																	$query = "SELECT j_name,j_id,j_code FROM `adm_journals` WHERE j_id = '67';";
																}
																
																$steps = $db->query($query) or ($db->error);
																$jninfo = $steps->fetch_assoc();
																
																
																$file_ary = explode("-",$filename);
															$fn=$file_ary[3];
															$artile_name= $fn;	
															
																$Cn= $crow['cust_name'];
																$jn= $jninfo['j_code'];	
																
													$artile_name= $artile_name;		
													$download_folder = 'downloads';
													$production_folder = 'production';
													$journal_folder = 'JOURNALS';
													$production = 'production';		
																
													// current directory
													//echo getcwd() . "\n";
													
													$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
													$txt = "Creating the folders\n";
		$txt .= "For each File name:".$work_dir."\n";		
		fwrite($myfile, $txt);
													
													 $destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/REV1/";
													$WF_DIR_LIVE_SYSTEM_PATH = WF_DIR_LIVE_SYSTEM_PATH."/$production/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/REV1/";
													if (!(is_dir($destination_dir))) {
													if (!mkdir($destination_dir, 0777, true)) {
													die('Failed to create folders...$jn');
													}
													}
													echo $destination_dir;
										if (strrpos($filename, '.zip') === strlen($filename)-strlen('.zip'))
										{
											$zip = new ZipArchive;
											$res = $zip->open($config['DOWNLOAD_PATH_SIF'].$filename);	
											//var_dump($res);		
											if ($res === TRUE)
											{
												$zip->extractTo($destination_dir);
												$zip->close();  
											}
										}
										else
										{
											$Path     = $destination_dir;
											move_uploaded_file($config['DOWNLOAD_PATH_SIF'].$filename, $Path.$filename);	
											
										}
											
/// Move the Previos stage production Files current stage production ///



		$production_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/FP/";
		$txt = "Creating the folders\n";
		$txt .= "For each File name:".$production_dir."\n";		
		fwrite($myfile, $txt);
		
		$production_dest_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME."/$artile_name/REV1/";
		frmove($production_dir, $production_dest_dir);
		
		$txt = "Creating the folders\n";
		$txt .= "For each File name:".$production_dest_dir."\n";		
		fwrite($myfile, $txt);
		
		//// Crete the production Next Dept folder ///
		if (!(is_dir($production_dest_dir.$dept_res['dept_name']))) 
		{
			if (!mkdir($production_dest_dir.$dept_res['dept_name'], 0777, true)) 		{
				$txt = "Failed to create folders\n";
		$txt .= "For each File name:".$production_dest_dir.$dept_res['dept_name']."\n";		
		fwrite($myfile, $txt);
				die('Failed to create folders...'.$production_dest_dir.$dept_res['dept_name']);
			}
		}					

			$query = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$fn."'"; 
			$inw_inward_dtls = $db->query($query) or ($db->error);
			$inw_inward_dtl = $inw_inward_dtls->fetch_assoc();			
				
			// echo $inw_inward_dtl['page_count'];
			
				
	 $insert_query = "INSERT INTO `inw_revisions_dtl` (`inw_id`,revision_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`, `type`) VALUES ('".$inw_inward_dtl['id']."','1','".$receivedDate."', '".$dueDate."', '".$inw_inward_dtl['page_count']."', '', '".$WF_DIR_LIVE_SYSTEM_PATH."','1', NOW(),'REV');";	
	
	//echo $insert_query; exit;
	$txt = "insert REV Query\n";
		$txt .= "Query:".$insert_query."\n";		
		fwrite($myfile, $txt);
	$result_db_status = $db->query($insert_query) or die($db->error);
	/// update the stage and department and status in inw details table /////
$txt = "insert REV Query\n";
		$txt .= "Query Status:".$result_db_status."\n";		
		fwrite($myfile, $txt);

	$prj_update_query="UPDATE inw_inward_dtl SET stage = 'REV1', wf_id = '".$deptinfo['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = '".$inw_inward_dtl['id']."' ";
	$txt = "Update REV Query\n";
		$txt .= "Query:".$prj_update_query."\n";		
		fwrite($myfile, $txt);
	
	$result_db_status = $db->query($prj_update_query) or die($db->error);
	
	$txt = "Update REV Query\n";
		$txt .= "Query Status:".$result_db_status."\n";		
		fwrite($myfile, $txt);

	
	
																//// Get password of production@transforma.in mail id //////
																$result = $db->query("SELECT j_from_email_pwd FROM `adm_journals` where j_from_email = 'production@transforma.in' ORDER BY `adm_journals`.`j_from_email_pwd` ASC LIMIT 1"); 	
																$toatl_count=$result->num_rows;
																$dept_res = array();
																if($toatl_count>0)
																{
																	$pass_info = $result->fetch_assoc();
																	
																	/////  Read mail content from production@transforma.in //////////
																	$imapPath = '{outlook.office365.com:993/imap/ssl}INBOX/SIF/EPL';
																	$username = 'production@transforma.in'; /// mail id
																	$password = $pass_info['j_from_email_pwd']; /// password
																	
																	$inbox = imap_open($imapPath,$username,$password); ///authenticate the user credentials
																	$emails = imap_search($inbox,'SUBJECT "Files uploaded for article '.$artile_name.' - Authors corrections"');
																	foreach($emails as $mail) 
																	{
																		////// Get the header information from mail /////////
																		$headerInfo = imap_headerinfo($inbox,$mail);
																		$fromsubject = $headerInfo->subject;
																		$fromdate = $headerInfo->date;
																		$fromaddr = $headerInfo->from[0]->mailbox . "@" . $headerInfo->from[0]->host;
																		
																		////// Get the body information from mail /////////
																		$bodyText = "";
																		$bodyText = imap_fetchbody($inbox, $mail,1.1);
																		if(!strlen($bodyText)>0)
																		{
																			$bodyText = imap_fetchbody($inbox, $mail,1);
																		}
																		$result=imap_fetch_overview($inbox, $mail);
																		$message_date=new DateTime($result[0]->date);//Put date result at DateTime constructor
																		$maildate = $message_date->format("Y-m-d");//Formating the DateTime value to MySQL DATETIME format
																		imap_clearflag_full($inbox, $mail, "\\Seen"); /// set mail as unread
																		
																		/////// Write mail content in downloads folder /////////
																		
																		$myfile = fopen($destination_dir.$artile_name."_mail.txt", "w") or die("Unable to open file!");
																		fwrite($myfile, $bodyText);
																		fclose($myfile);
																		break;
																	}
																	// colse the connection
																	imap_expunge($inbox);
																	imap_close($inbox);
																	
																}
																

	//// if its is first then send FP mail to others and do further process ////
											
													 $perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "6" "'.$journalname.'" "'.$filename.'" "'.$config['AUTOMATION_PATH_SIF_REV'].$journalname."\\'";
													$rs = exec($perl);
												
													//// Delete file from files folder
													// unlink($config['DOWNLOAD_PATH'].$filename);
													break;
												// }
											/* } */
//										}
									}
									else
									{
										while(1)
										{
											if(@copy($config['DOWNLOAD_PATH_SIF'].$filename , $config['AUTOMATION_PATH_SIF'].$journalname."\\".$filename ))
											{  
												if (file_exists($config['AUTOMATION_PATH_SIF'].$journalname."\\".$filename)) 
												{
													//// if its is first then send FP mail to others and do further process ////
											
													$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "5" "'.$journalname.'" "'.$filename.'"';
													$rs = exec($perl);
												
													//// Delete file from files folder
													// unlink($config['DOWNLOAD_PATH'].$filename);
													break;
												}
											}
										}
									}
									
								}
								else
								{
									$dup_count = $result->num_rows;
									$recount = addOrdinalNumberSuffix($dup_count) . "\t";
									
									//// Move file from Files forlder to DUPLICATED_PATH folder ////
									$filenameid = str_replace(".tar.bz2","",$filename);
									$filenameid = str_replace(".tar","",$filename);
									$filenameid = str_replace(".bz2","",$filenameid);
									
									$DUPLICATED_PATH = $config['DUPLICATED_PATH_SIF'].$filenameid;		

									if (!(is_dir($DUPLICATED_PATH))) 
									{
										if (!mkdir($DUPLICATED_PATH, 0777, true)) {
											
										}
									}
									
									$DUPLICATED_PATH .= "\\".$dup_count;
									if (!(is_dir($DUPLICATED_PATH))) 
									{
										if (!mkdir($DUPLICATED_PATH, 0777, true)) {
											
										}
									}
									
									if(@copy($config['DOWNLOAD_PATH_SIF'].$filename , $DUPLICATED_PATH."\\".$filename))
									{
										//// if its is first then send FP mail to others and do further process ////
									
										$mailids_to = MAIL_RP_TO;
										$mailids_cc = MAIL_RP_CC;
										$mailids_bcc = MAIL_RP_BCC;
										
										//// Send mail to more than one user ///
										$mail_to = explode(',',$mailids_to);
										foreach($mail_to as $email)
										{
											if($email!='')
											{
												$mail->addAddress($email);
											}
										}
										/////////// End here send mail ////////
										
										
										$mail->AltBody    = ""; // optional, comment out and test
										
										$mail_tocc 		= explode(',',$mailids_cc);
										foreach($mail_tocc as $email)
										{
											if($email!='')
											{
												$mail->AddCC($email);
											}
										}
										
										$mail_tobcc 		= explode(',',$mailids_bcc);
										foreach($mail_tobcc as $email)
										{
											if($email!='')
											{
												$mail->AddBCC($email);
											}
										}
										
										$filenameid = str_replace(".tar.bz2","",$filename);
										$filenameid = str_replace(".tar","",$filename);
										$filenameid = str_replace(".bz2","",$filenameid);
									
										
										$mail->Subject = $filenameid." First proof Re-Downloaded(".$recount.") Successfully!";
										
										$mail_body = file_get_contents(SIF_MailTemplate_RP);
										
										$mail_body = str_replace("#ClientName#","SIF",$mail_body);
										$mail_body = str_replace("#JournalName#",$journalname,$mail_body);
										$mail_body = str_replace("#FileName#",$filename,$mail_body);
										$mail_body = str_replace("#Filepath#",$DUPLICATED_PATH,$mail_body);
										$mail_body = str_replace("#TimesRevd#",$dup_count,$mail_body);
										
										$mail->Body = $mail_body;	
												
										
										if(!$mail->Send()) 
										{
											//// Send the mail using intar mail 
											// echo "Mailer Error: " . $mail->ErrorInfo;
										} 
										
										//// Delete file from files folder
										// unlink($config['DOWNLOAD_PATH'].$filename);
										
									}
									else
									{
										//// Send mail notification to file copy error
									}
								}
								//// if its more then once then send mail to managers with file attachement //// 
							}
							
						}
						else
						{
							//// FTP Not Connected or file dounload error ////
							/// To Send Mail Notification ///
							
						}
								//// if its more then once then send mail to managers with file attachement //// 
					}
					else
					{
						
						///// Updating the file size in  DB //// 
						$sql1 = "UPDATE trans_ftp_sif SET dateTime='".date("Y-m-d H:i:s")."', filesize='".$filesize."' WHERE sno=".$row["sno"]."";
						if ($mysqli->query($sql1) === TRUE) 
						{	
						}
					}
					break;
				}
			}
			
		}
			
					
	}
	else
	{
		
				
			$Internet   = "Internet connection not active";  /// Internet connection check
			$FTP_Down   = "FTP Down";  /// checking the FTP Status
			$FTP_User_details   = "FTP User details invalid";  /// checking the FTP User details
			$FTP_Directory   = "FTP To-Transforma directory not found";  /// checking the FTP directory
			
			if( strpos( $rs, $Internet ) !== false) 
			{
				/// Send mail when internet disconnect ////
				$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "1"';
				$rs = exec($perl);
				
			}
			else if( strpos( $rs, $FTP_Down ) !== false) 
			{
				/// Send mail when FTP Connection Down ////
				$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "2"';
				$rs = exec($perl);								
			}			
			else if( strpos( $rs, $FTP_User_details ) !== false) 
			{
				/// Send mail when Invalid FTP Credentials ////
				$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "3"';
				$rs = exec($perl);
				
			}			
			else if( strpos( $rs, $FTP_Directory ) !== false) 
			{
				/// Send mail when Invalid Directory Structure ////
				$perl = 'perl "'.$config['SMTPMAIL_SIF'].'" "4"';
				$rs = exec($perl);				
			}
	}
	
	  /*function addOrdinalNumberSuffix($num) {
    if (!in_array(($num % 100),array(11,12,13))){
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1:  return $num.'st';
        case 2:  return $num.'nd';
        case 3:  return $num.'rd';
      }
    }
    return $num.'th';
  } */
  
  

/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function frmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				frmove($f->getRealPath(), "$dest/$f");
				unlink($f->getRealPath());
				if(empty($f))
				{
					unlink($f);
				}
			}
        }
    }
    //unlink($src);
}

?>
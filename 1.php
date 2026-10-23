<?php 
  if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	
		
	//// Get the article details ////
	$query = "SELECT `cust_id`,`j_id`,`activity` FROM `inw_inward_dtl` WHERE id = '".$post['id']."';";
	$articledetails = $db->query($query) or ($db->error);
	$artilce_info = $articledetails->fetch_assoc();
	
	//// Get the journal details ////
	$query = "SELECT `tvl_work_departments`,`j_proofing_system_server` FROM `adm_journals` WHERE j_id = '".$artilce_info['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	//// Here we will get the department's to work in TVL //
	$tvl_dept_info = explode(',',$journalinfo['tvl_work_departments']);	
	
	
	
		
		$query = "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '".$post['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code,j_hid FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();
		
		$Cn= $crow['cust_name'];
		$J_name = $jninfo['j_code']; 	
		
		mysqli_query($db,"SET sql_mode = ''");
		
		
		if($post['dept_name']=="PRQC" && $post['cust_id']=="66" && $post['stage']!="FP")
		{
		$prj_update_querys=mysqli_query($db,"UPDATE  inw_meta_data SET  first_page ='".$post['fpage']."', last_page ='".$post['lpage']."' WHERE inw_id = ".$post['id']." ");
		}
		
		//if($post['status'] == 1)
	//{
		if($post['dept_name']=="PRQC" && $post['cust_id']=="42" && $post['stage']=="FP")
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  corresponding_author ='".$post['corresponding_author']."', corresponding_mail ='".$post['corresponding_mail']."' WHERE id = ".$post['id']." ");
	
		}
	//}
		
	if($post['status'] == 1 || $post['status'] == 6 )
	{
	//$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Completed',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."' WHERE id = ".$post['tr_id']." ";	
			$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ";

	}
	else
	{
		
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ";
		
		if($post['status'] == 7)
		{
			$user_jour=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$jninfo['j_hid']."'"));
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['user_id']."'"));
			if($user_jour['user_email']!="")
			{
				$from_name=$user_jour['full_name'];
				$to=$user_jour['user_email'];
			}
			else
			{
				$from_name='Bosco';
				$to='bosco@transforma.in';
			}
			
			
			@$body ="<table height='90' cellspacing='4' cellpadding='4' width='800' align='center' bgcolor='#ffffff' border='0' style='border:3px solid #cccccc'>
  <tbody>
   <tr>
    <td width='300'><a href='#' target='_blank'><img src='http://transforma.co:8005/workflow/assets/images/trans-logo.png' height='70' width='233' /></a></td>
    <td width='567'>
<table>
   
    <tr>
<td><hr></td>
    </tr>
   
</table>
    </td>
  </tr>   
 
    <tr>
      <td valign='middle' align='left' width='533' colspan='2'> Dear  ".@$from_name." ,<br><br>
	  
	 Query Notification for the article no :".$post['pub_id'].":<br><br> 


		File name: ".$post['pub_id']."<br><br> 
		Comment : ".$post['message_query']."
		<br>
       <br>
        Thanks and Regards,<br><br>
		".$user_jour_cc['full_name']."<br> 
		Transforma Pvt Ltd <br>        
         </td>
    </tr>
  </tbody>
</table>";
	
//echo $dest.$post['pub_id'].".pdf";exit;
@$sendto =$to; 
$subject = "Query Notification for the article no :".$post['pub_id']."";	
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $user_jour_cc['full_name'];
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;
//$mail_tocc	= $user_jour_cc['user_email'];
$mail_tocc	="pm@transforma.in";
$mail_tobcc	= "techsupport@transforma.in";
$mail->addAddress($sendto);	
$mail->addAddress($mail_tocc);	
$mail->addAddress($mail_tobcc);
$mail->Send();	
//echo "insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."')";exit;
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','Query')");
			
		}
	}
	
	if($db->query($update_query))
	{
		if($post['assign_department']=='')
		{
			if($post['status'] == 1 && $post['cust_id']==33)
			{
				if($post['department_id']==22)
				{
				include("pre-clean.php");
				}
			}
			if($post['status'] == 1 && $post['cust_id']==42)
			{
				if($post['department_id']==22)
				{
				include("pre-clean-tsp.php");
				}
			}
			if($post['status'] == 1 && $post['cust_id']==42 && $post['department_id']==1)
			{
				$ce_perl_file = "E:/Transforma/Word2HTML/Exe/METACONFIG.pl";
				$dest_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/CS/".$post['pub_id'].".xml";   // destination folder or file 
				$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$ce_perl_file.'" "'.$dest_xml_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1"';
				system($perl_update_article);				
			}
			//// Get the workflow Steps ////
			$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
			$steps = $db->query($query) or ($db->error);
			$deptinfo = $steps->fetch_assoc();
			$dept_info = explode(',',$deptinfo['wf_code']);
			$dept_key = array_keys($dept_info, $post['department_id']);		
					
			$dept_key = $dept_key['0'];			
			$dept_key = ($dept_key + 1);
			
			if($post['skip_dept'] != "" && $post['skip_dept'] > 0)
			{
				$department_id = $post['skip_dept'];
			}			
			else if($post['status'] == 1 || $post['status'] == 12)
			{			
				$department_id = $dept_info[$dept_key];
			}
			else
			{
				$department_id = $post['department_id'];
			}
			
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name,accept_files FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}	
			
		
		$err = array();
		///// NCC Client Check List ////
		if($dept_res['dept_name'] == 'Delivery')
		{	
			$tex_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
			//include('ncc_check_list.php');
		}
		if(count($err) == 0)
		{
			/// We update the page count when PRQC Team enters the DATA ////
			$page_count = '';
			if($post['page_count'] > 0)
			{
				$page_count = "page_count ='".$post['page_count']."',";
				$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");

			} 
						
			$corresponding_author_details = "";
			
			if($post['dept_name'] == 'FXML' && $post['stage'] == "FP" )
			{
				$xml_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".xml";
				
				if(file_exists($xml_file_path))
				{
				$html = file_get_contents($xml_file_path);
				
				$html = preg_replace("/(\n|\r|\r\n)/i", " ", $html);
				//// Get the title from XML file ////
				$title = explode("</title-group>",$html);
				$title = preg_replace("/(.*)(<article-title>)(.*)(<\/article-title>)(.*)/i", "$3", $title[0]);
				//// Get the author details from XML file ////
				$corresp = str_replace("<bold>", "", $html);
				$corresp = str_replace("</bold>", "", $corresp);
				$corresp = preg_replace("/(.*)(<corresp (.+?)>)(.*)(<\/corresp>)(.*)/i", "$4", $corresp);
				$corresp = preg_replace("/(.+?)(:\s+)(.*)/i", "$3", $corresp);
				$corresp = preg_replace("/((\,)\s+)/i", "", $corresp);
				$corresp = preg_replace("/(Email\:)/i", "", $corresp);				

				$corresps = explode("; ",$corresp);
				
				
				
				$corresp_name = "";
				$corresp_mail = "";
				if(sizeof($corresps) > 0)
				{
				for($i = 0 ; $i < sizeof($corresps); $i++)
				{
						$corresps_au = $corresps[$i];
						$corresps_au_name = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$1", $corresps_au));
						$corresps_au_name = trim(preg_replace("/(.*)(\.)/i", "$1", $corresps_au_name));
						$corresps_au_mail = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$3", $corresps_au));
				
				
				if($corresp_name !="")
				{
					$corresp_name .= ", ".$corresps_au_name;
				}
				else
				{
					$corresp_name .= $corresps_au_name;
				}
				
				
				if($corresp_mail !="")
				{
					$corresp_mail .= ",".$corresps_au_mail;
				}
				else
				{
					$corresp_mail .= $corresps_au_mail;
				}
				}
				
				}
				$corresponding_author_details = "corresponding_author ='".$corresp_name."', corresponding_mail ='".$corresp_mail."', article_title='".$title."', ";
				
				}

			}	
			//}
			$prj_update_query="UPDATE inw_inward_dtl SET ".$corresponding_author_details ." ".$page_count." `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`assign_department_id` ='0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."', present_user_id = '0' WHERE id = ".$post['id']." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			/// If completed the work files we will move to other department ////
			if($post['status'] == 1  || $post['status'] == 12)
			{
			///// Insert the checklist data to DB //////////
			foreach($_POST['checklist'] as $cl_id=>$value)
			{
				$insert_query = "INSERT INTO `inw_to_checklist` (`inw_id`,dept_id,stage,`cl_id`, `submited_value`, `created_by`, `created_on`) VALUES ('". $post['id']."', '".$post['department_id']."', '".$post['stage']."','". $cl_id."','". $value."', '". $_COOKIE['user_id']."', CURRENT_TIMESTAMP);";	
				//$db->query($insert_query) or die($db->error);
			}
			
				/// Move the Files From On Dept to another dept ////
				
					
	$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/";   // source folder or file
	



///// Get the Accept files from New Configuration based on Customer/Journal/Dept/Stage//////

$fe_query = "SELECT mf_id FROM `inw_move_files` WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND dept_id = '".$post['department_id']."';";
$fe_result = $db->query($fe_query) or ($db->error);
$fe_info = $fe_result->fetch_assoc();



//// According to New configuration we are moving the files /////
$cur_stage = preg_replace('/[0-9]+/', '', $post['stage']);
$fef_query = "SELECT move_files,move_folders,dept_id FROM `inw_move_files_dept_wise` WHERE mf_id = '".$fe_info['mf_id']."' AND stage = '".$cur_stage."';";
$fef_result = $db->query($fef_query) or ($db->error);
while($fef_info = $fef_result->fetch_assoc())
{
	/// Create the dept destination folder /////
	$dept_result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$fef_info['dept_id'].";");
	$dept_name = $dept_result->fetch_assoc();
	
	$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
	//mkdir($dest, 0777, true);
	//echo $dest.'<br>';
	
		
	
	
			//zip directory check validation //
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
				$dest_err = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
				
				if($post['stage']=='FP')
				{
				 $stage_pos=1;	
				}
				else
				{
				 $stage_pos=2;		
				}
				$stage=preg_replace("/REV([0-9]+)/","REV",$post['stage']);
				$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$post['stage']);

				//echo $stage;exit;
				if($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				$perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148' )
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage_f=='FIN' && $post['cust_id']==66 && $post['dept_name']=='ePub' && $post['wf_id']=='154')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage_f=='FIN' && $post['cust_id']==42 && $post['dept_name']=='XML' && $post['wf_id']=='162')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage=='REV' && $post['cust_id']!=66 && $post['dept_name']=='XML')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
			}
	
		//zip directory check validation //
		
		
	///// Moving Files ////////
	if($fef_info['move_files']!='')
	{
		$accept_files_list = explode("|",$fef_info["move_files"]);
		if(count($accept_files_list)>0)
		{
			if(!is_dir($dest))
			{
				///echo $dest.'<br>';
				mkdir($dest, 0777, true);
			}
			
					if($J_name!='EPL' && $J_name!='NCC' && $post['cust_id']!=33)
				{
			rcopy($src,$dest,$accept_files_list);
				}
				elseif($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
				{
						if($post['stage']!='FP' && $post['dept_name']!='PRQC')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
						if($stage!='REV' && $post['dept_name']!='XML')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
						if($post['stage']!='REV2' && $post['dept_name']!='ePub')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
				}
			
			 //// Copy the Files from production folder to another system ///////
			/* 
			$work_dir = $_POST['work_dir'];
			$work_dir = str_replace(WF_DIR,BACKUP_DIR,$work_dir);
			$Backup_dest = $work_dir.$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
			if (!(is_dir($Backup_dest))) 
			{
				if (!mkdir($Backup_dest, 0777, true)) 
				{
					die('Failed to create folders...$jn');
				}
			}	
			rcopy($src,$Backup_dest,$accept_files_list);  */
			
		 
		
		}
		
			//copying batch files to pag folder for automation process
		
	
		
	}
	
	///////// Moving Folders ///////
	if($fef_info['move_folders']!='')
	{
		$accept_folders_list = explode("|",$fef_info["move_folders"]);
		foreach($accept_folders_list as $folder)
		{
			
			if(is_dir($fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder))
			{
				$fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder; 		
				$fdest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/".$folder; 
				mkdir($fdest, 0777, true);
				if($J_name!='EPL' && $J_name!='NCC' && $post['cust_id']!=33)
				{
				frcopy($fsrc,$fdest);
				}
				elseif($J_name=='EPL'  || $J_name=='NCC' || $post['cust_id']==33)
				{
						if(($post['stage']!='FP' && $post['dept_name']!='PRQC') && ($stage!='REV' && $post['dept_name']!='XML'))
						{	
						frcopy($fsrc,$fdest);
						}
				}
			}
			
			
		}
	}
	
	
	
		//zip directory check validation //	
		$dest_errc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/Delivery/";   // destination folder or file 
		
		 $dests=$dest_errc.$post['pub_id'].".zip";//exit;
		 
		//echo $stage;echo "<br/>";exit;
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
	
			if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub' && $post['wf_id']=='154') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML' && $post['wf_id']=='162') || ($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148'))
			{
				
				//echo "eos-fp-prqc";exit;

		 $html_read = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['pub_id']."_report.html";   // destination folder or file 
		 $refdata = file_get_contents($html_read); 

			//echo $html_read;
		$success=preg_match('/<body class=\"SUCCESS\">/i', $refdata);
		//echo $success;exit;
			if($success==1)
			{
				//echo "1";exit;
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");

			}
			else
			{
					//echo $stage;echo "<br/>";
					//echo "2";
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET `status` ='".$post['dept_name']."',`department_id` ='". $post['department_id']."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
			//echo "UPDATE inw_transactions SET `status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."' WHERE id = ".$post['tr_id']." ";exit;
			$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$page_count."',`status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");
			}
		  }
			}
		//  echo $stage;echo "<br/>";
		//  echo $post['dept_name'];exit;
		  
		  //zip directory check validation //
		    
			///// Artile Details /////		
			
	//exit;
}


if($post['cust_id']==42 && $post['status']==1 && $post['stage']='FP' && $post['department_id']==1 )
		{
			$ps2pdf='PS2PDF.bat';
			$xmltopdf='XML2PDF.bat';
			$dest_pag=$_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/FP/PAG/";
				if (!is_dir($dest_pag)) 
				{
					mkdir($dest_pag, 0777, true);
				}
		
			   //copy($ps2pdf, $dest_pag.$ps2pdf);
			   //copy($xmltopdf, $dest_pag.$xmltopdf);
		} 
		
			}


		} //// NCC Check List End here ////
		
////////////////////////////////////////
////////////////////////////////////////
		
			//FP
			
			$tsp_current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			
			
			//DISPATCH ARTICLES TO AUHTOR VIA XPERTTRACK 

			if($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='1')
			{
						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175')
				{
					///// REV1: Send REV1 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_above_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_above_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("xpertrack_tsp_email_rev1_above_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev1_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			  
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 

			}
			elseif($tsp_current_stage == 'FIN' && $post['xperttrack_sync']=='1')
			{
						
				if($post['cust_id']=='42' && $post['status']==1 && $post['department_id']=='5')
				{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_final_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_final_regular_division.php");
						}
				}

					
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 
			}
			elseif($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='0')
			{
			//DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 

						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175')
				{
					///// REV2: Send REV2 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_email_rev1_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV2' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("tsp_ftp_rev_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_ftp_rev_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("tsp_ftp_rev_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("tsp_ftp_rev_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("tsp_ftp_rev_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("tsp_email_rev1_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			
			}
			
		//DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 
		
		
		}
		else
		{
			
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$post['assign_department'].";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			///// Create a assign department folder if not exists ////
			/// Added on 01-10-2019 //////
			$ass_dept_chk = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/";
			if(!is_dir($ass_dept_chk))
			{				
				mkdir($ass_dept_chk, 0777, true);				
			}
			//// END create assign folder /////	
				
			$assign_dept_status = $dept_res['dept_name']." | Assigned | ".$post['dept_name'];
			
			if($dept_res['dept_name'] == 'Delivery')
			{
				$assign_dept_status = $dept_res['dept_name'];
			}	
			
			//$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$dept_res['dept_name']." | Assigned | ".$post['dept_name']."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$assign_dept_status."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$db->query($prj_update_query);
		}
		
		
		
		
		// Mail to TSP
		
		
		
if($post['cust_id']==42 && $post['dept_name']=='PRQC' && $post['status'] == 1)	
{
	
	
	@$body ="<table height='90' cellspacing='4' cellpadding='4' width='800' align='center' bgcolor='#ffffff' border='0' style='border:3px solid #cccccc'>
  <tbody>
   <tr>
    <td width='300'><a href='http://transforma.co:8005/Workflow/customer/' target='_blank'><img src='http://transforma.co:8005/workflow/assets/images/trans-logo.png' height='70' width='233' /></a></td>
    <td width='567'>
<table>
    <tr>
  <td width='300'><a href='https://www.techscience.com/' target='_blank'><img src='https://www.techscience.com/images/logo.jpg' /></a></td>
    </tr>
    <tr>
<td><hr></td>
    </tr>
   
</table>
    </td>
  </tr>   
 
    <tr>
      <td valign='middle' align='left' width='533' colspan='2'> Dear  ".@$crow['cust_name']." Editor's,<br><br>
	  
	 You can use the below link to review and edit the content, please open the below mentioned link in Google Chrome or Mozilla Firefox:<br><br> <a href='http://transforma.co:8005/Workflow/customer/index.php'>http://transforma.co:8005/Workflow/customer/index.php </a><br><br>User Name  ".@$crow['email']." <br> Password: ".@$crow['password']." <br /><br /> 


		File name: ".$post['pub_id']."
		<br>
       
       
        Thanks and Regards,<br><br>
		Team<br> 
		Transforma Pvt Ltd <br>        
         </td>
    </tr>
  </tbody>
</table>";
	
	//@$sendto = @$crow['email']; 
	//echo $dest.$post['pub_id'].".pdf";exit;
@$sendto ="techsupport@transforma.in"; 
$subject = "Customer Notification for the manuscript no :".$post['pub_id']."";	
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;
//$mail->AddAttachment($_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".pdf");      // attachment
//$mail_tocc	= "techsupport@transforma.in";
//$mail_tocc	= "ganeshkoumar@transforma.in";
//$mail_tobcc	= "tec0304@transforma.co";
//$mail_tobcc	= "sathish@transforma.in";
$mail->addAddress($sendto);	
//$mail->addAddress($mail_tocc);	
//$mail->addAddress($mail_tobcc);
// $mail->Send();	
}	
		
		if($post['status']==7)
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='Query',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
		//echo "UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW() WHERE id = ".$post['tr_id']." ";exit;
		$prj_update_querys_trans=mysqli_query($db,"UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW(),`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");
		}
		//// adding the logic to update the status in inw_inward_details table to fetch data directly

$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$post['department_id']."'"));

if($post['status'] == 1)
{
	
	$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>';
	if($dept_res['dept_name'] == 'Delivery')
	{
		$user_working_status = '';
		$graphic_working_status = ", graphic_working_status = ''";
	}
}
else if($post['status'] == 6)
{ 
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
else if($post['status'] == 7)
{
    $user_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
//$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";		
$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."', last_transaction_id  = ".$post['tr_id']." ".$graphic_working_status." WHERE id = ".$post['id']."; ";			
	
$db->query($prj_update_query);
//// End logic Here 																						   
					
		///// Artile Details /////
		
			if($_POST['v_issue_id'] > 0)
			{
				header("location:show_issues.php?id=".$_POST['v_issue_id']."&msg=Successfully completed"); 
			}
			else
			{
				if($_POST['assign_department']=='')
				{
					if($J_name=='EPL' ||  $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
					{
				if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub' && $post['wf_id']=='154') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML' && $post['wf_id']=='162') || ($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148'))
				{				   

					if($success==1 && $post['status']==1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					elseif($success==1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>"; 
					}
					elseif($success!=1 && $post['status']==2)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					elseif($success!=1 && $post['status']==1)
					{
				    header("location:".$_COOKIE['set_url']."/startwork/".$post['id']."/new?msg=Error Please check the package&html"); 
					}
					elseif($success!=1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
				}
				else
				{
				echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
				}
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
				}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
			}		
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////
?>
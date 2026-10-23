<?php
include "includes/includes.php";
$query = "SELECT * FROM `inw_inward_dtl` WHERE id='".$_REQUEST['a_id']."'";
	$fileinfo = $db->query($query) or ($db->error);
	$app_res = $fileinfo->fetch_assoc();
	
	/// Get the Customer details ///
	$query = "SELECT * FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
	
	//// Get the journal details ////
	$query = "SELECT j_code FROM `adm_journals` WHERE j_id = '".$app_res['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	
	$xml_name = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".xml";
	$log_name = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id']."_validation.log";
	$epub_name = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".epub";
	$epub_path = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id']."";
	$html_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].""."/".$app_res['pub_id'].".xhtml";

	$work_dir = str_replace(WF_DIR,TOOL_DIR,$app_res['work_dir']);

	$map_html_file = $work_dir .$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].""."/".$app_res['pub_id'].".xhtml";
	$graphics_path = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Graphics/Web";
	
	$xml_name = str_replace("/","\\",$xml_name);
	$graphics_path = str_replace("/","\\",$graphics_path);
	$epub_path = str_replace("/","\\",$epub_path);
	
	/* echo $perl;
	exit; */
	$message = "";
	$status = 0;

	
		$html_url = "";

	if($_REQUEST['type'] == "1")
	{
		if (file_exists($xml_name)) 
		{
			$perl = "perl ".EPUB_GENERATION_PERL_PATH." \"". $xml_name."\" \"".$_REQUEST['type']."\"  \"".$graphics_path."\" \"". $customer_info['cust_name']. "\" \"".$journalinfo['j_code']."\"";
			exec($perl);
			if (!file_exists($log_name)) 
			{
				$status = 1;
				$message = "ePub Generation completed";
			}
			else
			{	
				$status = 0;
				$message = "Error in ePub Generation, Please check log file";
			}	
		}
		else
		{
			$status = 0;
			$message = "XML file not exists, Please check it";
		}
	}
	else if($_REQUEST['type'] == "2")
	{
		if (file_exists($epub_name)) 
		{
			$perl = "perl ".EPUB_GENERATION_PERL_PATH." \"". $epub_name."\" \"".$_REQUEST['type']."\"  \"".$graphics_path."\" \"". $customer_info['cust_name']. "\" \"".$journalinfo['j_code']."\"";
			exec($perl);
			if (!file_exists($log_name)) 
			{
				$status = 1;
				$message = "ePub Validaion completed";
			}
			else
			{	
				$status = 0;
				$message = "Error in ePub Validaion, Please check log file";
			}	
		}
		else
		{
			$status = 0;
			$message = "ePub file not exists, Please check it";
		}
		
	}
	
	else if($_REQUEST['type'] == "3")
	{
		if (is_dir($epub_path)) 
		{
			$perl = "perl ".EPUB_GENERATION_PERL_PATH." \"". $epub_path."\" \"".$_REQUEST['type']."\"  \"".$graphics_path."\" \"". $customer_info['cust_name']. "\" \"".$journalinfo['j_code']."\"";
			exec($perl);
			if (!file_exists($log_name)) 
			{
				$status = 1;
				$message = "ePub Validaion completed";
			}
			else
			{	
				$status = 0;
				$message = "Error in ePub Validaion, Please check log file";
			}	
		}
		else
		{
			$status = 0;
			$message = "ePub folder not exists, Please check it";
		}
		
	}
	
	else if($_REQUEST['type'] == "4")
	{
		if (file_exists($epub_name)) 
		{
			$perl = "perl ".EPUB_GENERATION_PERL_PATH." \"". $epub_name."\" \"".$_REQUEST['type']."\"  \"".$graphics_path."\" \"". $customer_info['cust_name']. "\" \"".$journalinfo['j_code']."\"";
			exec($perl);

			if (file_exists($html_file)) 
			{
				$status = 1;
				$message = "ePub to HTML creation completed";
				$html_url= $map_html_file;
			}
			else
			{	
				$status = 0;
				$message = "Error in ePub to HTML creation, Please check log file";
			}	
		}
		else
		{
			$status = 0;
			$message = "ePub file not exists, Please check it";
		}
		
	}
	
	/* 
	
	$array_info['status'] = $status;
	$array_info['message'] = $message;	
	echo json_encode($array_info); */
	
	$data = array('status' => $status, 'message' => $message, 'html_url' => $html_url);
	echo json_encode($data);
	

?>

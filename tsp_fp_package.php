<?php
include "includes/includes.php";

$query = "SELECT * FROM `inw_inward_dtl` WHERE id='".$_REQUEST['id']."'";
	$fileinfo = $db->query($query) or ($db->error);
	$app_res = $fileinfo->fetch_assoc();
	$html_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".html"; 
	$pdf_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".pdf"; 
	$pdf_file_res = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id']."-res.pdf";
	$graphics_path = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Graphics/Web/";
	$err ="";
	
	if (file_exists($html_file)) 
	{
		
		$html = file_get_contents($html_file);
		$dom = new DOMDocument();
		$dom->loadHTML($html);
		$xpath = new DOMXPath($dom);
		
		$nodes = $xpath->query('//*[@class="FIG"]');
		
		$html_figs = array();
		foreach ($nodes as $node) {
			
			$fig_names = $node->getAttribute("src");
			$fig_names = preg_replace("/(.*)(\/)(.+)/i", "$3", $fig_names);
			$fig_name = $graphics_path . $fig_names;
			array_push($html_figs, $fig_name);
			if (!file_exists($fig_name)) 
			{
				$err .=  "The Figure ".$fig_names." does not exist in Graphics folder.<br/>";
			}
		}	
		
    $Files = glob($graphics_path."*.png");
    foreach ($Files as $file) {
		if( !in_array( $file ,$html_figs ) )
		{
			$fig_names = preg_replace("/(.*)(\/)(.+)/i", "$3", $file);
			$err .=  "The Figure ".$fig_names." does not used in HTML.<br/>";
		}
    }
  
		
	} 
	else 
	{
		$err .=  "The file ".$app_res['pub_id'].".html does not exist.<br/>";
	}
	if (file_exists($pdf_file) || file_exists($pdf_file_res)) 
	{
   
	} 
	else 
	{
		$err .=  "The file ".$app_res['pub_id'].".pdf does not exist.<br/>";
	}
	
	if($err == "")
	{
		$zip_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".zip";
		// Initialize archive object
		$zip = new ZipArchive();
		$zip->open($zip_file , ZipArchive::CREATE | ZipArchive::OVERWRITE );
 
		if(file_exists($html_file))
		{
			$zip->addFile($html_file,$app_res['pub_id'].".html"); 
			$html = file_get_contents($html_file);
			$dom = new DOMDocument();
			$dom->loadHTML($html);
			$xpath = new DOMXPath($dom);
			
			$nodes = $xpath->query('//*[@class="FIG"]');
			$query_count = 1;
			foreach ($nodes as $node) {
				
				$fig_names = $node->getAttribute("src");
				$fig_names = preg_replace("/(.*)(\/)(.+)/i", "$3", $fig_names);
				$fig_name = $graphics_path . $fig_names;
				if (file_exists($fig_name)) 
				{
					$zip->addFile($fig_name,"FIGS/".$fig_names);
				}
			}
		}
		
		if(file_exists($pdf_file_res))
		{
			// $zip->addFile($pdf_file_res,$app_res['pub_id']."-res.pdf"); 
			$zip->addFile($pdf_file_res,$app_res['pub_id'].".pdf"); 
		}
		else if(file_exists($pdf_file))
		{
			$zip->addFile($pdf_file,$app_res['pub_id'].".pdf"); 
			// $zip->addFile($pdf_file,$app_res['pub_id']."-res.pdf"); 
		}	
	
		echo "completed";
	}
	else
	{
		echo $err;
	}
?>

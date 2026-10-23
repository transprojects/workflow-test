<?php
set_time_limit(0); 
 include "includes/includes.php";
 include_once 'includes/issue_drafts.php';
 $idr_art = idr_article_map(idr_runner_mysqli($db));
 $idr_issue = idr_issue_map(idr_runner_mysqli($db));
?>
<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

function addFolderToZip($zip, $folder, $folderInZip) {
    // Make sure the folder path ends with a slash
    if (substr($folder, -1) != '/') {
        $folder .= '/';
    }

    // Scan the folder for all files and directories
    $files = scandir($folder);

    foreach ($files as $file) {
        // Skip . and .. entries
        if ($file == '.' || $file == '..') {
            continue;
        }

        $filePath = $folder . $file;
        $zipFilePath = $folderInZip . '/' . $file;

        if (is_dir($filePath)) {
            // If it's a directory, add it recursively
            addFolderToZip($zip, $filePath, $zipFilePath);
        } else {
            // If it's a file, add it to the zip
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}
 
if (isset($_REQUEST['id'])) {
    // 'id' is set, continue with your logic
    $id = $_REQUEST['id'];
    // Your code here
} else {
    // 'id' is not set, redirect to another page
    header("location:manage_issues.php?msg=Please select the issue."); 
    exit; // Make sure to exit after redirecting
}

function deleteFile($file)
{
	/// To delete if zip files available ////
	if(file_exists($file))
	{
		unlink($file);
	}
}
if(isset($_POST['create_issue']) &&  $_POST['create_issue'] == 'Save') 
{
	$volume_query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = ".$_POST['vi_id']." ";
	$volume_issues = $db->query($volume_query) or ($db->error);
	$volume_dtl = $volume_issues->fetch_assoc();
	$toatl_count=$volume_issues->num_rows;
	if($toatl_count>0)
	{ 
		/// Get the customers details ////
		$query = "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '".$volume_dtl['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		if($crow['cust_name']  == "TSP")
		{
					
			////Get the Journal details ///	
			$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$volume_dtl['j_id']."';";
			$steps = $db->query($query) or ($db->error);
			$jninfo = $steps->fetch_assoc();
					
			$dest_path = $volume_dtl['dispatch_dir']; 
			$filename = $crow['cust_name']."_".$jninfo['j_code']."-".ltrim($volume_dtl['volume_number'],'0')."-".ltrim($volume_dtl['issue_number'],'0');
			$print_package = $dest_path.$filename."-Print-pdf.zip"; 
			$web_package = $dest_path.$filename."-Online-pdf.zip"; 
			$all_files_package = $dest_path.$filename.".zip"; 
			
			deleteFile($print_package);
			deleteFile($web_package);
			deleteFile($all_files_package);
			
			/// To get all article from this issue in inward table execpt FM, Hyperlink and BM  /////
			$inw_query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_dtl['vi_id']."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%' AND `v_issue_sequence_id` >= 1 ) ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($inw_query) or ($db->error);
			$toatl_count=$articledetails->num_rows;			
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					$toatl_files_length = strlen((string)$toatl_count);
					
					$v_issue_sequence_id = str_pad($article_info['v_issue_sequence_id'], $toatl_files_length, '0', STR_PAD_LEFT);
					
					/////// Get the department name /////
					$query = "SELECT * FROM adm_dept_master WHERE dept_code = '".$article_info['department_id']."'";
					$dpts = $db->query($query) or ($db->error);
					$dptinfo = $dpts->fetch_assoc();
				
					$work_dir = $article_info['work_dir']; 
					
					if(strpos($work_dir,$article_info['volume_folder']))
					{
						$src_path = $work_dir."/".$article_info['pub_id']."/".$article_info['stage']; 
					}
					else{
						$src_path = $work_dir.$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']; 
					}
					
					
					
					$pdf_file = $src_path."/PRQC/".$article_info['pub_id'].".pdf";
					$pdf_res_file = $src_path."/PRQC/".$article_info['pub_id']."-highres.pdf";
					$xml_file = $src_path."/XML/".$article_info['pub_id'].".xml";
					//$epub_file = $src_path."/ePub/".$article_info['pub_id'].".epub";
					$graphics_path = $src_path."/Graphics/";
					$graphics_web_path = $graphics_path."/Web/";
					$xhtml_path = $src_path."/ePub/".$article_info['pub_id']."/";
					$xhtml_file = $xhtml_path.$article_info['pub_id'].".xhtml";
					//$xhtml_path = $src_path."/ePub/".$article_info['pub_id']."/";
					//$xhtml_file = $src_path."/ePub/".$article_info['pub_id'].".xhtml";
					
					$pdf_file_check = false;
					if($article_info['stage'] != 'ISSCOR1')
					{	
						if(file_exists($pdf_file))
						{
							$pdf_file_check = true;		
						}
					}
					if($article_info['stage'] == 'ISSCOR1')
					{
						$pdf_file_check = true;
					}
					
					if($pdf_file_check)
					{
					
					
					if(!file_exists($pdf_file))
					{
						header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$article_info['pub_id'].".pdf file not available in PRQC folder, please check it.".$pdf_file); 
						exit();
					}
					/*if(!file_exists($pdf_res_file))
					{
						header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$article_info['pub_id']."-highres.pdf file not available in PRQC folder, please check it."); 
						exit();
					}*/
					if(file_exists($xml_file))
					{
						
						$doc = new DOMDocument();
						$doc->load( $xml_file);
						$figs = $doc->getElementsByTagName( "fig" );
						if(count($figs)>0)
						{						
							//echo $article_info['pub_id'].'-';
							foreach ( $figs as $graphics )  {
								$graphic = $graphics->getElementsByTagName( "graphic" );
								if($graphic->length > 0)
								{
									//var_dump($graphic);
									// $manager = $managers->item(0)->nodeValue;
									$img_name = $graphic->item(0)->getAttribute('xlink:href');
									$img_path = $graphics_web_path.$img_name;
									if(!file_exists($img_path))
									{
										header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$img_name." name used in ".$article_info['pub_id'].".xml file not available in Web folder, please check it.");  
										exit();
									}
								}
								else
								{
									header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=graphic tag is missed in ".$article_info['pub_id'].".xml file not available in Web folder, please check it.");  
									exit();
								}
							}
						}

					}
					else
					{
						header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$article_info['pub_id'].".xml file not available in XML folder, please check it.");  
						exit();
					}
					/*if(!file_exists($epub_file))
					{
						header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$article_info['pub_id'].".epub file not available in ePub folder, please check it."); 
						exit();
					}*/
					if(file_exists($xhtml_file))
					{
						
						$doc = new DOMDocument();
						$doc->load( $xhtml_file );
						$images = $doc->getElementsByTagName( "img" );
						if(count($images) > 0)
						{						
							foreach ( $images as $image )  
							{
								$image_name = $image->getAttribute('src');
								$xhtml_img = $xhtml_path.$image_name;
								//echo $xhtml_img.'<br>';
								if(!file_exists($xhtml_img))
								{
									header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$image_name." name used in ".$article_info['pub_id'].".xhtml file not available in Images folder, please check it.");  
									exit();
								}
							}
						}
					}
					else
					{
						header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".$article_info['pub_id'].".xhtml ".$xhtml_file." file not available in ePub folder, please check it."); 
						exit();
					}
				}
					
				}
				
				
						
				// Initialize archive object for Print PDF
				$print_zip = new ZipArchive();
				$zip_ok = true;
				if($print_zip->open($print_package, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE) { $zip_ok = false; }
		
				// Initialize archive object for Web PDF
				$web_zip = new ZipArchive();
				if($web_zip->open($web_package, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE) { $zip_ok = false; }
				
				// Initialize archive object for All other files
				$all_files_zip = new ZipArchive();
				if($all_files_zip->open($all_files_package, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE) { $zip_ok = false; }
					
				/// To get all article from this issue in inward table execpt FM, Hyperlink and BM  /////
				$articledetails = $db->query($inw_query) or ($db->error);
				while($article_info = $articledetails->fetch_assoc())
				{
					$toatl_files_length = strlen((string)$toatl_count);
					
					$v_issue_sequence_id = str_pad($article_info['v_issue_sequence_id'], $toatl_files_length, '0', STR_PAD_LEFT);
					
					/////// Get the department name /////
					$query = "SELECT * FROM adm_dept_master WHERE dept_code = '".$article_info['department_id']."'";
					$dpts = $db->query($query) or ($db->error);
					$dptinfo = $dpts->fetch_assoc();
				
					$work_dir = $article_info['work_dir']; 
					
					if(strpos($work_dir,$article_info['volume_folder']))
					{
						$src_path = $work_dir."/".$article_info['pub_id']."/".$article_info['stage']; 
					}
					else{
						$src_path = $work_dir.$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']; 
						//$src_path = $work_dir.$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage'];
					}
					
					
					
					
					
					$pdf_file = $src_path."/PRQC/".$article_info['pub_id'].".pdf";
					$pdf_res_file = $src_path."/PRQC/".$article_info['pub_id']."-highres.pdf";
					$xml_file = $src_path."/XML/".$article_info['pub_id'].".xml";
					$xml_folder = $src_path."/XML/Supp";
					//$epub_file = $src_path."/ePub/".$article_info['pub_id'].".epub";
					$graphics_path = $src_path."/Graphics/";
					$graphics_web_path = $graphics_path."/Web/";
					$xhtml_path = $src_path."/ePub/".$article_info['pub_id']."/";
					$xhtml_file = $xhtml_path.$article_info['pub_id'].".xhtml";
					
					
					if(!file_exists($pdf_res_file))
					{
						$pdf_res_file = $pdf_file;
					}
					
					$pdf_file_check = false;
					if($article_info['stage'] != 'ISSCOR1')
					{	
						if(file_exists($pdf_file))
						{
							$pdf_file_check = true;		
						}
					}
					if($article_info['stage'] == 'ISSCOR1')
					{
						$pdf_file_check = true;
					}
					
					if($pdf_file_check)
					{
					
					
					if(file_exists($pdf_file))
					{
						$web_zip->addFile($pdf_file,$article_info['pub_id'].'.pdf');
						$all_files_zip->addFile($pdf_file,$article_info['pub_id']."/".$article_info['pub_id'].'.pdf');
					}
					if(file_exists($pdf_res_file))
					{
						//$print_zip->addFile($pdf_res_file,$article_info['pub_id'].". ".$article_info['pub_id'].'.pdf');
						if($article_info['v_issue_sequence_id'] > 0)
						{
							$print_zip->addFile($pdf_res_file, $article_info['v_issue_sequence_id'].'. '.$article_info['pub_id'].'.pdf');
							
						} else {
							$print_zip->addFile($pdf_res_file, $article_info['pub_id'].'.pdf');
							
						}
					}
					if(file_exists($xml_file))
					{
						$all_files_zip->addFile($xml_file,$article_info['pub_id']."/".$article_info['pub_id'].'.xml');
						
						// Adding a folder and its contents (use the folder path and target zip folder path)
    $folderPath = $xml_folder;  // Path to the folder you want to add
    $folderInZip = $article_info['pub_id'].'/Supp'; // Target folder in the zip

    //addFolderToZip($all_files_zip, $folderPath, $folderInZip);
	
						$doc = new DOMDocument();
						$doc->load( $xml_file);
						$figs = $doc->getElementsByTagName( "fig" );
						
					       //figure tag   graphics tag move to delivery folder 

						foreach ( $figs as $graphics )  {
							$graphic = $graphics->getElementsByTagName( "graphic" );
							// $manager = $managers->item(0)->nodeValue;
							//$img_name = $graphic->item(0)->getAttribute('xlink:href');
							
							foreach ( $graphic as $graphics_fig )  
									{
										
											$img_name = $graphics_fig->getAttribute('xlink:href');
											$img_path = $graphics_web_path.$img_name;
											//print_r($img_path);
											 if(file_exists($img_path))
												{
													$all_files_zip->addFile($img_path,$article_info['pub_id']."/".$img_name);			
												}
									}
							
							//$img_path = $graphics_web_path.$img_name;
							

							
							

						}
					       //figure tag   graphics tag move to delivery folder 

						//inline  fig tag move to delivery folder 

						$inlines = $doc->getElementsByTagName( "inline-graphic" );
						
					       //figure tag   graphics tag move to delivery folder 

						
							
							foreach ($inlines as $inlines_graphic)  
									{
										
											$img_name_inline = $inlines_graphic->getAttribute('xlink:href');
											$img_path_inline = $graphics_web_path.$img_name_inline;
											//print_r($img_path);
											 if(file_exists($img_path_inline))
												{
													$all_files_zip->addFile($img_path_inline,$article_info['pub_id']."/".$img_name_inline);			
												}
									}
							
							//$img_path = $graphics_web_path.$img_name;
							

							
							

						

						//inline  fig tag move to delivery folder 

					}
					/*if(file_exists($epub_file))
					{
						$all_files_zip->addFile($epub_file,$article_info['pub_id']."/".$article_info['pub_id'].'.epub');
					}*/
					if(file_exists($xhtml_file))
					{
						$all_files_zip->addFile($xhtml_file,$article_info['pub_id']."/".$article_info['pub_id']."/".$article_info['pub_id'].'.xhtml');
						$css_path = $xhtml_path."css-template.css";
						if(file_exists($css_path))
						{
							$all_files_zip->addFile($css_path,$article_info['pub_id']."/".$article_info['pub_id']."/css-template.css");
						}
						$doc = new DOMDocument();
						$doc->load( $xhtml_file );
						$images = $doc->getElementsByTagName( "img" );
						foreach ( $images as $image )  
						{
							$image_name = $image->getAttribute('src');
							$xhtml_img = $xhtml_path.$image_name;
							if(file_exists($xhtml_img))
							{
								$all_files_zip->addFile($xhtml_img,$article_info['pub_id']."/".$article_info['pub_id']."/".$image_name);
							}
						}
					}
				}
					
				}
				if(!$zip_ok)
				{
					header("location:show_issues_merge.php?id=" . $_POST['vi_id'] . "&ermsg=".urlencode("The zip packages could not be created in ".$dest_path." - please check that this folder exists and can be written to."));
					exit;
				}
				$web_zip->close();
				$print_zip->close();
				$all_files_zip->close();
				$prj_update_query="UPDATE inw_volume_issues SET `status` ='Delivery' WHERE vi_id = ".$volume_dtl['vi_id']." ";	
				$db->query($prj_update_query);
				header("location:manage_issues.php?msg=Successfully Data set created"); 
				exit();
			}
		}
		else if($crow['cust_name']  == "ASP")
		{ 	
			/// To get all article from this issue in inward table execpt FM, Hyperlink and BM  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_dtl['vi_id']."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
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
					
					/////// Get the department name /////
					$query = "SELECT * FROM adm_dept_master WHERE dept_code = '".$article_info['department_id']."'";
					$dpts = $db->query($query) or ($db->error);
					$dptinfo = $dpts->fetch_assoc();
				
					$work_dir = $article_info['work_dir']; 
					
					$src_path = $work_dir.$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/".$dptinfo['dept_name']."/"; 
					
					$dest_path = $work_dir.$article_info['volume_folder']."/Delivery/".$article_info['stage']."/"; 
					$src_pdf = $src_path.$article_info['pub_id'].".pdf";
					
					/// replaceing the PDF file name as per sequnce number based ////
					$dest_pdf = $dest_path.$v_issue_sequence_id."_".$volume_dtl['volume_number'].$jninfo['j_code'].$volume_dtl['issue_number']."-".preg_replace('/(.*)(-)(.*)/i','$3',$article_info['pub_id']).".pdf";
					if(file_exists($src_pdf))
					{
						
						
							// If the destination directory does not exist create it
						if(!is_dir($dest_path)) { 
							if(!mkdir($dest_path)) {
							 
							}    
						} 
						 copy($src_pdf,$dest_pdf);
					}
					
				}
				
			}
			
			/// To get all article from this issue in inward table Only FM, Hyperlink and BM  /////
			$query = "SELECT * FROM `inw_inward_dtl` WHERE ( v_issue_id = '".$volume_dtl['vi_id']."'  AND `pub_id` LIKE '%-FM%' AND `pub_id` LIKE '%-BM%' AND `pub_id LIKE '%-Hyperlink%') ORDER BY `v_issue_sequence_id` ASC; ";
			$articledetails = $db->query($query) or ($db->error);
			$toatl_count=$articledetails->num_rows;
			if($toatl_count>0)
			{
				while($article_info = $articledetails->fetch_assoc())
				{
					// $toatl_files_length = strlen((string)$toatl_count);
					
					// $v_issue_sequence_id = str_pad($article_info['v_issue_sequence_id'], $toatl_files_length, '0', STR_PAD_LEFT);
					
					//// Get the customer details ////
					$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$article_info['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
					
					////Get the Journal details ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id = '".$article_info['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();
					
					/////// Get the department name /////
					$query = "SELECT * FROM adm_dept_master WHERE dept_code = '".$article_info['department_id']."'";
					$dpts = $db->query($query) or ($db->error);
					$dptinfo = $dpts->fetch_assoc();
				
					$work_dir = $article_info['work_dir']; 
					
					$src_path = $work_dir.$article_info['volume_folder']."/".$article_info['pub_id']."/".$article_info['stage']."/".$dptinfo['dept_name']."/"; 
					
					$dest_path = $work_dir.$article_info['volume_folder']."/Delivery/".$article_info['stage']."/"; 
					$src_pdf = $src_path.$article_info['pub_id'].".pdf";
					$src_docx = $src_path.$article_info['pub_id'].".docx";
					
					/// replaceing the PDF file name as per sequnce number based ////
					$dest_pdf = $dest_path."00_".$volume_dtl['volume_number'].$jninfo['j_code'].$volume_dtl['issue_number']."-".preg_replace('/(.*)(-)(.*)/i','$3',$article_info['pub_id']).".pdf";
					$dest_docx = $dest_path."00_".$volume_dtl['volume_number'].$jninfo['j_code'].$volume_dtl['issue_number']."-".preg_replace('/(.*)(-)(.*)/i','$3',$article_info['pub_id']).".docx";
					
					
					if(strpos($article_info['pub_id'], "Hyperlink") !== false){
						
						if(file_exists($src_docx))
						{
							
							
								// If the destination directory does not exist create it
							if(!is_dir($dest_path)) { 
								if(!mkdir($dest_path)) {
								
								}    
							} 
							copy($src_docx,$dest_docx);
						}						
					}
					else
					{
						 if(file_exists($src_pdf))
						{
							
							
								// If the destination directory does not exist create it
							if(!is_dir($dest_path)) { 
								if(!mkdir($dest_path)) {
								
								}    
							} 
							copy($src_pdf,$dest_pdf);
						}
					}

					
					
				}
				
			}
		
			$prj_update_query="UPDATE inw_volume_issues SET `status` ='Delivery' WHERE vi_id = ".$volume_dtl['vi_id']." ";	
			$db->query($prj_update_query);
			header("location:manage_issues.php?msg=Successfully added"); 
			exit();
	
		}
		else
		{
			$prj_update_query="UPDATE inw_volume_issues SET `status` ='Delivery' WHERE vi_id = ".$volume_dtl['vi_id']." ";	
			$db->query($prj_update_query);
			header("location:manage_issues.php?msg=Successfully added"); 
			exit();
		
		}
		
	}
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>

    <!-- Data Table CSS- -->

	<style>
    .ii-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
    .ii-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:150px;}
    .ii-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
    .ii-info b{color:#14304a;font-size:14px;}
    .ii-link{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-weight:700;text-decoration:none !important;border:1px solid #c5daef;background:#e8f1fb;color:#1b6ca8 !important;}
    .ii-link:hover{background:#1b6ca8;color:#fff !important;}
    .ii-link.green{border-color:#bfe8d2;background:#e3f6ec;color:#1e9e61 !important;}
    .ii-link.green:hover{background:#1e9e61;color:#fff !important;}
    .cm-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
    .cm-h i{color:#e8742a;}
    .cm-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;margin-top:12px;}
    .cm-row .cm-view{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
    .cm-row .cm-view span{font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
    #articles td:first-child,#articles th:first-child{width:34px;}
    /* legacy label-* pills in the status cell */
    .af-card .label{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;line-height:1.3;color:#fff;background:#6b7785;}
    .af-card .label-success{background:#1e9e61;} .af-card .label-info{background:#d98a06;} .af-card .label-warning{background:#e8932a;} .af-card .label-danger{background:#d64545;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>


	 <?php
		//// Get volume issues Details///
		$query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = '".(int)$_REQUEST['id']."';";
		$steps = $db->query($query) or ($db->error);
		$voliss_info = $steps->fetch_assoc();
	?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Issue collation</small><h1>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    }
    ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="ii-info">
        <div><small>Issue</small><b>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></b></div>
        <div><small>Received date</small><b><?php echo $voliss_info['received_date'] ? date('d-m-Y', strtotime($voliss_info['received_date'])) : '&ndash;'; ?></b></div>
        <div><small>Due date</small><b><?php echo $voliss_info['due_date'] ? date('d-m-Y', strtotime($voliss_info['due_date'])) : '&ndash;'; ?></b></div>
        <div><small>Draft status</small><b><?php echo idr_issue_cell($idr_issue, $voliss_info['vi_id']); ?></b></div>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      <div class="table-responsive">
        <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
          <thead>
            <tr>
              <th class="center"><input type="checkbox" id="chk_all" title="Select all on this page" /></th>
              <th data-af-role="num">Seq.no</th>
              <th data-af-role="client">Customer</th>
              <th data-af-role="journal">Journal</th>
              <th data-af-role="file">Article</th>
              <th data-af-role="recv">Due Date</th>
              <th data-af-role="recv">Created On</th>
              <th data-af-role="num">Manuscript</th>
              <th data-af-role="num">Fig</th>
              <th data-af-role="num">Table</th>
              <th data-af-role="stage">Stage</th>
              <th data-af-role="none">Status</th>
            </tr>
          </thead>
          <tbody>
                  <?php
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by <= 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status NOT IN ('Delivery','Client Review','Completed','DELIVERY-ELX') ORDER BY  `v_issue_sequence_id` ASC ";
			}
			else if($_COOKIE['department_id'] == 9)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.fig_complete_status = 0 OR department_id = '".$_COOKIE['department_id']."') AND fig > 0  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed','DELIVERY-ELX') ORDER BY  `v_issue_sequence_id` ASC ";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND department_id = '".$_COOKIE['department_id']."'  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed','DELIVERY-ELX') ORDER BY  `v_issue_sequence_id` ASC ";
			}
		}
		else
		{
			$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status IN ('Delivery','DELIVERY-ELX') ORDER BY  `v_issue_sequence_id` ASC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';
		// no due-date / priority highlight
		$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.process_user !='". $_COOKIE['user_id']."' AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();		
		?>
                  <tr>
                    <td class="center"><input type="checkbox" class="chk_row" /></td>
                    <td><?php echo $row['v_issue_sequence_id']; ?></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id'].idr_article_badge($idr_art, $row['id']); ?></td>                   
					<td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['created_dt']))); ?></td>
					 <td><?php echo $row['manuscript_count']; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td><?php echo $row['tab']; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    <td><span class="af-stat-wrap"><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span><?php if($transaction_toatl_count > 0){ echo '<span class="label label-sm label-info">'.$trans_res['full_name'].'</span><span class="label label-sm label-warning">'.$trans_res['current_status'].'</span>'; } ?></span></td>
                    
                  </tr>
                  <?php } ?>
                </tbody>
        </table>
      </div>
    </div></div>

    <div class="af-page"><div class="af-card">
      <form action="" name="merge_files" enctype="multipart/form-data" id="validation-form" method="post" autocomplete="off">
        <input type="hidden" name="vi_id" value="<?php echo $voliss_info['vi_id'] ?>">
        <input type="hidden" name="id" value="<?php echo $voliss_info['vi_id'] ?>">
        <h3 class="cm-h"><i class="fa fa-files-o"></i> Issue collation and upload file</h3>
        <div class="cm-row">
          <div class="cm-view"><span>View</span>
            <a class="ii-link" href="file:///<?php echo $voliss_info['work_dir'] ?>" target="_blank"><i class="fa fa-folder"></i> Source</a>
            <a class="ii-link green" href="file:///<?php echo $voliss_info['dispatch_dir'] ?>" target="_blank"><i class="fa fa-folder-open"></i> Work</a>
          </div>
          <div style="display:flex;gap:10px;align-items:center;">
            <a class="af-btn" href="manage_issues.php" title="Back to Manage Issues">Cancel</a>
            <button type="submit" id="create_issue" name="create_issue" value="Save" class="af-btn primary"><i class="fa fa-check"></i> Save</button>
          </div>
        </div>
      </form>
    </div></div>

 <?php echo footer(); ?>
<!-- Footer Section -->
<?php echo scripts(); ?>	
<!-- /.main-container --> 
<?php af_foot(); ?>
<?php if(!empty($_REQUEST['ermsg'])) { ?>
<script type="text/javascript">
/* collation errors (missing PDF / XML / graphics, zip not created ...) show as a pop-up */
$(function () {
	bootbox.alert({
		message: '<span class="bb-title">Cannot collate this issue</span><span class="bb-sub"></span>',
		className: 'bootbox-sm bb-bad',
		onShown: function () { $('.bootbox.bb-bad .bb-sub').text(<?php echo json_encode($_REQUEST['ermsg']); ?>); }
	});
	if (window.history && history.replaceState) { history.replaceState(null, '', 'show_issues_merge.php?id=<?php echo (int)$_REQUEST['id']; ?>'); }   // a refresh must not show it again
});
</script>
<?php } ?>
<script type="text/javascript">
$(function () { $('#chk_all').on('change', function () { $('.chk_row').prop('checked', this.checked); }); });
			jQuery(function($) {
				$('#articles').DataTable( {
					dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
					order: [],
					columnDefs: [{ orderable: false, targets: [0] }],
					//bAutoWidth: false,
					pageLength : 10,

					buttons: [
           	 {
            	    extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],
	
					// dom: 'Bfrtip',
         	

			    });
			
				
				
						
			})
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}
	
function check_work_status(id,tid)
			{	
			
			if(tid=='')
			{
				tid = 0;
			}
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_work_status.php',
					data:{id:id},
					success:function(result)
					{
						
						if(result == 0)
						{
							        	var url = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;	
										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();							
										return true;					
						}
						else
						{
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>	This Article currently in <strong>Progress.</strong> It is already started the work BY <strong>'+ result +'</strong></div>',
								buttons:
						{
							"success" :
							 {
								"label" : "<i class='ace-icon fa fa-check'></i> Take Over!",
								"className" : "btn-sm btn-success",
								"callback": function() {
										var url = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;	
										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();							
										return true;		
								}
							},
							"danger" :
							{
								"label" : "Override",
								"className" : "btn-sm btn-danger",
								"callback": function() {
										var url = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;	
										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();							
										return true;		
								}
							}, 
							"button" :
							{
								"label" : "Close",
								"className" : "btn-sm",
								"callback": function() {
									window.location.reload();				
									return true;
								}
							}							
						} 						
							});					
							return false;
						}
					}
				});
			}	
		</script>
</body>
</html>
<?php ob_end_flush(); ?>

<?php include "includes/includes.php";?>
<?php 
//echo "ajax page 1";


   	$j_id = $_REQUEST['j_id'];
	
	$journaldetails = $auth->getJournalCustomer($j_id);
	//echo "ajax page ".$j_id."";
	
	
	$Cn=$journaldetails[0]['cust_name'];
	$cust_id=$journaldetails[0]['j_cust_id'];
	$jn=$journaldetails[0]['j_code'];
	$article_id = $_REQUEST['pub_id'];
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$volume_no='Vol00000';
	
	
 $target_dir ="uploads/".$Cn."/".$jn."/".$article_id."";  
 

 // Upload directory */`
 //echo "SELECT * FROM `inw_inward_dtl` WHERE pub_id= '".$article_id."'";

 		$article_info = $auth->countArticle($_REQUEST['pub_id']);

	 
	// echo "<br/>".$article_info;exit;
		$zip = new ZipArchive();
		if($article_info[0]['count']==0)
		{
		$zip_folder_path= $target_dir. "/".$article_id. ".zip";
        $zipFileName =$article_id.".zip";	
		$zip_name_timestamp=$article_id;
		$file_check='new';
		}
		else
		{
		$zip_folder_path= $target_dir. "/".$article_id. ".zip";
		$zipFileName =$article_id.".zip";	
		$zip_name_timestamp=$article_id;
		$file_check='duplicate';
		}
        
        
	


		$fi = new FilesystemIterator($target_dir, FilesystemIterator::SKIP_DOTS);
 
		 $count_article_files=iterator_count($fi);


		// Create a new ZipArchive instance
$zip = new ZipArchive();

// Open the zip archive for writing
if ($zip->open($zip_folder_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    // Get all files in the folder
	//echo $target_dirs=$target_dir;
       // Open the source folder
	   $dir = opendir($target_dir);

	   // Iterate through the files in the folder
	   while (($file = readdir($dir)) !== false) {
		   if ($file != '.' && $file != '..') {
			   $filePath = $target_dir . '/' . $file;
   
			   // Add the file to the zip archive
			   $zip->addFile($filePath, $file);
		   }
	   }
   
	   // Close the source folder
	   closedir($dir);
   
	   // Close the zip archive
	   $zip->close();
	   
 // Get the size of the ZIP file
    $zipSize = filesize($zip_folder_path);
    

}
//insert log 

if (!file_exists($zip_folder_path))

{
    				$insert_log=$auth->insertLog('',$article_id,'3','LINUX','FP','zip not created or not existed in linux server','0');
			      	$insert_log=$auth->insertLog('',$article_id,'4','LINUX','FP','zip file size not updated in read me txt file','0');

}
else
{ 
    				$insert_log=$auth->insertLog('',$article_id,'3','LINUX','FP','zip created in linux server','1');
  			      	$insert_log=$auth->insertLog('',$article_id,'4','LINUX','FP','zip file size updated in read me txt file','1');
  
}



               $articledetails=$target_dir."/readme.txt";
		       $article_txt = "\n  Size of ZIP Package : '".$zipSize."' Bytes \n --------------------------------- \n";	
			   file_put_contents($articledetails, $article_txt, FILE_APPEND | LOCK_EX);
			
	   

?>

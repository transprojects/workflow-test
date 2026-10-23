<?php
set_time_limit(0);
include ('linux_sif_db.php');


    
	$project_id = "";
	$cust_id = "";
	$j_id = "";
	
    $stage = $_POST['stage'];


	    /// Checking the LINUX SERVER DB for existing article values ////
    $inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$app_res['id']."'";
    $inwerd_query = $linuxsif_db->query($inwerd_detl) or die($linuxsif_db->error);
    $inwed_run = $inwerd_query->fetch_assoc();


	
	
    /// $app_res in this variable we are geting the fulle article details ////
	
	
///ZIP ALL GRAPHICS FOLDER IN TO FTP

// Specify the folder containing the files you want to zip
 $GraphicsPath = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/FP/Graphics/Print";
 $GraphicsPath_Delivery = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/FP/Delivery/";

// Name for the output ZIP file
$GraphicszipFileNames =$app_res['pub_id'].'_graphics.zip';
$GraphicszipFileName =$app_res['pub_id'].'_graphics.zip';



		// Create a new ZipArchive instance
$zip = new ZipArchive();

// Open the zip archive for writing
if ($zip->open($GraphicsPath_Delivery.$GraphicszipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    // Get all files in the folder
	//echo $target_dirs=$target_dir;
       // Open the source folder
	   $dir = opendir($GraphicsPath);

	   // Iterate through the files in the folder
	   while (($file = readdir($dir)) !== false) {
		   if ($file != '.' && $file != '..') {
			   $filePath = $GraphicsPath . '/' . $file;
   
			   // Add the file to the zip archive
			   $zip->addFile($filePath, $file);
		   }
	   }
   
	   // Close the source folder
	   closedir($dir);
   
	   // Close the zip archive
	   $zip->close();
}



	

    $num_row = $inwerd_query->num_rows;

$filename = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/Delivery/". $app_res['pub_id'] . ".zip";

     $filename_graphics = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/Delivery/". $app_res['pub_id'] . "_graphics.zip";

     $name = $app_res['pub_id'] . ".zip";

     //$destination_file = "/Vendor/".$_POST['cust_name']."/".$_POST['j_code']."/". $app_res['pub_id']."/".$stage."/";
     
     $destination_file = $ftp_linux_path . "/FP/" . $_POST['cust_name'] . "/" . $_POST['j_code'] . "/" . $app_res['pub_id']."/transforma-files/";  // Upload directory */`

     //where you want to throw the file on the webserver (relative to your login dir)
     $conn_id = ftp_connect($ftp_linux_server) or die("Couldn't connect to $ftp_linux_server");        // set up basic connection

    $login_result = ftp_login($conn_id, $ftp_linux_user_name, $ftp_linux_user_pass) or die("You do not have access to this ftp server");   // login with username and 	password, or give invalid user message
	
	
/* 		echo $filename;
	echo "<br/>";
	echo $name;
	echo "<br/>";
	echo $destination_file;
exit; */

	ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($conn_id, true);

            if (ftp_mkdir($conn_id, $ftp_linux_path . "/FP/" . $_POST['cust_name'] . "/" . $_POST['j_code'] . "/")) {

                echo "Successfully created $destination_file"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id, $ftp_linux_path . "/FP/" . $_POST['cust_name'] . "/" . $_POST['j_code'] . "/" . $app_res['pub_id'] . "/")) {
                echo "Successfully created $destination_file"; 
            } else {
                echo "Error while creating folder";
            }

if (ftp_mkdir($conn_id, $destination_file)) {
            echo "Successfully created $destination_file";
        } else {
            echo "Error while creating $destination_file";
        }

    if ((!$conn_id) || (!$login_result)) {  // check connection
        // wont ever hit this, b/c of the die call on ftp_login
        echo "FTP connection has failed";
        echo "Attempted to connect to $ftp_linux_server for user $ftp_linux_user_name";
      
    } 
	
	
    if (file_exists($filename)) {

     
        $maxRetries = 3;
        $retryCount = 0;


do {
    $upload =ftp_put($conn_id, $destination_file . $name, $filename, FTP_BINARY);
    $upload_graphics=ftp_put($conn_id, $destination_file . $GraphicszipFileName, $filename_graphics, FTP_BINARY);

    $fileSize_aws = ftp_size($conn_id, $destination_file. $name);
    $fileSize_tvl_server =filesize($filename);
    echo $fileSize_aws."<br/>";
    echo $fileSize_tvl_server;

    if (!$upload || $fileSize_aws != $fileSize_tvl_server) 
    {
        echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount++;
    }
} while (!$upload && $retryCount < $maxRetries);



$prj_update_query_sif = "UPDATE inw_inward_dtl SET production_review ='0',final_delivery ='0',stage_with='SIF' WHERE id = ". $inwed_run['id'] ."";
$linuxsif_db->query($prj_update_query_sif);




    if (!$upload)
    {
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
   
        header("location:send_to_client.php?id=" . $app_res['id'] . "&msg=File upload FTP Issue in Client Portal , Please try again.");
        exit;
        
       
    }
}
else{
    
        $prj_update_query = "UPDATE inw_inward_dtl SET status='Delivery',`department_id` ='15' WHERE id = " . $app_res['id'] . "";
        //echo $prj_update_query; exit;
        $db->query($prj_update_query);
        
        
    header("location:send_to_client.php?id=" . $app_res['id'] . "&msg=Source file path is missing please check and try again.");
        exit;
}

ftp_close($conn_id);
	
?>
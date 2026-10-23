<?php 
	include('conn.php');
	include ('dbc.php'); 

//echo "xpertrack page";exit;

/// Checking the XPERTTRACKING DB for existing article values ////
    $inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl where pub_id ='" . $_REQUEST['pub_id'] . "';";
    $inwerd_query_xperttrack = $xperttrack_db->query($inwerd_detl_xperttrack) or die($xperttrack_db->error);
    $inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();
	//echo $inwed_run_xperttrack['workflow_id'];
	
		//// Get the article details ////
	$query_xarticle = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$_REQUEST['pub_id']."';";
	$articledetails_xpert = $db->query($query_xarticle) or ($db->error);
	$artilce_info_xpert= $articledetails_xpert->fetch_assoc();
	//echo $artilce_info_xpert['corresponding_mail'];
	
	
	
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$artilce_info_xpert['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
	 $query = "SELECT * FROM `adm_journals` WHERE j_id= '".$artilce_info_xpert['j_id']."'";
	 $steps = $db->query($query) or ($db->error);
	 $jninfo = $steps->fetch_assoc();
	

	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $artilce_info_xpert['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$work_dir =$artilce_info_xpert['work_dir'];	
	/////// Get the Journal Name ////
	
	
	
     
				
	
				
//LINUX SERVER REV1  FILE UPLOAD FROM TRANSFORMA TO AUTHOR
     $filename =$work_dir.$artilce_info_xpert['volume_folder']."/".$artilce_info_xpert['pub_id']."/".$_REQUEST['stage']."/Delivery/".$artilce_info_xpert['pub_id'].".pdf";


      $destination_file_linux = $ftp_path . "/REV/" . $Cn . "/" . $jn . "/" . $artilce_info_xpert['pub_id']."/REV1/transforma-to-client/";  // Upload directory */`
     $name = $artilce_info_xpert['pub_id'] . ".pdf";

     $conn_id_linux = ftp_connect($ftp_server) or die("Couldn't connect to $ftp_server");        // set up basic connection

    $login_result_linux = ftp_login($conn_id_linux, $ftp_user_name, $ftp_user_pass) or die("You do not have access to this ftp server");   // login with username and 	password, or give invalid user message

	
/* 		echo $filename;
	echo "<br/>";
	echo $name;
	echo "<br/>";
	echo $destination_file_linux;
exit; */

	ftp_set_option($conn_id_linux, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($conn_id_linux, true);

            if (ftp_mkdir($conn_id_linux, $ftp_path . "/REV/" . $Cn . "/" . $jn . "/")) {

                echo "Successfully created $destination_file_linux"; 
            }else {
                echo "Error while creating folder";
            }

            if (ftp_mkdir($conn_id_linux, $ftp_path . "/REV/" . $Cn . "/" . $jn . "/" . $artilce_info_xpert['pub_id'] . "/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }
			
			 if (ftp_mkdir($conn_id_linux, $ftp_path . "/REV/" . $Cn . "/" . $jn . "/" . $artilce_info_xpert['pub_id'] . "/". "/" . $_REQUEST['stage'] . "/")) {
                echo "Successfully created $destination_file_linux"; 
            } else {
                echo "Error while creating folder";
            }

if (ftp_mkdir($conn_id_linux, $destination_file_linux)) {
            echo "Successfully created $destination_file_linux";
        } else {
            echo "Error while creating $destination_file_linux";
        }

    if ((!$conn_id_linux) || (!$login_result_linux)) {  // check connection
        // wont ever hit this, b/c of the die call on ftp_login
        echo "FTP connection has failed";
        echo "Attempted to connect to $ftp_linux_server for user $ftp_linux_user_name";
      
    } 
	
	
    if (file_exists($filename)) {

     
        $maxRetries_linux = 3;
        $retryCount_linux = 0;


do {
    $upload_linux =ftp_put($conn_id_linux, $destination_file_linux . $name, $filename, FTP_BINARY);
    $fileSize_linux = ftp_size($conn_id_linux, $destination_file_linux. $name);
    $fileSize_tvl_server =filesize($filename);
    echo $fileSize_linux."<br/>";
    echo $fileSize_tvl_server;
	echo "file upload completed";
    if (!$upload_linux || $fileSize_linux != $fileSize_tvl_server) 
    {
        echo 'File upload failed. Retrying...' . PHP_EOL;
        $retryCount_linux++;
    }
} while (!$upload_linux && $retryCount_linux < $maxRetries_linux);

	}
	
	

//LINUX SERVER REV1  FILE UPLOAD FROM TRANSFORMA TO AUTHOR
			
	   
//exit;
	?>
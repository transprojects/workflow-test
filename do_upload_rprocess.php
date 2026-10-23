<?php include "includes/includes.php";?>


<?php

	
if($_POST['volume_folder_name'] == VOLUME_FOLDER_NAME)
{
	$target_dir = WF_DIR."/downloads/JOURNALS/".$_POST['cust_id']."/".$_POST['j_id']."/".$_POST['volume_folder_name'].$_POST['issueorgroup_folder']."/".$_POST['folder_name']."/".$_POST['stage']."/";  // Upload directory
}
else
{
	$target_dir = WF_DIR."/downloads/JOURNALS/".$_POST['cust_id']."/".$_POST['j_id']."/".$_POST['volume_folder_name']."/".$_POST['folder_name']."/".$_POST['stage']."/";  // Upload directory
}
								

if (!(is_dir($target_dir))) 
{
	if (!mkdir($target_dir, 0777, true)) 
	{
    	die('Failed to create folders...$jn');
	}
}
	
$request = 1;
if(isset($_POST['request'])){
	$request = $_POST['request'];
}

// Upload file
if($request == 1){
	$target_file = $target_dir . basename($_FILES["file"]["name"]);

	$msg = "";
	if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_dir.$_FILES['file']['name'])) {
	    $msg = "Successfully uploaded";
	}else{
	    $msg = "Error while uploading";
	}
	echo $msg;
}

// Remove file
if($request == 2){
	$filename = $target_dir.$_POST['name']; 
	unlink($filename); exit;
}

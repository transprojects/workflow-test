<?php 
include ('dbc.php'); 
?>

<?php

 $target_dir = WF_DIR."/production/JOURNALS/".$_POST['vcust_id']."/".$_POST['vj_id']."/".$_POST['volume_folder_name'].$_POST['issueorgroup_folder']."/".$_POST['folder_name']."/".$_POST['stage']."/CE/";  // Upload directory
 

$request = 1;
if(isset($_POST['request'])){
	$request = $_POST['request'];
}

// Upload file
if($request == 1){
	// create the CE folders only when a file is really being uploaded (not on every call)
if (!(is_dir($target_dir))) 
{
	if (!mkdir($target_dir, 0777, true)) 
	{
    	die('Failed to create folders...$jn');
	}
}

	$target_file = $target_dir . basename($_FILES["file"]["name"]);

	$msg = "";
	if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_dir.$_FILES['file']['name'])) {
	    $msg = "Successfully uploaded";
	    /* remember who uploaded it, so Start Work can list each user's own uploads */
	    $ul_dir = __DIR__.'/uploadlogs'; if (!is_dir($ul_dir)) { @mkdir($ul_dir, 0777, true); }
	    $ul_file = $ul_dir.'/'.md5($target_dir).'.json';
	    $ul = is_file($ul_file) ? json_decode(file_get_contents($ul_file), true) : array(); if (!is_array($ul)) { $ul = array(); }
	    $ul[] = array('file' => $_FILES['file']['name'], 'user' => isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '', 'time' => time());
	    @file_put_contents($ul_file, json_encode($ul), LOCK_EX);
	}else{
	    $msg = "Error while uploading";
	}
	echo $msg;
}

// Remove file
if($request == 2){
	/* a user may remove only a file he/she uploaded (checked against the upload log) */
	$name = basename($_POST['name']);
	$ul_file = __DIR__.'/uploadlogs/'.md5($target_dir).'.json';
	$ul = is_file($ul_file) ? json_decode(file_get_contents($ul_file), true) : array(); if (!is_array($ul)) { $ul = array(); }
	$mine = false;
	foreach ($ul as $ul_row) { if ($ul_row['file'] === $name && (string)$ul_row['user'] === (string)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '')) { $mine = true; } }
	if (!$mine) { http_response_code(403); echo "Not allowed: you can remove only your own files"; exit; }
	if (is_file($target_dir.$name)) { unlink($target_dir.$name); }
	echo "Removed"; exit;
}

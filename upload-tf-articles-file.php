<?php include "includes/includes.php";?>

<?php
$target_dir ="E:/New_Workflow_Files/AUTOMATION-SYSTEM/DEV-JRNL/"; 


	
 
if (!(is_dir($target_dir))) 
	{
		if (!mkdir($target_dir, 0755, true)) 
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
    

	$target_file = $target_dir."/" . basename($_FILES["file"]["name"]);

	$msg = "";
	if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_dir."/".$_FILES['file']['name'])) {
	    $msg = "Successfully uploaded";
	    	        $uf="Files uploaded --> ".$_FILES['file']['name'];

	          //insert log 
	}else{
	    $msg = "Error while uploading";
	      $uef="Files Error uploading -->".$_FILES['file']['name'];

	}
	echo $msg;
}

// Remove file
if($request == 2){
	$filename = $target_dir."/".$_POST['name']; 
	$rf="Files removed -->".$filename;
	unlink($filename); exit;
}

 //insert log 

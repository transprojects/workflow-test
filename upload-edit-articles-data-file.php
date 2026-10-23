<?php include "includes/includes.php";?>

<?php

echo $_POST['j_id'];
$journaldetails = $auth->getJournalCustomer($_POST['j_id']);
            

$Cn=$journaldetails[0]['cust_name'];
$jn=$journaldetails[0]['j_code'];
$article_id=$_POST['pub_id'];

    //echo $target_dir ="uploads/".$Cn."/".$jn."/".$article_id;  
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	
	 $work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
	
	echo  $target_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/".VOLUME_FOLDER_NAME.$_POST['group_folder_name']."/$article_id/".$_POST['stage']."/";

	
 
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

    			$insert_log=$auth->insertLog('',$article_id,'2','LINUX','FP',$uf,'1');
	          //insert log 
	}else{
	    $msg = "Error while uploading";
	      $uef="Files Error uploading -->".$_FILES['file']['name'];

    			$insert_log=$auth->insertLog('',$article_id,'2','LINUX','FP',$uef,'0');
	}
	echo $msg;
}

// Remove file
if($request == 2){
	$filename = $target_dir."/".$_POST['name']; 
	$rf="Files removed -->".$filename;
	    			$insert_log=$auth->insertLog('',$article_id,'2','LINUX','FP',$rf,'0');

	unlink($filename); exit;
}

 //insert log 

<?php include "includes/includes.php";?>

<?php

$result = $db->query("SELECT * FROM inw_inward_dtl WHERE pub_id = '".$_REQUEST['pub_id']."'"); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}
$journaldetails = $auth->getJournalCustomer($app_res['j_id']);
            

$Cn=$journaldetails[0]['cust_name'];
$jn=$journaldetails[0]['j_code'];
$article_id=$app_res['pub_id'];
$stage=$_REQUEST['stage'];

    //echo $target_dir ="uploads/".$Cn."/".$jn."/".$article_id;  
	
		$target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/";  // Upload directory



	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$volume_no='Vol00000';
 
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

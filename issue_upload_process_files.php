<?php include "includes/includes.php";

$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['vi_id']." ");	

$article_id = $_POST['vi_id'];
$toatl_count=$result->num_rows;
$app_res = array();
if($toatl_count>0)
{		
	$app_res = $result->fetch_assoc();
	/// Get the Customer Details /////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$app_res['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();


	//// Journal name ///	
	$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$app_res['j_id']."';";
	$steps = $db->query($query) or ($db->error);
	$jninfo = $steps->fetch_assoc();

	$Cn= $crow['cust_name'];
	$J_name = $jninfo['j_code']; 	
		
}

$volume_folder = 'Vol'.$app_res['volume_number'].$app_res['issue_number'];
$target_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$J_name."/".$volume_folder."/".$app_res['stage']."/misc";  // Upload directory
			  
    
 
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


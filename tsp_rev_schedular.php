<?php 

	// include('dbc.php'); 		
	$REV_INPUT_PATH = REV_INPUT_PATH."TSP/";

	$files = scandir($REV_INPUT_PATH);
	$file_count = 0;
	foreach ($files as $file) 
	{
		if(is_file($REV_INPUT_PATH.$file))
		{
			$file_count++;
			
			$info = new SplFileInfo($REV_INPUT_PATH.$file);
			$file_type = $info->getExtension();
			$names = trim(str_replace(".".$file_type," ",$file));
			$file_names = explode(" ",$names);
			$file_name = "";
			for($i= 0; $i < sizeof($file_names); $i++)
			{
				$name = $file_names[$i];
				if(startsWith($name, "TSP_"))
				{
					$file_name = trim($name);
					break;
				}
			}
			
			if($file_name != "")
			{
				$query = "SELECT * FROM inw_inward_dtl WHERE pub_id = '".$file_name."' ORDER BY id DESC LIMIT 1";
				$inwds = $db->query($query) or ($db->error);
				$app_res = $inwds->fetch_assoc();
				$num_row = $inwds->num_rows;
				if ($num_row > 0) 
				{
					/// Get the REV count /// 
					$query = "SELECT *  FROM `inw_revisions_dtl` WHERE `inw_id` = '".$app_res['id']."' AND type='REV'";
					$rev_details = $db->query($query) or ($db->error);
					$rev_detail = $rev_details->fetch_assoc();
					$rev_count = $rev_details->num_rows;
					$pstage = "";
					$nstage = "";
					$revision_count = ($rev_count + 1);
					if($rev_count == 0)
					{
						$nstage = "REV".$revision_count;
						$pstage = "FP";
						
					}
					else
					{
						$nstage = "REV" . $revision_count;
						$pstage = "REV" . $rev_count;
					}
					
					/// Get the customers details ///
					$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$app_res['cust_id']."' ";
					$customers = $db->query($query) or ($db->error);
					$crow = $customers->fetch_assoc();
	
					//// Journal name ///	
					$query = "SELECT * FROM `adm_journals` WHERE j_id= '".$app_res['j_id']."';";
					$steps = $db->query($query) or ($db->error);
					$jninfo = $steps->fetch_assoc();

					$rev_dest_dir = WF_DIR."/downloads/JOURNALS/".$crow['cust_name']."/".$jninfo['j_code']."/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$nstage."/";  // Upload directory */`
	
					if (!(is_dir($rev_dest_dir))) 
					{
						if (!mkdir($rev_dest_dir, 0777, true)) 
						{
							die('Failed to create folders...$jn');
						}
					} 
					rename($REV_INPUT_PATH.$file,$rev_dest_dir.$file);
					
					
					
					$Cn= $crow['cust_name'];
					$jn= $jninfo['j_code'];	
					
					$artile_name= $artile_name;		
					$download_folder = 'downloads';
					$production_folder = 'production';
					$journal_folder = 'JOURNALS';
					
					$production_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$pstage."/";
					$production_dest_dir = WF_DIR."/$production_folder/$journal_folder/$Cn/$jn/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$nstage."/";
					rmove($production_dir, $production_dest_dir);
				
					/*
					* Checking the due date is sunday or not
					* If due date is sunday then add 1 day to due date 
					* If due date is second saturday then add 2 days to due date 
					*/
			
					$dueDate = date('Y-m-d', strtotime('now + 1day'));
					$receivedDate = date('Y-m-d', strtotime('now'));

					$timestamp = strtotime($dueDate);
					$weekday= date("l", $timestamp );
					$normalized_weekday = strtolower($weekday);
					
					$firstOfMonth = strtotime(date("Y-m-01", $timestamp));
					$week_number = intval(date("W", $timestamp)) - intval(date("W", $firstOfMonth)) + 1;
					
					
					if($normalized_weekday == "sunday")
					{
						$dueDate =  date('Y-m-d', strtotime("+1 day", strtotime($dueDate)));
					}
					else if($normalized_weekday == "saturday" && $week_number == "2")
					{
						$dueDate =  date('Y-m-d', strtotime("+2 day", strtotime($dueDate)));
					}
					
			
					//// Get the worflow Steps ////
					$query = "SELECT * FROM `adm_workflow_master` WHERE wf_name = '".$jninfo['j_rev_wf']."';";
					$steps = $db->query($query) or ($db->error);
					$deptinfo = $steps->fetch_assoc();
					$dept_info = explode(',',$deptinfo['wf_code']);
					
					/////// Get the department name /////
					$result = $db->query("SELECT * FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
					$toatl_count=$result->num_rows;
					$dept_res = array();
					if($toatl_count>0)
					{
						$dept_res = $result->fetch_assoc();
					}
					
					
					$insert_query = "INSERT INTO `inw_revisions_dtl` (`inw_id`,revision_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`, `type`) VALUES ('".$app_res['id']."','".$revision_count."','".$receivedDate."', '".$dueDate."', '".$app_res['page_count']."', '', '".$rev_dest_dir."','1', NOW(),'REV');";	
			
					//echo $insert_query; exit;
					$db->query($insert_query) or die($db->error);
					/// update the stage and department and status in inw details table /////
		
					$prj_update_query="UPDATE inw_inward_dtl SET stage = '".$nstage."', wf_id = '".$deptinfo['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = '".$app_res['id']."' ";
					$db->query($prj_update_query) or die($db->error);	
					echo "<br><span class='light-green'> <b>$file File imported successfully</b></span>";
					
				}
				else
				{
					$rev_reject = $REV_INPUT_PATH."Rejected_files/";
					if (!(is_dir($rev_reject))) 
					{
						if (!mkdir($rev_reject, 0777, true)) 
						{
							die('Failed to create folders...$rev_reject');
						}
					}
					rename($REV_INPUT_PATH.$file,$rev_reject.$file);
					echo "<br><span class='red'></b> $file File Rejected</b></span>";
				}
			}
			else
			{
				$rev_reject = $REV_INPUT_PATH."Rejected_files/";
					if (!(is_dir($rev_reject))) 
					{
						if (!mkdir($rev_reject, 0777, true)) 
						{
							die('Failed to create folders...$rev_reject');
						}
					}
					rename($REV_INPUT_PATH.$file,$rev_reject.$file);
					echo "<br><span class='red'></b> $file File Rejected</b></span>";
			}
		}
	}
	
	if($file_count == 0)
	{
		echo "<br><br><b>There is no files in.... $REV_INPUT_PATH</b>";
	}
	
/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function rmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } 
		else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				rmove($f->getRealPath(), "$dest/$f");
				if(empty($f))
				{
					unlink($f);
				}
				// unlink($f->getRealPath());
			}
        }
    }
    //unlink($src);
}


	// Function to check string starting 
// with given substring 
function startsWith ($string, $startString) 
{ 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
} 

?>
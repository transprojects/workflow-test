<?php 
include ('includes/includes.php');



function frcopy($src, $dest){

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
        if($f->isFile()) {
            copy($f->getRealPath(), "$dest/" . $f->getFilename());
			///// Keep the same file time //////
			$dt = filemtime($f->getRealPath());
			if ($dt != FALSE)
			{ 
				touch("$dest/" . $f->getFilename(), $dt);
			}
						
        } else if(!$f->isDot() && $f->isDir()) {
            frcopy($f->getRealPath(), "$dest/$f");
        }
    }
}


if(isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
{
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();		
	}
	
	$ISSCOR_NUM = intval(preg_replace("/([a-zA-Z])/","",$app_res['stage'])) - 1;
	$LAST_STAGE = preg_replace("/(.*)(\\d+)/","$1",$app_res['stage']).$ISSCOR_NUM;
	
	$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$LAST_STAGE."/Delivery";
	$fdest = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery";
	
	
	if(is_dir($fsrc))
	{
		mkdir($fdest, 0777, true);
		frcopy($fsrc,$fdest);
	}
			
	$set_status = "`status` ='Client Review',";
	
		
	$prj_update_query="UPDATE inw_inward_dtl SET `status` ='Delivery', `department_id` ='15' WHERE id = ".$_REQUEST['id']." ";
		
	//echo $prj_update_query; exit;
	$db->query($prj_update_query);
	
	header('location:show_issues.php?id='.$_REQUEST['sid']);
	exit;
}
?>
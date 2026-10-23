<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$err = [];
include_once "includes/conn.php";
include_once "includes/global_variables.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update')
{
	$graphic_query_status = $graphic_query_status ?? [];
	
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	if(!empty($post['assign_department']))
	{
			$assign_dep=$post['assign_department'];
			$assign_dep_query=",`assign_department` ='". $assign_dep."'";
	}
	else
	{
	$assign_dep_query=",`assign_department` =NULL";
	}
	if(!empty($post['graphic_query']))
	{
	$graphic_query = $graphic_query_status[$post['graphic_query']] ?? '';
	$graphic_querys=",graphic_query='".$graphic_query."'";
	}
	else
	{
	$graphic_querys=",graphic_query=NULL";
	}
	
	// ✅ Clean comments handling
	$post['comments'] = trim($post['comments'] ?? '');

	if ($post['comments'] !== '') {
		$comments_query = ", `comments` = '".$db->real_escape_string($post['comments'])."'";
	} else {
		$comments_query = ", `comments` = NULL";
	}
	
	$query = "SELECT `cust_id`,`j_id`,`activity` FROM `inw_inward_dtl` WHERE id = '".$post['id']."';";
	$articledetails = $db->query($query) or ($db->error);
	$artilce_info = $articledetails->fetch_assoc();
	
		$query = "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '".$artilce_info['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		 $Cn= $crow['cust_name'];

	
	$dest_web = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Web";   //Graphics XML source folder or file
	$dest_print = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Print";   //Graphics XML source folder or file
	
	
		$stage_issue=preg_replace("/ISS([0-9]+)/","ISS",$post['stage']);
				if($stage_issue!='ISSCOR')
				{

			    $pbkp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/productionbackup/backup_department_files.py";
				$dest_pbkp_dept_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/"; // destination folder or file 
				$python_bkp_article_dept ='C:\\Windows\\py.exe "'.$pbkp_python_file.'" "'.$dest_pbkp_dept_file.'" "'.$post['id'].'" "'.$post['stage'].'" "'.$post['department_id'].'" "'.$post['tr_id'].'"';
				ob_start();
				system($python_bkp_article_dept);
				ob_end_clean();
				}
				
				
				
	if(($post['status'] == 1) && ($artilce_info['cust_id']=='33' || $artilce_info['cust_id']=='42' ))
	{
			//$config['IMAGE_CONVERSION'] = "E:/Transforma/PRINT_TO_WEB/run.bat";
			//$perl = '"'.$config['IMAGE_CONVERSION'].'" "'.$dest_print.'" "'.$dest_web.'" "'.strtoupper($Cn).'"';//exit;
			//exec($perl); 
	}
	//exit;
		if($post['status'] == 1 || $post['status'] == 6)
		{
			$update_query = "UPDATE inw_transactions 
			SET `end_time` = NOW(),
				current_status = 'Completed',
				`completion_status` = '".$post['status']."'
				".$comments_query."
				".$assign_dep_query."
				".$graphic_querys."
			WHERE id = ".$post['tr_id']." ";
		}
		else
		{
			$update_query = "UPDATE inw_transactions 
			SET `end_time` = NOW(),
				current_status = '".$project_status_array[$post['status']]."',
				`completion_status` = '".$post['status']."'
				".$comments_query."
				".$assign_dep_query."
				".$graphic_querys."
			WHERE id = ".$post['tr_id']." ";
		}
	    if(!$db->query($update_query)){
			echo json_encode([
				"status" => "error",
				"message" => "Transaction update failed"
			]);
			exit;
		}
							
	
		if($post['assign_department']=='')
		{
			
		//echo "asssigned:" .$post['assign_department'];exit;

			//// Get the worflow Steps ////				
			/// If completed the work files we will move to other department ////
			if($post['status'] == 1)
			{
			
			$department_id = 10; // This is the Default Pagination department ID	
				
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}	
	
			//// Add the fig count vlues here /////
			
			$prj_update_query="UPDATE inw_inward_dtl SET `fig_complete_status` ='1',img_disk_count = '".$post['img_disk_count']."',img_redraw_count = '".$post['img_redraw_count']."',img_relabel_count = '".$post['img_relabel_count']."',img_color_figures = '".$post['img_color_figures']."' WHERE id = ".$post['id']." ";
			$db->query($prj_update_query);
				
			///// Insert the checklist data to DB //////////
			foreach($_POST['checklist'] as $cl_id=>$value)
			{
				$insert_query = "INSERT INTO `inw_to_checklist` (`inw_id`,dept_id,stage,`cl_id`, `submited_value`, `created_by`, `created_on`) VALUES ('". $post['id']."', '".$post['department_id']."', '".$post['stage']."','". $cl_id."','". $value."', '". $_COOKIE['user_id']."', CURRENT_TIMESTAMP);";	
				//$db->query($insert_query) or die($db->error);
			}
			
				/// Move the Files From On Dept to another dept ////
				
					
$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Print";   //Graphics PAG source folder or file

$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/Print/";   // PAG destination folder or file 




$department_id = 5; // This is the Default XML department ID	
				
/////// Get the department name /////
$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
$toatl_count=$result->num_rows;
$dept_res = array();
if($toatl_count>0)
{
	$dept_res = $result->fetch_assoc();
}
			
		
$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Web";   //Graphics XML source folder or file

$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/Web/";   // XML destination folder or file 

		
			}
			
		
		
		
			//exit;
		}
		else
		{
			
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$post['assign_department'].";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			$assigned_dept_name = '';
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
				$assigned_dept_name = $dept_res['dept_name'];
				
			}
		
			
			/// Move the Files From On Dept to another dept ////
			
			$department_id = 10; // This is the Default Pagination department ID	
				
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Print";   //Graphics PAG source folder or file

			$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/Print/";   // PAG destination folder or file
			
			///// Copy the files from Graphics Print to PAG		

			$department_id = 5; // This is the Default XML department ID	
							
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
						
					
			$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/Web";   //Graphics XML source folder or file

			$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/Web/";   // XML destination folder or file 
			// rmdir($dest);
			/* delete_directory($dest);
			if(!mkdir($dest, 0777, true))
			{
				if(!mkdir($dest, 0777, true))
				{
					$err[] = "Error - Print folder in XML department is already opend, we can not create it now. Please try again.";
				}	
			}
			///// Copy the files from Graphics Web to XML			
			rcopy($src,$dest); */
			
			if(empty($err))
			{
				$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$assigned_dept_name."',`department_id` ='". $post['assign_department']."',`assign_department_id` ='". $post['assign_department']."' WHERE id = ".$post['id']." ";
				$db->query($prj_update_query);
																						 
			}
			
			
		}
		
		 //// adding the logic to update the status in inw_inward_details table to fetch data directly

$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$_COOKIE['department_id']."'"));
$user_working_status = '';
if($post['status'] == 1)
{
    $graphic_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>';
}
else if($post['status'] == 6)
{ 
    $graphic_working_status = '<span class="label label-sm label-success">'.$assigned_dept_name.'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';

    $user_working_status = ", user_working_status = '".$graphic_working_status."'";
}
else if($post['status'] == 7)
{
    $graphic_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $graphic_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
$prj_update_query="UPDATE inw_inward_dtl SET graphic_working_status = '".$graphic_working_status."',last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";			
$db->query($prj_update_query);	


		
			
echo json_encode([
    "status" => "success",
    "message" => "Art work Status Updated Successfully as ".$project_status_array[$post['status']]." ",
    "trigger_sync" => ((int)($artilce_info['cust_id'] ?? 0) === 82 && (int)($post['status'] ?? 0) === 1),
    "pub_id" => $post['pub_id'] ?? "",
    "vf" => $post['volume_folder'] ?? "",
    "stage" => $post['stage'] ?? ""
], JSON_UNESCAPED_SLASHES);

exit;
	}
/////////////// Update completed ///////////////
?>
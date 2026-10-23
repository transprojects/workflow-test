<?php include "includes/conn.php";?>
<?php include "includes/global_variables.php";?>
<?php 
$user_type='N';
$update_fs='NO';

if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		
$app_res = $result->fetch_assoc();
$user_details=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$app_res['present_user_id']."'"));
$full_name=$user_details['full_name'];
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET file_status='Inprogress' WHERE id = ".$_REQUEST['aid']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		
		if($app_res['volume_folder'] =="Vol00000")
		{
			$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
		}
	}
	
	/////////// Here Update the Take Over Transaction record //////
	//if($_REQUEST['tid']!="new")
	/* if(isset($_REQUEST['tid']))
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Inprogress',`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);	
	}
	*/
	



/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///
if($user_type=='S') 
{	
$user_sub=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$app_res['present_user_id']."'"));
//echo $_REQUEST['department_id'];
	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `sub_user_id` =  '".$app_res['present_user_id']."'  AND current_status ='Inprogress';"); 	

}
else
{
	
	
	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$app_res['present_user_id']."' AND `created_by` = '".$app_res['present_user_id']."' AND current_status ='Inprogress';"); 	

	
}

	
	 $transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
	 	  $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`,`sub_user_id`,`process_ulogin_type`,`force_started`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$app_res['present_user_id']."', NOW(), '0', '".$app_res['present_user_id']."',NOW(),'".$app_res['present_user_id']."','".$user_type."','".$update_fs."');";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
		$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$app_res['department_id']."'"));

		$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$full_name.'</span>|<span class="label label-sm label-warning">Inprogress</span>';
	 	 $prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$transaction_id."' WHERE id = ".$app_res['id']."; ";			
		$db->query($prj_update_query);			
	}
	
	
	 /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
	
}
?>
                        <?php
	//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$app_res['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
	}
						?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <!-- Data Table CSS -->
    <style>
th  {
  color: #336199 !important;
  background-color: #fff1f1 !important;
  border-top: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
}
 td {
  color: #336199 !important;
  background-color: #fff !important;
  border-top: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
}
/* td:last-child{
	border-right:  1px solid #dedef3 !important;
} */
table.dataTable tbody td:not(:last-child):after {
    content: "";
    height: 15px;
    width: 0.5px;
    border: none !important;
    position: absolute;
    right: 0;
    top: 20px;
}
.startnav-clock{
	right: 0 !important;
	-webkit-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	-moz-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	background-color: #ec5f19db !important;
	color: #fff !important;
	border-radius: 22px !important;
	font-weight: 600 !important;

}
  </style>
  
    <link href="http://103.66.12.3:8010/workflow-new/css/main.min.css" rel="stylesheet" />
    <link href="http://103.66.12.3:8010/workflow-new/styles.css" rel="stylesheet" />
    <link href="http://103.66.12.3:8010/workflow-new/assets/css/select2.min.css" rel="stylesheet" />
    <link href="http://103.66.12.3:8010/workflow-new/assets/css/dropzone.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="http://103.66.12.3:8010/workflow-new/assets/css/richtext.min.css" />
    <!--<link rel="stylesheet" href="http://103.66.12.3:8010/workflow-new/assets/css/jquery-ui.css"> -->
    <link type="text/css" href="http://103.66.12.3:8010/workflow-new/assets/css/ui.multiselect.css" rel="stylesheet" />
	<link type="text/css" rel="stylesheet" href="http://103.66.12.3:8010/workflow-new/assets/css/jquery-ui.css" />


<!-- Include jQuery -->




<!-- Include DataTables CSS -->


<!-- Include DataTables JavaScript -->


  </head>	

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

<?php
$fin_stage= preg_replace('/[0-9]+/', '', $app_res['stage']);
?>


      <!-- Page body starts -->
      
     

      

        <!-- form section  -->
        <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">

       
            <div class="container mt-4">
       		 <form class="form-horizontal" id="validation-form" method="post">

              <div class="formcont1heading">Work Status and comments to internal department</div>
			  <div class="p-5 pb-0 d-flex flex-column gap-2"> 
              
										  
							   
							
						  
						  
                        
                            <input type="hidden" name="action" value="update">
							<input type="hidden" name="cust_id" value="<?php echo $app_res['cust_id']; ?>" />
							<input type="hidden" name="j_id" value="<?php echo $app_res['j_id']; ?>" /> 
							<input type="hidden" name="tr_id" value="<?php echo $transaction_id; ?>" />
                            <input type="hidden" name="id" value="<?php echo $app_res['id']; ?>" />
                            <input type="hidden" name="wf_id" value="<?php echo $app_res['wf_id']; ?>" />
                            <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                            <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">                           
							<input type="hidden" name="department_id" value="<?php echo $app_res['department_id']; ?>" />
                            <input type="hidden" name="dept_name" value="<?php echo $pdept_res['dept_name']; ?>" />
                            <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
                            <input type="hidden" name="v_issue_id" value="<?php echo $app_res['v_issue_id']; ?>" />
                            <input type="hidden" name="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
							<input type="hidden" name="xperttrack_sync" value="<?php echo $app_res['xperttrack_sync']; ?>" />
                            
							
						

                    <div class="col-12 col-sm-6 col-lg-4">
                    <label class="mb-2" >Choose Status :<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Status" name="status" id="status">
				                                    <option value="">-Select Status-</option>

                                <?php foreach($project_status_array as $id=>$val){?>
										<?php 
										if($val!='Query')
										{
										?>
                                     <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
									 <?php 
										}
										?>
                                 <?php } ?>
                                 </select>
                </div>

                           <div class="col-12 col-sm-3 col-lg-2">
                          <label class="mb-2" >Page Count :<span style="color: red">* </span></label>
                          
                            <div class="clearfix">
                              <input type="number" name="page_count" id="page_count" class="form-control" required  placeholder="Page Count" value="<?php echo ($app_res['page_count'] > 0) ? $app_res['page_count'] : '0'; ?>" onkeyup="PreventFirstZero(event);">
                            </div>
                         
                        </div>
						
                        <div class="space-2"></div>
                        <div class="form-group" id="assign_department_div" style="display:none;">
                        <label class="mb-2" >Assign Department  :<span style="color: red">*</span></label>
                       <div class="col-12 col-sm-6 col-lg-4">
                          <div class="clearfix">
                            <?php
	
		$workflow_query = "SELECT wf_code FROM `adm_workflow_master` WHERE
		`wf_id` =".$app_res['wf_id']."";
		$workflow_details = $db->query($workflow_query) or
		($db->error);
		$workflow_info = $workflow_details->fetch_assoc();
		$all_dpt_info = explode(',',$workflow_info['wf_code']);
		$all_dept_array = $all_dpt_info;
		/// add graphics dpt in list ////
		if (!in_array("9", $all_dpt_info))
		{			
			$all_dpt_info[]="9"; 
		}
		//$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code != ".$app_res['department_id']." ORDER BY `dept_name` ASC";
		$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
		= 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code NOT IN ( ".$app_res['department_id'].",15 ) ORDER BY `dept_name` ASC";
		$customers = $db->query($query) or ($db->error);
			 		

	?>
                            <select id="assign_department" name="assign_department" data-placeholder="Choose" class="form-select customer_drop">
                              <option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" ><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
						</div> 

						 <div class="space-2"></div>
                        <div class="form-group" id="assign_user" style="display:none;">
                        <label class="mb-2" >Select User :<span style="color: red">*</span></label>

                        
                       <div class="col-12 col-sm-6 col-lg-4">
                          <div class="clearfix">
                            <select id="a_user_id" name="a_user_id" class="form-select customer_drop" data-placeholder="Choose">
                              <option value="">-Select-</option>
                            </select>
                          </div>
                        </div>
                       </div>
                        

        
                        
                        <div class="space-2"></div>
                         
                          <div class="row" id="checklist_table" style="display:none;">
                           <h3 class="formcont1heading" style="position: relative; left: -37px;">Checklist Form</h3>
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12 pt-3">
                                  <table  id="simple-table" class="table  table-bordered table-hover">
                                    <thead>
                                      <tr>
                                        <th>Details</th>
                                        <th width="10%">Yes</th>
                                        <th width="10%">NA</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php
		$query = "SELECT * FROM `jbm_checklist` WHERE (`stage` = '".$app_res['stage']."' OR `stage` = 'BOTH') AND `dept_id` = '".$app_res['department_id']."' ORDER BY `display_order` ASC ";
		$qry_result = $db->query($query) or ($db->error);		
		while($row = $qry_result->fetch_assoc()){ ?>
		
                                      <tr>
                                        <td><?php echo $row['description']; ?></td>
                                        
<td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="Yes" checked="checked"/>
                                            </td><td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="NA"/>
                                            </td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
                <div class="col-12 col-sm-12 col-lg-12" id="tmpa" >
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="vcomments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>


                <div class="container-fluid mt-5 mb-5">
			
			              
				<div class="container-fluid mt-5 mb-5 d-flex justify-content-center">
                <div class="row d-flex gap-3 justify-content-center">
                    <div class="col-12 col-lg-5 form_btn cancelbtn d-flex justify-content-md-end justify-content-center">
                        <button type="submit">Cancel</button>
                    </div>
                    <div class="col-12 col-lg-5 form_btn nxtbtn d-flex justify-content-center">
                        <button type="submit" id="submit-btn">Submit</button>
                    </div>
                </div>
          	  </div>
			  
						
            </div>
			
			</div>
        
		</form>
		</div>
		
			
            </div>
			</div>
	
			
					
     <!-- Footer Section -->
	 </div>


<script src="http://103.66.12.3:8010/workflow-new/assets/js/bootstrap.js"></script>

<!-- Include jQuery -->

<script src="http://103.66.12.3:8010/workflow-new/assets/js/jquery-3.6.0.js"></script>


<script src="http://103.66.12.3:8010/workflow-new/assets/js/jquery-3.6.0.min.js"></script>


  <script type="text/javascript">
    $(document).ready(function(){
  $('#assign_department').on('change',function(){
      var dep_id = $('#assign_department').val();
  //alert(dep_id);
  if(dep_id==1 || dep_id==2)
  {
  $('#assign_user').show();	
  }
      if(dep_id==1 || dep_id==2){
    
          $.ajax({
              type:'POST',
              url:'http://103.66.12.3:8010/workflow-new/get_cepe_user.php',
              data:'dept_id='+dep_id,
              success:function(html){
                  $('#a_user_id').html(html);                    
              }
          }); 
      }else{
    $('#assign_user').hide();
          $('#a_user_id').html('<option value="">Select User first</option>');
          
      }
  });
    
    


});
$(document).ready(function(){	
$('#status').on('change',function()
{
      var status = $(this).val();
      //alert(status);
  
  if(status==2 || status == 6 || status == 8 || status == 9)
  {
    
    $('#tmpa').show();
    $('#notmpa').hide();
  }
  else
  {
        
         $('#notmpa').show();
     $('#tmpa').hide();

      }
  
      if(status == 6)
  {
    $('#assign_department_div').show();
    //$('#assign_user').show();
  }
  else
  {
          $('#assign_department_div').hide();
    //$('#assign_user').hide();
          
      }
   if(status == 7)
  {
    $('#query_div').show();
  }
  else
  {
          $('#query_div').hide();
          
      } 
   if(status ==1)
  {
    $('#checklist_table').show();
  }
  else
  {
            $('#checklist_table').hide();
          
      }
  
  
  });
});
  
    
  </script>

  
  <?php 
  
  
/** copy files
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
function rcopy($src, $dest,$accept_files_list){

   // If source is not a directory stop processing
    if(!is_dir($src)) 
	{
		die('Source is not directory');
		//return false;
	}

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
            //copy($f->getRealPath(), "$dest/" . $f->getFilename());
			$filename = $f->getFilename();
			///// Move the dept related files only ////
			for($i=0; $i < sizeof($accept_files_list); $i++)
			{
				$accept_files_list[$i] = trim($accept_files_list[$i]);
				if (strrpos($filename, $accept_files_list[$i]) === strlen($filename)-strlen($accept_files_list[$i]))
					{
						$sfn = $src.$filename;
						$dfn = $dest.$filename;
						if(file_exists($sfn))
						{							
							if(!copy($sfn, $dfn)) 
							{
								echo 'Error in file moving';	
								//die();	
							}
							else
							{
								///// Keep the same file time //////
								$dt = filemtime($sfn);
								if ($dt != FALSE)
								{ 
									touch($dfn, $dt);
								}
							}
						}					
					}
					
			}
        } else if(!$f->isDot() && $f->isDir()) {
            rcopy($f->getRealPath(), $dest,$accept_files_list);
        }
    }
}

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
  if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	
		
	//// Get the article details ////
	$query = "SELECT `cust_id`,`j_id`,`activity` FROM `inw_inward_dtl` WHERE id = '".$post['id']."';";
	$articledetails = $db->query($query) or ($db->error);
	$artilce_info = $articledetails->fetch_assoc();
	
	//// Get the journal details ////
	$query = "SELECT `tvl_work_departments`,`j_proofing_system_server` FROM `adm_journals` WHERE j_id = '".$artilce_info['j_id']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	
	//// Here we will get the department's to work in TVL //
	$tvl_dept_info = explode(',',$journalinfo['tvl_work_departments']);	
	
	
	
		
		$query = "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '".$post['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code,j_hid FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();
		
		$Cn= $crow['cust_name'];
		$J_name = $jninfo['j_code']; 	
		
		mysqli_query($db,"SET sql_mode = ''");
		
		
		
			   // $pbkp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/productionbackup/backup_department_files.py";
				//$dest_pbkp_dept_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/"; // destination folder or file 
				// $python_bkp_article_dept ='C:\\Windows\\py.exe "'.$pbkp_python_file.'" "'.$dest_pbkp_dept_file.'" "'.$post['id'].'" "'.$post['stage'].'" "'.$post['department_id'].'" "'.$post['tr_id'].'"';
				//system($python_bkp_article_dept);
		
		if($post['dept_name']=="PRQC" && $post['cust_id']=="66" && $post['stage']!="FP")
		{
		$prj_update_querys=mysqli_query($db,"UPDATE  inw_meta_data SET  first_page ='".$post['fpage']."', last_page ='".$post['lpage']."' WHERE inw_id = ".$post['id']." ");
		}
		
		//if($post['status'] == 1)
	//{
		if($post['dept_name']=="PRQC" && $post['cust_id']=="42" && $post['stage']=="FP")
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  corresponding_author ='".$post['corresponding_author']."', corresponding_mail ='".$post['corresponding_mail']."' WHERE id = ".$post['id']." ");
	
		}
	//}
		
	if($post['status'] == 1 || $post['status'] == 6 )
	{
	//$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Completed',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."' WHERE id = ".$post['tr_id']." ";	
			$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$post['tr_id']." ";

	}
	else
	{
		
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$post['tr_id']." ";
		
		if($post['status'] == 7)
		{
			$user_jour=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$jninfo['j_hid']."'"));
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$app_res['present_user_id']."'"));

			
		
//echo "insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$app_res['present_user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."')";exit;
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$app_res['present_user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','Query')");
			
		}
	}
	
	if($db->query($update_query))
	{
		if($post['assign_department']=='')
		{
			if($post['status'] == 1 && $post['cust_id']==33)
			{
				if($post['department_id']==24)
				{
				include("pre-clean.php");
				}
			}
			if($post['status'] == 1 && $post['cust_id']==42)
			{
				if($post['department_id']==22)
				{
				include("pre-clean-tsp.php");
				}
			}
			
			if($post['status'] == 1 && $post['cust_id']==53)
			{
				$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$post['stage']);

				if($post['department_id']==5 && $stage_f='FIN')
				{
				$now_python_file = "E:/NOW-ZIP/NOW_Finals_ZIP.py";
				$dest_python_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
				$python_update_article ='C:\\Windows\\py.exe "'.$now_python_file.'" "'.$dest_python_xml_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1"';
				system($python_update_article);
				}
			}
			
			
			if($post['status'] == 1 && $post['cust_id']==42 && $post['department_id']==1)
			{
				$ce_perl_file = "E:/Transforma/Word2HTML/Exe/METACONFIG.pl";
				$dest_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/CE/".$post['pub_id'].".xml";   // destination folder or file 
				$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$ce_perl_file.'" "'.$dest_xml_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1"';
				system($perl_update_article);				
			}
			//// Get the workflow Steps ////
			$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
			$steps = $db->query($query) or ($db->error);
			$deptinfo = $steps->fetch_assoc();
			$dept_info = explode(',',$deptinfo['wf_code']);
			$dept_key = array_keys($dept_info, $post['department_id']);		
					
			$dept_key = $dept_key['0'];			
			$dept_key = ($dept_key + 1);
			
			if($post['status'] == 1 || $post['status'] == 12)
			{			
				$department_id = $dept_info[$dept_key];
			}
			else
			{
				$department_id = $post['department_id'];
			}
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name,accept_files FROM adm_dept_master WHERE dept_code = ".$department_id.";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}	
			
		
		$err = array();
		///// NCC Client Check List ////
		if($dept_res['dept_name'] == 'Delivery')
		{	
			$tex_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
			//include('ncc_check_list.php');
		}
		if(count($err) == 0)
		{
			/// We update the page count when PRQC Team enters the DATA ////
			$page_count = '';
			if($post['page_count'] > 0)
			{
				$page_count = "page_count ='".$post['page_count']."',";
				$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$post['tr_id']." ");

			} 
						
			$corresponding_author_details = "";
			
			if($post['dept_name'] == 'FXML' && $post['stage'] == "FP" )
			{
				$xml_file_path = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".xml";
				
				if(file_exists($xml_file_path))
				{
				$html = file_get_contents($xml_file_path);
				
				$html = preg_replace("/(\n|\r|\r\n)/i", " ", $html);
				//// Get the title from XML file ////
				$title = explode("</title-group>",$html);
				$title = preg_replace("/(.*)(<article-title>)(.*)(<\/article-title>)(.*)/i", "$3", $title[0]);
				//// Get the author details from XML file ////
				$corresp = str_replace("<bold>", "", $html);
				$corresp = str_replace("</bold>", "", $corresp);
				$corresp = preg_replace("/(.*)(<corresp (.+?)>)(.*)(<\/corresp>)(.*)/i", "$4", $corresp);
				$corresp = preg_replace("/(.+?)(:\s+)(.*)/i", "$3", $corresp);
				$corresp = preg_replace("/((\,)\s+)/i", "", $corresp);
				$corresp = preg_replace("/(Email\:)/i", "", $corresp);				

				$corresps = explode("; ",$corresp);
				
				
				
				$corresp_name = "";
				$corresp_mail = "";
				if(sizeof($corresps) > 0)
				{
				for($i = 0 ; $i < sizeof($corresps); $i++)
				{
						$corresps_au = $corresps[$i];
						$corresps_au_name = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$1", $corresps_au));
						$corresps_au_name = trim(preg_replace("/(.*)(\.)/i", "$1", $corresps_au_name));
						$corresps_au_mail = trim(preg_replace("/(.*)(<email>)(.*)(<\/email>)(.*)/i", "$3", $corresps_au));
				
				
				if($corresp_name !="")
				{
					$corresp_name .= ", ".$corresps_au_name;
				}
				else
				{
					$corresp_name .= $corresps_au_name;
				}
				
				
				if($corresp_mail !="")
				{
					$corresp_mail .= ",".$corresps_au_mail;
				}
				else
				{
					$corresp_mail .= $corresps_au_mail;
				}
				}
				
				}
				$corresponding_author_details = "corresponding_author ='".$corresp_name."', corresponding_mail ='".$corresp_mail."', article_title='".$title."', ";
				
				}

			}	
			//}
			$prj_update_query="UPDATE inw_inward_dtl SET ".$corresponding_author_details ." ".$page_count." `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`assign_department_id` ='0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."', present_user_id = '0' WHERE id = ".$post['id']." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			/// If completed the work files we will move to other department ////
			if($post['status'] == 1  || $post['status'] == 12)
			{
			///// Insert the checklist data to DB //////////
			foreach($_POST['checklist'] as $cl_id=>$value)
			{
				$insert_query = "INSERT INTO `inw_to_checklist` (`inw_id`,dept_id,stage,`cl_id`, `submited_value`, `created_by`, `created_on`) VALUES ('". $post['id']."', '".$post['department_id']."', '".$post['stage']."','". $cl_id."','". $value."', '". $app_res['present_user_id']."', CURRENT_TIMESTAMP);";	
				//$db->query($insert_query) or die($db->error);
			}
			
				/// Move the Files From On Dept to another dept ////
				
					
	$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/";   // source folder or file
	



///// Get the Accept files from New Configuration based on Customer/Journal/Dept/Stage//////

 $fe_query = "SELECT mf_id FROM `inw_move_files` WHERE cust_id = '".$artilce_info['cust_id']."' AND j_id = '".$artilce_info['j_id']."' AND dept_id = '".$post['department_id']."';";
$fe_result = $db->query($fe_query) or ($db->error);
$fe_info = $fe_result->fetch_assoc();



//// According to New configuration we are moving the files /////
$cur_stage = preg_replace('/[0-9]+/', '', $post['stage']);
 $fef_query = "SELECT move_files,move_folders,dept_id FROM `inw_move_files_dept_wise` WHERE mf_id = '".$fe_info['mf_id']."' AND stage = '".$cur_stage."';";
$fef_result = $db->query($fef_query) or ($db->error);
while($fef_info = $fef_result->fetch_assoc())
{
	/// Create the dept destination folder /////
	$dept_result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$fef_info['dept_id'].";");
	$dept_name = $dept_result->fetch_assoc();
	
	$dest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
	//mkdir($dest, 0777, true);
	//echo $dest.'<br>';
	
		
	
	
			//zip directory check validation //
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
				$dest_err = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
				
				if($post['stage']=='FP')
				{
				 $stage_pos=1;	
				}
				else
				{
				 $stage_pos=2;		
				}
				$stage=preg_replace("/REV([0-9]+)/","REV",$post['stage']);
				$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$post['stage']);

				//echo $stage;exit;
				if($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148' )
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage_f=='FIN' && $post['cust_id']==66 && $post['dept_name']=='ePub' && $post['wf_id']=='154')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage_f=='FIN' && $post['cust_id']==42 && $post['dept_name']=='XML' && $post['wf_id']=='162')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
				elseif($stage=='REV' && $post['cust_id']!=66 && $post['dept_name']=='XML')
				{
				$config['ZIP_VALIDATOR'] = "E:/PeerJ ZIP/Transforma/Exe/EPLZIPVALIDATOR.pl";
				 $perl = 'C:\\Perl64\\bin\\perl.exe "'.$config['ZIP_VALIDATOR'].'" "'.$dest_err.'" "'.strtoupper($Cn).'" "'.$J_name.'" "'.$stage_pos.'"';
				system($perl); 
				}
			}
	
		//zip directory check validation //
		
		
	///// Moving Files ////////
	
	if($fef_info['move_files']!='')
	{
		$accept_files_list = explode("|",$fef_info["move_files"]);
		if(count($accept_files_list)>0)
		{
			if(!is_dir($dest))
			{
				///echo $dest.'<br>';
				mkdir($dest, 0777, true);
			}
			
					if($J_name!='EPL' && $J_name!='NCC' && $post['cust_id']!=33)
				{
			rcopy($src,$dest,$accept_files_list);
				}
				elseif($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
				{
						if($post['stage']!='FP' && $post['dept_name']!='PRQC')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
						if($stage!='REV' && $post['dept_name']!='XML')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
						if($post['stage']!='REV2' && $post['dept_name']!='ePub')
						{	
						rcopy($src,$dest,$accept_files_list);
						}
				}
			
			 //// Copy the Files from production folder to another system ///////
			/* 
			$work_dir = $_POST['work_dir'];
			$work_dir = str_replace(WF_DIR,BACKUP_DIR,$work_dir);
			$Backup_dest = $work_dir.$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/";   // destination folder or file 
			if (!(is_dir($Backup_dest))) 
			{
				if (!mkdir($Backup_dest, 0777, true)) 
				{
					die('Failed to create folders...$jn');
				}
			}	
			rcopy($src,$Backup_dest,$accept_files_list);  */
			
		 
		
		}
		
			//copying batch files to pag folder for automation process
		
	
		
	}
	
	///////// Moving Folders ///////
	if($fef_info['move_folders']!='')
	{
		$accept_folders_list = explode("|",$fef_info["move_folders"]);
		foreach($accept_folders_list as $folder)
		{
			
			if(is_dir($fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder))
			{
				$fsrc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$folder; 		
				$fdest = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_name['dept_name']."/".$folder; 
				mkdir($fdest, 0777, true);
				if($J_name!='EPL' && $J_name!='NCC' && $post['cust_id']!=33)
				{
				frcopy($fsrc,$fdest);
				}
				elseif($J_name=='EPL'  || $J_name=='NCC' || $post['cust_id']==33)
				{
						if(($post['stage']!='FP' && $post['dept_name']!='PRQC') && ($stage!='REV' && $post['dept_name']!='XML'))
						{	
						frcopy($fsrc,$fdest);
						}
				}
			}
			
			
		}
	}
	
	
	
		//zip directory check validation //	
		$dest_errc = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/Delivery/";   // destination folder or file 
		
		 $dests=$dest_errc.$post['pub_id'].".zip";//exit;
		 
		//echo $stage;echo "<br/>";exit;
			if($J_name=='EPL' || $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
			{
	
			if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub' && $post['wf_id']=='154') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML' && $post['wf_id']=='162') || ($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148'))
			{
				
				//echo "eos-fp-prqc";exit;

		 $html_read = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['pub_id']."_report.html";   // destination folder or file 
		 $refdata = file_get_contents($html_read); 

			//echo $html_read;
		$success=preg_match('/<body class=\"SUCCESS\">/i', $refdata);
		//echo $success;exit;
			if($success==1)
			{
				//echo "1";exit;
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."' WHERE id = ".$post['id']." ");

			}
			else
			{
					//echo $stage;echo "<br/>";
					//echo "2";
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET `status` ='".$post['dept_name']."',`department_id` ='". $post['department_id']."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."' WHERE id = ".$post['id']." ");
			//echo "UPDATE inw_transactions SET `status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."' WHERE id = ".$post['tr_id']." ";exit;
			$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$post['tr_id']." ");
			}
		  }
			}
		//  echo $stage;echo "<br/>";
		//  echo $post['dept_name'];exit;
		  
		  //zip directory check validation //
		    
			///// Artile Details /////		
			
	//exit;
	
			
}


if($post['cust_id']==42 && $post['status']==1 && $post['stage']='FP' && $post['department_id']==1 )
		{
			$ps2pdf='PS2PDF.bat';
			$xmltopdf='XML2PDF.bat';
			$dest_pag=$_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/FP/PAG/";
				if (!is_dir($dest_pag)) 
				{
					mkdir($dest_pag, 0777, true);
				}
		
			   //copy($ps2pdf, $dest_pag.$ps2pdf);
			   //copy($xmltopdf, $dest_pag.$xmltopdf);
		} 
		
			}


		} //// NCC Check List End here ////
		
////////////////////////////////////////
////////////////////////////////////////
		
		
		
			//FP
			
			
			//OTHER CUSTOMERS FOR XPERTTRACK PPMS //
			
			$ppms_current_stage=preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			//DISPATCH REV ARTICLES TO KSU VIA XPERTTRACK 
			if($ppms_current_stage == 'REV' && $post['xperttrack_sync']=='1' && $post['cust_id']=='72' && $post['status']==1 && $post['department_id']=='14')
			{
				
					if($_POST['stage']=='REV1')
					{
						//echo "email";exit;
							include("xpertrack_ksu_email_rev1.php");
					}
					elseif($_POST['stage']=='REV2')
					{
						//echo "email";exit;
							include("xpertrack_ksu_email_rev2.php");
					}
					else if($_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $_POST['stage'] != 'REV2')
					{	
						//echo "email";exit;
							include("xpertrack_ksu_email_rev2_above.php");
					} 
					
					
			//DISPATCH REV ARTICLES TO KSU VIA XPERTTRACK 
			}
			
			//DISPATCH FIN ARTICLES TO KSU VIA XPERTTRACK 
			if($ppms_current_stage == 'FIN' && $post['xperttrack_sync']=='1' && $post['cust_id']=='72' && $post['status']==1 && $post['department_id']=='5')
			{
			include("xpertrack_ksu_email_final.php");
		   //DISPATCH FIN ARTICLES TO KSU VIA XPERTTRACK 
			}
			
			//OTHER CUSTOMERS FOR XPERTTRACK PPMS //



			$tsp_current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			if($tsp_current_stage == 'FIN' && $post['cust_id']=='42' && $post['xperttrack_sync']=='0' && $post['status']==1 && $post['department_id']=='5')
			{
				if($post['j_division']=='2'){
					include("tsp_ftp_fin_computer_division.php");
				}
				if($post['j_division']=='1'){
					include("tsp_ftp_fin_regular_division.php");					
				}
			}
			
			
			
			//DISPATCH ARTICLES TO AUHTOR VIA XPERTTRACK 

			if($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='1')
			{
						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175')
				{
					///// REV1: Send REV1 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_regular_division.php");
						}

					}elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev2_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev2_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $_POST['stage'] != 'REV2' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_rev1_above_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_rev1_above_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("xpertrack_tsp_email_rev1_computer_division.php");
					}
				
				}
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev2_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("xpertrack_tsp_email_rev2_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV2 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $_POST['stage']!='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV3 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='REV2' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("xpertrack_tsp_email_rev1_above_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV3 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev1_regular_division.php");
				}
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev2_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			  
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 

			}
			elseif($tsp_current_stage == 'FIN' && $post['xperttrack_sync']=='1')
			{
						
				if($post['cust_id']=='42' && $post['status']==1 && $post['department_id']=='5')
				{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("xpertrack_tsp_email_final_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("xpertrack_tsp_email_final_regular_division.php");
						}
				}

					
		//DISPATCH FINAL ARTICLES TO TSP VIA XPERTTRACK 
			}
			elseif($tsp_current_stage == 'REV' && $post['xperttrack_sync']=='0')
			{
			//DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 

						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175' && $post['j_id']!='262')
				{
					///// REV2: Send REV2 files to the authors as a PDF attachment in an email with cc to typesetting@techscience.com, marked as the finalized proof for their review.

					if($post['cust_id']=='42' && $_POST['stage']=='REV2' && $post['status']==1 && $post['department_id']=='14')
					{
						//echo "email";exit;
						if($post['j_division']=='2'){
							include("tsp_email_rev1_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_email_rev1_regular_division.php");
						}

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV2' && $post['status']==1 && $post['department_id']=='14')
					{					
						if($post['j_division']=='2'){
							include("tsp_ftp_rev_computer_division.php");
						}
						if($post['j_division']=='1'){
							include("tsp_ftp_rev_regular_division.php");
						}

					} 
				} 		
				else 
				{
				//////////TSP NEW NINE journals Code START Here ////
				//COMPUTER DIVISION REVISIES FILES (REV1)
				if($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					if($post['j_id']=='129' || $post['j_id']=='138' || $post['j_id']=='139')
					{
					//echo "FTP1";exit;
					include("tsp_email_rev1_computer_division.php");
					}
					elseif($_POST['stage']!='FP')
					{
					include("tsp_ftp_rev_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("tsp_ftp_rev_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("tsp_ftp_rev_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("tsp_email_rev1_regular_division.php");
				}
			//REGULAR DIVISION REVISIES FILES (REV1 & ABOVE))
			
			//////////TSP NEW NINE journals Code End Here ////
			  }
			
			}
			
		//DISPATCH ARTICLES TO AUHTOR VIA FTP OR EMAIL 
				if($post['department_id']==10)
				{
					if(($post['status'] == 1) && ($post['cust_id']==42 || $post['cust_id']==66 || $post['cust_id']==33))
					{
					$pagecount_perl_file = "E:/Transforma/Page_Count/page_count.pl";
					$dest_pdf_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/";   // destination folder or file 
					$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$pagecount_perl_file.'" "'.$dest_pdf_file.'" "'.strtoupper($Cn).'" "'.$J_name.'" "1" "'.$post['id'].'" "'.$post['stage'].'"';
					 system($perl_update_article);
					}
				}
		
		}
		else
		{
			
			/////// Get the department name /////
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$post['assign_department'].";"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			///// Create a assign department folder if not exists ////
			/// Added on 01-10-2019 //////
			$ass_dept_chk = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$dept_res['dept_name']."/";
			if(!is_dir($ass_dept_chk))
			{				
				mkdir($ass_dept_chk, 0777, true);				
			}
			//// END create assign folder /////	
				
			$assign_dept_status = $dept_res['dept_name']." | Assigned | ".$post['dept_name'];
			
			if($dept_res['dept_name'] == 'Delivery')
			{
				$assign_dept_status = $dept_res['dept_name'];
			}	
			
			//$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$dept_res['dept_name']." | Assigned | ".$post['dept_name']."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$assign_dept_status."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$db->query($prj_update_query);
		}
		
		
		
		
	
		
		if($post['status']==7)
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='Query',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$app_res['present_user_id']."' WHERE id = ".$post['id']." ");
		//echo "UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW() WHERE id = ".$post['tr_id']." ";exit;
		$prj_update_querys_trans=mysqli_query($db,"UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW(),`sub_user_id` ='". $app_res['present_user_id']."',`process_ulogin_type` ='". $user_type."' WHERE id = ".$post['tr_id']." ");
		}
		//// adding the logic to update the status in inw_inward_details table to fetch data directly

$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$post['department_id']."'"));

if($post['status'] == 1)
{
	
	$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>';
	if($dept_res['dept_name'] == 'Delivery')
	{
		$user_working_status = '';
		$graphic_working_status = ", graphic_working_status = ''";
	}
}
else if($post['status'] == 6)
{ 

	
				$graphic_working_status = ", graphic_working_status = ''";

				if($post['assign_department']=='1')
				{
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$post['a_user_id']."'"));
				}
				else
				{
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$app_res['present_user_id']."'"));
				}

	   // $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$full_name.'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
				if($post['assign_department']=='1')
				{
				$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';
				}
				else
				{
				$user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-warning">Assigned By</span> | <span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>';
				}
				
	
}
else if($post['status'] == 7)
{
    $user_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$full_name.'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$full_name.'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
//$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";		
$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."', last_transaction_id  = ".$post['tr_id']."  WHERE id = ".$post['id']."; ";			
	
$db->query($prj_update_query);
//// End logic Here 																						   
					
					
			
				
				
		///// Artile Details /////
		
			if($_POST['v_issue_id'] > 0)
			{
				
				//header("location:http://103.66.12.3:8010/workflow-new/show_issues.php?id=".$_POST['v_issue_id']."&msg=Successfully completed"); 
				
				header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

			}
			else
			{
				if($_POST['assign_department']=='')
				{
					if($J_name=='EPL' ||  $J_name=='NCC' || $post['cust_id']==33 || $post['cust_id']==66 || $post['cust_id']==42)
					{
				if(($post['stage']=='FP' && $post['dept_name']=='PRQC' && $post['cust_id']!='66' && $post['cust_id']!='42') || ($post['cust_id']!=66 && $stage=='REV' && $post['dept_name']=='XML') || ($post['cust_id']==66 && $stage_f=='FIN' && $post['dept_name']=='ePub' && $post['wf_id']=='154') || ($post['cust_id']==42 && $stage_f=='FIN' && $post['dept_name']=='XML' && $post['wf_id']=='162') || ($post['cust_id']==66 && $post['stage']!='REV1' && $post['dept_name']=='ePub' && $post['wf_id']=='148'))
				{				   

					if($success==1 && $post['status']==1)
					{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
					elseif($success==1 && $post['status']!=1)
					{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
					elseif($success!=1 && $post['status']==2)
					{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
					elseif($success!=1 && $post['status']==1)
					{
									//header("location:http://103.66.12.3:8010/workflow-new/startwork/".$post['id']."/new&msg=Error Please check the package&html"); 
					}
					elseif($success!=1 && $post['status']!=1)
					{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
					else
					{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
				}
				else
				{
									//header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

				}
					}
					else
					{
									header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
				}
					else
					{
									header("location:http://103.66.12.3:8010/workflow-new/artilces?msg=Successfully completed"); 

					}
			}		
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////
?>
 
  
  </body>
</html>

<?php include "includes/includes-sw2.php";?>
<?php 
						/* $mail_from = "techsupport@transforma.in";
$mail_pass = "Tr@n$!t2018"; */

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];

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



if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		
$app_res = $result->fetch_assoc();

		if($_COOKIE['user_level'] == 3)
		{	
			// Convert the string to an array using explode()
			$all_access_dept_array = explode(",", $_COOKIE['department_id'].','.$_COOKIE['odepartment_id']);			
			if ( !in_array($app_res['department_id'], $all_access_dept_array)  && !in_array('All', $all_access_dept_array)) {
				//header("location:projects.php?msg=You do not have access for this Article: ".$app_res['pub_id']);	
			header("location:".$_COOKIE['set_url']."/currentwork?msg=You do not have access for this Article: ".$app_res['pub_id'].""); 	

			}
		}
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET present_user_id = '".$_COOKIE['user_id']."',file_status='Inprogress' WHERE id = ".$_REQUEST['aid']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		
		if($app_res['volume_folder'] =="Vol00000")
		{
			$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
		}
	}
	
	/////////// Here Update the Take Over Transaction record //////
	if($_REQUEST['tid']!="new")
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Take Over',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///
if($_COOKIE['user_type']=='S') 
{	
$user_sub=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['sub_user_id']."'"));
//echo $_COOKIE['department_id'];
	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `sub_user_id` =  '".$_COOKIE['sub_user_id']."'  AND current_status ='Inprogress';"); 	

}
else
{
	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';"); 	

	
}

	
	$transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
	 	 $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`,`sub_user_id`,`process_ulogin_type`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '0', '".$_COOKIE['user_id']."',NOW(),'".$_COOKIE['sub_user_id']."','".$_COOKIE['user_type']."');";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
		$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$app_res['department_id']."'"));

		$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">Inprogress</span>';
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

//echo $transaction_id;

/////////////// Update completed ///////////////
/*
if(isset($_REQUEST['aid']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['aid']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		
		///// Added this for Current user Article working status
		$prj_update_query="UPDATE inw_inward_dtl SET present_user_id = '".$_COOKIE['user_id']."' WHERE id = ".$_REQUEST['aid']." ";			
		$db->query($prj_update_query);
		
		////////////////////////////////////
		
			
		$app_res = $result->fetch_assoc();
		
		if($app_res['volume_folder'] =="Vol00000")
		{
			$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
		}
	}
	
	/////////// Here Update the Take Over Transaction record //////
	if($_REQUEST['tid']!="new")
	{
		$update_trans_query="UPDATE inw_transactions SET current_status ='Take Over',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$_REQUEST['tid'].";";
		$db->query($update_trans_query);
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///
if($_COOKIE['user_type']=='S') 
{	
$user_sub=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['sub_user_id']."'"));
//echo $_COOKIE['department_id'];


	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `sub_user_id` =  '".$_COOKIE['sub_user_id']."'  AND current_status ='Inprogress';"); 	

}
else
{
	



	$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';"); 	

	
}

	
	 $transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
		 $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`,`sub_user_id`,`process_ulogin_type`) VALUES ('".$app_res['id']."','".$app_res['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '0', '".$_COOKIE['user_id']."',NOW(),'".$_COOKIE['sub_user_id']."','".$_COOKIE['user_type']."');";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
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
*/

//echo $transaction_id;
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
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

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
  </head>	

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>


      <!-- Page body starts -->
      
      <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
           <div class="col-4 col-lg-4">
            <p class="articleheading ms-4">Article Details</p>
			
			
          </div>

		   <div class="col-4 col-lg-4 d-flex" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
				</div>
			</div>
	</div>
		  
		  <div class="col-4 col-lg-4">
      <div class="row d-flex justify-content-end pe-2">
        <div class="col-3 articlebtns ">
          <a href="javascript:history.back();">
            <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
	
	
      
        </div>
      </div>

            <!-- Table section -->
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
        <div class="row">
        <div class="col-md-12">
                      <div class="" id="widget-box-2">
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
													<h5 class="widget-title bigger lighter pt-2" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-comments"></i>
														Article Information
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<tbody>
																<tr>
																	<td class="">Customer Name</td>
																	<td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
																</tr>
                                <tr>
																	<td class="">Journal</td>
																	<td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
																</tr>
                                <tr>
																	<td class="">Stage</td>
																	<td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
																</tr>
                                <tr>
																	<td class="">Article Title</td>
																	<td class="hidden-480"><?php echo $app_res['pub_id']; ?></td>
																</tr>
                                <tr>
																	<td class="">Received Date</td>
																	<td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></td>
																</tr>
                                <tr>
																	<td class="">Due Date</td>
																	<td class="hidden-480"> <?php
						  if($app_res['stage']=='FP')
						  {
						  echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
						  }
						  else
						  {
						  echo (date('d-m-Y',strtotime($app_res['due_dt'])));
						  }
						  ?></td>
																</tr>
															</tbody>
														</table>
													</div>
												</div>
											</div>
        </div>
      <div class="col-md-12">
                      <div class="" id="widget-box-2" >
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
													<h5 class="widget-title bigger lighter pt-2" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-table"></i>
														File History
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th>																		
																		Stage
																	</th>

																	
																	<th class="hidden-480">View DIR</th>
																</tr>
															</thead>

															<tbody>
																<tr>
																	<td class="">All Stages</td>

																	

																	<td class="hidden-480">
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="background: #45766e; color: white; border: 1px solid #45766e;"  href="file:///<?php echo str_replace(WF_DIR,TOOL_DIR,$app_res['work_dir']).$app_res['volume_folder']."/".$app_res['pub_id']."/"; ?>" target="_blank" target="_blank"> <i class="ace-icon fa fa-cloud-download bigger-120"></i> View Previous Files</a>
																		
																	</td>
																</tr>

																

																

																

																
															</tbody>
														</table>
													</div>
												</div>
											</div>
                      <div class="" id="widget-box-2" id="widget-box-2" >
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
                         <a style="text-decoration: none !important; color: #ffed8d !important;" href="view_article_emails.php?id=<?php echo $_REQUEST['aid']; ?>">
													<h5 class="widget-title bigger lighter pt-2" style=" font-size: 1.275em !important;border-bottom: 1px solid #ffed8d">
													  	<i class="ace-icon fa fa-envelope"></i>
														&nbsp;Related Emails
													</h5>
                        </a>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table" style="border-width: 1px solid white !important">
															
															<tbody>
																<tr style="display: flex; justify-content: space-evenly;">
																	<td class="hidden-480" style="background-color: white !important;">
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="color: white !important" href="view_article_emails.php?id=<?php echo $_REQUEST['aid']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Emails</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
                                                                    <a class="btn btn-xs btn-orange btn-sm" href="article-query.php?id=<?php echo $_REQUEST['aid']; ?>" style="color: white !important" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
                                                                    <a class="btn btn-xs btn-danger btn-sm" style="color: white !important" href="javascript:void();" style="text-decoration:none;color:red;" data-toggle="modal" data-target="#myModal" onclick="return timepopup('<?php echo $_REQUEST['aid']; ?>');" ><b style="text-decoration:none;color:white;"><i class="ace-icon fa fa-comments"></i>&nbsp;View Comments</b></a>
																		
																	</td>
																</tr>

																

																

																

																
															  </tbody>
													  	</table>
												  	</div>
											  	</div>
										  	</div>
                      </div>
                      </div>
                    </div>
                </div>
        <!-- form section  -->
        <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">

       
            <div class="container mt-4">
       		 <form class="form-horizontal" id="validation-form" method="post">

              <div class="formcont1heading">Work Status and comments to internal department</div>
			  <div class="p-5 pb-0 d-flex flex-column gap-2"> 
              <div class="row mt-1 g-4 addspacing">
               
             

                        

                    <?php 
					  if($app_res['department_id'] == 1 && ($app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 43) && $app_res['inw_type'] == "Word")
					  {
						  
					  ?>
                      
                      
					 
					    <a class="btn btn-danger" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_login_id']; ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> CE Editor</a>
					   
					    <?php 
					  }
					  ?>
						
						
							<?php if($customer_info['cust_name'] == "SIF" ){?>
									<a class="btn btn-danger" href="latex_editor.php?id=<?php echo $app_res['id'] ?>" target="_blank"><i class="ace-icon fa fa-edit align-top bigger-125"></i> Latex Editor</a>
							<?php } ?>
                </div>
            


            <?php 
						 
						 $ceditor_ip = "";
					 if($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "10.10.10.110" )

					 {
						$ceditor_ip = "10.10.10.109";
					 }
					 else if($_SERVER['HTTP_HOST'] == "117.232.75.162:8020" )
					 {
						$ceditor_ip = "117.232.75.162:8009";

					 }
					 else if($_SERVER['HTTP_HOST'] == "103.66.12.3:8010"  )

					 {
						$ceditor_ip = "103.66.12.3:8030";

					 }
							
					  if(($app_res['department_id'] == 1 || $app_res['department_id'] == 2) && ($app_res['cust_id'] == 33 || $app_res['cust_id'] ==37 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 43 || $app_res['cust_id'] == 66) && ($app_res['inw_type'] == "Word" || $app_res['inw_type'] == "docx" || $app_res['inw_type'] == "Latex") )
					  {
						  
					  ?>
                      
					  <div class="col-lg-12" style="border-width:5px;border-radius:20px;border-style:ridge;">
					  <iframe src="http://<?php echo $ceditor_ip; ?>/Transforma/products/word-to-pdf-xml_workflow.php?wid=<?php echo $app_res['id']; ?>" height="500px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>
					  
					 <!--  <div class="space-20"></div>
					  <div class="col-lg-12" style="border-width:5px;border-style:ridge;border-radius:20px;margin-top:50px;">
						<iframe  id="CEEDITOR"  src="http://192.168.0.4/Transforma/ce_editor/home/edit_file/<?php echo $app_res['activity']; ?>/<?php echo $app_res['file_name']; ?>" height="600px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>
					  </div>
					   <div class="space-20"></div> -->
					    <?php 
					  }
					  elseif(($app_res['department_id'] == 22 && ($app_res['inw_type'] == "Word" || $app_res['inw_type'] == "docx") && ($app_res['cust_id'] == 66 || $app_res['cust_id'] == 42 )))
					  {
						  
					  ?>
                      
                      <div class="space-20"></div>
					  <div class="col-lg-12" style="border-width:5px;border-radius:20px;border-style:ridge;">
					  <iframe src="http://<?php echo $ceditor_ip; ?>/Transforma/products/word-to-pdf-xml_workflow.php?wid=<?php echo $app_res['id']; ?>" height="500px" width="100%" scrolling="yes" frameborder="0"></iframe>
					  </div>
					  
					
					    <?php 
					  }
					  ?>
					  
                      <?php if(($customer_info['cust_name'] =="TSP" || $customer_info['cust_name'] =="Transforma" || $customer_info['cust_name'] =="EOS") && $app_res['stage'] == "FP" && (  ($app_res['status'] == "PRQC" && ($journal_info['j_code'] =="CMC" || $journal_info['j_code'] =="CMES"))  || ($app_res['status'] == "PRQC" || $app_res['status'] == "Proofing" || $app_res['status'] == "PAG"))){
?>	
                                                  
									<span class="btn btn-primary" id="3dtohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> 3D to HTML</span>
								
							<span class="btn btn-primary" id="xmltohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> XML to HTML</span>
							<span class="btn btn-primary" id="opfxmltohtml"><i class="ace-icon fa fa-file-code-o align-top bigger-125"></i> OPF XML to HTML</span>
							
							<a class="btn btn-primary" id="HTML_View" href="<?php echo Proof_Viewing_URL.$app_res['id']."/".$app_res['pub_id']; ?>" target="_blank"><i class="ace-icon fa fa-eye align-top bigger-125"></i> HTML View</a>
							
              <input type="hidden" name="cust_id" value="<?php echo $app_res['cust_id']; ?>" />
             <input type="hidden" name="j_id" value="<?php echo $app_res['j_id']; ?>" />
							
							<?php 
							if($customer_info['cust_name'] =="EOS")
							{
							?>
							
							<span class="btn btn-primary" id="eos_fp_package"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php
							}
							else
							{
							?>
							<span class="btn btn-primary" id="tsp_fp_package"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> Package creation</span>
							<?php 
							}	
							?>
											
						
							                         
					  <?php } ?>
					  
						  
					  <?php 
          
					/* Adding this for 94 XML editor 
					Swamy - 09-08-2022 
					Adding the records in DB
					Provide the link to PAG User */
					$copy_files = true;
					$temp_dept = $app_res['status'];
					if(strpos($app_res['status'], "PAG") !== false)
					{
						$app_res['status'] = "PAG";
					}
					if(strpos($app_res['status'], "CE") !== false)
					{
						$app_res['status'] = "CE";
					}
					
					//// Checking in Transactions table to stop the copy files to Edit Latex Server ////
					$transaction_query = "select count(id) cnt from `inw_transactions` where dept='".$app_res['department_id']."' and `project_id` = '".$app_res['id']."' and `stage` = '".$app_res['stage']."' ";
					$transaction_res = $db->query($transaction_query); 					
					$trans_res_row = $transaction_res->fetch_assoc();
					//print_r($trans_res_row);
					if($trans_res_row['cnt'] > 1)
					{
						$copy_files = false;
					}
					
	
					  if(($customer_info['cust_name'] == "SIF-Editlatex" || $customer_info['cust_name'] == "SIF" || $customer_info['cust_name'] =="Transforma") && $app_res['stage'] != "FIN1" && $app_res['status'] == "PAG" && ($journal_info['j_code'] =="EPL" || $journal_info['j_code'] =="NCC")){
						include("94_edit_latex_url.php");
					  } 
					  // Added on 19-10-2022 for JSIAM Customer
					  if($customer_info['cust_name'] =="IAP" && $app_res['stage'] != "FIN1" && ($app_res['status'] == "PAG" || $app_res['status'] == "CE") && $journal_info['j_code'] =="JSIAM")
					  {
						if($app_res['status'] == "PAG" && $app_res['stage'] == "FP")
						{
							$copy_files = false;
						}
						include("94_edit_latex_url.php");
					  } 
					  $app_res['status'] = $temp_dept;
					  ?>
					  
            <?php 
						   $html_read = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['pub_id']."_report.html";   // destination folder or file 
						  ?>
						  
						  	   <?php if($app_res['status'] == "ePub"){
?>	
                      <div class="col-xs-12 col-sm-9">
                            <div class="clearfix">
                                  
							<span class="btn btn-primary" id="ePub_Creation"><i class="ace-icon fa fa-file-archive-o align-top bigger-125"></i> ePub Creation</span>
							<span class="btn btn-primary" id="ePub_Validaion"><i class="ace-icon fa fa-check-circle-o align-top bigger-125"></i> ePub Validaion</span>
							<span class="btn btn-primary" id="rebuild_epub"><i class="ace-icon fa fa-retweet  align-top bigger-125"></i> Re-build ePub </span>
							<span class="btn btn-primary" id="ePub_to_html"><i class="ace-icon fa fa-html5 align-top bigger-125"></i> ePub to HTML</span>
											
						
							                            </div>
                            </div>
							
                      <div class="space-20"></div>
                      <div class="space-20"></div>
					  <?php } ?>	
                        
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="tr_id" value="<?php echo $transaction_id; ?>" />
                            <input type="hidden" name="id" value="<?php echo $_REQUEST['aid']; ?>" />
                            <input type="hidden" name="wf_id" value="<?php echo $app_res['wf_id']; ?>" />
                            <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                            <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">                           
							<input type="hidden" name="department_id" value="<?php echo $app_res['department_id']; ?>" />
                            <input type="hidden" name="dept_name" value="<?php echo $pdept_res['dept_name']; ?>" />
                            <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
                            <input type="hidden" name="v_issue_id" value="<?php echo $app_res['v_issue_id']; ?>" />
                            <input type="hidden" name="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
							<input type="hidden" name="xperttrack_sync" value="<?php echo $app_res['xperttrack_sync']; ?>" />
                            
							
							<?php 
							if($customer_info['id']==42)
							{
							?>
                            <input type="hidden" name="proof_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/Proofing/"; ?>">
                            <input type="hidden" name="article_id" value="<?php echo $app_res['pub_id']; ?>" />
							 <input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />
                             <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                            <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />	
                            <input name="j_division" type="hidden"  value="<?php echo $journal_info['j_division']; ?>"/>
							<?php
						if ($app_res['stage']== "FP") {
    ?>
							<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
                            <input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
                            <input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
                            <input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                            <input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
							<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
                            <input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
							<textarea name="txtBody" id="txtBody" style="visibility:hidden;" ><?php echo $d_mail_info['message'] ?></textarea>
							<?php
													//COMPUTER DIVISION REVISIES FILES (REV1)

						}
						elseif($app_res['stage']=='REV2' && $app_res['department_id']=='14')
						{
							if($app_res['j_id']!='170' && $app_res['j_id']!='173' && $app_res['j_id']!='167' && $app_res['j_id']!='178' && $app_res['j_id']!='180' && $app_res['j_id']!='257' && $app_res['j_id']!='166' && $app_res['j_id']!='164' && $app_res['j_id']!='175')
				{
					?>
										<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
										<input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
										<input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
										<input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
										<input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
										<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
										<input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
										<textarea name="txtBody" id="txtBody" style="visibility:hidden;" type="text" ><?php echo $d_mail_info['message'] ?></textarea>
								<?php 
				}
				else
				{
					?>
										<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
										<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
										<input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
										<input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
								<?php 
					
				}
							
						}
						elseif($app_res['stage']=='REV1' && $app_res['department_id']=='14' && $journal_info['j_division']=='2' )
			 
						{
								if($app_res['j_id']=='170' && $app_res['j_id']=='173' && $app_res['j_id']=='167' && $app_res['j_id']=='178' && $app_res['j_id']=='180' && $app_res['j_id']=='257' && $app_res['j_id']=='166' && $app_res['j_id']=='164' && $app_res['j_id']=='175')
								{								
								?>
										<input name="txtTo" type="hidden" id="txtTo"   value="<?php echo $app_res['corresponding_mail']; ?>" />
										<input name="txtToName" type="hidden"   value="<?php echo $app_res['corresponding_author']; ?>" />
										<input name="txtFrom" type="hidden"   value="<?php echo $journal_info['j_from_email']; ?>"/>
										<input name="txtMpass" type="hidden"  value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
										<input name="txtCc" type="hidden"  value="<?php echo $journal_info['j_cc_email']; ?>"/>
										<input name="txtBcc" type="hidden"  value="<?php echo $journal_info['j_bcc_email']; ?>" />
										<input name="txtSub" type="hidden"   value="<?php echo $d_mail_info['subject']; ?>"/>
										<textarea name="txtBody" id="txtBody" style="visibility:hidden;" type="text" ><?php echo $d_mail_info['message'] ?></textarea>
								<?php 
								}
								elseif($app_res['stage']!='FP')
								{
								?>
										<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
										<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
										<input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
										<input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
								<?php 
								}
					
				
				
			 //COMPUTER DIVISION REVISIES FILES			 (REV1)
			 }
			 else if($journal_info['ftp_send'] == 1 && $app_res['stage']!= "FP" && $journal_info['j_division']==2) {
						?>
                            <input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
							<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                            <input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                            <input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>

						<?php						 
						}else if ( $journal_info['ftp_send'] == 1 && (($app_res['stage']!= "REV1") || ($app_res['stage']!= "FP")) && $journal_info['j_division']==1) {
						?>
							<input name="FtpHost" type="hidden" value="<?php echo $journal_info['ftp_send_host']; ?>" />
							<input name="FtpPath" type="hidden" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                            <input name="FtpUser" type="hidden" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                            <input name="FtpPass" type="hidden" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
						<?php
						}
						
						?>
						
							
							<?php 
							}
							?>
							
							  <?php 
				
			  if($_COOKIE['department_id']==14 && $app_res['stage']!='FP' &&  $app_res['cust_id']=='66')
			  {
			  ?>
			   <input name="fpage" type="hidden" value="<?php echo $fpage; ?>" />
               <input name="lpage" type="hidden" value="<?php echo $lpage; ?>"/>
			  <?php 
			  }
			  ?>

                    <div class="col-12 col-sm-6 col-lg-4">
                    <label class="mb-2" >Choose Status :<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Status" name="status" id="status">
				                                    <option value="">-Select Status-</option>

                                <?php foreach($project_status_array as $id=>$val){?>
                                     <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                                 <?php } ?>
                                 </select>
                </div>

                <?php if($app_res['status']=="PRQC"){ ?>
                           <div class="col-6 col-sm-2 col-lg-2">
                          <label class="mb-2" >Page Count :<span style="color: red">*</span></label>
                          
                            <div class="clearfix">
                              <input type="number" name="page_count" id="page_count" class="form-control" required  placeholder="Page Count" value="<?php echo ($app_res['page_count'] > 0) ? $app_res['page_count'] : '0'; ?>" onkeyup="PreventFirstZero(event);">
                            </div>
                         
                        </div>
						 <?php if($app_res['status']=="PRQC" && $app_res['cust_id']=="42" && $app_res['stage']=="FP"){ ?>
						 <div class="space-2"></div>
						   <div class="col-12 col-sm-6 col-lg-4">
                          <label class="mb-2" >Corresponding Author Name  :<span style="color: red">*</span></label>
                          
                            <div class="clearfix">
                              <input type="text" name="corresponding_author" id="corresponding_author" class="form-control" required  placeholder="Corresponding Author Name" value="<?php echo  $app_res['corresponding_author']; ?>">
                            </div>
                          
                        </div>
						 <div class="space-2"></div>
						   <div class="col-12 col-sm-6 col-lg-4">
                          <label class="mb-2" >Corresponding Author Email ID  :<span style="color: red">*</span></label>

                          
                            <div class="clearfix">
                              <input type="text" name="corresponding_mail" id="corresponding_mail" class="form-control" required  placeholder="Corresponding Author Email ID" value="<?php echo  $app_res['corresponding_mail']; ?>">
                            </div>
                          
                        </div>
						 <?php } ?>
                        <?php } ?>
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
                              <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
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
                        
                        <?php 
		if($journal_info['skip_dept'] == '1'){
?>
						<div class="space-2"></div>
                        <div class="form-group" id="skip_department_div" >
                        <label class="mb-2" >Skip to Department :</label>

                       <div class="col-12 col-sm-6 col-lg-4">
                        <div class="clearfix">
    <?php
		
		$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
		= 0 AND dept_code IN (".implode(",",$all_dept_array).") and dept_code !=
		'".$app_res['department_id']."' ";
	    $customers = $db->query($query) or ($db->error);
	?>
                            <select id="skip_dept" name="skip_dept" data-placeholder="Choose" class="form-select customer_drop" >
                              <option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                            </select>
                        </div>
                        </div>
                        </div>
		<?php } ?>
        
                        
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

                <div class="col-12 col-sm-12 col-lg-12"  id="notmpa" style="display:none;">
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="comments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

                <div class="col-12 col-sm-12 col-lg-12"  id="query_div" style="display:none;">
                  <label class="mb-2">Remarks <span style="color: red">*</span></label>
                  <textarea name="message_query" id="message" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

                <div class="container-fluid mt-5 mb-5">
                <div class="row">
                     <div class="col form_btn cancelbtn">
                    <a class="btn btn-purple" style="color: white !important" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']; ?>" target="_blank">
					<i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>&nbsp Source</a>
                    </div>
                    <div class="col form_btn nxtbtn">
                    <a class="btn btn-success" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Work</a>
                    </div>


				<div class="container-fluid mt-5 mb-5">
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit">Cancel</button>
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit">Next</button>
                    </div>
                </div>
            </div>
			
			
                </div>
				
            </div>
			
			</div>
        
		</form>
		</div>
		
			
            </div>
			</div>
			<div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
			<div class="p-5 pb-0 d-flex flex-column gap-2"> 
                    <?php
                    // $base_path = "C:/xampp/htdocs/New_Workflow/Files/";
                    $base_path = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/".$pdept_res['dept_name']."/";
                    //date_default_timezone_set("Asia/Kolkata");
                    extract($_POST);
                    if (isset($back) && $back !== "") {
                        $folder = $backfolder;
                    } else if (isset($upload) && $upload !== "") {
                        $folder = $currentfolder;
                        $uploaddir = $folder;
                        $uploadfile = $uploaddir . basename($_FILES['upload_file']['name']);

                        if (file_exists($uploadfile)) {
                            $ext = pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION);
                            $file = pathinfo($_FILES['upload_file']['name'], PATHINFO_FILENAME);

                            if (rename($uploadfile, $uploaddir . $file . "_" . date('dmYHis') . "." . $ext)) {
                                if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
                                    echo "<p style='text-align:center;'>Successfully uploaded.</p>";
                                } else {
                                    echo "<p style='text-align:center;'>Error to uploaded.</p>";
                                }
                            } else {
                                echo "<p style='text-align:center;'>Error in renaming file</p>";
                            }
                        } else {
                            if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
                                echo "<p style='text-align:center;'>Successfully uploaded.</p>";
                            } else {
                                echo "<p style='text-align:center;'>Error to uploaded.</p>";
                            }
                        }
                    } else if (isset($cfolder) && $cfolder !== "") {
                        $folder = $path . $cfolder;
                    } else {
                        $folder = $base_path;
                    }
                    ?>
					<div class="formcont1heading" style="position: relative; left: -36px"><?php echo  $folder ?></div>

                    <?php //if ($base_path !== $folder) { ?>
					 
                        <table style="width:100%;border: none !important">
                            <tr>
                                <td style="width:50%;border: none !important">
                                    <form method="post">
                                        <?php
                                        $narr = explode('/', $folder);
                                        array_pop($narr);
                                        array_pop($narr);
                                        $bpath = implode('/', $narr) . "/";
                                        if ($base_path !== $folder) {
                                            ?>
                                            <input type="hidden" name="backfolder" value="<?php echo  $bpath ?>"/>
                                            <button name="back" value="<?php echo  $bpath ?>" style="cursor: pointer;font-weight: bold;padding: 10px;background: #ff9a9a;border: 1px solid #ff9a9a;"><i class="fa fa-arrow-left"></i> Back</button>
                                        <?php } ?>
                                    </form>
                                </td>
                                <td style="width: 100%; display: flex; justify-content: end;border: none !important;" >
                                    <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="currentfolder" value="<?php echo  $folder ?>"/>
                                        <input type="file" name="upload_file" required=""/>
                                        <button name="upload" value="upload" style="cursor: pointer;padding: 10px;background: #ff9a9a;font-weight: bold;border: 1px solid #ff9a9a;margin-left: 5px"><i class="fa fa-upload"></i> Upload</button>
                                    </form>
                                </td>
                            </tr>
                        </table>
                    <?php //} ?>
                
            <div class="main pb-5">
                <div class="bottom-content">
                    <form method="post" onsubmit="">
                        <input type="hidden" id="path" name="path" value="<?php echo  $folder ?>"/>
						<table class="table custtable  dataTable no-footer" id="example" style="border: none !important; background-color: white !important;padding-top: 10px !important">
						<thead>
                            <tr>
                                <th>Sno</th>
                                <th>Name</th>
                                <th>Date Modified</th>
                                <th>Type</th>
                                <th>Size</th>

                            </tr>
							</thead>
							 <tbody>
                            <?php
                            $all_files = array_diff(scandir($folder), array('..', '.'));
                            if (sizeof($all_files) > 0) {
                                $sno = 1;
                                foreach ($all_files as $file) {
                                    if (is_dir($folder . $file)) {
                                        ?>
                                        <tr>
                                            <td><?php echo  $sno++ ?></td>
                                            <td><i class="fa fa-folder" style="    color: gold;font-size: 20px;"></i> <?php echo  $file ?> <button class="edit-btn" name="cfolder" value="<?php echo  $file . "/" ?>" style="cursor: pointer;">Open</button></td>
                                            <td><?php echo  date('Y.m.d h:i:s A', filemtime($folder . $file)) ?></td>
                                            <td>Folder</td>
                                            <td></td>

                                        </tr>
                                        <?php
                                    }
                                }
                                foreach ($all_files as $file) {
                                    if (!is_dir($folder . $file)) {
                                        ?>

                                        <tr>
                                            <td><?php echo  $sno++ ?></td>
                                            <td><i class="fa fa-file-text-o"></i> <?php echo  $file ?> <a href="<?php echo $_COOKIE['set_url'];?>/download.php?name=<?php echo  $folder . $file ?>" class="edit-btn"><i class="fa fa-download"></i> </a></td>
                                            <td><?php echo  date('Y.m.d h:i:s A', filemtime($folder . $file)) ?></td>
                                            <td><?php echo  filetype($folder . $file) ?></td>
                                            <td><?php echo  round((filesize($folder . $file) / 1024), 0, PHP_ROUND_HALF_UP) . " KB" ?></td>

                                        </tr>
                                        <?php
                                    }
                                }
                            } else {

                                echo "<tr><td colspan='5' style='text-align:center;'>This folder is empty!..</td></tr>";
                            }
                            ?>
							</tbody>
                        </table>
                </div>
        </div>
		</div>
			</div>
		</div>

					
     <!-- Footer Section -->
     <?php echo footer(); ?>
	 </div>



<!-- Footer Section -->
<?php echo scripts(); ?>	

   
       <!-- Initialize DataTables -->
  <script>
        $(document).ready(function() {
           // $('#example').DataTable();
			
			// Correct: Ensure the selector matches the correct table

 $('#example').DataTable({
        "pageLength": 100
    });
    
console.log($('#example').DataTable().mData);


        });
		
		
    </script>		
    


    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>

<script type="text/javascript">

function RestrictFirstZero(e) {
          if (e.srcElement.value.length == 0 && e.which == 48) {
              e.preventDefault();
              return false;
          }
      };

      function PreventFirstZero(event) {
          if (event.srcElement.value.charAt(0) == '0') {
              event.srcElement.value = event.srcElement.value.slice(1);
          }
      };
    
    
    
jQuery(function($) {

$( "#3dtohtml" ).click(function() {
$(this).hide();
});
$( "#xmltohtml" ).click(function() {
$(this).hide();
});
$( "#opfxmltohtml" ).click(function() {
$(this).hide();
});
$( "#HTML_View" ).click(function() {
$(this).hide();
});
$( "#tsp_fp_package" ).click(function() {
$(this).hide();
});
$( "#eos_fp_package" ).click(function() {
$(this).hide();
});

      $('#validation-form').validate({errorClass: 'help-block'});	
      $('#message').richText();//this will create the default editor will all buttons			
    })
    
    function timepopup(id)
    {				
      var url = "project_comments.php?id="+id;
      $("#ppopup").load(url);
    }
    
    
$.validator.setDefaults({
  submitHandler: function(form) {
    var a_id = $("input[name='id']").val();
    var dept_name = $("input[name='dept_name']").val();
    var status = $( "#status option:selected" ).text();
    if(dept_name == "PAG" && status == "Completed")
    {
      $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/get_graphics_status.php',
        data:'a_id='+a_id,
        success:function(result){
          if(result == 1)
          {
            form.submit();
          }
          else
          {
          
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> <strong> Warning! </strong>'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
            return false;
          }         
        }
      });  
      return false;
    }
    else
    {
      form.submit();
    }
  }
});




  $(document).ready(function(){

  <?php if($customer_info['cust_name'] =="TSP" && $app_res['stage'] == "FP" && (($app_res['status'] == "PRQC" && ($journal_info['j_code'] =="CMC" || $journal_info['j_code'] =="CMES")) || $app_res['status'] == "Proofing")){
    $html_file = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".html"; 
    if (!file_exists($html_file)) 
  {
    
?>	
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
            $(".loader").fadeOut("slow");
          if(result.includes("completed"))
          {
            $(".loader").fadeOut("slow");
            $URL = $( "#HTML_View" ).attr( "href" );
        
           window.open($URL, '_blank');
          }							
          else
          {
            $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      }); 
    
  <?php } 
  else if (file_exists($html_file)) 
    {		?>
      $(".loader").fadeOut("slow");
      $URL = $( "#HTML_View" ).attr( "href" );
      window.open($URL, '_blank');
  <?php } 
  } ?>		
    ///////////// Get workflow from Journal //////////

$('#tsp_fp_package').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/tsp_fp_package.php',
        data:'id='+a_id,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});


$('#eos_fp_package').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/eos_fp_package.php',
        data:'id='+a_id,
        success:function(result){
          $(".loader").fadeOut("slow");
          if(result == "completed")
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning"> '+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });	
          }
                    
                 
        }
      });  
});

$('#3dtohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
          $(".loader").fadeOut("slow");
          
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});
$('#xmltohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=2',
        success:function(result){
          
          $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});
$('#opfxmltohtml').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/3dtohtml.php',
        data:'a_id='+a_id+'&type=3',
        success:function(result){
          
          $(".loader").fadeOut("slow");
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ result +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });					
                 
        }
      });  
});




  //$(document).ready(function(){	
    ///////////// Get workflow from Journal //////////

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
  

    $('#ePub_Creation').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=1',
        success:function(result){
          $(".loader").fadeOut("slow");


           response = JSON.parse(result);
        
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }

                 
        }
      });  
});


    $('#ePub_Validaion').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=2',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);
          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
















            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						

            });
          }
                 
        }
      });  
});
      


























$('#rebuild_epub').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=3',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);

          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
                 
        }
      });  
});



    $('#ePub_to_html').on('click',function()
{
  $(".loader").fadeIn("slow");
  var a_id = $("input[name='id']").val();
  // alert(a_id);
  $.ajax({
        type:'POST',
        url:'<?php echo $_COOKIE['set_url'];?>/epub_generation.php',
        data:'a_id='+a_id+'&type=4',
        success:function(result){
          $(".loader").fadeOut("slow");
           response = JSON.parse(result);
          
           if(response.status == 1)
           {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
          else
          {
            bootbox.dialog(
            {
              message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
              buttons:
              {
                "button" :
                {
                  "label" : "Close",
                  "className" : "btn-sm",
                  "callback": function() {
                    
                  }
                }							
              } 						
            });
          }
                 
        }
      });  
});


})

  </script>
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
              url:'<?php echo $_COOKIE['set_url'];?>/get_cepe_user.php',
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

    
  </script>
  
  <?php 
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
			$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ";

	}
	else
	{
		
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='".$post['vcomments'].$post['comments']."',`assign_department` ='". $post['assign_department']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ";
		
		if($post['status'] == 7)
		{
			$user_jour=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$jninfo['j_hid']."'"));
			$user_jour_cc=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_COOKIE['user_id']."'"));
			if($user_jour['user_email']!="")
			{
				$from_name=$user_jour['full_name'];
				$to=$user_jour['user_email'];
			}
			else
			{
				$from_name='Bosco';
				$to='bosco@transforma.in';
			}
			
			
			@$body ="<table height='90' cellspacing='4' cellpadding='4' width='800' align='center' bgcolor='#ffffff' border='0' style='border:3px solid #cccccc'>
  <tbody>
   <tr>
    <td width='300'><a href='#' target='_blank'><img src='http://transforma.co:8005/workflow/assets/images/trans-logo.png' height='70' width='233' /></a></td>
    <td width='567'>
<table>
   
    <tr>
<td><hr></td>
    </tr>
   
</table>
    </td>
  </tr>   
 
    <tr>
      <td valign='middle' align='left' width='533' colspan='2'> Dear  ".@$from_name." ,<br><br>
	  
	 Query Notification for the article no :".$post['pub_id'].":<br><br> 


		File name: ".$post['pub_id']."<br><br> 
		Comment : ".$post['message_query']."
		<br>
       <br>
        Thanks and Regards,<br><br>
		".$user_jour_cc['full_name']."<br> 
		Transforma Pvt Ltd <br>        
         </td>
    </tr>
  </tbody>
</table>";
	
//echo $dest.$post['pub_id'].".pdf";exit;
@$sendto =$to; 
$subject = "Query Notification for the article no :".$post['pub_id']."";	
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $user_jour_cc['full_name'];
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;
//$mail_tocc	= $user_jour_cc['user_email'];
$mail_tocc	="pm@transforma.in";
$mail_tobcc	= "techsupport@transforma.in";
$mail->addAddress($sendto);	
$mail->addAddress($mail_tocc);	
$mail->addAddress($mail_tobcc);
$mail->Send();	
//echo "insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."')";exit;
$insert=mysqli_query($db,"insert into tbl_query_client(`jid`,`cust_id`,`aid`,`from_userid`,`to_userid`,`subject`,`message`,`created_date`,`query_raised_ip`,`status`) values ('".$post['j_id']."','".$post['cust_id']."','".$post['id']."','".$_COOKIE['user_id']."','".$jninfo['j_hid']."','".$subject."','".$post['message_query']."','".date('Y-m-d H:i:s')."','".$_SERVER['SERVER_ADDR']."','Query')");
			
		}
	}
	
	if($db->query($update_query))
	{
		if($post['assign_department']=='')
		{
			if($post['status'] == 1 && $post['cust_id']==33)
			{
				if($post['department_id']==22)
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
			if($post['status'] == 1 && $post['cust_id']==42 && $post['department_id']==1)
			{
				$ce_perl_file = "E:/Transforma/Word2HTML/Exe/METACONFIG.pl";
				$dest_xml_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/CS/".$post['pub_id'].".xml";   // destination folder or file 
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
			
			if($post['skip_dept'] != "" && $post['skip_dept'] > 0)
			{
				$department_id = $post['skip_dept'];
			}			
			else if($post['status'] == 1 || $post['status'] == 12)
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
				$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$post['page_count']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");

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
			$prj_update_query="UPDATE inw_inward_dtl SET ".$corresponding_author_details ." ".$page_count." `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`assign_department_id` ='0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."', present_user_id = '0' WHERE id = ".$post['id']." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			/// If completed the work files we will move to other department ////
			if($post['status'] == 1  || $post['status'] == 12)
			{
			///// Insert the checklist data to DB //////////
			foreach($_POST['checklist'] as $cl_id=>$value)
			{
				$insert_query = "INSERT INTO `inw_to_checklist` (`inw_id`,dept_id,stage,`cl_id`, `submited_value`, `created_by`, `created_on`) VALUES ('". $post['id']."', '".$post['department_id']."', '".$post['stage']."','". $cl_id."','". $value."', '". $_COOKIE['user_id']."', CURRENT_TIMESTAMP);";	
				//$db->query($insert_query) or die($db->error);
			}
			
				/// Move the Files From On Dept to another dept ////
				
					
	$src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/";   // source folder or file
	



///// Get the Accept files from New Configuration based on Customer/Journal/Dept/Stage//////

$fe_query = "SELECT mf_id FROM `inw_move_files` WHERE cust_id = '".$post['cust_id']."' AND j_id = '".$post['j_id']."' AND dept_id = '".$post['department_id']."';";
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
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='".$dept_res['dept_name']."',`department_id` ='". $department_id."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");

			}
			else
			{
					//echo $stage;echo "<br/>";
					//echo "2";
			$prj_update_query=mysqli_query($db,"UPDATE inw_inward_dtl SET `status` ='".$post['dept_name']."',`department_id` ='". $post['department_id']."',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
			//echo "UPDATE inw_transactions SET `status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."' WHERE id = ".$post['tr_id']." ";exit;
			$update_squery=mysqli_query($db,"UPDATE inw_transactions SET `pages_completed`='".$page_count."',`status` ='".$post['dept_name']."',current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");
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
			
			$tsp_current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);
			
			
			
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

					} else if($post['cust_id']=='42' && $_POST['stage'] != 'FP' && $_POST['stage'] != 'REV1' && $post['status']==1 && $post['department_id']=='14')
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
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
					}
				
				}
				
			 //COMPUTER DIVISION REVISIES FILES (REV1)
			
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			elseif($post['cust_id']=='42' && $_POST['stage']!='FP' && $_POST['stage']!='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='2' )
				{
					//echo "FTP1";exit;
					include("xpertrack_tsp_email_rev1_above_computer_division.php");
				
				}
				
			//COMPUTER DIVISION REVISIES FILES (REV1 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
				elseif($post['cust_id']=='42' && $_POST['stage']!='REV1' && $_POST['stage']!='FP' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "ftp2";exit;
					include("xpertrack_tsp_email_rev1_above_regular_division.php");

				}
			//REGULAR DIVISION REVISIES FILES (REV2 & ABOVE)
			
			//REGULAR DIVISION REVISIES FILES (REV1)
				elseif($post['cust_id']=='42' && $_POST['stage']=='REV1' && $post['status']==1 && $post['department_id']=='14' && $post['j_division']=='1' )
				{
					//echo "email";exit;
					include("xpertrack_tsp_email_rev1_regular_division.php");
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

						
				if($post['j_id']!='170' && $post['j_id']!='173' && $post['j_id']!='167' && $post['j_id']!='178' && $post['j_id']!='180' && $post['j_id']!='257' && $post['j_id']!='166' && $post['j_id']!='164' && $post['j_id']!='175')
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
			
			//$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$dept_res['dept_name']." | Assigned | ".$post['dept_name']."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$prj_update_query="UPDATE inw_inward_dtl SET `status` ='".$assign_dept_status."',`department_id` ='". $post['assign_department']."',`prev_dept_id` ='". $post['department_id']."',`assign_department_id` ='". $post['assign_department']."', present_user_id = '0',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."',`assigned_user_id`='".$post['a_user_id']."' WHERE id = ".$post['id']." ";
			$db->query($prj_update_query);
		}
		
		
		
		
		// Mail to TSP
		
		
		
if($post['cust_id']==42 && $post['dept_name']=='PRQC' && $post['status'] == 1)	
{
	
	
	@$body ="<table height='90' cellspacing='4' cellpadding='4' width='800' align='center' bgcolor='#ffffff' border='0' style='border:3px solid #cccccc'>
  <tbody>
   <tr>
    <td width='300'><a href='http://transforma.co:8005/Workflow/customer/' target='_blank'><img src='http://transforma.co:8005/workflow/assets/images/trans-logo.png' height='70' width='233' /></a></td>
    <td width='567'>
<table>
    <tr>
  <td width='300'><a href='https://www.techscience.com/' target='_blank'><img src='https://www.techscience.com/images/logo.jpg' /></a></td>
    </tr>
    <tr>
<td><hr></td>
    </tr>
   
</table>
    </td>
  </tr>   
 
    <tr>
      <td valign='middle' align='left' width='533' colspan='2'> Dear  ".@$crow['cust_name']." Editor's,<br><br>
	  
	 You can use the below link to review and edit the content, please open the below mentioned link in Google Chrome or Mozilla Firefox:<br><br> <a href='http://transforma.co:8005/Workflow/customer/index.php'>http://transforma.co:8005/Workflow/customer/index.php </a><br><br>User Name  ".@$crow['email']." <br> Password: ".@$crow['password']." <br /><br /> 


		File name: ".$post['pub_id']."
		<br>
       
       
        Thanks and Regards,<br><br>
		Team<br> 
		Transforma Pvt Ltd <br>        
         </td>
    </tr>
  </tbody>
</table>";
	
	//@$sendto = @$crow['email']; 
	//echo $dest.$post['pub_id'].".pdf";exit;
@$sendto ="techsupport@transforma.in"; 
$subject = "Customer Notification for the manuscript no :".$post['pub_id']."";	
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $mail_pass;
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;
//$mail->AddAttachment($_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".pdf");      // attachment
//$mail_tocc	= "techsupport@transforma.in";
//$mail_tocc	= "ganeshkoumar@transforma.in";
//$mail_tobcc	= "tec0304@transforma.co";
//$mail_tobcc	= "sathish@transforma.in";
$mail->addAddress($sendto);	
//$mail->addAddress($mail_tocc);	
//$mail->addAddress($mail_tobcc);
// $mail->Send();	
}	
		
		if($post['status']==7)
		{
		$prj_update_querys=mysqli_query($db,"UPDATE inw_inward_dtl SET  `status` ='Query',`file_status` ='".$project_status_array[$post['status']]."',`last_worked_user_id`='".$_COOKIE['user_id']."' WHERE id = ".$post['id']." ");
		//echo "UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW() WHERE id = ".$post['tr_id']." ";exit;
		$prj_update_querys_trans=mysqli_query($db,"UPDATE inw_transactions SET `current_status` ='Query', `end_time`=NOW(),`sub_user_id` ='". $_COOKIE['sub_user_id']."',`process_ulogin_type` ='". $_COOKIE['user_type']."' WHERE id = ".$post['tr_id']." ");
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
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'| Assigned |'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
else if($post['status'] == 7)
{
    $user_working_status = '<span class="label label-sm label-success">Query</span>|<span class="label label-sm label-info">Raised By: '.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$current_user_deptname['dept_name'].'</span>';
}
else
{
    $user_working_status = '<span class="label label-sm label-success">'.$dept_res['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">'.$project_status_array[$post['status']].'</span>';
}
//$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."',last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";		
$prj_update_query="UPDATE inw_inward_dtl SET user_working_status = '".$user_working_status."', last_transaction_id  = ".$post['tr_id']." ".$graphic_working_status." WHERE id = ".$post['id']."; ";			
	
$db->query($prj_update_query);
//// End logic Here 																						   
					
		///// Artile Details /////
		
			if($_POST['v_issue_id'] > 0)
			{
				header("location:show_issues.php?id=".$_POST['v_issue_id']."&msg=Successfully completed"); 
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
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					elseif($success==1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>"; 
					}
					elseif($success!=1 && $post['status']==2)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					elseif($success!=1 && $post['status']==1)
					{
				    header("location:".$_COOKIE['set_url']."/startwork/".$post['id']."/new?msg=Error Please check the package&html"); 
					}
					elseif($success!=1 && $post['status']!=1)
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
				}
				else
				{
				echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
				}
					}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
					}
				}
					else
					{
					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Work Status Updated as ".$project_status_array[$post['status']]." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/currentwork';
});";
 echo "</script>";
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

<?php
//echo "SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['aid']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1";
$chk_transaction_time = mysqli_fetch_array(mysqli_query($db,"SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['aid']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1")); 	
// Start time from PHP
$startTime = strtotime($chk_transaction_time['start_time']); // Example start time
//$startTime = strtotime("2024-03-23 08:57:19"); // Example start time
?>
 <script>
        $(document).ready(function(){
            // Start time from PHP
            var startTime = <?php echo json_encode($startTime * 1000); ?>;
            
            // Function to update the time
            function updateTime() {
                // Current time
                var endTime = new Date().getTime();

                // Calculate the time difference (in seconds)
                var timeDiff = Math.floor((endTime - startTime) / 1000);

                // Calculate days, hours, minutes, and seconds
                var days = Math.floor(timeDiff / (3600 * 24));
                var hours = Math.floor((timeDiff % (3600 * 24)) / 3600);
                var minutes = Math.floor((timeDiff % 3600) / 60);
                var seconds = timeDiff % 60;

                // Update spans with days, hours, minutes, and seconds
                $(".gtime-days").text(formatTime(days) + " days").toggle(days > 0);
                $(".gtime-hours").text(formatTime(hours) + "");
                $(".gtime-min").text(formatTime(minutes) + "");
                $(".gtime-sec").text(formatTime(seconds) + "");
				
				
            }

            // Update time every second
            setInterval(function() {
                updateTime();
            }, 1000);

            // Initial update
            updateTime();
        });

        // Function to format time to add leading zeros if needed
        function formatTime(time) {
            return time < 10 ? "0" + time : time;
        }
    </script>
  </body>
</html>

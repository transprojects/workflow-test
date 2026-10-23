<?php include "includes/includes-sw2.php";?>
<?php 
						/* $mail_from = "techsupport@transforma.in";
$mail_pass = "Tr@n$!t2018"; */

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];

function rcopy($src, $dest){

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
            rcopy($f->getRealPath(), "$dest/$f");
        }
    }
}

function delete_directory($dirname) {
         if (is_dir($dirname))
           $dir_handle = opendir($dirname);
     if (!$dir_handle)
          return false;
     while($file = readdir($dir_handle)) {
           if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                     unlink($dirname."/".$file);
                else
                     delete_directory($dirname.'/'.$file);
           }
     }
     closedir($dir_handle);
     rmdir($dirname);
     return true;
}



if(isset($_REQUEST['id']))
{
	
	//echo "fdfs";
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
	}

/// Get the Client Name ////

	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
// Insert the New transaction ///

//echo "SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';";
$chk_transaction = $db->query("SELECT id FROM `inw_transactions` WHERE `project_id` = ".$app_res['id']." AND `process_user` =  '".$_COOKIE['user_id']."' AND `created_by` = '".$_COOKIE['user_id']."' AND current_status ='Inprogress';"); 	
	
	 $transaction_toatl_count=$chk_transaction->num_rows;
	if($transaction_toatl_count>0)
	{
		$trans_res = $chk_transaction->fetch_assoc();
		$transaction_id = $trans_res['id'];
	}
	else
	{	
		  $insert_query = "INSERT INTO `inw_transactions` (project_id,`dept`, `stage`, `current_stage`, `current_status`, `previous_stage`, `previous_id`, `process_user`, `start_time`, `completion_status`, `created_by`, `created_dt`) VALUES ('".$app_res['id']."','".$_COOKIE['department_id']."', '".$app_res['stage']."', '".$app_res['stage']."', 'Inprogress', '', '', '".$_COOKIE['user_id']."', NOW(), '1', '".$_COOKIE['user_id']."',NOW());";	
		$db->query($insert_query) or ($db->error);
		$transaction_id = $db->insert_id;
		 ///// Added this for Current user Article working status
		$current_user_deptname = mysqli_fetch_array(mysqli_query($db,"select dept_name from adm_dept_master where dept_code='".$_COOKIE['department_id']."'"));

		$graphic_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$_COOKIE['full_name'].'</span>|<span class="label label-sm label-warning">Inprogress</span>';
		$prj_update_query="UPDATE inw_inward_dtl SET graphic_working_status = '".$graphic_working_status."',last_transaction_id='".$transaction_id."' WHERE id = ".$_REQUEST['id']."; ";			
		$db->query($prj_update_query);																   
	}
	
}



//echo $transaction_id;
?>
                      

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
	<link href="<?php echo $_COOKIE['set_url']; ?>/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">

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
#syncEditlatexBtn {
    background-color: #f4c542; /* your yellow */
    border-color: #f4c542;
    color: #000;
}

#syncEditlatexBtn:hover {
    background-color: #e0b437 !important; /* darker yellow */
    border-color: #e0b437 !important;
    color: #000 !important;
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
      
      <div class="container-fluid" style="justify-content: space-between;position:sticky;top:120px;background:#fff;padding-bottom:5px;z-index:200;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-4">
            <p class="articleheading ms-4">Article Details</p>
			
			
          </div>
		  <div class="col-12 d-none d-lg-flex col-lg-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content;width:-moz-fit-content; justify-content: space-evenly">
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
       		 <div class="col-3 articlebtns">
          <!--<a href="javascript:history.back();">-->
          <a href="<?php echo $_COOKIE['set_url'];?>/graphicarticles">
            <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
          </a>
        </div>
      </div>
    </div>
	<div class="col-12 d-flex d-lg-none col-lg-4 mt-4" style="justify-content: center">
		  	<div class="nav-clock startnav-clock d-flex ps-4 pe-4 gap-3" style="width: fit-content;width:-moz-fit-content; justify-content: space-evenly">
				<p style="font-size: 15px;">Elapsed Time</p>
				<p><span class="gtime-days"></span></p>
				<div class="time"> 
					<span class="gtime-hours"></span>
					<span class="gtime-min"></span>
					<span class="gtime-sec"></span>
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
													<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-comments"></i>
														Graphic Query
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<tbody>
																<tr>
																	<td style="width: 50%" class="">Query's</td>
																	<td style="width: 50%" class="hidden-480"><?php
								  $i=0;
	 $graph_query="SELECT * FROM `inw_transactions`  WHERE dept IN ('9') and project_id='".$_REQUEST['id']."' and graphic_query!='' ";
     $graph_result = $db->query($graph_query); 	
   
     $graph_count=$graph_result->num_rows;
    if($graph_count>0)
    {
		while($graph_res = $graph_result->fetch_assoc())
		{ 
		$prev_depart_sql = mysqli_fetch_array(mysqli_query($db,"SELECT full_name FROM users where  id = '".$graph_res['process_user']."'"));

	$i++;
		echo "<b style='color:red;font-size:15px;'>".$i.") ".$graph_res['graphic_query']." </b>  (".$prev_depart_sql['full_name'].")";echo "<br/>";
		}
    }
    ?>
                       </td>
																</tr>
                                
															</tbody>
														</table>
													</div>
												</div>
											</div>
        </div>
		
        <div class="col-md-12">
                      <div class="" id="widget-box-2">
												<div class="formcont1heading" style="position: relative; left: 12px; top: 10px;">
														<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-comments"></i>
														Article Information
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-paddings">
														<table class="table table-hover">
															<tbody>
																<tr>
																	<td style="width: 50%" class="">Customer Name</td>
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
														<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;">
														<i class="ace-icon fa fa-table"></i>
														File History
													</h5>

													
												</div>

												<div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th style="width: 50%">																		
																		Stage
																	</th>
																	
																	<th class="hidden-480">View DIR</th>
																</tr>
															</thead>

															<tbody>
																<tr>
																<td style="width: 50%" class="">All Stages</td>

																	

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
                         <a style="text-decoration: none !important; color: #ffed8d !important;" href="view_article_emails.php?id=<?php echo $_REQUEST['id']; ?>">
													<h5 class="widget-title bigger lighter pt-0 pb-0 mb-0" style=" font-size: 1.275em !important;border-bottom: 1px solid #ffed8d">
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
                                                                    <a class="btn btn-xs btn-purple btn-sm" style="color: white !important" href="<?php echo $_COOKIE['set_url'];?>/view_article_emails.php?id=<?php echo $_REQUEST['id']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Emails</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
                                                                    <a class="btn btn-xs btn-orange btn-sm" href="<?php echo $_COOKIE['set_url'];?>/article-query.php?id=<?php echo $_REQUEST['id']; ?>" style="color: white !important" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
																		
																	</td>
																	
																		<td class="hidden-480" style="background-color: white !important">
																		<a class="btn btn-sm" style="text-decoration:none;color:red;cursor:pointer;border: 1px solid red;font-size:0.85em;width: fit-content" data-bs-toggle="modal" data-bs-target="#exampleModal" 
			onclick="return timepopup(<?php echo $_REQUEST['id']; ?>);">View Comments</a>
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
               
             
</div>


           
					  
						  
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

                        	<?php 
											$work_dir = $app_res['work_dir'];
											$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
											//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$_COOKIE['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
	}

?>
                           <input type="hidden" name="action" value="update">
                              <input type="hidden" name="tr_id" value="<?php echo $transaction_id; ?>" />
                              <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                              <input type="hidden" name="wf_id" value="<?php echo $app_res['wf_id']; ?>" />
                              <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                              <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">
                              <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
                              <input type="hidden" name="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
                              <input type="hidden" name="department_id" value="<?php echo $_COOKIE['department_id']; ?>" />
                              <input type="hidden" name="dept_name" value="<?php echo $pdept_res['dept_name']; ?>" />
                            
							
							
					<?php 
			//echo "folder access :".$_COOKIE['folder_access'];
			if($_COOKIE['folder_access']=='DOMAIN' || $_COOKIE['folder_access']=='BOTH' )
			{
			?>	
                 <div class="container-fluid ps-0 pb-3">
                <div class="d-flex gap-3 justify-content-center">
                     <div class=" form_btn cancelbtn">
                    <a class="btn btn-purple" style="color: white !important" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']; ?>" target="_blank">
					<i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>&nbsp Source</a>
                    </div>
                    <div class=" form_btn nxtbtn">
                    <a class="btn btn-success" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125 pt-1"></i> Work</a>
                    </div>
					<?php 
					if($app_res['cust_id']=='82')
					{
					?>
					<div class="form_btn">
						<button type="button" id="syncEditlatexBtn" class="btn btn-warning">
							<i class="ace-icon fa fa-refresh bigger-125 pt-1"></i> Sync to Editlatex
						</button>
					</div>
					<?php 
					}
					?>
                </div>
				
            </div>
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
					<div style="width: 100%" class="d-flex flex-column align-items-center">
                    <div class="col-12 col-sm-6 col-lg-4">
                    <label class="mb-2" >Choose Status :<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Status" name="status" id="status">
                                  <option value="">-Select Status-</option>
                                <?php 
								$temp_ps_array = $project_status_array;
								if($app_res['fig_complete_status']== 1){
									unset($temp_ps_array[1]);
								}
								
								foreach($temp_ps_array as $id=>$val){?>
                                  <option value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                  <?php } ?>
                                 </select>
								 
								  <?php 	if($app_res['fig_complete_status']== 1){
									echo "<span style='color:red;'>This is a assigned article, please re-check before click on save button.</span>";
								}
								?>
                </div>
  
           
						
						  <div id="fig_complete"   style="display:none;width: 100%;flex-direction:column;align-items:center;">
                    <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Query <span style="color:red;">*</span>:</label>
                           
                              <div class="clearfix">
                                <select name="graphic_query" id="graphic_query" class="form-select required">
                                  <option value="">-Select-</option>
                                  <?php 
								foreach($graphic_query_status as $id=>$val){?>
                                  <option value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                              
                            </div>
                          </div>
						  <div class="space-2"></div>
						  <div id="validate" style="display: flex; width: 100%;flex-direction:column;align-items:center;">
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Disk <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="img_disk_count" id="img_disk_count" class="form-control" required>
                              </div>                             
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Redraw <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="img_redraw_count" id="img_redraw_count" class="form-control" required>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Relabel <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="img_relabel_count" id="img_relabel_count" class="form-control" required>
                              </div>                             
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Color Figures <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="img_color_figures" id="img_color_figures" class="form-control" required>
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Total Figure Count <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="total_fig_count" id="total_fig_count" class="form-control" required>
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Anxillary Figures <span style="color:red;">*</span>:</label>
                              <div class="clearfix">
                              <input type="number" name="anxillary_fig" id="anxillary_fig" class="form-control" required>
                            </div>
                          </div>
						  </div>
						   <div id="nvalidate" style="display:none;">
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Disk:</label>
                              <div class="clearfix">
                              <input type="number" name="img_disk_count" id="img_disk_count" class="form-control">
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Redraw:</label>
                              <div class="clearfix">
                              <input type="number" name="img_redraw_count" id="img_redraw_count" class="form-control">
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Relabel:</label>
                              <div class="clearfix">
                              <input type="number" name="img_relabel_count" id="img_relabel_count" class="form-control">
                              </div>                             
                          </div>
                          <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Color Figures:</label>
                              <div class="clearfix">
                              <input type="number" name="img_color_figures" id="img_color_figures" class="form-control">
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Total Figure Count:</label>
                              <div class="clearfix">
                              <input type="number" name="total_fig_count" id="total_fig_count" class="form-control">
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="col-12 col-sm-6 col-lg-4">
                             <label class="mb-2" >Anxillary Figures:</label>
                              <div class="clearfix">
                              <input type="number" name="anxillary_fig" id="anxillary_fig" class="form-control">
                            </div>
                          </div>
						  </div>
                        
						   </div>
                        <div class="space-2"></div>
                        <div class="col-12 col-sm-6 col-lg-4" id="assign_department_div" <?php if($app_res['fig_complete_status']!=1){ ?> style="display:none;" <?php } ?>>
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
                           <h3 class="formcont1heading" style="position: relative; left: -103px;top: 10px">Checklist Form</h3>
                            <div class="col-xs-12 mt-3"> 
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
		 $query = "SELECT * FROM `jbm_checklist` WHERE (`stage` = '".$app_res['stage']."' OR `stage` = 'BOTH') AND `dept_id` = '".$_COOKIE['department_id']."' ORDER BY `display_order` ASC ";
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
						  </div>
                <div class="col-12 col-sm-12 col-lg-12"  >
                  <label class="mb-2">Comments <span style="color: red">*</span></label>
                  <textarea name="comments" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"></textarea>
                </div>

               

                
				<?php 
											$work_dir = $app_res['work_dir'];
											$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);

?>
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
        
		</form>
		</div>
		
			
            </div>
			</div>
			
			<?php 
			//echo "folder access :".$_COOKIE['folder_access'];
			if($_COOKIE['folder_access']=='WEB' || $_COOKIE['folder_access']=='BOTH' )
			{
			?>
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
					<div class="formcont1heading mb-2" style="position: relative; left: -36px"><?php echo str_replace("/"," / ",$folder) ?></div>
                    <?php //if ($base_path !== $folder) { ?>
					 
                         <div style="width:100%;border: none !important">
						<div class="row">
						<div class="col-12 col-md-6" style="border: none !important">
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
                               </div>
									<div class="col-12 col-md-6" style="width: 100%; display: flex; justify-content: end;border: none !important;" >
                                    <form method="post" enctype="multipart/form-data">
                                      <input style="width: 100%" type="hidden" name="currentfolder" value="<?php echo  $folder ?>"/>
                                        <input class="startworkuploadtype" type="file" name="upload_file" required=""/>
                                        <button class="mt-2 mt-md-0" name="upload" value="upload" style="cursor: pointer;padding: 10px;background: #ff9a9a;font-weight: bold;border: 1px solid #ff9a9a;margin-left: 5px"><i class="fa fa-upload"></i> Upload</button>
                                    </form>
                               </div>
							</div>
                        </div>
                    <?php //} ?>
                
            <div class="main pb-5">
                <div class="bottom-content" style="overflow: scroll">
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
		
			<?php 
			}
			?>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>
<div id="ajaxLoader_Editlatex" style="display:none;">
    <img src="loader.gif" />
</div>

<div id="overlay" style="display:none;"></div>					
     <!-- Footer Section -->
     <?php echo footer(); ?>
	 </div>

<!-- Footer Section -->
<?php echo scripts(); ?>	
	<script src="<?php echo $_COOKIE['set_url']; ?>/vendor/sweetalert2/dist/sweetalert2.min.js"></script>

   
       <!-- Initialize DataTables -->
  <!-- inline scripts related to this page --> 
<script type="text/javascript">


			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});				
			})
			
			
			function timepopup(id)
			{	
			//alert(id);
			
				var url = "<?php echo $_COOKIE['set_url'];?>/project_comments.php?id="+id;
				$("#ppopup").load(url);
			}
		$(document).ready(function(){	
			///////////// Get workflow from Journal //////////
	
	$('#status').on('change',function()
	{
        var status = $(this).val();
        if(status == 6)
		{
			$('#assign_department_div').show();
		}
		else
		{
            $('#assign_department_div').hide();
            
        }
		if(status == 1)
		{
			$('#fig_complete').css('display','flex');
			$('#fig_complete').show();
			$('#checklist_table').show();
			$('#validate').show();
			$('#nvalidate').hide();
		}
		else
		{
			$('#fig_complete').hide();
            $('#checklist_table').hide();
            $('#validate').hide();
			$('#nvalidate').show();
            
        }
		
    });
	
				/***************/
				$('.show-details-btn').on('click', function(e) {
					e.preventDefault();
					$( "#message-content" ).toggle( "slow");
					//add scrollbars to .message-body
						$('.message-content .message-body').ace_scroll({
							size: 150,
							mouseWheelLock: true,
							styleClass: 'scroll-visible'
						});
					$(this).find(ace.vars['.icon']).toggleClass('fa-angle-double-down').toggleClass('fa-angle-double-up');
				});
				/***************/
				
	
	})
	
		</script>
		
		<?php 
		if(isset($_POST['action']) && $_POST['action']=='update')
{
	
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	if($post['assign_department']!="")
	{
			$assign_dep=$post['assign_department'];
			$assign_dep_query=",`assign_department` ='". $assign_dep."'";
	}
	else
	{
	$assign_dep_query=",`assign_department` =NULL";
	}
	if($post['graphic_query']!="")
	{
	$graphic_query=$graphic_query_status[$post['graphic_query']];
	$graphic_querys=",graphic_query='".$graphic_query."'";
	}
	else
	{
	$graphic_querys=",graphic_query=NULL";
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
				system($python_bkp_article_dept);
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
	$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Completed',`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."' ".$assign_dep_query." ".$graphic_querys." WHERE id = ".$post['tr_id']." ";	
	}
	else
	{
		
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."'".$assign_dep_query." ".$graphic_querys." WHERE id = ".$post['tr_id']." ";
	}
	$db->query($update_query);
							
	//echo $update_query="UPDATE inw_transactions SET `end_time` =NOW(),`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."',`assign_department` ='". $post['assign_department']."' WHERE id = ".$post['tr_id']." ";	
	//echo  "1";
		//echo $update_query;		exit;			
							

	//if($db->query($update_query))
	//{
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
// rmdir($dest);
/* delete_directory($dest);
mkdir($dest, 0777, true);
rcopy($src,$dest); */



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
mkdir($dest, 0777, true);
rcopy($src,$dest); */
		
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
			// rmdir($dest);
			/* delete_directory($dest);		
			if(!mkdir($dest, 0777, true))
			{
				if(!mkdir($dest, 0777, true))
				{
					$err[] = "Error - Print folder in Pagination department is already opend, we can not create it now. Please try again.";			
				}
				
			}
			///// Copy the files from Graphics Print to PAG		
			rcopy($src,$dest); */

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
$prj_update_query="UPDATE inw_inward_dtl SET graphic_working_status = '".$graphic_working_status."' ".$user_working_status.",last_transaction_id='".$post['tr_id']."' WHERE id = ".$post['id']."; ";			
$db->query($prj_update_query);	

	// ✅ TRIGGER GRAPHICS SYNC ONLY FOR CUSTOMER 82 + COMPLETED


echo "<script type='text/javascript'>";

if ($artilce_info['cust_id'] == 82 && $post['status'] == 1) {

    echo "swal({
        title: 'Art work Completed',
        text: 'Graphics sync in progress...',
        type: 'success',
        confirmButtonColor: '#28a745',
        confirmButtonText: 'OK'
    }).then(function() {

        $.ajax({
            url: '{$_COOKIE['set_url']}/editlatex_graphics_sync.php',
            method: 'GET',
			dataType: 'json', // ✅ IMPORTANT
            data: {
                pub_id: '{$post['pub_id']}',
                vf: '{$post['volume_folder']}',
                stage: '{$post['stage']}'
            },

            // ✅ SHOW LOADER HERE
            beforeSend: function () {
                $('#ajaxLoader_Editlatex').show();
                $('#overlay').show();
            },

            success: function(response) {

                // ✅ HIDE LOADER
                $('#ajaxLoader_Editlatex').hide();
                $('#overlay').hide();

                try {
                    let res = response;

                    if (res.status === 'success') {

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Graphics Synced',
                            showConfirmButton: false,
                            timer: 3000
                        });

                    } else {
                        Swal.fire('Warning', res.message || 'Sync Failed', 'warning');
                    }

                } catch(e) {
                    console.error('Invalid JSON:', response);
                    Swal.fire('Error', 'Unexpected response from sync API', 'error');
                }

                // ✅ CLOSE AFTER AJAX COMPLETES
                setTimeout(function() {
                    window.close();
                    window.opener.focus();
                    window.opener.location.reload();
                }, 1000);
            },

            error: function(err) {

                // ✅ HIDE LOADER EVEN ON ERROR
                $('#ajaxLoader_Editlatex').hide();
                $('#overlay').hide();

                console.error(err);
                Swal.fire('Error', 'Graphics Sync Failed', 'error');

                setTimeout(function() {
                    window.close();
                    window.opener.focus();
                    window.opener.location.reload();
                }, 1000);
            }
        });

    });";

} else {

    // DEFAULT FLOW (NO CHANGE)
    echo "swal({
        title: 'Art work Status Updated as ".$project_status_array[$post['status']]." Successfully',
        type: 'success',
        confirmButtonColor: '#DD6B55',
        confirmButtonText: 'CLOSE'
    }).then(function() {
        window.close();
        window.opener.focus();
        window.opener.location.reload();
    });";

}

echo "</script>";

	
}

/////////////// Update completed ///////////////
?>
<?php
//echo "SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['id']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1";
$chk_transaction_time = mysqli_fetch_array(mysqli_query($db,"SELECT start_time FROM `inw_transactions` WHERE `project_id` = ".$_REQUEST['id']." AND `process_user` =  '".$_COOKIE['user_id']."' and current_stage='".$app_res['stage']."' order by id asc limit 1")); 	
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
		
		  // Add event listener to the submit button
    document.getElementById('submit-btn').addEventListener('click', function() {
        // Hide the submit button when clicked
        this.style.display = 'none';
    });
    </script>
	
	<script>
document.getElementById('syncEditlatexBtn').addEventListener('click', function (e) {
	
	  e.preventDefault(); // 🔥 prevents form submit

    const pubId = "<?php echo $app_res['pub_id']; ?>";
    const vf = "<?php echo $app_res['volume_folder']; ?>";
    const stage = "<?php echo $app_res['stage']; ?>";

    // Confirm before sync
    swal({
        title: "Are you sure?",
        text: "Do you want to sync graphics to Editlatex?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        confirmButtonText: "Yes, Sync",
        cancelButtonText: "Cancel"
    }).then(function() {

        $.ajax({
            url: "<?php echo $_COOKIE['set_url']; ?>/editlatex_graphics_sync.php",
            method: "GET",
			dataType: "json", // ✅ IMPORTANT
            data: {
                pub_id: pubId,
                vf: vf,
                stage: stage
            },

            // ✅ SHOW LOADER
            beforeSend: function () {
                $("#ajaxLoader_Editlatex").show();
                $("#overlay").show();
            },

            success: function(response) {

                // ✅ HIDE LOADER
                $("#ajaxLoader_Editlatex").hide();
                $("#overlay").hide();

                try {
                    let res = response;

                    if (res.status === "success") {

                        Swal.fire({
                            icon: "success",
                            title: "Success",
                            text: "Graphics Synced Successfully"
                        });

                    } else {

                        Swal.fire({
                            icon: "warning",
                            title: "Warning",
                            text: res.message || "Sync Failed"
                        });
                    }

                } catch (e) {
                    console.error("Invalid JSON:", response);

                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Unexpected response from server"
                    });
                }
            },

            error: function(err) {

                $("#ajaxLoader_Editlatex").hide();
                $("#overlay").hide();

                console.error(err);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Graphics Sync Failed"
                });
            }
        });

    });
});
</script>
  </body>
</html>

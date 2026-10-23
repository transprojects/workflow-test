<?php 
include ('dbc.php'); 
page_protect();

$err = array();
/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
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

if(isset($_POST['action']) && $_POST['action']=='update')
{
	
	foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
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
	if(($post['status'] == 1) && ($artilce_info['cust_id']=='33' || $artilce_info['cust_id']=='42' ))
	{
			//$config['IMAGE_CONVERSION'] = "E:/Transforma/PRINT_TO_WEB/run.bat";
			//$perl = '"'.$config['IMAGE_CONVERSION'].'" "'.$dest_print.'" "'.$dest_web.'" "'.strtoupper($Cn).'"';//exit;
			//exec($perl); 
	}
	//exit;
	if($post['status'] == 1 || $post['status'] == 6)
	{
	$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='Completed',`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."',`assign_department` ='". $post['assign_department']."',graphic_query='".$graphic_query_status[$post['graphic_query']]."' WHERE id = ".$post['tr_id']." ";	
	}
	else
	{
		$update_query="UPDATE inw_transactions SET `end_time` =NOW(),current_status ='".$project_status_array[$post['status']]."',`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."',`assign_department` ='". $post['assign_department']."',graphic_query='".$graphic_query_status[$post['graphic_query']]."' WHERE id = ".$post['tr_id']." ";
	}
		
	//$update_query="UPDATE inw_transactions SET `end_time` =NOW(),`completion_status` ='". $post['status']."',`comments` ='". $post['comments']."',`assign_department` ='". $post['assign_department']."' WHERE id = ".$post['tr_id']." ";	
	if($db->query($update_query))
	{
		if($post['assign_department']=='')
		{
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
		if(empty($err))
		{
			header("location:projects.php?msg=Successfully completed"); 
			die();
		}
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////

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
	}
	
}

//echo $transaction_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>Production - Workflow</title>
<meta name="description" content="3 styles with inline editable feature" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

<!-- page specific plugin styles -->
<!-- text fonts -->
<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
<link rel="stylesheet" href="assets/css/ace-skins.min.css" />

<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

<!-- inline styles related to this page -->

<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
</head>

<body class="no-skin">
<!-- .navbar-container -->
<?php include("includes/header.php"); ?>
<!-- /.navbar-container -->

<div class="main-container " id="main-container"> 
  <!-- .sidebar-shortcuts -->
  <?php include("includes/left_sidebar.php"); ?>
  <!-- /.sidebar-shortcuts -->
  
  <div class="main-content">
    <div class="main-content-inner">
      <div class="breadcrumbs " id="breadcrumbs">
        <ul class="breadcrumb">
          <li> <i class="ace-icon fa fa-home home-icon"></i> <a href="#">Home</a> </li>
          <li> <a href="#">Project</a> </li>
          <li class="active">Working on <?php echo $app_res['article_title']; ?></li>
        </ul>
        <!-- /.breadcrumb -->
        
        <div class="nav-search" id="nav-search">
          <form class="form-search">
            <span class="input-icon">
            <input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
            <i class="ace-icon fa fa-search nav-search-icon"></i> </span>
          </form>
        </div>
        <!-- /.nav-search --> 
      </div>
      <div class="page-content">
        <div class="page-header">
          <h1> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> </h1>
        </div>
        <!-- /.page-header -->
        
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS -->
            <div class="widget-box">
              <div class="widget-header widget-header-blue widget-header-flat">
                <h4 class="widget-title lighter">Production</h4>
              </div>
			  
			   <div class="profile-info-row">
                                <div class="profile-info-name"> <b style='color:black;font-size:15px;'>Graphic Query :</b> </div>
                                <div class="profile-info-value"> <span class="editable" id="login">
                                  <?php
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
                                  </span> </div>
                              </div>
              <p>
                <?php
	  /******************** ERROR MESSAGES*************************************************
	  This code is to show error messages 
	  **************************************************************************/
	  if(!empty($err))  {
	   echo "<div class=\"alert alert-block alert-danger\" style='font-size:12px;'>";
	  foreach ($err as $e) {
	    echo "$e <br>";
	    }
	  echo "</div>";	
	   }
	   
	    if(!empty($msg))  {
	    echo "<div class=\"msg-success\">" . $msg[0] . "</div>";

	   }
	  /******************************* END ********************************/	  
	  ?>
              </p>
              <div class="widget-body">
                <div class="widget-main">
                  <div id="fuelux-wizard-container">
                    <div class="step-content pos-rel">
                      <div class="step-pane active" data-step="1">
                        <h3 class="lighter block green">Article information</h3>
                        <div class="row">
                          <div class="col-md-8">
                            <div class="profile-user-info profile-user-info-striped">
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Customer </div>
                                <div class="profile-info-value"> <span class="editable" id="age"><?php echo $customer_info['cust_name']; ?></span> </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Journal </div>
                                <div class="profile-info-value"> <span class="editable" id="age"><?php echo $journal_info['j_code']; ?></span> </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Article </div>
                                <div class="profile-info-value"> <span class="editable" id="age"><?php echo $app_res['pub_id']; ?></span> </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Received Date </div>
                                <div class="profile-info-value"> <span class="editable" id="city"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></span> </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Due Date </div>
                                <div class="profile-info-value"> <span class="editable" id="login"><?php echo (date('d-m-Y',strtotime($app_res['due_dt']))); ?></span> </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Insructions </div>
                                <div class="profile-info-value"> <span class="editable" id="login"> <a href="#" class="green bigger-140 show-details-btn" title="Show Details"> <i class="ace-icon fa fa-angle-double-down"></i> <span class="">View Instructions</span> </a> </span><br>
                                  <div id="message-content" style="display:none;position: relative;" class="message-body ace-scroll" >
                                    <div class="hr hr-double"></div>
                                    <div class="scroll-content" style="max-height: 150px; overflow:scroll;"> <?php echo $app_res['instructions']; ?> </div>
                                    <div class="hr hr-double"></div>
                                  </div>
                                </div>
                              </div>
                              <div class="profile-info-row">
                                <div class="profile-info-name"> Department Comments </div>
                                <div class="profile-info-value"> <span class="editable" id="login">
                                  <?php
	 $di_query="SELECT comments FROM `inw_dept_comments`  WHERE dept_ids IN (".$_COOKIE['department_id'].") and inw_id='".$_REQUEST['id']."' ";
    $di_result = $db->query($di_query); 	
   
    $di_count=$di_result->num_rows;
    if($di_count>0)
    {
   	 	$di_res = $di_result->fetch_assoc();
		echo $di_res['comments'];
    }
    ?>
                                  </span> </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="widget-box widget-color-blue" id="widget-box-2">
                              <div class="widget-header">
                                <h5 class="widget-title bigger lighter"> <i class="ace-icon fa fa-table"></i> File History </h5>
                              </div>
                              <div class="widget-body">
                                <div class="widget-main no-padding">
                                  <table class="table table-striped table-bordered table-hover">
                                    <thead class="thin-border-bottom">
                                      <tr>
                                        <th> Stage </th>
                                        <th class="hidden-480">View DIR</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td class="">All Stages</td>
                                        <td class="hidden-480"><a class="btn btn-xs btn-purple btn-sm" href="file:///<?php echo str_replace(WF_DIR,TOOL_DIR,$app_res['work_dir']).$app_res['volume_folder']."/".$app_res['pub_id']."/"; ?>" target="_blank"> <i class="ace-icon fa fa-cloud-download bigger-120"></i> Veiw Previous FIles</a></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
						  <div class="col-md-4">
                      <div class="widget-box widget-color-blue" id="widget-box-2">
												<div class="widget-header">
													<h5 class="widget-title bigger lighter">
														<i class="ace-icon fa fa-envelope"></i>
														&nbsp;Related Emails
													</h5>

													
												</div>

												<div class="widget-body">
													<div class="widget-main no-padding">
														<table class="table table-striped table-bordered table-hover">
															
															<tbody>
																<tr>

																	

																	<td class="hidden-480">
                                                                    <a class="btn btn-xs btn-purple btn-sm" href="view_article_emails.php?id=<?php echo $_REQUEST['id']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Emails</a>
																		
																	</td>
																	
																		<td class="hidden-480">
                                                                    <a class="btn btn-xs btn-orange btn-sm" href="article-query.php?id=<?php echo $_REQUEST['id']; ?>" target="_blank"> <i class="ace-icon fa fa-envelope"></i>&nbsp;View Queries</a>
																		
																	</td>
																	
																		<td class="hidden-480">
                                                                    <a class="btn btn-xs btn-danger btn-sm" href="javascript:void();" style="text-decoration:none;color:red;" data-toggle="modal" data-target="#myModal" onClick="return timepopup('<?php echo $_REQUEST['id']; ?>');"> <i class="ace-icon fa fa-comments"></i>&nbsp;View Comments</a>
																		
																	</td>
																</tr>

																

																

																

																
															</tbody>
														</table>
													</div>
												</div>
											</div>
                      </div>
						  
                        </div>
						
						                          <?php
	//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$_COOKIE['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
	}
						?>
						                        <div class="space-20"></div>

                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Source DIR:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <div class="clearfix">
                                  <?php
							$work_dir = $app_res['work_dir'];
							$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
							?>
                                  <a class="btn btn-primary" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']; ?>" target="_blank"> <i class="ace-icon fa fa-folder align-top bigger-125"></i> Source </a> <a class="btn btn-success" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><i class="ace-icon fa fa-folder-open align-top bigger-125"></i> Work</a> </div>
                              </div>
                            </div>
                          </div>
                        <div class="space-20"></div>
						                        <div class="space-20"></div>

                        <form class="form-horizontal" id="validation-form" method="post">
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Change to Status :</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <select name="status" id="status" class="required">
                                  <option value="">-Select-</option>
                                  <?php 
								$temp_ps_array = $project_status_array;
								if($app_res['fig_complete_status']== 1){
									unset($temp_ps_array[1]);
								}
								
								foreach($temp_ps_array as $id=>$val){?>
                                  <option value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                              <?php 	if($app_res['fig_complete_status']== 1){
									echo "This is a assigned article, please re-check before click on save button.";
								}
								?>
                            </div>
                          </div>
                          <div class="space-2"></div>
						  <div id="fig_complete"   style="display:none;">
						  <div class="form-group" >
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Query <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <select name="graphic_query" id="graphic_query" class="required">
                                  <option value="">-Select-</option>
                                  <?php 
								foreach($graphic_query_status as $id=>$val){?>
                                  <option value="<?php echo $id; ?>"><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                              
                            </div>
                          </div>
						  <div class="space-2"></div>
						  <div id="validate">
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Disk <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_disk_count" id="img_disk_count" required>
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Redraw <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_redraw_count" id="img_redraw_count" required>
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Relabel <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_relabel_count" id="img_relabel_count" required>
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Color Figures <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_color_figures" id="img_color_figures" required>
                              </div>                             
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Total Figure Count <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="total_fig_count" id="total_fig_count" required>
                              </div>                             
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Anxillary Figures <span style="color:red;">*</span>:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="anxillary_fig" id="anxillary_fig" required>
                              </div>                             
                            </div>
                          </div>
						  </div>
						   <div id="nvalidate" style="display:none;">
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Disk:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_disk_count" id="img_disk_count">
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Redraw:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_redraw_count" id="img_redraw_count">
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Relabel:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_relabel_count" id="img_relabel_count">
                              </div>                             
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Color Figures:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="img_color_figures" id="img_color_figures">
                              </div>                             
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Total Figure Count:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="total_fig_count" id="total_fig_count">
                              </div>                             
                            </div>
                          </div>
						  <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Anxillary Figures:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <input type="number" name="anxillary_fig" id="anxillary_fig">
                              </div>                             
                            </div>
                          </div>
						  </div>
                        
						   </div>
						     <div class="space-2"></div>
                          <div class="form-group" id="assign_department_div" <?php if($app_res['fig_complete_status']!=1){ ?> style="display:none;" <?php } ?>>
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="email">Assign Department:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <?php
									$workflow_query = "SELECT wf_code FROM `adm_workflow_master` WHERE `wf_id` =".$app_res['wf_id']."";
									$workflow_details = $db->query($workflow_query) or ($db->error);
									$workflow_info = $workflow_details->fetch_assoc();
									$all_dpt_info = explode(',',$workflow_info['wf_code']);
									/// add graphics dpt in list ////
									if (!in_array("9", $all_dpt_info))
									{			
										$all_dpt_info[]="9"; 
									}
									$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code !=".$app_res['department_id']." ORDER BY `dept_name` ASC";
									$customers = $db->query($query) or ($db->error);		
								?>
                                <select id="assign_department" name="assign_department" class="col-xs-12 col-sm-4" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="row" id="checklist_table" style="display:none;">
                            <h3 class="lighter block green">Checlkist From</h3>
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12">
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
                                        <td><input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="Yes" checked="checked"/></td>
                                        <td><input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="NA"/></td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Comments:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <textarea name="comments" class="form-control" id="form-field-8" placeholder="Comments"></textarea>
                              </div>
                            </div>
                          </div>

                          <div class="space-2"></div>
                          <div class="form-group">
                            <div class="col-xs-12 col-sm-9 center">
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
                              <a href="projects.php" class="btn btn-grey">Cancel</a>
                              <button type="submit" value="Save" class="btn btn-info">Save</button>
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- /.widget-main --> 
              </div>
              <!-- /.widget-body --> 
            </div>
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
        <!-- /.row --> 
      </div>
      <!-- /.page-content --> 
    </div>
  </div>
  <!-- /.main-content -->
  <div class="container"> 
  
  <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">    
      <!-- Modal content-->
      <div class="modal-content">        
        <div class="modal-body">		
          <p id="ppopup"></p>
        </div>        
      </div>
      
    </div>
  </div>
  
</div>


  <?php include("includes/footer.php"); ?>
  <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"> <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i> </a> </div>
<!-- /.main-container --> 

<!-- basic scripts --> 

<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]--> 

<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]--> 
<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script> 
<script src="assets/js/bootstrap.min.js"></script> 

<!-- page specific plugin scripts --> 

<script src="assets/js/jquery.validate.min.js"></script> 

<!-- ace scripts --> 
<script src="assets/js/ace-elements.min.js"></script> 
<script src="assets/js/ace.min.js"></script>  
<script src="assets/js/bootbox.js"></script>

<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});				
			})
			function timepopup(id)
			{				
				var url = "project_comments.php?id="+id;
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
</body>
</html>

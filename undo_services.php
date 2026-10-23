<?php 
include ('dbc.php'); 
page_protect();
?>
<?php
function clean($string) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

/// Add New record
if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
	}

	$post['recv_dt'] = convert_mdy_ymd($post['recv_dt']);
	$post['accpt_dt'] = convert_mdy_ymd($post['accpt_dt']);
	$post['due_dt'] = convert_mdy_ymd($post['due_dt']);
	$post['pub_dt'] = convert_mdy_ymd($post['pub_dt']);
	

	//// Same Stage and change the Department ///////
	if($_POST['dept_id'] > 0)
	{
		/////// Get the department name /////
		$result = $db->query("SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = '".$_POST['dept_id']."' ;"); 	
		$toatl_count=$result->num_rows;
		$dept_res = array();
		if($toatl_count>0)
		{
			$dept_res = $result->fetch_assoc();
		}
	}
	else
	{
		//// Get the worflow Steps ////
		$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
		$steps = $db->query($query) or ($db->error);
		$deptinfo = $steps->fetch_assoc();
		$dept_info = explode(',',$deptinfo['wf_code']);
		/////// Get the department name /////
		$result = $db->query("SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
		$toatl_count=$result->num_rows;
		$dept_res = array();
		if($toatl_count>0)
		{
			$dept_res = $result->fetch_assoc();
		}	
		
	}
	/// Get the Customer Details /////	
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$_POST['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();

	$Cn= $crow['cust_name'];
	$artile_name= $_POST['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$production = 'production';
	$books_folder = 'BOOKS';

	
	/// Create the DIR
		$work_dir = BOOKS_WF_DIR . "/$production/$books_folder/$Cn/" . VOLUME_FOLDER_NAME . "/$bsn/";
	//$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
	//mkdir($dest, 0777, true);
	

	$query = $db -> query("SELECT id,book_short_name FROM inw_book_dtl WHERE id = '".$post['b_id']."' ");
	$fetch = $query -> fetch_assoc();
	
	$bsn = clean($fetch['book_short_name']);
	$bs_stage = "BS";
	
	$destination_dir = BOOKS_WF_DIR . "/$download_folder/$books_folder/$Cn/" . VOLUME_FOLDER_NAME . "/$bsn/" .$bs_stage."/";

	if (!(is_dir($destination_dir))) {
	if (!mkdir($destination_dir, 0777, true)) {
    die('Failed to create folders...$jn');
	}
	}
	
	
	if(isset($_FILES['attachment']) && $_FILES['attachment']['name']!='')
	{
		//// Check for zip file /////
	
		$errors= array();
		$file_name = $_FILES['attachment']['name'];
		$file_size =$_FILES['attachment']['size'];
		$file_tmp =$_FILES['attachment']['tmp_name'];
		$file_type=$_FILES['attachment']['type'];
		
		if (strrpos($file_name, '.zip') === strlen($file_name)-strlen('.zip'))
		{
			$zip = new ZipArchive;
			$res = $zip->open($file_tmp);	
			//var_dump($res);		
			if ($res === TRUE)
			{
				$zip->extractTo($destination_dir);
				$zip->close();  
			}
		}
		else
		{
			$Path     = $destination_dir;
			move_uploaded_file($file_tmp, $Path.$file_name);				
		}				
	}
	
	$update_dept_status = '';
	if($_POST['is_update_dept'] > 0)
	{
	$update_dept_status = ",wf_id = '".$post['wf_id']."',department_id = '".$dept_info[0]."',
	status = '".$dept_res['dept_name']."'";
	}
	
	if($_POST['dept_id'] > 0)
	{
		$update_dept_status = ",department_id = '".$_POST['dept_id']."',status = '".$dept_res['dept_name']."'";
	}
	    $dept_id = $post['dept_id'];
	// echo "SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = '".$dept_id."' ";
	$query = $db -> query("SELECT dept_code,dept_name FROM adm_dept_master WHERE dept_code = '".$dept_id."' ");
	$fetch = $query -> fetch_assoc();

	    $dept_name = $fetch['dept_name'];

		// Folder Movements 
		
		$work_dir = BOOKS_WF_DIR . "/$production/$books_folder/$Cn/" . VOLUME_FOLDER_NAME . "/$bsn/";
		 $dest = $work_dir . $post['stage'] . "/";   // destination folder or file 
         mkdir($dest, 0777, true);
		$fmdest = $dest . $post['service_title']."/" . $dept_res['dept_name']."/";   // destination folder or file 
        mkdir($fmdest, 0777, true);
	
	    $insert_query = "UPDATE inw_book_service_dtl SET `cust_id` = '".$post['cust_id']."',`b_id` ='".$post['b_id']."',`recv_dt` = '".$post['recv_dt']."',`due_dt` = '".$post['due_dt']."',`fig` = '".$post['fig']."',`tab` = '".$post['tab']."',`wf_id` = '".$post['wf_id']."',manuscript_count = '".$post['manuscript_count']."',notes = '".$post['notes']."',instructions = '".$post['instructions']."',graphics = '".$post['graphics']."',status = '".$dept_name."',department_id = '".$dept_id."'  WHERE id = '".$post['id']."' ;";	

		$db->query($insert_query) or die($db->error);
		
		/// IF Figures in Article we create the Graphics Folder ////
		
		/////// Get the department name /////
		if($post['fig']>0)
		{
			//// Graphics Dept ID is : 9 ////
			
			$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = 9;"); 	
			$toatl_count=$result->num_rows;
			$dept_res = array();
			if($toatl_count>0)
			{
				$dept_res = $result->fetch_assoc();
			}
			
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/";   // destination folder or file 
			mkdir($dest, 0777, true);
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/Print";   // PAG Folder 
			mkdir($dest, 0777, true);
			$dest = $work_dir.VOLUME_FOLDER_NAME.$post['group_folder_name']."/".$artile_name."/".$post['stage']."/".$dept_res['dept_name']."/Web";   // XML Folder 
			mkdir($dest, 0777, true);				
			//rcopy($src,$dest);
		
		}
		

		header("location:dispath_services.php?msg=Successfully added"); 
		die();	
}

/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_book_service_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$pinfo = $result->fetch_assoc();
	}	
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>Inward - Workflow</title>
<meta name="description" content="3 styles with inline editable feature" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

<!-- page specific plugin styles -->
<link rel="stylesheet" href="assets/css/select2.min.css" />
<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
<link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />

<!-- text fonts -->
<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

<!-- inline styles related to this page -->

<!-- ace settings handler -->
<script src="assets/js/ace-extra.min.js"></script>

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
          <li> <a href="#">Service</a> </li>
          <li class="active">Undo</li>
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
          <h1> Edit Services </h1>
        </div>
        <!-- /.page-header -->
        
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS -->
            <div class="widget-box">
              <div class="widget-header widget-header-blue widget-header-flat">
                <h4 class="widget-title lighter">Edit</h4>
              </div>
              <div class="widget-body">
                <div class="widget-main">
                  <div id="fuelux-wizard-container">
                    <div class="step-content pos-rel">
                      <div class="step-pane active" data-step="1">
                        <h3 class="lighter block green">Edit the following information</h3>
                        <form class="form-horizontal" id="validation-form" method="post" enctype="multipart/form-data">
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="email">Choose Customer:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <?php
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$pinfo['cust_id']."' ORDER BY `cust_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                <select id="cust_id" name="cust_id" class="col-xs-12 col-sm-4 required" readonly data-placeholder="Choose">
                            
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if($pinfo['cust_id'] ==$row['id']){?> selected <?php } ?>><?php echo $row['cust_name']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="email">Choose Book Short Name:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <?php
		$query = "SELECT `id`,`book_short_name`,cust_id FROM `inw_book_dtl` WHERE id = '".$pinfo['b_id']."' ORDER BY `id` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                <select id="b_id" name="b_id" class="col-xs-12 col-sm-4 required" data-placeholder="Choose">
                          
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>"><?php echo $row['book_short_name']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Service Title:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <input type="text" id="service_title" name="service_title" readonly = "readonly" class="col-xs-12 col-sm-10 required" value="<?php echo $pinfo['book_service_title']; ?>" />
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Received Date:</label>
                            <div class="col-xs-12 col-sm-4">
                              <div class="input-group">
                                <input type="text" id="recv_dt" name="recv_dt" class="required col-xs-12 col-sm-12 date-picker" value="<?php echo convert_ymd_dmy($pinfo['recv_dt']); ?>" />
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Due Date:</label>
                            <div class="col-xs-12 col-sm-4">
                              <div class="input-group">
                                <input type="text" id="due_dt" name="due_dt" class="required col-xs-12 col-sm-12 date-picker" value="<?php echo convert_ymd_dmy($pinfo['due_dt']); ?>" />
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div>
                          <div class="hr hr-dotted"></div>
                       
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Fig Count:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <input type="text" id="fig" name="fig" class="col-xs-12 col-sm-4" value="<?php echo $pinfo['fig']; ?>" />
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Table Count:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <input type="text" id="tab" name="tab" class="col-xs-12 col-sm-4" value="<?php echo $pinfo['tab']; ?>" />
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Manuscript Count:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <input type="text" value="<?php echo $pinfo['manuscript_count']; ?>" id="manuscript_count" name="manuscript_count" class="col-xs-12 col-sm-4" />
                              </div>
                            </div>
                          </div>
						    
                          <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Stage:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix"> 
                             
                              <input type="text" readonly = "readonly" name="stage" value="<?php echo $pinfo['stage']; ?>" >
                         
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>
                                 <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="email">Choose Workflow:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <?php
$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE w_cust_id = '".$pinfo['cust_id']."' ORDER BY `wf_code` ASC ";
$customers = $db->query($query) or ($db->error);	
		
		?>
                                <select id="wf_id" name="wf_id" class="col-xs-12 col-sm-4 required" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['wf_id']; ?>" <?php if($pinfo['wf_id'] ==$row['wf_id']){?> selected <?php } ?>><?php echo $row['wf_name']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                          </div>
                       
                           <div class="space-2"></div>
                          <div class="form-group">
								  <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Complexity:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix"> 
                              <select name="complexity" class="col-xs-12 col-sm-4 required">
                              <option value="">-Select-</option>
                              <?php foreach($complexity_types as $val){?>
                               <option value="<?php echo $val; ?>" <?php if($pinfo['complexity'] ==$val){?> selected <?php } ?>><?php echo $val; ?></option>
                              <?php } ?>                              
                              </select>
                              </div>
                            </div>
                            
                          </div>                          
                           <div class="space-2"></div>
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Instructions:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                              <textarea class="col-xs-12 col-sm-6" name="instructions" id="instructions" rows="5" cols="100"><?php echo $pinfo['instructions']; ?></textarea>
                              </div>
                            </div>
                          </div>
                          <div class="space-2"></div>                          
			                          <div class="form-group">
		                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Notes:</label>
		                          <div class="col-xs-12 col-sm-9">
		                             <div class="clearfix">
			                                <textarea type="text" id="notes" name="notes" class="col-xs-12 col-sm-4" value="<?php echo $pinfo['notes']?>" /><?php echo $pinfo['notes']?></textarea>
			                              </div>
		                            </div>
			                          </div>
                          <div class="space-2"></div>
                          <div class="form-group no-margin-bottom">
											<label class="col-sm-3 control-label no-padding-right">Attachment:</label>

											<div class="col-sm-9">
												<div id="form-attachments">
													<input type="file" name="attachment" />
												</div>
											</div>
										</div>
                          <div class="space-2"></div>                          
                          <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Do you need to change the Department?:</label>
                            <div class="col-xs-12 col-sm-9">
                              <div class="clearfix">
                                <input type="checkbox" id="is_update_dept" name="is_update_dept" value="1" />
                              </div>
                            </div>
                          </div>
                          
                          <div class="form-group" id="assign_department_div">
                          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="email">Choose Department to Assign in the same stage:</label>
                          <div class="col-xs-12 col-sm-9">
                            <div class="clearfix">
                            
                              <?php
	//// Get the worflow Steps ////
	$query1 = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$pinfo['wf_id']."' ;";
	$steps1 = $db->query($query1) or ($db->error);
	$deptinfo1 = $steps1-> fetch_assoc();
    $deptinfo1['wf_code'];	
	$dept_code1 = explode(",",$deptinfo1['wf_code']);
	$size = sizeof($dept_code1);

	?>
                              <select id="dept_id" name="dept_id" class="col-xs-12 col-sm-4" data-placeholder="Choose">
                                   
                                								
                                <?php 
								for($i = 0; $i < $size; $i++)
								{
								$query = "SELECT * FROM `adm_dept_master` WHERE dept_code = '".$dept_code1[$i]."' ORDER BY `dept_code` ASC";
	                            $customers = $db->query($query) or ($db->error);
								while($row = $customers->fetch_assoc()){ ?>
                                <option value="<?php echo $row['dept_code']; ?>"><?php echo $row['dept_name']; ?></option>
                                <?php } 
								}?>
                              </select>
                            </div>
                          </div>
                        </div>
                           <div class="space-2"></div>
                          <div class="form-group">
                            <div class="col-xs-12 col-sm-9 center">
                              <input type="hidden" name="action" value="update">
                              <input type="hidden" name="group_folder_name" value="<?php echo $pinfo['issueorgroup_folder']; ?>" />
                              
                              <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />  <a href="dispath_services.php" class="btn btn-grey">Cancel</a>
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
<script src="assets/js/wizard.min.js"></script> 
<script src="assets/js/jquery.validate.min.js"></script> 
<script src="assets/js/jquery-additional-methods.min.js"></script> 
<script src="assets/js/bootbox.js"></script> 
<script src="assets/js/jquery.maskedinput.min.js"></script> 
<script src="assets/js/select2.min.js"></script> 
<script src="assets/js/bootstrap-datepicker.min.js"></script> 

<!-- ace scripts --> 
<script src="assets/js/ace-elements.min.js"></script> 
<script src="assets/js/ace.min.js"></script> 

<!-- inline scripts related to this page --> 
<script type="text/javascript">
	$('#assign_department_div').hide();
			jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
				
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
				//show datepicker when clicking on the icon
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
			
			
				
				$("form").submit(function (e) {
					var isChecked = $("#is_update_dept").is(":checked");
					if (isChecked) {
						if(confirm('You are changing the main workflow for this Article. Are you sure you want to continue?'))
							$("#is_update_dept").attr("checked", "checked");
						else
							e.preventDefault();
					} 
				});
				
				$('#validation-form').validate({errorClass: 'help-block'});				
				
			})
			
			$(document).ready(function(){
    $('#cust_id').on('change',function(){
        var j_cust_id = $(this).val();
        if(j_cust_id){
            $.ajax({
                type:'POST',
                url:'get_journals.php',
                data:'j_cust_id='+j_cust_id,
                success:function(html){
                    $('#j_id').html(html);                    
                }
            }); 
        }else{
            $('#j_id').html('<option value="">Select customer first</option>');
            
        }
    });
	
		// Updating Dept
	$('#is_update_dept').on('change',function(){
        var is_update_dept = $(this).val();
        if(is_update_dept){
            $.ajax({
                type:'POST',
                url:'get_dept_update.php',
                data:'is_update_dept='+is_update_dept,
                success:function(result){
					// alert(result);
					if(result == 1)
					{
                    $('#assign_department_div').show(1000);   
					}
                    else if(result == 0)
					{
						$('#assign_department_div').hide();
					}						
                }
            }); 
        }
    });
	
	///////////// Get Dept from Workflow //////////
	
	$('#wf_id').on('change',function(){
        var wf_id = $(this).val();
        if(wf_id){
            $.ajax({
                type:'POST',
                url:'get_dept_service.php',
                data:'wf_id='+wf_id,
                success:function(html){
					// alert(html);
                    $('#dept_id').html(html);                    
                }
            }); 
        }else{
            $('#dept_id').html('<option value="">Select Workflow first</option>');
            
        }
    });
	});
		</script>
</body>
</html>

<?php 
include ('dbc.php'); 


/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
 
$sqlpuser = $db->query("select * from users where id = ".$_REQUEST['id']);
$userrow = $sqlpuser->fetch_array();



	$transsql = $db->query("select * from login_history where DATE_FORMAT(lh_current_dt,'%Y-%m-%d') = '".$_REQUEST['date']."' and lh_userid=".$_REQUEST['id']."") or ($db->error);$login_count=$transsql->num_rows;
			

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
<?PHP 
	$cdate =explode(" ",$app_res['created_dt']);	
	$startdate = date("d-m-Y", strtotime($cdate[0]));	
	$recvicedate = date("d-m-Y", strtotime($app_res['recv_dt']));
	$duedate = date("d-m-Y", strtotime($app_res['due_dt'])); ?>
<body class="no-skin">
<!-- .navbar-container -->
<?php //include("includes/header.php"); ?>
<!-- /.navbar-container -->
<div class="main-container " id="main-container" style="margin-left: -15px;"> 
  <!-- .sidebar-shortcuts -->
  <?php include("includes/left_sidebar.php"); ?>
  <!-- /.sidebar-shortcuts -->
  <div class="main-content">
    <div class="main-content-inner">
      <div class="breadcrumbs " id="breadcrumbs">
        <ul class="breadcrumb">
         <!-- <li> <i class="ace-icon fa fa-home home-icon"></i> <a href="#">Home</a> </li>
          <li> <a href="javascript:void(0);">Project</a> </li>-->
          <li class="active">Total Login- <b><?php echo $login_count;?></b></li>

        </ul>
        <!-- /.breadcrumb -->
        <!--<div class="nav-search" id="nav-search">
          <form class="form-search">
            <span class="input-icon">
            <input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
            <i class="ace-icon fa fa-search nav-search-icon"></i> </span>
          </form>
        </div>-->
        <!-- /.nav-search -->  
      </div>
      <div class="page-content">
        <!--<div class="page-header">
          <h1> <?php echo $userrow['full_name']; ?> (<?php echo $userrow['user_name']; ?>) </h1>
        </div>-->
        <!-- /.page-header -->
		<button type="button" class="close" onclick="return closepop();" data-dismiss="modal" style="margin-top: -40px;">&times;</button>
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS -->
				
						<div class="row">
							<div class="col-xs-12" >
								<!-- PAGE CONTENT BEGINS -->
								<div>
									<div class="row">
										<div class="col-xs-12 col-sm-10 col-sm-offset-1" style="width: 100%;margin-left: 0px;">
											<div class="">
												<div class="timeline-label">
													<span class="label label-primary arrowed-in-right label-lg">
														<b><?php echo date("d-m-Y", strtotime($_REQUEST['date']));?></b>
													</span>
												</div>
											
											</div>
												
										<table style="line-height: 50px;width:100%;" class="form-search">		
										<tr>
										<th  style="text-align:center;">S.no</th>
										<th  style="text-align:center;">Login time</th>
										<th  style="text-align:center;">Logout time</th>
										</tr>										
										<?PHP 
										$sno=1;
										while($row = $transsql->fetch_array())
												{	
												?>
												
													


														
																<tr>
																<td  style="text-align:center;"><?PHP echo $sno;?></td>
																<td  style="text-align:center;"> <?PHP   echo date("H:i a", strtotime($row['lh_current_dt']));?> </td>
																<td style="text-align:center;"><?PHP   echo date("H:i a", strtotime($row['lh_logout_time']));?> </td>
															</tr>
														
														
														
												 																			
																		
													<?PHP $sno++; } ?>
														
													</table>

											

								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						
			</div><!-- /.main-content -->
				
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

<?php //include("includes/footer.php"); ?>
<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"> <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i> </a>
</div>
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

<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});				
			})
			
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
			$('#checklist_table').show();
		}
		else
		{
            $('#checklist_table').hide();
            
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
	<!-- ace scripts -->
		
		
	
		
</body>
</html>

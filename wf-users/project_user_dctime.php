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
													
	$transsql = $db->query("select * from inw_transactions where DATE_FORMAT(created_dt,'%Y-%m-%d') = '".$_REQUEST['date']."' and process_user=".$_REQUEST['id']." and current_status='Completed' group by project_id ") or ($db->error);				
//echo $transaction_id;

function differenceInHours($startdate,$enddate){
	/*$starttimestamp = strtotime($startdate);
	$endtimestamp = strtotime($enddate);
	$difference = abs($endtimestamp - $starttimestamp)/3600;
	return round($difference,2);*/
	
	$datetime1 = new DateTime($startdate);
	$datetime2 = new DateTime($enddate);
	$interval = $datetime1->diff($datetime2);
	echo $interval->format('%h')." Hours ".$interval->format('%i')." Minutes ".$interval->format('%s')." Seconds";
}

function get_time_difference($time1, $time2) {
    $time1 = strtotime($time1);
    $time2 = strtotime($time2);

if ($time2 < $time1) {
    $time2 += 86400;
}

return date("H:i:s",($time2 - $time1));
}

function getHoursMinutes($seconds, $format = '%02d:%02d') {

    if (empty($seconds) || ! is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = ($minutes % 60);

    return sprintf($format, $hours, $remainMinutes);
}
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
          <li class="active">Completed Articles List - <b><?php echo $userrow['full_name'];?></b> </li>
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
          <h1> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> </h1>
        </div>-->
        <!-- /.page-header -->
		<button type="button" class="close" onclick="return closepop();" data-dismiss="modal" style="margin-top: -40px;">&times;</button>
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS -->
				
						<div class="row">
							<div class="col-xs-12" >
								<!-- PAGE CONTENT BEGINS -->
								<div id="timeline-1">
									<div class="row">
										<div class="col-xs-12 col-sm-10 col-sm-offset-1" style="width: 100%;margin-left: 0px;">
											<div class="timeline-container">
												<div class="timeline-label">
													<span class="label label-primary arrowed-in-right label-lg">
														<b><?PHP echo $_REQUEST['date'];?></b>
													</span>
												</div>
											
											</div><!-- /.timeline-container -->
											<div class="timeline-container">
												<!--<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Person Details</b>
													</span>
												</div>-->
												<div class="timeline-items">												
										<?PHP 
										$sno=1;
										while($row = $transsql->fetch_array())
												{	
													/*========== user table ===========*/
													$sqlpuser = $db->query("select * from users where id = ".$row['process_user']);
													$userrow = $sqlpuser->fetch_array();
													
													$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$row['project_id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}
/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();	
	
	
													/*========== Department table ===========*/
													$sqlpdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['dept']);
													$deptrow = $sqlpdept->fetch_array();
													
													$assigndept ="";
												if($row['assign_department']!="0"){
													/*========== Department Assign table ===========*/
													$sqlassdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['assign_department']);
													$assdeptrow = $sqlassdept->fetch_array();
													$assigndept = $assdeptrow['dept_name'];
												}
												$sd = explode(" ",$row['start_time']);
												$ed = explode(" ",$row['end_time']);
												$diff += strtotime($row['end_time'])-strtotime($row['start_time']);
												$ppic = "avatar2.png";
												if($userrow['profile_pic']!=""){ $ppic =$userrow['profile_pic']; }?>
												<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b><?PHP echo $row['stage'];?></b>
													</span>
												</div>
													<div class="timeline-item clearfix">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="assets/images/avatars/<?PHP echo $ppic; ?>" />
															<span class="label label-info label-sm"><?PHP echo $sd['1'];?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller"><?PHP echo ucwords($sno); ?>)</h5>
<?PHP if(isset($ed['1'])){?>
																<span class="widget-toolbar">
																	<i class="ace-icon fa fa-clock-o bigger-110"></i>
																	<?PHP  echo $ed['1'];?>
																</span>
<?PHP }?>
															</div>
															<div class="widget-body">
																<div class="widget-main">
																	<div class="clearfix">
																		<div class="pull-left">


														<table style="line-height: 30px;">
														
																<tr >
																<td width="150px;">Article/Pub ID</td>
																<td width=20px;>:</td>
																<td><?PHP echo $app_res['pub_id'];?></td>
															</tr>
															<tr>
																<td>Date</td>
																<td>:</td>
																<td><?PHP   echo date("d-m-Y", strtotime($row['created_dt']));?> </td>
															</tr>
														</table>
														
														
												 																			
																		</div>																		
																	</div>
																</div>
															</div>
														</div>
													</div>
													<?PHP $sno++; } ?>
														
													

												</div><!-- /.timeline-items -->
											</div><!-- /.timeline-container -->
											</div><!-- /.timeline-container -->
										</div>
									</div>
								</div>

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

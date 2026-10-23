<?php 
include ('dbc.php'); 
page_protect(); 

function differenceInHours($startdate,$enddate){
	/*$starttimestamp = strtotime($startdate);
	$endtimestamp = strtotime($enddate);
	$difference = abs($endtimestamp - $starttimestamp)/3600;
	return round($difference,2);*/
	
	$datetime1 = new DateTime($startdate);
	$datetime2 = new DateTime($enddate);
	$interval = $datetime1->diff($datetime2);
	echo $interval->format('%h')." Hours ".$interval->format('%i')." Minutes";
}

function get_time_difference($time1, $time2) {
    $time1 = strtotime($time1);
    $time2 = strtotime($time2);

if ($time2 < $time1) {
    $time2 += 86400;
}

return date("H:i:s",($time2 - $time1));
}



/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
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
	
	$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$app_res['id']." and stage = 'FP'") or ($db->error);
	$fp_cnt = $transsql->num_rows;
	if($fp_cnt > 1)
	{
		$app_res['stage'] = 'FP';
	}
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
		
<link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,600,700,800,900&display=swap" rel="stylesheet">

</head>

<body class="no-skin" style="font-family: 'Montserrat', sans-serif;">
<!-- .navbar-container --> 

<!-- /.navbar-container -->
<div class="main-container " id="main-container" style="margin-left: -15px;">
  <div class="main-content">
    <div class="main-content-inner">
      <div class="page-content">
        <div class="row">
          <div class="col-xs-12">
          
          <?php 
	$cdate =explode(" ",$app_res['created_dt']);	
	$startdate = date("d-m-Y", strtotime($cdate[0]));	
	$recvicedate = date("d-m-Y", strtotime($app_res['recv_dt']));
	$duedate = date("d-m-Y", strtotime($app_res['due_dt'])); ?>
            <div class="row">
              <div class="col-xs-12"> 
                <!-- PAGE CONTENT BEGINS -->
                <div class="space-6"></div>
                <div class="row">
                  <div class="col-sm-10 col-sm-offset-1">
                    <div class="widget-box transparent">
                      <div class="widget-header widget-header-large">
                        <h3 class="widget-title dark"> <img style="height:30px;" src="assets/images/trans-logo.png" alt="Transforma Pvt Ltd" title="Transforma Pvt Ltd"> <span class="col-sm-offset-2"> JOB CARD - <?php echo $customer_info['cust_name']; ?> - <?php echo $app_res['stage'];?> </span> </h3>
                        <div class="widget-toolbar no-border invoice-info"> <span class="invoice-info-label">Article ID:</span> <span class="red">#<?php echo $app_res['pub_id']; ?></span> <br />
                          <span class="invoice-info-label">Date:</span> <span class="blue"><?php echo $startdate; ?></span> </div>
                        <div class="widget-toolbar hidden-480"> <a href="#"> <i class="ace-icon fa fa-print"></i> </a> </div>
                      </div>
                      <div class="widget-body">
                        <div class="widget-main padding-24">
                          <div class="row">
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-info arrowed-in arrowed-right"> <b>Customer Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td> Client </td>
                                        <td><?php echo $customer_info['cust_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Journal/Book</td>
                                        <td><?php echo $journal_info['j_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Article/Chapter Name</td>
                                        <td><?php echo $app_res['pub_id']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Input Type</td>
                                        <td><?php echo $app_res['inw_type']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>No Of Pages</td>
                                        <td><?php echo $app_res['page_count']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col -->
                            
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-success arrowed-in arrowed-right"> <b>Proof Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td>Received Date </td>
                                        <td><?php echo $recvicedate;?></td>
                                      </tr>
                                      <tr>
                                        <td>Due Date</td>
                                        <td><?php echo $duedate; ?></td>
                                      </tr>
                                      <tr>
                                        <td>MSS Pages</td>
                                        <td><?php echo $app_res['manuscript_count']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Figures</td>
                                        <td><?php echo $app_res['fig']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Tables</td>
                                        <td><?php echo $app_res['tab']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col --> 
                          </div>
                          <!-- /.row -->
                          
                          <div class="space"></div>
                          <div class="row">
                            <div class="col-xs-12 label label-lg label-danger arrowed-in arrowed-right"> <b>Production Work Details</b> </div>
                          </div>
                          <div>
                            <table class="table table-striped table-bordered">
                              <thead>
                                <tr>
                                  <th class="center">#</th>
                                  <th class="hidden-480">Dept</th>
                                  <th>Name</th>
                                  <th>Date</th>
                                  <th class="hidden-xs">Time Spent in Hrs</th>
                                  <th>Completion Status</th>
                                  <th>Comments</th>
                                  <th>Assign Department</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php 	
								$sno=0;
								$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$app_res['id']." and stage = '".$app_res['stage']."'") or ($db->error);				

								while($row = $transsql->fetch_array())
												{	
												$sno++;
																									
												$assigndept ="";
												if($row['assign_department']!="0")
												{
													/*========== Department Assign table ===========*/
													$sqlassdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['assign_department']);
													$assdeptrow = $sqlassdept->fetch_array();
													$assigndept = $assdeptrow['dept_name'];
												}
												
												
												?>
                                <tr>
                                  <td class="center"><?php echo $sno; ?></td>
                                  <td class="hidden-480"><?php echo $row['dept_name'];?></td>
                                  <td><?php echo ucwords($row['full_name']); ?> (<b><?php echo ucwords($row['user_name']); ?></b>)</td>
                                  <td><?php echo date("d-m-Y", strtotime($row['start_time']));?>
                                   <?php if($row['end_time']!=`NULL`) {?>  - to -
                                    <?php echo date("d-m-Y", strtotime($row['end_time']));}?><br/><b style="color:black;"><?php echo date("H:i a", strtotime($row['start_time']));?> <?php if($row['end_time']!=`NULL`) {?> - to - <?php echo date("H:i a", strtotime($row['end_time'])); }?></b></td>
                                  <td class="hidden-xs"><?php if(!is_null($row['end_time'])){?>
                                    <i class="ace-icon fa fa-clock-o bigger-110"></i>
                                    <?php  echo differenceInHours($row['start_time'],$row['end_time']);?>
                                    <?php }?></td>
                                  <td><?php echo $project_status_array[$row['completion_status']];?></td>
                                  <td><?php echo $row['comments'];?></td>
                                  <td><?php echo $assigndept;?></td>
                                </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                          <div class="hr hr8 hr-double hr-dotted"></div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="widget-box">
                                <div class="widget-header">
                                  <h4 class="smaller"> Notes and Special Instructions </h4>
                                </div>
                                <div class="widget-body">
                                  <div class="widget-main">
                                    <p class="muted"><?php echo $app_res['notes']; ?></p>
                                    <p class="muted"><?php echo $app_res['instructions']; ?></p>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="space-6"></div>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="">REC-EDG-01 V1.1</div>
                        <div class="pull-right">Internal</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- PAGE CONTENT ENDS --> 
                </div>
                <!-- /.col --> 
              </div>
              <!-- PAGE CONTENT BEGINS --> 
              
            </div>
            
            <?php
	$query = "SELECT * FROM `inw_revisions_dtl` WHERE inw_id = ".$app_res['id']." ORDER BY `r_id` ASC;";
	$inw_rev = $db->query($query) or ($db->error);
	while($rev_info = $inw_rev->fetch_assoc()){
		
		$startdate = date("d-m-Y", strtotime($rev_info['created_on']));
		$recvicedate = date("d-m-Y", strtotime($rev_info['received_date']));
		$duedate = date("d-m-Y", strtotime($rev_info['due_date']));
		$app_res['stage'] = $rev_info['type'].$rev_info['revision_count'];
	?>
            <hr/>
            
            <div class="row">
              <div class="col-xs-12"> 
                <!-- PAGE CONTENT BEGINS -->
                <div class="space-6"></div>
                <div class="row">
                  <div class="col-sm-10 col-sm-offset-1">
                    <div class="widget-box transparent">
                      <div class="widget-header widget-header-large">
                        <h3 class="widget-title dark"> <img style="height:30px;" src="assets/images/trans-logo.png" alt="Transforma Pvt Ltd" title="Transforma Pvt Ltd"> <span class="col-sm-offset-2"> JOB CARD - <?php echo $customer_info['cust_name']; ?> - <?php echo $app_res['stage'];?> </span> </h3>
                        <div class="widget-toolbar no-border invoice-info"> <span class="invoice-info-label">Article ID:</span> <span class="red">#<?php echo $app_res['pub_id']; ?></span> <br />
                          <span class="invoice-info-label">Date:</span> <span class="blue"><?php echo $startdate; ?></span> </div>
                        <div class="widget-toolbar hidden-480"> <a href="#"> <i class="ace-icon fa fa-print"></i> </a> </div>
                      </div>
                      <div class="widget-body">
                        <div class="widget-main padding-24">
                          <div class="row">
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-info arrowed-in arrowed-right"> <b>Customer Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td> Client </td>
                                        <td><?php echo $customer_info['cust_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Journal/Book</td>
                                        <td><?php echo $journal_info['j_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Article/Chapter Name</td>
                                        <td><?php echo $app_res['pub_id']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Input Type</td>
                                        <td><?php echo $app_res['inw_type']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>No Of Pages</td>
                                        <td><?php echo $rev_info['correction_pages']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col -->
                            
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-success arrowed-in arrowed-right"> <b>Proof Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td>Received Date </td>
                                        <td><?php echo $recvicedate;?></td>
                                      </tr>
                                      <tr>
                                        <td>Due Date</td>
                                        <td><?php echo $duedate; ?></td>
                                      </tr>
                                      <tr>
                                        <td>MSS Pages</td>
                                        <td><?php echo $app_res['manuscript_count']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Figures</td>
                                        <td><?php echo $app_res['fig']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Tables</td>
                                        <td><?php echo $app_res['tab']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col --> 
                          </div>
                          <!-- /.row -->
                          
                          <div class="space"></div>
                          <div class="row">
                            <div class="col-xs-12 label label-lg label-danger arrowed-in arrowed-right"> <b>Production Work Details</b> </div>
                          </div>
                          <div>
                            <table class="table table-striped table-bordered">
                              <thead>
                                <tr>
                                  <th class="center">#</th>
                                  <th class="hidden-480">Dept</th>
                                  <th>Name</th>
                                  <th>Date</th>
                                  <th class="hidden-xs">Time Spent in Hrs</th>
                                  <th>Completion Status</th>
                                  <th>Comments</th>
                                  <th>Assign Department</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php 	
								$sno=0;
								$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$app_res['id']." and stage = '".$app_res['stage']."'") or ($db->error);				

								while($row = $transsql->fetch_array())
												{	
												$sno++;
																									
												$assigndept ="";
												if($row['assign_department']!="0")
												{
													/*========== Department Assign table ===========*/
													$sqlassdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['assign_department']);
													$assdeptrow = $sqlassdept->fetch_array();
													$assigndept = $assdeptrow['dept_name'];
												}
												
												
												?>
                                <tr>
                                  <td class="center"><?php echo $sno; ?></td>
                                  <td class="hidden-480"><?php echo $row['dept_name'];?></td>
                                  <td><?php echo ucwords($row['full_name']); ?> (<b><?php echo ucwords($row['user_name']); ?></b>)</td>
                                    <td><?php echo date("d-m-Y", strtotime($row['start_time']));?>
                                   <?php if($row['end_time']!=`NULL`) {?>  - to -
                                    <?php echo date("d-m-Y", strtotime($row['end_time']));}?><br/><b style="color:black;"><?php echo date("H:i a", strtotime($row['start_time']));?> <?php if($row['end_time']!=`NULL`) {?> - to - <?php echo date("H:i a", strtotime($row['end_time'])); }?></b></td>
                                  <td class="hidden-xs"><?php if(!is_null($row['end_time'])){?>
                                    <i class="ace-icon fa fa-clock-o bigger-110"></i>
                                    <?php  echo differenceInHours($row['start_time'],$row['end_time']);?>
                                    <?php }?></td>
                                  <td><?php echo $project_status_array[$row['completion_status']];?></td>
                                  <td><?php echo $row['comments'];?></td>
                                  <td><?php echo $assigndept;?></td>
                                </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                          <div class="hr hr8 hr-double hr-dotted"></div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="widget-box">
                                <div class="widget-header">
                                  <h4 class="smaller"> Notes and Special Instructions </h4>
                                </div>
                                <div class="widget-body">
                                  <div class="widget-main">                                   
                                    <p class="muted"><?php echo $rev_info['comments']; ?></p>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="space-6"></div>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="">REC-EDG-01 V1.1</div>
                        <div class="pull-right">Internal</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- PAGE CONTENT ENDS --> 
                </div>
                <!-- /.col --> 
              </div>
              <!-- PAGE CONTENT BEGINS --> 
              
            </div>
            
            <?php } ?>
            
			
            <?php
			//echo "SELECT * FROM `inw_issue_corrections` WHERE inw_id = ".$app_res['id']." ORDER BY `ic_id` ASC;";
	$query = "SELECT * FROM `inw_issue_corrections` WHERE inw_id = ".$app_res['id']." ORDER BY `ic_id` ASC;";
	$inw_rev = $db->query($query) or ($db->error);
	while($rev_info = $inw_rev->fetch_assoc()){
		
		$startdate = date("d-m-Y", strtotime($rev_info['created_on']));
		$recvicedate = date("d-m-Y", strtotime($rev_info['received_date']));
		$duedate = date("d-m-Y", strtotime($rev_info['due_date']));
		$app_res['stage'] = $rev_info['type'].$rev_info['revision_count'];
	?>
            <hr/>
            
            <div class="row">
              <div class="col-xs-12"> 
                <!-- PAGE CONTENT BEGINS -->
                <div class="space-6"></div>
                <div class="row">
                  <div class="col-sm-10 col-sm-offset-1">
                    <div class="widget-box transparent">
                      <div class="widget-header widget-header-large">
                        <h3 class="widget-title dark"> <img style="height:30px;" src="assets/images/trans-logo.png" alt="Transforma Pvt Ltd" title="Transforma Pvt Ltd"> <span class="col-sm-offset-2"> JOB CARD - <?php echo $customer_info['cust_name']; ?> - <?php echo $app_res['stage'];?> </span> </h3>
                        <div class="widget-toolbar no-border invoice-info"> <span class="invoice-info-label">Article ID:</span> <span class="red">#<?php echo $app_res['pub_id']; ?></span> <br />
                          <span class="invoice-info-label">Date:</span> <span class="blue"><?php echo $startdate; ?></span> </div>
                        <div class="widget-toolbar hidden-480"> <a href="#"> <i class="ace-icon fa fa-print"></i> </a> </div>
                      </div>
                      <div class="widget-body">
                        <div class="widget-main padding-24">
                          <div class="row">
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-info arrowed-in arrowed-right"> <b>Customer Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td> Client </td>
                                        <td><?php echo $customer_info['cust_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Journal/Book</td>
                                        <td><?php echo $journal_info['j_name']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Article/Chapter Name</td>
                                        <td><?php echo $app_res['pub_id']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Input Type</td>
                                        <td><?php echo $app_res['inw_type']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>No Of Pages</td>
                                        <td><?php echo $rev_info['correction_pages']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col -->
                            
                            <div class="col-sm-6">
                              <div class="row">
                                <div class="col-xs-11 label label-lg label-success arrowed-in arrowed-right"> <b>Proof Info</b> </div>
                              </div>
                              <div class="row">
                                <div class="col-xs-11">
                                  <table class="table table-striped table-bordered">
                                    <tbody>
                                      <tr>
                                        <td>Received Date </td>
                                        <td><?php echo $recvicedate;?></td>
                                      </tr>
                                      <tr>
                                        <td>Due Date</td>
                                        <td><?php echo $duedate; ?></td>
                                      </tr>
                                      <tr>
                                        <td>MSS Pages</td>
                                        <td><?php echo $app_res['manuscript_count']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Figures</td>
                                        <td><?php echo $app_res['fig']; ?></td>
                                      </tr>
                                      <tr>
                                        <td>Tables</td>
                                        <td><?php echo $app_res['tab']; ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                              <div> </div>
                            </div>
                            <!-- /.col --> 
                          </div>
                          <!-- /.row -->
                          
                          <div class="space"></div>
                          <div class="row">
                            <div class="col-xs-12 label label-lg label-danger arrowed-in arrowed-right"> <b>Production Work Details</b> </div>
                          </div>
                          <div>
                            <table class="table table-striped table-bordered">
                              <thead>
                                <tr>
                                  <th class="center">#</th>
                                  <th class="hidden-480">Dept</th>
                                  <th>Name</th>
                                  <th>Date</th>
                                  <th class="hidden-xs">Time Spent in Hrs</th>
                                  <th>Completion Status</th>
                                  <th>Comments</th>
                                  <th>Assign Department</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php 	
								$sno=0;
								$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$app_res['id']." and stage = '".$app_res['stage']."'") or ($db->error);				

								while($row = $transsql->fetch_array())
												{	
												$sno++;
																									
												$assigndept ="";
												if($row['assign_department']!="0")
												{
													/*========== Department Assign table ===========*/
													$sqlassdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['assign_department']);
													$assdeptrow = $sqlassdept->fetch_array();
													$assigndept = $assdeptrow['dept_name'];
												}
												
												
												?>
                                <tr>
                                  <td class="center"><?php echo $sno; ?></td>
                                  <td class="hidden-480"><?php echo $row['dept_name'];?></td>
                                  <td><?php echo ucwords($row['full_name']); ?> (<b><?php echo ucwords($row['user_name']); ?></b>)</td>
                                   <td><?php echo date("d-m-Y", strtotime($row['start_time']));?>
                                   <?php if($row['end_time']!=`NULL`) {?>  - to -
                                    <?php echo date("d-m-Y", strtotime($row['end_time']));}?><br/><b style="color:black;"><?php echo date("H:i a", strtotime($row['start_time']));?> <?php if($row['end_time']!=`NULL`) {?> - to - <?php echo date("H:i a", strtotime($row['end_time'])); }?></b></td>
                                  <td class="hidden-xs"><?php if(!is_null($row['end_time'])){?>
                                    <i class="ace-icon fa fa-clock-o bigger-110"></i>
                                    <?php  echo differenceInHours($row['start_time'],$row['end_time']);?>
                                    <?php }?></td>
                                  <td><?php echo $project_status_array[$row['completion_status']];?></td>
                                  <td><?php echo $row['comments'];?></td>
                                  <td><?php echo $assigndept;?></td>
                                </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                          <div class="hr hr8 hr-double hr-dotted"></div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="widget-box">
                                <div class="widget-header">
                                  <h4 class="smaller"> Notes and Special Instructions </h4>
                                </div>
                                <div class="widget-body">
                                  <div class="widget-main">                                   
                                    <p class="muted"><?php echo $rev_info['comments']; ?></p>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="space-6"></div>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="">REC-EDG-01 V1.1</div>
                        <div class="pull-right">Internal</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- PAGE CONTENT ENDS --> 
                </div>
                <!-- /.col --> 
              </div>
              <!-- PAGE CONTENT BEGINS --> 
              
            </div>
            
            <?php } ?>
            
            <!-- /.main-content --> 
            
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


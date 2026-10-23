<?php include "includes/includes.php";?>

<?php 

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
}
	$transsql = $db->query("select * from inw_transactions where project_id = ".$app_res['id']) or ($db->error);				
//echo $transaction_id;
?>

<?php 
	$cdate =explode(" ",$app_res['created_dt']);	
	$startdate = date("d-m-Y", strtotime($cdate[0]));	
	$recvicedate = date("d-m-Y", strtotime($app_res['recv_dt']));
	$duedate = date("d-m-Y", strtotime($app_res['due_dt'])); ?>

      <div class="page-content">
        <!--<div class="page-header">
          <h1> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> </h1>
        </div>-->
        <!-- /.page-header -->
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
														<b><?PHP echo $startdate;?></b>
													</span>
												</div>
												<div class="timeline-items">
													<div class="timeline-item ">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="<?php echo $_COOKIE['set_url'];?>/assets/images/avatars/avatar2.png" />
															<span class="label label-info label-sm"><?PHP echo $cdate[1];?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller">
																	<a href="javascript:void(0);" class="blue"><?PHP echo $customer_info['cust_name']; ?></a>
																</h5>
																<span class="widget-toolbar no-border">
																	<i class="ace-icon fa fa-clock-o bigger-110"></i>
																	<?PHP echo $cdate[1];?>
																</span>																
															</div>
															<div class="widget-body">
																<div class="widget-main">
																	<span class="grey"><?PHP echo $journal_info['j_name']; ?></span>																
																	<div class="space-6"></div>																	
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-cutlery btn btn-success no-hover"></i>
														</div>
														<div class="widget-box transparent">
															<div class="widget-body">
																<div class="widget-main">
																	Received Date : <?PHP echo $recvicedate;?>																	
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-star btn btn-warning no-hover green"></i>
														</div>

														<div class="widget-box transparent">
																													
															<div class="widget-body">
																<div class="widget-main">
																	Due Date : <?PHP echo $duedate; ?>																																
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-flask btn btn-default no-hover"></i>
														</div>
														<div class="widget-box transparent">
															<div class="widget-body">
																<div class="widget-main"> Article : <?PHP echo $app_res['article_title']; ?></div>
															</div>
														</div>
													</div>
												</div><!-- /.timeline-items -->
											</div><!-- /.timeline-container -->
											<div class="timeline-container">
												<!--<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Person Details</b>
													</span>
												</div>-->
												<div class="timeline-items">												
										<?PHP 	while($row = $transsql->fetch_array())
												{	
													/*========== user table ===========*/
													$sqlpuser = $db->query("select * from users where id = ".$row['process_user']);
													$userrow = $sqlpuser->fetch_array();
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
												$ppic = "avatar2.png";
												if($userrow['profile_pic']!=""){ $ppic =$userrow['profile_pic']; }?>
												<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b><?PHP echo $row['stage'];?></b>
													</span>
												</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="<?php echo $_COOKIE['set_url'];?>/assets/images/avatars/<?PHP echo $ppic; ?>" />
															<span class="label label-info label-sm"><?PHP echo $sd['1'];?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller"><?PHP echo ucwords($userrow['user_name']); ?> - <?PHP echo ucwords($userrow['full_name']); ?> </h5>
<?PHP if(isset($ed['1'])){?>
																<span class="widget-toolbar">
																	<i class="ace-icon fa fa-clock-o bigger-110"></i>
																	<?PHP  echo $ed['1'];?>
																</span>
<?PHP }?>
															</div>
															<div class="widget-body">
																<div class="widget-main">
																	<div class="">
																		<div class="pull-left">


														<table style="line-height: 30px;">
															<tr >
																<td width="150px;">Current Status</td>
																<td width=20px;>:</td>
																<td><?PHP echo $row['current_status'];?></td>
															</tr>
															<tr>
																<td>Date</td>
																<td>:</td>
																<td><?PHP   echo date("d-m-Y", strtotime($sd['0']));?> - to - <?PHP 																			
																			echo date("d-m-Y", strtotime($ed['0']));?></td>
															</tr>
															<tr>
																<td>Completion Status</td>
																<td>:</td>
																<td><?PHP echo $project_status_array[$row['completion_status']];?></td>
															</tr>
															<?PHP if($row['comments']!=""){?>
															<tr>
																<td>Comments</td>
																<td>:</td>
																<td><?PHP echo $row['comments'];?></td>
															</tr>
															<?PHP }if($assigndept!=""){?>
															<tr>
																<td>Assign Department</td>
																<td>:</td>
																<td><?PHP echo $assigndept;?></td>
															</tr>
															<?PHP }?>
															
														</table>
												 																			
																		</div>																		
																	</div>
																</div>
															</div>
														</div>
													</div>
													
													<?PHP } ?>
													

											
												</div><!-- /.timeline-items -->
											</div><!-- /.timeline-container -->
											</div><!-- /.timeline-container -->
										</div>
									</div>
								</div>

		
	
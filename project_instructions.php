<?php include "includes/conn.php";?>
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
	$transsql = $db->query("select * from inw_dept_comments where inw_id = ".$app_res['id']) or ($db->error);				
	$instruction_num=mysqli_num_rows(mysqli_query($db,"select * from inw_dept_comments where inw_id = ".$app_res['id'].""));				
	$transsql_sql = $db->query("select * from inw_transactions where project_id = ".$app_res['id']." and comments!='' ") or ($db->error);				
//echo $transaction_id;
?>
<style>
 .rounded-row td {
    background-color: #e5c99a; /* Row background color */
  }

  .rounded-row td:first-child {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
  }

  .rounded-row td:last-child {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
  }
</style>
<?php 
	$cdate =explode(" ",$app_res['created_dt']);	
	$startdate = date("d-m-Y", strtotime($cdate[0]));	
	$recvicedate = date("d-m-Y", strtotime($app_res['recv_dt']));
	$duedate = date("d-m-Y", strtotime($app_res['due_dt'])); ?>

  
      <div class="page-content">
        <div class="page-header">
          <!-- <h5> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> : <?php echo $app_res['pub_id']; ?></h5> -->
		  <h5 style="text-align:center;color:red;"> <?php echo $app_res['pub_id']; ?></h5> 

        </div>
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
											<?php /*?><div class="timeline-container">
												<div class="timeline-label">
													<span class="label label-primary arrowed-in-right label-lg">
														<b><?php echo $startdate;?></b>
													</span>
												</div>
												<div class="timeline-items">
													<div class="timeline-item ">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="http://103.66.12.3:8010/workflow-new/assets/images/avatars/avatar2.png" />
															<span class="label label-info label-sm"><?php echo $cdate[1];?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller">
																	<a href="javascript:void(0);" class="blue"><?php echo $customer_info['cust_name']; ?></a>
																</h5>
																<span class="widget-toolbar no-border">
																	<i class="ace-icon fa fa-clock-o bigger-110"></i>
																	 <?php echo $cdate[1];?>
																</span>																
															</div>
															<div class="widget-body">
																<div class="widget-main">
																	<span class="grey"><?php echo $journal_info['j_name']; ?></span>																
																	<div class="space-6"></div>																	
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-calendar btn btn-success no-hover"></i>
														</div>
														<div class="widget-box transparent">
															<div class="widget-body">
																<div class="widget-main">
																	Received Date : <?php echo $recvicedate;?>																	
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-calendar btn btn-warning no-hover green"></i>
														</div>

														<div class="widget-box transparent">
																													
															<div class="widget-body">
																<div class="widget-main">
																	Due Date : <?php echo $duedate; ?>																																
																</div>
															</div>
														</div>
													</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<i class="timeline-indicator ace-icon fa fa-sticky-note-o btn btn-default no-hover" style="background: #000; color: #fff"></i>
														</div>
														<div class="widget-box transparent">
															<div class="widget-body">
																<div class="widget-main"> Article : <?php echo $app_res['pub_id']; ?></div>
															</div>
														</div>
													</div>
												</div><!-- /.timeline-items -->
											</div>
											<?php */?>
											<!-- /.timeline-container -->
											<div class="timeline-container">
												<!--<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Person Details</b>
													</span>
												</div>-->
												<?php 
												if($instruction_num>0)
												{
												?>
												<p style="text-align: center;color: red;font-size: 20px;font-weight: 600;">Customer Instructions</p>
												<?php 
												}
												?>
												<div class="timeline-items">												
										<?PHP $ins=1;
										while($row = $transsql->fetch_array())
												{	
													/*========== user table ===========*/
													$sqlpuser = $db->query("select * from users where id = ".$row['created_by']);
													$userrow = $sqlpuser->fetch_array();
													/*========== Department table ===========*/
													$sqlpdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$row['created_dept']);
													$deptrow = $sqlpdept->fetch_array();
													
													$assigndept ="";
												
												
												$ppic = "avatar2.png";
												if($userrow['profile_pic']!=""){ $ppic =$userrow['profile_pic']; }?>
												<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Instructions: <?php echo $ins;?></b>
													</span>
												</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="http://103.66.12.3:8010/workflow-new/assets/images/avatars/<?php echo $ppic; ?>" />
															<span class="label label-info label-sm"><?php echo date('d-m-Y ',strtotime($row_trans['created_dt'])); ?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller"><?php echo ucwords($userrow['user_name']); ?></h5>
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
																		<div class="pull-left" style="float:none !important;">


														<table style="line-height: 30px;">
															<tr >
																<td width="150px;">From Name</td>
																<td width=20px;>:</td>
																<td><?php echo $userrow['full_name'] ?></td>
															</tr>
															<tr >
																<td width="150px;">From Department</td>
																<td width=20px;>:</td>
																<td><?php echo $deptrow['dept_name'];?></td>
															</tr>
															<?php 
															$array_dep=$row['dept_ids'];
															if($array_dep=='All')
															{
																$depart_names='ALL';
															}
															else
															{
															$sqlpdeptd=mysqli_query($db,"select dept_code,dept_name from adm_dept_master where FIND_IN_SET(dept_code,'".$array_dep."')");
															
															}
															?>
															<tr style="min-height: 100px !important; height: 171px !important;">
															<td width="150px;">To Department</td>
															<td width="20px;">:</td>
															<td style="max-height: 150px; overflow-y: auto; display: block;">
															<?php 
															$sno = 1;
															while ($res_dep = mysqli_fetch_array($sqlpdeptd)) {
															echo $sno . ")" . $res_dep['dept_name'] . "<br/>";
															$sno++;
															}
															?>
															</td>
															</tr>
															<tr>
																<td>Date</td>
																<td>:</td>
																<td><?php echo date('d-m-Y h:i:s A',strtotime($row['created_on'])); ?></td>
															</tr>
															
															<?PHP if($row['comments']!=""){?>
															<tr class="rounded-row">
																<td>Comments</td>
																<td>:</td>
																<td><?php echo nl2br($row['comments']);?></td>
															</tr>
															<?PHP }?>
															
														</table>
												 																			
																		</div>																		
																	</div>
																</div>
															</div>
														</div>
													</div>
													
													<?PHP
													$ins++;
													}
													?>
													

												</div><!-- /.timeline-items -->
											</div><!-- /.timeline-container -->
											
											<div class="timeline-container">
												<!--<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Person Details</b>
													</span>
												</div>-->
												<p style="text-align: center;color: red;font-size: 20px;font-weight: 600;">Internal Instructions</p>
												<div class="timeline-items">												
										<?PHP 
										$com=1;
										while($row_trans = $transsql_sql->fetch_array())
												{	
													/*========== user table ===========*/
													$sqlpuser = $db->query("select * from users where id = ".$row_trans['created_by']);
													$userrow = $sqlpuser->fetch_array();
													/*========== Department table ===========*/
													$sqlpdept = $db->query("select dept_code,dept_name from adm_dept_master where dept_code = ".$userrow['department_id']);
													$deptrow = $sqlpdept->fetch_array();
													
													$assigndept ="";
												
												
												$ppic = "avatar2.png";
												if($userrow['profile_pic']!=""){ $ppic =$userrow['profile_pic']; }?>
												<div class="timeline-label">
													<span class="label label-success arrowed-in-right label-lg">
														<b>Comments : <?php echo $com;?></b>
													</span>
												</div>
													<div class="timeline-item ">
														<div class="timeline-info">
															<img alt="Susan't Avatar" src="http://103.66.12.3:8010/workflow-new/assets/images/avatars/<?php echo $ppic; ?>" />
															<span class="label label-info label-sm"><?php echo date('d-m-Y',strtotime($row_trans['created_dt'])); ?></span>
														</div>
														<div class="widget-box transparent">
															<div class="widget-header widget-header-small">
																<h5 class="widget-title smaller"><?php echo ucwords($userrow['user_name']); ?></h5>
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
																		<div class="pull-left" style="float:none !important;">


														<table style="line-height: 30px;">
															<tr >
																<td width="150px;">From Name</td>
																<td width=20px;>:</td>
																<td><?php echo $userrow['full_name'] ?></td>
															</tr>
															<tr >
																<td width="150px;">From Department</td>
																<td width=20px;>:</td>
																<td><?php echo $deptrow['dept_name'];?></td>
															</tr>
															<tr>
																<td>Date</td>
																<td>:</td>
																<td><?php echo date('d-m-Y h:i:s A',strtotime($row_trans['created_dt'])); ?></td>
															</tr>
															
															<?PHP if($row_trans['comments']!=""){?>
															<tr>
																<td>Comments</td>
																<td>:</td>
																<td><?php echo $row_trans['comments'];?></td>
															</tr>
															<?PHP }?>
															
														</table>
												 																			
																		</div>																		
																	</div>
																</div>
															</div>
														</div>
													</div>
													
													<?PHP $com++; } ?>
													

												</div><!-- /.timeline-items -->
											</div><!-- /.timeline-container -->
											</div><!-- /.timeline-container -->
										</div>
									</div>
								</div>

		
	


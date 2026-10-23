<?php 
include ('dbc.php'); 
?>
<?php
header("Content-type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment;Filename=wip-report-".date('Y-m-d-H:i a').".xls");
?>
<?php
extract($_POST);
$search = "";
$search1 = "";
//echo $_REQUEST['mfrom_dt'];

if(isset($_SESSION['inpt_status_qry']) || $_SESSION['inpt_status_qry'] =='' )
{
	$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
}


if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != 'all') {
    $ccc .= "and wd.cust_id='" . $_REQUEST['cust_id'] . "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] == 'all') {
    $ccc .= "";
}
if ($_REQUEST['j_id'] != "" && $_REQUEST['j_id'] != 'all') {
    $ccc .= "and j.j_id='" . $_REQUEST['j_id'] . "' ";
}
if ($_REQUEST['j_id'] != "" && $_REQUEST['j_id'] == 'all') {
    $ccc .= "";
}
if ($_REQUEST['stage_id'] != "" && $_REQUEST['stage_id'] != 'all') {
    $search2 .= "and wd.stage like '%" . $_REQUEST['stage_id'] . "%' ";
}

if ($_REQUEST['stage_id'] != "" && $_REQUEST['stage_id'] == 'all') {
    $search2 .= "";
}
if($_REQUEST['dep_id']!=''){
	
     $ccc.= "and department_id= '".$_REQUEST['dep_id']."' ";
}
if ($_REQUEST['dp_platform'] != "") {
     $ccc .= "and j.j_platform='" . $_REQUEST['dp_platform'] . "' ";
}


$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";

if($_REQUEST['inpt_status']!=''){
	
		$_SESSION['inpt_status'] = $_POST['inpt_status'];
	 
	 if($_SESSION['inpt_status']!=''){
	if (in_array("Query", $_SESSION['inpt_status']) && in_array("Hold", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed') ";
	}
	else if(in_array("Query", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Hold') ";
	}
	else if(in_array("Hold", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query') ";
	}
     
}
}
if ($_REQUEST['radio']=='ce') {
if ($_REQUEST['cfrom_dt'] != "") {
    $frm = date('Y-m-d', strtotime($_REQUEST['cfrom_dt']));
    $to = date('Y-m-d', strtotime($_REQUEST['cto_dt']));
    $search .= "and DATE_FORMAT(sent_date,'%Y-%m-%d') >='" . $frm . "' AND DATE_FORMAT(sent_date,'%Y-%m-%d')<='" . $to . "'";
	
}
}
if ($_REQUEST['radio']=='dp' || $_REQUEST['radio']=='cr') {
if ($_REQUEST['from_dt'] != "" && $_REQUEST['date_wise'] == "d") {
    $frm = date('Y-m-d', strtotime($_REQUEST['from_dt']));
    $to = date('Y-m-d', strtotime($_REQUEST['to_dt']));
    $search .= "and DATE_FORMAT(wd.sent_date,'%Y-%m-%d') >='" . $frm . "' AND DATE_FORMAT(wd.sent_date,'%Y-%m-%d')<='" . $to . "'";
	
}
if ($_REQUEST['date_wise'] == "m") {
//echo $_REQUEST['mfrom_dt'];
    $frm = $_REQUEST['mfrom_dt'];
    $search .= "and DATE_FORMAT(wd.sent_date,'%m-%Y')='" . $frm . "'";

   

}
if ($_REQUEST['date_wise'] == "y") {
    $frm = $_REQUEST['yfrom_dt'];
    $to = $_REQUEST['yto_dt'];
    $search .= "and DATE_FORMAT(wd.sent_date,'%Y')='" . $frm . "'";
 
   
}
if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
{
 $search3.="AND wd.sent_date >='2020-03-01'";
 $search4.="AND wd.sent_date >='2020-03-01'";

}
if ($_REQUEST['dp_platform'] != "") {
     $search .= "and j.j_platform='" . $_REQUEST['dp_platform'] . "' ";
}
}
elseif ($_REQUEST['radio']=='sfd') {
if ($_REQUEST['sdf_dt'] != "") {
    $frm = date('Y-m-d', strtotime($_REQUEST['sdf_dt']));
    $search .= "and DATE_FORMAT(wd.target_due_dt,'%Y-%m-%d')='" . $frm . "'";
	
}
if ($_REQUEST['dp_platform'] != "") {
     $search .= "and j.j_platform='" . $_REQUEST['dp_platform'] . "' ";
}
}
else
{
	if ($_REQUEST['from_dt'] != "" && $_REQUEST['date_wise'] == "d") {
    $frm = date('Y-m-d', strtotime($_REQUEST['from_dt'])) . " 00:00:00";
    $to = date('Y-m-d', strtotime($_REQUEST['to_dt'])) . " 23:59:59";
    $search .= "and wd.recv_dt >='" . $frm . "' AND wd.recv_dt<='" . $to . "'";
//$search1.="and start_time >='".$frm."' AND start_time<='".$to."'";
    $search3 .= "and ((wd.recv_dt >='" . $frm . "' AND wd.recv_dt<='" . $to . "') or (r.received_date >='" . $frm . "' AND r.received_date<='" . $to . "'))";
    $search4 .= "and ((wd.due_dt >='" . $frm . "' AND wd.due_dt<='" . $to . "') or (r.due_date >='" . $frm . "' AND r.due_date<='" . $to . "'))";
	
	$search_cd.= "and (created_dt >='" . $frm . "' AND created_dt<='" . $to . "')";
	
}
if ($_REQUEST['date_wise'] == "m") {
//echo $_REQUEST['mfrom_dt'];
    $frm = $_REQUEST['mfrom_dt'];
    $search .= "and DATE_FORMAT(wd.recv_dt,'%m-%Y')='" . $frm . "'";
//$search1.="and DATE_FORMAT(start_time,'%m-%Y')='".$frm."'";
//$search3.="and DATE_FORMAT(r.received_date,'%m-%Y')='".$frm."'";
    $search3 .= "and ((DATE_FORMAT(wd.recv_dt,'%m-%Y')='" . $frm . "') or (DATE_FORMAT(r.received_date,'%m-%Y')='" . $frm . "'))";
    $search4 .= "and ((DATE_FORMAT(wd.recv_dt,'%m-%Y')='" . $frm . "') or (DATE_FORMAT(r.due_date,'%m-%Y')='" . $frm . "'))";
	$search_cm.= "and (DATE_FORMAT(created_dt,'%m-%Y')='" . $frm . "')";

}
if ($_REQUEST['date_wise'] == "y") {
    $frm = $_REQUEST['yfrom_dt'];
    $to = $_REQUEST['yto_dt'];
    $search .= "and DATE_FORMAT(recv_dt,'%Y')='" . $frm . "'";
    $search_cy .= "and (DATE_FORMAT(created_dt,'%Y')='" . $frm . "')";

//$search1.="and DATE_FORMAT(start_time,'%Y')='".$frm."'";
//$search3.="and DATE_FORMAT(r.received_date,'%Y')='".$frm."'";
    $search3 .= "and ((DATE_FORMAT(wd.recv_dt,'%Y')='" . $frm . "') or (DATE_FORMAT(r.received_date,'%Y')='" . $frm . "'))";
    $search4 .= "and ((DATE_FORMAT(wd.due_dt,'%Y')='" . $frm . "') or (DATE_FORMAT(r.due_date,'%Y')='" . $frm . "'))";
   
}
if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
{
 $search3.="AND wd.created_dt >='2020-03-01'";
 $search4.="AND wd.created_dt >='2020-03-01'";

}

}
?>



					 <?php
					 
						if($_SESSION['user_level']==5) 
						{
							$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_SESSION['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_SESSION['ocust_id']."'))  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']." ";
														
							$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_SESSION['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_SESSION['ocust_id']."')) AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']."  ";
						}
						else if($_SESSION['user_level']==6) 
						{
							$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_SESSION['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_SESSION['ocust_id']."')) AND (wd.department_id = '".$_SESSION['department_id']."' OR FIND_IN_SET(wd.department_id,'".$_SESSION['odepartment_id']."'))  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']." ";
														
							$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_SESSION['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_SESSION['ocust_id']."')) AND (wd.department_id = '".$_SESSION['department_id']."' OR FIND_IN_SET(wd.department_id,'".$_SESSION['odepartment_id']."')) AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']."  ";
						}
						else
						{
							$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3 ".$_SESSION['inpt_status_qry']."  ";
														
							$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']."  ";	
						}
														/* 
                                                           $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3 ".$_SESSION['inpt_status_qry']."  ";
															$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  ".$_SESSION['inpt_status_qry']."  ";	 */
	$customers = $db->query($cnt_query) or ($db->error);
													$page_count = $customers->fetch_assoc();
													$total_pages = $page_count['num'];
													
													$articles = $db->query($query) or ($db->error);	
                                                     $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   
													
													

												
                                                    ?>   
													<div class="page-header">
                                                            <h1>WIP Report <b style="color:red;font-size:14px;"> - Total count :<?php echo $total_pages;?></b></h1> 				

                                                        </div>	
										<table width="100%" border="1" cellspacing="0" cellpadding="0">
                                                             <thead>
                                                            <th>Client </th>
                                                            <th>Journal Name</th>
                                                            <th>FileName</th>
															<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                            <th>Stage</th>
															 <th>Vol No/Issue No</th>
															<?php 
															}
															?>
                                                            <th>Received Date</th>
															<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                             <th>Due Date</th>
															<?php 
															}
															?>
                                                            <th>Target Due Date</th>
															<?php 
															if($_REQUEST['dep_id']=='1' || $_REQUEST['dep_id']=='2')
															{
															?>
                                                              <th>Alloted to</th>
                                                              <th>Alloted Due date</th>
															<?php 
															}
															?>
                                                            <th>Status</th>
                                                            <th>Graphics</th>
                                                            <th>Figures Count</th>
                                                            <th>Manuscript Count</th>
															</thead>
                                                            <tbody>
                                                                <?php
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
                                                                   while ($row_history = $articles->fetch_assoc()) {
																		
																		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row_history['stage']);
																		$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row_history['stage']);
                                                                        //print_r($row_history);
																		
																		$trans_sql = "SELECT u.full_name,t.current_status,t.completion_status,t.comments,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row_history['id']."' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row_history['stage']."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$row_history['id']."' ORDER BY `id` DESC limit 1))  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
																			$chk_transaction = $db->query($trans_sql); 
																			$transaction_toatl_count=$chk_transaction->num_rows;
																			$trans_res = $chk_transaction->fetch_assoc();	
																			$assign_comments=mysqli_query($db,"select * from inw_transactions where project_id='".$row_history['id']."' and dept='14' and completion_status=6 order by id desc limit 1");
																			$num_acount=mysqli_num_rows($assign_comments);
																			$res_ac=mysqli_fetch_array($assign_comments);
																			
															if($_REQUEST['dep_id']=='1' || $_REQUEST['dep_id']=='2')
															{
															$assign_cepe=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$row_history['assigned_user_id']."'"));
															}				

		if($row_history['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($row_history['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row_history['recv_dt'])));
			$manuscript_count = $row_history['manuscript_count'];
		}
		else
		{
			if($stage_r=="REV")
			{
				$rev_sql = "SELECT *  FROM `inw_revisions_dtl` WHERE `inw_id` = '".$row_history['id']."' ORDER BY r_id DESC LIMIT 1";
				$rev_transaction = $db->query($rev_sql); 
				$rev_res = $rev_transaction->fetch_assoc();	
				$due_dt = (date('d-m-Y',strtotime($rev_res['due_date'])));
				$recv_dt = (date('d-m-Y',strtotime($rev_res['received_date'])));
				$manuscript_count = $rev_res['correction_pages'];
			}
			else
			{
				
				$vol_sql = "SELECT *  FROM `inw_volume_issues` WHERE `vi_id` = '".$row_history['v_issue_id']."'";
				$vol_transaction = $db->query($vol_sql); 
				$vol_res = $vol_transaction->fetch_assoc();	
				
				$due_dt = (date('d-m-Y',strtotime($vol_res['due_date'])));
				$recv_dt = (date('d-m-Y',strtotime($vol_res['received_date'])));
				$manuscript_count = $vol_res['correction_pages'];
			}
		}
		$tr_class = '';
		
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		if($row_history['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row_history['status'] == 'Query') || ($row_history['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $Due_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		
                                                                        ?>
                                                                        <tr class="<?php echo $tr_class;?>">
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																				<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
																				<td><?php echo $row_history['stage']; ?></td>
																				<td><?php echo $row_history['volume_folder']; ?></td>
															<?php 
															}
															?>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																				
																			<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
															<?php 
															}
															?>
                                                                                <td><?php echo date('d M Y', strtotime($row_history['target_due_dt'])) ?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															<?php 
															}
															?>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>

																				
																				<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
															<?php 
															}
															?>
                                                                                <td>-</td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															<?php 
															}
															?>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>

																				
																				<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
															<?php 
															}
															?>
																				<td>-</td>
                                                                                <?php
                                                                            }
																			else {
                                                                                ?>
																				 <?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															<?php 
															}
															?>
                                                                                <td><?php echo date('d M Y', strtotime($row_history['received_date'])) ?></td>

																				<?php 
															if(($_REQUEST['dep_id']!=1) && ($_REQUEST['dep_id']!=2))
															{
															?>
                                                                                <td><?php echo date('d M Y', strtotime($row_history['due_date'])) ?></td>
															<?php 
															}
															?>
																				<td>-</td>
                                                                                <?php
                                                                            }
                                                                            ?>
																			<?php 
															if($_REQUEST['dep_id']=='1' || $_REQUEST['dep_id']=='2')
															{
															?>
															<td><?php echo $assign_cepe['full_name'];?></td>
															<td><?php echo date('d M Y', strtotime($row_history['ce_pe_due_date'])) ?></td>
															<?php 
															}
															?>
                                                                             <td>
                                                                                <span class="label label-sm label-success"><?php echo $row_history['status']; ?></span>
        <?php
        if ($transaction_toatl_count > 0) {
            echo '<span class="label label-sm label-info">' . $trans_res['full_name'] . '</span>|<span class="label label-sm label-warning">' . $trans_res['current_status'] . '</span>';
        }
		if($num_acount>0 && $res_ac['comments']!='') 
		{
            echo '|<span class="label label-sm label-danger">' . $res_ac['comments'] . '</span>';
        }
		elseif($num_acount>0 && $res_ac['comments']=='') 
		{
			echo '|<span class="label label-sm label-danger">No Comments</span>';
		}
        ?>
                                                                            </td>
                                                                                <td><?php if ($row_history['fig_complete_status'] == '1') { echo "Yes";}else{ echo 'No';}?></td>
																				<td><?php echo $row_history['fig'];?></td>
                                                                                <td><?php echo $row_history['manuscript_count'];?></td>
                                                                        </tr>
                                                                        <?php
                                                                    }
                                                                }
																	
																	
																
															
                                                                ?>
                                                         
														
													
				  
				     </tbody>
                                                        </table>


</body>
</html>

<?php 
include ('dbc.php'); 
?>
<div class="container table-responsive custom-container p-4 mt-3"          style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">

<?php 
	$start_date=$_REQUEST['sdt'];
	$send_date=$_REQUEST['dt'];
	$cust_id=$_REQUEST['cust_id'];
	$stage=$_REQUEST['stage'];

	$searchc='';
	$search = "";
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $search .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $search .= " ";
}

if($stage=='FPALL')
{
$search .= "and dh.stage LIKE '%FP%'";

$sent_date .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(sent_date,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='REVALL')
{
	$search .= "and dh.stage LIKE '%REV%'";

$sent_date .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(sent_date,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."'";

}
elseif($stage=='FP')
{
	$search .= "and dh.stage LIKE '%FP%'";

$sent_date .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='REV')
{
	$search .= "and dh.stage LIKE '%REV%'";

$sent_date .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";

}

if($stage=='FP' || $stage=='REV' || $stage=='FPALL' || $stage=='REVALL')
{


  $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id    $sent_date  $search";

$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  $sent_date $search ";	
															
													$customers = $db->query($cnt_query) or ($db->error);
													$page_count = $customers->fetch_assoc();
													$total_pages = $page_count['num'];
													
													if($_SESSION['user_level']==5) 
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`due_dt` asc";
													}
													else
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`target_due_dt` asc";
													}
													
													if($_REQUEST['start'])
													{
													$start=$_REQUEST['start'];	
													}
													else
													{
													$start=0;
													}
										if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
										{
													$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)
										}
										else
										{
										$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&from_dt=".$_REQUEST['from_dt']."&to_dt=".$_REQUEST['to_dt']."&mfrom_dt=".$_REQUEST['mfrom_dt']."&yfrom_dt=".$_REQUEST['yfrom_dt']."&date_wise=".$_REQUEST['date_wise']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)	
										}
													$limit =1000; //how many items to show per page
													
													$query = $query. $order_by." LIMIT $start, $limit";  
													//echo $query;
													$articles = $db->query($query) or ($db->error);	
                                                    $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   

?>		
  <div class="row">
            <div class="col-5 date mb-2">
              Date:
              <span style="color: #4648b8; font-weight: 600"><?php echo date('d-m-Y',strtotime($send_date));?></span>
            </div>
            <div class="col-7">
              Stage:
              <span style="color: #4648b8; font-weight: 600"> <?php echo $stage;?></span>
            </div>
          </div>
          <table id="table" class="table table-striped custtable table-primary">

               <thead>
              <tr>
                <th data-sortable="true" scope="col" class="thcolor">Client</th>
                <th data-sortable="true" scope="col" class="thcolor">Journal Name</th>
                <th data-sortable="true" scope="col" class="thcolor">File Name</th>
                <th data-sortable="true" scope="col" class="thcolor">Stage</th>
                <th data-sortable="true" scope="col" class="thcolor">VolNo/Issue No</th>
                <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Status</th>
                <th class="thcolor">Graphics</th>
                <th class="thcolor">Figure Count</th>
                <th class="thcolor">Manuscript Count</th>
                <th class="thcolor">Instructions</th>
                <th class="thcolor">Job Card</th>
              </tr>
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
		$sent_dateTimestamp2 = strtotime($due_dt); 
		if($row_history['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row_history['status'] == 'Query') || ($row_history['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $sent_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		
                                                                        ?>
                                                                        <tr class="monthlytablebody">
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																			
																				<td><?php echo $row_history['stage']; ?></td>
																				<td><?php echo $row_history['volume_folder']; ?></td>
														
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														            <td><?php echo date('d M Y', strtotime($row_history['target_due_dt'])) ?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>

																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td>-</td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														
																				<td>-</td>
                                                                                <?php
                                                                            }
																			else {
                                                                                ?>
																				
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															
                                                                                <td><?php echo date('d M Y', strtotime($row_history['received_date'])) ?></td>

																
                                                                                <td><?php echo date('d M Y', strtotime($row_history['due_date'])) ?></td>
															
																				<td>-</td>
                                                                                <?php
                                                                            }
                                                                            ?>
																		
															
                                                                                <td><?php if ($row_history['fig_complete_status'] == '1') { echo "Yes";}else{ echo 'No';}?></td>
                                                                                <td><?php echo $row_history['fig'];?></td>
                                                                                <td><?php echo $row_history['manuscript_count'];?></td>
                                                                                <td><a href="send_instructions.php?id=<?php echo $row_history['id'] ?>" target="_blank" class="btn btn-info">Send</a></td>
                                                                                <td> <a title="Article history"  href="job_card.php?id=<?PHP echo $row_history['id']; ?>" target="_blank">   <button class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i>
					</button></a></td>

                                                                        </tr>
                                                                        <?php
                                                                        //}
                                                                        $i++;
                                                                    }
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
													<?php 
}
$searchcus='';
if($stage=='DALL' || $stage=='TALL')
{

	if($stage=='DALL')
{
	$search_fp .= "and dh.stage LIKE '%FP%'";
	
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $searchcus .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchcus .= " ";
}
	$sent_date_fp .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='TALL')
{
	$search_fp .= "and dh.stage LIKE '%FP%'";
	
	
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $searchcus .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchcus .= " ";
}
	
	$sent_date_fp .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and DATE_FORMAT(sent_date,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."'";	
}
  $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id    $sent_date_fp  $search_fp $searchcus";

$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  $sent_date_fp $search_fp $searchcus ";	
															
													$customers = $db->query($cnt_query) or ($db->error);
													$page_count = $customers->fetch_assoc();
													$total_pages = $page_count['num'];
													
													if($_SESSION['user_level']==5) 
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`due_dt` asc";
													}
													else
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`target_due_dt` asc";
													}
													
													if($_REQUEST['start'])
													{
													$start=$_REQUEST['start'];	
													}
													else
													{
													$start=0;
													}
										if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
										{
													$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)
										}
										else
										{
										$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&from_dt=".$_REQUEST['from_dt']."&to_dt=".$_REQUEST['to_dt']."&mfrom_dt=".$_REQUEST['mfrom_dt']."&yfrom_dt=".$_REQUEST['yfrom_dt']."&date_wise=".$_REQUEST['date_wise']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)	
										}
													$limit =1000; //how many items to show per page
													
													$query = $query. $order_by." LIMIT $start, $limit";  
													//echo $query;
													$articles = $db->query($query) or ($db->error);	
                                                    $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   

?>		
 <div class="row">
            <div class="col-5 date mb-2">
              Date:
              <span style="color: #4648b8; font-weight: 600"><?php echo date('d-m-Y',strtotime($send_date));?></span>
            </div>
            <div class="col-7">
              Stage:
              <span style="color: #4648b8; font-weight: 600"> <?php echo "FP";?></span>
            </div>
          </div>
          <table id="table" class="table table-striped custtable table-primary">
                 <thead>                                             <tr>
                <th data-sortable="true" scope="col" class="thcolor">Client</th>
                <th data-sortable="true" scope="col" class="thcolor">Journal Name</th>
                <th data-sortable="true" scope="col" class="thcolor">File Name</th>
                <th data-sortable="true" scope="col" class="thcolor">Stage</th>
                <th data-sortable="true" scope="col" class="thcolor">VolNo/Issue No</th>
                <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Status</th>
                <th class="thcolor">Graphics</th>
                <th class="thcolor">Figure Count</th>
                <th class="thcolor">Manuscript Count</th>
                <th class="thcolor">Instructions</th>
                <th class="thcolor">Job Card</th>
              </tr>
			   <thead>
                                                            <tbody>
                                                                <?php
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
                                                                    while ($row_history = $articles->fetch_assoc()) {
																		
																		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row_history['stage']);
																		$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row_history['stage']);
                                                                        //print_r($row_history);
																		
																	

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
		$sent_dateTimestamp2 = strtotime($due_dt); 
		if($row_history['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row_history['status'] == 'Query') || ($row_history['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $sent_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		
                                                                        ?>
                                                                        <tr class="monthlytablebody">
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																			
																				<td><?php echo $row_history['stage']; ?></td>
																				<td><?php echo $row_history['volume_folder']; ?></td>
														
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														            <td><?php echo date('d M Y', strtotime($row_history['target_due_dt'])) ?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>

																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td>-</td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														
																				<td>-</td>
                                                                                <?php
                                                                            }
																			else {
                                                                                ?>
																				
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															
                                                                                <td><?php echo date('d M Y', strtotime($row_history['received_date'])) ?></td>

																
                                                                                <td><?php echo date('d M Y', strtotime($row_history['due_date'])) ?></td>
															
																				<td>-</td>
                                                                                <?php
                                                                            }
                                                                            ?>
																		
														
                                                                                <td><?php if ($row_history['fig_complete_status'] == '1') { echo "Yes";}else{ echo 'No';}?></td>
                                                                                <td><?php echo $row_history['fig'];?></td>
                                                                                <td><?php echo $row_history['manuscript_count'];?></td>
                                                                                <td><a href="send_instructions.php?id=<?php echo $row_history['id'] ?>" target="_blank" class="btn btn-info">Send</a></td>
                                                                                <td> <a title="Article history"  href="job_card.php?id=<?PHP echo $row_history['id']; ?>" target="_blank">   <button class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i>
					</button></a></td>

                                                                        </tr>
                                                                        <?php
                                                                        //}
                                                                        $i++;
                                                                    }
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>

<?php 

if($stage=='DALL')
{
	$search_rev .= "and dh.stage LIKE '%REV%'";
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $searchcus .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchcus .= " ";
}
	$sent_date_rev .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='TALL')
{
	$search_rev .= "and dh.stage LIKE '%REV%'";
	
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $searchcus .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchcus .= " ";
}
	$sent_date_rev .= "and DATE_FORMAT(sent_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and DATE_FORMAT(sent_date,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."'";	
}

  $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id    $sent_date_rev  $search_rev $searchcus";

$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM inw_dispatch_history as dh LEFT JOIN `inw_inward_dtl` as wd on wd.id=dh.a_id LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  $sent_date_rev  $search_rev $searchcus";	
															
													$customers = $db->query($cnt_query) or ($db->error);
													$page_count = $customers->fetch_assoc();
													$total_pages = $page_count['num'];
													
													if($_SESSION['user_level']==5) 
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`due_dt` asc";
													}
													else
													{
													$order_by = "ORDER BY FIELD(highpriority, 1) desc,`target_due_dt` asc";
													}
													
													if($_REQUEST['start'])
													{
													$start=$_REQUEST['start'];	
													}
													else
													{
													$start=0;
													}
										if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
										{
													$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)
										}
										else
										{
										$filePath = "master_status_report.php?page=1&radio=".$_REQUEST['radio']."&cust_id=".$_REQUEST['cust_id']."&j_id=".$_REQUEST['j_id']."&stage_id=".$_REQUEST['stage_id']."&from_dt=".$_REQUEST['from_dt']."&to_dt=".$_REQUEST['to_dt']."&mfrom_dt=".$_REQUEST['mfrom_dt']."&yfrom_dt=".$_REQUEST['yfrom_dt']."&date_wise=".$_REQUEST['date_wise']."&dep_id=".$_REQUEST['dep_id']."&dp_platform=".$_REQUEST['dp_platform'].""
													; 	//your file name  (the name of this file)	
										}
													$limit =1000; //how many items to show per page
													
													$query = $query. $order_by." LIMIT $start, $limit";  
													//echo $query;
													$articles = $db->query($query) or ($db->error);	
                                                    $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   

?>		
<div class="row">
            <div class="col-5 date mb-2">
              Date:
              <span style="color: #4648b8; font-weight: 600"><?php echo date('d-m-Y',strtotime($send_date));?></span>
            </div>
            <div class="col-7">
              Stage:
              <span style="color: #4648b8; font-weight: 600"> <?php echo "REV";?></span>
            </div>
          </div>
          <table id="table" class="table table-striped custtable table-primary">
                 <thead>                                             <tr>
                <th data-sortable="true" scope="col" class="thcolor">Client</th>
                <th data-sortable="true" scope="col" class="thcolor">Journal Name</th>
                <th data-sortable="true" scope="col" class="thcolor">File Name</th>
                <th data-sortable="true" scope="col" class="thcolor">Stage</th>
                <th data-sortable="true" scope="col" class="thcolor">VolNo/Issue No</th>
                <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
                <th data-sortable="true" scope="col" class="thcolor">Status</th>
                <th class="thcolor">Graphics</th>
                <th class="thcolor">Figure Count</th>
                <th class="thcolor">Manuscript Count</th>
                <th class="thcolor">Instructions</th>
                <th class="thcolor">Job Card</th>
              </tr>
			   <thead>
                                                            <tbody>
                                                                <?php
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
                                                                    while ($row_history = $articles->fetch_assoc()) {
																		
																		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row_history['stage']);
																		$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row_history['stage']);
                                                                        //print_r($row_history);
																		
																	

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
		$sent_dateTimestamp2 = strtotime($due_dt); 
		if($row_history['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row_history['status'] == 'Query') || ($row_history['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		elseif($current_dateTimestamp1 > $sent_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		
                                                                        ?>
                                                                        <tr class="monthlytablebody">
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																			
																				<td><?php echo $row_history['stage']; ?></td>
																				<td><?php echo $row_history['volume_folder']; ?></td>
														
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														            <td><?php echo date('d M Y', strtotime($row_history['target_due_dt'])) ?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>

																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td>-</td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
														
																				<td>-</td>
                                                                                <?php
                                                                            }
																			else {
                                                                                ?>
																				
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
															
                                                                                <td><?php echo date('d M Y', strtotime($row_history['received_date'])) ?></td>

																
                                                                                <td><?php echo date('d M Y', strtotime($row_history['due_date'])) ?></td>
															
																				<td>-</td>
                                                                                <?php
                                                                            }
                                                                            ?>
																		
														
                                                                                <td><?php if ($row_history['fig_complete_status'] == '1') { echo "Yes";}else{ echo 'No';}?></td>
                                                                                <td><?php echo $row_history['fig'];?></td>
                                                                                <td><?php echo $row_history['manuscript_count'];?></td>
                                                                                <td><a href="send_instructions.php?id=<?php echo $row_history['id'] ?>" target="_blank" class="btn btn-info">Send</a></td>
                                                                                <td> <a title="Article history"  href="job_card.php?id=<?PHP echo $row_history['id']; ?>" target="_blank">   <button class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i>
					</button></a></td>

                                                                        </tr>
                                                                        <?php
                                                                        //}
                                                                        $i++;
                                                                    }
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>

<?php

if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
	$searchc .= "and c.id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchc .= " ";
}
?>
<div class="row">
            <div class="col-5 date mb-2">
              Date:
              <span style="color: #4648b8; font-weight: 600"><?php echo date('d-m-Y',strtotime($send_date));?></span>
            </div>
            <div class="col-7">
              Stage:
              <span style="color: #4648b8; font-weight: 600"> <?php echo "ISSUE";?></span>
            </div>
          </div>
          <table id="dynamic-table" class="table table-striped custtable table-primary">
                 <thead>    
				 <tr>
				 <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                <th>Customer</th>
                    <th>Journal</th>
                    <th>Issue Folder Name</th>
                    <th>Starting Page Number</th>
                    <th>Created Date</th>
                    <th>Due Date</th>
                    <th>Stage</th>
                    <th>#Articles Done</th>
                    <th>#Articles Pending</th>
              </tr>
			   <thead>
               
                <tbody>
                  <?php
		if($stage=='DALL')
		{
			$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_issue_dispatch_history as vdh LEFT JOIN inw_volume_issues as vi on  vdh.`vi_id` =vi.`vi_id`  LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id  AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($send_date))."' $searchc  ORDER BY vi.vi_id ASC;";

		}
		elseif($stage=='TALL')
		{
			$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_issue_dispatch_history as vdh LEFT JOIN inw_volume_issues as vi on  vdh.`vi_id` =vi.`vi_id`  LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id  AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($start_date))."' AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."' $searchc  ORDER BY vi.vi_id ASC;";

		}	  	
				
			
			
		
				
			

	
		
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		if($row['received_date']!= "")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['rcv_dt'])));
		}
		
		?>
                    <tr class="monthlytablebody">
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><? echo $row['cust_name']; ?></td>
                    <td><? echo $row['j_code']; ?></td>
                    <td><? echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></td>
                    <td><? echo $row['starting_page_number']; ?></td>
                    <td><? echo $recv_dt; ?></td>
                    <td><? echo $due_dt; ?></td>
                    <td><? echo $row['stage']; ?></td>
                    <td><? echo $row['ccount']; ?></td>
                    <td><? echo $row['pcount']; ?></td>
                   
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
													<?php 
}
elseif($stage=='ISSUE' || $stage=='ISSUEALL')
{

	
	if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
		$searchc .= "and c.id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $searchc .= " ";
}

?>
<div class="row">
            <div class="col-5 date mb-2">
              Date:
              <span style="color: #4648b8; font-weight: 600"><?php echo date('d-m-Y',strtotime($send_date));?></span>
            </div>
            <div class="col-7">
              Stage:
              <span style="color: #4648b8; font-weight: 600"> <?php echo 'ISSUE';?></span>
            </div>
          </div>
          <table id="dynamic-table" class="table table-striped custtable table-primary">
                 <thead>    
				 <tr>
				 <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                <th>Customer</th>
                    <th>Journal</th>
                    <th>Issue Folder Name</th>
                    <th>Starting Page Number</th>
                    <th>Created Date</th>
                    <th>Due Date</th>
                    <th>Stage</th>
                    <th>#Articles Done</th>
                    <th>#Articles Pending</th>
              </tr>
			   <thead>
               
                <tbody>
                  <?php
			if($stage=='ISSUE')
			{	  	
					$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_issue_dispatch_history as vdh LEFT JOIN inw_volume_issues as vi on  vdh.`vi_id` =vi.`vi_id` LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id  AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($send_date))."' $searchc  ORDER BY vi.vi_id ASC;";

			}
			elseif($stage=='ISSUEALL')
			{
				$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_issue_dispatch_history as vdh LEFT JOIN inw_volume_issues as vi on  vdh.`vi_id` =vi.`vi_id` LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id  AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($start_date))."' AND  DATE_FORMAT(vdh.sent_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."' $searchc  ORDER BY vi.vi_id ASC;";	
			}	
		
				
			

	
		
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		if($row['received_date']!= "")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['rcv_dt'])));
		}
		
		?>
                    <tr class="monthlytablebody">
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><? echo $row['cust_name']; ?></td>
                    <td><? echo $row['j_code']; ?></td>
                    <td><? echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></td>
                    <td><? echo $row['starting_page_number']; ?></td>
                    <td><? echo $recv_dt; ?></td>
                    <td><? echo $due_dt; ?></td>
                    <td><? echo $row['stage']; ?></td>
                    <td><? echo $row['ccount']; ?></td>
                    <td><? echo $row['pcount']; ?></td>
                   
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
<?php 
}
?>
</div>
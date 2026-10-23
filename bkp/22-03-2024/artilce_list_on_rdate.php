<?php 
include ('dbc.php'); 
?>
<div class="container table-responsive custom-container p-4 mt-3" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
<?php 
	$send_date=$_REQUEST['dt'];
	$cust_id=$_REQUEST['cust_id'];
	$stage=$_REQUEST['stage'];
	
	$search = "";
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id'] != "all") {
    $search .= "and wd.cust_id='" . $cust_id. "' ";
}
if ($_REQUEST['cust_id'] != "" && $_REQUEST['cust_id']== "all") {
    $search .= " ";
}
if ($_REQUEST['stage'] != "") {
    $search .= "and wd.stage LIKE '%" . $stage. "%'";
}

if($stage=='FP')
{
$due_date .= "and DATE_FORMAT(recv_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='REV')
{
$due_date .= "and DATE_FORMAT(received_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";

}
if($stage=='FP' || $stage=='REV')
{


 $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Client Review','Completed')  $due_date  $search";

$cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.status NOT IN ('Client Review','Completed') $due_date $search ";	
															
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
																		
		// 																$trans_sql = "SELECT u.full_name,t.current_status,t.completion_status,t.comments,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row_history['id']."' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row_history['stage']."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$row_history['id']."' ORDER BY `id` DESC limit 1))  ORDER BY t.`id` DESC limit 1;";
		// //echo $trans_sql.'<br>';		
		// 																	$chk_transaction = $db->query($trans_sql); 
		// 																	$transaction_toatl_count=$chk_transaction->num_rows;
		// 																	$trans_res = $chk_transaction->fetch_assoc();	
		// 																	$assign_comments=mysqli_query($db,"select * from inw_transactions where project_id='".$row_history['id']."' and dept='14' and completion_status=6 AND stage = '".$row_history['stage']."' order by id desc limit 1");
		// 																	$num_acount=mysqli_num_rows($assign_comments);
		// 																	$res_ac=mysqli_fetch_array($assign_comments);
																			
														
		// 													$assign_cepe=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$row_history['assigned_user_id']."'"));

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
																		
															<!-- <td><?php echo $assign_cepe['full_name'];?></td>
															<td><?php echo date('d M Y', strtotime($row_history['ce_pe_due_date'])) ?></td>
														
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
		<?php if(!empty($row_history['aq_status'])){ echo '|<span class="label label-sm label-primary">'.$row_history['aq_status'].'</span>'; } ?>
                                                                            </td> -->
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
elseif($stage=='ISSUE')
{
?>
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
                </thead>
                <tbody>
                  <?php
				  	
					$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed') AND  DATE_FORMAT(vi.received_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($send_date))."'  ORDER BY vi.vi_id ASC;";

					
		
				
			

	
		
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
?></div>
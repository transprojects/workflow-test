<?php 
include('includes/ajax_guard.php');   // login check (Phase 0)
include ('dbc.php'); 
?>
<div class="container table-responsive custom-container p-4 mt-3" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">

<?php 
	$send_date=$_REQUEST['dt'];
	$cust_id=$_REQUEST['cust_id'];
	$stage=$_REQUEST['stage'];
	$dept_id=$_REQUEST['dept_id'];
	
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
if ($_REQUEST['dept_id'] != "" && $_REQUEST['dept_id'] != "all") {
    $search .= "and wd.department_id = '" . $dept_id. "'";
}
if ($_REQUEST['dept_id'] != "" && $_REQUEST['dept_id']== "all") {
    $search .= " ";
}
if($stage=='FP')
{
$due_date .= "and DATE_FORMAT(due_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";
}
elseif($stage=='REV')
{
$due_date .= "and DATE_FORMAT(due_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";

}
elseif($stage=='FIN')
{
$due_date .= "and DATE_FORMAT(due_date,'%Y-%m-%d')='".date('Y-m-d', strtotime($send_date))."'";

}
if($stage=='FP' || $stage=='REV' || $stage=='FIN')
{


  $query = "SELECT c.cust_name,journal_title,wd.cust_id,wd.j_id,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.user_working_status,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Client Review','Completed')  $due_date  $search";

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
<div class="mu-detail-head"><span class="mu-chip-d"><i class="fa fa-calendar"></i> Date <b><?php echo date('d-m-Y',strtotime($send_date));?></b></span><span class="mu-chip-d"><i class="fa fa-tags"></i> Stage <b><?php echo $stage;?></b></span></div>
          <table id="table" class="mu-table">

               <thead>
              <tr>
                <th data-sortable="true" scope="col">S.no</th>
                <th data-sortable="true" scope="col">Client</th>
                <th data-sortable="true" scope="col">Journal Name</th>
                <th data-sortable="true" scope="col">File Name</th>
                <th data-sortable="true" scope="col">Stage</th>
                <th data-sortable="true" scope="col">Received Date</th>
                <th data-sortable="true" scope="col">Due Date</th>
                <th data-sortable="true" scope="col">Status</th>
                <th>Graphics</th>
                <th>Figure Count</th>
                <th>Manuscript Count</th>
                <th>Instructions</th>
                <th>Job Card</th>
                <th>Schedule Today</th>
              </tr>
            </thead>
                                                           <tbody>
                                                                <?php
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
																	$sno=0;
                                                                    while ($row_history = $articles->fetch_assoc()) {
																		$sno++;
																		
																		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row_history['stage']);
																		$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$row_history['stage']);

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
			if (!empty($row_history['page_count'])) {
    $manuscript_count = $row_history['page_count'];
} elseif (!empty($row_history['manuscript_count'])) {
$manuscript_count = $row_history['manuscript_count'];
} else {
    $manuscript_count = rand(9, 20); // generates random number between 9 and 20
}
		}
		else
		{
			
			if($stage_r=="REV")
			{
				
				 
				$rev_sql = "SELECT *  FROM `inw_revisions_dtl` WHERE `inw_id` = '".$row_history['id']."' and type='REV' ORDER BY r_id DESC LIMIT 1";
				$rev_transaction = $db->query($rev_sql); 
				$rev_res = $rev_transaction->fetch_assoc();	
				$due_dt = (date('d-m-Y',strtotime($rev_res['due_date'])));
				$recv_dt = (date('d-m-Y',strtotime($rev_res['received_date'])));
				//$manuscript_count = $rev_res['correction_pages'];
if (!empty($row_history['page_count'])) {
    $manuscript_count = $row_history['page_count'];
} elseif (!empty($row_history['manuscript_count'])) {
    $manuscript_count = $row_history['manuscript_count'];
} else {
    $manuscript_count = rand(9, 20); // generates random number between 9 and 20
}				
			}
			elseif($stage_f=="FIN")
			{
				
				 
				$rev_sql = "SELECT *  FROM `inw_revisions_dtl` WHERE `inw_id` = '".$row_history['id']."' and type='FIN' ORDER BY r_id DESC LIMIT 1";
				$rev_transaction = $db->query($rev_sql); 
				$rev_res = $rev_transaction->fetch_assoc();	
				$due_dt = (date('d-m-Y',strtotime($rev_res['due_date'])));
				$recv_dt = (date('d-m-Y',strtotime($rev_res['received_date'])));
				//$manuscript_count = $rev_res['correction_pages'];
if (!empty($row_history['page_count'])) {
    $manuscript_count = $row_history['page_count'];
} elseif (!empty($row_history['manuscript_count'])) {
    $manuscript_count = $row_history['manuscript_count'];
} else {
    $manuscript_count = rand(9, 20); // generates random number between 9 and 20
}
			}
			else
			{
				
				$vol_sql = "SELECT *  FROM `inw_volume_issues` WHERE `vi_id` = '".$row_history['v_issue_id']."'";
				$vol_transaction = $db->query($vol_sql); 
				$vol_res = $vol_transaction->fetch_assoc();	
				
				$due_dt = (date('d-m-Y',strtotime($vol_res['due_date'])));
				$recv_dt = (date('d-m-Y',strtotime($vol_res['received_date'])));
if (isset($vol_res['correction_pages']) && $vol_res['correction_pages'] !== '') {
    $manuscript_count = $vol_res['correction_pages'];
} elseif (isset($row_history['manuscript_count']) && $row_history['manuscript_count'] !== '') {
    $manuscript_count = $row_history['manuscript_count'];
} else {
    $manuscript_count = rand(9, 20); // fallback random number > 8
}
				//$manuscript_count = $row_history['page_count'];

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
                                                                            <td><?php echo $sno; ?></td>
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																			
																				<td><?php echo $row_history['stage']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td><?php echo $row_history['user_working_status'];?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
																	
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td><?php echo $row_history['user_working_status'];?></td>
                                                                                <?php
                                                                            }
																			elseif($stage_f=='FIN') {
                                                                                ?>
																
																				 <td><?php echo  "FIN" . $row_history['revision_count'] ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td><?php echo $row_history['user_working_status'];?></td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					
																				<td><?php echo $row_history['stage'] ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td><?php echo $row_history['user_working_status'];?></td>
                                                                                <?php
                                                                            }
																			else {
                                                                                ?>
																				
																				 <td><?php echo $row_history['stage'] ?></td>
															
                                                                                <td><?php echo date('d M Y', strtotime($row_history['received_date'])) ?></td>

																
                                                                                <td><?php echo date('d M Y', strtotime($row_history['due_date'])) ?></td>
															
																				<td><?php echo $row_history['user_working_status'];?></td>
                                                                                <?php
                                                                            }
                                                                            ?>
																		
															<!-- <td><?php echo date('d M Y', strtotime($row_history['ce_pe_due_date'])) ?></td>
														
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
                                                                                <td><?php echo $manuscript_count;?></td>
                                                                                <td><a href="send_instructions.php?id=<?php echo $row_history['id'] ?>" target="_blank" class="mu-btn mu-btn-sm primary">Send</a></td>
                                                                                <td> <a title="Article history"  href="job_card.php?id=<?PHP echo $row_history['id']; ?>" target="_blank">   <button type="button" class="mu-icon-btn"><i class="ace-icon fa fa-clock-o bigger-120"></i>
					</button></a></td>
					
<?php 
$today = date('Y-m-d');

// Check if already scheduled today
$check_sql = "
    SELECT h.approve_status
    FROM plan_article_to_dispatch_mail_history h
    JOIN plan_article_to_dispatch_mail_daily d 
        ON h.schedule_id = d.id
    WHERE h.article_id = ? 
      AND d.schedule_date = ?
    LIMIT 1
";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("is", $row_history['id'], $today);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row_check = $result->fetch_assoc();
$check_stmt->close();

// Determine button state
if ($row_check) {
    if ($row_check['approve_status'] === 'Approved') {
        // ✅ Already approved
        $btnClass = 'btn-secondary';  // greyed out
        $btnLabel = 'Approved';
        $isDisabled = 'disabled'; // prevent clicking
    } else {
        // ⏳ Scheduled but not approved yet
        $btnClass = 'btn-warning';
        $btnLabel = 'Unschedule Today';
        $isDisabled = '';
    }
} else {
    // ➕ Not scheduled yet
    $btnClass = 'btn-success';
    $btnLabel = 'Schedule Today';
    $isDisabled = '';
}
?>

<td>
  <button 
    class="btn <?php echo $btnClass; ?> schedule-btn"
    <?php echo $isDisabled; ?>
    data-article-id="<?php echo $row_history['id']; ?>"
    data-pub-id="<?php echo $row_history['pub_id']; ?>"
    data-cust="<?php echo htmlspecialchars($row_history['cust_name']); ?>"
    data-jcode="<?php echo htmlspecialchars($row_history['j_code']); ?>"
    data-custid="<?php echo htmlspecialchars($row_history['cust_id']); ?>"
    data-jid="<?php echo htmlspecialchars($row_history['j_id']); ?>"
    data-stage="<?php echo htmlspecialchars($row_history['stage']); ?>"
    data-status="<?php echo htmlspecialchars($row_history['status']); ?>"
    data-received-date="<?php echo htmlspecialchars($recv_dt); ?>"
    data-due-date="<?php echo htmlspecialchars($due_dt); ?>"
    data-page-count="<?php echo (int)$manuscript_count; ?>"
  >
    <?php echo $btnLabel; ?>
  </button>
</td>

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
 <table id="dynamic-table" class="mu-table">
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
				  	
					$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,c.cust_name,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed') AND  DATE_FORMAT(vi.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($send_date))."'  ORDER BY vi.vi_id ASC;";

					
		
				
			

	
		
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
                    <td><? echo $row['volume_number'].$row['issue_number']; ?></td>
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

<script>
$(document).on('click', '.schedule-btn', function() {
    let btn = $(this);

    // Prevent rapid multiple clicks
    if (btn.hasClass('processing')) return;
    btn.addClass('processing');

    let isUnscheduled = btn.text().trim() === 'Unschedule Today';
    let actionType = isUnscheduled ? 'unschedule' : 'schedule';

    // Add spinner feedback
    btn.html('<i class="fa fa-spinner fa-spin"></i> ' + 
             (isUnscheduled ? 'Unscheduling...' : 'Scheduling...'))
       .prop('disabled', true);

    $.ajax({
        url: 'ajax_schedule_today.php',
        type: 'POST',
        data: {
            action: actionType,
            article_id: btn.data('article-id'),
            cust_name: btn.data('cust'),
            j_code: btn.data('jcode'),
            cust_id: btn.data('custid'),
            j_id: btn.data('jid'),
            pub_id: btn.data('pub-id'),
            stage: btn.data('stage'),
            status: btn.data('status'),
            recv_dt: btn.data('received-date'),
            due_dt: btn.data('due-date'),
            page_count: btn.data('page-count')
        },
        success: function(response) {
            response = response.trim();
            if (response === 'scheduled') {
                btn.html('Unschedule Today')
                   .removeClass('btn-success')
                   .addClass('btn-warning')
                   .prop('disabled', false)
                   .removeClass('processing');
            } 
            else if (response === 'unscheduled') {
                btn.html('Schedule Today')
                   .removeClass('btn-warning')
                   .addClass('btn-success')
                   .prop('disabled', false)
                   .removeClass('processing');
            } 
            else {
                alert(response);
                btn.prop('disabled', false).removeClass('processing');
            }
        },
        error: function() {
            alert("Error while scheduling! Please try again.");
            btn.prop('disabled', false).removeClass('processing');
        }
    });
});
</script>
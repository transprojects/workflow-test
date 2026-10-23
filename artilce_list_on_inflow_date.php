<?php 
$ag_validate = true;
include('includes/ajax_guard.php');   // login + request check (Phase 0)
include ('dbc.php'); 
?>
<div class="container table-responsive custom-container p-4 mt-3" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
<?php 
	$send_date = $_REQUEST['dt'];
$cust_id = $_REQUEST['cust_id'];
$stage = $_REQUEST['stage'];

$search = "";

// Customer Filter
if (!empty($cust_id) && $cust_id != "all") {
    $search .= " AND wd.cust_id = '" . $cust_id . "' ";
}

// Stage Date Filter
$due_date = "";
if ($stage == 'FP') {
    $due_date .= " AND DATE_FORMAT(wd.created_dt, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($send_date)) . "'";
} elseif ($stage == 'REV' || $stage == 'FIN') {
    $due_date .= " AND DATE_FORMAT(received_date, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($send_date)) . "'";
}

// Main Query Construction
if ($stage == 'FP' || $stage == 'REV' || $stage == 'FIN') {
    // Only apply stage filter for REV or FIN
    $stage_filter = "";
    if ($stage == 'REV' || $stage == 'FIN') {
        $stage_filter = " AND wd.stage LIKE '%" . $stage. "%'";
    }

      $query = "
    SELECT * FROM (
        -- Live DB
        SELECT 
            c.cust_name, j.j_name, wd.pub_id, wd.recv_dt, wd.due_dt, wd.volume_folder, 
            wd.v_issue_id, wd.highpriority, wd.target_due_dt, wd.assigned_user_id, wd.ce_pe_due_date, 
            wd.created_dt, wd.fig, wd.tab, wd.status, wd.approved_by, wd.id, 
            j.j_code, wd.manuscript_count, wd.graphics, wd.fig_complete_status, wd.stage, 
            r.received_date, r.due_date, r.correction_pages, r.revision_count, wd.aq_status, 
            wd.user_working_status, 'Live' AS source
        FROM workflow.inw_inward_dtl AS wd
        LEFT JOIN (
            SELECT inw_id, MAX(r_id) AS max_rid
            FROM workflow.inw_revisions_dtl
            GROUP BY inw_id
        ) AS latest_r ON wd.id = latest_r.inw_id
        LEFT JOIN workflow.inw_revisions_dtl r 
            ON r.r_id = latest_r.max_rid
        JOIN workflow.adm_customer_master c ON wd.cust_id = c.id
        JOIN workflow.adm_journals j ON wd.j_id = j.j_id
        $stage_filter 
        $due_date 
        $search

        UNION ALL

        -- Archive DB
        SELECT 
            c.cust_name, j.j_name, wd.pub_id, wd.recv_dt, wd.due_dt, wd.volume_folder, 
            wd.v_issue_id, wd.highpriority, wd.target_due_dt, wd.assigned_user_id, wd.ce_pe_due_date, 
            wd.created_dt, wd.fig, wd.tab, wd.status, wd.approved_by, wd.id, 
            j.j_code, wd.manuscript_count, wd.graphics, wd.fig_complete_status, wd.stage, 
            r.received_date, r.due_date, r.correction_pages, r.revision_count, wd.aq_status, 
            wd.user_working_status, 'Archive' AS source
        FROM workflow_archive.inw_inward_dtl AS wd
        LEFT JOIN (
            SELECT inw_id, MAX(r_id) AS max_rid
            FROM workflow_archive.inw_revisions_dtl
            GROUP BY inw_id
        ) AS latest_r ON wd.id = latest_r.inw_id
        LEFT JOIN workflow_archive.inw_revisions_dtl r 
            ON r.r_id = latest_r.max_rid
        JOIN workflow_archive.adm_customer_master c ON wd.cust_id = c.id
        JOIN workflow_archive.adm_journals j ON wd.j_id = j.j_id
        $stage_filter 
        $due_date 
        $search
    ) AS combined
";

$cnt_query = "
    SELECT COUNT(*) AS num 
    FROM (
        SELECT wd.id
        FROM workflow.inw_inward_dtl AS wd
        LEFT JOIN (
            SELECT inw_id, MAX(r_id) AS max_rid
            FROM workflow.inw_revisions_dtl
            GROUP BY inw_id
        ) AS rmax ON wd.id = rmax.inw_id
        LEFT JOIN workflow.inw_revisions_dtl AS r 
            ON (wd.id = r.inw_id AND r.r_id = rmax.max_rid)
        JOIN workflow.adm_customer_master AS c ON wd.cust_id = c.id
        JOIN workflow.adm_journals AS j ON wd.j_id = j.j_id
        WHERE wd.status IS NOT NULL
        $stage_filter 
        $due_date 
        $search

        UNION ALL

        SELECT wd.id
        FROM workflow_archive.inw_inward_dtl AS wd
        LEFT JOIN (
            SELECT inw_id, MAX(r_id) AS max_rid
            FROM workflow_archive.inw_revisions_dtl
            GROUP BY inw_id
        ) AS rmax ON wd.id = rmax.inw_id
        LEFT JOIN workflow_archive.inw_revisions_dtl AS r 
            ON (wd.id = r.inw_id AND r.r_id = rmax.max_rid)
        JOIN workflow_archive.adm_customer_master AS c ON wd.cust_id = c.id
        JOIN workflow_archive.adm_journals AS j ON wd.j_id = j.j_id
        WHERE wd.status IS NOT NULL
        $stage_filter 
        $due_date 
        $search
    ) AS total
";



															
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
                <th data-sortable="true" scope="col">Current Stage</th>
                <th data-sortable="true" scope="col">VolNo/Issue No</th>
                <th data-sortable="true" scope="col">Received Date</th>
                <th data-sortable="true" scope="col">Due Date</th>
                <th data-sortable="true" scope="col">Status</th>
                <th>Graphics</th>
                <th>Figure Count</th>
                <th>Manuscript Count</th>
                <th>Instructions</th>
                <th>Job Card</th>
              </tr>
            </thead>
                                                           <tbody>
                                                                <?php
																$s=0;
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
                                                                    while ($row_history = $articles->fetch_assoc()) {
																		$s++;
																		$stage_f=preg_replace("/FIN([0-9]+)/","FIN",$row_history['stage']);
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

if ($row_history['stage'] == "FP") {
    $due_dt = date('d-m-Y', strtotime($row_history['due_dt']));
    $created_dt = date('d-m-Y', strtotime($row_history['created_dt']));
    $manuscript_count = $row_history['manuscript_count'];

} else {

    if ($stage_r == "REV" || $stage_f == "FIN") {

        // ✅ Check both DBs for revisions
        $rev_sql = "
            (SELECT * FROM workflow.inw_revisions_dtl 
             WHERE inw_id = '".$row_history['id']."' 
             ORDER BY r_id DESC LIMIT 1)
            UNION ALL
            (SELECT * FROM workflow_archive.inw_revisions_dtl 
             WHERE inw_id = '".$row_history['id']."' 
             ORDER BY r_id DESC LIMIT 1)
            LIMIT 1
        ";
        $rev_transaction = $db->query($rev_sql); 
        $rev_res = $rev_transaction->fetch_assoc();  

        if ($rev_res) {
            $due_dt = date('d-m-Y', strtotime($rev_res['due_date']));
            $created_dt = date('d-m-Y', strtotime($rev_res['received_date']));
            $manuscript_count = $rev_res['correction_pages'];
        }

    } else {

        // ✅ Check both DBs for volume issues
        $vol_sql = "
            (SELECT * FROM workflow.inw_volume_issues 
             WHERE vi_id = '".$row_history['v_issue_id']."')
            UNION ALL
            (SELECT * FROM workflow_archive.inw_volume_issues 
             WHERE vi_id = '".$row_history['v_issue_id']."')
            LIMIT 1
        ";
        $vol_transaction = $db->query($vol_sql); 
        $vol_res = $vol_transaction->fetch_assoc();  

        if ($vol_res) {
            $due_dt = date('d-m-Y', strtotime($vol_res['due_date']));
            $created_dt = date('d-m-Y', strtotime($vol_res['received_date']));
            $manuscript_count = $vol_res['correction_pages'];
        }
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
																			<td><?php echo $s; ?></td>		
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            
                                                                            <td><?php echo $row_history['pub_id'] ?></td>
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
                                                                                ?>
																			
																				<td><?php echo $row_history['stage']; ?></td>
																				<td><?php echo $row_history['volume_folder']; ?></td>
														
                                                                                <td><?php echo date('d M Y', strtotime($created_dt)) ?></td>
																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td><?php echo $row_history['user_working_statususer_working_status']; ?></td>
                                                                                <?php
                                                                            } elseif($stage_r=='REV') {
                                                                                ?>
																
																				 <td><?php echo  "REV" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($created_dt)) ?></td>

																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td>-</td>
                                                                                <?php
                                                                            }elseif($stage_f=='FIN') {
                                                                                ?>
																
																				 <td><?php echo  "FIN" . $row_history['revision_count'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($created_dt)) ?></td>

																				
																			
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
																				<td>-</td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
                                                                                ?>
																					
																				 <td><?php echo $row_history['stage'] ?></td>
																				 <td><?php echo $row_history['volume_folder']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($created_dt)) ?></td>
																
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
                                                                                <td><a href="send_instructions.php?id=<?php echo $row_history['id'] ?>" target="_blank" class="mu-btn mu-btn-sm primary">Send</a></td>
                                                                                <td> <a title="Article history"  href="jobcard.php?id=<?PHP echo $row_history['id']; ?>" target="_blank">   <button type="button" class="mu-icon-btn"><i class="ace-icon fa fa-clock-o bigger-120"></i>
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
				  	
$query = "
    SELECT * FROM (
        SELECT 
            vi.starting_page_number, 
            vi.stage,
            vi.vi_id,
            r.received_date,
            r.due_date,
            vi.received_date as rcv_dt,
            vi.due_date as due_dt,
            volume_number,
            issue_number,
            c.cust_name,
            j.j_code,
            (SELECT COUNT(id) 
             FROM inw_inward_dtl 
             WHERE v_issue_id = vi.vi_id 
             AND status='Delivery') as ccount,
            (SELECT COUNT(id) 
             FROM inw_inward_dtl 
             WHERE v_issue_id = vi.vi_id 
             AND status!='Delivery') as pcount,
            'workflow' as source
        FROM workflow.inw_volume_issues as vi
        LEFT JOIN workflow.inw_issue_corrections as r 
               ON vi.vi_id = r.vi_id 
              AND r.ic_id = (
                    SELECT ic_id 
                    FROM workflow.inw_issue_corrections 
                    WHERE vi_id = r.vi_id 
                    ORDER BY ic_id DESC 
                    LIMIT 1
                )
        JOIN workflow.adm_customer_master as c 
              ON vi.cust_id = c.id
        JOIN workflow.adm_journals as j 
              ON vi.j_id = j.j_id
        WHERE DATE_FORMAT(vi.received_date, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($send_date)) . "'

        UNION ALL

        SELECT 
            vi.starting_page_number, 
            vi.stage,
            vi.vi_id,
            r.received_date,
            r.due_date,
            vi.received_date as rcv_dt,
            vi.due_date as due_dt,
            volume_number,
            issue_number,
            c.cust_name,
            j.j_code,
            (SELECT COUNT(id) 
             FROM workflow_archive.inw_inward_dtl 
             WHERE v_issue_id = vi.vi_id 
             AND status='Delivery') as ccount,
            (SELECT COUNT(id) 
             FROM workflow_archive.inw_inward_dtl 
             WHERE v_issue_id = vi.vi_id 
             AND status!='Delivery') as pcount,
            'workflow_archive' as source
        FROM workflow_archive.inw_volume_issues as vi
        LEFT JOIN workflow_archive.inw_issue_corrections as r 
               ON vi.vi_id = r.vi_id 
              AND r.ic_id = (
                    SELECT ic_id 
                    FROM workflow_archive.inw_issue_corrections 
                    WHERE vi_id = r.vi_id 
                    ORDER BY ic_id DESC 
                    LIMIT 1
                )
        JOIN workflow_archive.adm_customer_master as c 
              ON vi.cust_id = c.id
        JOIN workflow_archive.adm_journals as j 
              ON vi.j_id = j.j_id
        WHERE DATE_FORMAT(vi.received_date, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($send_date)) . "'
    ) as combined
    ORDER BY vi_id ASC;
";

					
		
				
			

	
		
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		if($row['received_date']!= "")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$created_dt = (date('d-m-Y',strtotime($row['received_date'])));
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$created_dt = (date('d-m-Y',strtotime($row['rcv_dt'])));
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
                    <td><? echo $created_dt; ?></td>
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
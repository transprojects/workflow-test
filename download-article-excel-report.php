<?php 
include ('dbc.php'); 
?>
<?php
header("Content-type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment;Filename=article-report-".date('Y-m-d-H:i a').".xls");
?>
<?php
$_SESSION['fcust_id'] = $_REQUEST['cust_id'];
$_SESSION['stage_id'] = $_REQUEST['stage_id'];
$_SESSION['j_id'] = $_REQUEST['j_id'];
$_SESSION['pub_id'] = $_REQUEST['pub_id'];
$_SESSION['from_dt'] = $_REQUEST['from_dt'];
$_SESSION['to_dt'] = $_REQUEST['to_dt'];

$var = '';
if($_SESSION['fcust_id']!='' && $_SESSION['fcust_id']!='0'){
	
     $var.= "and c.id = '".$_SESSION['fcust_id']."' ";
}
else
{
	
     $var.= "";
}
if($_SESSION['stage_id']!='' && $_SESSION['stage_id']!='0'){
	
      $var.= "and stage LIKE '".$_SESSION['stage_id']."%' ";
}
else
{

            $var.= "";

}
if($_SESSION['j_id']!=''){
	
     $var.= "and j.j_id = '".$_SESSION['j_id']."' ";
}
if($_SESSION['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_SESSION['pub_id']."%' ";
}
if($_SESSION['from_dt']!='' && $_SESSION['to_dt']){
	
   	$var.="and (DATE_FORMAT(recv_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($_SESSION['from_dt']))."' and DATE_FORMAT(recv_dt,'%Y-%m-%d')<='".date('Y-m-d',strtotime($_SESSION['to_dt']))."' OR DATE_FORMAT(received_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($_SESSION['from_dt']))."' and DATE_FORMAT(received_date,'%Y-%m-%d')<='".date('Y-m-d',strtotime($_SESSION['to_dt']))."')";
}

	$_SESSION['varname'] = $var;


?>



														<?php
				      $query = "SELECT c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,file_status,due_dt,ce_pe_due_date,target_due_dt,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id  AND (wd.fig_complete_status = 0) AND fig > 0   AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ".$workloc." and wd.v_issue_id <= 0 and test_article!=1 ".$_SESSION['varname']."  ORDER BY  `due_dt` asc,FIELD(file_status,'Pending','Assign','Inprogress','Need Clarification','Correction Done','Correction Done','Completed',NULL)";
														
														
													
													$articles = $db->query($query) or ($db->error);	
                                                     $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   
													
													

												
                                                    ?>   
														
										<table width="100%" border="1" cellspacing="0" cellpadding="0">
                                                            <thead>
                                                           <th>Customer</th>
					<th>Journal</th>
                    <th>Article</th>                   
                    <th>Group folder</th>                   
                    <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Recv Date</th>
                    <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Due Date</th>
					<?php 
					if($_REQUEST['dep_id'] == 9)
			{?>
					 <th class="hidden-480">Fig</th>
					<th class="hidden-480">Table</th>
					<?php 
					
			}?>
                    <th class="hidden-480">Stage</th>
                    <th class="hidden-480">Status</th>
					
					<th class="hidden-480">Graphic </th>
<?php 
					if($_REQUEST['dep_id'] == 9)
			{?>
                   <th class="hidden-480">Other Team Status</th>
						<?php 
					
			}?>					
															</thead>
                                                            <tbody>
                                                                <?php
                                                                if ($articles->num_rows > 0) {
                                                                    ?>
                                                                    <?php
                                                                   while($row = $articles->fetch_assoc()){ 
		
		
		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row['stage']);
	$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row['stage']);
	
		 if($row['stage'] == "FP")
		{
			if($_SESSION['department_id'] == '1' || $_SESSION['department_id'] == '2')
			{
			$due_dt = (date('d-m-Y',strtotime($row['ce_pe_due_date'])));
			}
			else
			{
			$due_dt = (date('d-m-Y',strtotime($row['target_due_dt'])));
			}
			$recv_dt = (date('d-m-Y',strtotime($row['recv_dt'])));
			$manuscript_count = $row['manuscript_count'];
		}
		elseif($stage_r== "REV")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = $row['correction_pages'];
		}
		elseif($stage_i=='ISSCOR')
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['created_dt'])));
			$manuscript_count = $row['correction_pages'];
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = $row['correction_pages'];
		}
		$tr_class = '';
		
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		if($row['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row['status'] == 'Query') || ($row['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		
		elseif($current_dateTimestamp1 > $Due_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		
		
		
		//$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.process_user !='". $_SESSION['user_id']."' AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		if($_REQUEST['dep_id'] == 9)
		{
			$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$row['stage']."' AND (t.id=(select id from `inw_transactions` where dept=9 and `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		}
		else
		{
			$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$row['stage']."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		}
		
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();	
		$prev_depart_sql = mysqli_fetch_array(mysqli_query($db,"SELECT u.full_name,t.current_status,t.id,dpm.dept_name,dpm.dept_sn FROM `inw_transactions` as t,users as u,adm_dept_master as dpm WHERE t.process_user = u.id and t.dept = dpm.dept_code AND t.`project_id` = '".$row['id']."'  AND t.current_status IN ('Completed','Assign','Correction Done') AND t.stage = '".$row['stage']."' ORDER BY t.`id` DESC limit 1;"));

		 $jdt_sql =mysqli_fetch_array(mysqli_query($db,"select * from journal_type where jt_id = ".$row['jt_id'].""));	
																			//$chk_transaction = $db->query($trans_sql); 
																			//$transaction_toatl_count=$chk_transaction->num_rows;
																			//$trans_res = $chk_transaction->fetch_assoc();	
                                                                        $send_dt = date('d-m-Y', strtotime($row_history['sent_date']));
                                                                        $send_dts = date('d-m-Y', strtotime($row_history['sent_date']));
                                                                        ?>
                                                                        <tr>
                                                                             <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td><?php echo $row['pub_id']; echo ($row['notes']!='') ? ' <span class="label label-sm label-info">'.substr($row['notes'],0,35).'....</span>' : '';?></td>   
					<td><?php echo ltrim($row['issueorgroup_folder'],"/"); ?></td>
                   
                    <td><?php echo $recv_dt; ?></td>
					<td><?php echo $due_dt; ?></td>
						<?php 
					if($_REQUEST['dep_id'] == 9)
			{?>
					 <td><?php echo $row['fig']; ?></td>
                    <td><?php echo $row['tab']; ?></td>
					<?php 
			}
			?>
                    <td><?php echo $row['stage']; ?></td>
					
                      <td><span class="label label-sm label-success"><?php echo (($_REQUEST['dep_id'] == 9) ? 'Graphics' : 
		$row['status']) ; ?></span> <?php if($transaction_toatl_count > 0){ echo '<span class="label label-sm label-info">'.$trans_res['full_name'].'</span>|<span class="label label-sm label-warning">'.$trans_res['current_status'].'</span>'; } ?></td>
		    
					<td><?php if($row['fig_complete_status']==1){ echo 'Yes';}else{ echo 'No';} ?></td>
					<?php 
					if($_REQUEST['dep_id'] == 9)
			{?>
					 <td><span class="label label-sm label-success"><?php echo $prev_depart_sql['dept_sn']; ?></span> | <span class="label label-sm label-info"><?php echo $prev_depart_sql['full_name'];?></span></td>
					<?php 
			}
			?>

                                                                        </tr>
                                                                        <?php
                                                                    }
                                                                }
																	
																	
																
															
                                                                ?>
                                                         
														
													
				  
				     </tbody>
                                                        </table>


</body>
</html>

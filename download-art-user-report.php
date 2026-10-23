<?php 
include ('dbc.php'); 
?>
<?php
header("Content-type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment;Filename=art-user-report-".date('Y-m-d-H:i a').".xls");
?>
<?php
$var = '';
if($_REQUEST['cust_id']!=''){
	
     $var.= "and c.id = '".$_REQUEST['cust_id']."' ";
}
if($_REQUEST['stage_id']!=''){
	
      $var.= "and wd.stage LIKE '%".$_REQUEST['stage_id']."%' ";
}
if($_REQUEST['j_id']!=''){
	
     $var.= "and j.j_id = '".$_REQUEST['j_id']."' ";
}
if($_REQUEST['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_REQUEST['pub_id']."%' ";
}
if($_REQUEST['from_dt']!='' && $_REQUEST['to_dt']){
	
   $var.="and DATE_FORMAT(wd.sent_date,'%Y-%m-%d')>= '".date('Y-m-d',strtotime($_REQUEST['from_dt']))."' and DATE_FORMAT(wd.sent_date,'%Y-%m-%d')<= '".date('Y-m-d',strtotime($_REQUEST['to_dt']))."'";
}

?>



					 <?php
                                                             $query = "SELECT *,c.cust_name,j.j_code from inw_dispatch_history as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  ".$var." ORDER BY `idh_id` DESC ";
														
														$cnt_query = "SELECT COUNT(wd.a_id) as num from inw_dispatch_history as wd,adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  ".$var." ";
														
													 $customers = $db->query($cnt_query) or ($db->error);
													 $page_count = $customers->fetch_assoc();
												 	 $total_pages = $page_count['num'];
													$articles = $db->query($query) or ($db->error);	
                                                     $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   
													
													

												
                                                    ?>   
														
										<table width="100%" border="1" cellspacing="0" cellpadding="0">
                                                            <thead>
                                                            <th>Client </th>
                                                            <th>Journal Name</th>
                                                            <th>FileName</th>
                                                            <th>Stage</th>
															<th>Dispatched Date</th>
															<th>fig</th>
															<th>Total fig count</th>
															<th>Anxillary fig</th>
															<th>Image disk count</th>
															<th>Image Redraw count</th>
															<th>Image Relabel count</th>
															<th>Img color figures</th>
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
																		
																	
		
		$query_disp = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM `inw_inward_dtl` WHERE id ='" . $row_history['a_id'] . "'"));
		
		
		if($row_history['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($query_disp['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($query_disp['recv_dt'])));
			$manuscript_count = $query_disp['manuscript_count'];
			$page_count = $query_disp['page_count'];
		}
		else
		{
			 $rev_rep=str_replace("REV","",$row_history['stage']);
			$query = "SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$row_history['a_id']."' and revision_count='".$rev_rep."'";
		    $revin = $db->query($query) or ($db->error);
		    $revinfo = $revin->fetch_assoc();
			$due_dt = (date('d-m-Y',strtotime($revinfo['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($revinfo['received_date'])));
			//$manuscript_count = $revinfo['correction_pages'];
			$page_count = $revinfo['correction_pages'];
		}
																			//$chk_transaction = $db->query($trans_sql); 
																			//$transaction_toatl_count=$chk_transaction->num_rows;
																			//$trans_res = $chk_transaction->fetch_assoc();	
                                                                        $send_dt = date('d-m-Y', strtotime($row_history['sent_date']));
                                                                        $send_dts = date('d-m-Y', strtotime($row_history['sent_date']));
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo $row_history['cust_name'] ?></td>
                                                                            <td><?php echo $row_history['j_code'] ?></td>
                                                                            <td><?php echo $query_disp['pub_id'] ?></td>
                                                                            <td><?php echo $row_history['stage'] ?></td>
                                                                            <td><?php echo $send_dt; ?></td>
																			<td><?php echo $query_disp['fig']; ?></td>
																			<td><?php echo $query_disp['total_fig_count']; ?></td>
																			<td><?php echo $query_disp['anxillary_fig']; ?></td>
																			<td><?php echo $query_disp['img_disk_count']; ?></td>
																			<td><?php echo $query_disp['img_redraw_count']; ?></td>
																			<td><?php echo $query_disp['img_relabel_count']; ?></td>
																			<td><?php echo $query_disp['img_color_figures']; ?></td>

                                                                        </tr>
                                                                        <?php
                                                                    }
                                                                }
																	
																	
																
															
                                                                ?>
                                                         
														
													
				  
				     </tbody>
                                                        </table>


</body>
</html>

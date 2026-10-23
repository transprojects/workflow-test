<?php 
include ('dbc.php'); 
?>
<?php
header("Content-type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment;Filename=delivery-performance-report-".date('Y-m-d-H:i a').".xls");
?>
<?php
function getHoursMinutes($seconds, $format = '%02d:%02d') {

    if (empty($seconds) || ! is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = ($minutes % 60);

    return sprintf($format, $hours, $remainMinutes);
}
function getDaysHoursMinutes($seconds, $format = '%d days, %02d:%02d') {
    // Validate input
    if (empty($seconds) || !is_numeric($seconds)) {
        return false;
    }

    // Convert seconds into minutes, hours, and days
    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);
    $remainHours = $hours % 24;
    $remainMinutes = $minutes % 60;

    // Format and return the result
    return sprintf($format, $days, $remainHours, $remainMinutes);
}
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
if ($_REQUEST['dp_platform'] != "" && $_REQUEST['dp_platform'] != "all") {
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
if ($_REQUEST['dp_platform'] != "" && $_REQUEST['dp_platform'] != "all") {
     $search .= "and j.j_platform='" . $_REQUEST['dp_platform'] . "' ";
}
}
elseif ($_REQUEST['radio']=='sfd') {
if ($_REQUEST['sdf_dt'] != "") {
    $frm = date('Y-m-d', strtotime($_REQUEST['sdf_dt']));
    $search .= "and DATE_FORMAT(wd.target_due_dt,'%Y-%m-%d')='" . $frm . "'";
	
}
if ($_REQUEST['dp_platform'] != "" && $_REQUEST['dp_platform'] != "all") {
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
$query = "SELECT *,wdd.pub_id, c.cust_name, j.j_code, wd.stage FROM (SELECT DISTINCT cust_id, j_id, a_id, stage,sent_date,idh_id FROM inw_dispatch_history) AS wd
JOIN inw_inward_dtl AS wdd ON wd.a_id = wdd.id
JOIN adm_customer_master AS c ON wd.cust_id = c.id
JOIN adm_journals AS j ON wd.j_id = j.j_id
WHERE wdd.article_title NOT LIKE '%_TOC' 
$ccc $search $search2 $search4";
						
															 $cnt_query = "SELECT COUNT(DISTINCT wd.a_id) AS num
FROM inw_dispatch_history AS wd
JOIN inw_inward_dtl AS wdd ON wd.a_id = wdd.id
JOIN adm_customer_master AS c ON wd.cust_id = c.id
JOIN adm_journals AS j ON wd.j_id = j.j_id
WHERE wdd.article_title NOT LIKE '%_TOC' 
$ccc $search $search2 $search4 ";
														$customers = $db->query($cnt_query) or ($db->error);
													 $page_count = $customers->fetch_assoc();
												 	 $total_pages = $page_count['num'];
													$articles = $db->query($query) or ($db->error);	
                                                     $articles->num_rows;
													//echo "fdgfdg".$total_pages;
                                                   
													
													

												
                                                    ?>   
											<div class="page-header">
                                                            <h1>Delivery Performance Report <b style="color:red;font-size:14px;"> - Total count :<?php echo $total_pages;?></b> </h1>
                                                        </div>			
										<table width="100%" border="1" cellspacing="0" cellpadding="0">
                                                             <thead>
                                                           <th>Client </th>
                                                            <th>Journal Name</th>
                                                            <th>FileName</th>
                                                            <th>Stage</th>
                                                            <th>Received Date</th>
                                                            <th>Due Date</th>
															<th>Dispatched Date</th>
                                                            <th class="thcolor">CE/Pre Editing/Pre Clean</th>
                                                            <th class="thcolor">Art</th>
                                                            <th class="thcolor">PAG</th>
                                                            <th class="thcolor">PRQC</th>
                                                            <th class="thcolor">Proofing</th>
                                                            <th class="thcolor">XML</th>
                                                            <th class="thcolor">Epub</th>
                                                            <th class="thcolor">Total Time Taken</th>	
                                                            <th>MS count</th>
                                                            <th>Page count</th>
                                                            <th>Schedule</th>   
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
		
	                   	$total_time_all = 0; // Total time for all departments
                       $total_time_dept22 = 0; // Total time for department Pre EDITING/CE/PRE Clean
                       $total_time_dept10 = 0; // Total time for department PAG
                       $total_time_dept14 = 0; // Total time for department PRQC
                       $total_time_dept5 = 0; // Total time for department XML
                       $total_time_dept18 = 0; // Total time for department Epub
                       $total_time_dept17 = 0; // Total time for department Proofing
                       $total_time_dept9 = 0; // Total time for department graphics
					//$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$row_history['a_id']." and stage = '".$row_history['stage']."' order by created_dt asc") or ($db->error);				
						$transsql = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code AND project_id = ".$row_history['a_id']." and stage = '".$row_history['stage']."' and current_status NOT IN ('Break','Pending','Assign') order by created_dt asc") or ($db->error);				

							while ($row = $transsql->fetch_array()) {	
    $sno++;
    
    
    
    if ($row['end_time'] != NULL) {
        $time_diff = strtotime($row['end_time']) - strtotime($row['start_time']);
        $total_time_all += $time_diff;
        
        
      
        
        

        // Check if the department is 22 and calculate its total time
        if ($row['dept'] == 22 && $query_disp['cust_id']!='33' ) {
            $total_time_dept22 += $time_diff;
        }
       elseif ($row['dept'] == 24 && $query_disp['cust_id']=='33' &&  $query_disp['inw_type']=='Latex' )
        {
            $total_time_dept22 += $time_diff;
        }
        elseif ($row['dept'] == 1 && $query_disp['cust_id']=='33' &&  $query_disp['inw_type']!='Latex' )
        {
            $total_time_dept22 += $time_diff;
        }
         elseif ($row['dept'] == 1 && $query_disp['cust_id']=='33' &&  $query_disp['inw_type']=='Latex' )
        {
            $total_time_dept22 += $time_diff;
        }
        elseif ($row['dept'] == 1 && $query_disp['cust_id']!='33' &&  $query_disp['inw_type']!='Latex' )
        {
            $total_time_dept22 += $time_diff;
        }
         elseif ($row['dept'] == 2 && $query_disp['cust_id']=='7')
        {
            $total_time_dept22 += $time_diff;
        }
         elseif ($row['dept'] == 10)
        {
            $total_time_dept10 += $time_diff;
        }
         elseif ($row['dept'] == 14)
        {
            $total_time_dept14 += $time_diff;
        }
         elseif ($row['dept'] == 5)
        {
            $total_time_dept5 += $time_diff;
        }
         elseif ($row['dept'] == 17)
        {
            $total_time_dept17 += $time_diff;
        }
         elseif ($row['dept'] == 18)
        {
            $total_time_dept18 += $time_diff;
        }
         elseif ($row['dept'] == 9)
        {
            $total_time_dept9 += $time_diff;
        }
    }
}
					$stage_rev=preg_replace("/REV([0-9]+)/","REV",$row_history['stage']);
					$stage_fin=preg_replace("/FIN([0-9]+)/","FIN",$row_history['stage']);

		if($row_history['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($query_disp['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($query_disp['recv_dt'])));
			$manuscript_count = $query_disp['manuscript_count'];
			$page_count = $query_disp['page_count'];
             if($page_count>0)
            {
            $page_count = $query_disp['page_count'];
            }
            else
            {
            $page_count = $query_disp['manuscript_count'];
            }
            if($query_disp['manuscript_count']>0)
            {
            $manuscript_count = $query_disp['manuscript_count'];
            }
            else
            {
            $manuscript_count = $query_disp['page_count'];
            }
        }
		elseif($stage_rev == "REV")
		{
			 $rev_rep=str_replace("REV","",$row_history['stage']);
			$query = "SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$row_history['a_id']."' and type='REV' and revision_count='".$rev_rep."'";
		    $revin = $db->query($query) or ($db->error);
		    $revinfo = $revin->fetch_assoc();
			$due_dt = (date('d-m-Y',strtotime($revinfo['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($revinfo['received_date'])));
			//$manuscript_count = $revinfo['correction_pages'];
			//$page_count = $revinfo['correction_pages'];
			$page_count = $query_disp['page_count'];
		}
			elseif($stage_fin == "FIN")
		{
			 $rev_rep=str_replace("FIN","",$row_history['stage']);
			$query = "SELECT * FROM inw_revisions_dtl WHERE inw_id = '".$row_history['a_id']."' and type='FIN' and revision_count='".$rev_rep."'";
		    $revin = $db->query($query) or ($db->error);
		    $revinfo = $revin->fetch_assoc();
			$due_dt = (date('d-m-Y',strtotime($revinfo['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($revinfo['received_date'])));
			//$manuscript_count = $revinfo['correction_pages'];
			//$page_count = $revinfo['correction_pages'];
			$page_count = $query_disp['page_count'];
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
                                                                            
                                                                                <?php
                                                                            if ($row_history['stage'] == 'FP') {
																				$due_dts=date('d-m-Y', strtotime($due_dt));
                                                                                ?>
																				<td><?php echo $row_history['stage']; ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
                                                                                <?php
                                                                            } elseif($stage_rev=='REV') {
																				
																				$due_dts=date('d-m-Y', strtotime($due_dt));
                                                                                ?>
																				<td><?php echo $row_history['stage']; ?></td>
                                                                                 <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
                                                                                <?php
                                                                            }
                                                                            elseif($stage_fin=='FIN') {
																				
																				$due_dts=date('d-m-Y', strtotime($due_dt));
                                                                                ?>
																				<td><?php echo $row_history['stage']; ?></td>
                                                                                 <td><?php echo date('d M Y', strtotime($recv_dt)) ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($due_dt)) ?></td>
                                                                                <?php
                                                                            }
																			elseif($stage_i=='ISSCOR') {
																				
																				$due_dts=date('d-m-Y', strtotime($due_dt));
                                                                                ?>
																				 <td><?php echo $row_history['stage'] ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($query_disp['created_dt'])); ?></td>

                                                                                <td><?php echo date('d M Y', strtotime($query_disp['due_dt'])); ?></td>
                                                                                <?php
                                                                            }
																			else{
																				
																				$due_dts=date('d-m-Y', strtotime($due_dt));
                                                                                ?>
																				 <td><?php echo $row_history['stage'] ?></td>
                                                                                <td><?php echo date('d M Y', strtotime($query_disp['created_dt'])) ?></td>

                                                                                <td><?php echo date('d M Y', strtotime($query_disp['due_dt'])) ?></td>
                                                                                <?php
                                                                            }
																			
                                                                            ?>

                                                                             <td><?php echo $send_dt; ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept22, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept9, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept10, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept14, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept18, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept5, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_dept27, '%02d hours %02d minutes'); ?></td>
                                                                         <td><?php echo getHoursMinutes($total_time_all, '%02d hours %02d minutes'); ?></td>
                                                                         
                                                                            <td><?php echo $manuscript_count; ?></td>
																			<td><?php echo $page_count; ?></td>

                                                                            <td>	<?php
                                                                  if (strtotime($send_dts) == strtotime($due_dts)) {
                                                                    $l++;

                                                                    echo "On Time";
																	
                                                                } elseif (strtotime($send_dts) > strtotime($due_dts)) {
                                                                    $j++;
                                                                    echo "Delay";
                                                                } elseif (strtotime($send_dts) < strtotime($due_dts)) {
                                                                    $k++;
                                                                    echo "Ahead";
                                                                }
															     	
                                                                        ?>
                                                                            </td>
                                                                        </tr>
                                                                        <?php
                                                                    }
                                                                }
																	
																	
																
															
                                                                ?>
                                                         
														
													
				  
				     </tbody>
                                                        </table>


</body>
</html>

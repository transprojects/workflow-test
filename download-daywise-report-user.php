<?php 
ini_set('max_execution_time', 360);
$file= time()."_daywise_user_target_report.xls";
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$file"); 
?>
<?php 
include ('dbc.php'); 
include ('includes/paginate.php'); 
page_protect();
?>


              <table border="1" >
                <thead>
					
                  <tr>
					<th>Name</th>
					<th >#FP/Regular</th>
					<th >#FP/Complex</th>
					<th >#REV/Regular</th>
					<th >#REV/Complex</th>
					<th >#ISSUE/Regular</th>
					<th >#ISSUE/Complex</th>
					<th >#TOTAL FIXED %</th>
					<th >#TOTAL VARIABLE %</th>
                  </tr>
                </thead>
                <tbody>
				
				
	
				
		<?php
	

	$sno=1;	
	
$user_sql=mysqli_query($db,"SELECT * FROM `users` where user_level='3' and approved=1 and  department_id = '".$_REQUEST['dep_id']."'");	

	while ($user_name=mysqli_fetch_array($user_sql))
{
		$qry_tr= mysqli_query($db,"SELECT * FROM `adm_customer_master` where  id = '".$user_name['cust_id']."' OR FIND_IN_SET(id,'".$user_name['access_other_cust_id']."')");
		$num_cust=mysqli_num_rows($qry_tr);
		$res_tr=mysqli_fetch_array($qry_tr);

       // echo $ConverDateTomatch;
?>
 <tr>

				
<?php 

	
		
	

				 //FP
				$cnt_query_fp = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw where DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($_REQUEST['from_dt']))."' and current_status='Completed' and current_stage='FP' and process_user='".$user_name['id']."' ".$_SESSION['varname']." "));
				if($cnt_query_fp['num']>0)
				{
				 $assign_qry_fp= mysqli_query($db,"SELECT project_id from inw_transactions as inw where  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($_REQUEST['from_dt']))."' and current_status='Completed' and current_stage='FP' and process_user='".$user_name['id']."' ".$_SESSION['varname']." group by inw.project_id");
				//echo $assign_count=mysqli_num_rows($cnt_query);
				
				 while($assign_res_fp=mysqli_fetch_array($assign_qry_fp))
				 {
					$implode_array_fp[]=$assign_res_fp['project_id'];
					$implode_pid_fp=implode(",",$implode_array_fp) ;
				 }
				 
				 //FP
				 $simple_fixed_fp = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as s_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_fp."')  and complexity='Regular'"));
				 $complex_fixed_fp = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as c_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_fp."')   and complexity!='Regular'"));
				   $f_sfixed+=$simple_fixed_fp['s_fixed'];
				   $f_cfixed+=$complex_fixed_fp['c_fixed'];
				 
				}
				 unset($implode_array_fp);
				
				 //REV
				$cnt_query_rev = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw where DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($_REQUEST['from_dt']))."' and current_status='Completed' and current_stage LIKE '%REV%' and process_user='".$user_name['id']."' ".$_SESSION['varname']." "));
				if($cnt_query_rev['num']>0)
				{
				 $assign_qry_rev = mysqli_query($db,"SELECT project_id from inw_transactions as inw where  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($_REQUEST['from_dt']))."' and current_status='Completed' and current_stage LIKE '%REV%' and process_user='".$user_name['id']."' ".$_SESSION['varname']." group by inw.project_id");
				
				 while($assign_res_rev=mysqli_fetch_array($assign_qry_rev))
				 {
					$implode_array_rev[]=$assign_res_rev['project_id'];
					$implode_pid_rev=implode(",",$implode_array_rev) ;
				 }
				  unset($implode_array_rev);
				
				 //REV
				  $simple_fixed_rev = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as s_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_rev."')  and complexity='Regular'"));
				  $complex_fixed_rev = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as c_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_rev."')  and complexity!='Regular'"));
				   $r_sfixed+=$simple_fixed_rev['s_fixed'];
				   $r_cfixed+=$complex_fixed_rev['c_fixed'];
				
				}
				
				 //ISSUE
				$cnt_query_issue = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num from inw_transactions as inw where DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($_REQUEST['from_dt']))."' and current_status='Completed' and current_stage LIKE '%ISSUE%' and process_user='".$user_name['id']."' ".$_SESSION['varname']." "));
				if($cnt_query_issue['num']>0)
				{
				 $assign_qry_issue = mysqli_query($db,"SELECT project_id from inw_transactions as inw where  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($$_REQUEST['from_dt']))."' and current_status='Completed' and current_stage LIKE '%ISSUE%' and process_user='".$user_name['id']."' ".$_SESSION['varname']." group by inw.project_id");
				
				 while($assign_res_issue=mysqli_fetch_array($assign_qry_issue))
				 {
					$implode_array_issue[]=$assign_res_issue['project_id'];
					$implode_pid_issue=implode(",",$implode_array_issue) ;
				 }
				 				  unset($implode_array_issue);

				 //ISSUE
				  $simple_fixed_issue = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as s_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_issue."')  and complexity='Regular'"));
				  $complex_fixed_issue = mysqli_fetch_array(mysqli_query($db,"SELECT count(*) as c_fixed from inw_inward_dtl where FIND_IN_SET (id,'".$implode_pid_issue."')  and complexity!='Regular'"));
				  $i_sfixed+=$simple_fixed_issue['s_fixed'];
				  $i_cfixed+=$complex_fixed_issue['c_fixed'];
				}
				 
				
				

				  // FP TOTAL PERCENTAGE
				  
				    if($f_sfixed>$user_name['fp_tcount'])
					{ 
					$simple_fixed_fp_per=($user_name['fp_tcount']/$user_name['fp_tcount'])*100;
					}
					elseif($f_sfixed!='')
					{ 
					$simple_fixed_fp_per=($f_sfixed/$user_name['fp_tcount'])*100;
					}
					else
					{ 
					$simple_fixed_fp_per=0;
					}
					$var_fp_simple_1=$f_sfixed-$user_name['fp_tcount'];
					//$var_fp_simple_2=$user_name['fp_tcount']-$f_sfixed;
					
					 if($var_fp_simple_1>0)
					{ 
					$simple_var_fp_per=($var_fp_simple_1/$user_name['fp_tcount_sv'])*100;
					}
					else
					{ 
					$simple_var_fp_per=0;
					}
					
				 
				   if($f_cfixed>$user_name['fp_tcount_cf'])
					{ 
					$complex_fixed_fp_per=($user_name['fp_tcount_cf']/$user_name['fp_tcount_cf'])*100;
					}
					elseif($f_cfixed!='')
					{ 
					$complex_fixed_fp_per=($f_cfixed/$user_name['fp_tcount_cf'])*100;
					}
					else
					{ 
					$complex_fixed_fp_per=0;
					}
				 
                 
				    $var_fp_complex_1=$f_cfixed-$user_name['fp_tcount_cf'];
					//$var_fp_complex_2=$user_name['fp_tcount_cf']-$f_cfixed;
					
				    if($var_fp_complex_1>0)
					{ 
					$complex_var_fp_per=($var_fp_complex_1/$user_name['fp_tcount_cv'])*100;
					}
					else
					{ 
					$complex_var_fp_per=0;
					}
				 
				 // REV TOTAL PERCENTAGE
				 
				 if($r_sfixed>$user_name['rev_tcount'])
				 { 
				 $simple_fixed_rev_per=($user_name['rev_tcount']/$user_name['rev_tcount'])*100;

				 }
				 elseif($r_sfixed!='')
				{ 
				 $simple_fixed_rev_per=($r_sfixed/$user_name['rev_tcount'])*100;
				}
				else
				{ 
				$simple_fixed_rev_per=0;
				}
				 
				 
				 
				 	
					
					$var_rev_simple_1=$r_sfixed-$user_name['rev_tcount'];
					//$var_rev_simple_2=$user_name['rev_tcount']-$r_sfixed;
					
				    if($var_rev_simple_1>0)
					{ 
					$simple_var_rev_per=($var_rev_simple_1/$user_name['rev_tcount_sv'])*100;
					}
					else
					{ 
					$simple_var_rev_per=0;
					}
					
				 
				  if($r_cfixed>$user_name['rev_tcount_cf'])
				 { 
			     $complex_fixed_rev_per=($user_name['rev_tcount_cf']/$user_name['rev_tcount_cf'])*100;

				 }
				 elseif($r_cfixed!='')
				{ 
				 $complex_fixed_rev_per=($r_cfixed/$user_name['rev_tcount_cf'])*100;
				}
				else
				{ 
				  $complex_fixed_rev_per=0;
				}
				
				
				 
	
					
					$var_rev_complex_1=$r_cfixed-$user_name['rev_tcount'];
					//$var_rev_complex_2=$user_name['rev_tcount']-$r_cfixed;
					
				    if($var_rev_complex_1>0)
					{ 
					$complex_var_rev_per=($var_rev_complex_1/$user_name['rev_tcount_cv'])*100;
					}
					else
					{ 
					$complex_var_rev_per=0;
					}
				 
				  // ISSUE/FINAL TOTAL PERCENTAGE
				 
				  if($i_sfixed>$user_name['fin_tcount'])
				 { 
				 $simple_fixed_issue_per=($user_name['fin_tcount']/$user_name['fin_tcount'])*100;


				 }
				 elseif($i_sfixed!='')
				{ 
                 $simple_fixed_issue_per=($i_sfixed/$user_name['fin_tcount'])*100;
				}
				else
				{ 
				  $simple_fixed_issue_per=0;
				}
				 
				 
	
					
					$var_issue_simple_1=$i_sfixed-$user_name['fin_tcount'];
					//$var_issue_simple_2=$user_name['fin_tcount']-$i_sfixed;
					
				    if($var_issue_simple_1>0)
					{ 
					$simple_var_issue_per=($var_issue_simple_1/$user_name['fin_tcount_sv'])*100;
					}
					else
					{ 
					$simple_var_issue_per=0;
					}
				 
				 
				  if($i_cfixed>$user_name['fin_tcount_cf'])
				 { 
			     $complex_fixed_issue_per=($user_name['fin_tcount_cf']/$user_name['fin_tcount_cf'])*100;

				 }
				 elseif($r_cfixed!='')
				{ 
				 $complex_fixed_issue_per=($i_cfixed/$user_name['fin_tcount_cf'])*100;
				}
				else
				{ 
				 $complex_fixed_issue_per=0;
				}
				
				 

					
					
					$var_issue_complex_1=$i_cfixed-$user_name['fin_tcount'];
					//$var_issue_complex_2=$user_name['fin_tcount']-$i_cfixed;
					
				    if($var_issue_complex_1>0)
					{ 
					$complex_var_issue_per=($var_issue_complex_1/$user_name['fin_tcount_cv'])*100;
					}
					else
					{ 
					$complex_var_issue_per=0;
					} 
				 
				 
                ?>
					
                    <td><?php echo $user_name['full_name']; ?></td>
					<td><b><?php echo $user_name['fp_tcount'];?>/<?php echo $user_name['fp_tcount_sv'];?> -  <?php if($f_sfixed>$user_name['fp_tcount']){ echo $user_name['fp_tcount'];}elseif($f_sfixed!=''){ echo $f_sfixed;}else{ echo "0";}?><?php if($f_sfixed>$user_name['fp_tcount']){ echo "/".($f_sfixed-$user_name['fp_tcount']);}else{ echo '/0';}?></b></td>
					<td><b><?php echo $user_name['fp_tcount_cf'];?>/<?php echo $user_name['fp_tcount_cv'];?> - <?php if($f_cfixed>$user_name['fp_tcount_cf']){ echo $user_name['fp_tcount_cf'];}elseif($f_cfixed!=''){ echo $f_cfixed;}else{ echo "0";}?><?php if($f_cfixed>$user_name['fp_tcount_cf']){ echo "/".($f_cfixed-$user_name['fp_tcount_cf']);}else{ echo '/0';}?></b></td>
					<td><b><?php echo $user_name['rev_tcount'];?>/<?php echo $user_name['rev_tcount_sv'];?> - <?php if($r_sfixed>$user_name['rev_tcount']){ echo $user_name['rev_tcount'];}elseif($r_sfixed!=''){ echo $r_sfixed;}else{ echo "0";}?><?php if($r_sfixed>$user_name['rev_tcount']){ echo "/".($r_sfixed-$user_name['rev_tcount']);}else{ echo '/0';}?></b></td>
					<td><b><?php echo $user_name['rev_tcount_cf'];?>/<?php echo $user_name['rev_tcount_cv'];?> - <?php if($r_cfixed>$user_name['rev_tcount_cf']){ echo $user_name['rev_tcount_cf'];}elseif($r_cfixed!=''){ echo $r_cfixed;}else{ echo "0";}?><?php if($r_cfixed>$user_name['rev_tcount_cf']){ echo "/".($r_cfixed-$user_name['rev_tcount_cf']);}else{ echo '/0';}?></b></td>
					<td><b><?php echo $user_name['fin_tcount'];?>/<?php echo $user_name['fin_tcount_sv'];?> - <?php if($i_sfixed>$user_name['fin_tcount']){ echo $user_name['fin_tcount'];}elseif($i_sfixed!=''){ echo $i_sfixed;}else{ echo "0";}?><?php if($i_sfixed>$user_name['fin_tcount']){ echo "/".($i_sfixed-$user_name['fin_tcount']);}else{ echo '/0';}?></b></td>
					<td><b><?php echo $user_name['fin_tcount_cf'];?>/<?php echo $user_name['fin_tcount_cv'];?> - <?php if($i_cfixed>$user_name['fin_tcount_cf']){ echo $user_name['fin_tcount_cf'];}elseif($i_cfixed!=''){ echo $i_cfixed;}else{ echo "0";}?><?php if($i_cfixed>$user_name['fin_tcount_cf']){ echo "/".($i_cfixed-$user_name['fin_tcount_cf']);}else{ echo '/0';}?></b></td>	
					<td><b><?php echo round($simple_fixed_fp_per+$complex_fixed_fp_per+$simple_fixed_rev_per+$complex_fixed_rev_per+$simple_fixed_issue_per+$complex_fixed_issue_per);?>%</b></td>
					<td><b><?php echo round($simple_var_fp_per+$complex_var_fp_per+$simple_var_rev_per+$complex_var_rev_per+$simple_var_issue_per+$complex_var_issue_per);?>%</b></td>


                                                                                </tr>
                <?php
          
			
                     
				
				$start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));
				$simple_fixed_fp['s_fixed']=0;
				$complex_fixed_fp['c_fixed']=0;
				$simple_fixed_rev['s_fixed']=0;
				$complex_fixed_rev['c_fixed']=0;
				$simple_fixed_issue['s_fixed']=0;
				$complex_fixed_issue['c_fixed']=0;
				?>
				
				<?php 
				$f_sfixed=0;
				$f_cfixed=0;
				$r_sfixed=0;
				$r_cfixed=0;
				$i_sfixed=0;
				$i_cfixed=0;
				
				  $sno++;
}
				  ?>
                  
              
	
                </tbody>
              </table>
			  

         
		
        <!-- /.row --> 
   
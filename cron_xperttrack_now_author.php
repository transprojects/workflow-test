<?php
require('includes/conn.php');
require('linux_db_now.php');
ini_set('max_execution_time', '6000');

$cdate = date('Y-m-d'); // Example date

// Convert the date string into a DateTime object
$date = new DateTime($cdate);

// Subtract 15 days from the date
$date->modify('-60 days');

// Get the new date in the same format
$newDate = $date->format('Y-m-d');



$sn=1;

$sql_inward=mysqli_query($xpertrack_db,"SELECT wd.id,wd.workflow_id FROM `inw_inward_dtl` as wd where wd.workflow_id IS NOT NULL and  DATE_FORMAT(created_dt,'%Y-%m-%d')>='".$newDate."'");

while($res_inward=mysqli_fetch_array($sql_inward))
{
	
			//echo "<br/>";
			
			$sql_inward_fp=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage='FP' order by idh_id desc limit 1 "));
			
			$sql_inward_xtrev1=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage='REV1' order by idh_id desc limit 1 "));
			
			$sql_inward_xtrev2=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage='REV2'  order by idh_id desc limit 1 "));

			$sql_inward_xtrev3above=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage!='REV1' and stage!='REV2' and stage NOT LIKE '%FIN%' and stage!='FP'  order by idh_id desc limit 1 "));
			
			$sql_inward_xtfinals=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and  stage LIKE '%FIN%'   order by idh_id desc limit 1 "));

				// Initialize variables
$res_inward_xtfp = $sql_inward_fp['sent_date'] != "" ? "'".$sql_inward_fp['sent_date']."'" : "NULL";
$res_inward_xtrev1 = $sql_inward_xtrev1['sent_date'] != "" ? "'".$sql_inward_xtrev1['sent_date']."'" : "NULL";
$res_inward_xtrev2 = $sql_inward_xtrev2['sent_date'] != "" ? "'".$sql_inward_xtrev2['sent_date']."'" : "NULL";
$res_inward_xtrev3above = $sql_inward_xtrev3above['sent_date'] != "" ? "'".$sql_inward_xtrev3above['sent_date']."'" : "NULL";
$res_inward_xtfinals = $sql_inward_xtfinals['sent_date'] != "" ? "'".$sql_inward_xtfinals['sent_date']."'" : "NULL";

		
	echo $sn. ") UPDATE article_tracker set first_proof_sent_from_transforma=".$res_inward_xtfp.",revised_proof_from_transforma=".$res_inward_xtrev1.",finals_from_transforma=".$res_inward_xtfinals." where inw_id='".$res_inward['id']."'";
	echo "<br/>";
  
    $update_last_action=mysqli_query($xpertrack_db,"UPDATE article_tracker set first_proof_sent_from_transforma=".$res_inward_xtfp.",revised_proof_from_transforma=".$res_inward_xtrev1.",finals_from_transforma=".$res_inward_xtfinals." where inw_id='".$res_inward['id']."' ");
   $sn++;

    //exit;  
}


$sql_inward_to=mysqli_query($xpertrack_db,"SELECT wd.id,wd.workflow_id FROM `inw_inward_dtl` as wd where wd.workflow_id IS NOT NULL and  DATE_FORMAT(created_dt,'%Y-%m-%d')>='".$newDate."'");

while($res_inward_to=mysqli_fetch_array($sql_inward_to))
{
	
			//echo "<br/>";
			
			
			

			
			$sql_to_xtrevises=mysqli_fetch_array(mysqli_query($db,"SELECT received_date FROM  inw_revisions_dtl  where inw_id='".$res_inward_to['workflow_id']."' and  type LIKE '%REV%'   order by r_id  desc limit 1 "));
			$sql_to_xtfinals=mysqli_fetch_array(mysqli_query($db,"SELECT received_date FROM   inw_revisions_dtl  where inw_id='".$res_inward_to['workflow_id']."' and  type LIKE '%FIN%'   order by r_id  desc limit 1 "));

				// Initialize variables
$res_to_xtrevises = $sql_to_xtrevises['received_date'] != "" ? "'".$sql_to_xtrevises['received_date']."'" : "NULL";
$res_to_xtfinals = $sql_to_xtfinals['received_date'] != "" ? "'".$sql_to_xtfinals['received_date']."'" : "NULL";

		
	echo $sn. ") UPDATE article_tracker set revised_proof_corrections_to_transforma=".$res_to_xtrevises.",finals_to_transforma=".$res_to_xtfinals.",finals_corrections_to_transforma=".$res_to_xtfinals." where inw_id='".$res_inward_to['id']."'";
	echo "<br/>";
  
    $update_last_action=mysqli_query($xpertrack_db,"UPDATE article_tracker set revised_proof_corrections_to_transforma=".$res_to_xtrevises.",finals_to_transforma=".$res_to_xtfinals.",finals_corrections_to_transforma=".$res_to_xtfinals." where inw_id='".$res_inward_to['id']."' ");
   $sn++;

    //exit;  
}
?>

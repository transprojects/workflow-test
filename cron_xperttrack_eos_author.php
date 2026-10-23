<?php
require('includes/conn.php');
require('linux_db.php');
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
	
			//echo $sn. ") SELECT sent_date,a_id FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage LIKE '%FIN%' ";
			//echo "<br/>";

			$sql_inward_xt=mysqli_fetch_array(mysqli_query($db,"SELECT sent_date FROM  inw_dispatch_history  where a_id='".$res_inward['workflow_id']."' and stage LIKE '%FIN%' order by idh_id desc limit 1 "));
			// $nums_inward=mysqli_num_rows($sql_inward_xt);
			//$res_inward_xt=mysqli_fetch_array($sql_inward_xt);
			
			//echo $res_inward_xt['cc'];
			//echo "<br/>";
			//echo $res_inward_xt['sent_date'];
			//echo "<br/>";
	//if($nums_inward>0)
	//{
	echo $sn. ") UPDATE article_tracker set final_delivery='".$sql_inward_xt['sent_date']."' where inw_id='".$res_inward['id']."' ";
	echo "<br/>";
  
    $update_last_action=mysqli_query($xpertrack_db,"UPDATE article_tracker set final_delivery='".$sql_inward_xt['sent_date']."' where inw_id='".$res_inward['id']."' ");
	//}
   $sn++;

      

}
?>
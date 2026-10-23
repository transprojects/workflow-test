<?php
require('includes/conn.php');
ini_set('max_execution_time', '6000');
require('linux_db_tsp.php');


$sql_inward=mysqli_query($db,"SELECT * FROM `inw_inward_dtl` as wd where pub_id in ('TSP_Phyton_52739','TSP_CMC_52406','TSP_IASC_36897','TSP_JRM_51854','TSP_CMES_50349','TSP_CMC_54360','TSP_CMC_52476','TSP_CSSE_52510','TSP_CMC_52323','TSP_CMC_52804','TSP_CHD_52267','TSP_FDMP_52377','TSP_CSSE_50817','TSP_OR_51672','TSP_CMC_52155','TSP_JRM_52288','TSP_Phyton_51644','TSP_OR_50125','TSP_CHD_53484','TSP_JRM_50685','TSP_CMC_53630')");

while($res_inward=mysqli_fetch_array($sql_inward))
{
	
	echo $res_inward['id']."-->".$res_inward['pub_id'];
	echo "<br/>";
	
	    /*$inwerd_detl_xperttrack = "SELECT * FROM inw_inward_dtl where pub_id ='" . $res_inward['pub_id'] . "';";
		$inwerd_query_xperttrack = $xpertrack_db->query($inwerd_detl_xperttrack) or die($xpertrack_db->error);
		$inwed_run_xperttrack = $inwerd_query_xperttrack->fetch_assoc();
	
	
	
	 $body ="<div>Dear Dr. ##AuthorNames##,&nbsp;</div><div><br></div><div>Please check the attachment carefully and send your response back due in 48 hours. The proof will be considered the final version.<br/><br/>

If you have corrections, it is important to ensure that all of your corrections are highlighted in the proof and sent back to us. Please use the attached PDF for any corrections.<br/><br/>

If you have no corrections, please also respond within the required time to us with your confirmation.<br/><br/>

If you have any problems, please do not hesitate to contact us. If we don't receive your final revision email within 48 hours, we will assume no changes are needed, and proceed to schedule this version for finalization.<br/><br/>
 </div>
<br>
<br>
<table style='border-collapse: collapse;'>
    <tbody>
	  <tr style='border: 1px solid rgb(244, 176, 131); border-image: none;' bgcolor='#fbe4d5'>
	  	<td style='font-size: 14px; font-weight: bold;'>Link</td>
	  	<td style='color: rgb(0, 128, 255);'><a href='##HOSTNAME##=##URLPASSWORD##' target='_blank'>##HOSTNAME##=##URLPASSWORD##</a></td>
	  </tr>
     </tbody>
</table>
<br>
Regards,
<br>
<br>Tech Science Publications.<br>
*** This is an automated system generated e-mail, please do not return your correction to this mail.</div>";

	 $spassword=date('Y')."-".$inwed_run_xperttrack['id']."-".date('m')."-".date('d');
	//$spassword="12";
	//echo $artilce_info_xpert['corresponding_author'];
      $body = str_replace('##AuthorNames##', $res_inward['corresponding_author'], $body);
    $body = str_replace('##FileName##', $res_inward['pub_id'], $body);
    $body = str_replace('##ARTICLETITLE##', $res_inward['article_title'], $body);
	//	echo $body;
/////// Get the Client Name ////
	$url='https://xperttrack.transforma.in/publishers/tsp/author-revision/index.php?id';
	$sub="[##FileName##] - Finalised Proof for your record";
	$body = str_replace('##HOSTNAME##',$url, $body);
	$body = str_replace('##URLPASSWORD##', base64_encode($spassword), $body);
    $Subject = str_replace('##FileName##', $res_inward['pub_id'], $sub);
	
	
				$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $res_inward['cust_id'] . "', '" . $res_inward['j_id'] . "', '" . $res_inward['id'] . "', 'pm@transforma.in', '" . $res_inward['corresponding_mail']. "', '" .mysqli_real_escape_string($db,$body). "', '54', 'REV2', NOW(),'Attach file &amp; mail')";
				
				$db->query($dispat_qry);
				
				*/
				

}
?>
<?php require("dbc.php");  ?>
<style>
body {
  padding: 1rem;
}
table {
  border-collapse: collapse;
}
td, th {
  border: 1px solid #999;
  padding: 0.5rem;
  text-align: left;
}
th {
  background: lightblue;
  border-color: white;
}
.center {
  margin-left: auto;
  margin-right: auto;
}
.label-info {
    background-color: #5bc0de;
}
.label {
    display: inline;
    padding: .2em .6em .3em;
    font-size: 75%;
    color: #fff;
    border-radius: .25em;
}
.badge, .close, .label {
    line-height: 1;
}
.badge, .label {
    font-weight: 700;
    white-space: nowrap;
    text-align: center;
}
.label, sub, sup {
    vertical-align: baseline;
}
*, :after, :before {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
.label-warning {
    background-color: #f0ad4e;
}
.label {
    display: inline;
    padding: .2em .6em .3em;
    font-size: 75%;
    color: #fff;
    border-radius: .25em;
}

.square {
  width: 25px;
  height: 25px;
  background: red;
}
body {
  overflow: hidden;
}


/* Preloader */

#preloader {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #fff;
  /* change if the mask should have another color then white */
  z-index: 99;
  /* makes sure it stays on top */
}

#status {
  width: 200px;
  height: 200px;
  position: absolute;
  left: 50%;
  /* centers the loading animation horizontally one the screen */
  top: 50%;
  /* centers the loading animation vertically one the screen */
  background-image: url(https://raw.githubusercontent.com/niklausgerber/PreLoadMe/master/img/status.gif);
  /* path to your loading animation */
  background-repeat: no-repeat;
  background-position: center;
  margin: -100px 0 0 -100px;
  /* is width and height divided by two */
}

</style>
<?php 
$time = strtotime('2020-12-28 17:25:43');
;

function humanTiming ($time)
{

    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? 1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }

}
?>
<html>
<head>
<title>TVL WORKFLOW LOGIN DETAILS</title>
<!--<meta http-equiv="refresh" content="300">-->
</head>

<body>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<!-- Preloader -->
<div id="preloader">
  <div id="status">&nbsp;</div>
</div>
<CENTER><b>TVL WORKFLOW LOGIN/WORK DETAILS  </b><br/><br/>

<span style="color:red;font-weight:bolder;"><?php echo date('d-m-Y'); ?></span></CENTER><br/>

<CENTER>
</CENTER>
<table  class="center" style="border:0px;">

<tr class="center">
<td><div class="square" style="background:yellow !important;"></div><span style="font-size:11px;">NOT IN</span></td>
<td><div class="square" style="background:red !important;"></div><span style="font-size:11px;">WORKFLOW MISSING/NO WORK</span></td>

</tr>
<table class="center">
<tr>
<th>S.no</th>
<th>Workflow ID</th>
<th>Name</th>
<th>Email/Username</th>
<!--<th>Password</th>-->
<th>Department</th>  
<th>Total File count</th>  
<th>Completed File count</th>  
<th>Current Login  </th>
<th>Last Login</th>
<th>Current File  </th>
<th>Start time  </th>
<th>File Taken  </th>

</tr>
 <?php
 $sno=1;
		$query = "SELECT u.*,um.user_role_name,dm.dept_name FROM users as u
LEFT JOIN user_role_master as um ON u.user_level = um.user_role_id 
LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id where u.approved=1 and worklocation='TVL' ORDER BY dm.dept_code,  u.full_name ASC ";

		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		if($row['approved']==1)
                        $status="Active";
                        else
                        $status="In Active";
		$sql_password=mysqli_fetch_array(mysqli_query($db,"select * from tbl_user_password where user_id='".$row['id']."'"));
		$sql_login=mysqli_query($db,"select * from login_history where lh_userid='".$row['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".date('Y-m-d')."' order by lh_id desc limit 1");
		$res_login=mysqli_fetch_array($sql_login);
		$num_login=mysqli_num_rows($sql_login);
		$transaction_file=mysqli_fetch_array(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date('Y-m-d')."' order by id desc limit 1"));
		$transaction_tfile_count=mysqli_num_rows(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date('Y-m-d')."' group by project_id"));
		$transaction_cfile_count=mysqli_num_rows(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date('Y-m-d')."' and current_status='Completed' group by project_id"));
		
		$current_file=mysqli_fetch_array(mysqli_query($db,"select * from inw_inward_dtl where id='".$transaction_file['project_id']."' "));

				
		$trans_sql = "SELECT u.full_name,t.current_status,t.id,t.start_time FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$transaction_file['project_id']."' and  process_user='".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$current_file['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$transaction_file['project_id']."' and  process_user='".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();
		
		
		?>
                  <tr <?php if($num_login==0){?> style="background-color:yellow;"<?php }?>>                    
                    <td><?php echo $sno; ?></td>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['user_email']; ?></td>
                    <!--<td <?php if($sql_password['password']=='123456'){?>style="background-color:red;"<?php }?>><?php  echo $sql_password['password']; ?></td>-->
                    <td><b><?php echo $row['dept_name']; ?></b></td>
                    <td><b><?php echo $transaction_tfile_count; ?></b></td>
                    <td><b><?php echo $transaction_cfile_count; ?></b></td>
                    <td><?php if($num_login>0){ echo date('H:i:s',strtotime($res_login['lh_current_dt'])); } ?></td>
                    <td><?php if($num_login>0){ echo date('H:i:s',strtotime($res_login['lh_last_dt'])); } ?></td>
                    <td <?php if($num_login!=0 && $transaction_toatl_count==0){?> style="background-color:red;"<?php }?>><?php if($num_login>0){ echo $current_file['pub_id']; ?><span class="label label-sm label-success"><?php echo $row['status']; ?></span> <?php if($transaction_toatl_count > 0){ echo '<span class="label label-sm label-info">'.$trans_res['full_name'].'</span>|<span class="label label-sm label-warning">'.$trans_res['current_status'].'</span>'; } } ?></td>
					 <td><?php if($trans_res['current_status']=='Inprogress'){ echo date('H:i:s',strtotime($trans_res['start_time'])); } ?></td>
					 <td><?php if($trans_res['current_status']=='Inprogress'){ $time = strtotime($trans_res['start_time']); echo humanTiming($time).' ago'; } ?></td>

                  </tr>
                  <?php
				  $sno++;
				  } 
				  ?>
</table>
<script>
$(window).on('load', function() { // makes sure the whole site is loaded 
  $('#status').fadeOut(); // will first fade out the loading animation 
  $('#preloader').delay(350).fadeOut('slow'); // will fade out the white DIV that covers the website. 
  $('body').delay(350).css({'overflow':'visible'});
})
</script>
</body>
</html>
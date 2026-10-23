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
function getHoursMinutes($seconds, $format = '%02d:%02d') {

    if (empty($seconds) || ! is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = ($minutes % 60);

    return sprintf($format, $hours, $remainMinutes);
}
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">


<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<!-- Preloader -->

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>


<div id="preloader">
  <div id="status">&nbsp;</div>
</div>
<CENTER><b>TVL WORKFLOW LOGIN/WORK DETAILS  </b><br/><br/>


<CENTER>
</CENTER>
<form method="post">
<table  class="table" style="border:0px;">

<tr class="table center">


<tr>
                           

  <?php
		$query = "SELECT * FROM `users` where user_level =3 and worklocation='TVL' and approved='1'  order by full_name asc";	
		$users = $db->query($query) or ($db->error);		
		
		?>
                             
								 <label class="control-label col-xs-5 col-sm-1 no-padding-right" >From Date:</label>
									<input type="date" id="from_dt" name="from_dt" class="col-xs-3 col-sm-2 date-picker" value="<?php if($_REQUEST['from_dt']!=""){ echo $_REQUEST['from_dt'];}?>"/>
									
									<label class="control-label col-xs-5 col-sm-1 no-padding-right" >To Date:</label>
                                <input type="date" id="to_dt" name="to_dt" class="col-xs-3 col-sm-2 date-picker" value="<?php if($_REQUEST['to_dt']!=""){ echo $_REQUEST['to_dt'];}?>"/>
	                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="email">User :</label>
							   <select id="user_id" name="user_id" style="width:150px;height:35px;" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $users->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if ($_REQUEST['user_id'] == $row['id']) { echo ' selected="selected"'; } ?>><?php echo $row['full_name']; ?></option>
								  <?php } ?>
                                </select>
								<tr>
								    <input type="submit" value="Get Result"  name="getaction" class="btn btn-info" style="margin-left:100px;">

								</tr>
								
								

</tr>

                          
                        
</tr>
</table>
<?php 
$startTime = strtotime($_REQUEST['from_dt']);
$endTime = strtotime($_REQUEST['to_dt']);

// Loop between timestamps, 24 hours at a time
$j=1;
for ( $i = $startTime; $i <= $endTime; $i = $i + 86400 ) 
{
	
?>


 <?php
 if($_REQUEST['user_id']!="")
 {
 $sno=1;
		 $query = "SELECT u.*,um.user_role_name,dm.dept_name FROM users as u
LEFT JOIN user_role_master as um ON u.user_level = um.user_role_id 
LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id where u.approved=1 and worklocation='TVL' and u.id='".$_REQUEST['user_id']."' ORDER BY dm.dept_code,  u.full_name ASC ";

		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		if($row['approved']==1)
                        $status="Active";
                        else
                        $status="In Active";
		$sql_password=mysqli_fetch_array(mysqli_query($db,"select * from tbl_user_password where user_id='".$row['id']."'"));
		 $sql_login=mysqli_query($db,"select * from login_history where lh_userid='".$row['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' order by lh_id desc limit 1");
		$res_login=mysqli_fetch_array($sql_login);
		 $sql_login_first=mysqli_query($db,"select * from login_history where lh_userid='".$row['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' order by lh_id asc limit 1");
		 $res_login_first=mysqli_fetch_array($sql_login_first);

		$num_login=mysqli_num_rows($sql_login);
		
		$transsql = $db->query("select * from login_history where DATE_FORMAT(lh_current_dt,'%Y-%m-%d') = '".date( 'Y-m-d', $i )."' and lh_userid=".$row['id']."") or ($db->error);$login_count=$transsql->num_rows;
		
		
		$transaction_file=mysqli_fetch_array(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' order by id desc limit 1"));
		$transaction_tfile_count=mysqli_num_rows(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' group by project_id"));
		$transaction_cfile_count=mysqli_num_rows(mysqli_query($db,"select * from inw_transactions where process_user='".$row['id']."' and DATE_FORMAT(created_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' and current_status='Completed' group by project_id"));
		
		$current_file=mysqli_fetch_array(mysqli_query($db,"select * from inw_inward_dtl where id='".$transaction_file['project_id']."' "));

				
		$trans_sql = "SELECT u.full_name,t.current_status,t.id,t.start_time FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$transaction_file['project_id']."' and  process_user='".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$current_file['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$transaction_file['project_id']."' and  process_user='".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();
		
	$transsql_hrs = $db->query("select t.*,u.user_name,u.full_name,dm.dept_name from inw_transactions as t, users as u,adm_dept_master as dm where t.process_user = u.id AND t.dept = dm.dept_code and  t.process_user='".$row['id']."' and DATE_FORMAT(t.created_dt,'%Y-%m-%d')='".date( 'Y-m-d', $i )."' ") or ($db->error);				

			
			
			$diff=0;
			while($rows = $transsql_hrs->fetch_array())
			{	
		if($rows['end_time']!=`NULL`)
		{
			$diff+= strtotime($rows['end_time'])-strtotime($rows['start_time']);
		}
			}
			if($transaction_cfile_count>0)
			{
		?>
		<center><span style="color:red;font-weight:bolder;font-size:30px;"><?php echo date( 'd-m-Y', $i ); ?></span></center><br/>

<table class="table center">
<tr>
<th>S.no</th>
<th>Name</th>
<th>Department</th>  
<th>No of Files</th>  
<th>Login Count</th>
<th>Total Hrs</th>

</tr>
                  <tr <?php if($num_login==0){?> style="background-color:yellow;"<?php }?>>                    
                    <td><?php echo $j++; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><b><?php echo $row['dept_name']; ?></b></td>
                    <td><b><a href="javascript:void();" style="text-decoration:none;color:red;" data-toggle="modal" data-target="#myModals" onClick="return timepopups('<?php echo $row['id']; ?>','<?php echo date( 'Y-m-d', $i ); ?>');"><?php echo $transaction_cfile_count; ?></a></b></td>
                    <td><a href="javascript:void();" style="text-decoration:none;color:red;" data-toggle="modal" data-target="#myModal" onClick="return timepopup('<?php echo $row['id']; ?>','<?php echo date( 'Y-m-d', $i ); ?>');"><?php echo $login_count;?> </a></td>
					<td><?php  echo getHoursMinutes($diff, '%02d hours %02d minutes'); ?></td>
                   

                  </tr>
                  <?php
			}
				  $sno++;
				  } 
 }
				  ?>
</table>

 
  
  
</div>
<?php 
}
?>
<div class="container"> 
  
  <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">    
      <!-- Modal content-->
      <div class="modal-content">        
        <div class="modal-body">		
          <p id="ppopup"></p>
        </div>        
      </div>
      
    </div>
  </div>
  
   <div class="modal fade" id="myModals" role="dialog">
    <div class="modal-dialog">    
      <!-- Modal content-->
      <div class="modal-content">        
        <div class="modal-body">		
          <p id="ppopups"></p>
        </div>        
      </div>
      
    </div>
  </div>
<script>
$(window).on('load', function() { // makes sure the whole site is loaded 
  $('#status').fadeOut(); // will first fade out the loading animation 
  $('#preloader').delay(350).fadeOut('slow'); // will fade out the white DIV that covers the website. 
  $('body').delay(350).css({'overflow':'visible'});
})


function timepopup(id,date)
			{				
				var url = "login-history-popup.php?id="+id+"&date="+date;
				$("#ppopup").load(url);
			}
			
			function timepopups(id,date)
			{				
				var url = "project_user_dctime.php?id="+id+"&date="+date;
				$("#ppopups").load(url);
			}
</script>

<script type="text/javascript">

jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	

			})
		</script>
</body>
</html>
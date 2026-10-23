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
</style>
<?php 
//$time = strtotime('2020-12-28 17:25:43');
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
<title>TSP WORKFLOW DETAILS</title>
<meta http-equiv="refresh" content="300">
</head>
<CENTER><b>TSP WORKFLOW WORK DETAILS  </b><br/><br/>

<span style="color:red;font-weight:bolder;"><?php echo date('d-m-Y'); ?></span></CENTER><br/>

<CENTER><b style="font-size:25px;">TVL ASP </b></CENTER>
<table class="center">
<tr>
<th>File </th>  
<th>Status</th>  

</tr>
 <?php
		 $query = "SELECT * from inw_inward_dtl where cust_id='33' and DATE_FORMAT(recv_dt,'%Y-%m-%d')='".date('Y-m-d')."' ";

		$customers = $db->query($query) or ($db->error);	
		$transaction_toatl_count=$customers->num_rows;		
		while($row = $customers->fetch_assoc()){ 
	
		$transaction_file=mysqli_num_rows(mysqli_query($db,"select * from inw_transactions  where project_id='".$row['id']."' group by project_id"));
		

		?>
                  <tr >                    
                    <td><?php echo $row['job_id']; ?></td>
                    <td><b><?php if($transaction_file>0){?>In process<?php }else{?>Pending<?php }?></b></td>
                  
                  </tr>
			  <?php
				  } 
				  ?>
</table>

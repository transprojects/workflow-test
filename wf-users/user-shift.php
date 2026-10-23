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
<title>TVL SHIFT ASSSIGN</title>
<meta http-equiv="refresh" content="30">
</head>
<CENTER><b>TVL SHIFT ASSSIGN</b><br/><br/>

<span style="color:red;font-weight:bolder;"><?php echo date('d-m-Y'); ?></span></CENTER><br/>

<CENTER>
</CENTER>
<form name="shift" method="post">
<input type="hidden" name="shift_date" value="<?php echo date('Y-m-d'); ?>">
<table class="center">
<tr>
				  <td colspan="8"><input type="submit" name="assign" class="label label-sm label-info" value="ASSSIGN"></td>
				  </tr>
<tr>
<th>S.no</th>
<th>Name</th>
<th>Email/Username</th>
<th>Department</th>  
<th>First Shift</th>
<th>Second Shift</th>
<th>Night Shift</th>


</tr>
 
 <?php
 $sno=1;
		$query = "SELECT u.*,um.user_role_name,dm.dept_name FROM users as u
LEFT JOIN user_role_master as um ON u.user_level = um.user_role_id 
LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id where u.approved=1 and worklocation='TVL' ORDER BY dm.dept_code,  u.full_name ASC ";

		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		
		
		?>
                  <tr >                    
                    <td><?php echo $sno; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['user_email']; ?></td>
                    <td><b><?php echo $row['dept_name']; ?></b></td>
                    <td><input type="radio" name="first_<?php echo $sno;?>_<?php echo $row['id']; ?>" id="first" value="<?php echo $row['id']; ?>"></td>
                    <td><input type="radio" name="second_<?php echo $sno;?>_<?php echo $row['id']; ?>" id="second" value="<?php echo $row['id']; ?>"></td>
                    <td><input type="radio" name="night_<?php echo $sno;?>_<?php echo $row['id']; ?>" id="night" value="<?php echo $row['id']; ?>"></td>
                    

                  </tr>
                  <?php
				  $sno++;
				  } 
				  ?>
				  
				  <tr>
				  <td colspan="8"><input type="submit" name="assign" class="label label-sm label-info" value="ASSSIGN"></td>
				  </tr>
</table>
</form>
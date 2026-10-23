<?php
require_once('vendor/dbc.php');
?>

<?php
//read data
$j_cust_id = filter($_REQUEST['j_cust_id']);
$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_cust_id = '".$j_cust_id."' ORDER BY `j_name` ASC ";

$customers = $db->query($query) or ($db->error);
?>
<option value="">-All-</option>
<?php		
while($row = $customers->fetch_assoc())
{
	?>
<option value="<?php echo $row['j_id'];?>"><?php echo $row['j_code']; ?></option>
<?php
}
?>
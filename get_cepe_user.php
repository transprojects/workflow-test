<?php include "includes/includes.php";?>


<?php
//read data
$dept_id = $_REQUEST['dept_id'];
$query = "SELECT `id`,`user_name`,`full_name`,`worklocation` FROM `users` WHERE department_id in ('1','2') and  approved='1' ORDER BY `full_name` ASC ";
$customers = $db->query($query) or ($db->error);
?>
<option value="">-Select-</option>
<?php		
while($row = $customers->fetch_assoc())
{
	?>   
<option value="<?php echo $row['id'];?>"><?php echo $row['full_name']; ?> (<b><?php echo $row['worklocation']; ?></b>)</option>
    <?php
}
?>
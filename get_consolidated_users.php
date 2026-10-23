<?php 
	 require_once('includes/includes.php');
?>

<?php
//read data
$dept_id = filter($_REQUEST['dept_id']);
$query = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$dept_id."' and  approved='1' ORDER BY `full_name` ASC ";
$customers = $db->query($query) or ($db->error);
?>
<option value="all">-All-</option>
<?php		
while($row = $customers->fetch_assoc())
{

	?>   
<option value="<?php echo $row['id'];?>"><?php echo $row['full_name']; ?></option>
    <?php
}
?>
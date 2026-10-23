<?php 
	 require_once('includes/includes.php');
?>

<?php
//read data
$dept_id = filter($_REQUEST['dept_id']);
$query = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$dept_id."' and  approved='1' ORDER BY `full_name` ASC ";
$customers = $db->query($query) or ($db->error);
?>
<option value="">-Select-</option>
<?php		
while($row = $customers->fetch_assoc())
{
			$logged_users=mysqli_num_rows(mysqli_query($db,"select * from wf_token_auth where DATE_FORMAT(start_date,'%Y-%m-%d')='".date('Y-m-d')."' and username='".$row['user_name']."'"));

	?>   
<option value="<?php echo $row['id'];?>" <?php if($logged_users==0){?> style="color:red;" <?php }else{?>style="color:green;"<?php } ?>><?php echo $row['full_name']; ?></option>
    <?php
}
?>
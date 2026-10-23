<?php
	require_once('vendor/dbc.php');
	
	$cust_name = "";
	$cust_id="";
	if(isset($_POST['cust_name']))
	{
		$cust_name = $_POST['cust_name'];
	}
	$id=$_POST['cust_id'];
	
	$sql1 = "SELECT * FROM adm_customer_master WHERE cust_name = '".$cust_name."' ";
	$cust1 = $db->query($sql1) or ($db->error);
	$row1 = $cust1->fetch_assoc();
	
	if(mysqli_num_rows($cust1)>0)
	{
		$sql2 = "SELECT * FROM adm_customer_master WHERE id = '".$id."' ";
	$cust2 = $db->query($sql2) or ($db->error);
	$row2 = $cust2->fetch_assoc();
	 if($row2['cust_name']== $row1['cust_name'])
	 {
		 echo '1';
	 }
	 else
	 {
		 echo '0';
	 }
	   
	}
	else
    {
	   echo '2';
    } 
	
	
	

?>
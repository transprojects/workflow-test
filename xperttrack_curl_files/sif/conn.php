<?php
include_once("../../global_ip_config_godaddy.php");
$servername = GODADDY_DB_IP;
//$servername = "68.178.166.2";
$username = "xperttrackadmin_sif";
$password = "VmZNwa2woQu+";
$database_name = "xperttrackadmin_sif";

// Create connection
$linuxsif_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($linuxsif_db->connect_error) {
    die("Connection failed: " . $linuxsif_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($linuxsif_db,"select * from adm_customer_master where id=7"));
$sel_com=mysqli_query($linuxsif_db,"select * from eos_settings where set_id='1'");
$fet_com=mysqli_fetch_array($sel_com);

//echo $insert['cust_id'];




// sif linux server ftp details  
$ftp_linux_server =$fet_com['set_ftp_server']; // Address of FTP server.
$ftp_linux_port = $fet_com['set_ftp_port'];
$ftp_linux_user_name = $fet_com['set_ftp_user_name'];// Username
$ftp_linux_user_pass =$fet_com['set_ftp_user_pass'];// Password
$ftp_linux_path =$fet_com['set_ftp_path'];// Password
      
// sif  linux server ftp details  



?>
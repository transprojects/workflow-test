<?php
include_once("includes/global_variables.php");
$servername = GODADDY_DB_IP;
$username = "xperttrackadmin_ksu";
$password = "2g5x(NKEl7tE";
$database_name = "xperttrackadmin_ksu";

// Create connection
$xpertrack_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($xpertrack_db->connect_error) {
    die("Connection failed: " . $xpertrack_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from adm_customer_master where id=72"));
$sel_com=mysqli_query($xpertrack_db,"select * from settings where set_id='1'");
$fet_com=mysqli_fetch_array($sel_com);

//echo $insert['cust_id'];




// client-portal linux server ftp details  
$ftp_linux_server =$fet_com['set_ftp_server']; // Address of FTP server.
$ftp_linux_port = $fet_com['set_ftp_port'];
$ftp_linux_user_name = $fet_com['set_ftp_user_name'];// Username
$ftp_linux_user_pass =$fet_com['set_ftp_user_pass'];// Password
$ftp_linux_path =$fet_com['set_ftp_path'];// Password
      
// client-portal linux server ftp details  



?>
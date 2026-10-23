<?php
date_default_timezone_set('Asia/Kolkata');
include_once("../../global_ip_config_godaddy.php");
$servername = GODADDY_DB_IP;
//$servername = "68.178.166.2";
$username = "xperttrackadmin_tsp";
$password = "2g5x(NKEl7tE";
$database_name = "xperttrackadmin_tsp";
// Create connection
$xperttrack_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($xperttrack_db->connect_error) {
    die("Connection failed: " . $xperttrack_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($xperttrack_db,"select * from adm_customer_master where id=42"));
$settings=mysqli_fetch_array(mysqli_query($xperttrack_db,"select * from settings where set_id=1"));

//echo $insert['cust_id'];


	
///-- FTP Connection Settings

    $ftp_server =$settings['set_ftp_server']; // Address of FTP server.
    $port = $settings['set_ftp_port'];
    $ftp_user_name = $settings['set_ftp_user_name'];// Username
    $ftp_user_pass =$settings['set_ftp_user_pass'];// Password
    $ftp_path =$settings['set_ftp_path'];// Password


?>
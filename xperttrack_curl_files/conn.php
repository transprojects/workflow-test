<?php
date_default_timezone_set('Asia/Kolkata');
include_once("../global_ip_config_godaddy.php");
$servername = GODADDY_DB_IP;
//$servername = "68.178.166.2";
$username = "xperttrackadmin_eos";
$password = "VmZNwa2woQu+";
$database_name = "xperttrackadmin_eos";
// Create connection
$xperttrack_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($xperttrack_db->connect_error) {
    die("Connection failed: " . $xperttrack_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($xperttrack_db,"select * from adm_customer_master where id=66"));
$settings=mysqli_fetch_array(mysqli_query($xperttrack_db,"select * from eos_settings where set_id=1"));

//echo $insert['cust_id'];


	
///-- FTP Connection Settings

    $ftp_server =$settings['set_ftp_server']; // Address of FTP server.
    $port = $settings['set_ftp_port'];
    $ftp_user_name = $settings['set_ftp_user_name'];// Username
    $ftp_user_pass =$settings['set_ftp_user_pass'];// Password
    $ftp_path =$settings['set_ftp_path'];// Password


///-- FTP Connection Settings

    $ftp_editor_server =$settings['set_editor_ftp_server']; // Address of FTP server.
    $port_editor = $settings['set_editor_ftp_port'];
    $ftp_editor_user_name = $settings['set_editor_ftp_user_name'];// Username
    $ftp_editor_user_pass =$settings['set_editor_ftp_user_pass'];// Password
    $ftp_editor_path =$settings['set_editor_ftp_path'];// Password
?>
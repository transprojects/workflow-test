<?php
include_once("includes/global_variables.php");
$servername = GODADDY_DB_IP;
$username = "xperttrackadmin_csiro";
$password = "&5Cq5h}f7SIb[2b)";
$database_name = "xperttrackadmin_csiro";

// Create connection
$xpertrack_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($xpertrack_db->connect_error) {
    die("Connection failed: " . $xpertrack_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from adm_customer_master where id=79"));
$sel_com=mysqli_query($xpertrack_db,"select * from eos_settings where set_id='1'");
$settings=mysqli_fetch_array($sel_com);

echo $insert['cust_id'];




	
///-- FTP Connection Settings

    $ftp_linux_server =$settings['set_ftp_server']; // Address of FTP server.
    $ftp_linux_port = $settings['set_ftp_port'];
    $ftp_linux_user_name = $settings['set_ftp_user_name'];// Username
    $ftp_linux_user_pass =$settings['set_ftp_user_pass'];// Password
    $ftp_linux_path =$settings['set_ftp_path'];// Password


///-- FTP EDITOR Connection Settings

    $ftp_editor_server =$settings['set_editor_ftp_server']; // Address of FTP server.
    $port_editor = $settings['set_editor_ftp_port'];
    $ftp_editor_user_name = $settings['set_editor_ftp_user_name'];// Username
    $ftp_editor_user_pass =$settings['set_editor_ftp_user_pass'];// Password
    $ftp_editor_path =$settings['set_editor_ftp_path'];// Password
	
	
	
///-- FTP RAW Connection Settings

    $ftp_demo_server =$settings['set_demo_ftp_server']; // Address of FTP server.
    $port_demo = $settings['set_demo_ftp_port'];
    $ftp_demo_user_name = $settings['set_demo_ftp_user_name'];// Username
    $ftp_demo_user_pass =$settings['set_demo_ftp_user_pass'];// Password
    $ftp_demo_path =$settings['set_demo_ftp_path'];// Password



?>
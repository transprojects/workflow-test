<?php
$servername = "52.23.135.119";
$username = "root";
$password = "qNy63RoHjTMLgEgK";
$database_name = "workflow";
// Create connection
$awsdb = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($awsdb->connect_error) {
    die("AWS PROOFING SERVER Connection failed: " . $awsdb->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($awsdb,"select * from adm_customer_master where id=42"));

//echo $insert['cust_id'];


	
//-- Connection Settings
    $ftp_server = "52.23.135.119"; // Address of FTP server.
    $port = 22;
    $ftp_user_name = "jmir"; // Username
    $ftp_user_pass = 'TransJmir@2019'; // Password
	
	// define('WF_DIR','E:/New_Workflow_Files/JOURNAL');

?>
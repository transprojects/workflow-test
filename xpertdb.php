<?php
$servername = "xpertreview.net";
$username = "xpertrev_spring";
$password = "welcome#123";
$database_name = "xpertrev_springer";
// Create connection
$awsdb = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($awsdb->connect_error) {
    die("Connection failed: " . $awsdb->connect_error);
}




$xpert=mysqli_fetch_array(mysqli_query($awsdb,"select * from admin"));

	echo $xpert['name'];
//-- Connection Settings
    $ftp_server = "132.148.148.211"; // Address of FTP server.
    $port = 21;
    $ftp_user_name = "transx"; // Username
    $ftp_user_pass = 'welcome#123'; // Password
	
	// define('WF_DIR','E:/New_Workflow_Files/JOURNAL');

?>
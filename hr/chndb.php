<?php
global $db;

$servername = "10.10.10.117";
$username = "root";
$password = "root@123";
$database_name = "workflow";
// Create connection
$db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}


//$insert=mysqli_fetch_array(mysqli_query($db,"select * from adm_customer_master where id=42"));

//echo $insert['cust_id'];

$dbhost = '10.10.10.117';

// Database Name
$dbname = 'workflow';

// Database Username
$dbuser = 'root';

// Database Password
$dbpass = 'root@123';

// Defining base url

// Getting Admin url

try {
	$pdo = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch( PDOException $ex ) {
    echo "Connection error :" . $ex->getMessage();
}
?>
<?php
ob_start();
session_start();

// Error Reporting Turn On
ini_set('error_reporting', E_ALL);

// Setting up the time zone
date_default_timezone_set('Asia/Kolkata');

// Host Name
$dbhost = 'localhost';

// Database Name
$dbname = 'workflow_test';

// Database Username
$dbuser = 'root';

// Database Password
$dbpass = 'root@123';

// Defining base url
define("BASE_URL", "http://localhost/workflow-new/");

// Getting Admin url
define("ADMIN_URL", BASE_URL . "" . "/");

try {
	$pdo = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch( PDOException $ex ) {
    echo "Connection error :" . $ex->getMessage();
}


global $db;

$db=mysqli_connect("localhost","$dbuser","$dbpass","$dbname");
// Check connection
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
?>
<?php
ob_start();
session_start();

// Error Reporting Turn On
ini_set('error_reporting', E_ALL);

// Setting up the time zone
date_default_timezone_set('Asia/Kolkata');

// Database credentials
$dbhost = '10.10.10.94';
$dbname = 'trans_demo';
$dbuser = 'root';
$dbpass = 'root@123';

// ---- PDO Connection ----
try {
    $pdo = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>✅ PDO Connection successful!</p>";
} catch (PDOException $ex) {
    echo "<p style='color:red;'>❌ PDO Connection error: " . $ex->getMessage() . "</p>";
}

// ---- MySQLi Connection ----
global $db;
$db = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (mysqli_connect_errno()) {
    echo "<p style='color:red;'>❌ MySQLi Connection error: " . mysqli_connect_error() . "</p>";
} else {
    echo "<p style='color:green;'>✅ MySQLi Connection successful!</p>";
}
?>

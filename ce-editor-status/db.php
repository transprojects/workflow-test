<?php
$servername = "10.10.10.109";
$username = "root";
$password = "root@123";
$db="trans_demo";
$conn = new mysqli($servername, $username, $password,$db);

// if ($conn->connect_error) {
//   die("Connection failed: " . $conn->connect_error);
// }
// echo "Connected successfully";
?>
<?php
$host = 'db-mysql-se-ap-southeast-2-stagingf208b1f.cfycuweoun5v.ap-southeast-2.rds.amazonaws.com';
$port = 3306;
$username = 'seSTAGING';
$password = 'kVcoIwaTQLmERKdXJtGw';
$dbname = 'seSTAGING';

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!";
?>

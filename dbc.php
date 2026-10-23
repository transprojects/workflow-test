<?php
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');


define ("DB_HOST", "localhost"); // set database host
define ("DB_USER", "root"); // set database user
define ("DB_PASS","root@123"); // set database password
define ("DB_NAME","workflow"); // set database name


//Connect with the database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_errno) {
    printf("Connect failed: %s\n", $db->connect_error);
    exit();
}
mysqli_query($db,"SET sql_mode = ''");

$email_setting=mysqli_fetch_array(mysqli_query($db,"select * from email_settings where id=3"));

// function filter($data) {
// 	$data = trim(htmlentities(strip_tags($data)));
	
// 	return $data;
// }


?>

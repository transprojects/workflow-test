<?php
include_once("includes/global_variables.php");
$servername = GODADDY_DB_IP;
$username = "mytickettransfor_system";
$password = "1Dvwp3u6%4Kb";
$database_name = "mytickettransfor_system";

// Create connection
$mytickets_db = new mysqli($servername, $username, $password, $database_name);

// Check connection
if ($mytickets_db->connect_error) {
    die("❌ Connection failed: " . $mytickets_db->connect_error);
} else {
  //  echo "✅ Connection successful to the database: " . $database_name;
}

// Set character set to UTF-8 for better international support
if (!$mytickets_db->set_charset("utf8mb4")) {
    die("❌ Error loading character set utf8mb4: " . $mytickets_db->error);
} else {
  //  echo "✅ Character set successfully set to UTF-8.";
}

      



?>
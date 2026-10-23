<?php
//------------------------
// MySQL Connection
//------------------------
$servername = "184.168.31.102";
$username = "xpertproof_workflow";
$password = "qNy63RoHjTMLgEgK";
$database_name = "xpertproof_workflow";

// Create connection
$clouddb = new mysqli($servername, $username, $password, $database_name);

// Check connection
if ($clouddb->connect_error) {
    die("XPERTPROOF PROOFING SERVER Connection failed: " . $clouddb->connect_error);
}
echo "Database connected successfully.<br>";

// Fetch customer info
$insert = mysqli_fetch_array(mysqli_query($clouddb, "SELECT * FROM adm_customer_master WHERE id=42"));
if ($insert) {
    echo "Customer ID: " . $insert['cust_id'] . "<br>";
} else {
    echo "No customer found with ID 42.<br>";
}

// Initialize logging
$log = [];

function logMessage(&$log, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $log[] = "[$timestamp] $message";
}
//------------------------
// FTP Connection
//------------------------
$ftp_server = "xpertproof.com";  // FTP server
$port = 21;                      // FTP port
$ftp_user_name = "transformafp@xpertproof.com";  // FTP username
$ftp_user_pass = 'hWVYV]ku2Vx-TB0j';            // FTP password


/* 
// Connect to FTP server
$ftp_conn = @ftp_connect($ftp_server, $port, 10); // 10s timeout
if (!$ftp_conn) {
    die("FTP Connection failed to $ftp_server on port $port.");
}

// Login to FTP
if (!@ftp_login($ftp_conn, $ftp_user_name, $ftp_user_pass)) {
    ftp_close($ftp_conn);
    die("FTP Authentication failed for user $ftp_user_name.");
}

// Optional: Check passive mode
ftp_pasv($ftp_conn, true);

echo "FTP connected and logged in successfully.<br>";



//------------------------
// Create a Test File
//------------------------
$local_file = sys_get_temp_dir() . "/test_file_" . time() . ".txt";
file_put_contents($local_file, "This is a test file created at " . date('Y-m-d H:i:s'));
echo "Local test file created: $local_file<br>";

//------------------------
// Upload to FTP IN folder
//------------------------
$remote_dir = "/IN";  // make sure this folder exists on FTP
$remote_file = $remote_dir . "/test_file_" . time() . ".txt";

if (@ftp_put($ftp_conn, $remote_file, $local_file, FTP_ASCII)) {
    echo "File uploaded successfully to $remote_file<br>";
} else {
    echo "Failed to upload file to $remote_file<br>";
}

// Cleanup local temp file
@unlink($local_file);
 */
?>

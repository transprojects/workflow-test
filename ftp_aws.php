<?php
$ftp_server = "52.23.135.119";
$ftp_user_name = "jmir";
$ftp_user_pass = 'TransJmir@2019';
$port = 21; // default FTP port

// Connect to FTP server
$conn_id = ftp_connect($ftp_server, $port, 10); // 10s timeout
if (!$conn_id) {
    die("Could not connect to $ftp_server on port $port");
}

// Login
if (!ftp_login($conn_id, $ftp_user_name, $ftp_user_pass)) {
    die("FTP Authentication failed for user $ftp_user_name");
}

// Enable passive mode
ftp_pasv($conn_id, true);

echo "FTP connection successful!";

// Close connection
ftp_close($conn_id);
?>

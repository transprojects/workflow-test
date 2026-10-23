<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FTP details
$ftp_host = "ftp.edpsaga.org";
$ftp_user = "transforma";
$ftp_pass = "HLc4txG1";
$ftp_port = 21;

// Target directory (CONFIRMED from your screenshot)
$target_dir = "/";

// Logger
function logMsg($msg) {
    echo "[" . date("H:i:s") . "] $msg<br>";
}

// CONNECT
logMsg("Connecting to FTP...");
$conn = ftp_connect($ftp_host, $ftp_port, 30);

if (!$conn) {
    logMsg("❌ Connection failed");
    exit;
}
logMsg("✅ Connected");

// LOGIN
if (!ftp_login($conn, $ftp_user, $ftp_pass)) {
    logMsg("❌ Login failed");
    ftp_close($conn);
    exit;
}
logMsg("✅ Login successful");

// PASSIVE MODE
ftp_pasv($conn, true);
logMsg("✅ Passive mode enabled");

// CHANGE DIRECTORY
logMsg("Changing to directory: $target_dir");

if (!ftp_chdir($conn, $target_dir)) {
    logMsg("❌ Cannot access /check_test");
    ftp_close($conn);
    exit;
}
logMsg("✅ Inside check_test folder");

// LIST FILES (RAW LIST - RELIABLE)
logMsg("Fetching file list...");

$raw_list = ftp_rawlist($conn, ".");

echo "<pre>";
print_r($raw_list);
echo "</pre>";

// TEST FILE
$test_file = "ftp_test_" . time() . ".txt";
$tmp_local = tempnam(sys_get_temp_dir(), "ftp");
file_put_contents($tmp_local, "FTP Test File");

// UPLOAD
logMsg("Uploading test file...");

if (ftp_put($conn, $test_file, $tmp_local, FTP_BINARY)) {

    logMsg("✅ Upload successful");

    sleep(2); // FTP delay handling

    // VERIFY USING RAW LIST
    $raw_list = ftp_rawlist($conn, ".");
    $found = false;

    foreach ($raw_list as $line) {
        if (strpos($line, $test_file) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        logMsg("✅ File verified on server");
    } else {
        logMsg("⚠️ File not visible (server delay or restriction)");
    }

    // DELETE USING FULL PATH
    $full_path = $target_dir . "/" . $test_file;

    if (@ftp_delete($conn, $full_path)) {
        logMsg("🧹 File deleted successfully");
    } else {
        logMsg("⚠️ Delete failed (may need permission)");
    }

    unlink($tmp_local);

} else {
    logMsg("❌ Upload failed");
}

// CLOSE CONNECTION
ftp_close($conn);
logMsg("🔌 FTP connection closed");
?>
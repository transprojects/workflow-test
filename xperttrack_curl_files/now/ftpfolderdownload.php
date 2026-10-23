<?php

function downloadDirectory($ftpConnection, $remoteDir, $localDir)
{
    // Change the remote directory
    ftp_chdir($ftpConnection, $remoteDir);
	
	$success = true;

    // Get the list of files and directories in the current remote directory
    $files = ftp_nlist($ftpConnection, ".");

    foreach ($files as $file) {
        // Skip the current and parent directory links
        if ($file == "." || $file == "..") {
            continue;
        }

        // Construct the remote and local paths
        $remotePath = $remoteDir . '/' . $file;
        $localPath = $localDir . '/' . $file;

        // Check if it's a directory
        if (ftp_size($ftpConnection, $remotePath) == -1) {
            // If it's a directory, create the local directory if it doesn't exist
            if (!is_dir($localPath)) {
                mkdir($localPath);
            }

            // Recursively download the contents of the remote directory
            $success = $success && downloadDirectory($ftpConnection, $remotePath, $localPath);
        } else {
            // Download the file
            ftp_get($ftpConnection, $localPath, $remotePath, FTP_BINARY);
        }
    }
	
	return $success;
}

// FTP connection settings

//$ftp_server = "52.23.135.119";
//$ftp_username = "NOW Publishers";
//$ftp_password = 'T$p@2023';	

// Local and remote directories
//$localDirectory = "E:/Transforma/NOW Publishers_CMC_42711/";
//$remoteDirectory = "/production/JOURNALS/NOW Publishers/CMC/Vol00000/NOW Publishers_CMC_42711/FP/Proofing/";

// Connect to FTP server
$ftpConnection = ftp_connect($ftp_server);
if (!$ftpConnection) {
    die("Could not connect to $ftp_server");
}

// Login to FTP
$login_result = ftp_login($ftpConnection, $ftp_username, $ftp_password);
if (!$login_result) {
    die("FTP login failed");
}

// Call the function to download the directory
$success = downloadDirectory($ftpConnection, $remoteDirectory, $localDirectory);

// Close FTP connection
ftp_close($ftpConnection);

if ($success) {
    echo 'Download completed successfully';
} else {
    echo 'Download failed';
}


?>


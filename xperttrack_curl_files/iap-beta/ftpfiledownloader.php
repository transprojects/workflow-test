<?php
$max_attempts = 3;

foreach ($file_paths as $remote_file) {
    $attempt = 0;

    do {
        $attempt++;
        $ftp_conn = ftp_connect($ftp_server);
        if (!$ftp_conn) {
            die("Failed to connect to FTP server");
        }

        $login = ftp_login($ftp_conn, $ftp_username, $ftp_password);
        if (!$login) {
            die("Failed to login to FTP server");
        }

        $local_file = $localDirectory .'/'. basename($remote_file); // Use the remote file's name as the local file name

        if (ftp_get($ftp_conn, $local_file, $remote_file, FTP_BINARY)) {
            //echo "File '$local_file' downloaded successfully.\n";
            
            // Check the local file size
            $local_size = filesize($local_file);
            $remote_size = ftp_size($ftp_conn, $remote_file);

            if ($local_size === $remote_size) {
                //echo "File size matches.\n";
                break; // Exit the loop if sizes match
            } else {
                //echo "File size mismatch. Retrying...\n";
                unlink($local_file); // Remove the incomplete file
            }
        } else {
            //echo "Failed to download file '$local_file'. Retrying...\n";
        }

        ftp_close($ftp_conn);

        if ($attempt >= $max_attempts) {
            //echo "Max download attempts reached for file '$local_file'. Moving to the next file...\n";
			$file_upload_status = 'File Download Error';
            break;
        }

        sleep(5); // Wait for a few seconds before retrying
    } while (true);
}
?>

<?php
$servername = "68.178.166.2";
$username = "editlatex_trans_editor";
$password = "cXSWD3q?)fJJ";
$database_name = "editlatex_trans_editor";

// Create connection
$editlatex_db = new mysqli($servername, $username, $password,$database_name);

// Check connection
if ($editlatex_db->connect_error) {
    die("Connection failed: " . $editlatex_db->connect_error);
}

$insert=mysqli_fetch_array(mysqli_query($editlatex_db,"select * from  files where id=14058"));

echo $insert['file_name'];




// edit latex linux server ftp details  
$ftp_linux_server ='editlatex.transforma.in'; // Address of FTP server.
$ftp_linux_port ='';
$ftp_linux_user_name ='sif@editlatex.transforma.in';// Username
$ftp_linux_user_pass ='H1Qjhh{Y~y+U';// Password
$ftp_linux_path ='';// path
      
// edit latex linux server ftp details  



function zipFolder($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE)) {
        return false;
    }

    $source = realpath($source);

    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = realpath($file);
            if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } elseif (is_file($file)) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } elseif (is_file($source)) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}


?>
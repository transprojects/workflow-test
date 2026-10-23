<?php


include ('awsdb.php'); 


// (A) SETTINGS - CHANGE TO YOUR OWN!
$ftphost = $ftp_server;
$ftpuser = $ftp_user_name;
$ftppass = $ftp_user_pass;
$source = "/Out/".$crow['cust_name']."/".$jninfo['j_code']."/".$app_res['pub_id']."/";
// $source = "/Out/Transforma/TEST-04/Test_01";
 $destination_server = "//10.10.10.94/d$/Localserver/Apache/htdocs/New_Workflow_Files/JOURNAL/production/JOURNALS/".$crow['cust_name']."/".$jninfo['j_code']."/Vol00000/".$app_res['pub_id']."/FP/";
 $destination = "C:/LAMP/Apache/htdocs/workflow/FTP_Download/";

 
if (!(is_dir($destination_server))) 
{
	if (!mkdir($destination_server, 0777, true)) 
	{
		die('Failed to create folders in 10.10.10.94 Server...'.$$destination_server);
	}
}
		
		
// (B) CONNECT & LOGIN TO FTP SERVER
$ftp = ftp_connect($ftphost) or die("Failed to connect to $ftphost");
if (!ftp_login($ftp, $ftpuser, $ftppass)) {
  ftp_close($ftp);
  die("Invalid user/password");
}
 
// (C) DOWNLOAD EVERYTHING
function ftpGetAll ($path,$destination,$foldername) {
  // (C1) FTP OBJECT & DESTINATION FOLDER
  global $ftp;
  global $destination_server;
  global $destination;
 
  // (C2) CREATE FOLDER ON LOCAL SERVER
  // $saveTo = $path=="/" ? $destination : $destination . $path;
 
	$saveTo = $destination;
  $savefolder= $foldername;
 
 if (!file_exists($saveTo)) {
    if (mkdir($saveTo)) { echo "$saveTo created\r\n"; }
    else { echo "Error creating $saveTo\r\n"; return false; }
  }
  if (!file_exists($destination_server.$savefolder)) {
    if (mkdir($destination_server.$savefolder)) { echo "$destination_server.$savefolder created\r\n"; }
    else { echo "Error creating $destination_server.$savefolder\r\n"; return false; }
  }
 
  ftp_set_option($ftp, FTP_USEPASVADDRESS, false);
	
	// Switched to passive mode after logging in
			ftp_pasv($ftp, true);
			
			
			
  $files = ftp_mlsd($ftp, $path);
  
 
  if (count($files)!=0) { foreach ($files as $f) {
    // (C4) FOLDER - RECURSIVE LOOP
    if ($f["type"]=="dir") { ftpGetAll($path . $f["name"] . "/",$saveTo.$f["name"] . "/",$savefolder."/".$f["name"]. "/"); }
 
    // (C5) FILE - DOWNLOAD
    else {
		
			
      ftp_get($ftp, $saveTo . $f["name"], $path . $f["name"], FTP_BINARY)
      ? "Saved to " . $saveTo . $f["name"] . "\r\n"
      : "Error downloading " . $path . $f["name"] . "\r\n" ;
	  
	  rename($saveTo . $f["name"],$destination_server.$savefolder. $f["name"]);
    }
  }}
}
ftpGetAll($source,$destination,"");
 
// (D) CLOSE FTP CONNECTION
ftp_close($ftp);
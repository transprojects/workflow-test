<?php
date_default_timezone_set('Asia/Kolkata');

/************* MYSQL DATABASE SETTINGS *****************
1. Specify Database name in $dbname
2. MySQL host (localhost or remotehost)
3. MySQL user name with ALL previleges assigned.
4. MySQL password

Note: If you use cpanel, the name will be like account_database
*************************************************************/
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

define ("DB_HOST", "localhost"); // set database host
define ("DB_USER", "root"); // set database user
define ("DB_PASS","root@123"); // set database password
define ("DB_NAME","workflow"); // set database name
//Production Folder Structure ///
// define ("WF_DIR","J:/JOURNAL"); // set WF DIR Path
//define ("WF_DIR","//fileserver/New_Workflow/JOURNAL"); // set WF DIR Path

//Connect with the database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_errno) {
    printf("Connect failed: %s\n", $db->connect_error);
    exit();
}


/*  define ("CHN_DB_HOST", "192.168.0.3"); // set database host
define ("CHN_DB_USER", "root"); // set database user
define ("CHN_DB_PASS","root@123"); // set database password
define ("CHN_DB_NAME","workflow"); // set database name
//Production Folder Structure ///
// define ("WF_DIR","J:/JOURNAL"); // set WF DIR Path
//define ("WF_DIR","//fileserver/New_Workflow/JOURNAL"); // set WF DIR Path

//Connect with the database
$db_chn = new mysqli(CHN_DB_HOST, CHN_DB_USER, CHN_DB_PASS, CHN_DB_NAME);

if ($db_chn->connect_errno) {
    printf("Connect failed: %s\n", $db_chn->connect_error);
    exit();
} 
mysqli_query($db_chn,"SET sql_mode = ''");*/
 

include("global_variables.php");
error_reporting(0);

//define ("ADMIN_NAME", "admin"); // sp
mysqli_query($db,"SET sql_mode = ''");


?>
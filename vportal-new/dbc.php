<?php
/************* MYSQL DATABASE SETTINGS *****************
1. Specify Database name in $dbname
2. MySQL host (localhost or remotehost)
3. MySQL user name with ALL previleges assigned.
4. MySQL password

Note: If you use cpanel, the name will be like account_database
*************************************************************/

define ("DB_HOST", "localhost"); // set database host
define ("DB_USER", "root"); // set database user
define ("DB_PASS","root@123"); // set database password
define ("DB_NAME","workflow"); // set database name

//Production Folder Structure ///
// define ("WF_DIR","J:/JOURNAL"); // set WF DIR Path
//define ("WF_DIR","//fileserver/New_Workflow/JOURNAL_TEST"); // set WF DIR Path

//Connect with the database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_errno) {
    printf("Connect failed: %s\n", $db->connect_error);
    exit();
}
include("global_variables.php");
error_reporting(0);
/* Registration Type (Automatic or Manual) 
 1 -> Automatic Registration (Users will receive activation code and they will be automatically approved after clicking activation link)
 0 -> Manual Approval (Users will not receive activation code and you will need to approve every user manually)
*/
$user_registration = 1;  // set 0 or 1

define("COOKIE_TIME_OUT", 10); //specify cookie timeout in days (default is 10 days)
define('SALT_LENGTH', 9); // salt for password

//define ("ADMIN_NAME", "admin"); // sp

/// Portal Proect Status Types
$project_status_array =array(1=>'Completed',2=>'Pending',3=>'Lunch',4=>'Break',5=>'Need Clarification');

/// Poject stages array
//$project_status_types =array('FP'=>'FirstProof','REV'=>'Revises','FIN'=>'Final','Sample'=>'Sample','CE'=>'CopyEditing','G'=>'Graphics','I'=>'Instructions');
$project_status_types =array('FP'=>'FirstProof','REV'=>'Revises','FIN'=>'Final','ISSCOR'=>'Issue','Sample'=>'Sample');

/// complexity_types array ///
$complexity_types =array('Regular','Complex');
$work_loactions =array('CHN'=>"Chennai",'TVL'=>"Tirunelveli");

/// Color Details array ///
$book_color_types =array('4C', '2C', 'BW');

/// Platform Details array ///
$book_platform_types =array('InDesign', 'TeX', '3B2','Others');


/* Specify user levels */
define ("ADMIN_LEVEL", 1);
define ("MANAGER_LEVEL", 2);
define ("CONTRACTER_LEVEL", 3);
define ("EMPLOYEE_LEVEL", 5);
define ("USER_LEVEL", 4);
define ("GUEST_LEVEL", 0);

/// Portal User Types
$user_types =array(0=>'All',1=>'Portal Admin', 2=>'Manager',3=>'Contractor',4=>'Employee');


/*************** reCAPTCHA KEYS****************/
$publickey = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
$privatekey = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";


/**** PAGE PROTECT CODE  ********************************
This code protects pages to only logged in users. If users have not logged in then it will redirect to login page.
If you want to add a new page and want to login protect, COPY this from this to END marker.
Remember this code must be placed on very top of any html or php page.
********************************************************/

function page_protect() {
session_start();

global $db; 

/* Secure against Session Hijacking by checking user agent */
if (isset($_SESSION['HTTP_USER_AGENT']))
{
    if ($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT']))
    {
        logout();
        exit;
    }
}

// before we allow sessions, we need to check authentication key - ckey and ctime stored in database

/* If session not set, check for cookies set by Remember me */
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name']) ) 
{
	if(isset($_COOKIE['user_id']) && isset($_COOKIE['user_key'])){
	/* we double check cookie expiry time against stored in database */
	
	$cookie_user_id  = filter($_COOKIE['user_id']);
	$rs_ctime = $db->query("select `ckey`,`ctime` from `users` where `id` ='$cookie_user_id'") or die($db->error);
	$rs_ctime_rs = $rs_ctime->fetch_assoc();
	$ckey = $rs_ctime_rs['ckey'];
	$ctime = $rs_ctime_rs['ctime']; 
	// coookie expiry
	if( (time() - $ctime) > 60*60*24*COOKIE_TIME_OUT) {

		logout();
		}
/* Security check with untrusted cookies - dont trust value stored in cookie. 		
/* We also do authentication check of the `ckey` stored in cookie matches that stored in database during login*/

	 if( !empty($ckey) && is_numeric($_COOKIE['user_id']) && isUserID($_COOKIE['user_name']) && $_COOKIE['user_key'] == sha1($ckey)  ) {
	 	  session_regenerate_id(); //against session fixation attacks.
	
		  $_SESSION['user_id'] = $_COOKIE['user_id'];
		  $_SESSION['user_name'] = $_COOKIE['user_name'];
		/* query user level from database instead of storing in cookies */	
		  list($user_level) = mysql_fetch_row($db->query("select user_level from users where id='$_SESSION[user_id]'"));

		  $_SESSION['user_level'] = $user_level;
		  $_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT']);
		  
	   } else {
	   logout();
	   }

  } else {
	header("Location: index.php");
	exit();
	}
}
}



function filter($data) {
	$data = trim(htmlentities(strip_tags($data)));
	
	
	
	
	return $data;
}



function EncodeURL($url)
{
$new = strtolower(ereg_replace(' ','_',$url));
return($new);
}

function DecodeURL($url)
{
$new = ucwords(ereg_replace('_',' ',$url));
return($new);
}

function ChopStr($str, $len) 
{
    if (strlen($str) < $len)
        return $str;

    $str = substr($str,0,$len);
    if ($spc_pos = strrpos($str," "))
            $str = substr($str,0,$spc_pos);

    return $str . "...";
}	

function isEmail($email){
  return preg_match('/^\S+@[\w\d.-]{2,}\.[\w]{2,6}$/iU', $email) ? TRUE : FALSE;
}

function isUserID($username)
{
	if (preg_match('/^[a-z\d_]{5,20}$/i', $username)) {
		return true;
	} else {
		return false;
	}
 }	
 
function isURL($url) 
{
	if (preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) {
		return true;
	} else {
		return false;
	}
} 

function checkPwd($x,$y) 
{
if(empty($x) || empty($y) ) { return false; }
if (strlen($x) < 4 || strlen($y) < 4) { return false; }

if (strcmp($x,$y) != 0) {
 return false;
 } 
return true;
}

function GenPwd($length = 7)
{
  $password = "";
  $possible = "0123456789bcdfghjkmnpqrstvwxyz"; //no vowels
  
  $i = 0; 
    
  while ($i < $length) { 

    
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
       
    
    if (!strstr($password, $char)) { 
      $password .= $char;
      $i++;
    }

  }

  return $password;

}

function GenKey($length = 7)
{
  $password = "";
  $possible = "0123456789abcdefghijkmnopqrstuvwxyz"; 
  
  $i = 0; 
    
  while ($i < $length) { 

    
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
       
    
    if (!strstr($password, $char)) { 
      $password .= $char;
      $i++;
    }

  }

  return $password;

}


function logout()
{
global $db;
session_start();

$sess_user_id = strip_tags($_SESSION['user_id']);
$cook_user_id = strip_tags($_COOKIE['user_id']);

if(isset($sess_user_id) || isset($cook_user_id)) {
$db->query("update `users` 
			set `ckey`= '', `ctime`= '' 
			where `id`='$sess_user_id' OR  `id` = '$cook_user_id'") or die($db->error);
}		

/************ Delete the sessions****************/
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_level']);
unset($_SESSION['HTTP_USER_AGENT']);
session_unset();
session_destroy(); 

/* Delete the cookies*******************/
setcookie("user_id", '', time()-60*60*24*COOKIE_TIME_OUT, "/");
setcookie("user_name", '', time()-60*60*24*COOKIE_TIME_OUT, "/");
setcookie("user_key", '', time()-60*60*24*COOKIE_TIME_OUT, "/");

header("Location: index.php");
}

// Password and salt generation
function PwdHash($pwd, $salt = null)
{
    if ($salt === null)     {
        $salt = substr(md5(uniqid(rand(), true)), 0, SALT_LENGTH);
    }
    else     {
        $salt = substr($salt, 0, SALT_LENGTH);
    }
    return $salt . sha1($pwd . $salt);
}

function checkAdmin() {

if($_SESSION['user_level'] == ADMIN_LEVEL) {
return 1;
} else { return 0 ;
}

}

//////////////////// Common Dat Format //////////////

function convert_mdy_ymd($date)
{
	$dmdy =  date("Y-m-d", strtotime($date)); 
	return $dmdy;
}

function convert_ymd_dmy($date)
{
	$dmdy =  date("d-m-Y", strtotime($date)); 
	return $dmdy ;
}

?>

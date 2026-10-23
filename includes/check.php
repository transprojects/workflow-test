<?php
ob_start();
session_start();
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set("Asia/Calcutta");
if(!isset($_COOKIE['user_id']))
	echo '<script>window.location.replace("'.$_COOKIE['set_url'].'/index");</script>';

require_once "includes/Auth.php";
require_once "includes/Util.php";
$auth = new Auth();
$db_handle = new DBController();
$util = new Util();

require_once "includes/function.php";
require_once "includes/authCookieSessionValidate.php";

$sel_setting = $auth->showSelectSetting();
//include ('dbc.php'); 
include("smtpmail/phpmailer/class.phpmailer.php");

?>
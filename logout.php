<?php
require "includes/Util.php";
$util = new Util();

// clear cookies
$util->clearAuthCookie();

session_start();
session_destroy();
echo "<script>window.location='./index';</script>";
?>
<?php
require "includes/Util.php";
$util = new Util();

// clear cookies
$util->clearAuthCookie();
echo "<script>window.location='./index';</script>";
?>
<?php
session_name('tooltester');
session_start();
$_SESSION = array();
session_destroy();
header('Location: login.php');

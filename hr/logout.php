<?php 
include 'includes/conn.php'; 
unset($_SESSION['user']);
header("location: index.php"); 
?>
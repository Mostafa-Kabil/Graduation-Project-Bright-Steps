<?php
session_start();
$_SESSION['id'] = 71; // Using specialist 71 as an example based on earlier tests
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['ajax'] = '1';
$_GET['section'] = 'settings';
$_GET['action'] = 'get_profile';
include 'doctor-dashboard.php';
?>

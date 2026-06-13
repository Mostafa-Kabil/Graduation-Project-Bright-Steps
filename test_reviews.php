<?php
session_start();
$_SESSION['id'] = 9;
$_SESSION['role'] = 'parent';
$_SESSION['email'] = 'parent@test.com';
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'api_specialists.php';
?>

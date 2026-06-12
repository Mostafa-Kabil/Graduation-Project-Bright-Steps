<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['id'] = 9; // mostafakabil123
$_SESSION['role'] = 'parent';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['child_id'] = 1; // Assuming child 1 exists
$_POST['weight'] = 12.5;
$_POST['height'] = 85.0;
include 'api_add_growth.php';
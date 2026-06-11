<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['id'] = 9; // mostafakabil123
$_SESSION['role'] = 'parent';
$_GET['action'] = 'get_offers';
include 'api_points_engine.php';

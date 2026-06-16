<?php
session_start();
$_SESSION['id'] = 3;
$_SESSION['role'] = 'parent';
$_GET['action'] = 'recommend';
$_GET['child_id'] = ;
$_GET['force'] = 1;
require 'api_activities.php';

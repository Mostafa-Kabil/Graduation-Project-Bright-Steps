<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['role'] = 'parent';
$_GET['action'] = 'recommend';
$_GET['child_id'] = 1;
$_GET['force'] = 1;
require 'api_activities.php';

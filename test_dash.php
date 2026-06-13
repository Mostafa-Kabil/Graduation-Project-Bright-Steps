<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['role'] = 'parent';
$_GET['parent_id'] = 4;
require 'api_get_dashboard.php';

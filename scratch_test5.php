<?php
session_start();
$_GET['child_id'] = 1;
$_SESSION['id'] = 3;
$_SESSION['role'] = 'specialist';
require 'api_get_child_full_profile.php';

<?php
session_start();
$_SESSION['id'] = 3;
$_SESSION['role'] = 'specialist';
$_GET['child_id'] = 1; // Assuming 1 exists
require 'api_get_child_full_profile.php';

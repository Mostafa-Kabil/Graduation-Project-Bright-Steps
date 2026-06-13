<?php
session_start();
$_SESSION['id'] = 129; // Clinic admin ID
$_SESSION['role'] = 'clinic';
$_GET['specialist_id'] = 70;
require_once 'api_get_specialist_details.php';

<?php
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['ajax'] = 1;
$_GET['section'] = 'patients';
$_GET['action'] = 'get_patient_detail';
$_GET['specialist_id'] = 40; // Sarah Jenkins from previous query
$_GET['child_id'] = 1; // Assuming a child id
require 'doctor-dashboard.php';
?>

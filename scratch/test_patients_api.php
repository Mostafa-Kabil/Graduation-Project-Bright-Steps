<?php
$_GET['ajax'] = 1;
$_GET['section'] = 'patients';
$_GET['action'] = 'get_patients';
$_GET['specialist_id'] = 15;
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'doctor-dashboard.php';

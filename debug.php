<?php
$_GET['ajax'] = 1;
$_GET['section'] = 'specialists';
$_GET['action'] = 'get_specialists';
$_GET['clinic_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'clinic-dashboard.php';

<?php
require 'connection.php';
$_SESSION['id'] = 5100;
$_SESSION['role'] = 'doctor';
$_GET['ajax'] = 1;
$_GET['action'] = 'get_profile';
$_SERVER['REQUEST_METHOD'] = 'GET';
include 'dr-settings.php';

<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['role'] = 'parent';
$_GET['specialist_id'] = 47;
$_GET['date'] = '2026-06-16';
require 'api/api_get_specialist_slots.php';

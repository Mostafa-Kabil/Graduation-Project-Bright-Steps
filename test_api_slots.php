<?php
$_GET['specialist_id'] = 71;
$_GET['date'] = '2026-06-16'; // Tuesday

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['id'] = 1;
$_SESSION['role'] = 'parent';

require 'api/api_get_specialist_slots.php';

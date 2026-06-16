<?php
session_id('test');
session_start();
$_SESSION['id'] = 1;
$_GET['action'] = 'recommend';
$_GET['child_id'] = 1;

require 'api_activities.php';

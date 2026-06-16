<?php
session_id('test2');
session_start();
$_SESSION['email'] = 'test@example.com';
$_SESSION['role'] = 'parent';
$_SESSION['id'] = 1;
$_GET['child_id'] = 1;

require 'api_speech_history.php';

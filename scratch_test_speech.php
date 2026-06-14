<?php
session_start();
$_SESSION['id'] = 9;
$_SESSION['email'] = 'test@test.com';
$_SESSION['role'] = 'parent';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['child_id'] = 1;
$_POST['mode'] = 'free_talk';
$_FILES['audio'] = [
    'name' => 'tada.wav',
    'type' => 'audio/wav',
    'tmp_name' => 'C:\Windows\Media\tada.wav',
    'error' => 0,
    'size' => 100000
];
require 'api_speech_analysis.php';

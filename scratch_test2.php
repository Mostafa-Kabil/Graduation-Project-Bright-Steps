<?php
session_start();
$_SESSION['id'] = 3; // fake specialist_id
$_SESSION['role'] = 'specialist';
$_POST['receiver_id'] = 4;
$_POST['content'] = 'Test msg';

require 'api_send_message.php';

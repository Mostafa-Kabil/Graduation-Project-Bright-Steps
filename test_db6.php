<?php
session_start();
$_SESSION['id'] = 71;
$_GET['other_user_id'] = 9;
include 'api_get_messages.php';
?>

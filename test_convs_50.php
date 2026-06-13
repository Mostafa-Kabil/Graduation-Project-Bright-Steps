<?php
session_start();
$_SESSION['id'] = 50; // parent
$_SESSION['role'] = 'parent';
$_GET['action'] = 'get_conversations';
ob_start();
include 'api_get_messages.php';
$out = ob_get_clean();
echo $out;

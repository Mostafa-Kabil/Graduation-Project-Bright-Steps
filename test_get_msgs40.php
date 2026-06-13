<?php
session_start();
$_SESSION['id'] = 9; // parent
$_SESSION['role'] = 'parent';
$_GET['other_user_id'] = 40;
ob_start();
include 'api_get_messages.php';
$out = ob_get_clean();
echo $out;

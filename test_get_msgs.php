<?php
session_start();
$_SESSION['id'] = 9; // Assuming parent ID is 9
$_SESSION['role'] = 'parent';
$_GET['other_user_id'] = 1; // Assuming specialist ID is 1
ob_start();
include 'api_get_messages.php';
$out = ob_get_clean();
echo $out;

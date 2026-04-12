<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['action' => 'update_notifications', 'key' => 'email_updates', 'value' => '1'];

session_start();
$_SESSION['id'] = 1;
$_SESSION['role'] = 'admin';

ob_start();
include '../settings.php'; // Wait, it's ./settings.php from /admin/
$output = ob_get_clean();
echo "OUTPUT:\n" . $output;
?>

<?php
session_start();
$_SESSION['id'] = 9;
$_SESSION['email'] = 'test@test.com';
$_SESSION['role'] = 'parent';
$_POST['message'] = 'sleep guidance';
$json = json_encode(['message' => 'sleep guidance', 'child_id' => 1]);
$tmp = tmpfile();
fwrite($tmp, $json);
fseek($tmp, 0);

// Mock php://input
// wait, can't easily mock php://input. Let's modify api_chatbot.php temporarily or use test script to mock it.
require 'api_chatbot.php';
?>

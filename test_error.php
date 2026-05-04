<?php
session_start();
$_SESSION['id'] = 1; // Assuming parent ID 1 exists
$_SESSION['email'] = 'test@example.com';
$_SESSION['role'] = 'parent';
session_write_close();

// Mock php://input by replacing it or just calling the script via cURL
$ch = curl_init('http://localhost/Bright%20Steps%20Website/api_chatbot.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => 'sleep guidance', 'child_id' => 1]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>

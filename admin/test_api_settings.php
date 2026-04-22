<?php
$data = json_encode(['action' => 'update_notifications', 'key' => 'email_updates', 'value' => '0']);
$ch = curl_init('http://localhost/Bright%20Steps%20Website/admin/settings.php?v=' . time());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Cookie: PHPSESSID=' . session_id()
));
$response = curl_exec($ch);
echo "API Response: \n" . $response;
?>

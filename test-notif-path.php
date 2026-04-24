<?php
/**
 * Test if the API path from dashboard is correct
 */
session_start();

// This file is at root level, same as api_notifications.php
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Path Test</title></head><body style='font-family:monospace;padding:2rem;'>";

echo "<h1>Path Resolution Test</h1>";

echo "<h2>From dashboard (dashboards/clinic/):</h2>";
echo "<p><code>../../api_notifications.php</code> should resolve to: <strong>api_notifications.php</strong></p>";

echo "<h2>Testing include:</h2>";
echo "<pre>";
$testPath = __DIR__ . '/api_notifications.php';
echo "File path: $testPath\n";
echo "File exists: " . (file_exists($testPath) ? 'YES' : 'NO') . "\n";
echo "</pre>";

echo "<h2>Direct API Test (calling via cURL with session):</h2>";
if (!isset($_SESSION['id'])) {
    echo "<p style='color:red;'>Not logged in! <a href='clinic-login.php'>Login as clinic</a></p>";
} else {
    echo "<p>Session user_id: " . $_SESSION['id'] . "</p>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Bright Steps Website/api_notifications.php?action=list&limit=10');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    echo "<pre>";
    echo "HTTP Code: " . $info['http_code'] . "\n";
    echo "Content-Type: " . $info['content_type'] . "\n";
    echo "Response:\n$response\n";
    echo "</pre>";

    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Notifications count: " . count($data['notifications']) . "</strong></p>";
        echo "<p><strong>Unread count: " . $data['unread_count'] . "</strong></p>";
    }
}

echo "</body></html>";
?>

<?php
/**
 * Direct API Test - Tests the notifications API exactly as the dashboard does
 */
session_start();
include '../connection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct API Test</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #1e293b; color: #22d3ee; }
        .section { background: #0f172a; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .success { color: #4ade80; }
        .error { color: #ef4444; }
        pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Direct API Test</h1>

    <div class="section">
        <h2>Session Info</h2>
        <pre>
User ID: <?php echo isset($_SESSION['id']) ? htmlspecialchars($_SESSION['id']) : 'NOT SET'; ?>
Role: <?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'NOT SET'; ?>
Name: <?php echo (isset($_SESSION['fname']) ? htmlspecialchars($_SESSION['fname']) : '') . ' ' . (isset($_SESSION['lname']) ? htmlspecialchars($_SESSION['lname']) : ''); ?>
        </pre>
    </div>

    <?php
    if (!isset($_SESSION['id'])) {
        echo '<div class="section error"><strong>ERROR:</strong> Not logged in! Please <a href="../../clinic-login.php" style="color:#fbbf24">login as clinic</a> first.</div>';
        exit;
    }

    $userId = $_SESSION['id'];
    ?>

    <div class="section">
        <h2>Direct Database Query</h2>
        <?php
        try {
            $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt2 = $connect->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt2->execute([$userId]);
            $unread = (int) $stmt2->fetchColumn();

            echo '<p class="success">Found ' . count($notifications) . ' notifications (' . $unread . ' unread)</p>';
            echo '<pre>' . htmlspecialchars(json_encode($notifications, JSON_PRETTY_PRINT)) . '</pre>';
        } catch (Exception $e) {
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>API Call via cURL (simulates fetch)</h2>
        <?php
        // Simulate the exact fetch request the dashboard makes
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/Bright Steps Website/api_notifications.php?action=list&limit=10');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        echo '<p>HTTP Code: ' . $httpCode . '</p>';
        echo '<p>Content-Type: ' . htmlspecialchars($contentType) . '</p>';
        echo '<p>Response:</p>';
        echo '<pre>' . htmlspecialchars($response) . '</pre>';

        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<p class="success">Valid JSON! Notifications: ' . count($data['notifications'] ?? []) . ', Unread: ' . ($data['unread_count'] ?? 0) . '</p>';
        } else {
            echo '<p class="error">Invalid JSON! Error: ' . json_last_error_msg() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>API Call via file_get_contents (alternative)</h2>
        <?php
        // Another way to test
        $context = stream_context_create([
            'http' => [
                'header' => "Accept: application/json\r\nCookie: " . session_name() . '=' . session_id() . "\r\n"
            ]
        ]);
        $response2 = file_get_contents('http://localhost/Bright Steps Website/api_notifications.php?action=list&limit=10', false, $context);

        echo '<p>Response:</p>';
        echo '<pre>' . htmlspecialchars($response2) . '</pre>';

        $data2 = json_decode($response2, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<p class="success">Valid JSON! Notifications: ' . count($data2['notifications'] ?? []) . ', Unread: ' . ($data2['unread_count'] ?? 0) . '</p>';
        } else {
            echo '<p class="error">Invalid JSON! Error: ' . json_last_error_msg() . '</p>';
        }
        ?>
    </div>

    <p><a href="../../dashboards/clinic/clinic-dashboard.php" style="color:#fbbf24">Go to Dashboard</a> | <a href="debug-notif.php" style="color:#fbbf24">Debug Page</a></p>
</body>
</html>

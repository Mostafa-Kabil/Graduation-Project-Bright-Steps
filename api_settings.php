<?php
/**
 * Bright Steps – User Settings API
 * Manages user preferences (theme, language, notifications).
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'get':
        $stmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            // Create default settings
            $stmt2 = $connect->prepare(
                "INSERT IGNORE INTO user_settings (user_id) VALUES (?)"
            );
            $stmt2->execute([$userId]);
            $settings = [
                'user_id' => $userId,
                'theme' => 'light',
                'language' => 'en',
                'push_notifications' => 1,
                'email_notifications' => 1,
                'appointment_reminders' => 1,
                'daily_reminders' => 1,
                'milestone_alerts' => 1,
                'data_sharing' => 1
            ];
        }

        echo json_encode(['success' => true, 'settings' => $settings]);
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);

        $allowed = [
            'theme', 'language', 'push_notifications', 'email_notifications',
            'appointment_reminders', 'daily_reminders', 'milestone_alerts', 'data_sharing'
        ];

        // Ensure row exists
        $stmt = $connect->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        $updates = [];
        $params = [];
        foreach ($allowed as $key) {
            if (isset($input[$key])) {
                $updates[] = "`$key` = ?";
                $params[] = $input[$key];
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
            exit();
        }

        $params[] = $userId;
        $sql = "UPDATE user_settings SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Settings updated']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: get, update']);
        break;
}

<?php
/**
 * Bright Steps – Notifications API
 * PHP endpoint that works within XAMPP.
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

    // ── Get notifications ────────────────────────────────────────
    case 'list':
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $stmt = $connect->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Unread count
        $stmt2 = $connect->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt2->execute([$userId]);
        $unread = (int) $stmt2->fetchColumn();

        echo json_encode(['notifications' => $notifications, 'unread_count' => $unread]);
        break;

    // ── Mark as read ─────────────────────────────────────────────
    case 'read':
        $input = json_decode(file_get_contents('php://input'), true);
        $notifId = $input['notification_id'] ?? null;

        if ($notifId) {
            $stmt = $connect->prepare(
                "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?"
            );
            $stmt->execute([$notifId, $userId]);
        } else {
            // Mark all as read
            $stmt = $connect->prepare(
                "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
        }

        echo json_encode(['success' => true]);
        break;

    // ── Create notification (for internal use/testing) ───────────
    case 'create':
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? 'system';
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $targetUser = $input['user_id'] ?? $userId;

        if (!$title || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and message are required']);
            exit();
        }

        $stmt = $connect->prepare(
            "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$targetUser, $type, $title, $message]);

        echo json_encode(['success' => true, 'notification_id' => $connect->lastInsertId()]);
        break;

    // ── Delete notification ──────────────────────────────────────
    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $notifId = $input['notification_id'] ?? null;

        if ($notifId) {
            $stmt = $connect->prepare(
                "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?"
            );
            $stmt->execute([$notifId, $userId]);
        }

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: list, read, create, delete']);
        break;
}

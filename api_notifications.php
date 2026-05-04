<?php
/**
 * Bright Steps – Notifications API
 * PHP endpoint that works within XAMPP.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) && !isset($_GET['clinic_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['id'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Get notifications ────────────────────────────────────────
    case 'list':
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $isClinic = (isset($_SESSION['role']) && $_SESSION['role'] === 'clinic');
        $clinicIdParam = isset($_GET['clinic_id']) ? (int) $_GET['clinic_id'] : null;
        
        // Use clinic_id from query param or session
        if ($isClinic || $clinicIdParam) {
            $whereClause = "clinic_id = ?";
            $filterValue = $clinicIdParam ?: $userId;
        } else {
            $whereClause = "user_id = ?";
            $filterValue = $userId;
        }
        
        try {
            $stmt = $connect->prepare(
                "SELECT * FROM notifications WHERE $whereClause ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->bindValue(1, $filterValue);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Unread count
            $stmt2 = $connect->prepare(
                "SELECT COUNT(*) FROM notifications WHERE $whereClause AND is_read = 0"
            );
            $stmt2->execute([$filterValue]);
            $unread = (int) $stmt2->fetchColumn();

            echo json_encode(['notifications' => $notifications, 'unread_count' => $unread]);
        } catch (Exception $e) {
            // Table may not exist yet — return empty
            echo json_encode(['notifications' => [], 'unread_count' => 0]);
        }
        break;

    // ── Mark as read ─────────────────────────────────────────────
    case 'read':
        $input = json_decode(file_get_contents('php://input'), true);
        $notifId = $input['notification_id'] ?? null;
        $isClinic = (isset($_SESSION['role']) && $_SESSION['role'] === 'clinic');
        $whereClause = $isClinic ? "clinic_id = ?" : "user_id = ?";

        if ($notifId) {
            $stmt = $connect->prepare(
                "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND $whereClause"
            );
            $stmt->execute([$notifId, $userId]);
        } else {
            // Mark all as read
            $stmt = $connect->prepare(
                "UPDATE notifications SET is_read = 1 WHERE $whereClause"
            );
            $stmt->execute([$userId]);
        }

        echo json_encode(['success' => true]);
        break;

    case 'create':
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? 'system';
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $targetUser = $input['user_id'] ?? $userId;
        $isClinic = (isset($_SESSION['role']) && $_SESSION['role'] === 'clinic');

        if (!$title || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and message are required']);
            exit();
        }

        if (!$isClinic) {
            // Check user settings before inserting
            $stmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$targetUser]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $shouldInsert = true;
            if ($settings) {
                if ($type === 'appointment' && empty($settings['appointment_reminders'])) $shouldInsert = false;
                if ($type === 'milestone' && empty($settings['milestone_alerts'])) $shouldInsert = false;
                if ($type === 'system' && empty($settings['system_alerts'])) $shouldInsert = false;
            }
            
            if (!$shouldInsert) {
                echo json_encode(['success' => true, 'skipped' => true, 'reason' => 'User disabled this notification type']);
                exit();
            }

            $stmt = $connect->prepare(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$targetUser, $type, $title, $message]);
        } else {
            $stmt = $connect->prepare(
                "INSERT INTO notifications (clinic_id, type, title, message) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$targetUser, $type, $title, $message]);
        }

        echo json_encode(['success' => true, 'notification_id' => $connect->lastInsertId()]);
        break;

    // ── Delete notification ──────────────────────────────────────
    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $notifId = $input['notification_id'] ?? null;
        $isClinic = (isset($_SESSION['role']) && $_SESSION['role'] === 'clinic');
        $whereClause = $isClinic ? "clinic_id = ?" : "user_id = ?";

        if ($notifId) {
            $stmt = $connect->prepare(
                "DELETE FROM notifications WHERE notification_id = ? AND $whereClause"
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

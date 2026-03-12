<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit;
}

$adminId = $_SESSION['id'];

try {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    switch ($action) {
        case 'compose':
            $title = $input['title'] ?? '';
            $body = $input['body'] ?? '';
            $type = $input['type'] ?? 'in_app';
            $priority = $input['priority'] ?? 'normal';
            $targetType = $input['target_type'] ?? 'all';
            $targetFilter = $input['target_filter'] ?? null;
            $scheduledAt = $input['scheduled_at'] ?? null;
            if (!$title || !$body) { echo json_encode(['error' => 'Title and body required']); exit; }

            $status = $scheduledAt ? 'scheduled' : 'sent';
            $sentAt = $scheduledAt ? null : date('Y-m-d H:i:s');

            // Get recipient count
            $recipients = getTargetUsers($connect, $targetType, $targetFilter);
            $recipientCount = count($recipients);

            $stmt = $connect->prepare("INSERT INTO admin_notifications (title, body, type, priority, target_type, target_filter, scheduled_at, sent_at, status, recipient_count, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $body, $type, $priority, $targetType, $targetFilter ? json_encode($targetFilter) : null, $scheduledAt, $sentAt, $status, $recipientCount, $adminId]);
            $notifId = $connect->lastInsertId();

            // Create recipient records
            $ins = $connect->prepare("INSERT INTO admin_notification_recipients (notification_id, user_id, delivered) VALUES (?, ?, 1)");
            foreach ($recipients as $uid) { $ins->execute([$notifId, $uid]); }

            // If in_app or both, also insert into user-facing notifications table
            if ($type === 'in_app' || $type === 'both') {
                $notifIns = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                foreach ($recipients as $uid) { $notifIns->execute([$uid, $title, $body]); }
            }

            echo json_encode(['success' => true, 'notification_id' => $notifId, 'recipients' => $recipientCount]);
            break;

        case 'cancel':
            $id = $input['notification_id'] ?? 0;
            $stmt = $connect->prepare("UPDATE admin_notifications SET status='cancelled' WHERE id=? AND status='scheduled'");
            $stmt->execute([$id]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'resend':
            $id = $input['notification_id'] ?? 0;
            $stmt = $connect->prepare("UPDATE admin_notifications SET status='sent', sent_at=NOW() WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    $action = $_GET['action'] ?? 'list';
    switch ($action) {
        case 'list':
            $stmt = $connect->query("SELECT n.*, u.first_name, u.last_name FROM admin_notifications n LEFT JOIN users u ON n.created_by=u.user_id ORDER BY n.created_at DESC LIMIT 50");
            echo json_encode(['success' => true, 'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'view':
            $id = $_GET['id'] ?? 0;
            $stmt = $connect->prepare("SELECT n.*, u.first_name, u.last_name FROM admin_notifications n LEFT JOIN users u ON n.created_by=u.user_id WHERE n.id=?");
            $stmt->execute([$id]);
            $notif = $stmt->fetch(PDO::FETCH_ASSOC);
            $rStmt = $connect->prepare("SELECT r.*, u.first_name, u.last_name, u.email FROM admin_notification_recipients r JOIN users u ON r.user_id=u.user_id WHERE r.notification_id=?");
            $rStmt->execute([$id]);
            $recipients = $rStmt->fetchAll(PDO::FETCH_ASSOC);
            $readCount = array_filter($recipients, fn($r) => $r['read_at'] !== null);
            echo json_encode(['success' => true, 'notification' => $notif, 'recipients' => $recipients, 'open_rate' => count($recipients) > 0 ? round(count($readCount)/count($recipients)*100) : 0]);
            break;

        case 'users_search':
            $q = $_GET['q'] ?? '';
            $stmt = $connect->prepare("SELECT user_id, first_name, last_name, email, role FROM users WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) AND status='active' LIMIT 20");
            $qw = "%$q%";
            $stmt->execute([$qw, $qw, $qw]);
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

function getTargetUsers($connect, $targetType, $filter) {
    switch ($targetType) {
        case 'all':
            $stmt = $connect->query("SELECT user_id FROM users WHERE status='active'");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
        case 'specific':
            return is_array($filter) ? $filter : [];
        case 'segment':
            $where = "status='active'";
            if (!empty($filter['role'])) $where .= " AND role='" . $connect->quote($filter['role']) . "'";
            $stmt = $connect->query("SELECT user_id FROM users WHERE $where");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
        default:
            return [];
    }
}

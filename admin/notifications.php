<?php
session_start();
include '../connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = :admin_id ORDER BY created_at DESC LIMIT 50");
        $stmt->execute(['admin_id' => $_SESSION['id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unreadStmt = $connect->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :admin_id AND is_read = 0");
        $unreadStmt->execute(['admin_id' => $_SESSION['id']]);
        $unreadCount = $unreadStmt->fetchColumn();

        echo json_encode(['success' => true, 'notifications' => $notifications, 'unread' => $unreadCount]);
    } 
    elseif ($action === 'mark_read') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])) {
            $stmt = $connect->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :nid AND user_id = :uid");
            $stmt->execute(['nid' => $data['id'], 'uid' => $_SESSION['id']]);
        } else {
            $stmt = $connect->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
            $stmt->execute(['uid' => $_SESSION['id']]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

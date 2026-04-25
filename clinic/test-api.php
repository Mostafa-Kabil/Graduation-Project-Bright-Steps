<?php
/**
 * Test API directly - simulates what the dashboard JavaScript does
 */
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated - please login first']);
    exit;
}

$userId = $_SESSION['id'];

// Include connection
include '../connection.php';

// Query notifications
try {
    $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $connect->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt2->execute([$userId]);
    $unread = (int) $stmt2->fetchColumn();

    echo json_encode([
        'test' => true,
        'user_id' => $userId,
        'notifications' => $notifications,
        'unread_count' => $unread,
        'count' => count($notifications)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

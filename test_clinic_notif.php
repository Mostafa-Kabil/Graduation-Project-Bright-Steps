<?php
/**
 * Test the actual notification API logic with clinic_id param
 */
include 'connection.php';
header('Content-Type: application/json');

$clinicId = isset($_GET['clinic_id']) ? (int) $_GET['clinic_id'] : 29;
$limit = 10;

try {
    $stmt = $connect->prepare("SELECT * FROM notifications WHERE clinic_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$clinicId, $limit]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $connect->prepare("SELECT COUNT(*) FROM notifications WHERE clinic_id = ? AND is_read = 0");
    $stmt2->execute([$clinicId]);
    $unread = (int) $stmt2->fetchColumn();

    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $unread,
        'debug_clinic_id' => $clinicId,
        'debug_count' => count($notifications)
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

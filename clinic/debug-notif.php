<?php
/**
 * Debug Notifications - Check API and Database
 */
session_start();
include '../connection.php';

header('Content-Type: application/json');

$result = [
    'session' => [
        'user_id' => $_SESSION['id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'fname' => $_SESSION['fname'] ?? null,
    ],
    'api_test' => null,
    'db_test' => null,
    'notifications_in_db' => null,
];

// Test direct DB query
try {
    $userId = $_SESSION['id'] ?? 0;
    $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $connect->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt2->execute([$userId]);
    $unreadCount = (int) $stmt2->fetchColumn();

    $result['db_test'] = [
        'user_id' => $userId,
        'notifications' => $notifications,
        'count' => count($notifications),
        'unread_count' => $unreadCount,
    ];
} catch (Exception $e) {
    $result['db_test'] = ['error' => $e->getMessage()];
}

// Test API call internally
try {
    $_GET['action'] = 'list';
    $_GET['limit'] = '10';
    ob_start();
    include '../api_notifications.php';
    $apiOutput = ob_get_clean();
    $result['api_test'] = json_decode($apiOutput, true) ?? ['raw' => $apiOutput];
} catch (Exception $e) {
    $result['api_test'] = ['error' => $e->getMessage()];
}

// Check if notifications table exists and has any data
try {
    $stmt = $connect->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotif = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT user_id, COUNT(*) as count FROM notifications GROUP BY user_id");
    $byUser = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result['notifications_in_db'] = [
        'total' => (int) $totalNotif,
        'by_user' => $byUser,
    ];
} catch (Exception $e) {
    $result['notifications_in_db'] = ['error' => $e->getMessage()];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>

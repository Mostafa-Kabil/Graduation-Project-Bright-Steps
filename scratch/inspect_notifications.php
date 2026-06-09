<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/doctor_notifications.php';
try {
    // Test doctor_notify
    echo "Testing doctor_notify...\n";
    $success = doctor_notify($connect, 4, 'report_shared', 'Test Report Shared', 'This is a test notification.');
    echo "Success: " . ($success ? "YES" : "NO") . "\n\n";

    // 1. Show table structure
    $stmt = $connect->query("DESCRIBE notifications");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "STRUCTURE:\n" . json_encode($structure, JSON_PRETTY_PRINT) . "\n\n";

    // 2. Show recent rows
    $stmt2 = $connect->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "RECENT ROWS:\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}



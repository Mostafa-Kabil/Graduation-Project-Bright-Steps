<?php
require_once __DIR__ . '/../connection.php';
try {
    // Check shared_reports doctor_id values
    $stmt = $connect->query("SELECT sr.report_id, sr.doctor_id, sr.child_id, sr.file_path, sr.report_type FROM shared_reports sr LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "=== shared_reports ===\n";
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check specialist table
    $stmt2 = $connect->query("SELECT specialist_id, first_name, last_name, clinic_id FROM specialist LIMIT 10");
    $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "=== specialist ===\n";
    echo json_encode($rows2, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check users table for doctor role
    $stmt3 = $connect->query("SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('doctor','specialist') LIMIT 10");
    $rows3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo "=== doctor users ===\n";
    echo json_encode($rows3, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

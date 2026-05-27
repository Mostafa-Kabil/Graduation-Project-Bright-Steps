<?php
require_once __DIR__ . '/../connection.php';
try {
    $stmt = $connect->query("SELECT report_id, file_path, report_type, child_id, doctor_id FROM shared_reports LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

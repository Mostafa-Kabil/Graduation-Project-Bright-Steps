<?php
require_once __DIR__ . '/../connection.php';
try {
    $stmt = $connect->query("SELECT s.specialist_id, s.first_name, s.last_name, u.user_id, u.role FROM specialist s LEFT JOIN users u ON s.specialist_id = u.user_id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

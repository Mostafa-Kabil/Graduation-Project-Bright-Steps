<?php
require_once 'connection.php';
try {
    $stmt = $connect->query("SELECT * FROM shared_reports LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

<?php
require_once __DIR__ . '/../connection.php';
try {
    $stmt = $connect->prepare("SELECT * FROM child WHERE child_id = 5201");
    $stmt->execute();
    $child = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "CHILD:\n" . json_encode($child, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

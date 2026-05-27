<?php
require_once 'connection.php';
try {
    $stmt = $connect->query("SELECT user_id, first_name, last_name, email, role FROM users WHERE role IN ('doctor', 'specialist') LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "USERS:\n" . json_encode($users, JSON_PRETTY_PRINT) . "\n\n";

    $stmt2 = $connect->query("SELECT * FROM specialist LIMIT 10");
    $specialists = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "SPECIALISTS:\n" . json_encode($specialists, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

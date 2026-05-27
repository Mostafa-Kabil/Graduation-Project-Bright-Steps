<?php
include 'connection.php';
try {
    echo "--- DOCTORS / SPECIALISTS IN USERS TABLE ---\n";
    $stmt = $connect->query("SELECT user_id, first_name, last_name, email, role FROM users WHERE role IN ('doctor', 'specialist')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "--- SPECIALISTS TABLE ---\n";
    $stmt = $connect->query("SELECT * FROM specialist");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "--- SHARED REPORTS ---\n";
    $stmt = $connect->query("SELECT * FROM shared_reports");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

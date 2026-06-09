<?php
require 'connection.php';
try {
    echo "--- PAYMENTS ---\n";
    $stmt = $connect->query("DESCRIBE payments");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    // maybe the table is named payment
    try {
        echo "--- PAYMENT ---\n";
        $stmt = $connect->query("DESCRIBE payment");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e2) {
        echo "Error: " . $e2->getMessage();
    }
}

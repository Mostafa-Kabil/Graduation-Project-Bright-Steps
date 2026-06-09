<?php
include 'connection.php';
try {
    $stmt = $connect->query("DESCRIBE specialist");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

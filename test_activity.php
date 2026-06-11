<?php
require 'connection.php';
try {
    $stmt = $connect->query("DESCRIBE activity_log");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

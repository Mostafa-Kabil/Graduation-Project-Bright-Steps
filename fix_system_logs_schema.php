<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE system_logs ADD COLUMN status VARCHAR(20) DEFAULT 'unresolved'");
    echo "Columns added successfully.";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

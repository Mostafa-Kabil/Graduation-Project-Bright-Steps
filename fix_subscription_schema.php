<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE parent_subscription ADD COLUMN expires_at DATETIME NULL");
    $connect->exec("ALTER TABLE parent_subscription ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
    echo "Columns added successfully.";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE message ADD COLUMN child_id INT NULL");
    $connect->exec("ALTER TABLE message ADD COLUMN appointment_id INT NULL");
    $connect->exec("ALTER TABLE message ADD COLUMN meeting_link VARCHAR(255) NULL");
    $connect->exec("ALTER TABLE message ADD COLUMN file_path VARCHAR(255) NULL");
    echo "Columns added successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
require 'connection.php';
try {
    echo "Adding certification_path column to appointment table...\n";
    try {
        $connect->exec("ALTER TABLE appointment ADD COLUMN certification_path VARCHAR(255) NULL");
        echo "Added certification_path successfully.\n";
    } catch(PDOException $e) {
        echo "certification_path already exists or failed: " . $e->getMessage() . "\n";
    }
    echo "Done!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
require 'connection.php';
try {
    // Add is_first_login column if it doesn't exist
    $connect->exec("ALTER TABLE users ADD COLUMN is_first_login TINYINT(1) DEFAULT 0");
    echo "Migration Successful: is_first_login added to users table.\n";
} catch(Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Migration Note: Column is_first_login already exists.\n";
    } else {
        echo "Migration Error: " . $e->getMessage() . "\n";
    }
}

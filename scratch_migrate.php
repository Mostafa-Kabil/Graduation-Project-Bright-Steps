<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE reward_offers ADD COLUMN target_plan ENUM('standard', 'premium', 'all') DEFAULT 'all'");
    echo "Column target_plan added.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

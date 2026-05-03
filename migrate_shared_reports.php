<?php
/**
 * Migration: Create shared_reports table
 * Run once, then delete this file.
 */
include 'connection.php';

echo "Running migration...\n";

try {
    $connect->exec("
        CREATE TABLE IF NOT EXISTS `shared_reports` (
            `report_id` int(11) NOT NULL AUTO_INCREMENT,
            `file_path` varchar(500) DEFAULT NULL,
            `report_type` varchar(50) DEFAULT 'full-report',
            `child_id` int(11) NOT NULL,
            `parent_id` int(11) NOT NULL,
            `doctor_id` int(11) NOT NULL,
            `appointment_id` int(11) DEFAULT NULL,
            `is_shared` tinyint(1) DEFAULT 1,
            `doctor_reply` text DEFAULT NULL,
            `doctor_reply_date` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`report_id`),
            KEY `idx_sr_child` (`child_id`),
            KEY `idx_sr_parent` (`parent_id`),
            KEY `idx_sr_doctor` (`doctor_id`),
            KEY `idx_sr_appointment` (`appointment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "SUCCESS: shared_reports table created/verified!\n";

    // Verify
    $result = $connect->query("DESCRIBE shared_reports");
    $cols = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns: ";
    foreach ($cols as $col) {
        echo $col['Field'] . " (" . $col['Type'] . "), ";
    }
    echo "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

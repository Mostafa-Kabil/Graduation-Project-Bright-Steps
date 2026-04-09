<?php
require 'connection.php';

echo "=== Creating missing tables ===\n\n";

try {
    // doctor_report table
    $connect->exec("CREATE TABLE IF NOT EXISTS `doctor_report` (
        `report_id` int(11) NOT NULL AUTO_INCREMENT,
        `specialist_id` int(11) NOT NULL,
        `child_id` int(11) NOT NULL,
        `child_report` text DEFAULT NULL,
        `doctor_notes` text NOT NULL,
        `recommendations` text DEFAULT NULL,
        `report_date` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`report_id`),
        KEY `specialist_id` (`specialist_id`),
        KEY `child_id` (`child_id`),
        CONSTRAINT `doctor_report_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`),
        CONSTRAINT `doctor_report_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Created: doctor_report\n";

    // message table
    $connect->exec("CREATE TABLE IF NOT EXISTS `message` (
        `message_id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `content` text NOT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`message_id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`),
        CONSTRAINT `message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
        CONSTRAINT `message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Created: message\n";

    // child_milestones table
    $connect->exec("CREATE TABLE IF NOT EXISTS `child_milestones` (
        `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
        `child_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `category` varchar(100) DEFAULT 'general',
        `achieved_at` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`milestone_id`),
        KEY `child_id` (`child_id`),
        CONSTRAINT `child_milestones_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Created: child_milestones\n";

    echo "\n=== Done! Now run: php seed_doctor_data.php ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

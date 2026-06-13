<?php
require 'connection.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS `clinic_reviews` (
        `review_id` int(11) NOT NULL AUTO_INCREMENT,
        `clinic_id` int(11) NOT NULL,
        `parent_id` int(11) NOT NULL,
        `appointment_id` int(11) NOT NULL,
        `rating` int(11) NOT NULL,
        `comment` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`review_id`),
        KEY `clinic_id` (`clinic_id`),
        KEY `parent_id` (`parent_id`),
        KEY `appointment_id` (`appointment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $connect->exec($sql);
    echo "Table clinic_reviews created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

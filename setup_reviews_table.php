<?php
include "connection.php";

$sql = "CREATE TABLE IF NOT EXISTS `specialist_reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `specialist_id` (`specialist_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `reviews_specialist_fk` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    $connect->exec($sql);
    echo "Table specialist_reviews created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>

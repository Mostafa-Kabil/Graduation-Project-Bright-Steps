<?php
require_once 'connection.php';

$queries = [
"CREATE TABLE IF NOT EXISTS `motor_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_achieved` tinyint(1) DEFAULT 0,
  `achieved_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS `child_activities` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) NOT NULL,
  `duration_minutes` int(11) DEFAULT 15,
  `difficulty` varchar(50) DEFAULT 'medium',
  `source` varchar(50) DEFAULT 'ai',
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS `child_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `achieved_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  KEY `milestone_id` (`milestone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// Wait, milestones table might also be needed? Let's assume milestones exists or create it too
"CREATE TABLE IF NOT EXISTS `milestones` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  PRIMARY KEY (`milestone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS `points_wallet` (
  `wallet_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`wallet_id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $sql) {
    try {
        $connect->exec($sql);
        echo "Successfully executed query.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "Migration finished.\n";
?>

-- =====================================================
-- Migration: Add parent_points_tracking table
-- =====================================================

CREATE TABLE IF NOT EXISTS `parent_points_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `child_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `points` INT NOT NULL,
    `reason` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE,
    FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

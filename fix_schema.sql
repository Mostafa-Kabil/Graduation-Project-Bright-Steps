-- Fix Schema: Ensure core tables match expected structure and create missing tables

-- 1. Fix 'users' table structure
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` varchar(20) DEFAULT 'active';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp();

-- 2. Create missing 'user_settings' table
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int(11) NOT NULL,
  `push_notifications` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `system_alerts` tinyint(1) DEFAULT 1,
  `weekly_reports` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create missing 'streaks' table
CREATE TABLE IF NOT EXISTS `streaks` (
  `streak_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) DEFAULT NULL,
  `streak_type` varchar(50) NOT NULL,
  `current_count` int(11) DEFAULT 0,
  `longest_count` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  PRIMARY KEY (`streak_id`),
  KEY `idx_child_streak` (`child_id`, `streak_type`),
  CONSTRAINT `fk_streaks_child` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Insert a default admin user if not exists (required for dummy clinics)
-- Note: 'phone' is required based on current schema
INSERT IGNORE INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `phone`) 
VALUES (1, 'System', 'Admin', 'admin@brightsteps.com', 'admin123', 'admin', 'active', '0000000000');

INSERT IGNORE INTO `admin` (`admin_id`, `role_level`) 
VALUES (1, 1);

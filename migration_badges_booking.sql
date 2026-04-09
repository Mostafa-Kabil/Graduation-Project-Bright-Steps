-- =====================================================
-- Migration: Seed all badges + specialist availability
-- =====================================================

-- 1. Seed all 15 badge definitions (ignore duplicates)
INSERT IGNORE INTO `badge` (`name`, `description`, `icon`) VALUES
('Rising Star', 'Maintain a 3-day login streak', 'rising_star'),
('Consistency King', 'Maintain a 7-day login streak', 'consistency_king'),
('Super Parent', 'Login for 30 consecutive days', 'super_parent'),
('Weekly Champion', 'Complete 5 activities in a week', 'weekly_champion'),
('Monthly Master', 'Complete 20 activities in a month', 'monthly_master'),
('First Steps', 'Complete your first activity', 'first_steps'),
('Growth Tracker', 'Log 5 growth measurements', 'growth_tracker'),
('Article Reader', 'Read your first article', 'article_reader'),
('Bookworm', 'Read 10 articles', 'bookworm'),
('Speech Explorer', 'Complete 5 speech analyses', 'speech_explorer'),
('Motor Master', 'Complete 5 motor skill milestones', 'motor_master'),
('Health Champion', 'Log 5 growth measurements', 'health_champion'),
('Clinic Regular', 'Book 3 clinic appointments', 'clinic_regular'),
('Message Pro', 'Send 10 messages to specialists', 'message_pro'),
('Game Master', 'Complete 5 mini-games', 'game_master'),
('Voice Hero', 'Upload 5 voice samples', 'voice_hero');

-- 2. Add parent_id column to streaks table for parent-level streaks
ALTER TABLE `streaks` ADD COLUMN `parent_id` INT(11) DEFAULT NULL AFTER `child_id`;
ALTER TABLE `streaks` ADD INDEX `idx_parent_streak` (`parent_id`, `streak_type`);

-- 3. Create specialist_availability table
CREATE TABLE IF NOT EXISTS `specialist_availability` (
  `availability_id` INT(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` INT(11) NOT NULL,
  `day_of_week` TINYINT(1) NOT NULL COMMENT '0=Sunday, 1=Monday ... 6=Saturday',
  `start_time` TIME NOT NULL DEFAULT '09:00:00',
  `end_time` TIME NOT NULL DEFAULT '17:00:00',
  `slot_duration_minutes` INT(11) NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`availability_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `specialist_avail_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Seed sample availability for existing specialists (Mon-Fri 9am-5pm)
INSERT IGNORE INTO `specialist_availability` (`specialist_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration_minutes`)
SELECT s.specialist_id, d.day_num, '09:00:00', '17:00:00', 30
FROM specialist s
CROSS JOIN (
  SELECT 1 AS day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
) d;

-- 5. Add consultation_fee to specialist table
ALTER TABLE `specialist` ADD COLUMN `consultation_fee` DECIMAL(10,2) DEFAULT 50.00;
ALTER TABLE `specialist` ADD COLUMN `bio` TEXT DEFAULT NULL;
ALTER TABLE `specialist` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL;


-- =====================================================
-- Admin Dashboard Migration - Bright Steps
-- Run this AFTER the main grad.sql has been imported
-- =====================================================

-- Add status column to users table
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'active' AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;

-- Add status and rating columns to clinic table
ALTER TABLE `clinic` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'pending' AFTER `location`;
ALTER TABLE `clinic` ADD COLUMN IF NOT EXISTS `rating` DECIMAL(3,2) DEFAULT 0.00 AFTER `status`;

-- Activity Log table
CREATE TABLE IF NOT EXISTS `activity_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `related_user_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `related_user_id` (`related_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Platform Settings table
CREATE TABLE IF NOT EXISTS `platform_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Subscription features table
CREATE TABLE IF NOT EXISTS `subscription_feature` (
  `feature_id` INT(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` INT(11) NOT NULL,
  `feature_text` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`feature_id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `sub_feature_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Default Platform Settings
INSERT IGNORE INTO `platform_settings` (`setting_key`, `setting_value`) VALUES
('allow_clinic_registration', '1'),
('auto_approve_clinics', '0'),
('enable_free_trial', '1'),
('weekly_digest', '1'),
('maintenance_mode', '0');

-- Sample Admin User (password is: password)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Super', 'Admin', 'admin@brightsteps.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

SET @admin_user_id = LAST_INSERT_ID();

INSERT INTO `admin` (`admin_id`, `role_level`) VALUES (@admin_user_id, 1);

-- Sample Parent Users
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Sarah', 'Johnson', 'sarah.j@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active'),
('Michael', 'Thompson', 'michael.t@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active'),
('Jennifer', 'Williams', 'jennifer.w@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'inactive'),
('Ahmed', 'Hassan', 'ahmed.parent@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active');

SET @p1 = LAST_INSERT_ID();

INSERT IGNORE INTO `parent` (`parent_id`, `number_of_children`) VALUES
(@p1, 2), (@p1 + 1, 1), (@p1 + 2, 1), (@p1 + 3, 2);

-- Sample Doctor Users
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Sarah', 'Mitchell', 'sarah.m@citykids.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'active'),
('Ahmed', 'Hassan', 'ahmed.h@sunrisepeds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'active'),
('Layla', 'Noor', 'layla.n@citykids.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'active');

-- Sample Clinics
INSERT INTO `clinic` (`admin_id`, `clinic_name`, `email`, `password`, `location`, `status`, `rating`) VALUES
(@admin_user_id, 'City Kids Care', 'info@citykidscare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Downtown Blvd', 'verified', 4.80),
(@admin_user_id, 'Sunrise Pediatrics', 'hello@sunrisepeds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Avenue', 'verified', 4.60),
(@admin_user_id, 'Happy Smiles Clinic', 'contact@happysmiles.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Maple Street', 'verified', 4.90),
(@admin_user_id, 'Little Stars Wellness', 'info@littlestars.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Elm Drive', 'pending', 4.40);

-- Sample Subscription Plans
INSERT INTO `subscription` (`plan_name`, `plan_period`, `price`) VALUES
('Free Trial', 'monthly', 0.00),
('Standard', 'monthly', 9.99),
('Premium', 'monthly', 24.99);

SET @sub_free = LAST_INSERT_ID();
SET @sub_std = @sub_free + 1;
SET @sub_prem = @sub_free + 2;

INSERT INTO `subscription_feature` (`subscription_id`, `feature_text`) VALUES
(@sub_free, 'Basic growth tracking'),
(@sub_free, '1 child profile'),
(@sub_free, 'Monthly reports'),
(@sub_std, 'AI-powered insights'),
(@sub_std, 'Up to 3 child profiles'),
(@sub_std, 'Weekly reports'),
(@sub_std, 'Speech analysis'),
(@sub_prem, 'Everything in Standard'),
(@sub_prem, 'Unlimited child profiles'),
(@sub_prem, 'Doctor consultations'),
(@sub_prem, 'Priority support'),
(@sub_prem, '1-on-1 specialist access');

-- Sample Points Rules
INSERT INTO `points_refrence` (`admin_id`, `action_name`, `points_value`, `adjust_sign`) VALUES
(@admin_user_id, 'Daily Login', 10, '+'),
(@admin_user_id, 'Growth Measurement', 25, '+'),
(@admin_user_id, 'Voice Sample Upload', 50, '+'),
(@admin_user_id, 'Complete Weekly Goal', 100, '+'),
(@admin_user_id, 'Redeem Badge', 200, '-'),
(@admin_user_id, 'Missed Check-in', 5, '-');

-- Sample Behavior Categories
INSERT INTO `behavior_category` (`category_name`, `category_type`, `category_description`) VALUES
('Motor Development', 'Physical', 'Tracks gross and fine motor skill progression'),
('Speech and Language', 'Communication', 'Tracks verbal and non-verbal communication skills'),
('Social Interaction', 'Social-Emotional', 'Tracks social behavior and emotional development'),
('Cognitive Skills', 'Cognitive', 'Tracks problem-solving and learning abilities'),
('Self-Care', 'Adaptive', 'Tracks self-care and daily living skills');

-- Sample Activity Logs
INSERT INTO `activity_log` (`activity_type`, `description`, `related_user_id`, `created_at`) VALUES
('clinic_registered', 'New Clinic registered: Sunrise Pediatrics', NULL, NOW() - INTERVAL 2 HOUR),
('user_signup', 'New User signed up: Ahmed Hassan (Parent)', NULL, NOW() - INTERVAL 3 HOUR),
('subscription_upgrade', 'Subscription Upgrade: Sarah Johnson to Premium', NULL, NOW() - INTERVAL 5 HOUR),
('payment_received', 'Payment Received: $300.00 from Michael Thompson', NULL, NOW() - INTERVAL 6 HOUR),
('specialist_added', 'New Specialist added: Dr. Layla Noor at City Kids Care', NULL, NOW() - INTERVAL 8 HOUR),
('alert', 'Alert: 3 children flagged for developmental review', NULL, NOW() - INTERVAL 1 DAY),
('user_signup', 'New User signed up: Jennifer Williams (Parent)', NULL, NOW() - INTERVAL 2 DAY),
('clinic_verified', 'Clinic verified: Happy Smiles Clinic', NULL, NOW() - INTERVAL 3 DAY),
('payment_received', 'Payment Received: $150.00 from Jennifer Williams', NULL, NOW() - INTERVAL 4 DAY),
('system_update', 'Platform updated to version 2.1.0', NULL, NOW() - INTERVAL 5 DAY);

-- Sample Badges
INSERT INTO `badge` (`name`, `description`, `icon`) VALUES
('First Steps', 'Complete your first growth measurement', 'first_steps'),
('Voice Hero', 'Upload 5 voice samples', 'voice_hero'),
('Weekly Champion', 'Complete 4 weekly goals in a row', 'weekly_champion'),
('Growth Tracker', 'Log 10 growth measurements', 'growth_tracker'),
('Super Parent', 'Login for 30 consecutive days', 'super_parent');

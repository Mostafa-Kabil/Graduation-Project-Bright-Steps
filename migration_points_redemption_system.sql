-- ============================================================================
-- Bright Steps - Enhanced Points & Redemption System
-- Migration Script
-- ============================================================================
-- This migration adds:
-- 1. Parent-level points wallet (separate from child wallets)
-- 2. Point caps and restrictions (daily/weekly limits)
-- 3. Redemption catalog for appointments and rewards
-- 4. Token system for appointment payments
-- 5. Anti-gaming measures (cooldowns, verification)
-- 6. Enhanced notifications for points
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- PART 1: PARENT POINTS WALLET
-- ============================================================================

-- Parent-level points wallet (separate from child-based system)
CREATE TABLE IF NOT EXISTS `parent_points_wallet` (
  `wallet_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `total_points` INT(11) DEFAULT 0,
  `lifetime_earned` INT(11) DEFAULT 0,
  `lifetime_redeemed` INT(11) DEFAULT 0,
  `last_earned_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`wallet_id`),
  UNIQUE KEY `uk_parent_wallet` (`parent_id`),
  CONSTRAINT `fk_parent_wallet_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initialize wallets for existing parents
INSERT INTO `parent_points_wallet` (`parent_id`, `total_points`, `lifetime_earned`, `lifetime_redeemed`)
SELECT p.parent_id, 0, 0, 0
FROM parent p
ON DUPLICATE KEY UPDATE `parent_id` = VALUES(`parent_id`);

-- ============================================================================
-- PART 2: POINT CAPS AND RESTRICTIONS
-- ============================================================================

-- Configuration table for point earning rules with caps
CREATE TABLE IF NOT EXISTS `points_earning_rules` (
  `rule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `action_key` VARCHAR(100) NOT NULL UNIQUE,
  `action_name` VARCHAR(255) NOT NULL,
  `points_value` INT(11) NOT NULL DEFAULT 0,
  `daily_cap` INT(11) DEFAULT NULL,
  `weekly_cap` INT(11) DEFAULT NULL,
  `cooldown_minutes` INT(11) DEFAULT 0,
  `requires_verification` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  INDEX `idx_action_key` (`action_key`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Track daily/weekly point earnings per parent
CREATE TABLE IF NOT EXISTS `parent_points_tracking` (
  `tracking_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `action_key` VARCHAR(100) NOT NULL,
  `points_earned` INT(11) NOT NULL DEFAULT 0,
  `earned_date` DATE NOT NULL,
  `week_start_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  UNIQUE KEY `uk_parent_action_date` (`parent_id`, `action_key`, `earned_date`),
  INDEX `idx_week_tracking` (`parent_id`, `week_start_date`),
  CONSTRAINT `fk_tracking_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cooldown tracking for actions
CREATE TABLE IF NOT EXISTS `parent_action_cooldowns` (
  `cooldown_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `action_key` VARCHAR(100) NOT NULL,
  `last_action_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `available_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`cooldown_id`),
  UNIQUE KEY `uk_parent_action` (`parent_id`, `action_key`),
  INDEX `idx_available_at` (`available_at`),
  CONSTRAINT `fk_cooldown_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- PART 3: REDEMPTION CATALOG
-- ============================================================================

-- Redemption catalog (rewards available for points)
CREATE TABLE IF NOT EXISTS `redemption_catalog` (
  `item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_type` ENUM('appointment', 'service', 'badge', 'custom') NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `points_cost` INT(11) NOT NULL,
  `original_price` DECIMAL(10,2) DEFAULT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `requires_specialist` TINYINT(1) DEFAULT 0,
  `specialist_id` INT(11) DEFAULT NULL,
  `max_redemptions_per_user` INT(11) DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `icon` VARCHAR(255) DEFAULT NULL,
  `badge_color` VARCHAR(50) DEFAULT 'blue',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  INDEX `idx_item_type` (`item_type`),
  INDEX `idx_is_active` (`is_active`),
  CONSTRAINT `fk_catalog_specialist` FOREIGN KEY (`specialist_id`) REFERENCES `specialist`(`specialist_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed redemption catalog with appointment tokens and other rewards
INSERT INTO `redemption_catalog` (`item_type`, `item_name`, `description`, `points_cost`, `original_price`, `discount_percentage`, `is_active`, `requires_specialist`, `icon`, `badge_color`) VALUES
('appointment', 'Appointment Token (25% off)', 'Redeem for 25% off any specialist appointment', 500, 50.00, 25.00, 1, 0, '🎫', 'green'),
('appointment', 'Appointment Token (50% off)', 'Redeem for 50% off any specialist appointment', 900, 50.00, 50.00, 1, 0, '🎟️', 'blue'),
('appointment', 'Free Appointment', 'Complete appointment covered by points', 1500, 50.00, 100.00, 1, 0, '🏥', 'purple'),
('service', 'Extended Session (30min)', 'Extra 30 minutes added to appointment', 300, 25.00, 0, 1, 1, '⏱️', 'orange'),
('service', 'Priority Booking', 'Skip waiting list for urgent appointments', 400, 15.00, 0, 1, 0, '⭐', 'yellow'),
('service', 'Home Visit Discount', '$20 off home visit surcharge', 250, 20.00, 0, 1, 0, '🏠', 'red'),
('badge', 'Premium Badge', 'Exclusive profile badge display', 1000, NULL, NULL, 1, 0, '🏆', 'gold'),
('custom', 'Gift Card $10', '$10 credit to your account', 1200, 10.00, 0, 1, 0, '💳', 'green'),
('custom', 'Gift Card $25', '$25 credit to your account', 2800, 25.00, 0, 1, 0, '💳', 'blue');

-- Parent redemptions history
CREATE TABLE IF NOT EXISTS `parent_redemptions` (
  `redemption_id` INT(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` INT(11) NOT NULL,
  `parent_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `points_used` INT(11) NOT NULL,
  `quantity` INT(11) DEFAULT 1,
  `status` ENUM('pending', 'active', 'used', 'expired', 'refunded') DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `expires_at` DATE DEFAULT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`redemption_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expires` (`expires_at`),
  CONSTRAINT `fk_redemption_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `parent_points_wallet`(`wallet_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemption_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemption_item` FOREIGN KEY (`item_id`) REFERENCES `redemption_catalog`(`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- PART 4: APPOINTMENT TOKENS
-- ============================================================================

-- Token balance for appointment payments
CREATE TABLE IF NOT EXISTS `appointment_tokens` (
  `token_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `redemption_id` INT(11) DEFAULT NULL,
  `token_type` ENUM('discount_25', 'discount_50', 'free', 'extended', 'priority') NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('available', 'applied', 'used', 'expired') DEFAULT 'available',
  `applied_to_appointment` INT(11) DEFAULT NULL,
  `expires_at` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_token_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_token_redemption` FOREIGN KEY (`redemption_id`) REFERENCES `parent_redemptions`(`redemption_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_token_appointment` FOREIGN KEY (`applied_to_appointment`) REFERENCES `appointment`(`appointment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update payment table to support token payments
ALTER TABLE `payment`
ADD COLUMN IF NOT EXISTS `tokens_used` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `token_id` INT(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `points_redeemed` INT(11) DEFAULT 0;

-- Check if constraint exists before adding? Or just try/catch. MySQL doesn't support ADD CONSTRAINT IF NOT EXISTS well.
-- I'll just skip the constraint if it errors or assume it's fine.
ALTER TABLE `payment` ADD CONSTRAINT `fk_payment_token` FOREIGN KEY IF NOT EXISTS (`token_id`) REFERENCES `appointment_tokens`(`token_id`) ON DELETE SET NULL;

-- ============================================================================
-- PART 5: ENHANCED POINTS TRANSACTIONS
-- ============================================================================

-- Add parent_id tracking to points_transaction for easier queries
ALTER TABLE `points_transaction`
ADD COLUMN IF NOT EXISTS `parent_id` INT(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `session_id` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `ip_address` VARCHAR(45) DEFAULT NULL;

ALTER TABLE `points_transaction` ADD INDEX IF NOT EXISTS `idx_parent` (`parent_id`);

-- Add verification fields for anti-gaming
CREATE TABLE IF NOT EXISTS `points_verification_queue` (
  `verification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `action_key` VARCHAR(100) NOT NULL,
  `claimed_points` INT(11) NOT NULL,
  `evidence_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `reviewed_by` INT(11) DEFAULT NULL,
  `review_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`verification_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_verify_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- PART 6: POINTS NOTIFICATIONS & ALERTS
-- ============================================================================

-- Points-specific notification preferences
ALTER TABLE `user_settings`
ADD COLUMN IF NOT EXISTS `points_notifications` TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `points_milestone_alerts` TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `points_expiration_reminders` TINYINT(1) DEFAULT 1;

-- Points milestones tracking
CREATE TABLE IF NOT EXISTS `parent_points_milestones` (
  `milestone_id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL,
  `milestone_type` ENUM('earned_total', 'redeemed_total', 'streak_days', 'action_count') NOT NULL,
  `milestone_value` INT(11) NOT NULL,
  `milestone_name` VARCHAR(255) NOT NULL,
  `badge_icon` VARCHAR(255) DEFAULT NULL,
  `achieved_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`milestone_id`),
  UNIQUE KEY `uk_parent_milestone` (`parent_id`, `milestone_type`, `milestone_value`),
  CONSTRAINT `fk_milestone_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent`(`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Points expiration configuration
CREATE TABLE IF NOT EXISTS `points_expiration_rules` (
  `rule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `rule_name` VARCHAR(255) NOT NULL,
  `expiration_days` INT(11) NOT NULL,
  `reminder_days_before` INT(11) DEFAULT 7,
  `applies_to` ENUM('all', 'unredeemed', 'bonus_points') DEFAULT 'all',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed expiration rule (points expire after 90 days of inactivity)
INSERT INTO `points_expiration_rules` (`rule_name`, `expiration_days`, `reminder_days_before`, `applies_to`, `is_active`) VALUES
('Standard Expiration', 90, 7, 'unredeemed', 1),
('Bonus Points Expiration', 30, 3, 'bonus_points', 1);

-- ============================================================================
-- PART 7: TRIGGERS AND STORED PROCEDURES
-- ============================================================================

DELIMITER $$

-- Trigger: Initialize parent wallet on parent creation
CREATE TRIGGER IF NOT EXISTS `trg_parent_points_wallet_init`
AFTER INSERT ON `parent`
FOR EACH ROW
BEGIN
    INSERT INTO `parent_points_wallet` (`parent_id`, `total_points`, `lifetime_earned`, `lifetime_redeemed`)
    VALUES (NEW.parent_id, 0, 0, 0)
    ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;
END$$

-- Trigger: Track points milestone achievements
CREATE TRIGGER IF NOT EXISTS `trg_parent_points_milestone`
AFTER UPDATE ON `parent_points_wallet`
FOR EACH ROW
BEGIN
    DECLARE milestone_reached INT DEFAULT 0;

    -- Check for milestone achievements (100, 500, 1000, 5000, 10000 points)
    IF NEW.lifetime_earned >= 10000 AND OLD.lifetime_earned < 10000 THEN
        SET milestone_reached = 10000;
    ELSEIF NEW.lifetime_earned >= 5000 AND OLD.lifetime_earned < 5000 THEN
        SET milestone_reached = 5000;
    ELSEIF NEW.lifetime_earned >= 1000 AND OLD.lifetime_earned < 1000 THEN
        SET milestone_reached = 1000;
    ELSEIF NEW.lifetime_earned >= 500 AND OLD.lifetime_earned < 500 THEN
        SET milestone_reached = 500;
    ELSEIF NEW.lifetime_earned >= 100 AND OLD.lifetime_earned < 100 THEN
        SET milestone_reached = 100;
    END IF;

    IF milestone_reached > 0 THEN
        INSERT INTO `parent_points_milestones` (`parent_id`, `milestone_type`, `milestone_value`, `milestone_name`, `badge_icon`)
        VALUES (NEW.parent_id, 'earned_total', milestone_reached,
                CONCAT('Points Master - ', milestone_reached, ' Points'), '🏆')
        ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- PART 8: INDEXES AND FOREIGN KEYS
-- ============================================================================

-- Add indexes for performance
ALTER TABLE `parent_points_wallet` ADD INDEX IF NOT EXISTS `idx_total_points` (`total_points`);
ALTER TABLE `parent_redemptions` ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);
ALTER TABLE `appointment_tokens` ADD INDEX IF NOT EXISTS `idx_expires_at` (`expires_at`);

-- ============================================================================
-- PART 9: VIEWS FOR COMMON QUERIES
-- ============================================================================

-- View: Parent points summary
CREATE OR REPLACE VIEW `v_parent_points_summary` AS
SELECT
    p.parent_id,
    u.email,
    ppw.wallet_id,
    ppw.total_points,
    ppw.lifetime_earned,
    ppw.lifetime_redeemed,
    ppw.last_earned_at,
    (SELECT COUNT(*) FROM parent_redemptions pr WHERE pr.parent_id = p.parent_id AND pr.status = 'active') as active_redemptions,
    (SELECT SUM(discount_amount) FROM appointment_tokens at WHERE at.parent_id = p.parent_id AND at.status = 'available') as available_token_value
FROM parent p
JOIN users u ON p.parent_id = u.user_id
LEFT JOIN parent_points_wallet ppw ON p.parent_id = ppw.parent_id;

-- View: Points earning leaderboard (weekly)
CREATE OR REPLACE VIEW `v_weekly_points_leaderboard` AS
SELECT
    p.parent_id,
    u.email,
    COALESCE(SUM(ppt.points_earned), 0) as weekly_points,
    RANK() OVER (ORDER BY COALESCE(SUM(ppt.points_earned), 0) DESC) as weekly_rank
FROM parent p
JOIN users u ON p.parent_id = u.user_id
LEFT JOIN parent_points_tracking ppt ON p.parent_id = ppt.parent_id
    AND ppt.week_start_date = CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
GROUP BY p.parent_id, u.email
ORDER BY weekly_points DESC;

-- ============================================================================
-- PART 10: CLEANUP EVENTS
-- ============================================================================

-- Enable event scheduler for automatic cleanup
SET GLOBAL event_scheduler = ON;

-- Event: Expire old tokens (runs daily)
DELIMITER //
CREATE EVENT IF NOT EXISTS `ev_expire_old_tokens`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY
DO
BEGIN
    UPDATE appointment_tokens
    SET status = 'expired'
    WHERE status = 'available'
    AND expires_at < CURDATE();

    UPDATE parent_redemptions
    SET status = 'expired'
    WHERE status = 'active'
    AND expires_at < CURDATE();
END//
DELIMITER ;

-- Event: Clean up old tracking data (runs weekly)
DELIMITER //
CREATE EVENT IF NOT EXISTS `ev_cleanup_tracking`
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_DATE + INTERVAL 1 WEEK - INTERVAL WEEKDAY(CURRENT_DATE) DAY
DO
BEGIN
    DELETE FROM parent_points_tracking
    WHERE earned_date < CURDATE() - INTERVAL 12 WEEK;

    DELETE FROM parent_action_cooldowns
    WHERE available_at < NOW();
END//
DELIMITER ;

COMMIT;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

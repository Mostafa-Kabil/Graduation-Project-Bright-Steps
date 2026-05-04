-- =====================================================
-- Migration: Add reward_offers table
-- Run this if the table doesn't exist yet
-- =====================================================

CREATE TABLE IF NOT EXISTS `reward_offers` (
    `offer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `points_required` INT NOT NULL DEFAULT 100,
    `icon` VARCHAR(10) DEFAULT '🎁',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admin`(`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample offers
INSERT IGNORE INTO `reward_offers` (`admin_id`, `title`, `description`, `points_required`, `icon`) VALUES
(1, 'Free Growth Report', 'Get a detailed PDF growth report for your child', 200, '📊'),
(1, 'Priority Appointment', 'Book a priority slot with any specialist', 500, '⭐'),
(1, 'Free Consultation', 'One free online consultation with a pediatrician', 750, '🩺'),
(1, 'Premium Activities Pack', 'Unlock 10 premium development activities', 300, '🎯'),
(1, 'Custom Meal Plan', 'Receive a personalized nutrition plan for your child', 400, '🥗');

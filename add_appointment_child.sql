-- Add child_id to appointment table for child-specific bookings
ALTER TABLE `appointment`
ADD COLUMN `child_id` INT(11) DEFAULT NULL AFTER `parent_id`,
ADD INDEX `idx_appointment_child` (`child_id`);

-- Add foreign key constraint
ALTER TABLE `appointment`
ADD CONSTRAINT `fk_appointment_child`
FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE SET NULL;

-- Add google_meet_link column for online appointments
ALTER TABLE `appointment`
ADD COLUMN `google_meet_link` VARCHAR(500) DEFAULT NULL AFTER `scheduled_at`,
ADD COLUMN `doctor_notes` TEXT DEFAULT NULL AFTER `google_meet_link`,
ADD COLUMN `share_notes_with_parent` TINYINT(1) DEFAULT 0 AFTER `doctor_notes`;

-- Create doctor notes table for detailed notes with sharing
CREATE TABLE IF NOT EXISTS `doctor_notes` (
  `note_id` INT(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` INT(11) DEFAULT NULL,
  `specialist_id` INT(11) NOT NULL,
  `child_id` INT(11) NOT NULL,
  `note_text` TEXT NOT NULL,
  `note_type` ENUM('private', 'shared') DEFAULT 'private',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`),
  INDEX `idx_specialist` (`specialist_id`),
  INDEX `idx_child` (`child_id`),
  INDEX `idx_appointment` (`appointment_id`),
  CONSTRAINT `fk_note_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointment`(`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_note_specialist` FOREIGN KEY (`specialist_id`) REFERENCES `specialist`(`specialist_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_child` FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create messages table for parent-doctor communication
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` INT(11) NOT NULL,
  `sender_role` ENUM('parent', 'specialist', 'clinic', 'admin') NOT NULL,
  `recipient_id` INT(11) NOT NULL,
  `recipient_role` ENUM('parent', 'specialist', 'clinic', 'admin') NOT NULL,
  `appointment_id` INT(11) DEFAULT NULL,
  `child_id` INT(11) DEFAULT NULL,
  `message_text` TEXT DEFAULT NULL,
  `attachment_url` VARCHAR(500) DEFAULT NULL,
  `attachment_type` ENUM('document', 'image', 'audio', 'video', 'other') DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  INDEX `idx_sender` (`sender_id`, `sender_role`),
  INDEX `idx_recipient` (`recipient_id`, `recipient_role`),
  INDEX `idx_appointment` (`appointment_id`),
  INDEX `idx_child` (`child_id`),
  INDEX `idx_unread` (`recipient_id`, `recipient_role`, `is_read`),
  CONSTRAINT `fk_message_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointment`(`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_message_child` FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

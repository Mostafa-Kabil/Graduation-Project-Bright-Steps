-- 1. Child-Specific Appointment Booking
ALTER TABLE `appointment` ADD COLUMN IF NOT EXISTS `child_id` INT(11) DEFAULT NULL AFTER `parent_id`;

-- Drop constraint if it already exists to avoid errors (MariaDB 10.4+ supports IF EXISTS for DROP CONSTRAINT, but we can just use a safe way, or just run the ALTER)
-- Actually, MariaDB supports `ALTER TABLE appointment DROP FOREIGN KEY IF EXISTS fk_appointment_child;`
ALTER TABLE `appointment` DROP FOREIGN KEY IF EXISTS `fk_appointment_child`;
ALTER TABLE `appointment` ADD CONSTRAINT `fk_appointment_child` FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE SET NULL;

-- 2. Google Meet Link Handling & 3. Messaging System Optimization
ALTER TABLE `message` ADD COLUMN IF NOT EXISTS `meeting_link` TEXT NULL AFTER `content`;
ALTER TABLE `message` ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(500) NULL AFTER `meeting_link`;

-- Drop index if exists and recreate
-- Procedure to drop index if it exists in MariaDB
SET @dbname = DATABASE();
SET @tablename = 'message';
SET @indexname = 'idx_msg_thread';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE `table_schema` = @dbname AND `table_name` = @tablename AND `index_name` = @indexname
  ) > 0,
  'ALTER TABLE message DROP INDEX idx_msg_thread',
  'SELECT 1'
));
PREPARE dropIndex FROM @preparedStatement;
EXECUTE dropIndex;
DEALLOCATE PREPARE dropIndex;

CREATE INDEX `idx_msg_thread` ON `message` (`child_id`, `sender_id`, `receiver_id`);

-- 4. Doctor Portal – Child Data + Notes
ALTER TABLE `doctor_report` ADD COLUMN IF NOT EXISTS `visibility` ENUM('private', 'shared') DEFAULT 'private' AFTER `doctor_notes`;

<?php
require 'connection.php';
$sqls = [
    "CREATE TABLE IF NOT EXISTS `appointment_slots` (
        `slot_id`       INT(11)    NOT NULL AUTO_INCREMENT,
        `doctor_id`     INT(11)    NOT NULL,
        `clinic_id`     INT(11)    NOT NULL,
        `day_of_week`   TINYINT(1) NOT NULL COMMENT '0=Sun,1=Mon..6=Sat',
        `start_time`    TIME       NOT NULL,
        `end_time`      TIME       NOT NULL,
        `slot_duration` INT(11)    NOT NULL DEFAULT 30,
        `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`slot_id`),
        UNIQUE KEY `uq_doctor_day` (`doctor_id`,`day_of_week`),
        CONSTRAINT `as_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `specialist`(`specialist_id`) ON DELETE CASCADE,
        CONSTRAINT `as_clinic_fk` FOREIGN KEY (`clinic_id`) REFERENCES `clinic`(`clinic_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `bio` TEXT DEFAULT NULL",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `consultation_fee` DECIMAL(8,2) DEFAULT 200.00",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `profile_photo` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `consultation_types` VARCHAR(50) DEFAULT NULL",
];
foreach ($sqls as $sql) {
    try {
        $connect->exec($sql);
        echo "OK: " . substr(trim($sql), 0, 55) . "\n";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}
echo "Migration complete.\n";

<?php
/**
 * One-time migration: create the appointment_slots table
 * used by dr-settings.php for doctor availability.
 * Run once: http://localhost/Graduation-Project-Bright-Steps/create_appointment_slots.php
 */
require_once 'connection.php';

$results = [];

// ── appointment_slots ────────────────────────────────────
$sql = "
CREATE TABLE IF NOT EXISTS `appointment_slots` (
  `slot_id`       INT(11)    NOT NULL AUTO_INCREMENT,
  `doctor_id`     INT(11)    NOT NULL,
  `clinic_id`     INT(11)    NOT NULL,
  `day_of_week`   TINYINT(1) NOT NULL COMMENT '0=Sun, 1=Mon, ..., 6=Sat',
  `start_time`    TIME       NOT NULL,
  `end_time`      TIME       NOT NULL,
  `slot_duration` INT(11)    NOT NULL DEFAULT 30 COMMENT 'minutes per slot',
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `uq_doctor_day` (`doctor_id`, `day_of_week`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `as_doctor_fk` FOREIGN KEY (`doctor_id`)
    REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE,
  CONSTRAINT `as_clinic_fk` FOREIGN KEY (`clinic_id`)
    REFERENCES `clinic` (`clinic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $connect->exec($sql);
    $results[] = ['table' => 'appointment_slots', 'status' => 'OK'];
} catch (PDOException $e) {
    $results[] = ['table' => 'appointment_slots', 'status' => 'ERROR', 'msg' => $e->getMessage()];
}

// ── doctor_profile (optional extra fields) ───────────────
// Add bio and consultation_fee columns to specialist if missing
$alterations = [
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `bio` TEXT DEFAULT NULL",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `consultation_fee` DECIMAL(8,2) DEFAULT 200.00",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `profile_photo` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `specialist` ADD COLUMN IF NOT EXISTS `consultation_types` SET('online','onsite') DEFAULT 'onsite,online'",
];

foreach ($alterations as $alt) {
    try {
        $connect->exec($alt);
        $results[] = ['alter' => substr($alt, 0, 60) . '...', 'status' => 'OK'];
    } catch (PDOException $e) {
        $results[] = ['alter' => substr($alt, 0, 60) . '...', 'status' => 'SKIP/ERR', 'msg' => $e->getMessage()];
    }
}

// Output
header('Content-Type: text/html; charset=utf-8');
echo '<h2>Migration Results</h2><pre>';
foreach ($results as $r) {
    echo json_encode($r, JSON_PRETTY_PRINT) . "\n\n";
}
echo '</pre><p style="color:green;font-weight:bold;">Done! You can delete this file.</p>';
?>

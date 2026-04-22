<?php
require_once "connection.php";

echo "<h2>Bright Steps — Ultimate Database Schema Fixer</h2>";

try {
    // 1. Fix Appointment Table
    echo "<h3>Checking 'appointment' table...</h3>";
    $stmt = $connect->query("SHOW COLUMNS FROM `appointment` LIKE 'child_id'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Column 'child_id' is missing. Adding it now...</p>";
        $connect->exec("ALTER TABLE `appointment` ADD `child_id` INT(11) NULL AFTER `parent_id` ");
        echo "<p style='color:green;'>✓ Column 'child_id' added successfully.</p>";
    } else {
        echo "<p style='color:green;'>✓ Column 'child_id' already exists.</p>";
    }

    // 2. Fix Medical Records Table
    echo "<h3>Checking 'medical_records' table...</h3>";
    $connect->exec("CREATE TABLE IF NOT EXISTS `medical_records` (
        `record_id` int(11) NOT NULL AUTO_INCREMENT,
        `child_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `diagnosis` text NOT NULL,
        `symptoms` text DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`record_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p style='color:green;'>✓ Table 'medical_records' verified/created.</p>";

    // 3. Fix Prescriptions Table
    echo "<h3>Checking 'prescriptions' table...</h3>";
    $connect->exec("CREATE TABLE IF NOT EXISTS `prescriptions` (
        `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
        `child_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `medication_name` varchar(255) NOT NULL,
        `dosage` varchar(100) DEFAULT NULL,
        `frequency` varchar(100) DEFAULT NULL,
        `instructions` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`prescription_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p style='color:green;'>✓ Table 'prescriptions' verified/created.</p>";

    echo "<hr><h2 style='color:blue;'>ALL FIXES APPLIED!</h2>";
    echo "<p>Please go back to your dashboard and try the booking again.</p>";
    echo "<a href='dashboards/clinic/clinic-dashboard.php' style='padding:10px 20px; background:#0d9488; color:white; text-decoration:none; border-radius:5px;'>Back to Dashboard</a>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}

<?php
require_once 'clinic/api/config.php';

echo "<h2>Bright Steps — Clinic Data Repair & Seeding</h2>";

try {
    $db = get_db();
    echo "<p style='color:green;'>✓ Database connected successfully.</p>";

    // 1. Ensure Clinic Tables Exist
    $queries = [
        "CREATE TABLE IF NOT EXISTS `medical_records` (
            `record_id` int(11) NOT NULL AUTO_INCREMENT,
            `child_id` int(11) NOT NULL,
            `doctor_id` int(11) NOT NULL,
            `diagnosis` text NOT NULL,
            `symptoms` text DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`record_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS `prescriptions` (
            `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
            `child_id` int(11) NOT NULL,
            `doctor_id` int(11) NOT NULL,
            `medication_name` varchar(255) NOT NULL,
            `dosage` varchar(100) DEFAULT NULL,
            `frequency` varchar(100) DEFAULT NULL,
            `instructions` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`prescription_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "ALTER TABLE `appointment` ADD COLUMN IF NOT EXISTS `child_id` INT(11) AFTER `parent_id`;"
    ];

    foreach ($queries as $q) {
        $db->exec($q);
    }
    echo "<p style='color:green;'>✓ Essential tables (medical_records, prescriptions) verified/created.</p>";

    // 2. Check for a child to attach records to
    $stmt = $db->query("SELECT child_id, first_name FROM child LIMIT 1");
    $child = $stmt->fetch();

    if (!$child) {
        echo "<p style='color:orange;'>! No children found in database. Please register a child first or run the full grad.sql import.</p>";
    } else {
        $child_id = $child['child_id'];
        $child_name = $child['first_name'];
        echo "<p>Found child: <b>$child_name (ID: $child_id)</b>. Adding test records...</p>";

        // Get a doctor (any user with role 'doctor' or 'clinic' or just the first user)
        $stmt = $db->query("SELECT id FROM users LIMIT 1");
        $doctor = $stmt->fetch();
        $doctor_id = $doctor ? $doctor['id'] : 1;

        // Insert Sample Medical Record
        $stmt = $db->prepare("INSERT IGNORE INTO medical_records (child_id, doctor_id, diagnosis, symptoms, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$child_id, $doctor_id, 'Mild Speech Delay', 'Limited vocabulary, difficulty forming sentences', 'Child shows great potential, recommended 2 sessions of speech therapy per week.']);
        
        // Insert Sample Prescription
        $stmt = $db->prepare("INSERT IGNORE INTO prescriptions (child_id, doctor_id, medication_name, dosage, frequency, instructions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$child_id, $doctor_id, 'Vitamin B12 Supplement', '500mcg', 'Once daily', 'Take with breakfast for better absorption.']);

        echo "<p style='color:blue;'><b>✓ SUCCESS: Test data added for $child_name.</b> Now go to the Patients tab and click 'View Records' for this child.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<br><a href='dashboards/clinic/clinic-dashboard.php'>Go back to Dashboard</a>";

<?php
// migration_appointment_enhancement.php
// Run once via browser: http://localhost/grad/Graduation-Project-Bright-Steps/migration_appointment_enhancement.php
require_once 'connection.php';
$changes = [];

$migrations = [
    // Specialist extra fields
    "ALTER TABLE specialist ADD COLUMN IF NOT EXISTS patient_age_group VARCHAR(100) NULL",
    "ALTER TABLE specialist ADD COLUMN IF NOT EXISTS therapy_approaches TEXT NULL",
    "ALTER TABLE specialist ADD COLUMN IF NOT EXISTS focus_areas TEXT NULL",
    "ALTER TABLE specialist ADD COLUMN IF NOT EXISTS description TEXT NULL",
    
    // Clinic extra fields
    "ALTER TABLE clinic ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) NULL",
    "ALTER TABLE clinic ADD COLUMN IF NOT EXISTS description TEXT NULL",
    "ALTER TABLE clinic ADD COLUMN IF NOT EXISTS bio TEXT NULL",
    
    // Appointment extra fields
    "ALTER TABLE appointment ADD COLUMN IF NOT EXISTS next_visit_recommendation DATE NULL",
    "ALTER TABLE appointment ADD COLUMN IF NOT EXISTS child_id INT NULL",
    
    // Message type
    "ALTER TABLE message ADD COLUMN IF NOT EXISTS message_type VARCHAR(50) DEFAULT 'text'",
    
    // Specialist reviews - appointment_id linkage
    "ALTER TABLE specialist_reviews ADD COLUMN IF NOT EXISTS appointment_id INT NULL",
    "ALTER TABLE specialist_reviews ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    
    // Clinic reviews table
    "CREATE TABLE IF NOT EXISTS clinic_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        clinic_id INT NOT NULL,
        parent_id INT NOT NULL,
        appointment_id INT NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_clinic_review (appointment_id)
    )"
];

foreach ($migrations as $sql) {
    try {
        $connect->exec($sql);
        $changes[] = ['sql' => substr($sql, 0, 80) . '...', 'status' => '✅ OK'];
    } catch (Exception $e) {
        $changes[] = ['sql' => substr($sql, 0, 80) . '...', 'status' => '⚠️ ' . $e->getMessage()];
    }
}

// Check if unique key exists for specialist_reviews, if not add it.
try {
    $checkKey = $connect->query("SHOW INDEX FROM specialist_reviews WHERE Key_name = 'uq_spec_review'");
    if ($checkKey->rowCount() == 0) {
        $connect->exec("ALTER TABLE specialist_reviews ADD UNIQUE KEY IF NOT EXISTS uq_spec_review (appointment_id)");
        $changes[] = ['sql' => 'ALTER TABLE specialist_reviews ADD UNIQUE KEY uq_spec_review...', 'status' => '✅ OK'];
    }
} catch (Exception $e) {
    $changes[] = ['sql' => 'Check/Add uq_spec_review...', 'status' => '⚠️ ' . $e->getMessage()];
}


echo '<pre>' . json_encode($changes, JSON_PRETTY_PRINT) . '</pre>';
?>

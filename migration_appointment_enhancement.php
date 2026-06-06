<?php
require_once 'connection.php';
$changes = [];

// Helper to run ALTER TABLE gracefully
function alterTableAdd($connect, $table, $column, $definition, &$changes) {
    try {
        $connect->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        $changes[] = ['sql' => "ALTER TABLE `$table` ADD COLUMN `$column` $definition", 'status' => '✅ Added'];
    } catch (PDOException $e) {
        // SQLSTATE 42S21 is 'Duplicate column name'
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column name') !== false) {
            $changes[] = ['sql' => "ALTER TABLE `$table` ADD COLUMN `$column` $definition", 'status' => '✅ Exists (Skipped)'];
        } else {
            $changes[] = ['sql' => "ALTER TABLE `$table` ADD COLUMN `$column` $definition", 'status' => '⚠️ Error: ' . $e->getMessage()];
        }
    }
}

// 1. Specialist extra fields
alterTableAdd($connect, 'specialist', 'patient_age_group', 'VARCHAR(100) NULL', $changes);
alterTableAdd($connect, 'specialist', 'therapy_approaches', 'TEXT NULL', $changes);
alterTableAdd($connect, 'specialist', 'focus_areas', 'TEXT NULL', $changes);
alterTableAdd($connect, 'specialist', 'description', 'TEXT NULL', $changes);
alterTableAdd($connect, 'specialist', 'bio', 'TEXT NULL', $changes);
alterTableAdd($connect, 'specialist', 'consultation_types', "VARCHAR(255) DEFAULT 'online,onsite'", $changes);

// 2. Clinic extra fields
alterTableAdd($connect, 'clinic', 'logo_url', 'VARCHAR(500) NULL', $changes);
alterTableAdd($connect, 'clinic', 'description', 'TEXT NULL', $changes);
alterTableAdd($connect, 'clinic', 'bio', 'TEXT NULL', $changes);

// 3. Appointment extra fields
alterTableAdd($connect, 'appointment', 'next_visit_recommendation', 'DATE NULL', $changes);
alterTableAdd($connect, 'appointment', 'child_id', 'INT NULL', $changes);

// 4. Message type
alterTableAdd($connect, 'message', 'message_type', "VARCHAR(50) DEFAULT 'text'", $changes);

// 5. Specialist reviews linkage
alterTableAdd($connect, 'specialist_reviews', 'appointment_id', 'INT NULL', $changes);
alterTableAdd($connect, 'specialist_reviews', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $changes);

// Add Unique Key for appointment_id on specialist_reviews gracefully
try {
    $connect->exec("ALTER TABLE specialist_reviews ADD UNIQUE KEY uq_spec_review (appointment_id)");
    $changes[] = ['sql' => 'ALTER TABLE specialist_reviews ADD UNIQUE KEY uq_spec_review (appointment_id)', 'status' => '✅ Added'];
} catch (Exception $e) {
    $changes[] = ['sql' => 'ALTER TABLE specialist_reviews ADD UNIQUE KEY uq_spec_review (appointment_id)', 'status' => '✅ Exists (Skipped)'];
}

// 6. Clinic reviews table
$sql_clinic = "CREATE TABLE IF NOT EXISTS clinic_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT NOT NULL,
    parent_id INT NOT NULL,
    appointment_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clinic_review (appointment_id)
)";
try {
    $connect->exec($sql_clinic);
    $changes[] = ['sql' => 'CREATE TABLE IF NOT EXISTS clinic_reviews...', 'status' => '✅ OK'];
} catch (Exception $e) {
    $changes[] = ['sql' => 'CREATE TABLE IF NOT EXISTS clinic_reviews...', 'status' => '⚠️ Error: ' . $e->getMessage()];
}

echo '<pre>' . json_encode($changes, JSON_PRETTY_PRINT) . '</pre>';
?>

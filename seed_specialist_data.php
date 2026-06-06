<?php
require_once 'connection.php';

// 1. Alter specialist table to ensure columns exist
$columns = [
    'bio' => 'TEXT',
    'description' => 'TEXT',
    'patient_age_group' => 'VARCHAR(100)',
    'therapy_approaches' => 'TEXT',
    'focus_areas' => 'TEXT'
];

foreach ($columns as $col => $type) {
    try {
        $connect->exec("ALTER TABLE `specialist` ADD COLUMN `$col` $type");
        echo "Added column $col\n";
    } catch (PDOException $e) {
        // Column likely exists
    }
}

// 2. Make sure there is an Admin account
$adminEmail = 'admin@brightsteps.com';
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? AND role = 'admin'");
$stmt->execute([$adminEmail]);
$adminId = $stmt->fetchColumn();

if (!$adminId) {
    $stmt = $connect->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'admin', 'active')");
    $stmt->execute([$adminEmail, password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "Created admin account: $adminEmail / admin123\n";
} else {
    // Reset password
    $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash('admin123', PASSWORD_DEFAULT), $adminId]);
    echo "Admin account exists: $adminEmail / admin123\n";
}

// 3. Create or Update a Test Specialist
$drEmail = 'dr_test@brightsteps.com';
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? AND role = 'specialist'");
$stmt->execute([$drEmail]);
$specId = $stmt->fetchColumn();

if (!$specId) {
    $stmt = $connect->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'specialist', 'active')");
    $stmt->execute([$drEmail, password_hash('doctor123', PASSWORD_DEFAULT)]);
    $specId = $connect->lastInsertId();

    $stmt = $connect->prepare("INSERT INTO specialist (specialist_id, first_name, last_name, specialization, experience_years, consultation_fee, clinic_id) VALUES (?, 'Test', 'Doctor', 'Speech Therapy, Occupational Therapy', 10, 150, 1)");
    $stmt->execute([$specId]);
    echo "Created new test specialist: $drEmail / doctor123\n";
} else {
    $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash('doctor123', PASSWORD_DEFAULT), $specId]);
    echo "Test specialist exists: $drEmail / doctor123\n";
}

// Ensure clinic 1 exists
try {
    $connect->exec("INSERT IGNORE INTO clinic (clinic_id, clinic_name, location) VALUES (1, 'Bright Steps Main Clinic', 'Downtown Medical Center')");
} catch(Exception $e) {}

// 4. Populate Full Data for the Specialist
$updateStmt = $connect->prepare("
    UPDATE specialist SET 
        bio = 'I am a highly experienced pediatric therapist dedicated to helping children overcome developmental challenges through play-based therapy and family-centered care.',
        description = 'Specializes in early childhood intervention, motor skills development, and speech articulation delays.',
        patient_age_group = '2-12 years',
        therapy_approaches = 'Play-based, Family-centered, CBT',
        focus_areas = 'Speech Articulation, Motor Coordination, Sensory Processing',
        clinic_id = 1
    WHERE specialist_id = ?
");
$updateStmt->execute([$specId]);

// Also update Emily Davis (ID 44 from screenshot) to have data just in case
$connect->exec("
    UPDATE specialist SET 
        bio = 'Dr. Emily Davis is a compassionate speech-language pathologist specializing in pediatric care.',
        description = 'Helping children find their voice with individualized, evidence-based therapy approaches.',
        patient_age_group = '3-10 years',
        therapy_approaches = 'Evidence-based, Holistic',
        focus_areas = 'Language Delay, Stuttering, Autism Spectrum',
        experience_years = 4
    WHERE first_name = 'Emily' AND last_name = 'Davis'
");

// 5. Populate Availability Slots
$connect->prepare("DELETE FROM specialist_availability WHERE specialist_id = ?")->execute([$specId]);
$availStmt = $connect->prepare("INSERT INTO specialist_availability (specialist_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)");
// Monday (1) to Friday (5)
for ($d = 1; $d <= 5; $d++) {
    $availStmt->execute([$specId, $d, '09:00:00', '12:00:00']);
    $availStmt->execute([$specId, $d, '14:00:00', '18:00:00']);
}
// Also for Emily Davis if she exists
$emilyId = $connect->query("SELECT specialist_id FROM specialist WHERE first_name = 'Emily' AND last_name = 'Davis'")->fetchColumn();
if ($emilyId) {
    $connect->prepare("DELETE FROM specialist_availability WHERE specialist_id = ?")->execute([$emilyId]);
    $availStmt->execute([$emilyId, 1, '10:00:00', '14:00:00']);
    $availStmt->execute([$emilyId, 3, '10:00:00', '14:00:00']);
}

// 6. Populate Fake Reviews
// Ensure a parent exists
$parentId = $connect->query("SELECT parent_id FROM parent LIMIT 1")->fetchColumn();
if (!$parentId) {
    $connect->exec("INSERT INTO users (email, password, role) VALUES ('parent@test.com', 'test', 'parent')");
    $uid = $connect->lastInsertId();
    $connect->exec("INSERT INTO parent (parent_id, first_name, last_name) VALUES ($uid, 'Test', 'Parent')");
    $parentId = $uid;
}

$connect->prepare("DELETE FROM specialist_reviews WHERE specialist_id = ?")->execute([$specId]);

$connect->exec("SET FOREIGN_KEY_CHECKS=0");
$connect->prepare("DELETE FROM specialist_reviews WHERE specialist_id = ?")->execute([$specId]);

$connect->exec("INSERT IGNORE INTO appointment (appointment_id, parent_id, specialist_id, status) VALUES (999991, $parentId, $specId, 'completed')");
$connect->exec("INSERT IGNORE INTO appointment (appointment_id, parent_id, specialist_id, status) VALUES (999992, $parentId, $specId, 'completed')");
if ($emilyId) $connect->exec("INSERT IGNORE INTO appointment (appointment_id, parent_id, specialist_id, status) VALUES (999993, $parentId, $emilyId, 'completed')");

$reviewStmt = $connect->prepare("INSERT INTO specialist_reviews (specialist_id, parent_id, rating, comment, created_at, appointment_id) VALUES (?, ?, ?, ?, ?, ?)");
$reviewStmt->execute([$specId, $parentId, 5, "Absolutely wonderful! My child has made so much progress.", date('Y-m-d H:i:s', strtotime('-2 days')), 999991]);
$reviewStmt->execute([$specId, $parentId, 4, "Very professional and patient. Highly recommended.", date('Y-m-d H:i:s', strtotime('-1 week')), 999992]);

if ($emilyId) {
    $connect->prepare("DELETE FROM specialist_reviews WHERE specialist_id = ?")->execute([$emilyId]);
    $reviewStmt->execute([$emilyId, $parentId, 5, "Dr. Davis is amazing with kids! Such a gentle approach.", date('Y-m-d H:i:s', strtotime('-5 days')), 999993]);
}
$connect->exec("SET FOREIGN_KEY_CHECKS=1");

echo "\nSeed complete! Test Specialist ID: $specId";
if ($emilyId) echo "\nUpdated Emily Davis ID: $emilyId";

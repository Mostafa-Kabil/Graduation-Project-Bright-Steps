<?php
require 'connection.php';

// Add phone to clinic
$connect->query("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS phone VARCHAR(20)");

// Seed clinic phone numbers
$clinics = $connect->query("SELECT clinic_id FROM clinic WHERE phone IS NULL OR phone = ''")->fetchAll(PDO::FETCH_COLUMN);
foreach ($clinics as $cid) {
    $phone = '01' . rand(0, 2) . rand(10000000, 99999999);
    $connect->query("UPDATE clinic SET phone = '$phone' WHERE clinic_id = $cid");
}

// Seed doctor_onboarding for all specialists
$specialists = $connect->query("SELECT specialist_id FROM specialist")->fetchAll(PDO::FETCH_COLUMN);
foreach ($specialists as $sid) {
    $goals = json_encode(['Cognitive Therapy', 'Speech Delay', 'Behavioral Correction']);
    $focus = json_encode(['Autism', 'ADHD', 'Learning Disabilities']);
    $types = json_encode(['online', 'onsite']);
    
    $stmt = $connect->prepare("INSERT IGNORE INTO doctor_onboarding (doctor_id, goals, focus_areas, consultation_types, session_duration, max_patients_per_day) VALUES (?, ?, ?, ?, 30, 10)");
    $stmt->execute([$sid, $goals, $focus, $types]);
    
    // Also update certificates
    $connect->query("UPDATE specialist SET certifications = 'Board Certified, Master in Pediatrics' WHERE specialist_id = $sid AND (certifications IS NULL OR certifications = '')");
}

echo "Database updated successfully.\n";

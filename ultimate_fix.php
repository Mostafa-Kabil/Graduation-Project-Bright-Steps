<?php
require 'connection.php';
try {
    $connect->beginTransaction();

    // 1. Ensure child table is AUTO_INCREMENT
    $connect->exec("ALTER TABLE child MODIFY child_id INT AUTO_INCREMENT");

    // 2. Create Doctors in the users table first (to satisfy FK constraint)
    $doctorEmails = ['doc1@test.com', 'doc2@test.com'];
    foreach ($doctorEmails as $email) {
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            $connect->prepare("INSERT INTO users (first_name, last_name, email, role, status) VALUES ('Dr.', 'Test', ?, 'doctor', 'active')")->execute([$email]);
            echo "Created doctor user: $email\n";
        }
    }

    // 3. Populate children
    $parent = $connect->query("SELECT user_id FROM users WHERE role='parent' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($parent) {
        $pid = $parent['user_id'];
        $connect->prepare("INSERT INTO parent (parent_id) VALUES (?) ON DUPLICATE KEY UPDATE parent_id=parent_id")->execute([$pid]);
        $connect->prepare("INSERT IGNORE INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES 
            ('SSN_FIX_1', ?, 'Adam', 'Test', 1, 1, 2020, 'Male'),
            ('SSN_FIX_2', ?, 'Zoe', 'Test', 5, 5, 2021, 'Female')
        ")->execute([$pid, $pid]);
        echo "Seeded 2 children for parent $pid.\n";
    }

    // 4. Link specialists to EVERY clinic record (to be safe)
    $specialistUsers = $connect->query("SELECT user_id FROM users WHERE role='doctor' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    $clinics = $connect->query("SELECT clinic_id FROM clinic")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clinics as $cl) {
        foreach ($specialistUsers as $su) {
            $connect->prepare("
                INSERT IGNORE INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years) 
                VALUES (?, ?, 'Medical', 'Specialist', 'Pediatrician', 10)
            ")->execute([$su['user_id'], $cl['clinic_id']]);
        }
    }
    echo "Linked specialists to all " . count($clinics) . " clinics.\n";

    $connect->commit();
    echo "Ultimate fix completed.";
} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo "Error: " . $e->getMessage();
}
?>

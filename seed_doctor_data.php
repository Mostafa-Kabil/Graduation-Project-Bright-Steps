<?php
/**
 * Seed script: Creates test data for the doctor dashboard.
 * Run once: http://localhost/Graduation-Project-Bright-Steps/seed_doctor_data.php
 */
require 'connection.php';
header('Content-Type: text/plain');

$results = [];

try {
    // 1. Create admin user
    $pw = password_hash('12345678', PASSWORD_DEFAULT);
    $connect->exec("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role) VALUES (1, 'Admin', 'System', 'admin@brightsteps.com', '$pw', 'admin')");
    $connect->exec("INSERT IGNORE INTO admin (admin_id, role_level) VALUES (1, 1)");
    $results[] = "✓ Admin user created (admin@brightsteps.com / 12345678)";

    // 2. Create clinic
    $connect->exec("INSERT IGNORE INTO clinic (clinic_id, admin_id, clinic_name, location, email, bio, status, rating) VALUES (1, 1, 'Bright Steps Pediatric Center', 'Cairo, Nasr City - 5th Settlement', 'clinic@brightsteps.com', 'Leading pediatric development center in Egypt', 'active', 4.80)");
    $results[] = "✓ Clinic created: Bright Steps Pediatric Center";

    // 3. Create doctor user
    $connect->exec("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role) VALUES (100, 'Ahmed', 'Hassan', 'dr.ahmed@brightsteps.com', '$pw', 'doctor')");
    $connect->exec("INSERT IGNORE INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, experience_years) VALUES (100, 1, 'Ahmed', 'Hassan', 'pediatrician', 'MD, FAAP, Board Certified Pediatrician', 12)");
    $results[] = "✓ Doctor created: Dr. Ahmed Hassan (dr.ahmed@brightsteps.com / 12345678)";

    // 4. Create parent users
    $parents = [
        [200, 'Sara', 'Mohamed', 'sara@test.com'],
        [201, 'Fatma', 'Ali', 'fatma@test.com'],
        [202, 'Nour', 'Ibrahim', 'nour@test.com'],
        [203, 'Hana', 'Youssef', 'hana@test.com'],
        [204, 'Layla', 'Khaled', 'layla@test.com'],
    ];
    foreach ($parents as $p) {
        $connect->exec("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role) VALUES ({$p[0]}, '{$p[1]}', '{$p[2]}', '{$p[3]}', '$pw', 'parent')");
        $connect->exec("INSERT IGNORE INTO parent (parent_id, number_of_children) VALUES ({$p[0]}, 1)");
    }
    $results[] = "✓ 5 parent accounts created";

    // 5. Create children
    $children = [
        [300, 200, 'Omar', 'Mohamed', 5, 3, 2021, 'M'],
        [301, 201, 'Yara', 'Ali', 12, 7, 2020, 'F'],
        [302, 202, 'Adam', 'Ibrahim', 20, 1, 2023, 'M'],
        [303, 203, 'Mona', 'Youssef', 3, 11, 2019, 'F'],
        [304, 204, 'Zain', 'Khaled', 15, 9, 2022, 'M'],
    ];
    foreach ($children as $c) {
        $ssn = 'SSN' . str_pad($c[0], 6, '0', STR_PAD_LEFT);
        $connect->exec("INSERT IGNORE INTO child (ssn, child_id, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES ('$ssn', {$c[0]}, {$c[1]}, '{$c[2]}', '{$c[3]}', {$c[4]}, {$c[5]}, {$c[6]}, '{$c[7]}')");
    }
    $results[] = "✓ 5 children created";

    // 6. Create payments (needed for appointments FK)
    for ($i = 1; $i <= 15; $i++) {
        $connect->exec("INSERT IGNORE INTO payment (payment_id, amount_pre_discount, discount_rate, amount_post_discount, method, status) VALUES ($i, 300.00, 0.00, 300.00, 'card', 'completed')");
    }
    $results[] = "✓ 15 payment records created";

    // 7. Create appointments
    $statuses = ['completed','completed','completed','completed','completed','scheduled','scheduled','scheduled','cancelled','completed','completed','completed','scheduled','completed','cancelled'];
    $dates = [];
    for ($i = 0; $i < 15; $i++) {
        $offset = $i < 5 ? -rand(1,30) : ($i < 10 ? -rand(31,90) : rand(1,14));
        $dates[] = date('Y-m-d H:i:s', strtotime("$offset days " . rand(9,16) . ":00:00"));
    }
    $parentIds = [200,201,202,203,204,200,201,202,203,204,200,201,202,203,204];
    for ($i = 0; $i < 15; $i++) {
        $aid = $i + 1;
        $pid = $parentIds[$i];
        $st = $statuses[$i];
        $dt = $dates[$i];
        $type = $i % 2 == 0 ? 'online' : 'onsite';
        $connect->exec("INSERT IGNORE INTO appointment (appointment_id, parent_id, payment_id, specialist_id, status, type, scheduled_at) VALUES ($aid, $pid, $aid, 100, '$st', '$type', '$dt')");
    }
    $results[] = "✓ 15 appointments created (mix of completed/scheduled/cancelled)";

    // 8. Create appointment slots
    $connect->exec("DELETE FROM appointment_slots WHERE doctor_id = 100");
    $days = [0, 1, 2, 3, 4]; // Sun-Thu
    foreach ($days as $d) {
        $connect->exec("INSERT INTO appointment_slots (doctor_id, clinic_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (100, 1, $d, '09:00:00', '17:00:00', 30, 1)");
    }
    $results[] = "✓ Availability slots created (Sun-Thu, 9AM-5PM)";

    // 9. Create feedback/reviews
    $reviews = [
        [200, 5, 'Dr. Ahmed is amazing with children! Very patient and thorough.'],
        [201, 4, 'Great doctor, helped my daughter improve significantly.'],
        [202, 5, 'Highly recommend! Very knowledgeable about developmental delays.'],
        [203, 4, 'Good experience overall. Clear communication with parents.'],
        [204, 5, 'The best pediatrician we have visited. Thank you!'],
    ];
    foreach ($reviews as $i => $r) {
        $fid = $i + 1;
        $connect->exec("INSERT IGNORE INTO feedback (feedback_id, parent_id, specialist_id, content, rating) VALUES ($fid, {$r[0]}, 100, '{$r[1]}', {$r[2]})");
    }
    $results[] = "✓ 5 reviews created (avg 4.6 stars)";

    // 10. Create messages table & data
    $connect->exec("CREATE TABLE IF NOT EXISTS messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $msgs = [
        [200, 100, 'Hi Dr. Ahmed, when is Omar\'s next appointment?', 1],
        [100, 200, 'Hello Sara, I have availability next Sunday at 10 AM. Shall I book it?', 1],
        [200, 100, 'Yes please! That works perfectly.', 1],
        [100, 200, 'Done! See you then. Please bring his previous reports.', 1],
        [201, 100, 'Dr. Ahmed, Yara has been showing progress with speech therapy!', 1],
        [100, 201, 'That\'s wonderful news, Fatma! Let\'s discuss in the next session.', 1],
        [201, 100, 'Thank you for everything!', 0],
        [202, 100, 'Hello doctor, I need advice about Adam\'s behavior.', 0],
        [203, 100, 'Can we reschedule Mona\'s appointment to next week?', 0],
    ];
    foreach ($msgs as $i => $m) {
        $mid = $i + 1;
        $ago = (count($msgs) - $i) * 3600;
        $time = date('Y-m-d H:i:s', time() - $ago);
        $content = addslashes($m[2]);
        $connect->exec("INSERT IGNORE INTO messages (message_id, sender_id, receiver_id, content, is_read, sent_at) VALUES ($mid, {$m[0]}, {$m[1]}, '$content', {$m[3]}, '$time')");
    }
    $results[] = "✓ 9 messages created across 4 conversations";

    // 11. Create doctor_reports table & data
    $connect->exec("CREATE TABLE IF NOT EXISTS doctor_reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        specialist_id INT NOT NULL,
        child_id INT NOT NULL,
        parent_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        status VARCHAR(50) DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $reports = [
        [100, 300, 200, 'Initial Assessment - Omar', 'Omar shows typical development milestones for his age. Recommended follow-up in 3 months.', 'published'],
        [100, 301, 201, 'Speech Therapy Progress - Yara', 'Yara has made significant improvement in articulation. Vocabulary has expanded by 40% since last assessment.', 'published'],
        [100, 302, 202, 'Behavioral Observation - Adam', 'Adam demonstrates some sensory seeking behaviors. Recommending occupational therapy evaluation.', 'published'],
        [100, 303, 203, 'Development Milestone Check - Mona', 'Mona is meeting all developmental milestones. Social skills are developing well.', 'draft'],
        [100, 304, 204, 'Follow-up Report - Zain', 'Zain shows improvement in motor skills after recommended exercises. Continue current plan.', 'published'],
    ];
    foreach ($reports as $i => $r) {
        $rid = $i + 1;
        $title = addslashes($r[3]);
        $content = addslashes($r[4]);
        $ago = ($i + 1) * 86400 * 3;
        $time = date('Y-m-d H:i:s', time() - $ago);
        $connect->exec("INSERT IGNORE INTO doctor_reports (report_id, specialist_id, child_id, parent_id, title, content, status, created_at) VALUES ($rid, {$r[0]}, {$r[1]}, {$r[2]}, '$title', '$content', '{$r[5]}', '$time')");
    }
    $results[] = "✓ 5 doctor reports created";

    // 12. Set specialist session vars
    $connect->exec("UPDATE specialist SET bio = 'Dedicated pediatrician with 12 years of experience in child development and behavioral therapy. Passionate about early intervention and helping families navigate developmental challenges.', consultation_fee = 350.00 WHERE specialist_id = 100");
    $results[] = "✓ Doctor bio & consultation fee updated";

    echo "=== SEED DATA COMPLETE ===\n\n";
    foreach ($results as $r) echo "$r\n";
    echo "\n=== LOGIN CREDENTIALS ===\n";
    echo "Doctor:  dr.ahmed@brightsteps.com / 12345678\n";
    echo "Parent:  sara@test.com / 12345678\n";
    echo "Admin:   admin@brightsteps.com / 12345678\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Results so far:\n";
    foreach ($results as $r) echo "$r\n";
}

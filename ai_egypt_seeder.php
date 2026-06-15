<?php
require 'connection.php';
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // Allow time for big inserts
error_reporting(E_ALL);

echo "Starting Comprehensive Egyptian Data Seeder...\n";

$first_names_m = ['Ahmed', 'Mohamed', 'Mahmoud', 'Mostafa', 'Omar', 'Youssef', 'Ali', 'Hassan', 'Hussein', 'Ibrahim', 'Karim', 'Tarek', 'Hisham'];
$first_names_f = ['Fatima', 'Aisha', 'Nada', 'Nour', 'Maha', 'Salma', 'Sara', 'Laila', 'Habiba', 'Mariam', 'Dina', 'Yasmin', 'Mona'];
$last_names = ['Hassan', 'Ibrahim', 'Sayed', 'Ali', 'Mahmoud', 'Fawzy', 'Tarek', 'Osama', 'Sami', 'Magdy', 'Kamal', 'Mansour', 'Abdelrahman'];
$cities = ['Cairo, Nasr City', 'Cairo, Maadi', 'Giza, Dokki', 'Giza, 6th of October', 'Alexandria, Smouha', 'Alexandria, Miami', 'Mansoura, Hay El Gamaa'];
$specializations = ['Speech Therapist', 'Pediatrician', 'Child Psychologist', 'Physical Therapist', 'Behavioral Therapist'];

function randomDateInPast($days = 365) {
    $timestamp = time() - rand(0, $days * 24 * 60 * 60);
    return date("Y-m-d H:i:s", $timestamp);
}

// Ensure Admin 1 exists
$admin_id = 1;
$stmt = $connect->prepare("SELECT user_id FROM users WHERE user_id = 1");
$stmt->execute();
if (!$stmt->fetch()) {
    $connect->query("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role, status) VALUES (1, 'System', 'Admin', 'admin@brightsteps.com', 'passbrightsteps', 'admin', 'active')");
    $connect->query("INSERT IGNORE INTO admin (admin_id, role_level) VALUES (1, 1)");
}

$connect->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Generate Parents
$parent_ids = [];
for ($i = 0; $i < 30; $i++) {
    $fn = rand(0, 1) ? $first_names_m[array_rand($first_names_m)] : $first_names_f[array_rand($first_names_f)];
    $ln = $last_names[array_rand($last_names)];
    $email = strtolower($fn . '.' . $ln . rand(100, 999) . '@gmail.com');
    $phone = '01' . rand(0, 2) . rand(10000000, 99999999);
    
    $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status, created_at) VALUES (?, ?, ?, 'passbrightsteps', 'parent', ?, 'active', ?)");
    $stmt->execute([$fn, $ln, $email, $phone, randomDateInPast(400)]);
    $uid = $connect->lastInsertId();
    $parent_ids[] = $uid;
    
    $connect->query("INSERT INTO parent (parent_id, number_of_children) VALUES ($uid, " . rand(1, 3) . ")");
    $connect->query("INSERT INTO user_settings (user_id) VALUES ($uid)");
}

// 2. Generate Clinics
$clinic_ids = [];
$clinic_names = ['Al Amal Kids Clinic', 'Nour Pediatric Center', 'Hayat Speech Therapy', 'El Safa Rehabilitation', 'Future Steps Clinic', 'Sunrise Child Care'];
foreach ($clinic_names as $cname) {
    $loc = $cities[array_rand($cities)];
    $stmt = $connect->prepare("INSERT INTO clinic (admin_id, clinic_name, logo, email, password, location, bio, cover_image, profile_image, opening_hours, specialties, website, status, rating, added_at) VALUES (1, ?, 'uploads/default_clinic.jpg', ?, 'passbrightsteps', ?, 'Top tier clinic providing the best care in Egypt.', 'uploads/cover.jpg', 'uploads/profile.jpg', '09:00 - 17:00', 'Pediatrics, Speech', 'www.clinic.com', 'verified', ?, ?)");
    $stmt->execute([$cname, str_replace(' ', '', strtolower($cname)).'@clinic.com', $loc, 4 + (rand(0, 10)/10), randomDateInPast(300)]);
    $clinic_ids[] = $connect->lastInsertId();
}

// 3. Generate Specialists
$specialist_ids = [];
foreach ($clinic_ids as $cid) {
    for ($i = 0; $i < 3; $i++) {
        $fn = rand(0, 1) ? $first_names_m[array_rand($first_names_m)] : $first_names_f[array_rand($first_names_f)];
        $ln = $last_names[array_rand($last_names)];
        $email = strtolower($fn . '.' . $ln . rand(100, 999) . '@doc.com');
        $phone = '01' . rand(0, 2) . rand(10000000, 99999999);
        $spec = $specializations[array_rand($specializations)];
        $fee = rand(200, 600);
        $exp = rand(2, 20);
        
        $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, status, created_at) VALUES (?, ?, ?, 'passbrightsteps', 'specialist', ?, 'active', ?)");
        $stmt->execute([$fn, $ln, $email, $phone, randomDateInPast(300)]);
        $uid = $connect->lastInsertId();
        $specialist_ids[] = $uid;
        
        $stmt = $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, experience_years, consultation_fee, bio, phone, certification_text, certification_pdf, profile_photo) VALUES (?, ?, ?, ?, ?, 'Certified by Egyptian Medical Syndicate', ?, ?, 'Passionate specialist dedicated to helping children reach their full potential.', ?, 'MD in Pediatrics', 'uploads/cert.pdf', 'uploads/doc.jpg')");
        $stmt->execute([$uid, $cid, $fn, $ln, $spec, $exp, $fee, $phone]);
        
        // Availability
        for($d=0; $d<=4; $d++) {
            $connect->query("INSERT INTO specialist_availability (specialist_id, day_of_week, start_time, end_time, slot_duration_minutes) VALUES ($uid, $d, '09:00:00', '15:00:00', 30)");
        }
    }
}

// 4. Generate Children
$child_ids = [];
foreach ($parent_ids as $pid) {
    $num_children = rand(1, 3);
    for ($i = 0; $i < $num_children; $i++) {
        $fn = rand(0, 1) ? $first_names_m[array_rand($first_names_m)] : $first_names_f[array_rand($first_names_f)];
        $ln = $last_names[array_rand($last_names)]; // Parent's last name technically, but keeping it simple
        $gender = in_array($fn, $first_names_m) ? 'male' : 'female';
        $byear = rand(2019, 2025);
        
        $stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender, birth_certificate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uploads/birth_cert.jpg')");
        $stmt->execute([uniqid('s_'), $pid, $fn, $ln, rand(1,28), rand(1,12), $byear, $gender]);
        $cid = $connect->lastInsertId();
        $child_ids[] = $cid;
        
        $connect->query("INSERT INTO points_wallet (child_id, total_points) VALUES ($cid, ".rand(100, 1000).")");
    }
}

// 5. Payments & Subscriptions (Using sub_id 1 to 4)
$payments = [];
foreach ($parent_ids as $pid) {
    $sub_id = rand(1, 4);
    $stmt = $connect->prepare("INSERT INTO payment (parent_id, subscription_id, amount_pre_discount, discount_rate, amount_post_discount, method, status, paid_at, token_id) VALUES (?, ?, 500, 0, 500, 'credit_card', 'completed', ?, 0)");
    $stmt->execute([$pid, $sub_id, randomDateInPast(60)]);
    $payments[$pid] = $connect->lastInsertId();
}

// 6. Appointments, Reviews, Reports, Prescriptions
foreach ($child_ids as $cid) {
    // Get child's parent
    $pid = $connect->query("SELECT parent_id FROM child WHERE child_id = $cid")->fetchColumn();
    $payment_id = $payments[$pid] ?? 1;
    $spec_id = $specialist_ids[array_rand($specialist_ids)];
    
    // Past completed appointment
    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, report, comment, scheduled_at, cancelled_by) VALUES (?, ?, ?, ?, 'completed', 'online', 'Session went well. Child is responsive.', 'Great doctor!', ?, '')");
    $stmt->execute([$pid, $cid, $payment_id, $spec_id, randomDateInPast(100)]);
    $app_id = $connect->lastInsertId();
    
    // Specialist Review
    $connect->query("INSERT INTO specialist_reviews (parent_id, specialist_id, appointment_id, rating, comment, created_at) VALUES ($pid, $spec_id, $app_id, ".rand(3,5).", 'Very patient and helpful with my child.', '".randomDateInPast(90)."')");
    
    // Clinic Review
    $clinic_id = $connect->query("SELECT clinic_id FROM specialist WHERE specialist_id = $spec_id")->fetchColumn();
    if($clinic_id) {
        $connect->query("INSERT INTO clinic_reviews (clinic_id, parent_id, appointment_id, rating, comment, created_at) VALUES ($clinic_id, $pid, $app_id, ".rand(4,5).", 'Clean clinic and friendly staff.', '".randomDateInPast(90)."')");
    }
    
    // Doctor Report
    $connect->query("INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date) VALUES ($spec_id, $cid, 'Routine Checkup', 'Child is hitting milestones.', 'Continue daily reading.', '".date("Y-m-d", time() - rand(100000, 500000))."')");
    
    // Prescription
    $connect->query("INSERT INTO prescriptions (child_id, doctor_id, medication_name, dosage, frequency, duration, instructions) VALUES ($cid, $spec_id, 'Vitamin C Supplement', '5ml', 'Once a day', '30 days', 'Take after breakfast')");
    
    // Future appointment
    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, report, comment, scheduled_at, cancelled_by) VALUES (?, ?, ?, ?, 'scheduled', 'onsite', '', '', DATE_ADD(NOW(), INTERVAL ".rand(1, 14)." DAY), '')");
    $stmt->execute([$pid, $cid, $payment_id, $spec_id]);
    
    // Growth Records (Chronological)
    for($m=12; $m>=1; $m--) {
        $w = 10 + ((12-$m) * 0.2);
        $h = 80 + ((12-$m) * 1.5);
        $connect->query("INSERT INTO growth_record (child_id, recorded_at, weight, height, head_circumference) VALUES ($cid, DATE_SUB(NOW(), INTERVAL $m MONTH), $w, $h, 45)");
    }
    
    // Voice Sample & Speech Analysis
    for($m=6; $m>=1; $m--) {
        $connect->query("INSERT INTO voice_sample (child_id, feedback, audio_url, sent_at, mode, target_text) VALUES ($cid, 'Good attempt', 'audio.wav', DATE_SUB(NOW(), INTERVAL $m WEEK), 'free_talk', 'Hello world')");
        $vs_id = $connect->lastInsertId();
        $connect->query("INSERT INTO speech_analysis (sample_id, analyzed_at, transcript, vocabulary_score, clarify_score, match_score, overall_development_score, developmental_feedback) VALUES ($vs_id, DATE_SUB(NOW(), INTERVAL $m WEEK), 'He lo word', ".rand(50,90).", ".rand(50,90).", ".rand(50,90).", ".rand(50,90).", 'Keep practicing consonants.')");
    }
    
    // Child Activities
    $cats = ['article', 'real_life', 'website_game', 'speech', 'motor', 'cognitive', 'social'];
    for($a=0; $a<5; $a++) {
        $cat = $cats[array_rand($cats)];
        $connect->query("INSERT INTO child_activities (child_id, title, description, category, duration_minutes, is_completed, completed_at, points_earned) VALUES ($cid, 'Activity $a', 'Fun learning activity', '$cat', 15, 1, '".randomDateInPast(30)."', 50)");
    }
}

// 7. Support Tickets
for($i=0; $i<10; $i++) {
    $uid = $parent_ids[array_rand($parent_ids)];
    $connect->query("INSERT INTO support_tickets (user_id, subject, priority, status, created_at) VALUES ($uid, 'Issue with booking', 'medium', 'resolved', '".randomDateInPast(60)."')");
    $tid = $connect->lastInsertId();
    $connect->query("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES ($tid, $uid, 'user', 'I cannot book a slot.')");
    $connect->query("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES ($tid, 1, 'admin', 'This has been fixed.')");
}

$connect->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Data Seeding Complete!\n";
?>

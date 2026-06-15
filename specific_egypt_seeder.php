<?php
include 'connection.php';

echo "Running specific egypt seeder for Moaz, Salsabel, Mallak...\n";

// 1. Get their user IDs
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");

$stmt->execute(['moaz@gmail.com']);
$moaz_user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$moaz_user) { echo "Moaz not found!\n"; exit; }
$moaz_user_id = $moaz_user['user_id'];

$stmt->execute(['salsabel@gmail.com']);
$salsabel_user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$salsabel_user) { echo "Salsabel not found!\n"; exit; }
$salsabel_user_id = $salsabel_user['user_id'];

$stmt->execute(['mallak@gmail.com']);
$mallak_user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$mallak_user) { echo "Mallak not found!\n"; exit; }
$mallak_user_id = $mallak_user['user_id'];

// Get specific IDs
$moaz_id = $moaz_user_id;
$salsabel_id = $salsabel_user_id;
$mallak_clinic_id = $connect->query("SELECT clinic_id FROM clinic WHERE email = 'mallak@gmail.com'")->fetchColumn();

// Setup Moaz 3 children
// Omar (4 yrs)
$stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, gender, birth_day, birth_month, birth_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([uniqid('ssn_'), $moaz_id, 'Omar', 'Moaz', 'male', 10, 3, 2022]);
$omar_id = $connect->lastInsertId();

// Laila (2 yrs)
$stmt->execute([uniqid('ssn_'), $moaz_id, 'Laila', 'Moaz', 'female', 15, 5, 2024]);
$laila_id = $connect->lastInsertId();

// Yassin (8 mo)
$stmt->execute([uniqid('ssn_'), $moaz_id, 'Yassin', 'Moaz', 'male', 1, 10, 2025]);
$yassin_id = $connect->lastInsertId();

echo "Children created for Moaz: Omar ($omar_id), Laila ($laila_id), Yassin ($yassin_id)\n";

// Insert child_activities
$activities = [
    ['title' => 'Saying 5 simple words', 'category' => 'speech', 'duration' => 10],
    ['title' => 'Walking up stairs', 'category' => 'motor', 'duration' => 15],
    ['title' => 'Pointing to objects', 'category' => 'cognitive', 'duration' => 5],
    ['title' => 'Playing with blocks', 'category' => 'real_life', 'duration' => 20],
    ['title' => 'Pronouncing R sound', 'category' => 'speech', 'duration' => 10],
];

foreach([$omar_id, $laila_id, $yassin_id] as $child_id) {
    foreach($activities as $act) {
        $stmt = $connect->prepare("INSERT INTO child_activities (child_id, title, category, duration_minutes, is_completed, completed_at, points_earned) VALUES (?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY), ?)");
        $stmt->execute([$child_id, $act['title'], $act['category'], $act['duration'], rand(1, 20), rand(10, 50)]);
    }
}

// Generate historical speech analysis
$stmt = $connect->prepare("INSERT INTO voice_sample (child_id, feedback, audio_url, sent_at) VALUES (?, ?, 'dummy.wav', DATE_SUB(NOW(), INTERVAL ? DAY))");
for($i = 1; $i <= 6; $i++) {
    $stmt->execute([$omar_id, "Pronunciation is improving steadily.", $i*15]);
    $stmt->execute([$laila_id, "Needs practice with complex sounds.", $i*15]);
}

// Growth records
$stmt = $connect->prepare("INSERT INTO growth_record (child_id, recorded_at, weight, height, head_circumference) VALUES (?, DATE_SUB(NOW(), INTERVAL ? MONTH), ?, ?, ?)");
for($i = 1; $i <= 12; $i++) {
    $stmt->execute([$omar_id, $i, 15 - ($i*0.2), 100 - ($i*1.5), 50 - ($i*0.1)]);
    $stmt->execute([$laila_id, $i, 12 - ($i*0.3), 85 - ($i*2), 48 - ($i*0.1)]);
    $stmt->execute([$yassin_id, $i, 8 - ($i*0.5), 70 - ($i*3), 45 - ($i*0.2)]);
}

// Salsabel availability
$connect->query("DELETE FROM specialist_availability WHERE specialist_id = $salsabel_id");
$stmt = $connect->prepare("INSERT INTO specialist_availability (specialist_id, day_of_week, start_time, end_time) VALUES (?, ?, '09:00:00', '17:00:00')");
foreach([1, 2, 3, 4] as $day) {
    $stmt->execute([$salsabel_id, $day]);
}

// Add payments for moaz
$connect->query("INSERT INTO payment (parent_id, amount_pre_discount, amount_post_discount, method, status, paid_at) VALUES 
($moaz_id, 250.00, 250.00, 'credit_card', 'completed', DATE_SUB(NOW(), INTERVAL 1 MONTH)),
($moaz_id, 250.00, 250.00, 'credit_card', 'completed', DATE_SUB(NOW(), INTERVAL 2 MONTH))");
$payment1 = $connect->query("SELECT payment_id FROM payment WHERE parent_id = $moaz_id LIMIT 1")->fetchColumn();
$payment2 = $connect->query("SELECT payment_id FROM payment WHERE parent_id = $moaz_id ORDER BY payment_id DESC LIMIT 1")->fetchColumn();

// Appointments between Moaz children and Salsabel
$stmt = $connect->prepare("INSERT INTO appointment (parent_id, specialist_id, scheduled_at, status, type, payment_id, child_id) VALUES (?, ?, ?, ?, 'online', ?, ?)");

// Past completed
$stmt->execute([$moaz_id, $salsabel_id, '2026-05-10 10:00:00', 'completed', $payment1, $omar_id]);
$stmt->execute([$moaz_id, $salsabel_id, '2026-05-20 14:00:00', 'completed', $payment2, $laila_id]);

// Future scheduled
$stmt->execute([$moaz_id, $salsabel_id, '2026-07-05 11:00:00', 'scheduled', $payment1, $yassin_id]);
$stmt->execute([$moaz_id, $salsabel_id, '2026-07-15 09:30:00', 'scheduled', $payment2, $omar_id]);

// Doctor reports for past appointments
$connect->query("INSERT INTO doctor_report (child_id, specialist_id, doctor_notes, report_date) VALUES 
($omar_id, $salsabel_id, 'Omar is making excellent progress in his speech therapy.', CURDATE()),
($laila_id, $salsabel_id, 'Laila requires a few more motor skill exercises.', CURDATE())");

// Mallak clinic reviews
if($mallak_clinic_id) {
    $connect->query("INSERT INTO clinic_reviews (clinic_id, parent_id, appointment_id, rating, comment) VALUES 
    ($mallak_clinic_id, $moaz_id, 1, 5, 'Exceptional clinic, Mallak is extremely welcoming!'),
    ($mallak_clinic_id, $moaz_id, 2, 4, 'Great facilities and doctors.')");
    $connect->query("UPDATE clinic SET rating = 4.5, status = 'verified' WHERE clinic_id = $mallak_clinic_id");
    
    // Tie Salsabel to Mallak clinic
    $connect->query("UPDATE specialist SET clinic_id = $mallak_clinic_id WHERE specialist_id = $salsabel_id");
}

// Notifications for admin
$connect->query("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES 
(1, 'New Clinic Registered', 'Mallak Clinic has registered and is pending approval.', 'system', 0, NOW()),
(1, 'High Traffic Alert', 'Unusual amount of logins detected.', 'system', 0, NOW())");

echo "Seeding completed successfully.\n";
?>

<?php
include 'connection.php';

echo "Seeding Mallak Clinic Data...\n";

// Get Mallak clinic ID
$stmt = $connect->query("SELECT clinic_id FROM clinic WHERE email = 'mallak@gmail.com'");
$clinic_id = $stmt->fetchColumn();

if (!$clinic_id) {
    echo "Clinic not found.\n";
    exit;
}

// 1. Fix Salsabel
$stmt = $connect->query("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$salsabel_id = $stmt->fetchColumn();

if ($salsabel_id) {
    // Make sure she is in specialist table with names
    $connect->query("INSERT IGNORE INTO specialist (specialist_id, first_name, last_name, specialization, experience_years, clinic_id) VALUES ($salsabel_id, 'Salsabel', 'Ahmed', 'Speech Therapy', 5, $clinic_id) ON DUPLICATE KEY UPDATE first_name='Salsabel', last_name='Ahmed', clinic_id=$clinic_id");
}

// 2. Add New Specialists
$specs = [
    ['email' => 'youssef@mallak.com', 'fname' => 'Youssef', 'lname' => 'Hassan', 'spec' => 'Occupational Therapy'],
    ['email' => 'amina@mallak.com', 'fname' => 'Amina', 'lname' => 'Fawzy', 'spec' => 'Behavioral Therapy']
];

$spec_ids = [];

foreach ($specs as $s) {
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$s['email']]);
    $uid = $stmt->fetchColumn();

    if (!$uid) {
        $pwd = password_hash('pass123', PASSWORD_DEFAULT);
        $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, 'specialist', 'active')")->execute([$s['fname'], $s['lname'], $s['email'], $pwd]);
        $uid = $connect->lastInsertId();
    }
    
    $connect->prepare("INSERT IGNORE INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years) VALUES (?, ?, ?, ?, ?, 8) ON DUPLICATE KEY UPDATE clinic_id = ?")->execute([$uid, $clinic_id, $s['fname'], $s['lname'], $s['spec'], $clinic_id]);
    $spec_ids[] = $uid;
}

// 3. Ensure we have parents & children to assign
$parent_id = $connect->query("SELECT user_id FROM users WHERE role='parent' LIMIT 1")->fetchColumn();
if (!$parent_id) {
    $pwd = password_hash('pass123', PASSWORD_DEFAULT);
    $connect->query("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES ('Test', 'Parent', 'testparent@test.com', '$pwd', 'parent', 'active')");
    $parent_id = $connect->lastInsertId();
    $connect->query("INSERT INTO parent (parent_id) VALUES ($parent_id)");
}

$child_id = $connect->query("SELECT child_id FROM child WHERE parent_id=$parent_id LIMIT 1")->fetchColumn();
if (!$child_id) {
    $connect->query("INSERT INTO child (parent_id, first_name, last_name, birth_year, birth_month, birth_day, gender) VALUES ($parent_id, 'Ali', 'Parent', 2020, 5, 1, 'male')");
    $child_id = $connect->lastInsertId();
}

// 4. Generate Appointments & Payments (Revenue Data)
// Generate for the past 30 days to populate charts
$all_specialists = array_merge([$salsabel_id], $spec_ids);

foreach ($all_specialists as $sid) {
    for ($i = 0; $i < 5; $i++) {
        $days_ago = rand(1, 28);
        $amount = rand(300, 800);
        $method = ['credit_card', 'cash'][rand(0, 1)];
        $status = ['completed', 'completed', 'scheduled', 'cancelled'][rand(0, 3)];
        
        // Payment
        $connect->query("INSERT INTO payment (parent_id, amount_pre_discount, amount_post_discount, method, status, paid_at) VALUES ($parent_id, $amount, $amount, '$method', 'paid', DATE_SUB(NOW(), INTERVAL $days_ago DAY))");
        $payment_id = $connect->lastInsertId();
        
        // Appointment
        $connect->query("INSERT INTO appointment (parent_id, specialist_id, child_id, payment_id, scheduled_at, status, type) VALUES ($parent_id, $sid, $child_id, $payment_id, DATE_SUB(NOW(), INTERVAL $days_ago DAY), '$status', 'in-person')");
    }
}

// Update reviews for clinic rating
$connect->query("INSERT INTO clinic_reviews (clinic_id, parent_id, appointment_id, rating, comment) VALUES ($clinic_id, $parent_id, 1, 5, 'Great clinic, very professional staff.') ON DUPLICATE KEY UPDATE rating=5");

echo "Mallak Clinic Seeded Successfully!\n";

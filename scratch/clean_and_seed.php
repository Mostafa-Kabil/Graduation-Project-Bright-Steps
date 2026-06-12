<?php
require __DIR__ . '/../connection.php';

echo "Cleaning up fake 'maryam gharib' users...\n";
$stmt = $connect->query("SELECT user_id, first_name, last_name, email FROM users WHERE first_name LIKE '%maryam%' AND last_name LIKE '%gharib%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "Found user to delete: {$u['first_name']} {$u['last_name']} (ID: {$u['user_id']})\n";
    $uid = $u['user_id'];
    
    // Delete specialist related records in case they were a doctor
    $connect->query("DELETE FROM doctor_report WHERE specialist_id = $uid OR child_id IN (SELECT child_id FROM child WHERE parent_id = $uid)");
    $connect->query("DELETE FROM message WHERE sender_id = $uid OR receiver_id = $uid");
    $connect->query("DELETE FROM appointment WHERE specialist_id = $uid");
    $connect->query("DELETE FROM doctor_onboarding WHERE doctor_id = $uid");
    $connect->query("DELETE FROM appointment_slots WHERE doctor_id = $uid");
    $connect->query("DELETE FROM specialist WHERE specialist_id = $uid");
    
    // Delete parent/child
    $connect->query("DELETE FROM child WHERE parent_id = $uid");
    $connect->query("DELETE FROM parent WHERE parent_id = $uid");
    
    // Finally delete user
    $connect->query("DELETE FROM users WHERE user_id = $uid");
    
    echo "Deleted user $uid.\n";
}

echo "\nCleaning up other obviously fake test users (from test scripts)...\n";
// Sometimes test users have 'test' in their email
$stmt = $connect->query("SELECT user_id, first_name, last_name, email FROM users WHERE email LIKE '%test%' AND email != 'test@test.com'"); // Keeping main test user if any, but deleting generated ones
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    if (strpos($u['first_name'], 'test') !== false || strpos($u['email'], 'maryam') !== false || strpos($u['first_name'], 'Maryam') !== false) {
        $uid = $u['user_id'];
        $connect->query("DELETE FROM doctor_report WHERE specialist_id = $uid OR child_id IN (SELECT child_id FROM child WHERE parent_id = $uid)");
        $connect->query("DELETE FROM message WHERE sender_id = $uid OR receiver_id = $uid");
        $connect->query("DELETE FROM appointment WHERE specialist_id = $uid OR parent_id = $uid");
        $connect->query("DELETE FROM doctor_onboarding WHERE doctor_id = $uid");
        $connect->query("DELETE FROM appointment_slots WHERE doctor_id = $uid");
        $connect->query("DELETE FROM specialist WHERE specialist_id = $uid");
        
        $connect->query("DELETE FROM child WHERE parent_id = $uid");
        $connect->query("DELETE FROM parent WHERE parent_id = $uid");
        $connect->query("DELETE FROM users WHERE user_id = $uid");
        echo "Deleted fake user {$u['email']} (ID: $uid).\n";
    }
}

// Add more realistic data for test
echo "\nAdding realistic test patient data...\n";

// Get a specialist
$stmt = $connect->query("SELECT specialist_id FROM specialist LIMIT 1");
$doc = $stmt->fetch();
if (!$doc) {
    echo "No specialist found. Run seed_doctor_data.php first.\n";
    exit;
}
$doctor_id = $doc['specialist_id'];

$new_patients = [
    ['fname' => 'Ahmed', 'lname' => 'Hassan', 'email' => 'ahmed.h.parent@example.com', 'child' => 'Omar'],
    ['fname' => 'Nadia', 'lname' => 'Kamel', 'email' => 'nadia.k.parent@example.com', 'child' => 'Lina'],
    ['fname' => 'Youssef', 'lname' => 'Ibrahim', 'email' => 'youssef.i.parent@example.com', 'child' => 'Ziad']
];

foreach ($new_patients as $p) {
    // Insert user if not exists
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$p['email']]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $uid = $existing;
    } else {
        $ins_u = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'parent')");
        $ins_u->execute([$p['fname'], $p['lname'], $p['email'], password_hash('password123', PASSWORD_DEFAULT)]);
        $uid = $connect->lastInsertId();
    }
    
    // Insert parent if not exists
    $connect->query("INSERT IGNORE INTO parent (parent_id) VALUES ($uid)");
    
    // Insert child
    $max_c = $connect->query("SELECT MAX(child_id) FROM child")->fetchColumn();
    $next_cid = $max_c ? $max_c + 1 : 1;
    $ins_c = $connect->prepare("INSERT INTO child (child_id, parent_id, first_name, last_name, gender, birth_year, birth_month, birth_day) VALUES (?, ?, ?, ?, 'Male', 2020, 5, 12)");
    $ins_c->execute([$next_cid, $uid, $p['child'], $p['lname']]);
    
    // Insert some appointments
    $ins_app = $connect->prepare("INSERT INTO appointment (parent_id, specialist_id, scheduled_at, status, type, payment_id, comment) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?, 'online', ?, ?)");
    
    // Randomize some appointments
    $statuses = [
        ['status' => 'completed', 'days' => -10, 'comment' => 'Initial consultation'],
        ['status' => 'completed', 'days' => -3, 'comment' => 'Follow-up'],
        ['status' => 'pending', 'days' => 2, 'comment' => 'Next checkup'],
        ['status' => 'scheduled', 'days' => 5, 'comment' => 'Therapy session']
    ];
    
    // Randomly pick 2 appointments per user
    shuffle($statuses);
    for ($i = 0; $i < 2; $i++) {
        $s = $statuses[$i];
        
        $connect->query("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status) VALUES (500, 500, 'credit_card', 'completed')");
        $payment_id = $connect->lastInsertId();
        
        $ins_app->execute([$uid, $doctor_id, $s['days'], $s['status'], $payment_id, $s['comment']]);
    }
    
    echo "Added user {$p['fname']} {$p['lname']} with child {$p['child']} and 2 appointments.\n";
}

echo "Done.\n";
?>

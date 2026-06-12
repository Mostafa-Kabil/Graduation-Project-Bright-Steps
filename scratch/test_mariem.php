<?php
require __DIR__ . '/../connection.php';

$stmt = $connect->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = 5116");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    $parent_id = $users[0]['user_id'];
    
    // Check if in parent table
    $p_stmt = $connect->prepare("SELECT * FROM parent WHERE parent_id = ?");
    $p_stmt->execute([$parent_id]);
    if ($p_stmt->rowCount() == 0) {
        $ins_p = $connect->prepare("INSERT INTO parent (parent_id) VALUES (?)");
        $ins_p->execute([$parent_id]);
        echo "Created parent record for $parent_id\n";
    }
    
    // Get a doctor
    $stmt = $connect->query("SELECT specialist_id FROM specialist LIMIT 1");
    $doc = $stmt->fetch();
    $doctor_id = $doc ? $doc['specialist_id'] : 0;
    
    echo "Found Parent: $parent_id, Doctor: $doctor_id\n";
    
    if ($doctor_id && $parent_id) {
        $statuses = [
            ['status' => 'pending', 'days' => 2],
            ['status' => 'confirmed', 'days' => 4],
            ['status' => 'scheduled', 'days' => 6],
            ['status' => 'completed', 'days' => -5],
            ['status' => 'cancelled', 'days' => -2]
        ];
        
        $insert = $connect->prepare("INSERT INTO appointment (parent_id, specialist_id, scheduled_at, status, type, comment, payment_id) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?, 'online', 'Test data for UI', ?)");
        
        foreach ($statuses as $s) {
            $ins_pay = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status) VALUES (500, 500, 'credit_card', 'completed')");
            $ins_pay->execute();
            $payment_id = $connect->lastInsertId();
            
            $insert->execute([$parent_id, $doctor_id, $s['days'], $s['status'], $payment_id]);
            echo "Inserted {$s['status']} appointment.\n";
        }
        echo "Done.\n";
    }
} else {
    echo "User maryam gharib not found.\n";
}
?>

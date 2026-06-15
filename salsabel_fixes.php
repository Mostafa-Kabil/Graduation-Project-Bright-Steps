<?php
require 'connection.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting Salsabel Specific Fixes...\n";

$connect->query("SET FOREIGN_KEY_CHECKS=0;");

// Get Salsabel
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$stmt->execute();
$sal_user = $stmt->fetchColumn();

// Get Moaz
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'moaz@gmail.com'");
$stmt->execute();
$moaz_user = $stmt->fetchColumn();

if ($sal_user && $moaz_user) {
    // 1. CONFIRMED APPOINTMENTS
    // Update a few future appointments to 'confirmed'
    $connect->query("UPDATE appointment SET status = 'confirmed' WHERE specialist_id = $sal_user AND scheduled_at > NOW() LIMIT 3");
    
    // Update a few past appointments to 'completed' (for 'On Track' metric)
    $connect->query("UPDATE appointment SET status = 'completed' WHERE specialist_id = $sal_user AND scheduled_at < NOW() AND status != 'completed' LIMIT 15");

    echo "Updated appointment statuses to 'confirmed' and 'completed'.\n";

    // 2. SHARED REPORTS
    $stmt = $connect->prepare("SELECT child_id FROM child WHERE parent_id = (SELECT parent_id FROM parent WHERE parent_id = ?)");
    $stmt->execute([$moaz_user]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($children) > 0) {
        $sr_stmt = $connect->prepare("INSERT INTO shared_reports (doctor_id, parent_id, child_id, report_type, file_path, is_shared, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)");
        
        $types = ['speech-assessment', 'behavior-assessment', 'growth-assessment'];
        
        foreach ($children as $c) {
            $cid = $c['child_id'];
            $type = $types[array_rand($types)];
            $path = "reports/{$cid}_{$type}.pdf";
            $date = date("Y-m-d H:i:s", time() - rand(86400, 86400 * 30));
            
            $sr_stmt->execute([$sal_user, $moaz_user, $cid, $type, $path, $date]);
        }
        echo "Inserted shared reports for Moaz's children.\n";
    }

    // 3. MORE MESSAGES
    $mstmt = $connect->prepare("INSERT INTO message (sender_id, receiver_id, content, is_read, sent_at) VALUES (?, ?, ?, 1, ?)");
    
    $chat = [
        ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "Hello Moaz, I have reviewed the shared reports for Omar. His progress is excellent.", 'days' => 2],
        ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "Thank you doctor. I was worried about his speech delay.", 'days' => 2],
        ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "No need to worry. We just need to keep up with the daily 20-minute exercises.", 'days' => 2],
        ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "Will do. Should I schedule the next session for next week?", 'days' => 1],
        ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "Yes, please do. I have some openings on Wednesday.", 'days' => 1],
        ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "Great, I just booked it. See you then!", 'days' => 1],
        ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "Looking forward to it. Have a great day!", 'days' => 0]
    ];

    foreach ($chat as $msg) {
        $sent_date = date("Y-m-d H:i:s", time() - ($msg['days'] * 86400) + rand(1000, 5000));
        $mstmt->execute([$msg['sender'], $msg['receiver'], $msg['msg'], $sent_date]);
    }
    echo "Inserted more chat messages between Moaz and Salsabel.\n";
}

echo "Fixes Complete!\n";
?>

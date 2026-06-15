<?php
require 'connection.php';
$connect->query("SET FOREIGN_KEY_CHECKS=0;");

$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$stmt->execute();
$sal_user = $stmt->fetchColumn();

// Get parents who have appointments with Salsabel
$stmt = $connect->prepare("
    SELECT DISTINCT p.parent_id, u.first_name, u.last_name 
    FROM appointment a 
    JOIN parent p ON a.parent_id = p.parent_id 
    JOIN users u ON u.user_id = p.parent_id 
    WHERE a.specialist_id = ? AND u.email != 'moaz@gmail.com' LIMIT 5
");
$stmt->execute([$sal_user]);
$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mstmt = $connect->prepare("INSERT INTO message (sender_id, receiver_id, content, is_read, sent_at) VALUES (?, ?, ?, 1, ?)");

foreach ($parents as $p) {
    $pid = $p['parent_id'];
    $name = $p['first_name'];
    
    $chat = [
        ['sender' => $pid, 'receiver' => $sal_user, 'msg' => "Hello Dr. Salsabel, I have a question about the recent session.", 'days' => rand(1, 10)],
        ['sender' => $sal_user, 'receiver' => $pid, 'msg' => "Hi $name! Of course, how can I help you?", 'days' => rand(1, 10)],
        ['sender' => $pid, 'receiver' => $sal_user, 'msg' => "Can we reschedule our upcoming appointment?", 'days' => rand(0, 5)],
        ['sender' => $sal_user, 'receiver' => $pid, 'msg' => "Sure. Please request a new slot through the app and I'll confirm it.", 'days' => rand(0, 5)]
    ];
    
    // Sort chat by days descending to simulate chronological order
    usort($chat, function($a, $b) { return $b['days'] <=> $a['days']; });

    foreach ($chat as $msg) {
        $sent_date = date("Y-m-d H:i:s", time() - ($msg['days'] * 86400) + rand(1000, 5000));
        $mstmt->execute([$msg['sender'], $msg['receiver'], $msg['msg'], $sent_date]);
    }
}
echo "Added messages for " . count($parents) . " other parents.";

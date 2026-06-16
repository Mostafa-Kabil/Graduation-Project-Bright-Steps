<?php
require_once 'connection.php';
// Get activities for child 207
$stmt = $connect->query("SELECT activity_id, child_id, title, category, is_completed FROM child_activities WHERE child_id = 207 AND is_completed = 0");
$acts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Child 207 activities:\n";
print_r($acts);

// Try inserting manually
try {
    $stmt2 = $connect->prepare("INSERT INTO child_activities (child_id, title, category, is_completed, completed_at, points_earned) VALUES (?, ?, ?, 1, NOW(), ?)");
    $stmt2->execute([207, 'Sing-Along Time', 'speech', 15]);
    echo "Insert successful\n";
} catch (Exception $e) {
    echo "Insert failed: " . $e->getMessage() . "\n";
}

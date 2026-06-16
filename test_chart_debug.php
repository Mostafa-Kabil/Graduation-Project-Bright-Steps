<?php
require 'connection.php';

// Test what data the chart API would return for child_id=207
$childId = 207;
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('D', strtotime("-{$i} days"));
    $stmtC = $connect->prepare(
        "SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND DATE(completed_at) = ?"
    );
    $stmtC->execute([$childId, $date]);
    $days[] = ['label' => $label, 'date' => $date, 'count' => (int)$stmtC->fetchColumn()];
}
echo "Chart data for child 207:\n";
print_r($days);

// Also show all completed activities
echo "\nAll completed activities for child 207:\n";
$stmt = $connect->query("SELECT activity_id, title, category, is_completed, completed_at FROM child_activities WHERE child_id = 207 AND is_completed = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Show all activities for child 207 
echo "\nAll activities for child 207:\n";
$stmt2 = $connect->query("SELECT activity_id, title, category, is_completed, completed_at FROM child_activities WHERE child_id = 207 ORDER BY activity_id DESC LIMIT 15");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

<?php
require_once 'connection.php';
// Reset the test activity back to uncompleted
$stmt = $connect->prepare("UPDATE child_activities SET is_completed = 0, completed_at = NULL, points_earned = 0 WHERE activity_id = 487");
$stmt->execute();
echo "Reset done. Rows: " . $stmt->rowCount() . "\n";

<?php
require 'connection.php';
$childId = 209;
$title = "Sing-Along Time";
$category = "SPEECH 🎥";
$pointsToAward = 15;

try {
    $stmt = $connect->prepare("INSERT INTO child_activities (child_id, title, category, is_completed, completed_at, points_earned) VALUES (?, ?, ?, 1, NOW(), ?)");
    $stmt->execute([$childId, $title, $category, $pointsToAward]);
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

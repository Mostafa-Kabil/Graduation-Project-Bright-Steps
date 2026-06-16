<?php
require 'connection.php';

$childId = 207;

// Motor milestones 
$s7 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = :cid");
$s7->execute(['cid' => $childId]);
$motorTotal = (int)$s7->fetchColumn();

$s8 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = :cid AND is_achieved = 1");
$s8->execute(['cid' => $childId]);
$motorDone = (int)$s8->fetchColumn();

$motorPct = $motorTotal > 0 ? round(($motorDone / $motorTotal) * 100) : 0;

echo "Motor total: $motorTotal\n";
echo "Motor done: $motorDone\n";
echo "Motor pct: $motorPct%\n";

// Also check activities completed
$s9 = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = :cid AND is_completed = 1");
$s9->execute(['cid' => $childId]);
echo "\nActivities completed: " . $s9->fetchColumn() . "\n";

<?php
require 'connection.php';

$parentId = 76; // moaz's parent id

$children = [];
$sql = "SELECT child_id, first_name, last_name, birth_day, birth_month, birth_year, gender, ssn
        FROM child WHERE parent_id = :parent_id ORDER BY child_id ASC";
$stmt = $connect->prepare($sql);
$stmt->execute(['parent_id' => $parentId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($children as &$ch) {
    // Motor milestones completion percentage
    try {
        $s7 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = :cid");
        $s7->execute(['cid' => $ch['child_id']]);
        $motorTotal = (int)$s7->fetchColumn();

        $s8 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = :cid AND is_achieved = 1");
        $s8->execute(['cid' => $ch['child_id']]);
        $motorDone = (int)$s8->fetchColumn();

        $ch['_motorPct'] = $motorTotal > 0 ? round(($motorDone / $motorTotal) * 100) : 0;
    } catch (Exception $e) { $ch['_motorPct'] = 0; }

    // Activities completed
    try {
        $s9 = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = :cid AND is_completed = 1");
        $s9->execute(['cid' => $ch['child_id']]);
        $ch['activities_completed'] = (int)$s9->fetchColumn();
    } catch (Exception $e) { $ch['activities_completed'] = 0; }
}
unset($ch);

echo "Children data that would be in dashboardData:\n";
foreach ($children as $c) {
    echo "  Child {$c['child_id']} ({$c['first_name']}): _motorPct={$c['_motorPct']}, activities_completed={$c['activities_completed']}\n";
}

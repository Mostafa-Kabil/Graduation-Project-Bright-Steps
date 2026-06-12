<?php
include 'connection.php';

$validNames = [
    'First Steps', 'Rising Star', 'Weekly Champion', 'Consistency King',
    'Growth Tracker', 'Health Champion', 'Voice Hero', 'Speech Explorer',
    'Motor Master', 'Monthly Master', 'Super Parent', 'Article Reader',
    'Bookworm', 'Game Master'
];

try {
    $connect->beginTransaction();
    
    // Create mapping of name -> best ID (keep the lowest ID for each name if it exists)
    $stmt = $connect->query("SELECT * FROM badge ORDER BY badge_id ASC");
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $keptIds = [];
    $keptNames = [];
    
    foreach ($badges as $b) {
        if (in_array($b['name'], $validNames) && !in_array($b['name'], $keptNames)) {
            $keptIds[] = $b['badge_id'];
            $keptNames[] = $b['name'];
        }
    }
    
    // For any badge in child_badge that we are removing, re-map to the kept ID if possible
    foreach ($badges as $b) {
        if (!in_array($b['badge_id'], $keptIds)) {
            // we are removing this badge. What is its name?
            $name = $b['name'];
            // If the name is valid, we have another ID for it in keptIds.
            $newId = null;
            if (in_array($name, $validNames)) {
                $idx = array_search($name, $keptNames);
                $newId = $keptIds[$idx];
            } else {
                // If it's completely invalid, just map it to First Steps (index 0)
                $newId = $keptIds[0] ?? 1;
            }
            
            // Re-map in child_badge using UPDATE IGNORE equivalent
            $connect->prepare("UPDATE IGNORE child_badge SET badge_id = ? WHERE badge_id = ?")->execute([$newId, $b['badge_id']]);
            $connect->prepare("DELETE FROM child_badge WHERE badge_id = ?")->execute([$b['badge_id']]);
            
            // Delete from badge
            $connect->prepare("DELETE FROM badge WHERE badge_id = ?")->execute([$b['badge_id']]);
        }
    }
    
    // Ensure all 14 valid badges exist
    $missing = array_diff($validNames, $keptNames);
    $icons = [
        'First Steps' => 'first_steps', 'Rising Star' => 'rising_star', 'Weekly Champion' => 'weekly_champion',
        'Consistency King' => 'consistency_king', 'Growth Tracker' => 'growth_tracker', 'Health Champion' => 'health_champion',
        'Voice Hero' => 'voice_hero', 'Speech Explorer' => 'speech_explorer', 'Motor Master' => 'motor_master',
        'Monthly Master' => 'monthly_master', 'Super Parent' => 'super_parent', 'Article Reader' => 'article_reader',
        'Bookworm' => 'bookworm', 'Game Master' => 'game_master'
    ];
    $descs = [
        'First Steps' => 'Complete your first activity', 'Rising Star' => 'Maintain a 3-day login streak',
        'Weekly Champion' => 'Complete 5 activities in a week', 'Consistency King' => 'Maintain a 7-day login streak',
        'Growth Tracker' => 'Log 5 growth measurements', 'Health Champion' => 'Log 15 growth measurements',
        'Voice Hero' => 'Upload a voice sample', 'Speech Explorer' => 'Upload 5 voice samples',
        'Motor Master' => 'Complete 5 motor milestones', 'Monthly Master' => 'Complete 20 activities in a month',
        'Super Parent' => 'Login for 30 consecutive days', 'Article Reader' => 'Read your first article',
        'Bookworm' => 'Read 10 articles', 'Game Master' => 'Play 5 games'
    ];
    
    foreach ($missing as $m) {
        $stmt = $connect->prepare("INSERT INTO badge (name, description, icon) VALUES (?, ?, ?)");
        $stmt->execute([$m, $descs[$m], $icons[$m]]);
    }
    
    $connect->commit();
    echo "Badges cleaned up successfully!\n";
} catch(Exception $e) {
    $connect->rollBack();
    echo "Error: " . $e->getMessage();
}

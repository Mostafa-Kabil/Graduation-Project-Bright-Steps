<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connection.php';

try {
    $badges = [
        ['name' => 'First Steps', 'description' => 'Complete your first activity', 'icon' => '👶'],
        ['name' => 'Rising Star', 'description' => 'Maintain a 3-day streak', 'icon' => '⭐'],
        ['name' => 'Weekly Champion', 'description' => 'Complete 5 activities in a week', 'icon' => '🥇'],
        ['name' => 'Consistency King', 'description' => 'Maintain a 7-day streak', 'icon' => '👑'],
        ['name' => 'Growth Tracker', 'description' => 'Log your first growth record', 'icon' => '📈'],
        ['name' => 'Health Champion', 'description' => 'Log 5 growth records', 'icon' => '💪'],
        ['name' => 'Voice Hero', 'description' => 'Record your first voice sample', 'icon' => '🎙️'],
        ['name' => 'Speech Explorer', 'description' => 'Record 5 voice samples', 'icon' => '🗣️'],
        ['name' => 'Motor Master', 'description' => 'Complete 5 motor milestones', 'icon' => '🏃'],
        ['name' => 'Monthly Master', 'description' => 'Complete 20 activities in a month', 'icon' => '📅'],
        ['name' => 'Super Parent', 'description' => 'Maintain a 30-day streak', 'icon' => '🦸'],
        ['name' => 'Article Reader', 'description' => 'Read your first article', 'icon' => '📖'],
        ['name' => 'Bookworm', 'description' => 'Read 10 articles', 'icon' => '📚'],
        ['name' => 'Game Master', 'description' => 'Play 5 educational games', 'icon' => '🎮']
    ];

    $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM badge WHERE name = ?");
    $stmtInsert = $connect->prepare("INSERT INTO badge (name, description, icon) VALUES (?, ?, ?)");

    $added = 0;
    foreach ($badges as $b) {
        $stmtCheck->execute([$b['name']]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$b['name'], $b['description'], $b['icon']]);
            $added++;
        }
    }

    echo "Badges checked. Added $added new badges to the database.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

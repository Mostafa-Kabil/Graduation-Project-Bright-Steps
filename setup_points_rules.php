<?php
// setup_points_rules.php
include 'connection.php';
$connect->exec("
CREATE TABLE IF NOT EXISTS points_rules (
    rule_key VARCHAR(50) PRIMARY KEY,
    points INT NOT NULL,
    daily_cap INT DEFAULT NULL,
    weekly_cap INT DEFAULT NULL,
    cooldown_minutes INT DEFAULT 0,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT 1
)");

// Insert default rules based on requirements
$rules = [
    ['daily_login', 10, 10, 70, 1440, 'Log in once per day'],
    ['log_growth', 25, 25, 100, 43200, 'Record growth (1 month cooldown)'], // 43200 mins = 30 days
    ['record_speech', 15, 45, 200, 60, 'Record child speaking'],
    ['log_milestone', 30, 90, 300, 30, 'Mark developmental milestone'],
    ['complete_motor_activity', 20, 60, 250, 30, 'Finish motor exercise'],
    ['weekly_goal', 100, null, 100, 10080, 'Complete weekly goals'],
    ['attend_appointment', 50, 50, 100, 1440, 'Complete appointment'],
    ['submit_feedback', 20, 40, 80, 60, 'Provide feedback after an appointment'],
    ['complete_profile', 100, 100, null, 0, 'Fill parent profile'],
    ['complete_child_profile', 75, 150, null, 0, 'Fill child profile'],
    ['refer_parent', 200, null, null, 0, 'Refer another parent to join'],
    ['read_article', 5, 25, 100, 5, 'Read parenting article'],
    ['complete_activity', 35, 70, 200, 30, 'Complete recommended activity']
];

$stmt = $connect->prepare("INSERT IGNORE INTO points_rules (rule_key, points, daily_cap, weekly_cap, cooldown_minutes, description) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($rules as $r) {
    $stmt->execute($r);
}

// Remove share_story if it exists from older logic
$connect->exec("DELETE FROM points_rules WHERE rule_key = 'share_story'");

echo "Points rules table created and seeded.";
?>

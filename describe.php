<?php
include 'connection.php';

// Check which badges are actually used in child_badge
$stmt = $connect->query("SELECT badge_id, COUNT(*) as c FROM child_badge GROUP BY badge_id");
$used = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Used badges in child_badge:\n";
print_r($used);

// The problem is that parent dashboard checks for specific badge names, or relies on specific badges.
// Let's look at the parent dashboard's API for badges: api_points_engine.php

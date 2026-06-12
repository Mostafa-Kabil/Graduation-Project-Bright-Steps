<?php
include 'connection.php';

$rules = [
    [1, 'read_article', 5, '+'],
    [2, 'complete_activity', 35, '+'],
    [3, 'streak_7day', 50, '+'],
    [4, 'streak_30day', 250, '+'],
    [5, 'streak_100day', 1000, '+'],
    [6, 'share_story', 100, '+'],
    [7, 'missed_checkin', 5, '-'],
    [8, 'free_consultation', 500, '-']
];

$stmt = $connect->prepare("UPDATE points_refrence SET action_name=?, points_value=?, adjust_sign=? WHERE refrence_id=?");
foreach ($rules as $r) {
    $stmt->execute([$r[1], $r[2], $r[3], $r[0]]);
}

echo "Rules updated.";

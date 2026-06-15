<?php
require 'connection.php';
// Find user id for mohamed mostafa
$stmt = $connect->query("SELECT user_id FROM users WHERE first_name LIKE '%mohamed%' AND last_name LIKE '%mostafa%'");
$user_id = $stmt->fetchColumn();

if ($user_id) {
    echo "Specialist ID is $user_id\n";
    // Check their availability on 2026-06-16
    $date = '2026-06-16';
    $dt = new DateTime($date);
    $day_of_week = (int)$dt->format('w');
    echo "Day of week for $date is $day_of_week\n";
    
    $avail = $connect->query("SELECT * FROM specialist_availability WHERE specialist_id = $user_id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($avail);
} else {
    echo "Specialist not found\n";
}

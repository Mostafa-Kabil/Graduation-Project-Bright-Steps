<?php
require 'connection.php';
$specialist_id = 64; // Dr. mohamed mostafa
// Insert availability for all days of the week, 9 AM to 5 PM
$connect->query("DELETE FROM specialist_availability WHERE specialist_id = $specialist_id");
$stmt = $connect->prepare("INSERT INTO specialist_availability (specialist_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, '09:00:00', '17:00:00', 1)");
for ($i = 0; $i <= 6; $i++) {
    $stmt->execute([$specialist_id, $i]);
}
echo "Inserted availability for all 7 days for Dr. mohamed mostafa.\n";

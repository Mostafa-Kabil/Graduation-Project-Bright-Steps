<?php
require 'connection.php';
$stmt = $connect->query("SELECT appointment_id FROM appointment WHERE type IS NULL OR type = ''");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$types = ['online', 'onsite'];
$updated = 0;

foreach ($appointments as $index => $appt) {
    $type = $types[$index % 2];
    $updateStmt = $connect->prepare("UPDATE appointment SET type = ? WHERE appointment_id = ?");
    $updateStmt->execute([$type, $appt['appointment_id']]);
    $updated++;
}

echo "Updated $updated appointments to have a valid type.";

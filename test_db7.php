<?php
require 'connection.php';
$stmt = $connect->prepare("SELECT specialist_id, clinic_id FROM specialist LIMIT 10");
$stmt->execute();
$specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $connect->prepare("SELECT clinic_id, clinic_name FROM clinic LIMIT 10");
$stmt2->execute();
$clinics = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['specialists' => $specs, 'clinics' => $clinics]);
?>

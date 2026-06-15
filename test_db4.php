<?php
require 'connection.php';
$stmt = $connect->prepare("SELECT appointment_id, parent_id, specialist_id, status, scheduled_at FROM appointment ORDER BY appointment_id DESC LIMIT 10");
$stmt->execute();
$appts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($appts);
?>

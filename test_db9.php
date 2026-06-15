<?php
require 'connection.php';
$stmt = $connect->prepare("SELECT specialist_id, clinic_id FROM specialist WHERE specialist_id = 71");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

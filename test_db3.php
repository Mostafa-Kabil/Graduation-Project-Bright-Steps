<?php
require 'connection.php';
$stmt = $connect->prepare("SELECT * FROM clinic");
$stmt->execute();
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($clinics);
?>

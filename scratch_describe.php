<?php
include 'connection.php';
$stmt = $connect->query("DESCRIBE activity_log");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

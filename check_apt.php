<?php
include 'connection.php';
$stmt = $connect->query("DESCRIBE appointment");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

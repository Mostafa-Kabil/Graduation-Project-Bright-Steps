<?php
include 'connection.php';
$stmt = $connect->query("SELECT * FROM users WHERE user_id = 40");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $connect->query("SELECT * FROM specialist");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

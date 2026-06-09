<?php
require 'connection.php';
$stmt = $connect->query("SELECT user_id, first_name, last_name, email, role, status FROM users WHERE user_id IN (4, 5100, 5106) OR role = 'doctor' LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

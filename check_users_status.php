<?php
include 'connection.php';
$stmt = $connect->query("SELECT user_id, email, status FROM users LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

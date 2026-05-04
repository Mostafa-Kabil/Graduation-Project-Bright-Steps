<?php
include 'connection.php';
$stmt = $connect->query("SELECT user_id, email, role FROM users WHERE email = 's.jenkins@brightsteps.com'");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

$stmt2 = $connect->query("SELECT * FROM notifications WHERE user_id = 40");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>

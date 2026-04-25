<?php
include 'connection.php';
$stmt = $connect->query("DESCRIBE specialist");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $connect->query("DESCRIBE users");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>

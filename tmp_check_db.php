<?php
include 'connection.php';
$stmt = $connect->query("DESCRIBE clinic");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

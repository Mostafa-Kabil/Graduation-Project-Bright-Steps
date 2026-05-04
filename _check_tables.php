<?php
include 'connection.php';
$stmt = $connect->query("SHOW TABLES LIKE '%milestone%'");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
?>

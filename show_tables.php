<?php
require 'connection.php';
$stmt = $connect->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>

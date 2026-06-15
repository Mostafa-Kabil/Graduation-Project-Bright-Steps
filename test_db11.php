<?php
require 'connection.php';
$stmt = $connect->query("SHOW TABLES");
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
?>

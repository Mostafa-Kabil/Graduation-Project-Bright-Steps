<?php
require 'connection.php';
$stmt = $connect->query("SHOW TABLES LIKE '%clinic%'");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

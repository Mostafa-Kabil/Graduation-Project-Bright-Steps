<?php
require 'connection.php';
$stmt = $connect->query("DESCRIBE message");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

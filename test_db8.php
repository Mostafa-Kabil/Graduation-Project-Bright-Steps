<?php
require 'connection.php';
$stmt = $connect->prepare("DESCRIBE clinic");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

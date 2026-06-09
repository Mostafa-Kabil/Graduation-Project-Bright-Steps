<?php
require 'connection.php';
$stmt = $connect->query("SELECT * FROM appointment");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

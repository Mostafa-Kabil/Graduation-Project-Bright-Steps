<?php
require 'connection.php';
$stmt = $connect->query("DESCRIBE specialist");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

<?php
require __DIR__ . '/../connection.php';

$stmt = $connect->query("DESCRIBE payment");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
?>

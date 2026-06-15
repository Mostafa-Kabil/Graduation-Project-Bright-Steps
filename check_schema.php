<?php
include 'connection.php';
$cols = $connect->query("DESCRIBE payment")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
$cols = $connect->query("DESCRIBE appointment")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

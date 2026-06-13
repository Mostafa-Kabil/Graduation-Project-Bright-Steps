<?php
require 'connection.php';
$stmt1 = $connect->query("DESCRIBE parent");
$stmt2 = $connect->query("DESCRIBE users");
$stmt3 = $connect->query("DESCRIBE appointment");
echo "PARENT:\n";
print_r($stmt1->fetchAll(PDO::FETCH_ASSOC));
echo "\nUSERS:\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
echo "\nAPPT:\n";
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

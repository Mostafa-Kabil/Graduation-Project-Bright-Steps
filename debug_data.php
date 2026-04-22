<?php
require 'connection.php';
echo "Clinics:\n";
$clinics = $connect->query("SELECT * FROM clinic")->fetchAll(PDO::FETCH_ASSOC);
print_r($clinics);

echo "\nSpecialists:\n";
$specs = $connect->query("SELECT * FROM specialist")->fetchAll(PDO::FETCH_ASSOC);
print_r($specs);

echo "\nChildren:\n";
$children = $connect->query("SELECT * FROM child LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($children);
?>

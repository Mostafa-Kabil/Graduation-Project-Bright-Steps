<?php
require 'connection.php';
$clinic = $connect->query("DESCRIBE clinic")->fetchAll(PDO::FETCH_ASSOC);
$specialist = $connect->query("DESCRIBE specialist")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['clinic' => $clinic, 'specialist' => $specialist], JSON_PRETTY_PRINT);

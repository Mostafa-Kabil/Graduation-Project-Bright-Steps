<?php
require 'connection.php';
$stmt = $connect->query("SELECT DISTINCT indicator FROM behavior");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

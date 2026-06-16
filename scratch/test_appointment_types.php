<?php
require 'connection.php';
$stmt = $connect->query('SELECT type, COUNT(*) FROM appointment GROUP BY type');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

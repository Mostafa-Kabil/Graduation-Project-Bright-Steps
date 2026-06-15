<?php
require 'connection.php';
$stmt = $connect->query('DESCRIBE message');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

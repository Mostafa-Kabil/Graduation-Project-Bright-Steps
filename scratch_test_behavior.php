<?php
require 'connection.php';
$stmt = $connect->query('SELECT behavior_id, behavior_details, indicator FROM behavior LIMIT 10');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

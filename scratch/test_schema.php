<?php
require 'connection.php';
$stmt = $connect->query('DESCRIBE motor_milestones');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

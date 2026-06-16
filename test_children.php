<?php
require 'connection.php';
$stmt = $connect->query('SELECT child_id FROM child');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

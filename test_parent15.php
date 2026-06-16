<?php
require 'connection.php';
$stmt = $connect->query('SELECT child_id FROM child WHERE parent_id = 15');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

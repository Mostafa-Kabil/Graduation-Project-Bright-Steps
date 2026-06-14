<?php
require 'connection.php';
$stmt = $connect->query('SELECT child_id, parent_id FROM child LIMIT 1');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

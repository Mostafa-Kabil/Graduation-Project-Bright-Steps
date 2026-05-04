<?php
require 'connection.php';
$stmt = $connect->query("EXPLAIN specialist");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$stmt = $connect->query("EXPLAIN users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$stmt = $connect->query("SELECT specialist_id, COUNT(*) FROM appointment GROUP BY specialist_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

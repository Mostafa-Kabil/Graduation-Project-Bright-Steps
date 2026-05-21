<?php
require_once "connection.php";
$query = $connect->query("SHOW COLUMNS FROM appointment LIKE 'status'");
$result = $query->fetch(PDO::FETCH_ASSOC);
print_r($result);
?>
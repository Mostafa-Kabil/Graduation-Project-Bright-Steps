<?php
require_once "connection.php";
$result = $connect->query("SELECT DISTINCT status FROM appointment")->fetchAll(PDO::FETCH_COLUMN);
print_r($result);
?>
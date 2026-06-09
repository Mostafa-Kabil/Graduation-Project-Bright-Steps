<?php
include "connection.php";
$r = $connect->query("SHOW COLUMNS FROM payment")->fetchAll(PDO::FETCH_ASSOC);
print_r($r);
?>

<?php
require 'connection.php';
$stmt = $connect->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

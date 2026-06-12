<?php
require 'connection.php';
$stmt = $connect->query("SHOW COLUMNS FROM reward_offers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

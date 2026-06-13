<?php
require 'connection.php';
$stmt = $connect->query("SELECT user_id, first_name, last_name FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$stmt = $connect->query("SELECT s.specialist_id, s.first_name, s.last_name FROM specialist s");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

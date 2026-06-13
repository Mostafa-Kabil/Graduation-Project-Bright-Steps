<?php
require 'connection.php';
$stmt = $connect->query("SELECT appointment_id, parent_id, specialist_id, status FROM appointment");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

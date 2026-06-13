<?php
require 'connection.php';
$stmt=$connect->query("SELECT * FROM clinic WHERE clinic_id = 0");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

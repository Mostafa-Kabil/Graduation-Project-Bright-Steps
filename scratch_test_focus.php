<?php
require 'connection.php';
$stmt = $connect->query("SELECT bc.category_id, bc.category_name, bc.category_type, b.behavior_id, b.behavior_details, b.indicator FROM behavior b JOIN behavior_category bc ON b.category_id = bc.category_id WHERE b.behavior_details LIKE '%focus%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

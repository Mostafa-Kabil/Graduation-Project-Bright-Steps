<?php
require 'connection.php';
$stmt = $connect->query("SELECT bc.category_id, bc.category_name, bc.category_type, b.behavior_id, b.behavior_details FROM behavior b JOIN behavior_category bc ON b.category_id = bc.category_id WHERE LOWER(bc.category_name) LIKE '%motor%' OR b.behavior_details LIKE '%focus%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

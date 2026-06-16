<?php
require 'connection.php';
$stmt = $connect->query('DESCRIBE child_exhibited_behavior');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $connect->query('SELECT child_id, behavior_id, COUNT(*) FROM child_exhibited_behavior GROUP BY child_id, behavior_id HAVING COUNT(*) > 1');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

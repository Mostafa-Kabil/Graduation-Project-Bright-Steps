<?php
require 'connection.php';
$stmt = $connect->query('SELECT child_id, milestone_name, category, COUNT(*) FROM motor_milestones GROUP BY child_id, milestone_name, category HAVING COUNT(*) > 1');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require_once 'connection.php';
$stmt = $connect->query("SELECT activity_id, child_id, title, category, is_completed, completed_at FROM child_activities ORDER BY activity_id DESC LIMIT 10");
echo "Latest activities:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$sql = "SELECT b.name, b.icon, b.description FROM child_badge cb JOIN badge b ON cb.badge_id = b.badge_id WHERE cb.child_id = 1";
$stmt = $connect->prepare($sql);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($badges);

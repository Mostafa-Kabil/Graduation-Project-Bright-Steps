<?php
require 'connection.php';
$tables = ['parent_points_wallet', 'parent_points_tracking', 'points_earning_rules'];
foreach ($tables as $t) {
    $stmt = $connect->query("SHOW TABLES LIKE '$t'");
    echo "$t exists: " . ($stmt->fetch() ? "YES" : "NO") . "\n";
}
?>

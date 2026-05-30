<?php
require_once 'connection.php';
echo "PHP local time: " . date('Y-m-d H:i:s') . "\n";
try {
    $q = $connect->query("SELECT NOW() as now, UTC_TIMESTAMP() as utc");
    $row = $q->fetch(PDO::FETCH_ASSOC);
    echo "MySQL NOW(): " . $row['now'] . "\n";
    echo "MySQL UTC:   " . $row['utc'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require 'connection.php';
$enumQuery = $connect->query("SHOW COLUMNS FROM appointment LIKE 'status'");
$row = $enumQuery->fetch(PDO::FETCH_ASSOC);
var_dump($row);
$type = $row['Type'];
echo "Original type: $type\n";
$newValues = ['Pending Reschedule','Cancelled','Refunded'];
foreach ($newValues as $val) {
    if (strpos($type, "'$val'") === false) {
        $type = rtrim($type, ')') . ",'$val')";
    }
}
echo "New type: $type\n";
?>

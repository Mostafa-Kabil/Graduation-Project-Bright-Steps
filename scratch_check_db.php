<?php
require 'connection.php';
$stmt = $connect->query("SELECT DISTINCT indicator FROM behavior ORDER BY indicator");
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "All indicators:\n";
foreach ($rows as $r) echo "  '$r'\n";
echo "\nTotal behaviors: ";
$stmt2 = $connect->query("SELECT COUNT(*) FROM behavior");
echo $stmt2->fetchColumn() . "\n";
echo "\nBehavior categories:\n";
$stmt3 = $connect->query("SELECT category_id, category_name, category_type FROM behavior_category ORDER BY category_id");
foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $cat) {
    echo "  {$cat['category_id']}: {$cat['category_name']} ({$cat['category_type']})\n";
}

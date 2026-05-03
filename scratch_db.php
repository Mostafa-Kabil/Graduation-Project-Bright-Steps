<?php
include 'connection.php';
$tables = $connect->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES:\n";
foreach($tables as $t) echo "- $t\n";
echo "\n";
foreach(['gift','reward','redeem','prize','shop','store','catalog'] as $kw) {
    foreach($tables as $t) {
        if(stripos($t, $kw) !== false) {
            echo "FOUND: $t\n";
            $cols = $connect->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
            foreach($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";
        }
    }
}
// Also check points-related tables
foreach(['point','wallet','engage'] as $kw) {
    foreach($tables as $t) {
        if(stripos($t, $kw) !== false) {
            echo "\nRELATED: $t\n";
            $cols = $connect->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
            foreach($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";
        }
    }
}
?>

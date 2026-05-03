<?php
include 'connection.php';
$res = $connect->query("SHOW TABLES");
$tables = $res->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $cols = $connect->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . $c['Field'] . "\n";
    }
}

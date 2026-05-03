<?php
include 'connection.php';

echo "=== child_milestones ===\n";
$r = $connect->query("DESCRIBE child_milestones");
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $c) echo "  " . $c['Field'] . "\n";

echo "\n=== milestones table? ===\n";
try {
    $r2 = $connect->query("DESCRIBE milestones");
    foreach ($r2->fetchAll(PDO::FETCH_ASSOC) as $c) echo "  " . $c['Field'] . "\n";
} catch (Exception $e) {
    echo "  TABLE DOES NOT EXIST\n";
}

echo "\n=== doctor_report ===\n";
$r3 = $connect->query("DESCRIBE doctor_report");
foreach ($r3->fetchAll(PDO::FETCH_ASSOC) as $c) echo "  " . $c['Field'] . "\n";

echo "\n=== notifications table? ===\n";
try {
    $r4 = $connect->query("DESCRIBE notifications");
    foreach ($r4->fetchAll(PDO::FETCH_ASSOC) as $c) echo "  " . $c['Field'] . "\n";
} catch (Exception $e) {
    echo "  TABLE DOES NOT EXIST\n";
}

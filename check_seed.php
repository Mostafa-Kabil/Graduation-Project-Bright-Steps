<?php
session_start();
require 'connection.php';
header('Content-Type: text/plain; charset=utf-8');

$SID = intval($_SESSION['specialist_id'] ?? $_SESSION['id'] ?? 25);
echo "═══ RAW DATA CHECK ═══\n";
echo "Doctor ID: $SID\n\n";

$checks = [
    ["Users (seed parents)", "SELECT COUNT(*) AS n FROM users WHERE user_id BETWEEN 5100 AND 5106"],
    ["Parent table (seed)", "SELECT COUNT(*) AS n FROM parent WHERE parent_id BETWEEN 5100 AND 5106"],
    ["Children (seed)", "SELECT COUNT(*) AS n FROM child WHERE child_id BETWEEN 5200 AND 5210"],
    ["Payments (seed)", "SELECT COUNT(*) AS n FROM payment WHERE payment_id BETWEEN 5000 AND 5029"],
    ["Appointments (doctor $SID)", "SELECT COUNT(*) AS n FROM appointment WHERE specialist_id = $SID"],
    ["Appointments (seed IDs)", "SELECT COUNT(*) AS n FROM appointment WHERE appointment_id BETWEEN 5000 AND 5029"],
    ["Doctor reports (seed)", "SELECT COUNT(*) AS n FROM doctor_report WHERE specialist_id = $SID"],
    ["Messages (seed)", "SELECT COUNT(*) AS n FROM message WHERE message_id BETWEEN 5000 AND 5020"],
    ["Feedback (seed)", "SELECT COUNT(*) AS n FROM feedback WHERE specialist_id = $SID"],
    ["Growth records (seed)", "SELECT COUNT(*) AS n FROM growth_record WHERE record_id BETWEEN 5000 AND 5010"],
    ["System reports (seed)", "SELECT COUNT(*) AS n FROM child_generated_system_report WHERE child_id BETWEEN 5200 AND 5210"],
];

foreach ($checks as $c) {
    try {
        $r = $connect->query($c[1])->fetch(PDO::FETCH_ASSOC);
        $status = $r['n'] > 0 ? "✓ {$r['n']}" : "❌ 0";
        echo "  $status — {$c[0]}\n";
    } catch (Exception $e) {
        echo "  ⚠ {$c[0]}: " . $e->getMessage() . "\n";
    }
}

// Check payment table columns
echo "\n── PAYMENT TABLE COLUMNS ──\n";
try {
    $cols = $connect->query("SHOW COLUMNS FROM payment")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) echo "  {$col['Field']} ({$col['Type']}) " . ($col['Null']==='NO'?'NOT NULL':'NULL') . " " . ($col['Key']??"") . "\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }

// Check child table columns  
echo "\n── CHILD TABLE COLUMNS ──\n";
try {
    $cols = $connect->query("SHOW COLUMNS FROM child")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) echo "  {$col['Field']} ({$col['Type']}) " . ($col['Null']==='NO'?'NOT NULL':'NULL') . " " . ($col['Key']??"") . "\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }

// Check appointment table FKs
echo "\n── APPOINTMENT FOREIGN KEYS ──\n";
try {
    $fks = $connect->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointment' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fks as $fk) echo "  {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }

// Try a test appointment insert to see the real error
echo "\n── TEST INSERT (appointment) ──\n";
try {
    $connect->beginTransaction();
    $connect->exec("INSERT INTO appointment (appointment_id, parent_id, payment_id, specialist_id, status, type, scheduled_at) VALUES (9999, 5100, 5000, $SID, 'scheduled', 'online', NOW())");
    echo "  ✓ Insert works\n";
    $connect->rollBack();
} catch (Exception $e) {
    echo "  ❌ " . $e->getMessage() . "\n";
    try { $connect->rollBack(); } catch(Exception $ex) {}
}

echo "\n═══ DONE ═══\n";

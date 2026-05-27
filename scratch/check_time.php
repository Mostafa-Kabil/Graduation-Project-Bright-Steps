<?php
require_once __DIR__ . '/../connection.php';
echo "PHP local time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
$stmt = $connect->query("SELECT NOW() as mysql_now, UTC_TIMESTAMP() as mysql_utc");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "MySQL NOW(): " . $res['mysql_now'] . "\n";
echo "MySQL UTC_TIMESTAMP(): " . $res['mysql_utc'] . "\n\n";

$stmt2 = $connect->query("SELECT appointment_id, specialist_id, scheduled_at, status FROM appointment ORDER BY scheduled_at DESC LIMIT 10");
$appointments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "RECENT APPOINTMENTS:\n" . json_encode($appointments, JSON_PRETTY_PRINT) . "\n";


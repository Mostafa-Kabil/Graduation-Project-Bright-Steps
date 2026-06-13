<?php
require 'connection.php';
$stmtAvail = $connect->prepare("
    SELECT day_of_week, start_time, end_time, slot_duration
    FROM appointment_slots
    WHERE is_active = 1
");
$stmtAvail->execute();
print_r($stmtAvail->fetchAll(PDO::FETCH_ASSOC));

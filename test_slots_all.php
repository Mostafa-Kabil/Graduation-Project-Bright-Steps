<?php
require 'connection.php';
$stmtAvail = $connect->query("
    SELECT *
    FROM appointment_slots
");
print_r($stmtAvail->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$s = $connect->prepare("SELECT * FROM appointment_slots WHERE doctor_id IN (59, 60, 61, 62, 63)");
$s->execute();
print_r($s->fetchAll(PDO::FETCH_ASSOC));

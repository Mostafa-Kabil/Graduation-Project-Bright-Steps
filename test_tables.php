<?php
require 'connection.php';
$id = 64;
echo "specialist_availability:\n";
print_r($connect->query("SELECT * FROM specialist_availability WHERE specialist_id = $id")->fetchAll(PDO::FETCH_ASSOC));

echo "\nappointment_slots:\n";
print_r($connect->query("SELECT * FROM appointment_slots WHERE doctor_id = $id")->fetchAll(PDO::FETCH_ASSOC));

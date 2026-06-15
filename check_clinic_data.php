<?php
include 'connection.php';
print_r($connect->query("SELECT specialist_id, first_name, last_name, clinic_id FROM specialist WHERE clinic_id=2")->fetchAll(PDO::FETCH_ASSOC));
print_r($connect->query("SELECT a.scheduled_at, s.first_name, s.last_name FROM appointment a JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id=2 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));

<?php
require 'connection.php';
$s = $connect->prepare("SELECT a.appointment_id, a.scheduled_at, s.first_name as spec_name 
                        FROM appointment a 
                        JOIN specialist s ON a.specialist_id = s.specialist_id 
                        WHERE s.clinic_id = 149");
$s->execute();
print_r($s->fetchAll(PDO::FETCH_ASSOC));

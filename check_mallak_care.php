<?php
include 'connection.php';
$clinic_id = 16;
$connect->query("UPDATE clinic SET is_first_login = 0 WHERE clinic_id = 16");

$specs = $connect->query("SELECT specialist_id, first_name, last_name FROM specialist WHERE clinic_id = 16")->fetchAll(PDO::FETCH_ASSOC);
print_r($specs);

$patients = $connect->query("SELECT c.child_id, c.first_name, c.last_name, s.first_name as spec_fname, s.last_name as spec_lname FROM appointment a JOIN child c ON a.child_id = c.child_id JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = 16")->fetchAll(PDO::FETCH_ASSOC);
print_r($patients);

$rev = $connect->query("SELECT p.amount_post_discount, a.status FROM appointment a JOIN payment p ON a.payment_id = p.payment_id JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = 16")->fetchAll(PDO::FETCH_ASSOC);
print_r($rev);

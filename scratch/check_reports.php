<?php
require 'connection.php';
$stmt = $connect->query('SELECT report_id, doctor_reply, is_shared FROM shared_reports');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

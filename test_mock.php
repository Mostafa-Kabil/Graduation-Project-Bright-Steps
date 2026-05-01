<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['ajax'] = '1';
$_GET['section'] = 'reports';
$_SESSION = ['id' => 5100, 'role' => 'specialist', 'fname' => 'John', 'lname' => 'Doe'];

$json = json_encode([
    'action' => 'submit_report',
    'specialist_id' => 5100,
    'doctor_report_id' => 0,
    'child_id' => 5100,
    'child_report' => '',
    'doctor_notes' => 'Test Notes',
    'recommendations' => 'Test Recs',
    'report_date' => '2026-05-01',
    'shared_report_id' => 0
]);

// Mock php://input
// wait, we can just redefine $input locally if we include it? No, doctor-dashboard.php reads php://input.
// We can't mock php://input easily without stream wrapper. Let's just create a test script that includes connection.php and runs the submit_report block.

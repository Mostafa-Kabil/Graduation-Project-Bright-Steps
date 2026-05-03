<?php
$data = array(
    'action' => 'submit_report',
    'specialist_id' => 5100,
    'doctor_report_id' => 0,
    'child_id' => 5100,
    'child_report' => '',
    'doctor_notes' => 'Test Notes',
    'recommendations' => 'Test Recs',
    'report_date' => '2026-05-01',
    'shared_report_id' => 0
);
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/Graduation-Project-Bright-Steps/doctor-dashboard.php?ajax=1&section=reports', false, $context);
echo "Result:\n" . $result . "\n";

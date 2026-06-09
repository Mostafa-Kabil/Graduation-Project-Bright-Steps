<?php
session_start();
$_SESSION['id'] = 4;
$_SESSION['role'] = 'doctor';
$_SESSION['specialist_id'] = 4;
$_SESSION['fname'] = 'mariam';
$_SESSION['lname'] = 'ghareb';
$_SESSION['email'] = 'mariam@gmail.com';

$_GET['type'] = 'growth-report';
$_GET['child_id'] = '5201';
$_GET['download'] = '0';

ob_start();
include 'api_export_pdf.php';
$output = ob_get_clean();

echo "HTTP STATUS: " . http_response_code() . "\n";
echo "OUTPUT LENGTH: " . strlen($output) . "\n";
if (strlen($output) < 2000) {
    echo "OUTPUT:\n" . $output . "\n";
} else {
    echo "OUTPUT (TRUNCATED):\n" . substr($output, 0, 1000) . "\n...\n" . substr($output, -1000) . "\n";
}

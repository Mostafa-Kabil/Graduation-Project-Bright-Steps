<?php
session_start();
$_SESSION['id'] = 4; // Assuming a clinic ID that exists
$_SESSION['role'] = 'clinic';

// Include the connection
require_once 'connection.php';

// Capture the output of api_get_clinic_data.php
ob_start();
include 'api_get_clinic_data.php';
$output = ob_get_clean();

$data = json_decode($output, true);

echo "--- Dashboard Data Test ---\n";
if (isset($data['success']) && $data['success']) {
    echo "Clinic: " . $data['clinic']['clinic_name'] . "\n";
    echo "Specialist Avg Rating: " . $data['stats']['avg_rating'] . "\n";
    echo "Total Appointments: " . $data['stats']['total_appointments'] . "\n";
    echo "Revenue: " . $data['stats']['revenue'] . "\n";
    
    echo "\nSpecialists:\n";
    foreach ($data['specialists'] as $s) {
        echo "- Dr. " . $s['first_name'] . " " . $s['last_name'] . " | Patients: " . $s['patients_count'] . " | Rating: " . $s['rating'] . "\n";
    }
} else {
    echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    echo "Raw output: " . $output . "\n";
}
?>

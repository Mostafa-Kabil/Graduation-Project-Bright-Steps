<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['parent', 'admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Add columns to avoid errors if they don't exist yet
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS bio TEXT");
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS medical_specialties TEXT");

    $stmt = $connect->query("SELECT clinic_id, clinic_name, location, rating, logo, bio, medical_specialties FROM clinic WHERE status = 'verified'");
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get specialist count per clinic
    $countStmt = $connect->query("SELECT clinic_id, COUNT(*) as specialist_count FROM specialist GROUP BY clinic_id");
    $counts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($clinics as &$c) {
        $c['specialist_count'] = isset($counts[$c['clinic_id']]) ? (int)$counts[$c['clinic_id']] : 0;
    }

    echo json_encode(['success' => true, 'clinics' => array_values($clinics)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

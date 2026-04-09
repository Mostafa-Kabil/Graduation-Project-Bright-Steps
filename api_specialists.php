<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $connect->prepare("
        SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, s.experience_years, 
               c.clinic_name, c.location, c.rating 
        FROM specialist s
        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
    ");
    $stmt->execute();
    $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'specialists' => $specialists]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>

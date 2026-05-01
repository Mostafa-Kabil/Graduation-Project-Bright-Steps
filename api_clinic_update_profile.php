<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $connect->prepare("
        UPDATE clinic 
        SET clinic_name = ?, 
            email = ?, 
            location = ?, 
            website = ?, 
            bio = ?, 
            opening_hours = ? 
        WHERE admin_id = ?
    ");
    
    $stmt->execute([
        $input['clinic_name'] ?? '',
        $input['email'] ?? '',
        $input['location'] ?? '',
        $input['website'] ?? '',
        $input['bio'] ?? '',
        $input['opening_hours'] ?? '',
        $_SESSION['id']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

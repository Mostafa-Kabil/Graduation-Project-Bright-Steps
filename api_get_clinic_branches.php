<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$clinicId = intval($_SESSION['id']);

try {
    $stmt = $connect->prepare("SELECT id, branch_name, city, area FROM clinic_branches WHERE clinic_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$clinicId]);
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $branches]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

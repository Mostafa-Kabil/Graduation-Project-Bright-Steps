<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$clinic_id = $_SESSION['id'];

try {
    // We update the status to 'deactivated'
    $stmt = $connect->prepare("UPDATE clinic SET status = 'deactivated' WHERE admin_id = ? OR clinic_id = ?");
    $stmt->execute([$clinic_id, $clinic_id]);

    // Optional: Log them out immediately
    session_destroy();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

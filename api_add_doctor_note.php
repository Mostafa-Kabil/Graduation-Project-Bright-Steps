<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['specialist', 'doctor'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$doctorId = $_SESSION['id'];
$childId = $_POST['child_id'] ?? null;
$noteText = trim($_POST['doctor_notes'] ?? '');
$recommendations = trim($_POST['recommendations'] ?? '');
$visibility = $_POST['visibility'] ?? 'private';

if (!$childId || !$noteText) {
    http_response_code(400);
    echo json_encode(['error' => 'child_id and doctor_notes are required']);
    exit();
}

if (!in_array($visibility, ['private', 'shared'])) {
    $visibility = 'private';
}

try {
    $stmt = $connect->prepare("
        INSERT INTO doctor_report (specialist_id, child_id, doctor_notes, recommendations, visibility, report_date)
        VALUES (:doctor, :child, :notes, :recommendations, :visibility, CURDATE())
    ");
    
    $stmt->execute([
        ':doctor' => $doctorId,
        ':child' => $childId,
        ':notes' => $noteText,
        ':recommendations' => $recommendations,
        ':visibility' => $visibility
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'report_id' => $connect->lastInsertId()
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

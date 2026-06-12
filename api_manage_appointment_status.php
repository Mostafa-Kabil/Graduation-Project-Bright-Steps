<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

// Must be specialist
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'specialist' && $_SESSION['role'] !== 'doctor')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointmentId = $input['appointment_id'] ?? null;
$action = $input['action'] ?? ''; // 'approve' or 'reject'
$specialistId = $_SESSION['id'];

if (!$appointmentId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Verify ownership
$stmt = $connect->prepare("SELECT parent_id FROM appointment WHERE appointment_id = ? AND specialist_id = ?");
$stmt->execute([$appointmentId, $specialistId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'Appointment not found']);
    exit();
}

$newStatus = $action === 'approve' ? 'confirmed' : 'Cancelled';

$connect->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?")->execute([$newStatus, $appointmentId]);

// Notify parent
$title = $action === 'approve' ? 'Appointment Confirmed!' : 'Appointment Rejected';
$msg   = $action === 'approve'
    ? 'Your appointment has been approved by the specialist. You can now message them.'
    : 'Your appointment request was not approved. Please book another time.';

$connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'appointment', ?, ?)")
         ->execute([$row['parent_id'], $title, $msg]);

echo json_encode(['success' => true, 'new_status' => $newStatus]);
?>

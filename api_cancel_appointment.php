<?php
/**
 * api_cancel_appointment.php
 * Cancel an existing appointment.
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$parentId = $_SESSION['id'];
$appointmentId = $_POST['appointment_id'] ?? null;
if (!$appointmentId) {
    echo json_encode(['error' => 'Missing appointment ID']);
    exit();
}

try {
    // Verify ownership and status
    $stmt = $connect->prepare("SELECT status FROM appointment WHERE appointment_id = ? AND parent_id = ?");
    $stmt->execute([$appointmentId, $parentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['error' => 'Appointment not found']);
        exit();
    }
    $currentStatus = $row['status'];
    if (in_array($currentStatus, ['Cancelled', 'Refunded'])) {
        echo json_encode(['error' => 'Appointment already cancelled']);
        exit();
    }

    $connect->beginTransaction();
    $update = $connect->prepare("UPDATE appointment SET status = 'Cancelled', cancelled_by = 'patient' WHERE appointment_id = ?");
    $update->execute([$appointmentId]);

    // Notification to parent
    $title = 'Appointment Cancelled';
    $message = "Your appointment (ID: $appointmentId) has been cancelled.
        Please contact the clinic if you have questions.";
    $notif = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $notif->execute([$parentId, $title, $message]);

    $connect->commit();
    echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

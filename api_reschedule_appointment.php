<?php
/**
 * api_reschedule_appointment.php
 * Allows a parent to request rescheduling of an existing appointment.
 * The request creates a pending state awaiting doctor approval.
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
$appointmentId = $_POST['appointment_id'] ?? '';
$newDate = $_POST['new_date'] ?? '';

if (!$appointmentId || !$newDate) {
    echo json_encode(['error' => 'Appointment ID and new date are required']);
    exit();
}

try {
    // Verify ownership and current status
    $stmt = $connect->prepare("SELECT status FROM appointment WHERE appointment_id = ? AND parent_id = ?");
    $stmt->execute([$appointmentId, $parentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['error' => 'Appointment not found or not owned by you']);
        exit();
    }
    $currentStatus = $row['status'];
    if (in_array($currentStatus, ['Cancelled', 'Refunded'])) {
        echo json_encode(['error' => 'Cannot reschedule a cancelled appointment']);
        exit();
    }

    $connect->beginTransaction();
    // Update status to Pending Reschedule and store the requested new datetime
    $newDateTime = date('Y-m-d H:i:s', strtotime($newDate));
    $update = $connect->prepare("UPDATE appointment SET status = 'Pending Reschedule', scheduled_at = ?, comment = CONCAT(IFNULL(comment, ''), '\nReschedule requested to: $newDateTime') WHERE appointment_id = ?");
    $update->execute([$newDateTime, $appointmentId]);

    // Notification to parent
    $notifParent = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $notifParent->execute([
        $parentId,
        'Reschedule Requested',
        "Your reschedule request for appointment #$appointmentId is pending doctor approval."
    ]);

    // Notification to doctor (assuming we have a doctor user_id mapping via specialist)
    $docStmt = $connect->prepare("SELECT specialist_id FROM appointment WHERE appointment_id = ?");
    $docStmt->execute([$appointmentId]);
    $specId = $docStmt->fetchColumn();
    if ($specId) {
        require_once __DIR__ . '/includes/doctor_notifications.php';
        doctor_notify(
            $connect,
            (int) $specId,
            'new_appointment',
            'Reschedule Request',
            "A parent requested to reschedule appointment #{$appointmentId} to {$newDateTime}."
        );
    }

    $connect->commit();
    echo json_encode(['success' => true, 'appointment_id' => $appointmentId, 'new_date' => $newDateTime]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

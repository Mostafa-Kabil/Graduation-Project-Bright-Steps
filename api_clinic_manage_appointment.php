<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$appointmentId = $input['appointment_id'] ?? '';

if (!$action || !$appointmentId) {
    echo json_encode(['success' => false, 'error' => 'Action and Appointment ID are required.']);
    exit();
}

try {
    // Check if appointment exists and belongs to this clinic
    // Clinic is linked to specialists, specialists have clinic_id
    $stmt = $connect->prepare("
        SELECT a.*, s.clinic_id, p.parent_id, u.first_name as parent_fname, u.email as parent_email 
        FROM appointment a
        JOIN specialist s ON a.specialist_id = s.specialist_id
        JOIN parent p ON a.parent_id = p.parent_id
        JOIN users u ON p.parent_id = u.user_id
        WHERE a.appointment_id = ?
    ");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        throw new Exception("Appointment not found.");
    }

    // Resolve clinic_id for current user (robust lookup)
    $clinic_id = null;
    $cIdStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_id = ? OR admin_id = ? LIMIT 1");
    $cIdStmt->execute([$_SESSION['id'], $_SESSION['id']]);
    $currentClinicId = $cIdStmt->fetchColumn();

    if (!$currentClinicId || $appointment['clinic_id'] != $currentClinicId) {
        throw new Exception("Access denied for this appointment.");
    }

    $connect->beginTransaction();
    $notificationTitle = "";
    $notificationMessage = "";

    switch ($action) {
        case 'approve':
            $stmt = $connect->prepare("UPDATE appointment SET status = 'Scheduled' WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);
            $notificationTitle = "Appointment Confirmed";
            $notificationMessage = "Your appointment request for " . date('M j, Y g:i A', strtotime($appointment['scheduled_at'])) . " has been confirmed by the clinic.";
            break;

        case 'accepted':
            $stmt = $connect->prepare("UPDATE appointment SET status = 'Accepted' WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);
            $notificationTitle = "Appointment Accepted";
            $notificationMessage = "Your appointment request for " . date('M j, Y g:i A', strtotime($appointment['scheduled_at'])) . " has been accepted by the clinic.";
            break;

        case 'cancel':
            $stmt = $connect->prepare("UPDATE appointment SET status = 'Cancelled', cancelled_by = 'clinic' WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);
            $notificationTitle = "Appointment Cancelled";
            $notificationMessage = "Your appointment on " . date('M j, Y g:i A', strtotime($appointment['scheduled_at'])) . " has been cancelled by the clinic.";
            break;

        case 'reschedule':
            $newDate = $input['new_date'] ?? '';
            if (!$newDate) throw new Exception("New date is required for rescheduling.");

            if (strtotime($newDate) < time()) {
                throw new Exception("Rescheduled date and time cannot be in the past.");
            }

            $stmt = $connect->prepare("UPDATE appointment SET scheduled_at = ?, status = 'Scheduled' WHERE appointment_id = ?");
            $stmt->execute([$newDate, $appointmentId]);

            $notificationTitle = "Appointment Rescheduled";
            $notificationMessage = "Your appointment has been rescheduled to " . date('M j, Y g:i A', strtotime($newDate)) . ".";
            break;

        case 'reject':
            $stmt = $connect->prepare("UPDATE appointment SET status = 'Refunded' WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);

            // Mark the corresponding payment as Refunded
            $stmtP = $connect->prepare("UPDATE payment SET status = 'refunded' WHERE payment_id = (SELECT payment_id FROM appointment WHERE appointment_id = ?)");
            $stmtP->execute([$appointmentId]);

            $notificationTitle = "Appointment Refunded";
            $notificationMessage = "The doctor was unable to reschedule your appointment. Your funds have been credited back/refunded.";
            break;

        default:
            throw new Exception("Invalid action.");
    }

    // Create notification for the parent
    if ($notificationTitle) {
        $stmtN = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'appointment_confirmed', ?, ?)");
        $stmtN->execute([$appointment['parent_id'], $notificationTitle, $notificationMessage]);
    }

    $connect->commit();
    echo json_encode(['success' => true, 'message' => "Appointment $action successfully."]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

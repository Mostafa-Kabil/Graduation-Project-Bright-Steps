<?php
/**
 * Creates appointment_reminder notifications for the logged-in doctor
 * when an appointment starts in ~30 minutes.
 */
session_start();
require_once 'connection.php';
require_once __DIR__ . '/includes/doctor_notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'] ?? '', ['doctor', 'specialist'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$doctorId = (int) ($_SESSION['specialist_id'] ?? $_SESSION['id'] ?? 0);
if ($doctorId <= 0) {
    echo json_encode(['success' => true, 'created' => 0]);
    exit;
}

$created = 0;

try {
    $stmt = $connect->prepare("
        SELECT a.appointment_id, a.scheduled_at, a.type,
               u.first_name, u.last_name
        FROM appointment a
        JOIN users u ON u.user_id = a.parent_id
        WHERE a.specialist_id = ?
          AND LOWER(a.status) IN ('scheduled', 'confirmed', 'approved', 'pending')
          AND a.scheduled_at > NOW()
          AND a.scheduled_at <= DATE_ADD(NOW(), INTERVAL 35 MINUTE)
          AND a.scheduled_at >= DATE_ADD(NOW(), INTERVAL 25 MINUTE)
    ");
    $stmt->execute([$doctorId]);
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($upcoming as $appt) {
        $apptId = (int) $appt['appointment_id'];
        $parentName = trim(($appt['first_name'] ?? '') . ' ' . ($appt['last_name'] ?? ''));
        $when = date('M j, Y g:i A', strtotime($appt['scheduled_at']));
        $aptType = $appt['type'] ?? 'appointment';
        $title = 'Appointment Reminder';
        $message = $parentName
            ? "Your {$aptType} appointment with {$parentName} starts in 30 minutes ({$when})."
            : "You have a {$aptType} appointment starting in 30 minutes ({$when}).";

        $dup = $connect->prepare("
            SELECT 1 FROM notifications
            WHERE user_id = ? AND type = 'appointment_reminder'
              AND title = ? AND message LIKE ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            LIMIT 1
        ");
        $dup->execute([$doctorId, $title, '%#' . $apptId . '%']);
        if ($dup->fetch()) {
            continue;
        }

        $message .= " [#{$apptId}]";
        if (doctor_notify($connect, $doctorId, 'appointment_reminder', $title, $message)) {
            $created++;
        }
    }

    echo json_encode(['success' => true, 'created' => $created]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to process reminders']);
}

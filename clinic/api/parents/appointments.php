<?php
/**
 * Bright Steps Clinic API — Appointment Booking (Parent)
 * 
 * Endpoints:
 *   POST ?action=book       — Book an appointment for a child
 *   GET  ?action=history    — View appointment history
 *   PUT  ?action=cancel     — Cancel an appointment
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Book Appointment ──────────────────────────────────
if ($action === 'book' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['child_id', 'specialist_id', 'scheduled_at', 'type']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    if (!in_array($input['type'], ['online', 'onsite'])) {
        json_error("Type must be 'online' or 'onsite'");
    }

    $db = get_db();

    // Verify child belongs to parent
    $stmt = $db->prepare("SELECT child_id FROM child WHERE child_id = :child_id AND parent_id = :parent_id");
    $stmt->execute([':child_id' => $input['child_id'], ':parent_id' => $authUser['user_id']]);
    if (!$stmt->fetch()) {
        json_error('Child not found or access denied', 404);
    }

    // Verify specialist exists
    $stmt = $db->prepare("SELECT specialist_id, clinic_id FROM specialist WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $input['specialist_id']]);
    $specialist = $stmt->fetch();
    if (!$specialist) {
        json_error('Specialist not found', 404);
    }

    // Check for scheduling conflicts
    $stmt = $db->prepare("
        SELECT appointment_id FROM appointment
        WHERE specialist_id = :sid AND scheduled_at = :scheduled_at AND status NOT IN ('cancelled')
    ");
    $stmt->execute([':sid' => $input['specialist_id'], ':scheduled_at' => $input['scheduled_at']]);
    if ($stmt->fetch()) {
        json_error('This time slot is already booked. Please choose another.', 409);
    }

    try {
        $db->beginTransaction();

        // Create appointment
        $stmt = $db->prepare("
            INSERT INTO appointment (parent_id, child_id, specialist_id, status, type, comment, scheduled_at, payment_id)
            VALUES (:parent_id, :child_id, :specialist_id, 'scheduled', :type, :comment, :scheduled_at, :payment_id)
        ");
        $stmt->execute([
            ':parent_id'     => $authUser['user_id'],
            ':child_id'      => intval($input['child_id']),
            ':specialist_id' => intval($input['specialist_id']),
            ':type'          => $input['type'],
            ':comment'       => $input['comment'] ?? null,
            ':scheduled_at'  => $input['scheduled_at'],
            ':payment_id'    => $input['payment_id'] ?? 0
        ]);
        $appointmentId = $db->lastInsertId();

        // Create notification for parent
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, reference_id)
            VALUES (:user_id, :title, :message, 'appointment_confirmed', :ref_id)
        ");
        $stmt->execute([
            ':user_id' => $authUser['user_id'],
            ':title'   => 'Appointment Booked',
            ':message' => 'Your appointment has been scheduled for ' . $input['scheduled_at'],
            ':ref_id'  => $appointmentId
        ]);

        // Create notification for doctor
        $stmt->execute([
            ':user_id' => intval($input['specialist_id']),
            ':title'   => 'New Appointment',
            ':message' => 'A new appointment has been booked for ' . $input['scheduled_at'],
            ':ref_id'  => $appointmentId
        ]);

        $db->commit();

        json_success([
            'appointment_id' => (int)$appointmentId,
            'status'         => 'scheduled'
        ], 'Appointment booked successfully', 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to book appointment. Please try again.', 500);
    }
}

// ── Appointment History ───────────────────────────────
elseif ($action === 'history' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $db = get_db();

    $status = get_string('status');
    $childId = get_int('child_id');

    $sql = "
        SELECT a.appointment_id, a.child_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization,
               c.clinic_name, c.location AS clinic_location,
               ch.first_name AS child_first_name, ch.last_name AS child_last_name
        FROM appointment a
        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        LEFT JOIN child ch ON a.child_id = ch.child_id
        WHERE a.parent_id = :parent_id
    ";
    $params = [':parent_id' => $authUser['user_id']];

    if ($status) {
        $sql .= " AND a.status = :status";
        $params[':status'] = $status;
    }
    if ($childId) {
        $sql .= " AND a.child_id = :child_id";
        $params[':child_id'] = $childId;
    }

    $sql .= " ORDER BY a.scheduled_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    json_success([
        'count'        => count($appointments),
        'appointments' => $appointments
    ]);
}

// ── Cancel Appointment ────────────────────────────────
elseif ($action === 'cancel' && $method === 'PUT') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $input = get_json_input();
    $appointmentId = intval($input['appointment_id'] ?? 0);

    if (!$appointmentId) {
        json_error('appointment_id is required');
    }

    $db = get_db();

    // Verify ownership and status
    $stmt = $db->prepare("
        SELECT appointment_id, status, specialist_id
        FROM appointment
        WHERE appointment_id = :aid AND parent_id = :parent_id
    ");
    $stmt->execute([':aid' => $appointmentId, ':parent_id' => $authUser['user_id']]);
    $appt = $stmt->fetch();

    if (!$appt) {
        json_error('Appointment not found or access denied', 404);
    }
    if (in_array($appt['status'], ['completed', 'cancelled'])) {
        json_error("Cannot cancel a {$appt['status']} appointment");
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE appointment SET status = 'cancelled' WHERE appointment_id = :aid");
        $stmt->execute([':aid' => $appointmentId]);

        // Notify doctor about cancellation
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, reference_id)
            VALUES (:user_id, 'Appointment Cancelled', 'An appointment has been cancelled by the parent.', 'appointment_cancelled', :ref_id)
        ");
        $stmt->execute([
            ':user_id' => $appt['specialist_id'],
            ':ref_id'  => $appointmentId
        ]);

        $db->commit();

        json_success([], 'Appointment cancelled successfully');

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to cancel appointment', 500);
    }
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST ?action=book'                    => 'Book an appointment (child_id, specialist_id, scheduled_at, type)',
            'GET  ?action=history'                 => 'View appointment history (?status=, ?child_id=)',
            'PUT  ?action=cancel'                  => 'Cancel an appointment (appointment_id)'
        ]
    ], 'Bright Steps Clinic — Appointments API');
}

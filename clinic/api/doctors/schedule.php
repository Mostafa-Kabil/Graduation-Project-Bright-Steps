<?php
/**
 * Bright Steps Clinic API — Doctor Schedule
 * 
 * Endpoints:
 *   GET  ?action=appointments      — View doctor's appointment schedule
 *   PUT  ?action=update_status     — Update appointment status (confirm/complete/cancel)
 *   GET  ?action=today             — Get today's appointments
 *   GET  ?action=stats             — Get appointment statistics
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── View Appointments ─────────────────────────────────
if ($action === 'appointments' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $db = get_db();
    $doctorId = $authUser['user_id'];

    $status = get_string('status');
    $date = get_string('date');
    $upcoming = get_string('upcoming') === '1';

    $sql = "
        SELECT a.appointment_id, a.child_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
               pu.first_name AS parent_first_name, pu.last_name AS parent_last_name,
               ch.first_name AS child_first_name, ch.last_name AS child_last_name,
               ch.gender AS child_gender, ch.birth_year AS child_birth_year
        FROM appointment a
        INNER JOIN parent p ON a.parent_id = p.parent_id
        INNER JOIN users pu ON p.parent_id = pu.user_id
        LEFT JOIN child ch ON a.child_id = ch.child_id
        WHERE a.specialist_id = :doctor_id
    ";
    $params = [':doctor_id' => $doctorId];

    if ($status) {
        $sql .= " AND a.status = :status";
        $params[':status'] = $status;
    }
    if ($date) {
        $sql .= " AND DATE(a.scheduled_at) = :date";
        $params[':date'] = $date;
    }
    if ($upcoming) {
        $sql .= " AND a.scheduled_at >= NOW() AND a.status IN ('scheduled', 'confirmed')";
    }

    $sql .= " ORDER BY a.scheduled_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    json_success([
        'count'        => count($appointments),
        'appointments' => $appointments
    ]);
}

// ── Update Appointment Status ─────────────────────────
elseif ($action === 'update_status' && $method === 'PUT') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $input = get_json_input();
    $appointmentId = intval($input['appointment_id'] ?? 0);
    $newStatus = sanitize_input($input['status'] ?? '');
    $report = sanitize_input($input['report'] ?? '');
    $comment = sanitize_input($input['comment'] ?? '');

    if (!$appointmentId) {
        json_error('appointment_id is required');
    }

    $validStatuses = ['confirmed', 'completed', 'cancelled', 'no_show'];
    if (!in_array($newStatus, $validStatuses)) {
        json_error('Status must be one of: ' . implode(', ', $validStatuses));
    }

    $db = get_db();

    // Verify doctor owns this appointment
    $stmt = $db->prepare("
        SELECT appointment_id, status, parent_id
        FROM appointment
        WHERE appointment_id = :aid AND specialist_id = :doctor_id
    ");
    $stmt->execute([':aid' => $appointmentId, ':doctor_id' => $authUser['user_id']]);
    $appt = $stmt->fetch();

    if (!$appt) {
        json_error('Appointment not found', 404);
    }

    if (in_array($appt['status'], ['completed', 'cancelled'])) {
        json_error("Cannot update a {$appt['status']} appointment");
    }

    try {
        $db->beginTransaction();

        $fields = ["status = :status"];
        $params = [':status' => $newStatus, ':aid' => $appointmentId];

        if ($report) {
            $fields[] = "report = :report";
            $params[':report'] = $report;
        }
        if ($comment) {
            $fields[] = "comment = :comment";
            $params[':comment'] = $comment;
        }

        $sql = "UPDATE appointment SET " . implode(', ', $fields) . " WHERE appointment_id = :aid";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Notify parent
        $notifTitle = 'Appointment ' . ucfirst($newStatus);
        $notifMessage = "Your appointment has been {$newStatus}.";
        $notifType = 'appointment_' . $newStatus;
        if (!in_array($notifType, ['appointment_confirmed', 'appointment_cancelled'])) {
            $notifType = 'general';
        }

        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (:user_id, :title, :message, :type)
        ");
        $stmt->execute([
            ':user_id' => $appt['parent_id'],
            ':title'   => $notifTitle,
            ':message' => $notifMessage,
            ':type'    => $notifType
        ]);

        $db->commit();

        json_success(['updated' => 1], "Appointment {$newStatus} successfully");

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to update appointment', 500);
    }
}

// ── Today's Appointments ──────────────────────────────
elseif ($action === 'today' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $db = get_db();

    $stmt = $db->prepare("
        SELECT a.appointment_id, a.child_id, a.status, a.type, a.scheduled_at, a.comment,
               pu.first_name AS parent_first_name, pu.last_name AS parent_last_name,
               ch.first_name AS child_first_name, ch.last_name AS child_last_name
        FROM appointment a
        INNER JOIN parent p ON a.parent_id = p.parent_id
        INNER JOIN users pu ON p.parent_id = pu.user_id
        LEFT JOIN child ch ON a.child_id = ch.child_id
        WHERE a.specialist_id = :doctor_id AND DATE(a.scheduled_at) = CURDATE()
        ORDER BY a.scheduled_at ASC
    ");
    $stmt->execute([':doctor_id' => $authUser['user_id']]);
    $appointments = $stmt->fetchAll();

    json_success([
        'date'         => date('Y-m-d'),
        'count'        => count($appointments),
        'appointments' => $appointments
    ]);
}

// ── Statistics ────────────────────────────────────────
elseif ($action === 'stats' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $db = get_db();
    $doctorId = $authUser['user_id'];

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status IN ('scheduled', 'confirmed') AND scheduled_at >= NOW() THEN 1 ELSE 0 END) AS upcoming,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN DATE(scheduled_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN scheduled_at >= CURDATE() AND scheduled_at < CURDATE() + INTERVAL 7 DAY THEN 1 ELSE 0 END) AS this_week
        FROM appointment
        WHERE specialist_id = :doctor_id
    ");
    $stmt->execute([':doctor_id' => $doctorId]);
    $stats = $stmt->fetch();

    // Total unique patients
    $stmt2 = $db->prepare("
        SELECT COUNT(DISTINCT child_id) AS total_patients
        FROM appointment
        WHERE specialist_id = :doctor_id AND child_id IS NOT NULL
    ");
    $stmt2->execute([':doctor_id' => $doctorId]);
    $patients = $stmt2->fetch();

    json_success([
        'stats' => [
            'total_appointments' => (int)($stats['total'] ?? 0),
            'upcoming'           => (int)($stats['upcoming'] ?? 0),
            'completed'          => (int)($stats['completed'] ?? 0),
            'cancelled'          => (int)($stats['cancelled'] ?? 0),
            'today'              => (int)($stats['today'] ?? 0),
            'this_week'          => (int)($stats['this_week'] ?? 0),
            'total_patients'     => (int)($patients['total_patients'] ?? 0)
        ]
    ]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'GET  ?action=appointments'  => 'View schedule (?status=, ?date=, ?upcoming=1)',
            'PUT  ?action=update_status' => 'Update status (appointment_id, status, ?report, ?comment)',
            'GET  ?action=today'         => 'Today\'s appointments',
            'GET  ?action=stats'         => 'Appointment statistics'
        ]
    ], 'Bright Steps Clinic — Doctor Schedule API');
}

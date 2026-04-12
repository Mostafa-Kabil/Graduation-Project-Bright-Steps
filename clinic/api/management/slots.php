<?php
/**
 * Bright Steps Clinic API — Appointment Slots Management
 * 
 * Endpoints:
 *   POST   ?action=create    — Create available appointment slots
 *   GET    ?action=list      — List available slots (filter by doctor/date)
 *   DELETE ?action=remove    — Remove a slot
 *   GET    ?action=available — Get available slots for booking (public)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Create Slot ───────────────────────────────────────
if ($action === 'create' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, ['admin', 'clinic', 'specialist', 'doctor']);

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['doctor_id', 'clinic_id', 'day_of_week', 'start_time', 'end_time']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    $dayOfWeek = intval($input['day_of_week']);
    if ($dayOfWeek < 0 || $dayOfWeek > 6) {
        json_error('day_of_week must be 0 (Sunday) to 6 (Saturday)');
    }

    $db = get_db();

    // Verify doctor exists in the clinic
    $stmt = $db->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = :sid AND clinic_id = :cid");
    $stmt->execute([':sid' => $input['doctor_id'], ':cid' => $input['clinic_id']]);
    if (!$stmt->fetch()) {
        json_error('Doctor not found in this clinic', 404);
    }

    // Check for overlapping slots
    $stmt = $db->prepare("
        SELECT slot_id FROM appointment_slots
        WHERE doctor_id = :did AND day_of_week = :dow AND is_active = 1
          AND ((start_time <= :start AND end_time > :start2) OR (start_time < :end AND end_time >= :end2))
    ");
    $stmt->execute([
        ':did'    => $input['doctor_id'],
        ':dow'    => $dayOfWeek,
        ':start'  => $input['start_time'],
        ':start2' => $input['start_time'],
        ':end'    => $input['end_time'],
        ':end2'   => $input['end_time']
    ]);
    if ($stmt->fetch()) {
        json_error('This slot overlaps with an existing slot', 409);
    }

    $stmt = $db->prepare("
        INSERT INTO appointment_slots (doctor_id, clinic_id, day_of_week, start_time, end_time, slot_duration)
        VALUES (:doctor_id, :clinic_id, :day_of_week, :start_time, :end_time, :slot_duration)
    ");
    $stmt->execute([
        ':doctor_id'     => intval($input['doctor_id']),
        ':clinic_id'     => intval($input['clinic_id']),
        ':day_of_week'   => $dayOfWeek,
        ':start_time'    => $input['start_time'],
        ':end_time'      => $input['end_time'],
        ':slot_duration' => intval($input['slot_duration'] ?? 30)
    ]);

    json_success([
        'slot_id' => (int)$db->lastInsertId()
    ], 'Slot created successfully', 201);
}

// ── List Slots ────────────────────────────────────────
elseif ($action === 'list' && $method === 'GET') {
    $doctorId = get_int('doctor_id');
    $clinicId = get_int('clinic_id');

    $db = get_db();

    $sql = "
        SELECT sl.slot_id, sl.doctor_id, sl.clinic_id, sl.day_of_week, sl.start_time, sl.end_time,
               sl.slot_duration, sl.is_active,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization,
               c.clinic_name
        FROM appointment_slots sl
        INNER JOIN specialist s ON sl.doctor_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        INNER JOIN clinic c ON sl.clinic_id = c.clinic_id
        WHERE sl.is_active = 1
    ";
    $params = [];

    if ($doctorId) {
        $sql .= " AND sl.doctor_id = :doctor_id";
        $params[':doctor_id'] = $doctorId;
    }
    if ($clinicId) {
        $sql .= " AND sl.clinic_id = :clinic_id";
        $params[':clinic_id'] = $clinicId;
    }

    $sql .= " ORDER BY sl.day_of_week ASC, sl.start_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $slots = $stmt->fetchAll();

    // Add day names
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($slots as &$slot) {
        $slot['day_name'] = $dayNames[$slot['day_of_week']] ?? 'Unknown';
    }

    json_success([
        'count' => count($slots),
        'slots' => $slots
    ]);
}

// ── Remove Slot ───────────────────────────────────────
elseif ($action === 'remove' && $method === 'DELETE') {
    $authUser = require_auth();
    require_role($authUser, ['admin', 'clinic', 'specialist', 'doctor']);

    $slotId = get_int('slot_id');
    if (!$slotId) {
        json_error('slot_id parameter is required');
    }

    $db = get_db();

    $stmt = $db->prepare("UPDATE appointment_slots SET is_active = 0 WHERE slot_id = :sid");
    $stmt->execute([':sid' => $slotId]);

    if ($stmt->rowCount() === 0) {
        json_error('Slot not found', 404);
    }

    json_success([], 'Slot removed successfully');
}

// ── Available Slots for Booking ───────────────────────
elseif ($action === 'available' && $method === 'GET') {
    $doctorId = get_int('doctor_id');
    $date = get_string('date'); // YYYY-MM-DD

    if (!$doctorId) {
        json_error('doctor_id parameter is required');
    }

    $db = get_db();

    // If date given, get the day of week for that date
    if ($date) {
        $targetDate = new DateTime($date);
        $dayOfWeek = (int)$targetDate->format('w'); // 0 = Sunday
    } else {
        // Default: show next 7 days
        $results = [];
        for ($i = 0; $i < 7; $i++) {
            $targetDate = new DateTime();
            $targetDate->modify("+$i days");
            $dow = (int)$targetDate->format('w');
            $dateStr = $targetDate->format('Y-m-d');

            // Get slots for this day
            $stmt = $db->prepare("
                SELECT slot_id, start_time, end_time, slot_duration
                FROM appointment_slots
                WHERE doctor_id = :did AND day_of_week = :dow AND is_active = 1
                ORDER BY start_time ASC
            ");
            $stmt->execute([':did' => $doctorId, ':dow' => $dow]);
            $daySlots = $stmt->fetchAll();

            if (empty($daySlots)) continue;

            // Get booked appointments for this date
            $stmt2 = $db->prepare("
                SELECT scheduled_at FROM appointment
                WHERE specialist_id = :did AND DATE(scheduled_at) = :date AND status NOT IN ('cancelled')
            ");
            $stmt2->execute([':did' => $doctorId, ':date' => $dateStr]);
            $booked = array_column($stmt2->fetchAll(), 'scheduled_at');
            $bookedTimes = array_map(function($t) {
                return (new DateTime($t))->format('H:i');
            }, $booked);

            // Generate individual time slots
            $timeSlots = [];
            foreach ($daySlots as $slot) {
                $start = new DateTime($dateStr . ' ' . $slot['start_time']);
                $end = new DateTime($dateStr . ' ' . $slot['end_time']);
                $interval = new DateInterval('PT' . $slot['slot_duration'] . 'M');

                while ($start < $end) {
                    $timeStr = $start->format('H:i');
                    $now = new DateTime();
                    $slotDateTime = new DateTime($dateStr . ' ' . $timeStr);

                    $timeSlots[] = [
                        'time'      => $timeStr,
                        'datetime'  => $slotDateTime->format('Y-m-d H:i:s'),
                        'available' => !in_array($timeStr, $bookedTimes) && $slotDateTime > $now
                    ];
                    $start->add($interval);
                }
            }

            if (!empty($timeSlots)) {
                $results[] = [
                    'date'  => $dateStr,
                    'day'   => $targetDate->format('l'),
                    'slots' => $timeSlots
                ];
            }
        }

        json_success([
            'doctor_id' => $doctorId,
            'schedule'  => $results
        ]);
    }

    // Single date requested
    $stmt = $db->prepare("
        SELECT slot_id, start_time, end_time, slot_duration
        FROM appointment_slots
        WHERE doctor_id = :did AND day_of_week = :dow AND is_active = 1
        ORDER BY start_time ASC
    ");
    $stmt->execute([':did' => $doctorId, ':dow' => $dayOfWeek]);
    $daySlots = $stmt->fetchAll();

    // Get booked
    $stmt2 = $db->prepare("
        SELECT scheduled_at FROM appointment
        WHERE specialist_id = :did AND DATE(scheduled_at) = :date AND status NOT IN ('cancelled')
    ");
    $stmt2->execute([':did' => $doctorId, ':date' => $date]);
    $booked = array_column($stmt2->fetchAll(), 'scheduled_at');
    $bookedTimes = array_map(function($t) {
        return (new DateTime($t))->format('H:i');
    }, $booked);

    $timeSlots = [];
    foreach ($daySlots as $slot) {
        $start = new DateTime($date . ' ' . $slot['start_time']);
        $end = new DateTime($date . ' ' . $slot['end_time']);
        $interval = new DateInterval('PT' . $slot['slot_duration'] . 'M');

        while ($start < $end) {
            $timeStr = $start->format('H:i');
            $now = new DateTime();
            $slotDateTime = new DateTime($date . ' ' . $timeStr);

            $timeSlots[] = [
                'time'      => $timeStr,
                'datetime'  => $slotDateTime->format('Y-m-d H:i:s'),
                'available' => !in_array($timeStr, $bookedTimes) && $slotDateTime > $now
            ];
            $start->add($interval);
        }
    }

    json_success([
        'doctor_id' => $doctorId,
        'date'      => $date,
        'day'       => $targetDate->format('l'),
        'slots'     => $timeSlots
    ]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST   ?action=create'                    => 'Create slot (doctor_id, clinic_id, day_of_week, start_time, end_time)',
            'GET    ?action=list'                      => 'List slots (?doctor_id=, ?clinic_id=)',
            'DELETE ?action=remove&slot_id=N'          => 'Remove a slot',
            'GET    ?action=available&doctor_id=N'     => 'Available booking slots (?date=YYYY-MM-DD)'
        ]
    ], 'Bright Steps Clinic — Appointment Slots API');
}

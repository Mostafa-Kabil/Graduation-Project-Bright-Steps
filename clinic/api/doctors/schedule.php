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
        SELECT appointment_id, status, parent_id, child_id, type
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

        // Feature 2: Google Meet Link Handling on Confirmation
        if ($newStatus === 'confirmed' && $appt['type'] === 'online') {
            $meetLink = 'https://meet.google.com/' . substr(md5(uniqid()), 0, 3) . '-' . substr(md5(uniqid()), 3, 4) . '-' . substr(md5(uniqid()), 7, 3);
            
            $msgContent = "Your online appointment has been confirmed. Please join using this Google Meet link at the scheduled time.";
            $msgStmt = $db->prepare("
                INSERT INTO message (sender_id, receiver_id, appointment_id, child_id, content, meeting_link)
                VALUES (:sender, :receiver, :aid, :cid, :content, :link)
            ");
            $msgStmt->execute([
                ':sender' => $authUser['user_id'],
                ':receiver' => $appt['parent_id'],
                ':aid' => $appointmentId,
                ':cid' => $appt['child_id'],
                ':content' => $msgContent,
                ':link' => $meetLink
            ]);
        }

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

        // Award points for attending appointment (50 points per 'attend_appointment' rule)
        $pointsMessage = "";
        if ($newStatus === 'completed') {
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));

            // Check/create wallet
            $walletStmt = $db->prepare("SELECT wallet_id, total_points FROM parent_points_wallet WHERE parent_id = :pid");
            $walletStmt->execute([':pid' => $appt['parent_id']]);
            $wallet = $walletStmt->fetch();

            if (!$wallet) {
                $walletStmt = $db->prepare("INSERT INTO parent_points_wallet (parent_id, total_points, last_earned_at) VALUES (:pid, 0, NOW())");
                $walletStmt->execute([':pid' => $appt['parent_id']]);
                $walletId = $db->lastInsertId();
            } else {
                $walletId = $wallet['wallet_id'];
            }

            // Check daily cap (50 points/day for attend_appointment)
            $dailyCapStmt = $db->prepare("
                SELECT COALESCE(SUM(points_earned), 0) as daily_total
                FROM parent_points_tracking
                WHERE parent_id = :pid AND action_key = 'attend_appointment' AND earned_date = :today
            ");
            $dailyCapStmt->execute([':pid' => $appt['parent_id'], ':today' => $today]);
            $dailyTotal = $dailyCapStmt->fetchColumn();

            // Check weekly cap (100 points/week for attend_appointment)
            $weeklyCapStmt = $db->prepare("
                SELECT COALESCE(SUM(points_earned), 0) as weekly_total
                FROM parent_points_tracking
                WHERE parent_id = :pid AND action_key = 'attend_appointment' AND earned_date >= :weekStart
            ");
            $weeklyCapStmt->execute([':pid' => $appt['parent_id'], ':weekStart' => $weekStart]);
            $weeklyTotal = $weeklyCapStmt->fetchColumn();

            // Get rule caps
            $ruleStmt = $db->prepare("SELECT daily_cap, weekly_cap, points_value FROM points_earning_rules WHERE action_key = 'attend_appointment'");
            $ruleStmt->execute();
            $rule = $ruleStmt->fetch();

            $dailyCap = (int) $rule['daily_cap'];
            $weeklyCap = (int) $rule['weekly_cap'];
            $pointsValue = (int) $rule['points_value'];
            $pointsToAward = 0;

            // Check if already earned points for this action today
            $alreadyEarnedStmt = $db->prepare("
                SELECT points_earned FROM parent_points_tracking
                WHERE parent_id = :pid AND action_key = 'attend_appointment' AND earned_date = :today
            ");
            $alreadyEarnedStmt->execute([':pid' => $appt['parent_id'], ':today' => $today]);
            $alreadyEarned = $alreadyEarnedStmt->fetchColumn();

            // Check if within caps and not already earned today
            if ($alreadyEarned == null && ($dailyTotal + $pointsValue) <= $dailyCap && ($weeklyTotal + $pointsValue) <= $weeklyCap) {
                $pointsToAward = $pointsValue;

                // Update wallet balance and lifetime earned
                $updateWallet = $db->prepare("UPDATE parent_points_wallet SET total_points = total_points + ?, lifetime_earned = lifetime_earned + ?, last_earned_at = NOW() WHERE wallet_id = ?");
                $updateWallet->execute([$pointsToAward, $pointsToAward, $walletId]);

                // Track the transaction (single entry per day)
                $trackStmt = $db->prepare("
                    INSERT INTO parent_points_tracking (parent_id, action_key, points_earned, earned_date, week_start_date)
                    VALUES (:pid, 'attend_appointment', ?, ?, ?)
                ");
                $trackStmt->execute([$pointsToAward, $today, $weekStart]);

                // Create points notification
                $ptsNotifStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                $ptsNotifStmt->execute([$appt['parent_id'], 'Points Earned!',
                                         "You earned {$pointsToAward} points for attending your appointment."]);
                $pointsMessage = " +{$pointsToAward} pts";
            } elseif ($alreadyEarned > 0) {
                $pointsMessage = " (Points already earned for this appointment today)";
            } elseif ($dailyTotal >= $dailyCap) {
                $pointsMessage = " (Daily appointment points cap reached)";
            } elseif ($weeklyTotal >= $weeklyCap) {
                $pointsMessage = " (Weekly appointment points cap reached)";
            }
        }

        $db->commit();

        json_success(['updated' => 1], "Appointment {$newStatus} successfully" . $pointsMessage);

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

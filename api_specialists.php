<?php
/**
 * api_specialists.php
 * Returns public/professional data for all active specialists.
 * Exposed to: Parent Dashboard (Book Appointments view)
 * Private data (email, password, phone) is NEVER returned.
 */
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

// Must be a logged-in parent (or any authenticated user) to browse specialists
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // ── Detect which optional columns exist on specialist table ────
    $colCheck = $connect->query("SHOW COLUMNS FROM `specialist`");
    $existingCols = array_column($colCheck->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $hasBio       = in_array('bio',                $existingCols);
    $hasFee       = in_array('consultation_fee',   $existingCols);
    $hasPhoto     = in_array('profile_photo',      $existingCols);
    $hasCtypes    = in_array('consultation_types', $existingCols);

    $bioCol    = $hasBio    ? 's.bio'                : "NULL AS bio";
    $feeCol    = $hasFee    ? 's.consultation_fee'   : "200.00 AS consultation_fee";
    $photoCol  = $hasPhoto  ? 's.profile_photo'      : "NULL AS profile_photo";
    $ctypesCol = $hasCtypes ? 's.consultation_types' : "'onsite,online' AS consultation_types";

    // ── Main specialists query ─────────────────────────────────────
    // Only exposes professional/public fields — never email, password, phone
    $stmt = $connect->prepare("
        SELECT
            s.specialist_id,
            s.first_name,
            s.last_name,
            s.specialization,
            s.experience_years,
            s.certificate_of_experience,
            {$ctypesCol},
            {$feeCol},
            {$photoCol},
            {$bioCol},
            c.clinic_name,
            c.location,
            c.rating,
            (
                SELECT COUNT(*)
                FROM appointment a
                WHERE a.specialist_id = s.specialist_id
                  AND a.status IN ('Scheduled','Completed')
            ) AS total_appointments
        FROM specialist s
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        INNER JOIN users u ON u.user_id = s.specialist_id
        WHERE u.status = 'active'
        ORDER BY c.rating DESC, s.experience_years DESC
    ");
    $stmt->execute();
    $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($specialists)) {
        echo json_encode(['success' => true, 'specialists' => []]);
        exit();
    }

    // ── Fetch availability slots if table exists ───────────────────
    $slotMap = [];
    try {
        $slotStmt = $connect->query("
            SELECT specialist_id, day_of_week, start_time, end_time, slot_duration_minutes
            FROM specialist_availability
            WHERE is_active = 1
            ORDER BY specialist_id, day_of_week, start_time
        ");
        $allSlots = $slotStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allSlots as $sl) {
            $did = (int)$sl['specialist_id'];
            $dow = (int)$sl['day_of_week'];
            $slotMap[$did][$dow] = [
                'start_time'    => $sl['start_time'],
                'end_time'      => $sl['end_time'],
                'slot_duration' => (int)$sl['slot_duration_minutes'],
            ];
        }
    } catch (Exception $e) {
        // specialist_availability table may not exist yet — gracefully skip
        $slotMap = [];
    }

    // ── Build next-3-days availability for each specialist ─────────
    $today = new DateTime();

    foreach ($specialists as &$sp) {
        $sid      = (int)$sp['specialist_id'];
        $docSlots = $slotMap[$sid] ?? [];

        $availability = [];
        for ($i = 0; $i < 3; $i++) {
            $d   = (clone $today)->modify("+{$i} day");
            $dow = (int)$d->format('w'); // 0=Sun … 6=Sat

            if (isset($docSlots[$dow])) {
                $sl = $docSlots[$dow];
                $availability[] = [
                    'date'          => $d->format('Y-m-d'),
                    'label'         => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $d->format('D M/j')),
                    'start_time'    => $sl['start_time'],
                    'end_time'      => $sl['end_time'],
                    'slot_duration' => $sl['slot_duration'],
                    'available'     => true,
                ];
            } else {
                $availability[] = [
                    'date'      => $d->format('Y-m-d'),
                    'label'     => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $d->format('D M/j')),
                    'available' => false,
                ];
            }
        }

        $sp['availability']       = $availability;
        $sp['experience_years']   = (int)($sp['experience_years'] ?? 0);
        $sp['rating']             = $sp['rating'] !== null ? (float)$sp['rating'] : null;
        $sp['consultation_fee']   = (float)($sp['consultation_fee'] ?? 200.00);
        $sp['total_appointments'] = (int)($sp['total_appointments'] ?? 0);
    }
    unset($sp);

    echo json_encode(['success' => true, 'specialists' => $specialists]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

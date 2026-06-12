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

    $hasBio           = in_array('bio',                $existingCols);
    $hasFee           = in_array('consultation_fee',   $existingCols);
    $hasPhoto         = in_array('profile_photo',      $existingCols);
    $hasCtypes        = in_array('consultation_types', $existingCols);
    $hasCertifications = in_array('certifications',     $existingCols);

    $bioCol    = $hasBio    ? 's.bio'                : "NULL AS bio";
    $feeCol    = $hasFee    ? 's.consultation_fee'   : "200.00 AS consultation_fee";
    $photoCol  = $hasPhoto  ? 's.profile_photo'      : "NULL AS profile_photo";
    $ctypesCol = $hasCtypes ? 's.consultation_types' : "'onsite,online' AS consultation_types";
    $certificationsCol = $hasCertifications ? 's.certifications AS certificate_of_experience' : 's.certificate_of_experience';

    $tableCheck = $connect->query("SHOW TABLES LIKE 'specialist_reviews'");
    $hasReviewsTable = $tableCheck->rowCount() > 0;
    $reviewsCols = $hasReviewsTable ? "
            (
                SELECT COUNT(*)
                FROM specialist_reviews sr
                WHERE sr.specialist_id = s.specialist_id
            ) AS total_reviews,
            (
                SELECT AVG(rating)
                FROM specialist_reviews sr
                WHERE sr.specialist_id = s.specialist_id
            ) AS db_rating" : "
            0 AS total_reviews,
            NULL AS db_rating";

    // ── Main specialists query ─────────────────────────────────────
    // Only exposes professional/public fields — never email, password, phone
    $stmt = $connect->prepare("
        SELECT
            s.specialist_id,
            s.first_name,
            s.last_name,
            s.specialization,
            s.experience_years,
            s.clinic_id,
            {$certificationsCol},
            {$ctypesCol},
            {$feeCol},
            {$photoCol},
            {$bioCol},
            COALESCE(c.clinic_name, 'Independent Practice') AS clinic_name,
            COALESCE(c.location, 'Online/Remote') AS location,
            COALESCE(c.rating, 5.0) AS clinic_rating,
            (
                SELECT COUNT(*)
                FROM appointment a
                WHERE a.specialist_id = s.specialist_id
                  AND a.status IN ('Scheduled','Completed')
            ) AS total_appointments,
            {$reviewsCols}
        FROM specialist s
        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
        INNER JOIN users u ON u.user_id = s.specialist_id
        WHERE u.status = 'active'
        ORDER BY COALESCE(c.rating, 5.0) DESC, s.experience_years DESC
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
        $slotMap = [];
    }

    $bookingsMap = [];
    $bookedMap = [];
    try {
        // Fetch booked appointments for the next 3 days (Local HEAD style)
        $todayStr = date('Y-m-d');
        $maxDateStr = date('Y-m-d', strtotime('+3 days'));
        $apptStmt = $connect->prepare("
            SELECT specialist_id, scheduled_at 
            FROM appointment 
            WHERE status NOT IN ('cancelled', 'Rejected')
              AND DATE(scheduled_at) >= ? 
              AND DATE(scheduled_at) <= ?
        ");
        $apptStmt->execute([$todayStr, $maxDateStr]);
        $allBookings = $apptStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allBookings as $b) {
            $sid = (int)$b['specialist_id'];
            $formattedDT = date('Y-m-d H:i:s', strtotime($b['scheduled_at']));
            $bookingsMap[$sid][] = $formattedDT;
        }

        // Fetch booked slots (Remote style)
        $apptsStmt = $connect->query("
            SELECT specialist_id, scheduled_at 
            FROM appointment 
            WHERE status IN ('Scheduled', 'Pending Reschedule', 'Completed') 
              AND scheduled_at >= CURDATE()
        ");
        $allAppts = $apptsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allAppts as $a) {
            $sid = (int)$a['specialist_id'];
            $dt = substr($a['scheduled_at'], 0, 16); // format: YYYY-MM-DD HH:MM
            $bookedMap[$sid][] = $dt;
        }
    } catch (Exception $e) {}

    // ── Build next-3-days availability for each specialist ─────────
    $today = new DateTime();

    foreach ($specialists as &$sp) {
        $sid      = (int)$sp['specialist_id'];
        $docSlots = $slotMap[$sid] ?? [];

        $availability = [];
        for ($i = 0; $i < 14; $i++) {
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
        $sp['booked_appointments'] = $bookingsMap[$sid] ?? [];
        $sp['experience_years']   = (int)($sp['experience_years'] ?? 0);
        $sp['rating']             = $sp['db_rating'] !== null ? (float)$sp['db_rating'] : null;
        $sp['clinic_rating']      = $sp['clinic_rating'] !== null ? (float)$sp['clinic_rating'] : null;
        $sp['consultation_fee']   = (float)($sp['consultation_fee'] ?? 200.00);
        $sp['total_appointments'] = (int)($sp['total_appointments'] ?? 0);
        $sp['total_reviews']      = (int)($sp['total_reviews'] ?? 0);
        $sp['booked_slots']       = $bookedMap[$sid] ?? [];
    }
    unset($sp);

    echo json_encode(['success' => true, 'specialists' => $specialists]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$specialist_id = intval($_GET['specialist_id'] ?? 0);
if (!$specialist_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'specialist_id is required']);
    exit;
}

$user_id = intval($_SESSION['id']);

try {
    // ── Resolve clinic_id ────────────────────────────────────────────
    $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $row = $cStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? LIMIT 1");
        $cStmt->execute([$user_id]);
        $row = $cStmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Clinic not found']);
        exit;
    }
    $clinic_id = $row['clinic_id'];

    // ── Verify specialist belongs to this clinic ─────────────────────
    $verifyStmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = ? AND clinic_id = ?");
    $verifyStmt->execute([$specialist_id, $clinic_id]);
    if (!$verifyStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Specialist not found in your clinic']);
        exit;
    }

    // ── 1. Specialist Profile ────────────────────────────────────────
    $profileStmt = $connect->prepare("
        SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, s.experience_years,
               s.certification_text, s.certification_pdf, s.phone,
               u.email, u.created_at AS joined_at
        FROM specialist s
        LEFT JOIN users u ON u.user_id = s.specialist_id
        WHERE s.specialist_id = ? AND s.clinic_id = ?
    ");
    $profileStmt->execute([$specialist_id, $clinic_id]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    // ── 2. Weekly Slot Schedule ──────────────────────────────────────
    $slotsStmt = $connect->prepare("
        SELECT slot_id, day_of_week, start_time, end_time, slot_duration, is_active
        FROM appointment_slots
        WHERE doctor_id = ? AND is_active = 1
        ORDER BY day_of_week ASC, start_time ASC
    ");
    $slotsStmt->execute([$specialist_id]);
    $slots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add human-readable day names
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($slots as &$slot) {
        $slot['day_name'] = $dayNames[$slot['day_of_week']] ?? 'Unknown';
    }
    unset($slot);

    // ── 3. Appointments + Payment method ────────────────────────────
    $apptStmt = $connect->prepare("
        SELECT a.appointment_id, a.status, a.type, a.scheduled_at,
               u.first_name AS parent_first_name, u.last_name AS parent_last_name,
               c.first_name AS child_first_name, c.last_name AS child_last_name,
               p.method AS payment_method, p.status AS payment_status,
               p.amount_post_discount AS payment_amount
        FROM appointment a
        JOIN users u ON u.user_id = a.parent_id
        LEFT JOIN child c ON c.child_id = a.child_id
        LEFT JOIN payment p ON p.payment_id = a.payment_id
        WHERE a.specialist_id = ?
        ORDER BY a.scheduled_at DESC
        LIMIT 50
    ");
    $apptStmt->execute([$specialist_id]);
    $appointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 4. Appointment Stats ─────────────────────────────────────────
    $statsStmt = $connect->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status IN ('scheduled','confirmed') THEN 1 ELSE 0 END) AS upcoming,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN DATE(scheduled_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
            COUNT(DISTINCT child_id) AS total_patients,
            (SELECT IFNULL(AVG(rating), 0) FROM feedback WHERE specialist_id = ?) AS avg_rating
        FROM appointment WHERE specialist_id = ?
    ");
    $statsStmt->execute([$specialist_id, $specialist_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // ── 5. Free vs Booked slot counts (next 7 days) ──────────────────
    $freeCount = 0;
    $bookedCount = 0;
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));
        $dow  = intval(date('w', strtotime($date)));

        // Get schedule for this day of week
        foreach ($slots as $sl) {
            if (intval($sl['day_of_week']) !== $dow) continue;
            $start = new DateTime($date . ' ' . $sl['start_time']);
            $end   = new DateTime($date . ' ' . $sl['end_time']);
            $dur   = intval($sl['slot_duration']) ?: 30;
            $interval = new DateInterval("PT{$dur}M");
            $current = clone $start;
            while ($current < $end) {
                // Check if this slot is booked
                $timeStr = $current->format('H:i');
                $booked = false;
                foreach ($appointments as $a) {
                    if (!$a['scheduled_at']) continue;
                    $apDate = date('Y-m-d', strtotime($a['scheduled_at']));
                    $apTime = date('H:i', strtotime($a['scheduled_at']));
                    if ($apDate === $date && $apTime === $timeStr && in_array($a['status'], ['scheduled','confirmed'])) {
                        $booked = true;
                        break;
                    }
                }
                if ($booked) $bookedCount++;
                else $freeCount++;
                $current->add($interval);
            }
        }
    }

    echo json_encode([
        'success'      => true,
        'profile'      => $profile,
        'slots'        => $slots,
        'appointments' => $appointments,
        'stats'        => $stats,
        'availability' => [
            'free'   => $freeCount,
            'booked' => $bookedCount,
            'window' => 'Next 7 days'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$specialistId = $_GET['specialist_id'] ?? '';
$dateVal = $_GET['date'] ?? '';

if (!$specialistId || !$dateVal) {
    echo json_encode(['error' => 'Specialist and Date are required.']);
    exit();
}

// Validate date format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit();
}

try {
    // 1. Get specialist availability for this day of the week
    $timestamp = strtotime($dateVal);
    $dayOfWeek = (int)date('w', $timestamp); // 0=Sun, 6=Sat

    $stmt = $connect->prepare("
        SELECT start_time, end_time, slot_duration_minutes 
        FROM specialist_availability 
        WHERE specialist_id = ? AND day_of_week = ? AND is_active = 1
    ");
    $stmt->execute([$specialistId, $dayOfWeek]);
    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$availability) {
        // Try fallback table appointment_slots if specialist_availability has no slots
        $stmt = $connect->prepare("
            SELECT start_time, end_time, slot_duration AS slot_duration_minutes 
            FROM appointment_slots 
            WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1
        ");
        $stmt->execute([$specialistId, $dayOfWeek]);
        $availability = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$availability) {
        echo json_encode([
            'success' => true, 
            'available' => false, 
            'reason' => 'Specialist is not available on this day.',
            'slots' => []
        ]);
        exit();
    }

    $startTime = $availability['start_time'];
    $endTime = $availability['end_time'];
    $duration = (int)($availability['slot_duration_minutes'] ?? 30);
    if ($duration <= 0) $duration = 30;

    // 2. Fetch all booked/scheduled appointments for this specialist on this date
    // Note: status != 'cancelled'
    $apptStmt = $connect->prepare("
        SELECT scheduled_at 
        FROM appointment 
        WHERE specialist_id = ? 
          AND DATE(scheduled_at) = ? 
          AND status NOT IN ('cancelled', 'Rejected')
    ");
    $apptStmt->execute([$specialistId, $dateVal]);
    $bookedTimes = $apptStmt->fetchAll(PDO::FETCH_COLUMN);

    // Convert booked times to just H:i:s
    $bookedSlots = [];
    foreach ($bookedTimes as $bt) {
        $bookedSlots[] = date('H:i:s', strtotime($bt));
    }

    // 3. Generate slots
    $slots = [];
    $current = strtotime($startTime);
    $end = strtotime($endTime);

    while ($current < $end) {
        $timeStr = date('H:i:s', $current);
        $timeFormatted = date('g:i A', $current);
        
        $isBooked = in_array($timeStr, $bookedSlots);

        $slots[] = [
            'time' => date('H:i', $current),
            'time_db' => $timeStr,
            'formatted_time' => $timeFormatted,
            'available' => !$isBooked
        ];

        $current = strtotime("+$duration minutes", $current);
    }

    echo json_encode([
        'success' => true,
        'available' => true,
        'slots' => $slots,
        'working_hours' => [
            'start' => $startTime,
            'end' => $endTime,
            'duration' => $duration
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

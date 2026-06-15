<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

// Must be logged in parent
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$specialist_id = $_GET['specialist_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$specialist_id || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'specialist_id and date are required']);
    exit();
}

try {
    // 1. Get day of week (0 = Sunday, 1 = Monday, ...)
    $dt = new DateTime($date);
    $day_of_week = (int)$dt->format('w');
    
    // 2. Query availability for this day of week
    $stmt = $connect->prepare("
        SELECT start_time, end_time 
        FROM specialist_availability 
        WHERE specialist_id = ? AND day_of_week = ? AND is_active = 1
    ");
    $stmt->execute([$specialist_id, $day_of_week]);
    $window = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$window) {
        echo json_encode(['success' => true, 'date' => $date, 'slots' => [], 'message' => 'Specialist not available on this day']);
        exit();
    }

    $start_time = $window['start_time'];
    $end_time = $window['end_time'];

    // Get doctor onboarding preferences
    $stmtPrefs = $connect->prepare("SELECT session_duration, max_patients_per_day FROM doctor_onboarding WHERE doctor_id = ?");
    $stmtPrefs->execute([$specialist_id]);
    $prefs = $stmtPrefs->fetch(PDO::FETCH_ASSOC);
    $session_duration = $prefs['session_duration'] ?? 30;
    $max_patients = $prefs['max_patients_per_day'] ?? 10;

    // 4. Query booked appointments for this date
    $stmtBooked = $connect->prepare("
        SELECT scheduled_at 
        FROM appointment 
        WHERE specialist_id = ? 
          AND DATE(scheduled_at) = ? 
          AND status NOT IN ('Cancelled')
    ");
    $stmtBooked->execute([$specialist_id, $date]);
    $booked_rows = $stmtBooked->fetchAll(PDO::FETCH_ASSOC);
    
    // Check max patients
    if (count($booked_rows) >= $max_patients) {
        echo json_encode(['success' => true, 'date' => $date, 'slots' => [], 'message' => 'Specialist is fully booked on this day']);
        exit();
    }

    $booked_times = [];
    foreach ($booked_rows as $row) {
        $dt_booked = new DateTime($row['scheduled_at']);
        $booked_times[] = $dt_booked->format('H:i:s');
    }

    // 3. Generate slots with gap (session_duration * 2)
    $start = new DateTime("$date $start_time");
    $end = new DateTime("$date $end_time");
    $slots = [];
    $total_minutes = $session_duration * 2;
    $interval = new DateInterval("PT{$total_minutes}M");

    $now = new DateTime();

    $current = clone $start;
    while ($current < $end) {
        $time_str = $current->format('H:i:s');
        $display_time = $current->format('h:i A');
        $is_past = $current <= $now;
        $is_booked = in_array($time_str, $booked_times);

        $slots[] = [
            'time' => substr($time_str, 0, 5), // HH:MM
            'label' => $display_time,
            'booked' => $is_booked,
            'past' => $is_past
        ];

        $current->add($interval);
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'slots' => $slots
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

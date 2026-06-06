<?php
session_start();
require_once '../connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$specialist_id = intval($_GET['specialist_id'] ?? 0);
$date = $_GET['date'] ?? '';

if ($specialist_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid specialist_id or date format (YYYY-MM-DD)']);
    exit;
}

try {
    // 1. Determine day of week (0 = Sunday, 1 = Monday... 6 = Saturday)
    // Note: PHP 'w' format returns 0 (for Sunday) through 6 (for Saturday)
    $dayOfWeek = (int)date('w', strtotime($date));

    // 2. Fetch working windows for this day
    $stmt = $connect->prepare("
        SELECT start_time, end_time 
        FROM specialist_availability 
        WHERE specialist_id = ? AND day_of_week = ? AND is_active = 1
    ");
    $stmt->execute([$specialist_id, $dayOfWeek]);
    $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($windows) === 0) {
        echo json_encode([
            "success" => true,
            "date" => $date,
            "slots" => [],
            "message" => "Specialist not available on this day"
        ]);
        exit;
    }

    // 3. Generate 30-minute slots
    $slots = [];
    $now = new DateTime();

    foreach ($windows as $win) {
        $start = new DateTime("$date {$win['start_time']}");
        $end = new DateTime("$date {$win['end_time']}");
        
        while ($start < $end) {
            $slotTime = $start->format('H:i');
            $slotLabel = $start->format('g:i A');
            
            $isPast = $start <= $now;

            $slots[] = [
                'time' => $slotTime,
                'label' => $slotLabel,
                'datetime_full' => $start->format('Y-m-d H:i:s'),
                'booked' => false,
                'past' => $isPast
            ];

            $start->modify('+30 minutes');
        }
    }

    if (count($slots) > 0) {
        // 4. Check bookings
        $times = array_column($slots, 'datetime_full');
        $placeholders = implode(',', array_fill(0, count($times), '?'));
        
        $params = array_merge([$specialist_id], $times);
        
        $bookStmt = $connect->prepare("
            SELECT scheduled_at 
            FROM appointment 
            WHERE specialist_id = ? 
              AND status NOT IN ('Cancelled', 'Rejected')
              AND scheduled_at IN ($placeholders)
        ");
        $bookStmt->execute($params);
        
        $bookedTimes = $bookStmt->fetchAll(PDO::FETCH_COLUMN);

        // Mark as booked
        foreach ($slots as &$slot) {
            if (in_array($slot['datetime_full'], $bookedTimes)) {
                $slot['booked'] = true;
            }
            unset($slot['datetime_full']); // Clean up response
        }
    }

    echo json_encode([
        "success" => true,
        "date" => $date,
        "slots" => $slots
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error']);
}

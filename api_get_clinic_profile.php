<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

$clinic_id = $_GET['id'] ?? null;
if (!$clinic_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Clinic ID is required']);
    exit();
}

try {
    // 1. Get clinic details
    $stmtClinic = $connect->prepare("
        SELECT clinic_id, clinic_name, location, logo_url, description, bio, rating
        FROM clinic WHERE clinic_id = ?
    ");
    $stmtClinic->execute([$clinic_id]);
    $clinic = $stmtClinic->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        exit();
    }

    // 2. Get specialists
    $stmtSpec = $connect->prepare("
        SELECT 
            s.specialist_id, s.first_name, s.last_name, s.specialization,
            s.experience_years, s.profile_photo,
            (SELECT ROUND(AVG(rating), 1) FROM specialist_reviews WHERE specialist_id = s.specialist_id) as avg_rating
        FROM specialist s
        JOIN users u ON s.specialist_id = u.user_id
        WHERE s.clinic_id = ? AND u.status = 'active'
    ");
    $stmtSpec->execute([$clinic_id]);
    $specialists = $stmtSpec->fetchAll(PDO::FETCH_ASSOC);
    
    // Get availability for specialists
    $stmtAvail = $connect->prepare("
        SELECT day_of_week, start_time, end_time, slot_duration_minutes as slot_duration
        FROM specialist_availability
        WHERE specialist_id = ? AND is_active = 1
        ORDER BY day_of_week, start_time
    ");
    $days_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    foreach ($specialists as &$sp) {
        $stmtAvail->execute([$sp['specialist_id']]);
        $avail = $stmtAvail->fetchAll(PDO::FETCH_ASSOC);
        foreach($avail as &$a) {
            $a['day_name'] = $days_map[$a['day_of_week']] ?? 'Unknown';
        }
        $sp['availability'] = $avail;
        $sp['avg_rating'] = $sp['avg_rating'] ? (float)$sp['avg_rating'] : 0.0;
    }

    // 3. Get clinic reviews
    $stmtReviews = $connect->prepare("
        SELECT r.rating, r.comment, r.created_at, p.first_name, SUBSTRING(p.last_name, 1, 1) as last_initial
        FROM clinic_reviews r
        JOIN parent p ON r.parent_id = p.parent_id
        WHERE r.clinic_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmtReviews->execute([$clinic_id]);
    $reviews_items = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

    $total_reviews = count($reviews_items);
    $avg_rating = $clinic['rating'] ? (float)$clinic['rating'] : 0.0;
    
    // Format review items
    foreach($reviews_items as &$r) {
        $r['parent_name'] = $r['first_name'] . ' ' . $r['last_initial'] . '.';
        unset($r['first_name']);
        unset($r['last_initial']);
    }

    $reviews = [
        'avg_rating' => $avg_rating,
        'total' => $total_reviews,
        'items' => $reviews_items
    ];

    echo json_encode([
        'success' => true,
        'clinic' => $clinic,
        'specialists' => $specialists,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

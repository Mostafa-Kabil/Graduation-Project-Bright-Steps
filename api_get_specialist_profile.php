<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

$specialist_id = $_GET['id'] ?? null;
if (!$specialist_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Specialist ID is required']);
    exit();
}

try {
    // 1. Get specialist details
    $stmt = $connect->prepare("
        SELECT 
            s.specialist_id, s.first_name, s.last_name, s.specialization,
            s.experience_years, s.certificate_of_experience,
            s.patient_age_group, s.therapy_approaches, s.focus_areas, s.description,
            s.consultation_types, s.profile_photo, s.consultation_fee,
            s.bio, s.clinic_id
        FROM specialist s
        JOIN users u ON s.specialist_id = u.user_id
        WHERE s.specialist_id = ? AND u.status = 'active'
    ");
    $stmt->execute([$specialist_id]);
    $specialist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$specialist) {
        http_response_code(404);
        echo json_encode(['error' => 'Specialist not found']);
        exit();
    }

    // 2. Get clinic details
    $clinic_id = $specialist['clinic_id'];
    $stmtClinic = $connect->prepare("
        SELECT clinic_id, clinic_name, location, logo_url, description
        FROM clinic WHERE clinic_id = ?
    ");
    $stmtClinic->execute([$clinic_id]);
    $clinic = $stmtClinic->fetch(PDO::FETCH_ASSOC);

    // 3. Get availability
    $stmtAvail = $connect->prepare("
        SELECT day_of_week, start_time, end_time, slot_duration_minutes as slot_duration
        FROM specialist_availability
        WHERE specialist_id = ? AND is_active = 1
        ORDER BY day_of_week, start_time
    ");
    $stmtAvail->execute([$specialist_id]);
    $availability_raw = $stmtAvail->fetchAll(PDO::FETCH_ASSOC);

    $days_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $availability = [];
    foreach ($availability_raw as $av) {
        $av['day_name'] = $days_map[$av['day_of_week']] ?? 'Unknown';
        $availability[] = $av;
    }

    // 4. Get reviews
    $stmtReviews = $connect->prepare("
        SELECT r.rating, r.comment, r.created_at, p.first_name, SUBSTRING(p.last_name, 1, 1) as last_initial
        FROM specialist_reviews r
        JOIN parent p ON r.parent_id = p.parent_id
        WHERE r.specialist_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmtReviews->execute([$specialist_id]);
    $reviews_items = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

    $total_reviews = count($reviews_items);
    $avg_rating = 0;
    if ($total_reviews > 0) {
        $sum = array_sum(array_column($reviews_items, 'rating'));
        $avg_rating = round($sum / $total_reviews, 1);
    }
    
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
        'specialist' => $specialist,
        'clinic' => $clinic ?: null,
        'availability' => $availability,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
?>

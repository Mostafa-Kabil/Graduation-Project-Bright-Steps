<?php
session_start();
require_once '../connection.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid specialist id']);
    exit;
}

try {
    // Detect optional columns safely
    $colCheck = $connect->query("SHOW COLUMNS FROM `specialist`");
    $existingCols = array_column($colCheck->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $bioCol = in_array('bio', $existingCols) ? 's.bio' : "'' AS bio";
    $photoCol = in_array('profile_photo', $existingCols) ? 's.profile_photo' : "'' AS profile_photo";
    $descCol = in_array('description', $existingCols) ? 's.description' : "'' AS description";
    $expCol = in_array('experience_years', $existingCols) ? 's.experience_years' : "0 AS experience_years";

    $clinicColCheck = $connect->query("SHOW COLUMNS FROM `clinic`");
    $clinicCols = array_column($clinicColCheck->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $clinicLogoCol = in_array('logo_url', $clinicCols) ? 'c.logo_url' : "'' AS clinic_logo";

    $stmt = $connect->prepare("
        SELECT 
            s.specialist_id, s.first_name, s.last_name, s.specialization,
            {$bioCol}, {$photoCol}, {$descCol}, {$expCol},
            c.clinic_id, c.clinic_name, c.location AS clinic_location, {$clinicLogoCol},
            ob.age_groups, ob.therapy_approaches, ob.focus_areas, ob.consultation_types
        FROM specialist s
        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
        LEFT JOIN doctor_onboarding ob ON s.specialist_id = ob.doctor_id
        WHERE s.specialist_id = ?
    ");
    $stmt->execute([$id]);
    $spec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spec) {
        http_response_code(404);
        echo json_encode(['error'=>'Specialist not found']);
        exit;
    }

    // Availability
    $availability = [];
    try {
    $slotDuration = 30; // default
    try {
        $stmtAvail = $connect->prepare("
            SELECT day_of_week, start_time, end_time, slot_duration
            FROM appointment_slots
            WHERE doctor_id = ? AND is_active = 1
            ORDER BY day_of_week, start_time
        ");
        $stmtAvail->execute([$id]);
        $days_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($stmtAvail->fetchAll(PDO::FETCH_ASSOC) as $av) {
            if (isset($av['slot_duration']) && $av['slot_duration'] > 0) {
                $slotDuration = (int)$av['slot_duration'];
            }
            $availability[] = [
                'day' => $days_map[(int)$av['day_of_week']] ?? 'Unknown',
                'start' => date('H:i', strtotime($av['start_time'])),
                'end' => date('H:i', strtotime($av['end_time']))
            ];
        }
    } catch (Exception $e) {}

    // Reviews
    $reviews = [];
    $avg_rating = 0;
    $review_count = 0;
    try {
        $stmtReviews = $connect->prepare("
            SELECT r.rating, r.comment, r.created_at as date, p.first_name as parent
            FROM specialist_reviews r
            JOIN parent p ON r.parent_id = p.parent_id
            WHERE r.specialist_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmtReviews->execute([$id]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
        $review_count = count($reviews);
        if ($review_count > 0) {
            $sum = array_sum(array_column($reviews, 'rating'));
            $avg_rating = round($sum / $review_count, 1);
        }
    } catch (Exception $e) {}

    // Map to the exact shape requested by the prompt
    $response = [
        "specialist_id" => (int)$spec['specialist_id'],
        "logo_url" => $spec['profile_photo'] ?: "",
        "full_name" => "Dr. " . $spec['first_name'] . " " . $spec['last_name'],
        "bio" => $spec['bio'] ?: "",
        "description" => $spec['description'] ?: "",
        "specialties" => array_values(array_filter(array_map('trim', explode(',', $spec['specialization'] ?? '')))),
        "years_experience" => (int)$spec['experience_years'],
        "certificates" => [], // Not in schema, mocked
        "clinic" => $spec['clinic_id'] ? [
            "id" => (int)$spec['clinic_id'],
            "name" => $spec['clinic_name'],
            "logo_url" => $spec['clinic_logo'] ?? "",
            "location" => $spec['clinic_location'] ?? ""
        ] : null,
        "patient_age_group" => $spec['age_groups'] ? implode(', ', json_decode($spec['age_groups'], true) ?: []) : "",
        "therapy_approaches" => $spec['therapy_approaches'] ? json_decode($spec['therapy_approaches'], true) ?: [] : [],
        "session_preferences" => ["duration" => $slotDuration . " min", "frequency" => "As needed"],
        "consultation_types" => $spec['consultation_types'] ? json_decode($spec['consultation_types'], true) ?: [] : [],
        "focus_areas" => $spec['focus_areas'] ? json_decode($spec['focus_areas'], true) ?: [] : [],
        "availability" => $availability,
        "average_rating" => $avg_rating,
        "review_count" => $review_count,
        "reviews" => $reviews
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Server Error: ' . $e->getMessage()]);
}

<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    die(json_encode(["error" => "Unauthorized"]));
}

try {
    // The clinic login sets $_SESSION['id'] = clinic_id directly
    $clinic_id = $_SESSION['id'];

    // 1. Fetch the clinic record by clinic_id
    $cStmt = $connect->prepare("SELECT * FROM clinic WHERE clinic_id = ? LIMIT 1");
    $cStmt->execute([$clinic_id]);
    $clinic = $cStmt->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        echo json_encode([
            "success" => true,
            "clinic" => ["clinic_name" => $_SESSION['clinic_name'] ?? "My Clinic", "clinic_id" => $clinic_id],
            "specialists" => [],
            "patients" => [],
            "stats" => [
                "total_appointments" => 0,
                "today_appointments" => 0,
                "completed_appointments" => 0,
                "pending_appointments" => 0,
                "revenue" => 0,
                "avg_rating" => 0
            ],
            "reviews" => []
        ]);
        exit;
    }

    // 2. Fetch Specialists for this clinic
    $specStmt = $connect->prepare("
        SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, s.experience_years, u.email
        FROM specialist s
        LEFT JOIN users u ON s.specialist_id = u.user_id
        WHERE s.clinic_id = ?
    ");
    $specStmt->execute([$clinic_id]);
    $specialists = $specStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Patients that have appointments with this clinic's specialists
    $childStmt = $connect->prepare("
        SELECT DISTINCT c.child_id, c.first_name, c.last_name, 
               u.first_name as parent_fname, u.last_name as parent_lname
        FROM child c
        JOIN users u ON c.parent_id = u.user_id
        JOIN appointment a ON a.parent_id = c.parent_id
        JOIN specialist s ON a.specialist_id = s.specialist_id
        WHERE s.clinic_id = ?
        LIMIT 100
    ");
    $childStmt->execute([$clinic_id]);
    $patients = $childStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback removed to ensure only clinic's patients are shown

    // 4. Fetch Stats (Appointments & Revenue)
    $statsStmt = $connect->prepare("
        SELECT 
            COUNT(a.appointment_id) as total_appointments,
            SUM(CASE WHEN DATE(a.scheduled_at) = CURDATE() THEN 1 ELSE 0 END) as today_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN a.status = 'scheduled' THEN 1 ELSE 0 END) as pending_appointments
        FROM appointment a
        JOIN specialist s ON a.specialist_id = s.specialist_id
        WHERE s.clinic_id = ?
    ");
    $statsStmt->execute([$clinic_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate revenue (mock: $50 per completed appointment)
    $revenue = ($stats['completed_appointments'] ?: 0) * 50;

    // 5. Fetch Reviews/Feedback from parents about this clinic's specialists
    $reviews = [];
    try {
        $reviewStmt = $connect->prepare("
            SELECT f.feedback_id, f.content, f.rating, f.submitted_at,
                   u.first_name as parent_fname, u.last_name as parent_lname,
                   s.first_name as spec_fname, s.last_name as spec_lname
            FROM feedback f
            JOIN users u ON f.parent_id = u.user_id
            JOIN specialist s ON f.specialist_id = s.specialist_id
            WHERE s.clinic_id = ?
            ORDER BY f.submitted_at DESC
            LIMIT 20
        ");
        $reviewStmt->execute([$clinic_id]);
        $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // feedback table might not have all expected columns
        $reviews = [];
    }

    // Calculate review stats
    $reviewCount = count($reviews);
    $avgReviewRating = 0;
    $positiveCount = 0;
    if ($reviewCount > 0) {
        $totalRating = 0;
        foreach ($reviews as $r) {
            $totalRating += (int)$r['rating'];
            if ((int)$r['rating'] >= 4) $positiveCount++;
        }
        $avgReviewRating = round($totalRating / $reviewCount, 1);
    }

    echo json_encode([
        "success" => true,
        "clinic" => $clinic,
        "specialists" => $specialists,
        "patients" => $patients,
        "stats" => [
            "total_appointments" => (int)($stats['total_appointments'] ?? 0),
            "today_appointments" => (int)($stats['today_appointments'] ?? 0),
            "completed_appointments" => (int)($stats['completed_appointments'] ?? 0),
            "pending_appointments" => (int)($stats['pending_appointments'] ?? 0),
            "revenue" => $revenue,
            "avg_rating" => $clinic['rating'] ? (float)$clinic['rating'] : ($avgReviewRating > 0 ? $avgReviewRating : 0)
        ],
        "reviews" => $reviews,
        "review_stats" => [
            "count" => $reviewCount,
            "avg_rating" => $avgReviewRating,
            "positive_pct" => $reviewCount > 0 ? round(($positiveCount / $reviewCount) * 100) : 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

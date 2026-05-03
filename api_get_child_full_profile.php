<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$childId = $_GET['child_id'] ?? null;
if (!$childId) {
    http_response_code(400);
    echo json_encode(['error' => 'child_id is required']);
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['id'];

try {
    // 1. Basic Data + Parent Info
    $childStmt = $connect->prepare("
        SELECT c.child_id, c.first_name, c.last_name, c.birth_day, c.birth_month, c.birth_year, c.gender, c.parent_id,
               u.first_name AS parent_first_name, u.last_name AS parent_last_name, u.phone AS parent_phone
        FROM child c
        JOIN users u ON c.parent_id = u.user_id
        WHERE c.child_id = :child_id
    ");
    $childStmt->execute([':child_id' => $childId]);
    $child = $childStmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        http_response_code(404);
        echo json_encode(['error' => 'Child not found']);
        exit();
    }

    // Compute age in months
    if ($child['birth_year'] && $child['birth_month']) {
        $birthDate = new DateTime($child['birth_year'] . '-' . str_pad($child['birth_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($child['birth_day'] ?? 1, 2, '0', STR_PAD_LEFT));
        $now = new DateTime();
        $diff = $now->diff($birthDate);
        $child['age_months'] = ($diff->y * 12) + $diff->m;
    } else {
        $child['age_months'] = null;
    }

    // Security Check: If parent, must own the child
    if ($role === 'parent' && $child['parent_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }

    // 2. Latest Growth Record
    $growthStmt = $connect->prepare("
        SELECT height, weight, head_circumference, recorded_at 
        FROM growth_record 
        WHERE child_id = :child_id 
        ORDER BY recorded_at DESC LIMIT 1
    ");
    $growthStmt->execute([':child_id' => $childId]);
    $growth = $growthStmt->fetch(PDO::FETCH_ASSOC);

    // 3. Speech Analysis (latest)
    $speechStmt = $connect->prepare("
        SELECT sa.transcript, sa.vocabulary_score, sa.clarify_score, sa.analyzed_at
        FROM speech_analysis sa
        JOIN voice_sample vs ON sa.sample_id = vs.sample_id
        WHERE vs.child_id = :child_id
        ORDER BY sa.analyzed_at DESC LIMIT 5
    ");
    $speechStmt->execute([':child_id' => $childId]);
    $speech = $speechStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Motor/Behavior Data
    $behaviorStmt = $connect->prepare("
        SELECT b.behavior_type, b.behavior_details, cb.frequency, cb.severity, cb.recorded_at, bc.category_name
        FROM child_exhibited_behavior cb
        JOIN behavior b ON cb.behavior_id = b.behavior_id
        JOIN behavior_category bc ON b.category_id = bc.category_id
        WHERE cb.child_id = :child_id
        ORDER BY cb.recorded_at DESC LIMIT 20
    ");
    $behaviorStmt->execute([':child_id' => $childId]);
    $behaviors = $behaviorStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Doctor Notes
    // If parent, only show 'shared'. If doctor/specialist, show all (private and shared).
    $notesQuery = "
        SELECT dr.doctor_report_id, dr.doctor_notes, dr.recommendations, dr.visibility, dr.report_date,
               u.first_name as doctor_first_name, u.last_name as doctor_last_name
        FROM doctor_report dr
        JOIN users u ON dr.specialist_id = u.user_id
        WHERE dr.child_id = :child_id
    ";
    
    if ($role === 'parent') {
        $notesQuery .= " AND dr.visibility = 'shared'";
    }
    
    $notesQuery .= " ORDER BY dr.report_date DESC, dr.created_at DESC";
    
    $notesStmt = $connect->prepare($notesQuery);
    $notesStmt->execute([':child_id' => $childId]);
    $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'profile' => [
            'basic' => $child,
            'latest_growth' => $growth,
            'speech_history' => $speech,
            'behaviors' => $behaviors,
            'notes' => $notes
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

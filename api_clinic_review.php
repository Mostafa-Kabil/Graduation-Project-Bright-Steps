<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'submit') {
    $input = json_decode(file_get_contents('php://input'), true);
    $clinicId = $input['clinic_id'] ?? null;
    $appointmentId = $input['appointment_id'] ?? null;
    $rating = $input['rating'] ?? null;
    $comment = $input['comment'] ?? '';

    if (!$clinicId || !$appointmentId || !$rating) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Rating must be between 1 and 5']);
        exit();
    }

    // Verify appointment belongs to parent and is completed
    $stmt = $connect->prepare("SELECT status, type FROM appointment WHERE appointment_id = ? AND parent_id = ?");
    $stmt->execute([$appointmentId, $parentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['status'] !== 'Completed' || $row['type'] !== 'onsite') {
        http_response_code(400);
        echo json_encode(['error' => 'Can only review completed onsite appointments']);
        exit();
    }

    try {
        $connect->beginTransaction();
        
        $stmt = $connect->prepare("INSERT INTO clinic_reviews (clinic_id, parent_id, appointment_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$clinicId, $parentId, $appointmentId, $rating, $comment]);
        
        // Recalculate clinic rating
        $stmtCalc = $connect->prepare("SELECT AVG(rating) as avg_rating FROM clinic_reviews WHERE clinic_id = ?");
        $stmtCalc->execute([$clinicId]);
        $avg = $stmtCalc->fetchColumn();
        
        $stmtUpdate = $connect->prepare("UPDATE clinic SET rating = ? WHERE clinic_id = ?");
        $stmtUpdate->execute([$avg, $clinicId]);
        
        $connect->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $connect->rollBack();
        if (strpos($e->getMessage(), 'uq_clinic_review') !== false) {
            echo json_encode(['error' => 'You have already reviewed this appointment']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
    }
} elseif ($action === 'list') {
    $clinicId = $_GET['clinic_id'] ?? null;
    if (!$clinicId) {
        http_response_code(400);
        echo json_encode(['error' => 'clinic_id is required']);
        exit();
    }
    
    $stmt = $connect->prepare("
        SELECT r.rating, r.comment, r.created_at, p.first_name, p.last_name 
        FROM clinic_reviews r 
        JOIN parent p ON r.parent_id = p.parent_id 
        WHERE r.clinic_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$clinicId]);
    echo json_encode(['success' => true, 'reviews' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?>

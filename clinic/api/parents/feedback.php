<?php
/**
 * Bright Steps Clinic API — Feedback Handler
 * Handles parent reviews/ratings for specialists.
 */

// Basic session and connection are usually handled by the router or middleware
// But we'll include them if this is called directly or as a safeguard.
if (!isset($connect)) {
    require_once __DIR__ . '/../../connection.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Authentication Check: Only parents can submit feedback
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized Access. Parent session required.']);
    exit;
}

$parentId = $_SESSION['id'];

try {
    if ($method === 'POST' && $action === 'submit') {
        // Read input
        $input = json_decode(file_get_contents('php://input'), true);
        
        $specialistId = intval($input['specialist_id'] ?? 0);
        $rating = intval($input['rating'] ?? 0);
        $content = trim($input['content'] ?? $input['comment'] ?? '');
        $appointmentId = intval($input['appointment_id'] ?? 0);

        // Validation
        if (!$specialistId || !$rating) {
            echo json_encode(['success' => false, 'error' => 'Missing specialist_id or rating (1-5)']);
            exit;
        }

        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
            exit;
        }

        // Optional: Check if the specialist belongs to a valid clinic
        $checkSpec = $connect->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = ?");
        $checkSpec->execute([$specialistId]);
        if (!$checkSpec->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Invalid specialist selected.']);
            exit;
        }

        // Insert into specialist_reviews table
        $stmt = $connect->prepare("
            INSERT INTO specialist_reviews (parent_id, specialist_id, rating, comment, created_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $success = $stmt->execute([$parentId, $specialistId, $rating, $content]);

        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Feedback submitted successfully! Your rating helps the clinic improve.',
                'feedback_id' => $connect->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save feedback to database.']);
        }
    } 
    else if ($method === 'GET' && $action === 'my_reviews') {
        // Fetch reviews submitted by this parent
        $stmt = $connect->prepare("
            SELECT f.review_id as feedback_id, f.parent_id, f.specialist_id, f.comment as content, f.rating, f.created_at as submitted_at, s.first_name as spec_fname, s.last_name as spec_lname 
            FROM specialist_reviews f
            JOIN specialist s ON f.specialist_id = s.specialist_id
            WHERE f.parent_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$parentId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'reviews' => $reviews]);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action or method.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'API Error: ' . $e->getMessage()]);
}

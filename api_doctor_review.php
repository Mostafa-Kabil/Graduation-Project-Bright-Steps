<?php
/**
 * api_doctor_review.php
 * Handles submission and retrieval of specialist (doctor) reviews by parents.
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// Ensure parent is authenticated
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // ------------------------------------------------------------
    // Submit a new review for a specialist (doctor)
    // ------------------------------------------------------------
    case 'submit':
        $input = json_decode(file_get_contents('php://input'), true);
        $specialistId = $input['specialist_id'] ?? null;
        $rating = $input['rating'] ?? null;
        $comment = $input['comment'] ?? '';
        $appointmentId = $input['appointment_id'] ?? null;

        // Basic validation
        if (!$specialistId || !$rating || !$appointmentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields (specialist_id, rating, appointment_id)']);
            exit();
        }
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            exit();
        }

        // Verify that the appointment belongs to this parent and is completed
        $stmt = $connect->prepare("SELECT status FROM appointment WHERE appointment_id = ? AND parent_id = ?");
        $stmt->execute([$appointmentId, $parentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Appointment not found for this parent']);
            exit();
        }
        if (strtolower($row['status']) !== 'completed') {
            http_response_code(400);
            echo json_encode(['error' => 'Can only review completed appointments']);
            exit();
        }

        try {
            $connect->beginTransaction();
            $stmt = $connect->prepare(
                "INSERT INTO specialist_reviews (parent_id, specialist_id, rating, comment) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$parentId, $specialistId, $rating, $comment]);
            $connect->commit();
            echo json_encode(['success' => true, 'message' => 'Review submitted']);
        } catch (Exception $e) {
            $connect->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit review: ' . $e->getMessage()]);
        }
        break;

    // ------------------------------------------------------------
    // Retrieve reviews for a specific specialist (doctor)
    // ------------------------------------------------------------
    case 'list':
        $specialistId = $_GET['specialist_id'] ?? null;
        if (!$specialistId) {
            http_response_code(400);
            echo json_encode(['error' => 'specialist_id is required']);
            exit();
        }
        $tableCheck = $connect->query("SHOW TABLES LIKE 'specialist_reviews'");
        if ($tableCheck->rowCount() == 0) {
            echo json_encode(['specialist_id' => $specialistId, 'reviews' => []]);
            exit();
        }

        $stmt = $connect->prepare(
            "SELECT sr.rating, sr.comment, sr.created_at, p.first_name, p.last_name " .
            "FROM specialist_reviews sr " .
            "JOIN parent p ON sr.parent_id = p.parent_id " .
            "WHERE sr.specialist_id = ? ORDER BY sr.created_at DESC"
        );
        $stmt->execute([$specialistId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['specialist_id' => $specialistId, 'reviews' => $reviews]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: submit or list']);
        break;
}
?>

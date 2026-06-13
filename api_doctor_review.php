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
        if (!$appointmentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field (appointment_id)']);
            exit();
        }

        $hasSpecialistRating = ($specialistId && $rating >= 1 && $rating <= 5);
        $hasClinicRating = (isset($input['clinic_rating']) && $input['clinic_rating'] >= 1 && $input['clinic_rating'] <= 5);

        if (!$hasSpecialistRating && !$hasClinicRating) {
            http_response_code(400);
            echo json_encode(['error' => 'Please provide a rating for either the specialist or the clinic.']);
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

        // Check if review already exists for this appointment
        $stmtCheck = $connect->prepare("
            SELECT 1 FROM specialist_reviews WHERE appointment_id = ? AND parent_id = ?
            UNION 
            SELECT 1 FROM clinic_reviews WHERE appointment_id = ? AND parent_id = ?
        ");
        $stmtCheck->execute([$appointmentId, $parentId, $appointmentId, $parentId]);
        if ($stmtCheck->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'You have already submitted a review for this appointment.']);
            exit();
        }

        try {
            $connect->beginTransaction();
            
            if ($hasSpecialistRating) {
                $stmt = $connect->prepare(
                    "INSERT INTO specialist_reviews (parent_id, specialist_id, appointment_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$parentId, $specialistId, $appointmentId, $rating, $comment]);
            }

            if (isset($input['clinic_rating']) && $input['clinic_rating'] >= 1 && $input['clinic_rating'] <= 5) {
                $clinicRating = $input['clinic_rating'];
                $clinicComment = $input['clinic_comment'] ?? '';
                $clinicId = $input['clinic_id'];
                $stmt2 = $connect->prepare("INSERT INTO clinic_reviews (parent_id, clinic_id, appointment_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt2->execute([$parentId, $clinicId, $appointmentId, $clinicRating, $clinicComment]);
            }

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
            "SELECT sr.rating, sr.comment, sr.created_at, u.first_name, u.last_name " .
            "FROM specialist_reviews sr " .
            "JOIN users u ON sr.parent_id = u.user_id " .
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

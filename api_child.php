<?php
/**
 * Bright Steps – Child Profile API
 * AJAX endpoint for adding/editing child profiles from the dashboard modal.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

switch ($action) {

    case 'add':
    case 'edit':
        $childId = !empty($input['child_id']) ? (int)$input['child_id'] : null;
        $fname = trim($input['first_name'] ?? '');
        
        // Fetch parent first name to use for the child (father's name)
        $stmtP = $connect->prepare("SELECT first_name FROM users WHERE user_id = ?");
        $stmtP->execute([$parentId]);
        $parentRow = $stmtP->fetch(PDO::FETCH_ASSOC);
        $lname = $parentRow ? $parentRow['first_name'] : '';

        $gender = trim($input['gender'] ?? 'male');
        $birthDate = trim($input['birth_date'] ?? '');
        $weight = !empty($input['weight']) ? (float)$input['weight'] : null;
        $height = !empty($input['height']) ? (float)$input['height'] : null;
        $headCirc = !empty($input['head_circumference']) ? (float)$input['head_circumference'] : null;

        if ($fname === '' || $lname === '' || $birthDate === '') {
            http_response_code(400);
            echo json_encode(['error' => 'First name, last name, and date of birth are required.']);
            exit;
        }

        $parts = explode('-', $birthDate);
        if (count($parts) !== 3) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
            exit;
        }
        $birthYear = (int)$parts[0];
        $birthMonth = (int)$parts[1];
        $birthDay = (int)$parts[2];

        try {
            $connect->beginTransaction();

            if ($childId) {
                // Verify ownership then update
                $stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
                $stmt->execute([$childId, $parentId]);
                if (!$stmt->fetch()) {
                    $connect->rollBack();
                    echo json_encode(['error' => 'Child not found or access denied.']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE child SET first_name=?, last_name=?, gender=?, birth_day=?, birth_month=?, birth_year=? WHERE child_id=? AND parent_id=?");
                $stmt->execute([$fname, $lname, $gender, $birthDay, $birthMonth, $birthYear, $childId, $parentId]);
            } else {
                // Insert new child
                $ssn = 'BS-' . strtoupper(bin2hex(random_bytes(5)));
                $stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$ssn, $parentId, $fname, $lname, $birthDay, $birthMonth, $birthYear, $gender]);
                $childId = (int)$connect->lastInsertId();

                // Create points wallet
                $stmt = $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (?, 0)");
                $stmt->execute([$childId]);
            }

            // Log growth if provided
            $pointsEarned = 0;
            if ($weight !== null || $height !== null || $headCirc !== null) {
                $stmt = $connect->prepare("INSERT INTO growth_record (child_id, height, weight, head_circumference) VALUES (?,?,?,?)");
                $stmt->execute([$childId, $height, $weight, $headCirc]);
                $pointsEarned = 25;
                $stmt = $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE child_id = ?");
                $stmt->execute([$pointsEarned, $childId]);
            }

            $connect->commit();
            echo json_encode([
                'success' => true,
                'child_id' => $childId,
                'points_earned' => $pointsEarned,
                'message' => ($action === 'add' ? 'Child added' : 'Profile updated') . ($pointsEarned ? " — +{$pointsEarned} points earned!" : '')
            ]);
        } catch (Exception $e) {
            $connect->rollBack();
            echo json_encode(['error' => 'Save failed: ' . $e->getMessage()]);
        }
        break;

    case 'list':
        try {
            $stmt = $connect->prepare("SELECT * FROM child WHERE parent_id = ? ORDER BY child_id ASC");
            $stmt->execute([$parentId]);
            $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'children' => $children]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action. Use: add, edit, list']);
        break;
}

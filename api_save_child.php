<?php
session_start();
include "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$parentId = $_SESSION['id'];
$childId = !empty($_POST['child_id']) ? (int) $_POST['child_id'] : null;
$fname = trim($_POST['first_name'] ?? '');
$lname = trim($_POST['last_name'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$ssn = trim($_POST['ssn'] ?? '');
$birthDate = trim($_POST['birth_date'] ?? ''); // YYYY-MM-DD format

// Growth fields (optional)
$weight = !empty($_POST['weight']) ? (float) $_POST['weight'] : null;
$height = !empty($_POST['height']) ? (float) $_POST['height'] : null;
$headCirc = !empty($_POST['head_circumference']) ? (float) $_POST['head_circumference'] : null;

$errors = [];
if ($fname === '')
    $errors[] = 'First name is required.';
if ($lname === '')
    $errors[] = 'Last name is required.';
if ($birthDate === '') {
    $errors[] = 'Date of birth is required.';
} else {
    $parts = explode('-', $birthDate);
    if (count($parts) !== 3) {
        $errors[] = 'Invalid date format. Use YYYY-MM-DD.';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['errors' => $errors]);
    exit();
}

$birthYear = (int) $parts[0];
$birthMonth = (int) $parts[1];
$birthDay = (int) $parts[2];

try {
    $connect->beginTransaction();

    if ($childId) {
        // UPDATE existing child — verify ownership
        $stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = :cid AND parent_id = :pid");
        $stmt->execute(['cid' => $childId, 'pid' => $parentId]);
        if ($stmt->rowCount() === 0) {
            $connect->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'Child not found or access denied.']);
            exit();
        }

        $stmt = $connect->prepare("UPDATE child SET first_name = :fname, last_name = :lname, 
                gender = :gender, birth_day = :bd, birth_month = :bm, birth_year = :by
                WHERE child_id = :cid AND parent_id = :pid");
        $stmt->execute([
            'fname' => $fname,
            'lname' => $lname,
            'gender' => $gender,
            'bd' => $birthDay,
            'bm' => $birthMonth,
            'by' => $birthYear,
            'cid' => $childId,
            'pid' => $parentId
        ]);
    } else {
        // Failsafe: Ensure parent record exists to prevent FK constraints failure (e.g. for manually created users)
        $ensureParent = $connect->prepare("INSERT IGNORE INTO parent (parent_id, number_of_children) VALUES (:pid, 0)");
        $ensureParent->execute(['pid' => $parentId]);

        // INSERT new child
        if ($ssn === '') {
            $connect->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'SSN is required.']);
            exit();
        }

        $stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender)
                VALUES (:ssn, :pid, :fname, :lname, :bd, :bm, :by, :gender)");
        $stmt->execute([
            'ssn' => $ssn,
            'pid' => $parentId,
            'fname' => $fname,
            'lname' => $lname,
            'bd' => $birthDay,
            'bm' => $birthMonth,
            'by' => $birthYear,
            'gender' => $gender
        ]);
        $childId = (int) $connect->lastInsertId();

        // Create a points wallet for the new child
        $stmt = $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (:cid, 0)");
        $stmt->execute(['cid' => $childId]);
    }

    // Insert growth record if any measurement is provided
    if ($weight !== null || $height !== null || $headCirc !== null) {
        $stmt = $connect->prepare("INSERT INTO growth_record (child_id, height, weight, head_circumference)
                VALUES (:cid, :h, :w, :hc)");
        $stmt->execute([
            'cid' => $childId,
            'h' => $height,
            'w' => $weight,
            'hc' => $headCirc
        ]);
    }

    $connect->commit();

    echo json_encode([
        'success' => true,
        'child_id' => $childId,
        'message' => $childId ? 'Child profile saved successfully.' : 'Child added successfully.'
    ]);

} catch (Exception $e) {
    $connect->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

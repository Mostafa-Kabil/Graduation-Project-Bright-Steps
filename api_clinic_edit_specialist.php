<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    echo json_encode(["success" => false, "error" => "Unauthorized access."]);
    exit;
}

$user_id = intval($_SESSION['id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
    exit;
}

$specialist_id = intval($_POST['specialist_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$experience = intval($_POST['experience'] ?? 0);

if (!$specialist_id || !$first_name || !$email || !$specialization) {
    echo json_encode(["success" => false, "error" => "Missing required fields."]);
    exit;
}

try {
    // Resolve clinic_id
    $clinic_id = null;
    $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $row = $cStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $clinic_id = $row['clinic_id'];
    } else {
        $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? LIMIT 1");
        $cStmt->execute([$user_id]);
        $row = $cStmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $clinic_id = $row['clinic_id'];
    }

    if (!$clinic_id) {
        echo json_encode(["success" => false, "error" => "Clinic not found."]);
        exit;
    }

    // Verify specialist belongs to clinic
    $checkStmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = ? AND clinic_id = ?");
    $checkStmt->execute([$specialist_id, $clinic_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(["success" => false, "error" => "Specialist not found in your clinic."]);
        exit;
    }

    // Check if email is being changed and if it's already taken by someone else
    $emailCheckStmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $emailCheckStmt->execute([$email, $specialist_id]);
    if ($emailCheckStmt->fetch()) {
        echo json_encode(["success" => false, "error" => "Email is already in use by another account."]);
        exit;
    }

    $connect->beginTransaction();

    // Update specialist table
    $specStmt = $connect->prepare("
        UPDATE specialist 
        SET first_name = ?, last_name = ?, specialization = ?, experience_years = ? 
        WHERE specialist_id = ? AND clinic_id = ?
    ");
    $specStmt->execute([$first_name, $last_name, $specialization, $experience, $specialist_id, $clinic_id]);

    // Update users table
    // We update password only if provided
    $password = $_POST['password'] ?? '';
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $userStmt = $connect->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, password = ? 
            WHERE user_id = ? AND role = 'doctor'
        ");
        $userStmt->execute([$first_name, $last_name, $email, $hashed_password, $specialist_id]);
    } else {
        $userStmt = $connect->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ? 
            WHERE user_id = ? AND role = 'doctor'
        ");
        $userStmt->execute([$first_name, $last_name, $email, $specialist_id]);
    }

    $connect->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

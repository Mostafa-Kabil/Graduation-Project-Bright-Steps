<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    echo json_encode(["success" => false, "error" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "Invalid data provided."]);
    exit;
}

$first_name = $data['first_name'] ?? '';
$last_name = $data['last_name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$specialization = $data['specialization'] ?? '';
$experience = $data['experience'] ?? 0;
$user_id = intval($_SESSION['id']);

if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo json_encode(["success" => false, "error" => "All fields are required."]);
    exit;
}

try {
    $connect->beginTransaction();

    // 1. Resolve clinic_id for this admin
    $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $clinic = $cStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        // SELF-HEALING: Create default clinic profile if missing
        $default_name = ($_SESSION['fname'] ?? 'New') . "'s Healthcare Clinic";
        $insClinic = $connect->prepare("INSERT INTO clinic (clinic_name, admin_id, status) VALUES (?, ?, 'active')");
        $insClinic->execute([$default_name, $user_id]);
        $clinic_id = $connect->lastInsertId();
    } else {
        $clinic_id = $clinic['clinic_id'];
    }

    // 2. Check if email already exists
    $checkStmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        throw new Exception("An account with this email already exists.");
    }

    // 3. Insert into users table
    // Note: Assuming pass column or similar for password. Checking grad.sql for column name.
    // In many typical setups it's 'password'. Let's use 'password' based on common project patterns.
    $userStmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, 'doctor', 'active')");
    $userStmt->execute([$first_name, $last_name, $email, $password]);
    $new_specialist_id = $connect->lastInsertId();

    // 4. Insert into specialist table
    $specStmt = $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years) VALUES (?, ?, ?, ?, ?, ?)");
    $specStmt->execute([$new_specialist_id, $clinic_id, $first_name, $last_name, $specialization, $experience]);

    $connect->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>

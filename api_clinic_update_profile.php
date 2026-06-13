<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$input = $_POST;
if (empty($input)) {
    $input = json_decode(file_get_contents('php://input'), true);
}

try {
    // Self-healing: Ensure new columns exist for onboarding
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL");
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS branches TEXT DEFAULT NULL");
    $connect->exec("ALTER TABLE clinic ADD COLUMN IF NOT EXISTS medical_specialties TEXT DEFAULT NULL");
    
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $newFileName = 'clinic_' . $_SESSION['id'] . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destPath)) {
            $profile_image = $destPath;
        }
    }

    $query = "UPDATE clinic SET clinic_name = ?, email = ?, location = ?, bio = ?, branches = ?, medical_specialties = ?";
    $params = [
        $input['clinic_name'] ?? '',
        $input['email'] ?? '',
        $input['location'] ?? '',
        $input['bio'] ?? '',
        $input['branches'] ?? '',
        $input['medical_specialties'] ?? ''
    ];

    if ($profile_image) {
        $query .= ", profile_image = ?";
        $params[] = $profile_image;
    }

    $query .= " WHERE admin_id = ? OR clinic_id = ?";
    $params[] = $_SESSION['id'];
    $params[] = $_SESSION['id'];

    $stmt = $connect->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'profile_image' => $profile_image]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$clinicId = intval($_SESSION['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinicName = trim($_POST['clinic_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $specialties = trim($_POST['specialties'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $branches = trim($_POST['branches'] ?? '');

    if (empty($clinicName)) {
        echo json_encode(['error' => 'Clinic name is required.']);
        exit();
    }

    $profileImagePath = null;
    $uploadDir = __DIR__ . '/uploads/clinics/';
    
    // Handle logo upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = 'clinic_' . $clinicId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $profileImagePath = 'uploads/clinics/' . $filename;
            }
        }
    }

    $city = trim($_POST['city'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $additionalBranches = json_decode($_POST['additional_branches'] ?? '[]', true);

    try {
        if ($profileImagePath) {
            $stmt = $connect->prepare("UPDATE clinic SET clinic_name=?, bio=?, medical_specialties=?, location=?, specialties=?, profile_image=?, is_first_login=0 WHERE clinic_id=?");
            $stmt->execute([$clinicName, $bio, $specialties, $location, $branches, $profileImagePath, $clinicId]);
        } else {
            $stmt = $connect->prepare("UPDATE clinic SET clinic_name=?, bio=?, medical_specialties=?, location=?, specialties=?, is_first_login=0 WHERE clinic_id=?");
            $stmt->execute([$clinicName, $bio, $specialties, $location, $branches, $clinicId]);
        }

        // Insert main branch
        $branchStmt = $connect->prepare("INSERT INTO clinic_branches (clinic_id, branch_name, detailed_address, city, area, street, building, is_main) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $branchStmt->execute([$clinicId, "Main Branch", $location, $city, $area, $street, $building]);

        // Insert additional branches
        if (is_array($additionalBranches)) {
            $branchStmt = $connect->prepare("INSERT INTO clinic_branches (clinic_id, branch_name, detailed_address, city, area, street, building, is_main) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            foreach ($additionalBranches as $b) {
                $bLoc = $b['building'] . ', ' . $b['street'] . ', ' . $b['area'] . ', ' . $b['city'];
                $branchStmt->execute([$clinicId, $b['name'], $bLoc, $b['city'], $b['area'], $b['street'], $b['building']]);
            }
        }

        // Update session name just in case
        $_SESSION['clinic_name'] = $clinicName;

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>

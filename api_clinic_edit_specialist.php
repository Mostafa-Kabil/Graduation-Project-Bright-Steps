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
$certification_text = trim($_POST['certification_text'] ?? '');
$location = ''; // Location is obsolete

if (!$specialist_id || !$first_name || !$specialization) {
    echo json_encode(["success" => false, "error" => "Missing required fields."]);
    exit;
}

try {
    // Process PDF upload if provided
    $certification_pdf = null;
    if (isset($_FILES['certification_pdf']) && $_FILES['certification_pdf']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['certification_pdf']['tmp_name'];
        $fileName = $_FILES['certification_pdf']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        if ($fileExtension === 'pdf') {
            $uploadFileDir = 'uploads/certifications/';
            if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0777, true);
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $certification_pdf = $dest_path;
            }
        }
    }
    
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

    $connect->beginTransaction();

    // Update specialist table
    $updateFields = "first_name = ?, last_name = ?, specialization = ?, experience_years = ?, certification_text = ?";
    $params = [$first_name, $last_name, $specialization, $experience, $certification_text];
    
    if ($certification_pdf) {
        $updateFields .= ", certification_pdf = ?";
        $params[] = $certification_pdf;
    }
    
    $params[] = $specialist_id;
    $params[] = $clinic_id;
    
    $specStmt = $connect->prepare("UPDATE specialist SET $updateFields WHERE specialist_id = ? AND clinic_id = ?");
    $specStmt->execute($params);

    // Update users table (first_name and last_name only)
    $userStmt = $connect->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ? 
        WHERE user_id = ? AND role = 'doctor'
    ");
    $userStmt->execute([$first_name, $last_name, $specialist_id]);

    $connect->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

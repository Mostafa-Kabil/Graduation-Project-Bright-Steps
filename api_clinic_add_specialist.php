<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    echo json_encode(["success" => false, "error" => "Unauthorized access."]);
    exit;
}

// Check & create columns in specialist table dynamically (self-healing migration)
try {
    $connect->exec("ALTER TABLE specialist ADD COLUMN certification_text TEXT NULL");
} catch (Exception $e) {}
try {
    $connect->exec("ALTER TABLE specialist ADD COLUMN certification_pdf VARCHAR(255) NULL");
} catch (Exception $e) {}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data)) {
    $data = $_POST;
}

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
$location = ''; // Location is obsolete
$user_id = intval($_SESSION['id']);

$certification_text = $data['certification_text'] ?? null;
$certification_pdf = null;

if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($certification_text)) {
    echo json_encode(["success" => false, "error" => "All fields including certification text are required."]);
    exit;
}

if (!isset($_FILES['certification_pdf']) || $_FILES['certification_pdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "Certification PDF document is required."]);
    exit;
}

try {
    // Process PDF upload
    if (true) {
        $fileTmpPath = $_FILES['certification_pdf']['tmp_name'];
        $fileName = $_FILES['certification_pdf']['name'];
        $fileSize = $_FILES['certification_pdf']['size'];
        $fileType = $_FILES['certification_pdf']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        if ($fileExtension === 'pdf') {
            $uploadFileDir = 'uploads/certifications/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $certification_pdf = $dest_path;
            } else {
                throw new Exception("Failed to save uploaded PDF file.");
            }
        } else {
            throw new Exception("Only PDF files are allowed for the certification document.");
        }
    }

    $connect->beginTransaction();

    // 1. Resolve clinic_id for this session
    // Try admin_id first (original behavior)
    $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $clinic = $cStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        // Try clinic_id next (if session ID is clinic_id)
        $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_id = ? LIMIT 1");
        $cStmt->execute([$user_id]);
        $clinic = $cStmt->fetch(PDO::FETCH_ASSOC);
    }
    
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
        echo json_encode(["success" => false, "error" => "This email is already registered. Please use a different one."]);
        exit;
    }

    // 3. Insert into users table
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $userStmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status, is_first_login) VALUES (?, ?, ?, ?, 'doctor', 'active', 1)");
    $userStmt->execute([$first_name, $last_name, $email, $hashed_password]);
    $new_specialist_id = $connect->lastInsertId();

    $branch_id = !empty($data['branch_id']) ? intval($data['branch_id']) : null;

    // 4. Insert into specialist table
    $specStmt = $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years, certification_text, certification_pdf, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $specStmt->execute([$new_specialist_id, $clinic_id, $first_name, $last_name, $specialization, $experience, $certification_text, $certification_pdf, $branch_id]);

    $connect->commit();
    echo json_encode(["success" => true, "email" => $email]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>

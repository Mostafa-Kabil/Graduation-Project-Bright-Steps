<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$childId = $_POST['child_id'] ?? '';
$specialistId = $_POST['specialist_id'] ?? '';
$type = $_POST['type'] ?? 'onsite';
$scheduledAt = $_POST['scheduled_at'] ?? '';
$comment = trim($_POST['comment'] ?? 'Booked by Clinic');

if (!$childId || !$specialistId || !$scheduledAt) {
    echo json_encode(['error' => 'Patient, Specialist and Date/Time are required.']);
    exit();
}

if (strtotime($scheduledAt) < time()) {
    echo json_encode(['error' => 'Appointment date and time cannot be in the past.']);
    exit();
}

// 1. Prevent double booking for the same specialist at the same time
$scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));
$checkStmt = $connect->prepare("SELECT COUNT(*) FROM appointment WHERE specialist_id = ? AND scheduled_at = ? AND status NOT IN ('cancelled', 'Rejected')");
$checkStmt->execute([$specialistId, $scheduledDateTime]);
if ($checkStmt->fetchColumn() > 0) {
    echo json_encode(['error' => 'This time slot is already booked for this specialist. Please choose another time.']);
    exit();
}

// 2. Validate Certification File Upload (Mandatory / Must)
if (!isset($_FILES['certification_pdf']) || $_FILES['certification_pdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Certification PDF document is required. Please upload a PDF certification.']);
    exit();
}

$fileTmpPath = $_FILES['certification_pdf']['tmp_name'];
$fileName = $_FILES['certification_pdf']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'pdf') {
    echo json_encode(['error' => 'Only PDF files are allowed for the certification document.']);
    exit();
}

$uploadFileDir = 'uploads/appointment_certifications/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$newFileName = md5(time() . $fileName) . '.pdf';
$certificationPath = $uploadFileDir . $newFileName;

if (!move_uploaded_file($fileTmpPath, $certificationPath)) {
    echo json_encode(['error' => 'Failed to save the uploaded certification file.']);
    exit();
}

try {
    // Resolve parent_id from child_id
    $pStmt = $connect->prepare("SELECT parent_id FROM child WHERE child_id = ?");
    $pStmt->execute([$childId]);
    $parentId = $pStmt->fetchColumn();

    if (!$parentId) {
        throw new Exception("Patient not found.");
    }

    $connect->beginTransaction();

    // 1. Create Payment Record (Clinic booking usually pending or prepaid)
    $amount = 50.00;
    $stmt = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status) VALUES (?, ?, 'Clinic-side', 'Pending')");
    $stmt->execute([$amount, $amount]);
    $paymentId = $connect->lastInsertId();

    // 2. Create Appointment
    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, comment, scheduled_at, certification_path) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?, ?)");
    $stmt->execute([$parentId, $childId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime, $certificationPath]);

    // 3. Create Notification for the parent
    $title = "New Appointment Scheduled by Clinic";
    $message = "The clinic has scheduled an appointment for your child on " . date('M j, Y g:i A', strtotime($scheduledDateTime)) . ".";
    $stmtN = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $stmtN->execute([$parentId, $title, $message]);

    $connect->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    
    // SELF-HEALING: If column child_id is missing, add it and retry once
    if (strpos($e->getMessage(), "Unknown column 'child_id'") !== false) {
        try {
            $connect->exec("ALTER TABLE appointment ADD COLUMN child_id INT(11) NULL AFTER parent_id");
            
            // Retry the insert
            $connect->beginTransaction();
            $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, comment, scheduled_at, certification_path) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?, ?)");
            $stmt->execute([$parentId, $childId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime, $certificationPath]);
            $connect->commit();
            
            echo json_encode(['success' => true, 'info' => 'Database self-healed and appointment booked.']);
            exit();
        } catch (Exception $retryError) {
            http_response_code(500);
            echo json_encode(['error' => "Self-healing failed: " . $retryError->getMessage()]);
            exit();
        }
    }

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

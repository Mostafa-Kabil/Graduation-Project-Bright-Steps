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
    $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));

    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, comment, scheduled_at) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?)");
    $stmt->execute([$parentId, $childId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);

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
            $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, comment, scheduled_at) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?)");
            $stmt->execute([$parentId, $childId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);
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

<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$parentId = $_SESSION['id'];
$specialistId = $_POST['specialist_id'] ?? '';
$type = $_POST['type'] ?? 'onsite';
$scheduledAt = $_POST['scheduled_at'] ?? '';
$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$comment = trim($_POST['comment'] ?? '');

if (!$specialistId || !$scheduledAt) {
    echo json_encode(['error' => 'Specialist and Date/Time are required.']);
    exit();
}

try {
    $connect->beginTransaction();

    // 1. Create Payment Record First
    // Standard appointment price is $50. No discount for now mapped.
    $amountPre = 50.00;
    $amountPost = 50.00;
    $status = ($paymentMethod === 'Credit Card') ? 'Paid' : 'Pending';

    $stmt = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$amountPre, $amountPost, $paymentMethod, $status]);
    $paymentId = $connect->lastInsertId();

    // 2. Create Appointment
    // Ensure format matches DATETIME (YYYY-MM-DD HH:MM:SS)
    $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));

    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, payment_id, specialist_id, status, type, comment, scheduled_at) VALUES (?, ?, ?, 'Scheduled', ?, ?, ?)");
    $stmt->execute([$parentId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);

    $connect->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: occurred while booking.']);
}
?>
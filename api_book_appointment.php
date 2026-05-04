<?php
/**
 * Bright Steps – Appointment Booking API (Enhanced)
 * Supports: standard booking, token-based free consultation, pre-consultation intake.
 */
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
$tokenId = trim($_POST['token_id'] ?? $_POST['token_code'] ?? '');
$childId = $_POST['child_id'] ?? null;

// Pre-consultation intake (JSON string)
$intakeDataRaw = $_POST['intake_data'] ?? '';
$intakeData = null;
if ($intakeDataRaw) {
    $intakeData = json_decode($intakeDataRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $intakeData = null;
    }
}

if (!$specialistId || !$scheduledAt) {
    echo json_encode(['error' => 'Specialist and Date/Time are required.']);
    exit();
}

// Disable cash payment for online appointments
if ($type === 'online' && $paymentMethod === 'Cash') {
    echo json_encode(['error' => 'Cash payment is not available for online appointments. Please select Credit Card.']);
    exit();
}

// Validate child_id if provided
if ($childId) {
    $childCheckStmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
    $childCheckStmt->execute([$childId, $parentId]);
    if (!$childCheckStmt->fetch()) {
        echo json_encode(['error' => 'Invalid child selected.']);
        exit();
    }
}

try {
    $connect->beginTransaction();

    // Standard appointment price
    $amountPre = 50.00;
    $amountPost = 50.00;
    $discountRate = 0.00;
    $tokenUsed = false;

    // Token validation — apply 100% discount if valid token
    if ($tokenId) {
        $tokenStmt = $connect->prepare("SELECT token_id, parent_id, child_id, discount_amount, status FROM appointment_tokens WHERE token_id = ? AND status = 'available'");
        $tokenStmt->execute([$tokenId]);
        $token = $tokenStmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) {
            $connect->rollBack();
            echo json_encode(['error' => 'Invalid or expired token code.']);
            exit();
        }

        if ($token['parent_id'] != $parentId) {
            $connect->rollBack();
            echo json_encode(['error' => 'This token does not belong to your account.']);
            exit();
        }

        // Apply discount
        $discountRate = 100.00;
        $amountPost = 0.00;
        $tokenUsed = true;
    }

    $status = ($amountPost <= 0) ? 'Paid' : (($paymentMethod === 'Credit Card') ? 'Paid' : 'Pending');

    // 1. Create Payment Record
    $stmt = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, discount_rate, method, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$amountPre, $amountPost, $discountRate, $tokenUsed ? 'Token' : $paymentMethod, $status]);
    $paymentId = $connect->lastInsertId();

    // Mark token as used if applied
    if ($tokenUsed && isset($token)) {
        $updateToken = $connect->prepare("UPDATE appointment_tokens SET status = 'used', applied_to_appointment = ?, used_at = NOW() WHERE token_id = ?");
        $updateToken->execute([$paymentId, $token['token_id']]);
    }

    // 2. Create Appointment
    $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));

    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, payment_id, specialist_id, status, type, comment, scheduled_at) VALUES (?, ?, ?, 'Scheduled', ?, ?, ?)");
    $stmt->execute([$parentId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);
    $appointmentId = $connect->lastInsertId();

    // 3. Mark token as used
    if ($tokenUsed && isset($token)) {
        $connect->prepare("UPDATE appointment_tokens SET status = 'used', appointment_id = ?, used_at = NOW() WHERE token_id = ?")->execute([$appointmentId, $token['token_id']]);
    }

    // 4. Create Notification
    $childName = '';
    if ($childId) {
        $childStmt = $connect->prepare("SELECT first_name FROM child WHERE child_id = ?");
        $childStmt->execute([$childId]);
        $childRow = $childStmt->fetchColumn();
        $childName = $childRow ? " for " . $childRow : "";
    }
    $title = "Appointment Scheduled" . $childName;
    $message = "Your " . ($type === 'onsite' ? 'clinic visit' : 'online session') . " has been successfully scheduled for " . date('M j, Y g:i A', strtotime($scheduledDateTime)) . ".";
    if ($tokenUsed) {
        $message .= " (Free consultation token applied)";
    }
    $stmtN = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $stmtN->execute([$parentId, $title, $message]);

    $connect->commit();
    echo json_encode([
        'success' => true, 'appointment_id' => $appointmentId,
        'appointment_id' => $appointmentId,
        'token_applied' => $tokenUsed,
        'amount_paid' => $amountPost
    ]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error occurred while booking.']);
}
?>
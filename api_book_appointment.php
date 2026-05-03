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
$tokenId = $_POST['token_id'] ?? null; // Optional token to apply
$childId = $_POST['child_id'] ?? null; // Child for this appointment

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

    // 1. Calculate pricing with optional token discount
    $amountPre = 50.00;
    $discountAmount = 0.00;
    $tokenUsed = null;

    // Apply token discount if provided and valid
    if ($tokenId) {
        // Verify token belongs to this parent and is available
        $tokenStmt = $connect->prepare("
            SELECT at.token_id, at.discount_amount, at.token_type
            FROM appointment_tokens at
            WHERE at.token_id = ? AND at.parent_id = ? AND at.status = 'available'
            AND (at.expires_at IS NULL OR at.expires_at > NOW())
        ");
        $tokenStmt->execute([$tokenId, $parentId]);
        $token = $tokenStmt->fetch(PDO::FETCH_ASSOC);

        if ($token) {
            $discountAmount = (float) $token['discount_amount'];
            $tokenUsed = $token['token_id'];
        }
    }

    $amountPost = $amountPre - $discountAmount;
    $status = ($paymentMethod === 'Credit Card') ? 'Paid' : 'Pending';

    // Include token_id in payment record
    $stmt = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status, token_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$amountPre, $amountPost, $paymentMethod, $status, $tokenUsed]);
    $paymentId = $connect->lastInsertId();

    // Mark token as used if applied
    if ($tokenUsed) {
        $updateToken = $connect->prepare("UPDATE appointment_tokens SET status = 'used', applied_to_appointment = ?, used_at = NOW() WHERE token_id = ?");
        $updateToken->execute([$paymentId, $tokenUsed]);
    }

    // 2. Create Appointment
    // Ensure format matches DATETIME (YYYY-MM-DD HH:MM:SS)
    $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));

    $stmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, comment, scheduled_at) VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?)");
    $stmt->execute([$parentId, $childId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);
    $appointmentId = $connect->lastInsertId();

    // 3. Create Notification
    $childName = '';
    if ($childId) {
        $childStmt = $connect->prepare("SELECT first_name FROM child WHERE child_id = ?");
        $childStmt->execute([$childId]);
        $childRow = $childStmt->fetchColumn();
        $childName = $childRow ? " for " . $childRow : "";
    }
    $title = "Appointment Scheduled" . $childName;
    $message = "Your " . ($type === 'onsite' ? 'clinic visit' : 'online session') . " has been successfully scheduled for " . date('M j, Y g:i A', strtotime($scheduledDateTime)) . ".";
    $stmtN = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $stmtN->execute([$parentId, $title, $message]);

    $connect->commit();
    echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: occurred while booking.']);
}
?>
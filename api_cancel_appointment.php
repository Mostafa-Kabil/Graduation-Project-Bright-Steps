<?php
/**
 * api_cancel_appointment.php
 * Cancel an existing appointment.
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
$appointmentId = $_POST['appointment_id'] ?? null;
if (!$appointmentId) {
    echo json_encode(['error' => 'Missing appointment ID']);
    exit();
}

try {
    // Verify ownership and status
    $stmt = $connect->prepare("SELECT status FROM appointment WHERE appointment_id = ? AND parent_id = ?");
    $stmt->execute([$appointmentId, $parentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['error' => 'Appointment not found']);
        exit();
    }
    $currentStatus = $row['status'];
    if (in_array($currentStatus, ['Cancelled', 'Refunded'])) {
        echo json_encode(['error' => 'Appointment already cancelled']);
        exit();
    }

    $connect->beginTransaction();

    // Retrieve child_id associated with the appointment
    $stmtChild = $connect->prepare("SELECT child_id FROM appointment WHERE appointment_id = ?");
    $stmtChild->execute([$appointmentId]);
    $childId = $stmtChild->fetchColumn();

    if (!$childId) {
        // Fallback to first child of the parent
        $stmtFallback = $connect->prepare("SELECT child_id FROM child WHERE parent_id = ? LIMIT 1");
        $stmtFallback->execute([$parentId]);
        $childId = $stmtFallback->fetchColumn();
    }

    $update = $connect->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appointment_id = ?");
    $update->execute([$appointmentId]);

    $pointsDeducted = 0;
    if ($childId) {
        $pointsDeducted = 50;

        // Find or create points wallet
        $stmtWallet = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ? LIMIT 1");
        $stmtWallet->execute([$childId]);
        $wallet = $stmtWallet->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $stmtNewWallet = $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (?, 0)");
            $stmtNewWallet->execute([$childId]);
            $walletId = $connect->lastInsertId();
        } else {
            $walletId = $wallet['wallet_id'];
        }

        // Deduct points
        $stmtDeduct = $connect->prepare("UPDATE points_wallet SET total_points = GREATEST(0, total_points - ?) WHERE wallet_id = ?");
        $stmtDeduct->execute([$pointsDeducted, $walletId]);

        // Log transaction in parent_points_tracking
        $stmtTrack = $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Appointment Cancellation', ?, ?)");
        $stmtTrack->execute([$parentId, $childId, -$pointsDeducted, "Cancelled appointment #$appointmentId. 50 points deducted."]);

        // Log transaction in points_transaction
        $stmtTrans = $connect->prepare("INSERT INTO points_transaction (wallet_id, points_change, transaction_type, parent_id) VALUES (?, ?, 'withdrawal', ?)");
        $stmtTrans->execute([$walletId, -$pointsDeducted, $parentId]);
    }

    // Notification to parent
    $title = 'Appointment Cancelled';
    $message = "Your appointment (ID: $appointmentId) has been cancelled. Based on pointing rules, 50 points were deducted from your child's points balance. Please contact the clinic if you have questions.";
    $notif = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $notif->execute([$parentId, $title, $message]);

    $connect->commit();
    echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

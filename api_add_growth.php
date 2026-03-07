<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$parentId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$childId = $_POST['child_id'] ?? '';
$weight = !empty($_POST['weight']) ? (float) $_POST['weight'] : null;
$height = !empty($_POST['height']) ? (float) $_POST['height'] : null;
$headCirc = !empty($_POST['head_circumference']) ? (float) $_POST['head_circumference'] : null;

if (!$childId) {
    echo json_encode(['error' => 'Child ID is required']);
    exit();
}

if ($weight === null && $height === null && $headCirc === null) {
    echo json_encode(['error' => 'At least one measurement is required']);
    exit();
}

// Verify child belongs to parent
$stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Invalid child selected or access denied']);
    exit();
}

try {
    $connect->beginTransaction();

    // 1. Insert Growth Record
    $stmt = $connect->prepare("INSERT INTO growth_record (child_id, height, weight, head_circumference) VALUES (:cid, :h, :w, :hc)");
    $stmt->execute([
        'cid' => $childId,
        'h' => $height,
        'w' => $weight,
        'hc' => $headCirc
    ]);

    $pointsToAward = 25; // Points for adding growth measurements

    // 2. Ensure Points Wallet Exists
    $stmt = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ?");
    $stmt->execute([$childId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $stmt = $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (?, ?)");
        $stmt->execute([$childId, $pointsToAward]);
        $walletId = $connect->lastInsertId();
    } else {
        $walletId = $wallet['wallet_id'];
        $stmt = $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE wallet_id = ?");
        $stmt->execute([$pointsToAward, $walletId]);
    }

    // 3. Log Points Transaction (Try to gracefully insert if a valid admin exists)
    $stmt = $connect->prepare("SELECT admin_id FROM admin LIMIT 1");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();

    if ($adminId) {
        // Ensure reference exists for Growth Update
        $stmt = $connect->prepare("SELECT refrence_id FROM points_refrence WHERE action_name = 'Growth Update' LIMIT 1");
        $stmt->execute();
        $refId = $stmt->fetchColumn();

        if (!$refId) {
            $stmt = $connect->prepare("INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign) VALUES (?, 'Growth Update', ?, '+')");
            $stmt->execute([$adminId, $pointsToAward]);
            $refId = $connect->lastInsertId();
        }

        // Insert Transaction
        $stmt = $connect->prepare("INSERT INTO points_transaction (refrence_id, wallet_id, points_change, transaction_type) VALUES (?, ?, ?, 'deposit')");
        $stmt->execute([$refId, $walletId, $pointsToAward]);
    }

    $connect->commit();
    echo json_encode([
        'success' => true,
        'message' => "Growth recorded successfully! Your child earned $pointsToAward points.",
        'points_awarded' => $pointsToAward
    ]);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
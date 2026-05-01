<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    echo json_encode(["success" => false, "error" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$specialist_id = intval($data['specialist_id'] ?? 0);

if (!$specialist_id) {
    echo json_encode(["success" => false, "error" => "Invalid specialist ID."]);
    exit;
}

$user_id = intval($_SESSION['id']);

try {
    // Resolve clinic_id (check both clinic_id and admin_id)
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

    // Verify the specialist actually belongs to this clinic (security check)
    $checkStmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = ? AND clinic_id = ?");
    $checkStmt->execute([$specialist_id, $clinic_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(["success" => false, "error" => "Specialist not found in your clinic."]);
        exit;
    }

    $connect->beginTransaction();

    // 1. Delete dependent feedback/reviews
    $connect->prepare("DELETE FROM feedback WHERE specialist_id = ?")->execute([$specialist_id]);

    // 2. Delete dependent appointments (we must delete instead of cancel due to NOT NULL FK constraint)
    $connect->prepare("DELETE FROM appointment WHERE specialist_id = ?")->execute([$specialist_id]);

    // 3. Delete the specialist record
    $connect->prepare("DELETE FROM specialist WHERE specialist_id = ? AND clinic_id = ?")->execute([$specialist_id, $clinic_id]);

    // 4. Remove from users table 
    $connect->prepare("DELETE FROM users WHERE user_id = ? AND role = 'doctor'")->execute([$specialist_id]);

    $connect->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

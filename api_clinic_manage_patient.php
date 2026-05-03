<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$childName = $input['child_name'] ?? '';
$parentName = $input['parent_name'] ?? '';
$parentEmail = $input['parent_email'] ?? '';
$specialistId = $input['specialist_id'] ?? null;

if (!$childName || !$parentName || !$parentEmail) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled.']);
    exit();
}

try {
    $connect->beginTransaction();

    // 1. Resolve clinic_id
    $clinicStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? OR clinic_id = ? LIMIT 1");
    $clinicStmt->execute([$_SESSION['id'], $_SESSION['id']]);
    $clinicId = $clinicStmt->fetchColumn();

    if (!$clinicId) throw new Exception("Clinic profile not found.");

    // 2. Check if parent (user) exists
    $userStmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$parentEmail]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        // Create new parent user
        $nameParts = explode(' ', $parentName, 2);
        $fname = $nameParts[0];
        $lname = $nameParts[1] ?? '';
        
        $insertUser = $connect->prepare("INSERT INTO users (first_name, last_name, email, role, status) VALUES (?, ?, ?, 'parent', 'active')");
        $insertUser->execute([$fname, $lname, $parentEmail]);
        $userId = $connect->lastInsertId();
    }

    // 2.5 Ensure record exists in parent table
    $pCheck = $connect->prepare("SELECT parent_id FROM parent WHERE parent_id = ?");
    $pCheck->execute([$userId]);
    if (!$pCheck->fetch()) {
        $insertParent = $connect->prepare("INSERT INTO parent (parent_id, number_of_children) VALUES (?, 1)");
        $insertParent->execute([$userId]);
    } else {
        // Increment children count
        $updateParent = $connect->prepare("UPDATE parent SET number_of_children = number_of_children + 1 WHERE parent_id = ?");
        $updateParent->execute([$userId]);
    }

    // 3. Create child
    $childParts = explode(' ', $childName, 2);
    $cfname = $childParts[0];
    $clname = $childParts[1] ?? '';
    
    // We need an SSN for the child table (usually unique, but for clinic add we can use a generated one if not provided)
    $ssn = "CLINIC-" . time() . "-" . rand(100, 999);
    
    $insertChild = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name) VALUES (?, ?, ?, ?)");
    $insertChild->execute([$ssn, $userId, $cfname, $clname]);
    $childId = $connect->lastInsertId();

    // 4. Create a placeholder appointment to link to this clinic
    if (!$specialistId) {
        $specStmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE clinic_id = ? LIMIT 1");
        $specStmt->execute([$clinicId]);
        $specialistId = $specStmt->fetchColumn();
    }

    if ($specialistId) {
        // Create dummy payment record to satisfy foreign key
        $insertPayment = $connect->prepare("INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status) VALUES (0, 0, 'clinic_add', 'Pending')");
        $insertPayment->execute();
        $paymentId = $connect->lastInsertId();

        $insertAppt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, specialist_id, status, type, comment, scheduled_at, payment_id) VALUES (?, ?, ?, 'Pending', 'onsite', 'Added by clinic', NOW(), ?)");
        $insertAppt->execute([$userId, $childId, $specialistId, $paymentId]);
    }

    $connect->commit();
    echo json_encode(['success' => true, 'message' => 'Patient added successfully.']);

} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

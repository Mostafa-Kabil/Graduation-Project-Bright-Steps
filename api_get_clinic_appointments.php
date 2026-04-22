<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    die(json_encode(["error" => "Unauthorized"]));
}

$user_id = intval($_SESSION['id']);

try {
    // 1. Resolve clinic_id
    $cStmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE admin_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $clinic_id = $cStmt->fetchColumn();

    if (!$clinic_id) {
        die(json_encode(["error" => "Clinic profile not found"]));
    }

    // 2. Fetch Appointments
    $stmt = $connect->prepare("
        SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment,
               u.first_name as parent_fname, u.last_name as parent_lname,
               c.first_name as child_fname, c.last_name as child_lname,
               spec.first_name as specialist_fname, spec.last_name as specialist_lname
        FROM appointment a
        JOIN child c ON a.parent_id = c.parent_id
        JOIN specialist spec ON a.specialist_id = spec.specialist_id
        JOIN users u ON a.parent_id = u.user_id
        WHERE spec.clinic_id = ?
        ORDER BY a.scheduled_at ASC
    ");
    $stmt->execute([$clinic_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

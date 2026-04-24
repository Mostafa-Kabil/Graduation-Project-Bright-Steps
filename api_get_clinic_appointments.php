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
    $clinic_id = $user_id;

    if (!$clinic_id) {
        die(json_encode(["error" => "Clinic profile not found"]));
    }

    // 2. Fetch Appointments
    $stmt = $connect->prepare("
        SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment,
               u.first_name as parent_fname, u.last_name as parent_lname,
               spec.first_name as specialist_fname, spec.last_name as specialist_lname,
               (SELECT first_name FROM child WHERE parent_id = a.parent_id LIMIT 1) as child_fname,
               (SELECT last_name FROM child WHERE parent_id = a.parent_id LIMIT 1) as child_lname
        FROM appointment a
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

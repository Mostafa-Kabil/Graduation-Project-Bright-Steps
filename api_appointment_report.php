<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Specialist writes report
    if ($_SESSION['role'] !== 'specialist') {
        http_response_code(403);
        echo json_encode(['error' => 'Only specialists can write reports']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $appointmentId = $input['appointment_id'] ?? null;
    $reportText = $input['report_text'] ?? null;
    $nextVisitDate = $input['next_visit_date'] ?? null;
    $specialistId = $_SESSION['id'];

    if (!$appointmentId || !$reportText) {
        echo json_encode(['error' => 'appointment_id and report_text are required']);
        exit();
    }

    // Verify ownership and status
    $stmt = $connect->prepare("SELECT parent_id, child_id FROM appointment WHERE appointment_id = ? AND specialist_id = ? AND status = 'Completed'");
    $stmt->execute([$appointmentId, $specialistId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'Appointment not found or not completed']);
        exit();
    }

    try {
        $connect->prepare("UPDATE appointment SET report = ?, next_visit_recommendation = ? WHERE appointment_id = ?")
                ->execute([$reportText, $nextVisitDate, $appointmentId]);

        // Notify parent
        $childName = '';
        if ($row['child_id']) {
            $childStmt = $connect->prepare("SELECT first_name FROM child WHERE child_id = ?");
            $childStmt->execute([$row['child_id']]);
            $cRow = $childStmt->fetchColumn();
            if ($cRow) $childName = " for " . $cRow;
        }

        $title = "Report Ready";
        $msg = "Your specialist has written a report" . $childName . ". Check your appointments.";
        $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'appointment', ?, ?)")
                ->execute([$row['parent_id'], $title, $msg]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }

} elseif ($method === 'GET') {
    // Parent reads report
    if ($_SESSION['role'] !== 'parent') {
        http_response_code(403);
        echo json_encode(['error' => 'Only parents can read reports']);
        exit();
    }

    $appointmentId = $_GET['appointment_id'] ?? null;
    $parentId = $_SESSION['id'];

    if (!$appointmentId) {
        echo json_encode(['error' => 'appointment_id is required']);
        exit();
    }

    try {
        $stmt = $connect->prepare("
            SELECT a.report, a.next_visit_recommendation, u.first_name, u.last_name
            FROM appointment a
            JOIN users u ON a.specialist_id = u.user_id
            WHERE a.appointment_id = ? AND a.parent_id = ?
        ");
        $stmt->execute([$appointmentId, $parentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['error' => 'Report not found']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'report' => $row['report'],
            'next_visit_recommendation' => $row['next_visit_recommendation'],
            'specialist_name' => $row['first_name'] . ' ' . $row['last_name']
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
}
?>

<?php
session_start();
header('Content-Type: application/json');
include 'connection.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'create_ticket') {
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');
        $priority = $input['priority'] ?? 'medium';

        if (empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
            exit;
        }

        try {
            // Insert ticket
            $stmt = $connect->prepare("INSERT INTO support_tickets (user_id, subject, priority, status) VALUES (?, ?, ?, 'open')");
            $stmt->execute([$userId, $subject, $priority]);
            $ticketId = $connect->lastInsertId();

            // Insert initial message
            $msgStmt = $connect->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message, is_internal) VALUES (?, ?, 'user', ?, 0)");
            $msgStmt->execute([$ticketId, $userId, $message]);

            // Try logging the activity if possible
            try {
                $userRole = $_SESSION['role'] ?? 'user';
                $userName = ($_SESSION['fname'] ?? 'User') . ' ' . ($_SESSION['lname'] ?? '');
                $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description, user_name, user_role, related_user_id) VALUES ('ticket_created', ?, ?, ?, ?)");
                $logStmt->execute(["Created support ticket #$ticketId: $subject", $userName, $userRole, $userId]);
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'ticket_id' => $ticketId, 'message' => 'Your support ticket has been submitted successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>

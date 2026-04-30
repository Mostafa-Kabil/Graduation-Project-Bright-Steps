<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit;
}
$adminId = $_SESSION['id'];

try {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'reply':
            $ticketId = $input['ticket_id'] ?? 0;
            $message = $input['message'] ?? '';
            $isInternal = $input['is_internal'] ?? 0;
            if (!$message) { echo json_encode(['error' => 'Message required']); exit; }
            $stmt = $connect->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message, is_internal) VALUES (?, ?, 'admin', ?, ?)");
            $stmt->execute([$ticketId, $adminId, $message, $isInternal]);
            // Update ticket timestamp
            $connect->prepare("UPDATE support_tickets SET updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            echo json_encode(['success' => true, 'message_id' => $connect->lastInsertId()]);
            break;

        case 'update_status':
            $ticketId = $input['ticket_id'] ?? 0;
            $status = $input['status'] ?? '';
            $stmt = $connect->prepare("UPDATE support_tickets SET status=? WHERE id=?");
            $stmt->execute([$status, $ticketId]);
            echo json_encode(['success' => true]);
            break;

        case 'assign':
            $ticketId = $input['ticket_id'] ?? 0;
            $assignTo = $input['assign_to'] ?? null;
            $stmt = $connect->prepare("UPDATE support_tickets SET assigned_to=?, status='in_progress' WHERE id=?");
            $stmt->execute([$assignTo, $ticketId]);
            echo json_encode(['success' => true]);
            break;

        case 'update_priority':
            $ticketId = $input['ticket_id'] ?? 0;
            $priority = $input['priority'] ?? 'medium';
            $stmt = $connect->prepare("UPDATE support_tickets SET priority=? WHERE id=?");
            $stmt->execute([$priority, $ticketId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            $status = $_GET['status'] ?? '';
            $where = $status ? "WHERE t.status='$status'" : '';
            $stmt = $connect->query("SELECT t.*, COALESCE(u.first_name, SUBSTRING_INDEX(t.guest_name, ' ', 1)) as first_name, COALESCE(u.last_name, SUBSTRING_INDEX(t.guest_name, ' ', -1)) as last_name, COALESCE(u.email, t.guest_email) as email, a.first_name as assigned_first, a.last_name as assigned_last FROM support_tickets t LEFT JOIN users u ON t.user_id=u.user_id LEFT JOIN users a ON t.assigned_to=a.user_id $where ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.updated_at DESC");
            echo json_encode(['success' => true, 'tickets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'view':
            $id = $_GET['id'] ?? 0;
            // Ticket details
            $stmt = $connect->prepare("SELECT t.*, COALESCE(u.first_name, SUBSTRING_INDEX(t.guest_name, ' ', 1)) as first_name, COALESCE(u.last_name, SUBSTRING_INDEX(t.guest_name, ' ', -1)) as last_name, COALESCE(u.email, t.guest_email) as email, COALESCE(u.role, 'guest') as role, u.created_at as user_joined FROM support_tickets t LEFT JOIN users u ON t.user_id=u.user_id WHERE t.id=?");
            $stmt->execute([$id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            // Messages
            $msgs = $connect->prepare("SELECT tm.*, COALESCE(u.first_name, SUBSTRING_INDEX(t.guest_name, ' ', 1)) as first_name, COALESCE(u.last_name, SUBSTRING_INDEX(t.guest_name, ' ', -1)) as last_name FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id=u.user_id LEFT JOIN support_tickets t ON tm.ticket_id = t.id WHERE tm.ticket_id=? ORDER BY tm.created_at ASC");
            $msgs->execute([$id]);
            $messages = $msgs->fetchAll(PDO::FETCH_ASSOC);
            // Previous tickets by this user
            $previousTickets = [];
            if (!empty($ticket['user_id'])) {
                $prev = $connect->prepare("SELECT id, subject, status, created_at FROM support_tickets WHERE user_id=? AND id!=? ORDER BY created_at DESC LIMIT 5");
                $prev->execute([$ticket['user_id'], $id]);
                $previousTickets = $prev->fetchAll(PDO::FETCH_ASSOC);
            } elseif (!empty($ticket['guest_email'])) {
                $prev = $connect->prepare("SELECT id, subject, status, created_at FROM support_tickets WHERE guest_email=? AND id!=? ORDER BY created_at DESC LIMIT 5");
                $prev->execute([$ticket['guest_email'], $id]);
                $previousTickets = $prev->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Available admins for assignment
            $admins = $connect->query("SELECT u.user_id, u.first_name, u.last_name FROM users u WHERE u.role='admin' AND u.status='active'")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'ticket' => $ticket, 'messages' => $messages, 'previous_tickets' => $previousTickets, 'admins' => $admins]);
            break;

        case 'analytics':
            $total = $connect->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
            $open = $connect->query("SELECT COUNT(*) FROM support_tickets WHERE status='open'")->fetchColumn();
            $resolved = $connect->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('resolved','closed')")->fetchColumn();
            $resolutionRate = $total > 0 ? round($resolved / $total * 100) : 0;
            echo json_encode(['success' => true, 'analytics' => [
                'total' => $total, 'open' => $open, 'resolved' => $resolved,
                'in_progress' => $connect->query("SELECT COUNT(*) FROM support_tickets WHERE status='in_progress'")->fetchColumn(),
                'resolution_rate' => $resolutionRate,
                'avg_response_hours' => 2.4
            ]]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

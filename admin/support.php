<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'summary';

        if ($action === 'summary') {
            // Tickets
            $stmt = $connect->query("SELECT COUNT(*) as total, 
                                            SUM(CASE WHEN status IN ('open','in_progress','waiting') THEN 1 ELSE 0 END) as active 
                                     FROM support_tickets");
            $ticketStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Moderation
            $stmt = $connect->query("SELECT COUNT(*) as total, 
                                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending 
                                     FROM flagged_content");
            $modStats = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'tickets' => ['total' => (int) $ticketStats['total'], 'active' => (int) $ticketStats['active']],
                'moderation' => ['total' => (int) $modStats['total'], 'pending' => (int) $modStats['pending']]
            ]);

        } elseif ($action === 'tickets') {
            $status = $_GET['status'] ?? 'active';
            $sql = "SELECT t.id, t.subject, t.priority, t.status, t.created_at, t.updated_at,
                           u.first_name, u.last_name, u.email, u.role
                    FROM support_tickets t
                    LEFT JOIN users u ON t.user_id = u.user_id WHERE 1=1";
            
            if ($status === 'active') {
                $sql .= " AND t.status IN ('open','in_progress','waiting')";
            } elseif ($status === 'closed') {
                $sql .= " AND t.status IN ('resolved','closed')";
            }
            $sql .= " ORDER BY t.priority DESC, t.updated_at DESC";

            $stmt = $connect->query($sql);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'tickets' => $tickets]);

        } elseif ($action === 'ticket_detail') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $connect->prepare("SELECT t.*, u.first_name, u.last_name, u.email FROM support_tickets t LEFT JOIN users u ON t.user_id = u.user_id WHERE t.id = :id");
            $stmt->execute(['id' => $id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) { echo json_encode(['success' => false, 'error' => 'Not found']); exit; }

            $stmt = $connect->prepare("SELECT m.*, u.first_name, u.last_name 
                                       FROM ticket_messages m 
                                       LEFT JOIN users u ON m.sender_id = u.user_id 
                                       WHERE m.ticket_id = :id ORDER BY m.created_at ASC");
            $stmt->execute(['id' => $id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'ticket' => $ticket, 'messages' => $messages]);

        } elseif ($action === 'moderation') {
            $status = $_GET['status'] ?? 'pending';
            $sql = "SELECT f.*, u.first_name, u.last_name, u.email, u.role 
                    FROM flagged_content f 
                    LEFT JOIN users u ON f.user_id = u.user_id";
            if ($status !== 'all') {
                $sql .= " WHERE f.status = :status";
            }
            $sql .= " ORDER BY f.created_at DESC";

            $stmt = $connect->prepare($sql);
            if ($status !== 'all') $stmt->execute(['status' => $status]);
            else $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $items]);
        }
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $action = $data['action'] ?? '';

        if ($action === 'reply_ticket') {
            $ticketId = (int)$data['ticket_id'];
            $msg = trim($data['message'] ?? '');
            if (!$msg) { echo json_encode(['error' => 'Message empty']); exit; }

            $stmt = $connect->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (:tid, :sid, 'admin', :msg)");
            $stmt->execute(['tid' => $ticketId, 'sid' => $_SESSION['id'], 'msg' => $msg]);

            $stmt = $connect->prepare("UPDATE support_tickets SET status = 'waiting', updated_at = NOW() WHERE id = :tid");
            $stmt->execute(['tid' => $ticketId]);

            echo json_encode(['success' => true]);

        } elseif ($action === 'close_ticket') {
            $ticketId = (int)$data['ticket_id'];
            $stmt = $connect->prepare("UPDATE support_tickets SET status = 'resolved', updated_at = NOW() WHERE id = :tid");
            $stmt->execute(['tid' => $ticketId]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'moderate_item') {
            $id = (int)$data['id'];
            $status = $data['status'] ?? 'approved'; // approved, removed, warned
            
            $stmt = $connect->prepare("UPDATE flagged_content SET status = :status, reviewed_by = :aid, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $status, 'aid' => $_SESSION['id'], 'id' => $id]);
            echo json_encode(['success' => true]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}

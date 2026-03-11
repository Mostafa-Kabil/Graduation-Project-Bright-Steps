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
        case 'moderate':
            $id = $input['content_id'] ?? 0;
            $modAction = $input['mod_action'] ?? '';
            $note = $input['note'] ?? '';
            if (!in_array($modAction, ['approved','removed','warned'])) { echo json_encode(['error' => 'Invalid action']); exit; }

            $stmt = $connect->prepare("UPDATE flagged_content SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            $stmt->execute([$modAction, $adminId, $id]);

            // Get target user
            $uStmt = $connect->prepare("SELECT user_id FROM flagged_content WHERE id=?");
            $uStmt->execute([$id]);
            $targetUserId = $uStmt->fetchColumn();

            // Log moderation action
            $log = $connect->prepare("INSERT INTO moderation_log (admin_id, action, target_user_id, content_id, note) VALUES (?, ?, ?, ?, ?)");
            $log->execute([$adminId, $modAction, $targetUserId, $id, $note]);

            // If ban, suspend the user
            if ($modAction === 'warned') {
                // Just log, user stays active
            }

            echo json_encode(['success' => true]);
            break;

        case 'ban_user':
            $userId = $input['user_id'] ?? 0;
            $note = $input['note'] ?? 'Banned by admin';
            $stmt = $connect->prepare("UPDATE users SET status='suspended' WHERE user_id=?");
            $stmt->execute([$userId]);
            $log = $connect->prepare("INSERT INTO moderation_log (admin_id, action, target_user_id, note) VALUES (?, 'ban', ?, ?)");
            $log->execute([$adminId, $userId, $note]);
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
            $where = $status ? "WHERE fc.status='$status'" : '';
            $stmt = $connect->query("SELECT fc.*, u.first_name, u.last_name, u.email FROM flagged_content fc LEFT JOIN users u ON fc.user_id=u.user_id $where ORDER BY fc.created_at DESC LIMIT 50");
            echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'view':
            $id = $_GET['id'] ?? 0;
            $stmt = $connect->prepare("SELECT fc.*, u.first_name, u.last_name, u.email, u.role, u.status as user_status FROM flagged_content fc LEFT JOIN users u ON fc.user_id=u.user_id WHERE fc.id=?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            // Previous violations
            $vStmt = $connect->prepare("SELECT COUNT(*) as c FROM flagged_content WHERE user_id=? AND status IN ('removed','warned')");
            $vStmt->execute([$item['user_id'] ?? 0]);
            $item['previous_violations'] = $vStmt->fetchColumn();
            echo json_encode(['success' => true, 'item' => $item]);
            break;

        case 'stats':
            $pending = $connect->query("SELECT COUNT(*) FROM flagged_content WHERE status='pending'")->fetchColumn();
            $reviewed = $connect->query("SELECT COUNT(*) FROM flagged_content WHERE status!='pending'")->fetchColumn();
            $removed = $connect->query("SELECT COUNT(*) FROM flagged_content WHERE status='removed'")->fetchColumn();
            echo json_encode(['success' => true, 'stats' => ['pending' => $pending, 'reviewed' => $reviewed, 'removed' => $removed]]);
            break;

        case 'log':
            $stmt = $connect->query("SELECT ml.*, u.first_name as admin_name, u.last_name as admin_last FROM moderation_log ml LEFT JOIN users u ON ml.admin_id=u.user_id ORDER BY ml.created_at DESC LIMIT 50");
            echo json_encode(['success' => true, 'log' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

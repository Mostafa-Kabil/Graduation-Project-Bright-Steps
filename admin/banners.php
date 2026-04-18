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
        case 'create':
            $message = $input['message'] ?? '';
            $style = $input['style'] ?? 'info';
            $link = $input['link'] ?? null;
            $target = $input['target_audience'] ?? 'all';
            $startsAt = $input['starts_at'] ?? null;
            $endsAt = $input['ends_at'] ?? null;
            if (!$message) { echo json_encode(['error' => 'Message required']); exit; }
            $stmt = $connect->prepare("INSERT INTO announcement_banners (message, style, link, target_audience, starts_at, ends_at, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$message, $style, $link, $target, $startsAt, $endsAt, $adminId]);
            echo json_encode(['success' => true, 'banner_id' => $connect->lastInsertId()]);
            break;

        case 'update':
            $id = $input['banner_id'] ?? 0;
            $message = $input['message'] ?? '';
            $style = $input['style'] ?? 'info';
            $link = $input['link'] ?? null;
            $target = $input['target_audience'] ?? 'all';
            $startsAt = $input['starts_at'] ?? null;
            $endsAt = $input['ends_at'] ?? null;
            $stmt = $connect->prepare("UPDATE announcement_banners SET message=?, style=?, link=?, target_audience=?, starts_at=?, ends_at=? WHERE id=?");
            $stmt->execute([$message, $style, $link, $target, $startsAt, $endsAt, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle':
            $id = $input['banner_id'] ?? 0;
            $stmt = $connect->prepare("UPDATE announcement_banners SET is_active = NOT is_active WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = $input['banner_id'] ?? 0;
            $stmt = $connect->prepare("DELETE FROM announcement_banners WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            $stmt = $connect->query("SELECT b.*, u.first_name, u.last_name FROM announcement_banners b LEFT JOIN users u ON b.created_by=u.user_id ORDER BY b.created_at DESC");
            echo json_encode(['success' => true, 'banners' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'view':
            $id = $_GET['id'] ?? 0;
            $stmt = $connect->prepare("SELECT b.*, u.first_name, u.last_name FROM announcement_banners b LEFT JOIN users u ON b.created_by=u.user_id WHERE b.id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'banner' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

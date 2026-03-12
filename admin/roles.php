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
        case 'add_role':
            $name = $input['name'] ?? '';
            $desc = $input['description'] ?? '';
            $perms = $input['permissions'] ?? [];
            if (!$name) { echo json_encode(['error' => 'Role name required']); exit; }
            $stmt = $connect->prepare("INSERT INTO admin_roles (name, description, permissions) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, json_encode($perms)]);
            echo json_encode(['success' => true, 'role_id' => $connect->lastInsertId()]);
            break;

        case 'update_role':
            $id = $input['role_id'] ?? 0;
            $name = $input['name'] ?? '';
            $desc = $input['description'] ?? '';
            $perms = $input['permissions'] ?? [];
            $stmt = $connect->prepare("UPDATE admin_roles SET name=?, description=?, permissions=? WHERE id=?");
            $stmt->execute([$name, $desc, json_encode($perms), $id]);
            echo json_encode(['success' => true]);
            break;

        case 'assign_role':
            $userId = $input['user_id'] ?? 0;
            $roleId = $input['role_id'] ?? 0;
            // Update admin's role_level to match role_id
            $stmt = $connect->prepare("UPDATE admin SET role_level=? WHERE admin_id=?");
            $stmt->execute([$roleId, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'revoke_access':
            $userId = $input['user_id'] ?? 0;
            $stmt = $connect->prepare("UPDATE users SET status='suspended' WHERE user_id=? AND role='admin'");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            $roles = $connect->query("SELECT * FROM admin_roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            // Get admin users
            $admins = $connect->query("SELECT u.user_id, u.first_name, u.last_name, u.email, u.status, u.created_at, a.role_level FROM users u JOIN admin a ON u.user_id=a.admin_id ORDER BY u.created_at")->fetchAll(PDO::FETCH_ASSOC);
            // Map role names
            $roleMap = [];
            foreach ($roles as $r) { $roleMap[$r['id']] = $r['name']; }
            foreach ($admins as &$a) { $a['role_name'] = $roleMap[$a['role_level']] ?? 'Unknown'; }
            echo json_encode(['success' => true, 'roles' => $roles, 'admins' => $admins]);
            break;

        case 'view':
            $id = $_GET['id'] ?? 0;
            $stmt = $connect->prepare("SELECT u.*, a.role_level FROM users u JOIN admin a ON u.user_id=a.admin_id WHERE u.user_id=?");
            $stmt->execute([$id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            // Audit trail
            $audit = $connect->query("SELECT * FROM activity_log WHERE related_user_id=$id ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'admin' => $admin, 'audit' => $audit]);
            break;

        case 'audit_log':
            $stmt = $connect->query("SELECT al.*, u.first_name, u.last_name FROM activity_log al LEFT JOIN users u ON al.related_user_id=u.user_id ORDER BY al.created_at DESC LIMIT 50");
            echo json_encode(['success' => true, 'audit' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

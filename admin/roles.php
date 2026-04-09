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

        case 'delete_role':
            $id = $input['role_id'] ?? 0;
            // Don't allow deleting the Super Admin role
            $stmt = $connect->prepare("SELECT name FROM admin_roles WHERE id = ?");
            $stmt->execute([$id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($role && strtolower($role['name']) === 'super admin') {
                echo json_encode(['success' => false, 'error' => 'Cannot delete the Super Admin role']);
                exit;
            }
            $connect->prepare("DELETE FROM admin_roles WHERE id=?")->execute([$id]);
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
            // Map role names
            $roleMap = [];
            foreach ($roles as $r) { $roleMap[$r['id']] = $r; }
            foreach ($admins as &$a) {
                $a['role_name'] = $roleMap[$a['role_level']]['name'] ?? 'Unknown';
                $a['role_permissions'] = isset($roleMap[$a['role_level']]) ? json_decode($roleMap[$a['role_level']]['permissions'], true) : [];
            }

            // Get current admin's permissions
            $myPerms = ['all']; // default
            $myRoleStmt = $connect->prepare("SELECT role_level FROM admin WHERE admin_id = ?");
            $myRoleStmt->execute([$adminId]);
            $myRole = $myRoleStmt->fetch(PDO::FETCH_ASSOC);
            if ($myRole && isset($roleMap[$myRole['role_level']])) {
                $myPerms = json_decode($roleMap[$myRole['role_level']]['permissions'], true) ?: ['all'];
            }

            echo json_encode(['success' => true, 'roles' => $roles, 'admins' => $admins, 'my_permissions' => $myPerms]);
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

        case 'get_permissions':
            $stmt = $connect->prepare("SELECT role_level FROM admin WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $myRole = $stmt->fetch(PDO::FETCH_ASSOC);
            $perms = ['all']; // default for Super Admin
            if ($myRole) {
                $roleStmt = $connect->prepare("SELECT permissions FROM admin_roles WHERE id = ?");
                $roleStmt->execute([$myRole['role_level']]);
                $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleData) {
                    $perms = json_decode($roleData['permissions'], true) ?: ['all'];
                }
            }
            echo json_encode(['success' => true, 'permissions' => $perms]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

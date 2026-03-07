<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

// Verify admin access
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $role = $_GET['role'] ?? 'all';
            $search = $_GET['search'] ?? '';

            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at FROM users u WHERE 1=1";
            $params = [];

            if ($role !== 'all' && $role !== '') {
                $sql .= " AND u.role = :role";
                $params['role'] = $role;
            }

            if ($search !== '') {
                $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search2 OR u.email LIKE :search3)";
                $params['search'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            }

            $sql .= " ORDER BY u.created_at DESC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        $action = $data['action'] ?? '';

        if ($action === 'toggle_status') {
            $userId = (int) ($data['user_id'] ?? 0);
            $newStatus = $data['status'] ?? '';

            if (!$userId || !in_array($newStatus, ['active', 'inactive', 'suspended'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                exit;
            }

            $stmt = $connect->prepare("UPDATE users SET status = :status WHERE user_id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $userId]);

            // Log activity
            $statusLabel = ucfirst($newStatus);
            $stmt2 = $connect->prepare("SELECT first_name, last_name FROM users WHERE user_id = :id");
            $stmt2->execute(['id' => $userId]);
            $u = $stmt2->fetch(PDO::FETCH_ASSOC);
            $name = $u ? $u['first_name'] . ' ' . $u['last_name'] : 'Unknown';

            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description, related_user_id) VALUES (:type, :desc, :uid)");
            $logStmt->execute([
                'type' => 'user_status_change',
                'desc' => "User {$statusLabel}: {$name}",
                'uid' => $userId
            ]);

            echo json_encode(['success' => true, 'message' => "User status changed to {$newStatus}"]);

        } elseif ($action === 'update') {
            $userId = (int) ($data['user_id'] ?? 0);
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $role = $data['role'] ?? '';

            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }

            $fields = [];
            $params = ['id' => $userId];

            if ($firstName !== '') {
                $fields[] = "first_name = :fname";
                $params['fname'] = $firstName;
            }
            if ($lastName !== '') {
                $fields[] = "last_name = :lname";
                $params['lname'] = $lastName;
            }
            if ($email !== '') {
                $fields[] = "email = :email";
                $params['email'] = $email;
            }
            if ($role !== '' && in_array($role, ['parent', 'doctor', 'admin'])) {
                $fields[] = "role = :role";
                $params['role'] = $role;
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                exit;
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);

        } elseif ($action === 'add') {
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $role = $data['role'] ?? 'parent';

            if (!$firstName || !$email || !$password) {
                echo json_encode(['success' => false, 'error' => 'First name, email, and password are required']);
                exit;
            }

            // Check if email already exists
            $check = $connect->prepare("SELECT user_id FROM users WHERE email = :email");
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Email already exists']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (:fname, :lname, :email, :pass, :role, 'active')");
            $stmt->execute([
                'fname' => $firstName,
                'lname' => $lastName,
                'email' => $email,
                'pass' => $hashedPassword,
                'role' => $role
            ]);

            $newUserId = $connect->lastInsertId();

            // If parent, also insert into parent table
            if ($role === 'parent') {
                $stmt = $connect->prepare("INSERT INTO parent (parent_id, number_of_children) VALUES (:pid, 0)");
                $stmt->execute(['pid' => $newUserId]);
            }
            // If admin, also insert into admin table
            if ($role === 'admin') {
                $stmt = $connect->prepare("INSERT INTO admin (admin_id, role_level) VALUES (:aid, 2)");
                $stmt->execute(['aid' => $newUserId]);
            }

            // Log activity
            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description, related_user_id) VALUES ('user_added', :desc, :uid)");
            $logStmt->execute([
                'desc' => "New user added: {$firstName} {$lastName} ({$role})",
                'uid' => $newUserId
            ]);

            echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $newUserId]);

        } elseif ($action === 'delete') {
            $userId = (int) ($data['user_id'] ?? 0);
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }

            // Don't allow deleting yourself
            if ($userId === (int) $_SESSION['id']) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
                exit;
            }

            $stmt = $connect->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute(['id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'User deleted']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

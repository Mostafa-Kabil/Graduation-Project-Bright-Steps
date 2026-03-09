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
        $action = $_GET['action'] ?? 'profile';

        if ($action === 'profile') {
            $adminId = $_SESSION['id'];
            $stmt = $connect->prepare("
                SELECT u.user_id, u.first_name, u.last_name, u.email, u.role,
                    a.role_level
                FROM users u
                JOIN admin a ON u.user_id = a.admin_id
                WHERE u.user_id = :id
            ");
            $stmt->execute(['id' => $adminId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'profile' => $profile]);

        } elseif ($action === 'config') {
            $stmt = $connect->query("SELECT setting_key, setting_value FROM platform_settings");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            echo json_encode(['success' => true, 'config' => $settings]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'update_profile') {
            $adminId = $_SESSION['id'];
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            $fields = [];
            $params = ['id' => $adminId];

            if ($email !== '') {
                // Check email uniqueness
                $check = $connect->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id");
                $check->execute(['email' => $email, 'id' => $adminId]);
                if ($check->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Email already in use']);
                    exit;
                }
                $fields[] = "email = :email";
                $params['email'] = $email;
            }

            if ($password !== '') {
                $fields[] = "password = :pass";
                $params['pass'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                exit;
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);

            // Update session email if changed
            if ($email !== '') {
                $_SESSION['email'] = $email;
            }

            echo json_encode(['success' => true, 'message' => 'Profile updated']);

        } elseif ($action === 'update_config') {
            $key = $data['setting_key'] ?? '';
            $value = $data['setting_value'] ?? '';

            if (!$key) {
                echo json_encode(['success' => false, 'error' => 'Setting key required']);
                exit;
            }

            $stmt = $connect->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val2");
            $stmt->execute(['key' => $key, 'val' => $value, 'val2' => $value]);

            echo json_encode(['success' => true, 'message' => 'Setting updated']);

        } elseif ($action === 'purge_inactive') {
            // Delete users who have been inactive for 6+ months (status = 'inactive')
            // Only delete parents, not admins or doctors
            $stmt = $connect->query("SELECT COUNT(*) as c FROM users WHERE status = 'inactive' AND role = 'parent' AND created_at < NOW() - INTERVAL 6 MONTH");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            $stmt = $connect->query("DELETE FROM users WHERE status = 'inactive' AND role = 'parent' AND created_at < NOW() - INTERVAL 6 MONTH");

            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description) VALUES ('purge_inactive', :desc)");
            $logStmt->execute(['desc' => "Purged {$count} inactive users"]);

            echo json_encode(['success' => true, 'message' => "Purged {$count} inactive users"]);

        } elseif ($action === 'reset_points') {
            $stmt = $connect->query("UPDATE points_wallet SET total_points = 0");
            $affected = $stmt->rowCount();

            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description) VALUES ('points_reset', :desc)");
            $logStmt->execute(['desc' => "All points wallets reset ({$affected} wallets affected)"]);

            echo json_encode(['success' => true, 'message' => "Reset {$affected} wallets to 0 points"]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

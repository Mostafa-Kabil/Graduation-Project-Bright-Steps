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

// Helper: log activity with user info
function logActivity($connect, $type, $desc, $relatedUserId = null) {
    $adminId = $_SESSION['id'] ?? null;
    $userName = '';
    $userRole = 'admin';
    if ($adminId) {
        $s = $connect->prepare("SELECT first_name, last_name FROM users WHERE user_id = :id");
        $s->execute(['id' => $adminId]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u) $userName = $u['first_name'] . ' ' . $u['last_name'];
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $connect->prepare("INSERT INTO activity_log (activity_type, description, related_user_id, user_name, user_role, ip_address) VALUES (:type, :desc, :uid, :uname, :urole, :ip)");
    $stmt->execute(['type' => $type, 'desc' => $desc, 'uid' => $relatedUserId, 'uname' => $userName, 'urole' => $userRole, 'ip' => $ip]);
}

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
        } elseif ($action === 'notifications') {
            $stmt = $connect->prepare("SELECT push_notifications, email_notifications, system_alerts, weekly_reports FROM user_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['id']]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$settings) {
                $settings = ['push_notifications' => 1, 'email_notifications' => 1, 'system_alerts' => 1, 'weekly_reports' => 1];
            }
            // Map email_notifications back to email_updates for JS expectations
            $mappedSettings = [
                'push_notifications' => $settings['push_notifications'],
                'email_updates' => $settings['email_notifications'],
                'system_alerts' => $settings['system_alerts'],
                'weekly_reports' => $settings['weekly_reports']
            ];
            echo json_encode(['success' => true, 'settings' => $mappedSettings]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'update_config') {
            $key = $data['setting_key'] ?? '';
            $value = $data['setting_value'] ?? '';

            if (!$key) {
                echo json_encode(['success' => false, 'error' => 'Setting key required']);
                exit;
            }

            $stmt = $connect->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val2");
            $stmt->execute(['key' => $key, 'val' => $value, 'val2' => $value]);

            logActivity($connect, 'config_updated', "Platform setting updated: {$key} = {$value}");

            echo json_encode(['success' => true, 'message' => 'Setting updated']);

        } elseif ($action === 'purge_inactive') {
            // Delete users who have been inactive for 6+ months (status = 'inactive')
            // Only delete parents, not admins or specialists
            $stmt = $connect->query("SELECT COUNT(*) as c FROM users WHERE status = 'inactive' AND role = 'parent' AND created_at < NOW() - INTERVAL 6 MONTH");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            $stmt = $connect->query("DELETE FROM users WHERE status = 'inactive' AND role = 'parent' AND created_at < NOW() - INTERVAL 6 MONTH");

            logActivity($connect, 'purge_inactive', "Purged {$count} inactive users");

            echo json_encode(['success' => true, 'message' => "Purged {$count} inactive users"]);

        } elseif ($action === 'reset_points') {
            $stmt = $connect->query("UPDATE points_wallet SET total_points = 0");
            $affected = $stmt->rowCount();

            logActivity($connect, 'points_reset', "All points wallets reset ({$affected} wallets affected)");

            echo json_encode(['success' => true, 'message' => "Reset {$affected} wallets to 0 points"]);

        } elseif ($action === 'update_profile') {
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            if (!$firstName || !$lastName) {
                echo json_encode(['success' => false, 'error' => 'First and last name required']);
                exit;
            }
            $stmt = $connect->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?");
            $stmt->execute([$firstName, $lastName, $_SESSION['id']]);
            $_SESSION['fname'] = $firstName;
            $_SESSION['lname'] = $lastName;
            logActivity($connect, 'config_updated', "Admin profile updated: {$firstName} {$lastName}");
            echo json_encode(['success' => true, 'message' => 'Profile updated']);

        } elseif ($action === 'update_notifications') {
            $key = $data['key'] ?? '';
            $value = $data['value'] ?? '0';
            
            $dbKey = $key;
            if ($key === 'email_updates') $dbKey = 'email_notifications';

            $validKeys = ['push_notifications', 'email_notifications', 'system_alerts', 'weekly_reports'];
            if (!in_array($dbKey, $validKeys)) {
                echo json_encode(['success' => false, 'error' => 'Invalid setting']);
                exit;
            }

            $valInt = $value ? 1 : 0;
            $stmt = $connect->prepare("INSERT INTO user_settings (user_id, `$dbKey`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `$dbKey` = ?");
            $stmt->execute([$_SESSION['id'], $valInt, $valInt]);

            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

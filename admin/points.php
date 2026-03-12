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
        $action = $_GET['action'] ?? 'stats';

        if ($action === 'stats') {
            // Total points issued (sum of positive transactions)
            $stmt = $connect->query("SELECT COALESCE(SUM(ABS(points_change)), 0) as total FROM points_transaction WHERE transaction_type = 'deposit'");
            $totalPoints = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Active wallets
            $stmt = $connect->query("SELECT COUNT(*) as total FROM points_wallet WHERE total_points > 0");
            $activeWallets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Badges earned
            $stmt = $connect->query("SELECT COUNT(*) as total FROM child_badge");
            $badgesEarned = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_points_issued' => (int) $totalPoints,
                    'active_wallets' => (int) $activeWallets,
                    'badges_earned' => (int) $badgesEarned
                ]
            ]);

        } elseif ($action === 'rules') {
            $stmt = $connect->query("SELECT refrence_id, action_name, points_value, adjust_sign FROM points_refrence ORDER BY adjust_sign DESC, points_value ASC");
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'rules' => $rules]);

        } elseif ($action === 'top_wallets') {
            $stmt = $connect->query("
                SELECT pw.wallet_id, pw.total_points, pw.child_id,
                    c.first_name, c.last_name,
                    (SELECT COUNT(*) FROM child_badge cb WHERE cb.child_id = pw.child_id) as badge_count
                FROM points_wallet pw
                JOIN child c ON pw.child_id = c.child_id
                ORDER BY pw.total_points DESC
                LIMIT 10
            ");
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'wallets' => $wallets]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'add_rule') {
            $actionName = $data['action_name'] ?? '';
            $pointsValue = (int) ($data['points_value'] ?? 0);
            $adjustSign = $data['adjust_sign'] ?? '+';

            if (!$actionName || !$pointsValue) {
                echo json_encode(['success' => false, 'error' => 'Action name and points value required']);
                exit;
            }

            $adminId = $_SESSION['id'];
            $stmt = $connect->prepare("INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign) VALUES (:aid, :name, :val, :sign)");
            $stmt->execute([
                'aid' => $adminId,
                'name' => $actionName,
                'val' => $pointsValue,
                'sign' => $adjustSign
            ]);

            echo json_encode(['success' => true, 'message' => 'Points rule added']);

        } elseif ($action === 'update_rule') {
            $ruleId = (int) ($data['refrence_id'] ?? 0);
            $actionName = $data['action_name'] ?? '';
            $pointsValue = isset($data['points_value']) ? (int) $data['points_value'] : null;
            $adjustSign = $data['adjust_sign'] ?? null;

            if (!$ruleId) {
                echo json_encode(['success' => false, 'error' => 'Rule ID required']);
                exit;
            }

            $fields = [];
            $params = ['id' => $ruleId];
            if ($actionName !== '') {
                $fields[] = "action_name = :name";
                $params['name'] = $actionName;
            }
            if ($pointsValue !== null) {
                $fields[] = "points_value = :val";
                $params['val'] = $pointsValue;
            }
            if ($adjustSign !== null && in_array($adjustSign, ['+', '-'])) {
                $fields[] = "adjust_sign = :sign";
                $params['sign'] = $adjustSign;
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                exit;
            }

            $sql = "UPDATE points_refrence SET " . implode(', ', $fields) . " WHERE refrence_id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Points rule updated']);

        } elseif ($action === 'delete_rule') {
            $ruleId = (int) ($data['refrence_id'] ?? 0);
            if (!$ruleId) {
                echo json_encode(['success' => false, 'error' => 'Rule ID required']);
                exit;
            }

            $stmt = $connect->prepare("DELETE FROM points_refrence WHERE refrence_id = :id");
            $stmt->execute(['id' => $ruleId]);

            echo json_encode(['success' => true, 'message' => 'Points rule deleted']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

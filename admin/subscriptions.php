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
        $action = $_GET['action'] ?? 'plans';

        if ($action === 'plans') {
            // Get plans with active user counts and features
            $stmt = $connect->query("
                SELECT s.subscription_id, s.plan_name, s.plan_period, s.price,
                    (SELECT COUNT(*) FROM parent_subscription ps WHERE ps.subscription_id = s.subscription_id) as active_users
                FROM subscription s
                ORDER BY s.price ASC
            ");
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get features for each plan
            foreach ($plans as &$plan) {
                $fStmt = $connect->prepare("SELECT feature_text FROM subscription_feature WHERE subscription_id = :sid ORDER BY feature_id ASC");
                $fStmt->execute(['sid' => $plan['subscription_id']]);
                $plan['features'] = $fStmt->fetchAll(PDO::FETCH_COLUMN);
                $plan['mrr'] = round((float) $plan['price'] * (int) $plan['active_users'], 2);
            }

            echo json_encode(['success' => true, 'plans' => $plans]);

        } elseif ($action === 'revenue') {
            $stmt = $connect->query("
                SELECT s.plan_name, s.price,
                    COUNT(ps.parent_id) as subscriber_count,
                    ROUND(s.price * COUNT(ps.parent_id), 2) as monthly_revenue
                FROM subscription s
                LEFT JOIN parent_subscription ps ON s.subscription_id = ps.subscription_id
                GROUP BY s.subscription_id
                ORDER BY s.price ASC
            ");
            $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalMRR = 0;
            foreach ($revenue as $r) {
                $totalMRR += (float) $r['monthly_revenue'];
            }

            echo json_encode(['success' => true, 'revenue' => $revenue, 'total_mrr' => $totalMRR]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'create_plan') {
            $planName = $data['plan_name'] ?? '';
            $planPeriod = $data['plan_period'] ?? 'monthly';
            $price = (float) ($data['price'] ?? 0);
            $features = $data['features'] ?? [];

            if (!$planName) {
                echo json_encode(['success' => false, 'error' => 'Plan name required']);
                exit;
            }

            $stmt = $connect->prepare("INSERT INTO subscription (plan_name, plan_period, price) VALUES (:name, :period, :price)");
            $stmt->execute(['name' => $planName, 'period' => $planPeriod, 'price' => $price]);
            $newId = $connect->lastInsertId();

            // Insert features
            if (!empty($features)) {
                $fStmt = $connect->prepare("INSERT INTO subscription_feature (subscription_id, feature_text) VALUES (:sid, :ft)");
                foreach ($features as $feat) {
                    $fStmt->execute(['sid' => $newId, 'ft' => $feat]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Plan created', 'subscription_id' => $newId]);

        } elseif ($action === 'update_plan') {
            $subId = (int) ($data['subscription_id'] ?? 0);
            $planName = $data['plan_name'] ?? '';
            $price = isset($data['price']) ? (float) $data['price'] : null;
            $features = $data['features'] ?? null;

            if (!$subId) {
                echo json_encode(['success' => false, 'error' => 'Subscription ID required']);
                exit;
            }

            $fields = [];
            $params = ['id' => $subId];
            if ($planName !== '') {
                $fields[] = "plan_name = :name";
                $params['name'] = $planName;
            }
            if ($price !== null) {
                $fields[] = "price = :price";
                $params['price'] = $price;
            }

            if (!empty($fields)) {
                $sql = "UPDATE subscription SET " . implode(', ', $fields) . " WHERE subscription_id = :id";
                $stmt = $connect->prepare($sql);
                $stmt->execute($params);
            }

            // Update features if provided
            if ($features !== null) {
                $connect->prepare("DELETE FROM subscription_feature WHERE subscription_id = :sid")->execute(['sid' => $subId]);
                $fStmt = $connect->prepare("INSERT INTO subscription_feature (subscription_id, feature_text) VALUES (:sid, :ft)");
                foreach ($features as $feat) {
                    $fStmt->execute(['sid' => $subId, 'ft' => $feat]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Plan updated']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

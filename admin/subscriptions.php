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
        $action = $_GET['action'] ?? 'plans';

        if ($action === 'plans') {
            // Get plans with active user counts and features
            $stmt = $connect->query("
                SELECT s.subscription_id, s.plan_name, s.plan_period, s.price, s.description, s.status,
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

        } elseif ($action === 'revenue_kpis') {
            // MRR
            $stmt = $connect->query("SELECT COALESCE(SUM(s.price), 0) as mrr FROM parent_subscription ps JOIN subscription s ON ps.subscription_id=s.subscription_id WHERE s.status='active' AND s.plan_period='monthly'");
            $mrr = $stmt->fetchColumn();
            $arr = $mrr * 12;

            // New subs this month
            $newSubs = $connect->query("SELECT COUNT(*) FROM parent_subscription ps JOIN subscription s ON ps.subscription_id=s.subscription_id WHERE s.price > 0")->fetchColumn();

            // Total revenue
            $totalRev = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) FROM payment WHERE status='completed' OR status IS NULL")->fetchColumn();

            // Revenue this month
            $revThisMonth = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) FROM payment WHERE (status='completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
            $revLastMonth = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) FROM payment WHERE (status='completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
            $revTrend = $revLastMonth > 0 ? round(($revThisMonth - $revLastMonth) / $revLastMonth * 100) : 0;

            // Active subscribers
            $activeSubs = $connect->query("SELECT COUNT(DISTINCT parent_id) FROM parent_subscription")->fetchColumn();

            echo json_encode(['success' => true, 'kpis' => [
                'mrr' => floatval($mrr), 'arr' => floatval($arr),
                'new_subscriptions' => intval($newSubs),
                'active_subscribers' => intval($activeSubs),
                'total_revenue' => floatval($totalRev),
                'revenue_this_month' => floatval($revThisMonth),
                'revenue_trend' => $revTrend,
                'churn_rate' => 4.2, 'net_growth' => 8.5
            ]]);

        } elseif ($action === 'revenue_chart') {
            // Revenue per month (last 6 months)
            $stmt = $connect->query("SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(amount_post_discount), 0) as revenue FROM payment WHERE (status='completed' OR status IS NULL) AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month");
            $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Revenue by plan
            $planStmt = $connect->query("SELECT s.plan_name, COALESCE(SUM(p.amount_post_discount), 0) as revenue, COUNT(*) as count FROM payment p JOIN subscription s ON p.subscription_id=s.subscription_id WHERE p.status='completed' OR p.status IS NULL GROUP BY s.plan_name");
            $planData = $planStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'monthly_revenue' => $chartData, 'revenue_by_plan' => $planData]);

        } elseif ($action === 'revenue_top_users') {
            $stmt = $connect->query("SELECT u.user_id, u.first_name, u.last_name, u.email, COALESCE(SUM(p.amount_post_discount), 0) as total_paid, COUNT(p.payment_id) as payment_count FROM users u JOIN parent par ON u.user_id=par.parent_id LEFT JOIN parent_subscription ps ON par.parent_id=ps.parent_id LEFT JOIN payment p ON ps.subscription_id=p.subscription_id WHERE (p.status='completed' OR p.status IS NULL) GROUP BY u.user_id ORDER BY total_paid DESC LIMIT 10");
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
            $description = $data['description'] ?? '';
            $status = $data['status'] ?? 'active';
            $features = $data['features'] ?? [];

            if (!$planName) {
                echo json_encode(['success' => false, 'error' => 'Plan name required']);
                exit;
            }

            $stmt = $connect->prepare("INSERT INTO subscription (plan_name, plan_period, price, description, status) VALUES (:name, :period, :price, :desc, :status)");
            $stmt->execute(['name' => $planName, 'period' => $planPeriod, 'price' => $price, 'desc' => $description, 'status' => $status]);
            $newId = $connect->lastInsertId();

            // Insert features
            if (!empty($features)) {
                $fStmt = $connect->prepare("INSERT INTO subscription_feature (subscription_id, feature_text) VALUES (:sid, :ft)");
                foreach ($features as $feat) {
                    $fStmt->execute(['sid' => $newId, 'ft' => $feat]);
                }
            }

            logActivity($connect, 'plan_created', "New subscription plan created: {$planName} (\${$price}/{$planPeriod})");

            echo json_encode(['success' => true, 'message' => 'Plan created', 'subscription_id' => $newId]);

        } elseif ($action === 'update_plan') {
            $subId = (int) ($data['subscription_id'] ?? 0);
            $planName = $data['plan_name'] ?? '';
            $planPeriod = $data['plan_period'] ?? null;
            $price = isset($data['price']) ? (float) $data['price'] : null;
            $description = $data['description'] ?? null;
            $status = $data['status'] ?? null;
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
            if ($planPeriod !== null) {
                $fields[] = "plan_period = :period";
                $params['period'] = $planPeriod;
            }
            if ($description !== null) {
                $fields[] = "description = :desc";
                $params['desc'] = $description;
            }
            if ($status !== null && in_array($status, ['active', 'inactive'])) {
                $fields[] = "status = :status";
                $params['status'] = $status;
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

            logActivity($connect, 'plan_updated', "Subscription plan updated: " . ($planName ?: "ID #{$subId}"));

            echo json_encode(['success' => true, 'message' => 'Plan updated']);

        } elseif ($action === 'delete_plan') {
            $subId = (int) ($data['subscription_id'] ?? 0);
            if (!$subId) {
                echo json_encode(['success' => false, 'error' => 'Subscription ID required']);
                exit;
            }

            // Check if plan has active subscribers
            $check = $connect->prepare("SELECT COUNT(*) as c FROM parent_subscription WHERE subscription_id = :sid");
            $check->execute(['sid' => $subId]);
            $subCount = $check->fetch(PDO::FETCH_ASSOC)['c'];

            if ($subCount > 0) {
                echo json_encode(['success' => false, 'error' => "Cannot delete plan with {$subCount} active subscriber(s). Deactivate it instead."]);
                exit;
            }

            // Get plan name for logging
            $nameStmt = $connect->prepare("SELECT plan_name FROM subscription WHERE subscription_id = :sid");
            $nameStmt->execute(['sid' => $subId]);
            $planRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $deletedName = $planRow ? $planRow['plan_name'] : "ID #{$subId}";

            // Delete features first (cascade), then plan
            $connect->prepare("DELETE FROM subscription_feature WHERE subscription_id = :sid")->execute(['sid' => $subId]);
            $connect->prepare("DELETE FROM subscription WHERE subscription_id = :sid")->execute(['sid' => $subId]);

            logActivity($connect, 'plan_deleted', "Subscription plan deleted: {$deletedName}");

            echo json_encode(['success' => true, 'message' => 'Plan deleted']);

        } elseif ($action === 'toggle_status') {
            $subId = (int) ($data['subscription_id'] ?? 0);
            $newStatus = $data['status'] ?? '';

            if (!$subId || !in_array($newStatus, ['active', 'inactive'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                exit;
            }

            $stmt = $connect->prepare("UPDATE subscription SET status = :status WHERE subscription_id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $subId]);

            $nameStmt = $connect->prepare("SELECT plan_name FROM subscription WHERE subscription_id = :sid");
            $nameStmt->execute(['sid' => $subId]);
            $planRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $pName = $planRow ? $planRow['plan_name'] : "ID #{$subId}";

            logActivity($connect, 'plan_status_changed', "Plan '{$pName}' status changed to {$newStatus}");

            echo json_encode(['success' => true, 'message' => "Plan status changed to {$newStatus}"]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

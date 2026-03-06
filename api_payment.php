<?php
/**
 * Bright Steps – PHP Payment Processing Endpoint
 * Works within XAMPP without needing the Python FastAPI Payment API.
 * Handles payment recording directly via PDO.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$parentId = $_SESSION['id'];

switch ($action) {

    // ── Record a payment ──────────────────────────────────────────────
    case 'process':
        $input = json_decode(file_get_contents('php://input'), true);

        $subscriptionId = $input['subscription_id'] ?? null;
        $paymentMethod = $input['payment_method'] ?? 'card';
        $childName = $input['child_name'] ?? 'All Children';

        if (!$subscriptionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing subscription_id']);
            exit();
        }

        // Get subscription details
        $stmt = $connect->prepare("SELECT plan_name, price FROM subscription WHERE subscription_id = ?");
        $stmt->execute([$subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            http_response_code(404);
            echo json_encode(['error' => 'Subscription plan not found']);
            exit();
        }

        $amount = floatval($sub['price']);

        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Free plans do not require payment']);
            exit();
        }

        try {
            $connect->beginTransaction();

            // Insert payment record
            $stmt = $connect->prepare(
                "INSERT INTO payment (subscription_id, amount_pre_discount, discount_rate, method, status) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$subscriptionId, $amount, 0.00, $paymentMethod, 'completed']);
            $paymentId = $connect->lastInsertId();

            // Insert/update parent subscription
            $stmt = $connect->prepare(
                "INSERT INTO parent_subscription (parent_id, subscription_id, child_name) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE subscription_id = VALUES(subscription_id)"
            );
            $stmt->execute([$parentId, $subscriptionId, $childName]);

            $connect->commit();

            echo json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'plan_name' => $sub['plan_name'],
                'amount' => $amount,
                'message' => "Payment recorded! You are now on the {$sub['plan_name']} plan."
            ]);

        } catch (Exception $e) {
            $connect->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
        break;

    // ── Get payment history ───────────────────────────────────────────
    case 'history':
        $stmt = $connect->prepare(
            "SELECT p.payment_id, p.amount_pre_discount, p.amount_post_discount,
                    p.discount_rate, p.method, p.status, p.paid_at,
                    s.plan_name, s.plan_period
             FROM payment p
             INNER JOIN subscription s ON p.subscription_id = s.subscription_id
             INNER JOIN parent_subscription ps ON p.subscription_id = ps.subscription_id
             WHERE ps.parent_id = ?
             ORDER BY p.paid_at DESC"
        );
        $stmt->execute([$parentId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['parent_id' => $parentId, 'payments' => $payments]);
        break;

    // ── Get subscription info ─────────────────────────────────────────
    case 'subscriptions':
        $stmt = $connect->prepare("SELECT * FROM subscription ORDER BY price ASC");
        $stmt->execute();
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['plans' => $plans]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: process, history, subscriptions']);
        break;
}

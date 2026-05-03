<?php
/**
 * Bright Steps - Parent Points System API
 * Handles point earning with caps, restrictions, and cooldowns
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// Only allow authenticated parents
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Parent access required']);
    exit();
}

$parentId = $_SESSION['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'balance';

        // Get parent wallet balance
        if ($action === 'balance') {
            $stmt = $connect->prepare("
                SELECT wallet_id, total_points, lifetime_earned, lifetime_redeemed,
                       last_earned_at, created_at
                FROM parent_points_wallet
                WHERE parent_id = ?
            ");
            $stmt->execute([$parentId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet) {
                // Initialize wallet if not exists
                $connect->beginTransaction();
                $stmt = $connect->prepare("INSERT INTO parent_points_wallet (parent_id) VALUES (?)");
                $stmt->execute([$parentId]);
                $walletId = $connect->lastInsertId();
                $connect->commit();

                $wallet = [
                    'wallet_id' => $walletId,
                    'total_points' => 0,
                    'lifetime_earned' => 0,
                    'lifetime_redeemed' => 0,
                    'last_earned_at' => null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            echo json_encode([
                'success' => true,
                'wallet' => $wallet
            ]);

        // Get points transaction history
        } elseif ($action === 'history') {
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

            $stmt = $connect->prepare("
                SELECT pt.*, pr.action_name, pr.points_value
                FROM points_transaction pt
                LEFT JOIN points_refrence pr ON pt.refrence_id = pr.refrence_id
                WHERE pt.parent_id = ?
                ORDER BY pt.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$parentId, $limit, $offset]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $stmt = $connect->prepare("SELECT COUNT(*) FROM points_transaction WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            $total = $stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'transactions' => $transactions,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);

        // Get earning rules with caps
        } elseif ($action === 'rules') {
            $stmt = $connect->query("
                SELECT action_key, action_name, points_value, daily_cap, weekly_cap,
                       cooldown_minutes, requires_verification, description
                FROM points_earning_rules
                WHERE is_active = 1
                ORDER BY points_value DESC
            ");
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'rules' => $rules
            ]);

        // Check earning eligibility for specific action
        } elseif ($action === 'eligibility') {
            $actionKey = $_GET['action_key'] ?? '';

            if (!$actionKey) {
                http_response_code(400);
                echo json_encode(['error' => 'action_key parameter required']);
                exit();
            }

            // Get rule
            $stmt = $connect->prepare("SELECT * FROM points_earning_rules WHERE action_key = ? AND is_active = 1");
            $stmt->execute([$actionKey]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rule) {
                http_response_code(404);
                echo json_encode(['error' => 'Action rule not found or inactive']);
                exit();
            }

            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));

            $eligibility = [
                'action_key' => $actionKey,
                'action_name' => $rule['action_name'],
                'points_value' => (int) $rule['points_value'],
                'eligible' => true,
                'reason' => null,
                'daily_earned' => 0,
                'daily_remaining' => $rule['daily_cap'] ?? null,
                'weekly_earned' => 0,
                'weekly_remaining' => $rule['weekly_cap'] ?? null,
                'cooldown_remaining_minutes' => 0
            ];

            // Check daily cap
            if ($rule['daily_cap']) {
                $stmt = $connect->prepare("
                    SELECT COALESCE(SUM(points_earned), 0) as daily_total
                    FROM parent_points_tracking
                    WHERE parent_id = ? AND action_key = ? AND earned_date = ?
                ");
                $stmt->execute([$parentId, $actionKey, $today]);
                $dailyTotal = (int) $stmt->fetchColumn();

                $eligibility['daily_earned'] = $dailyTotal;
                $eligibility['daily_remaining'] = max(0, $rule['daily_cap'] - $dailyTotal);

                if ($dailyTotal >= $rule['daily_cap']) {
                    $eligibility['eligible'] = false;
                    $eligibility['reason'] = 'Daily cap reached';
                }
            }

            // Check weekly cap
            if ($rule['weekly_cap'] && $eligibility['eligible']) {
                $stmt = $connect->prepare("
                    SELECT COALESCE(SUM(points_earned), 0) as weekly_total
                    FROM parent_points_tracking
                    WHERE parent_id = ? AND action_key = ? AND week_start_date = ?
                ");
                $stmt->execute([$parentId, $actionKey, $weekStart]);
                $weeklyTotal = (int) $stmt->fetchColumn();

                $eligibility['weekly_earned'] = $weeklyTotal;
                $eligibility['weekly_remaining'] = max(0, $rule['weekly_cap'] - $weeklyTotal);

                if ($weeklyTotal >= $rule['weekly_cap']) {
                    $eligibility['eligible'] = false;
                    $eligibility['reason'] = 'Weekly cap reached';
                }
            }

            // Check cooldown
            if ($rule['cooldown_minutes'] > 0 && $eligibility['eligible']) {
                $stmt = $connect->prepare("
                    SELECT available_at FROM parent_action_cooldowns
                    WHERE parent_id = ? AND action_key = ?
                ");
                $stmt->execute([$parentId, $actionKey]);
                $cooldown = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($cooldown) {
                    $availableAt = strtotime($cooldown['available_at']);
                    $now = time();

                    if ($now < $availableAt) {
                        $eligibility['eligible'] = false;
                        $eligibility['reason'] = 'Cooldown active';
                        $eligibility['cooldown_remaining_minutes'] = ceil(($availableAt - $now) / 60);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'eligibility' => $eligibility
            ]);

        // Get dashboard summary
        } elseif ($action === 'summary') {
            // Wallet info
            $stmt = $connect->prepare("SELECT * FROM parent_points_wallet WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet) {
                $wallet = ['total_points' => 0, 'lifetime_earned' => 0, 'lifetime_redeemed' => 0];
            }

            // Weekly earnings
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $stmt = $connect->prepare("
                SELECT COALESCE(SUM(points_earned), 0)
                FROM parent_points_tracking
                WHERE parent_id = ? AND week_start_date = ?
            ");
            $stmt->execute([$parentId, $weekStart]);
            $weeklyEarned = (int) $stmt->fetchColumn();

            // Today's earnings
            $today = date('Y-m-d');
            $stmt = $connect->prepare("
                SELECT COALESCE(SUM(points_earned), 0)
                FROM parent_points_tracking
                WHERE parent_id = ? AND earned_date = ?
            ");
            $stmt->execute([$parentId, $today]);
            $todayEarned = (int) $stmt->fetchColumn();

            // Active redemptions count
            $stmt = $connect->prepare("
                SELECT COUNT(*) FROM parent_redemptions
                WHERE parent_id = ? AND status = 'active'
            ");
            $stmt->execute([$parentId]);
            $activeRedemptions = (int) $stmt->fetchColumn();

            // Available tokens
            $stmt = $connect->prepare("
                SELECT COUNT(*) FROM appointment_tokens
                WHERE parent_id = ? AND status = 'available'
            ");
            $stmt->execute([$parentId]);
            $availableTokens = (int) $stmt->fetchColumn();

            // Milestones
            $stmt = $connect->prepare("
                SELECT milestone_type, milestone_value, achieved_at
                FROM parent_points_milestones
                WHERE parent_id = ?
                ORDER BY achieved_at DESC LIMIT 5
            ");
            $stmt->execute([$parentId]);
            $recentMilestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'summary' => [
                    'wallet' => $wallet,
                    'weekly_earned' => $weeklyEarned,
                    'today_earned' => $todayEarned,
                    'active_redemptions' => $activeRedemptions,
                    'available_tokens' => $availableTokens,
                    'recent_milestones' => $recentMilestones
                ]
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? '';

        // Earn points for an action
        if ($action === 'earn') {
            $actionKey = $input['action_key'] ?? '';
            $evidenceUrl = $input['evidence_url'] ?? null;

            if (!$actionKey) {
                http_response_code(400);
                echo json_encode(['error' => 'action_key required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                // Get rule
                $stmt = $connect->prepare("SELECT * FROM points_earning_rules WHERE action_key = ? AND is_active = 1");
                $stmt->execute([$actionKey]);
                $rule = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$rule) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Action rule not found or inactive']);
                    exit();
                }

                $today = date('Y-m-d');
                $weekStart = date('Y-m-d', strtotime('monday this week'));
                $now = date('Y-m-d H:i:s');

                // Check daily cap
                if ($rule['daily_cap']) {
                    $stmt = $connect->prepare("
                        SELECT COALESCE(SUM(points_earned), 0) as daily_total
                        FROM parent_points_tracking
                        WHERE parent_id = ? AND action_key = ? AND earned_date = ?
                    ");
                    $stmt->execute([$parentId, $actionKey, $today]);
                    $dailyTotal = (int) $stmt->fetchColumn();

                    if ($dailyTotal >= $rule['daily_cap']) {
                        $connect->rollBack();
                        http_response_code(400);
                        echo json_encode(['error' => 'Daily cap reached', 'daily_earned' => $dailyTotal, 'daily_cap' => $rule['daily_cap']]);
                        exit();
                    }
                }

                // Check weekly cap
                if ($rule['weekly_cap']) {
                    $stmt = $connect->prepare("
                        SELECT COALESCE(SUM(points_earned), 0) as weekly_total
                        FROM parent_points_tracking
                        WHERE parent_id = ? AND action_key = ? AND week_start_date = ?
                    ");
                    $stmt->execute([$parentId, $actionKey, $weekStart]);
                    $weeklyTotal = (int) $stmt->fetchColumn();

                    if ($weeklyTotal >= $rule['weekly_cap']) {
                        $connect->rollBack();
                        http_response_code(400);
                        echo json_encode(['error' => 'Weekly cap reached', 'weekly_earned' => $weeklyTotal, 'weekly_cap' => $rule['weekly_cap']]);
                        exit();
                    }
                }

                // Check cooldown
                if ($rule['cooldown_minutes'] > 0) {
                    $stmt = $connect->prepare("
                        SELECT available_at FROM parent_action_cooldowns
                        WHERE parent_id = ? AND action_key = ?
                    ");
                    $stmt->execute([$parentId, $actionKey]);
                    $cooldown = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($cooldown && strtotime($cooldown['available_at']) > time()) {
                        $connect->rollBack();
                        echo json_encode([
                            'success' => false,
                            'error' => 'Cooldown active',
                            'available_at' => $cooldown['available_at']
                        ]);
                        exit();
                    }
                }

                // Calculate points to award (respect caps)
                $pointsToAward = (int) $rule['points_value'];

                if ($rule['daily_cap']) {
                    $stmt = $connect->prepare("
                        SELECT COALESCE(SUM(points_earned), 0)
                        FROM parent_points_tracking
                        WHERE parent_id = ? AND action_key = ? AND earned_date = ?
                    ");
                    $stmt->execute([$parentId, $actionKey, $today]);
                    $dailyRemaining = $rule['daily_cap'] - (int) $stmt->fetchColumn();
                    $pointsToAward = min($pointsToAward, $dailyRemaining);
                }

                if ($rule['weekly_cap']) {
                    $stmt = $connect->prepare("
                        SELECT COALESCE(SUM(points_earned), 0)
                        FROM parent_points_tracking
                        WHERE parent_id = ? AND action_key = ? AND week_start_date = ?
                    ");
                    $stmt->execute([$parentId, $actionKey, $weekStart]);
                    $weeklyRemaining = $rule['weekly_cap'] - (int) $stmt->fetchColumn();
                    $pointsToAward = min($pointsToAward, $weeklyRemaining);
                }

                if ($pointsToAward <= 0) {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'No points available due to caps']);
                    exit();
                }

                // Check if requires verification
                if ($rule['requires_verification']) {
                    $stmt = $connect->prepare("
                        INSERT INTO points_verification_queue (parent_id, action_key, claimed_points, evidence_url)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$parentId, $actionKey, $pointsToAward, $evidenceUrl]);
                    $verificationId = $connect->lastInsertId();

                    $connect->commit();

                    echo json_encode([
                        'success' => true,
                        'pending_verification' => true,
                        'verification_id' => $verificationId,
                        'points_claimed' => $pointsToAward,
                        'message' => 'Points pending admin verification'
                    ]);
                    exit();
                }

                // Get or create wallet
                $stmt = $connect->prepare("SELECT wallet_id FROM parent_points_wallet WHERE parent_id = ?");
                $stmt->execute([$parentId]);
                $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$wallet) {
                    $stmt = $connect->prepare("INSERT INTO parent_points_wallet (parent_id) VALUES (?)");
                    $stmt->execute([$parentId]);
                    $walletId = $connect->lastInsertId();
                } else {
                    $walletId = $wallet['wallet_id'];
                }

                // Update wallet
                $stmt = $connect->prepare("
                    UPDATE parent_points_wallet
                    SET total_points = total_points + ?,
                        lifetime_earned = lifetime_earned + ?,
                        last_earned_at = ?
                    WHERE wallet_id = ?
                ");
                $stmt->execute([$pointsToAward, $pointsToAward, $now, $walletId]);

                // Track daily/weekly earnings
                $stmt = $connect->prepare("
                    INSERT INTO parent_points_tracking (parent_id, action_key, points_earned, earned_date, week_start_date)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE points_earned = points_earned + VALUES(points_earned)
                ");
                $stmt->execute([$parentId, $actionKey, $pointsToAward, $today, $weekStart]);

                // Update/set cooldown
                if ($rule['cooldown_minutes'] > 0) {
                    $availableAt = date('Y-m-d H:i:s', time() + ($rule['cooldown_minutes'] * 60));
                    $stmt = $connect->prepare("
                        INSERT INTO parent_action_cooldowns (parent_id, action_key, available_at)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            last_action_at = NOW(),
                            available_at = VALUES(available_at)
                    ");
                    $stmt->execute([$parentId, $actionKey, $availableAt]);
                }

                // Create transaction record
                $stmt = $connect->prepare("
                    INSERT INTO points_transaction (parent_id, refrence_id, points_change, transaction_type, session_id)
                    VALUES (?, ?, ?, 'deposit', ?)
                ");
                $refrenceId = $rule['rule_id']; // Using rule_id as reference
                $sessionId = session_id();
                $stmt->execute([$parentId, $refrenceId, $pointsToAward, $sessionId]);

                // Create notification
                $stmt = $connect->prepare("
                    INSERT INTO notifications (user_id, type, title, message)
                    VALUES (?, 'points', ?, ?)
                ");
                $title = "Points Earned! 🎉";
                $message = "You earned {$pointsToAward} points for {$rule['action_name']}";
                $stmt->execute([$parentId, $title, $message]);

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'points_earned' => $pointsToAward,
                    'action_name' => $rule['action_name'],
                    'new_balance' => (int) ($wallet['total_points'] ?? 0) + $pointsToAward
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to earn points: ' . $e->getMessage()]);
            }

        // Redeem points for catalog item
        } elseif ($action === 'redeem') {
            $itemId = $input['item_id'] ?? null;
            $quantity = $input['quantity'] ?? 1;

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                // Get wallet
                $stmt = $connect->prepare("SELECT * FROM parent_points_wallet WHERE parent_id = ?");
                $stmt->execute([$parentId]);
                $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$wallet || $wallet['total_points'] <= 0) {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Insufficient points']);
                    exit();
                }

                // Get catalog item
                $stmt = $connect->prepare("SELECT * FROM redemption_catalog WHERE item_id = ? AND is_active = 1");
                $stmt->execute([$itemId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Item not found or inactive']);
                    exit();
                }

                $totalCost = $item['points_cost'] * $quantity;

                // Check balance
                if ($wallet['total_points'] < $totalCost) {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Insufficient points',
                        'required' => $totalCost,
                        'available' => $wallet['total_points'],
                        'shortfall' => $totalCost - $wallet['total_points']
                    ]);
                    exit();
                }

                // Check user redemption limit
                if ($item['max_redemptions_per_user']) {
                    $stmt = $connect->prepare("
                        SELECT COUNT(*) FROM parent_redemptions
                        WHERE parent_id = ? AND item_id = ? AND status IN ('active', 'pending')
                    ");
                    $stmt->execute([$parentId, $itemId]);
                    $currentRedemptions = (int) $stmt->fetchColumn();

                    if ($currentRedemptions >= $item['max_redemptions_per_user']) {
                        $connect->rollBack();
                        http_response_code(400);
                        echo json_encode(['error' => 'Redemption limit reached for this item']);
                        exit();
                    }
                }

                // Deduct points
                $stmt = $connect->prepare("
                    UPDATE parent_points_wallet
                    SET total_points = total_points - ?,
                        lifetime_redeemed = lifetime_redeemed + ?
                    WHERE wallet_id = ?
                ");
                $stmt->execute([$totalCost, $totalCost, $wallet['wallet_id']]);

                // Create redemption record(s)
                $redemptionIds = [];
                for ($i = 0; $i < $quantity; $i++) {
                    $expiresAt = $item['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));

                    $stmt = $connect->prepare("
                        INSERT INTO parent_redemptions (wallet_id, parent_id, item_id, points_used, expires_at)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$wallet['wallet_id'], $parentId, $itemId, $item['points_cost'], $expiresAt]);
                    $redemptionIds[] = $connect->lastInsertId();

                    // If appointment item, create token
                    if ($item['item_type'] === 'appointment') {
                        $tokenType = match($itemId) {
                            1 => 'discount_25',
                            2 => 'discount_50',
                            3 => 'free',
                            4 => 'extended',
                            5 => 'priority',
                            default => 'discount_25'
                        };

                        $discountAmount = match($itemId) {
                            1 => $item['original_price'] * 0.25,
                            2 => $item['original_price'] * 0.50,
                            3 => $item['original_price'],
                            default => 0
                        };

                        $stmt = $connect->prepare("
                            INSERT INTO appointment_tokens (parent_id, redemption_id, token_type, discount_amount, expires_at)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$parentId, end($redemptionIds), $tokenType, $discountAmount, $expiresAt]);
                    }
                }

                // Create transaction record
                $stmt = $connect->prepare("
                    INSERT INTO points_transaction (parent_id, points_change, transaction_type, session_id)
                    VALUES (?, ?, 'withdrawal', ?)
                ");
                $stmt->execute([$parentId, -$totalCost, session_id()]);

                // Create notification
                $stmt = $connect->prepare("
                    INSERT INTO notifications (user_id, type, title, message)
                    VALUES (?, 'points', ?, ?)
                ");
                $title = "Redemption Successful! 🎁";
                $message = "You redeemed {$item['item_name']} for {$totalCost} points";
                $stmt->execute([$parentId, $title, $message]);

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'redemption_ids' => $redemptionIds,
                    'item_name' => $item['item_name'],
                    'points_used' => $totalCost,
                    'new_balance' => $wallet['total_points'] - $totalCost,
                    'token_created' => $item['item_type'] === 'appointment'
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Redemption failed: ' . $e->getMessage()]);
            }

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

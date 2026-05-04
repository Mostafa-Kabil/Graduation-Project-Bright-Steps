<?php
/**
 * Bright Steps – Centralized Points Engine API
 * Handles point awarding, balance queries, transaction history, and token redemption.
 * Actions: get_balance, get_history, award, redeem_token
 */
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$childId = $_GET['child_id'] ?? $_POST['child_id'] ?? '';

// Verify child belongs to parent
function verifyChildOwnership($connect, $childId, $parentId) {
    if (!$childId) return false;
    $stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
    $stmt->execute([$childId, $parentId]);
    return $stmt->fetch() !== false;
}

// Core function: Award points to a child's wallet + log transaction + log tracking
function awardPoints($connect, $parentId, $childId, $actionName, $points, $reason = '') {
    if ($points <= 0) return ['success' => false, 'error' => 'Invalid points value'];

    try {
        // 1. Ensure wallet exists
        $stmt = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ?");
        $stmt->execute([$childId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (?, ?)")->execute([$childId, $points]);
            $walletId = $connect->lastInsertId();
        } else {
            $walletId = $wallet['wallet_id'];
            $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE wallet_id = ?")->execute([$points, $walletId]);
        }

        // 2. Log to points_transaction (using points_refrence if available)
        $stmt = $connect->prepare("SELECT refrence_id FROM points_refrence WHERE action_name = ? LIMIT 1");
        $stmt->execute([$actionName]);
        $refId = $stmt->fetchColumn();

        if (!$refId) {
            // Auto-create reference
            $adminStmt = $connect->prepare("SELECT admin_id FROM admin LIMIT 1");
            $adminStmt->execute();
            $adminId = $adminStmt->fetchColumn();
            if ($adminId) {
                $connect->prepare("INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign) VALUES (?, ?, ?, '+')")->execute([$adminId, $actionName, $points]);
                $refId = $connect->lastInsertId();
            }
        }

        if ($refId) {
            $connect->prepare("INSERT INTO points_transaction (refrence_id, wallet_id, points_change, transaction_type) VALUES (?, ?, ?, 'deposit')")->execute([$refId, $walletId, $points]);
        }

        // 3. Log to parent_points_tracking
        $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, ?, ?, ?)")->execute([$parentId, $childId, $actionName, $points, $reason]);

        // 4. Get updated balance
        $stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ?");
        $stmt->execute([$childId]);
        $newBalance = (int)$stmt->fetchColumn();

        return ['success' => true, 'points_awarded' => $points, 'new_balance' => $newBalance];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

switch ($action) {

    case 'get_balance':
        if (!$childId || !verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }
        $stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ? LIMIT 1");
        $stmt->execute([$childId]);
        $pts = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'balance' => $pts !== false ? (int)$pts : 0]);
        break;

    case 'get_history':
        if (!$childId || !verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }
        $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
        $stmt = $connect->prepare("
            SELECT action, points, reason, created_at
            FROM parent_points_tracking
            WHERE parent_id = ? AND child_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $parentId, PDO::PARAM_INT);
        $stmt->bindValue(2, $childId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also get balance
        $stmt2 = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ? LIMIT 1");
        $stmt2->execute([$childId]);
        $balance = $stmt2->fetchColumn();

        echo json_encode([
            'success' => true,
            'balance' => $balance !== false ? (int)$balance : 0,
            'history' => $history
        ]);
        break;

    case 'redeem_token':
        $input = json_decode(file_get_contents('php://input'), true);
        $childId = $input['child_id'] ?? '';
        $requiredPoints = 500; // Cost of one free consultation token

        if (!$childId || !verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }

        try {
            $connect->beginTransaction();

            // Check balance
            $stmt = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ?");
            $stmt->execute([$childId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet || $wallet['total_points'] < $requiredPoints) {
                $connect->rollBack();
                echo json_encode(['error' => "Insufficient points. You need $requiredPoints points but have " . ($wallet['total_points'] ?? 0) . "."]);
                exit();
            }

            // Deduct points
            $connect->prepare("UPDATE points_wallet SET total_points = total_points - ? WHERE wallet_id = ?")->execute([$requiredPoints, $wallet['wallet_id']]);

            // Generate unique token code
            $tokenCode = 'BS-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

            // Create token
            $connect->prepare("INSERT INTO appointment_tokens (parent_id, child_id, token_code, points_redeemed, discount_amount, status) VALUES (?, ?, ?, ?, 50.00, 'active')")->execute([$parentId, $childId, $tokenCode, $requiredPoints]);

            // Log transaction
            $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Token Redemption', ?, ?)")->execute([$parentId, $childId, -$requiredPoints, "Redeemed $requiredPoints points for free consultation token $tokenCode"]);

            // Get new balance
            $stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ?");
            $stmt->execute([$childId]);
            $newBalance = (int)$stmt->fetchColumn();

            $connect->commit();
            echo json_encode([
                'success' => true,
                'token_code' => $tokenCode,
                'points_deducted' => $requiredPoints,
                'new_balance' => $newBalance,
                'message' => "Token $tokenCode created! Use it when booking your next appointment."
            ]);
        } catch (Exception $e) {
            $connect->rollBack();
            echo json_encode(['error' => 'Failed to create token: ' . $e->getMessage()]);
        }
        break;

    case 'get_tokens':
        $stmt = $connect->prepare("
            SELECT at.token_code, at.discount_amount, at.status, at.created_at, at.used_at,
                   c.first_name AS child_fname, c.last_name AS child_lname
            FROM appointment_tokens at
            JOIN child c ON at.child_id = c.child_id
            WHERE at.parent_id = ?
            ORDER BY at.created_at DESC
        ");
        $stmt->execute([$parentId]);
        echo json_encode(['success' => true, 'tokens' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_offers':
        $stmt = $connect->query("SELECT * FROM reward_offers WHERE is_active = 1 ORDER BY points_required ASC");
        echo json_encode(['success' => true, 'offers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'redeem_offer':
        $input = json_decode(file_get_contents('php://input'), true);
        $offerId = $input['offer_id'] ?? null;
        $childId = $input['child_id'] ?? null;

        if (!$offerId || !$childId) {
            echo json_encode(['error' => 'Missing offer or child ID']);
            exit();
        }

        try {
            $connect->beginTransaction();

            $stmt = $connect->prepare("SELECT * FROM reward_offers WHERE offer_id = ? AND is_active = 1");
            $stmt->execute([$offerId]);
            $offer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$offer) throw new Exception("Offer not found or inactive.");

            $reqPoints = (int)$offer['points_required'];

            $stmt2 = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ?");
            $stmt2->execute([$childId]);
            $bal = $stmt2->fetchColumn();

            if ($bal === false || $bal < $reqPoints) {
                throw new Exception("Not enough points.");
            }

            // Deduct points
            $connect->prepare("UPDATE points_wallet SET total_points = total_points - ? WHERE child_id = ?")->execute([$reqPoints, $childId]);

            // Log tracking (create table if missing)
            try {
                $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Offer Redemption', ?, ?)")
                    ->execute([$parentId, $childId, -$reqPoints, "Redeemed " . $offer['title']]);
            } catch (Exception $e) {
                $connect->exec("CREATE TABLE IF NOT EXISTS `parent_points_tracking` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY, `parent_id` INT NOT NULL, `child_id` INT DEFAULT NULL,
                    `action` VARCHAR(100) NOT NULL, `points` INT NOT NULL, `reason` TEXT DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Offer Redemption', ?, ?)")
                    ->execute([$parentId, $childId, -$reqPoints, "Redeemed " . $offer['title']]);
            }

            // Save redemption (create table if missing)
            try {
                $connect->prepare("INSERT INTO parent_redeemed_offers (parent_id, child_id, offer_id, points_spent) VALUES (?, ?, ?, ?)")
                    ->execute([$parentId, $childId, $offerId, $reqPoints]);
            } catch (Exception $e) {
                $connect->exec("CREATE TABLE IF NOT EXISTS `parent_redeemed_offers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY, `parent_id` INT NOT NULL, `child_id` INT NOT NULL, `offer_id` INT NOT NULL,
                    `points_spent` INT NOT NULL, `redeemed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $connect->prepare("INSERT INTO parent_redeemed_offers (parent_id, child_id, offer_id, points_spent) VALUES (?, ?, ?, ?)")
                    ->execute([$parentId, $childId, $offerId, $reqPoints]);
            }

            // Get new balance
            $stmt3 = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ?");
            $stmt3->execute([$childId]);
            $newBalance = (int)$stmt3->fetchColumn();

            $connect->commit();
            echo json_encode(['success' => true, 'new_balance' => $newBalance, 'message' => "Successfully redeemed " . $offer['title'] . "!"]);
        } catch (Exception $e) {
            $connect->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_all_badges':
        $stmt = $connect->query("SELECT name, description as `desc`, icon FROM badge ORDER BY badge_id ASC");
        echo json_encode(['success' => true, 'badges' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_badge_progress':
        if (!$childId) { echo json_encode(['error' => 'Missing child_id']); exit; }
        $progress = [];
        try {
            // Total activities completed
            $s = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1");
            $s->execute([$childId]); $totalAct = (int)$s->fetchColumn();
            // Weekly activities
            $s = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $s->execute([$childId]); $weeklyAct = (int)$s->fetchColumn();
            // Monthly activities
            $s = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $s->execute([$childId]); $monthlyAct = (int)$s->fetchColumn();
            // Growth records
            $s = $connect->prepare("SELECT COUNT(*) FROM growth_record WHERE child_id = ?");
            $s->execute([$childId]); $growthCount = (int)$s->fetchColumn();
            // Voice samples
            $speechCount = 0;
            try { $s = $connect->prepare("SELECT COUNT(*) FROM voice_sample WHERE child_id = ?"); $s->execute([$childId]); $speechCount = (int)$s->fetchColumn(); } catch(Exception $e) {}
            // Motor milestones
            $motorCount = 0;
            try { $s = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND is_achieved = 1"); $s->execute([$childId]); $motorCount = (int)$s->fetchColumn(); } catch(Exception $e) {}
            // Streak
            $streakCount = 0;
            try {
                $s = $connect->prepare("SELECT current_count FROM streaks WHERE child_id = ? AND streak_type = 'daily_login' LIMIT 1");
                $s->execute([$childId]);
                $sc = $s->fetchColumn();
                if ($sc !== false) $streakCount = (int)$sc;
            } catch(Exception $e) {}

            // Article count
            $s = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'article' AND is_completed = 1");
            $s->execute([$childId]); $articleCount = (int)$s->fetchColumn();
            
            // Game count
            $s = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'website_game' AND is_completed = 1");
            $s->execute([$childId]); $gameCount = (int)$s->fetchColumn();

            $progress = [
                'First Steps' => ['current' => min($totalAct, 1), 'needed' => 1, 'label' => 'activity completed'],
                'Rising Star' => ['current' => min($streakCount, 3), 'needed' => 3, 'label' => 'day streak'],
                'Weekly Champion' => ['current' => min($weeklyAct, 5), 'needed' => 5, 'label' => 'weekly activities'],
                'Consistency King' => ['current' => min($streakCount, 7), 'needed' => 7, 'label' => 'day streak'],
                'Growth Tracker' => ['current' => min($growthCount, 1), 'needed' => 1, 'label' => 'growth record'],
                'Health Champion' => ['current' => min($growthCount, 5), 'needed' => 5, 'label' => 'growth records'],
                'Voice Hero' => ['current' => min($speechCount, 1), 'needed' => 1, 'label' => 'voice sample'],
                'Speech Explorer' => ['current' => min($speechCount, 5), 'needed' => 5, 'label' => 'voice samples'],
                'Motor Master' => ['current' => min($motorCount, 5), 'needed' => 5, 'label' => 'motor milestones'],
                'Monthly Master' => ['current' => min($monthlyAct, 20), 'needed' => 20, 'label' => 'monthly activities'],
                'Super Parent' => ['current' => min($streakCount, 30), 'needed' => 30, 'label' => 'day streak'],
                'Article Reader' => ['current' => min($articleCount, 1), 'needed' => 1, 'label' => 'article read'],
                'Bookworm' => ['current' => min($articleCount, 10), 'needed' => 10, 'label' => 'articles read'],
                'Game Master' => ['current' => min($gameCount, 5), 'needed' => 5, 'label' => 'games played']
            ];

            // Retrospective check: if progress is met, award the badge if not already awarded
            $stmtEarned = $connect->prepare("SELECT b.name FROM child_badge cb JOIN badge b ON cb.badge_id = b.badge_id WHERE cb.child_id = ?");
            $stmtEarned->execute([$childId]);
            $earnedNames = $stmtEarned->fetchAll(PDO::FETCH_COLUMN);

            $stmtAll = $connect->query("SELECT badge_id, name FROM badge");
            $allBadges = [];
            while($b = $stmtAll->fetch(PDO::FETCH_ASSOC)) { $allBadges[$b['name']] = $b['badge_id']; }

            foreach($progress as $bName => $pData) {
                if ($pData['current'] >= $pData['needed'] && !in_array($bName, $earnedNames) && isset($allBadges[$bName])) {
                    $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)")->execute([$childId, $allBadges[$bName]]);
                    $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)")->execute([$_SESSION['id'], "Badge Earned: $bName", "Congratulations! You earned the '$bName' badge!"]);
                }
            }
        } catch (Exception $e) {
            error_log("Badge progress error: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'progress' => $progress]);
        break;

    case 'get_earned_badges':
        if (!$childId) { echo json_encode(['error' => 'Missing child_id']); exit; }
        $stmt = $connect->prepare("SELECT b.name, b.icon, b.description as `desc`, cb.redeemed_at FROM child_badge cb JOIN badge b ON cb.badge_id = b.badge_id WHERE cb.child_id = ?");
        $stmt->execute([$childId]);
        echo json_encode(['success' => true, 'badges' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'redeem':
        $input = json_decode(file_get_contents('php://input'), true);
        $childId = $input['child_id'] ?? null;
        $offerName = $input['offer_name'] ?? null;
        $pointsCost = (int)($input['points_cost'] ?? 0);

        if (!$childId || !$offerName || $pointsCost <= 0) {
            echo json_encode(['error' => 'Missing required fields (child_id, offer_name, points_cost)']);
            exit();
        }

        if (!verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Unauthorized child']);
            exit();
        }

        try {
            $connect->beginTransaction();

            // Check balance
            $stmt = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ?");
            $stmt->execute([$childId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet || $wallet['total_points'] < $pointsCost) {
                $connect->rollBack();
                echo json_encode(['error' => 'Not enough points. You have ' . ($wallet['total_points'] ?? 0) . ' but need ' . $pointsCost . '.']);
                exit();
            }

            // Deduct points
            $connect->prepare("UPDATE points_wallet SET total_points = total_points - ? WHERE wallet_id = ?")->execute([$pointsCost, $wallet['wallet_id']]);

            // Log tracking
            try {
                $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Reward Redemption', ?, ?)")
                    ->execute([$parentId, $childId, -$pointsCost, "Redeemed: $offerName"]);
            } catch (Exception $e) {
                $connect->exec("CREATE TABLE IF NOT EXISTS `parent_points_tracking` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY, `parent_id` INT NOT NULL, `child_id` INT DEFAULT NULL,
                    `action` VARCHAR(100) NOT NULL, `points` INT NOT NULL, `reason` TEXT DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Reward Redemption', ?, ?)")
                    ->execute([$parentId, $childId, -$pointsCost, "Redeemed: $offerName"]);
            }

            // Save redemption record
            try {
                $connect->prepare("INSERT INTO parent_redeemed_offers (parent_id, child_id, offer_id, points_spent) VALUES (?, ?, 0, ?)")
                    ->execute([$parentId, $childId, $pointsCost]);
            } catch (Exception $e) {
                $connect->exec("CREATE TABLE IF NOT EXISTS `parent_redeemed_offers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY, `parent_id` INT NOT NULL, `child_id` INT NOT NULL, `offer_id` INT NOT NULL DEFAULT 0,
                    `points_spent` INT NOT NULL, `redeemed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $connect->prepare("INSERT INTO parent_redeemed_offers (parent_id, child_id, offer_id, points_spent) VALUES (?, ?, 0, ?)")
                    ->execute([$parentId, $childId, $pointsCost]);
            }

            // Notification
            try {
                $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)")
                    ->execute([$parentId, 'Reward Redeemed!', "You redeemed '$offerName' for $pointsCost points."]);
            } catch (Exception $e) { /* silent */ }

            // Get new balance
            $stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ?");
            $stmt->execute([$childId]);
            $newBalance = (int)$stmt->fetchColumn();

            $connect->commit();
            echo json_encode(['success' => true, 'new_balance' => $newBalance, 'message' => "Successfully redeemed $offerName!"]);
        } catch (Exception $e) {
            $connect->rollBack();
            echo json_encode(['error' => 'Failed to redeem: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: get_balance, get_history, redeem_token, get_tokens, get_offers, redeem_offer, redeem, get_badge_progress, get_earned_badges']);
}
?>

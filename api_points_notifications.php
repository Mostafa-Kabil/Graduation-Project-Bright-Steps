<?php
/**
 * Bright Steps - Points Notifications & Alerts API
 * Handles milestone alerts, cap warnings, expiration reminders, and promotional notifications
 */
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once "connection.php";
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        // Get points-related notifications for authenticated user
        if ($action === 'list') {
            if (!isset($_SESSION['id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            $userId = $_SESSION['id'];
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

            $stmt = $connect->prepare("
                SELECT * FROM notifications
                WHERE user_id = ? AND type IN ('points', 'appointment')
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $unreadCount = 0;
            foreach ($notifications as $n) {
                if (!$n['is_read']) $unreadCount++;
            }

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);

        // Get parent's points milestones
        } elseif ($action === 'milestones') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Parent access required']);
                exit();
            }

            $parentId = $_SESSION['id'];

            try {
                $stmt = $connect->prepare("
                    SELECT milestone_type, milestone_value, milestone_name, badge_icon, achieved_at
                    FROM parent_points_milestones
                    WHERE parent_id = ?
                    ORDER BY milestone_value DESC, achieved_at DESC
                ");
                $stmt->execute([$parentId]);
                $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Table may not exist yet - return empty
                $milestones = [];
            }

            // Next milestone suggestions
            $totalEarned = 0;
            try {
                $stmt = $connect->prepare("SELECT lifetime_earned FROM parent_points_wallet WHERE parent_id = ?");
                $stmt->execute([$parentId]);
                $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalEarned = $wallet['lifetime_earned'] ?? $wallet['total_points'] ?? 0;
            } catch (Exception $e) {
                $totalEarned = 0;
            }

            $nextMilestones = [];
            $milestoneThresholds = [100, 500, 1000, 5000, 10000];

            foreach ($milestoneThresholds as $threshold) {
                if ($totalEarned < $threshold) {
                    $nextMilestones[] = [
                        'milestone_value' => $threshold,
                        'milestone_name' => "Points Master - {$threshold} Points",
                        'points_needed' => $threshold - $totalEarned
                    ];
                    break; // Only show next immediate milestone
                }
            }

            echo json_encode([
                'success' => true,
                'milestones' => $milestones,
                'total_lifetime_earned' => $totalEarned,
                'next_milestone' => $nextMilestones[0] ?? null
            ]);

        // Get expiring redemptions/tokens
        } elseif ($action === 'expiring') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Parent access required']);
                exit();
            }

            $parentId = $_SESSION['id'];
            $daysThreshold = isset($_GET['days']) ? (int) $_GET['days'] : 7;

            // Expiring tokens
            $stmt = $connect->prepare("
                SELECT at.*, rc.item_name, rc.icon,
                       DATEDIFF(at.expires_at, CURDATE()) as days_until_expiry
                FROM appointment_tokens at
                LEFT JOIN parent_redemptions pr ON at.redemption_id = pr.redemption_id
                LEFT JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE at.parent_id = ?
                  AND at.status = 'available'
                  AND at.expires_at <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY at.expires_at ASC
            ");
            $stmt->execute([$parentId, $daysThreshold]);
            $expiringTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Expiring redemptions
            $stmt = $connect->prepare("
                SELECT pr.*, rc.item_name, rc.icon,
                       DATEDIFF(pr.expires_at, CURDATE()) as days_until_expiry
                FROM parent_redemptions pr
                INNER JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE pr.parent_id = ?
                  AND pr.status = 'active'
                  AND pr.expires_at <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY pr.expires_at ASC
            ");
            $stmt->execute([$parentId, $daysThreshold]);
            $expiringRedemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'expiring_tokens' => $expiringTokens,
                'expiring_redemptions' => $expiringRedemptions,
                'total_expiring' => count($expiringTokens) + count($expiringRedemptions)
            ]);

        // Admin: Get all points notifications to send
        } elseif ($action === 'admin_list') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
                http_response_code(401);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            $stmt = $connect->query("
                SELECT * FROM admin_notifications
                WHERE notification_type = 'points'
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // Create a points notification for current user
        if ($action === 'create') {
            if (!isset($_SESSION['id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            $userId = $_SESSION['id'];
            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            $priority = $input['priority'] ?? 'normal'; // normal, high, urgent

            if (!$title || !$message) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and message required']);
                exit();
            }

            $stmt = $connect->prepare("
                INSERT INTO notifications (user_id, type, title, message, priority)
                VALUES (?, 'points', ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $message, $priority]);

            echo json_encode([
                'success' => true,
                'notification_id' => $connect->lastInsertId()
            ]);

        // Admin: Send bulk points notification
        } elseif ($action === 'broadcast') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
                http_response_code(401);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            $targetAudience = $input['target_audience'] ?? 'all'; // all, top_earners, low_balance, inactive

            if (!$title || !$message) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and message required']);
                exit();
            }

            $adminId = $_SESSION['id'];
            $sentCount = 0;

            // Determine target users
            if ($targetAudience === 'all') {
                $stmt = $connect->query("SELECT parent_id FROM parent");
                $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetAudience === 'top_earners') {
                $stmt = $connect->query("
                    SELECT parent_id FROM parent_points_wallet
                    WHERE lifetime_earned >= 500
                ");
                $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetAudience === 'low_balance') {
                $stmt = $connect->query("
                    SELECT p.parent_id FROM parent p
                    LEFT JOIN parent_points_wallet ppw ON p.parent_id = ppw.parent_id
                    WHERE ppw.total_points < 100 OR ppw.total_points IS NULL
                ");
                $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetAudience === 'inactive') {
                $stmt = $connect->query("
                    SELECT p.parent_id FROM parent p
                    LEFT JOIN parent_points_wallet ppw ON p.parent_id = ppw.parent_id
                    WHERE ppw.last_earned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                    OR ppw.last_earned_at IS NULL
                ");
                $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid target_audience']);
                exit();
            }

            // Send notifications
            $stmt = $connect->prepare("
                INSERT INTO notifications (user_id, type, title, message)
                VALUES (?, 'points', ?, ?)
            ");

            foreach ($targets as $parentId) {
                try {
                    $stmt->execute([$parentId, $title, $message]);
                    $sentCount++;
                } catch (Exception $e) {
                    // Continue on error
                }
            }

            // Log broadcast
            $stmt = $connect->prepare("
                INSERT INTO admin_notifications (admin_id, notification_type, title, message, target_audience, recipients_count)
                VALUES (?, 'points', ?, ?, ?, ?)
            ");
            $stmt->execute([$adminId, $title, $message, $targetAudience, $sentCount]);

            echo json_encode([
                'success' => true,
                'sent_count' => $sentCount,
                'target_audience' => $targetAudience
            ]);

        // Trigger milestone check and notification
        } elseif ($action === 'check_milestone') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Parent access required']);
                exit();
            }

            $parentId = $_SESSION['id'];

            // Get lifetime earned
            $stmt = $connect->prepare("SELECT lifetime_earned FROM parent_points_wallet WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
            $lifetimeEarned = $wallet['lifetime_earned'] ?? 0;

            $milestoneThresholds = [
                100 => ['name' => 'Points Beginner', 'icon' => '🥉'],
                500 => ['name' => 'Points Collector', 'icon' => '🥈'],
                1000 => ['name' => 'Points Master', 'icon' => '🥇'],
                5000 => ['name' => 'Points Legend', 'icon' => '🏆'],
                10000 => ['name' => 'Points Champion', 'icon' => '👑']
            ];

            $newMilestones = [];

            foreach ($milestoneThresholds as $threshold => $info) {
                // Check if milestone reached but not yet recorded
                if ($lifetimeEarned >= $threshold) {
                    $stmt = $connect->prepare("
                        SELECT * FROM parent_points_milestones
                        WHERE parent_id = ? AND milestone_type = 'earned_total' AND milestone_value = ?
                    ");
                    $stmt->execute([$parentId, $threshold]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        // Record milestone
                        $stmt = $connect->prepare("
                            INSERT INTO parent_points_milestones
                            (parent_id, milestone_type, milestone_value, milestone_name, badge_icon)
                            VALUES (?, 'earned_total', ?, ?, ?)
                        ");
                        $stmt->execute([$parentId, $threshold, $info['name'], $info['icon']]);

                        // Create notification
                        $stmt = $connect->prepare("
                            INSERT INTO notifications (user_id, type, title, message)
                            VALUES (?, 'points', ?, ?)
                        ");
                        $title = "Milestone Reached! {$info['icon']}";
                        $message = "Congratulations! You've reached the '{$info['name']}' milestone with {$lifetimeEarned} total points earned!";
                        $stmt->execute([$parentId, $title, $message]);

                        $newMilestones[] = $info;
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'lifetime_earned' => $lifetimeEarned,
                'new_milestones' => $newMilestones,
                'milestones_count' => count($newMilestones)
            ]);

        // Send cap warning notification
        } elseif ($action === 'cap_warning') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Parent access required']);
                exit();
            }

            $parentId = $_SESSION['id'];
            $actionKey = $input['action_key'] ?? '';
            $capType = $input['cap_type'] ?? 'daily'; // daily or weekly
            $earned = (int) ($input['earned'] ?? 0);
            $cap = (int) ($input['cap'] ?? 0);

            if (!$actionKey || $capType === '' || $earned === 0 || $cap === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit();
            }

            $percentage = round(($earned / $cap) * 100);

            $stmt = $connect->prepare("
                INSERT INTO notifications (user_id, type, title, message)
                VALUES (?, 'points', ?, ?)
            ");

            if ($percentage >= 100) {
                $title = "Daily Cap Reached ⏰";
                $message = "You've reached your {$capType} points limit for this action. Come back tomorrow for more!";
            } elseif ($percentage >= 80) {
                $title = "Almost at Cap! ⚠️";
                $message = "You've earned {$earned}/{$cap} points ({$percentage}%) for this action today. {$cap - $earned} points remaining!";
            } else {
                $title = "Points Progress 📊";
                $message = "You've earned {$earned}/{$cap} points for this action today.";
            }

            $stmt->execute([$parentId, $title, $message]);

            echo json_encode([
                'success' => true,
                'notification_sent' => true,
                'percentage' => $percentage
            ]);

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

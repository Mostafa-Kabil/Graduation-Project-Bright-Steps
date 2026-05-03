<?php
/**
 * Bright Steps – Motor Milestones API
 * Manages child motor skill milestone tracking.
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

// Verify child belongs to parent
function verifyChild($connect, $childId, $parentId) {
    $stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
    $stmt->execute([$childId, $parentId]);
    return $stmt->fetch() !== false;
}

switch ($action) {

    case 'list':
        $childId = $_GET['child_id'] ?? '';
        if (!$childId || !verifyChild($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }

        try {
            $stmt = $connect->prepare(
                "SELECT * FROM motor_milestones WHERE child_id = ? ORDER BY category, milestone_name"
            );
            $stmt->execute([$childId]);
            $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no milestones exist yet, seed with defaults based on age
            if (empty($milestones)) {
                $defaults = [
                    ['Lifts head when on tummy', 'gross_motor'],
                    ['Rolls over (tummy to back)', 'gross_motor'],
                    ['Sits without support', 'gross_motor'],
                    ['Crawls', 'gross_motor'],
                    ['Pulls to stand', 'gross_motor'],
                    ['Walks with support', 'gross_motor'],
                    ['Walks independently', 'gross_motor'],
                    ['Runs', 'gross_motor'],
                    ['Kicks a ball', 'gross_motor'],
                    ['Jumps with both feet', 'gross_motor'],
                    ['Grasps objects', 'fine_motor'],
                    ['Transfers objects hand to hand', 'fine_motor'],
                    ['Pincer grasp (thumb + finger)', 'fine_motor'],
                    ['Stacks 2-3 blocks', 'fine_motor'],
                    ['Scribbles with crayon', 'fine_motor'],
                    ['Turns pages in a book', 'fine_motor'],
                    ['Uses spoon/fork', 'fine_motor'],
                    ['Draws a circle', 'fine_motor'],
                ];

                $insertStmt = $connect->prepare(
                    "INSERT INTO motor_milestones (child_id, milestone_name, category) VALUES (?, ?, ?)"
                );
                foreach ($defaults as $d) {
                    $insertStmt->execute([$childId, $d[0], $d[1]]);
                }

                // Re-fetch
                $stmt->execute([$childId]);
                $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(['success' => true, 'milestones' => $milestones]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'toggle':
        $input = json_decode(file_get_contents('php://input'), true);
        $milestoneId = $input['milestone_id'] ?? '';
        $isAchieved = $input['is_achieved'] ?? 0;
        $childId = $input['child_id'] ?? '';

        if (!$childId || !verifyChild($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }

        try {
            $achievedAt = $isAchieved ? date('Y-m-d H:i:s') : null;
            $stmt = $connect->prepare(
                "UPDATE motor_milestones SET is_achieved = ?, achieved_at = ? WHERE id = ? AND child_id = ?"
            );
            $stmt->execute([$isAchieved ? 1 : 0, $achievedAt, $milestoneId, $childId]);

            // Award points for achieving a milestone (30 points per log_milestone rule)
            // Parents can earn points for each milestone checked, up to daily cap (3 milestones = 90 pts)
            $pointsMessage = "";
            if ($isAchieved) {
                $today = date('Y-m-d');
                $weekStart = date('Y-m-d', strtotime('monday this week'));

                // Check/create wallet
                $walletStmt = $connect->prepare("SELECT wallet_id, total_points FROM parent_points_wallet WHERE parent_id = ?");
                $walletStmt->execute([$parentId]);
                $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);

                if (!$wallet) {
                    $walletStmt = $connect->prepare("INSERT INTO parent_points_wallet (parent_id, total_points, last_earned_at) VALUES (?, 0, NOW())");
                    $walletStmt->execute([$parentId]);
                    $walletId = $connect->lastInsertId();
                } else {
                    $walletId = $wallet['wallet_id'];
                }

                // Check daily cap (90 points/day for log_milestone = ~3 milestones)
                $dailyCapStmt = $connect->prepare("
                    SELECT COALESCE(SUM(points_earned), 0) as daily_total
                    FROM parent_points_tracking
                    WHERE parent_id = ? AND action_key = 'log_milestone' AND earned_date = ?
                ");
                $dailyCapStmt->execute([$parentId, $today]);
                $dailyTotal = $dailyCapStmt->fetchColumn();

                // Check weekly cap (300 points/week for log_milestone = ~10 milestones)
                $weeklyCapStmt = $connect->prepare("
                    SELECT COALESCE(SUM(points_earned), 0) as weekly_total
                    FROM parent_points_tracking
                    WHERE parent_id = ? AND action_key = 'log_milestone' AND earned_date >= ?
                ");
                $weeklyCapStmt->execute([$parentId, $weekStart]);
                $weeklyTotal = $weeklyCapStmt->fetchColumn();

                // Get rule caps
                $ruleStmt = $connect->prepare("SELECT daily_cap, weekly_cap, points_value FROM points_earning_rules WHERE action_key = 'log_milestone'");
                $ruleStmt->execute();
                $rule = $ruleStmt->fetch(PDO::FETCH_ASSOC);

                $dailyCap = (int) $rule['daily_cap'];
                $weeklyCap = (int) $rule['weekly_cap'];
                $pointsValue = (int) $rule['points_value'];
                $pointsToAward = 0;

                // Calculate remaining points before caps
                $remainingDaily = $dailyCap - $dailyTotal;
                $remainingWeekly = $weeklyCap - $weeklyTotal;

                // Award points if within caps (can earn for each milestone until cap reached)
                if ($remainingDaily >= $pointsValue && $remainingWeekly >= $pointsValue) {
                    $pointsToAward = $pointsValue;

                    // Update wallet balance and lifetime earned
                    $updateWallet = $connect->prepare("UPDATE parent_points_wallet SET total_points = total_points + ?, lifetime_earned = lifetime_earned + ?, last_earned_at = NOW() WHERE wallet_id = ?");
                    $updateWallet->execute([$pointsToAward, $pointsToAward, $walletId]);

                    // Track the transaction (aggregate for the day)
                    $trackStmt = $connect->prepare("
                        INSERT INTO parent_points_tracking (parent_id, action_key, points_earned, earned_date, week_start_date)
                        VALUES (?, 'log_milestone', ?, ?, ?)
                        ON DUPLICATE KEY UPDATE points_earned = points_earned + VALUES(points_earned)
                    ");
                    $trackStmt->execute([$parentId, $pointsToAward, $today, $weekStart]);

                    // Create notification
                    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                    $nstmt->execute([$parentId, 'Points Earned!',
                                     "You earned {$pointsToAward} points for logging a milestone achievement."]);
                    $pointsMessage = " +{$pointsToAward} points";
                } elseif ($remainingDaily <= 0 || $remainingWeekly <= 0) {
                    $pointsMessage = " (Daily/weekly cap reached - come back tomorrow!)";
                } else {
                    // Partial points remaining (not enough for full milestone)
                    $pointsMessage = " (Not enough points remaining before cap)";
                }
            }

            echo json_encode([
                'success' => true,
                'points_awarded' => $isAchieved ? $pointsToAward : 0,
                'message' => $isAchieved ? 'Milestone saved!' . $pointsMessage : 'Milestone updated'
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>

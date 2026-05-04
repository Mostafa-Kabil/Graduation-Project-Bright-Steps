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

            // Check for category completion bonus (+20 pts)
            $newBadges = [];
            if ($isAchieved) {
                $catStmt = $connect->prepare("SELECT category FROM motor_milestones WHERE id = ?");
                $catStmt->execute([$milestoneId]);
                $cat = $catStmt->fetchColumn();
                if ($cat) {
                    $totalStmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = ?");
                    $totalStmt->execute([$childId, $cat]);
                    $totalInCat = (int)$totalStmt->fetchColumn();
                    $doneStmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = ? AND is_achieved = 1");
                    $doneStmt->execute([$childId, $cat]);
                    $doneInCat = (int)$doneStmt->fetchColumn();
                    if ($doneInCat === $totalInCat && $totalInCat > 0) {
                        $bonusPts = 20;
                        $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE child_id = ?")->execute([$bonusPts, $childId]);
                        try {
                            $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Motor Checklist Complete', ?, ?)")->execute([$parentId, $childId, $bonusPts, "Completed all $cat milestones"]);
                        } catch (Exception $e2) { /* table may not exist yet */ }
                    }
                }

                // Log individual milestone points
                try {
                    $connect->prepare("INSERT INTO parent_points_tracking (parent_id, child_id, action, points, reason) VALUES (?, ?, 'Motor Milestone', ?, ?)")->execute([$parentId, $childId, 15, "Achieved motor milestone"]);
                } catch (Exception $e2) { /* table may not exist yet */ }

                // Check Motor Master badge (5+ milestones achieved)
                $totalAchieved = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND is_achieved = 1");
                $totalAchieved->execute([$childId]);
                if ((int)$totalAchieved->fetchColumn() >= 5) {
                    $bStmt = $connect->prepare("SELECT badge_id FROM badge WHERE name = 'Motor Master'");
                    $bStmt->execute();
                    $badgeId = $bStmt->fetchColumn();
                    if ($badgeId) {
                        $chk = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?");
                        $chk->execute([$childId, $badgeId]);
                        if ($chk->fetchColumn() == 0) {
                            $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)")->execute([$childId, $badgeId]);
                            $newBadges[] = 'Motor Master';
                            $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)")->execute([$parentId, 'Badge Earned: Motor Master', 'You achieved 5 motor milestones! Great progress!']);
                        }
                    }
                }
            }

            echo json_encode(['success' => true, 'points_awarded' => $isAchieved ? 15 : 0, 'new_badges' => $newBadges]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'stats':
        $childId = $_GET['child_id'] ?? '';
        if (!$childId || !verifyChild($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }

        try {
            // Gross motor stats
            $stmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = 'gross_motor'");
            $stmt->execute([$childId]);
            $grossTotal = (int)$stmt->fetchColumn();
            $stmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = 'gross_motor' AND is_achieved = 1");
            $stmt->execute([$childId]);
            $grossDone = (int)$stmt->fetchColumn();

            // Fine motor stats
            $stmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = 'fine_motor'");
            $stmt->execute([$childId]);
            $fineTotal = (int)$stmt->fetchColumn();
            $stmt = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = ? AND category = 'fine_motor' AND is_achieved = 1");
            $stmt->execute([$childId]);
            $fineDone = (int)$stmt->fetchColumn();

            // Per-milestone detail for chart
            $stmt = $connect->prepare("SELECT milestone_name, category, is_achieved, achieved_at FROM motor_milestones WHERE child_id = ? ORDER BY category, milestone_name");
            $stmt->execute([$childId]);
            $allMilestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Achievement timeline (last 6 months)
            $stmt = $connect->prepare("
                SELECT DATE_FORMAT(achieved_at, '%Y-%m') AS month, COUNT(*) AS count
                FROM motor_milestones
                WHERE child_id = ? AND is_achieved = 1 AND achieved_at IS NOT NULL
                GROUP BY DATE_FORMAT(achieved_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 6
            ");
            $stmt->execute([$childId]);
            $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'gross_motor' => ['total' => $grossTotal, 'achieved' => $grossDone, 'pct' => $grossTotal > 0 ? round(($grossDone / $grossTotal) * 100) : 0],
                'fine_motor' => ['total' => $fineTotal, 'achieved' => $fineDone, 'pct' => $fineTotal > 0 ? round(($fineDone / $fineTotal) * 100) : 0],
                'milestones' => $allMilestones,
                'timeline' => array_reverse($timeline)
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: list, toggle, stats']);
}
?>

<?php
/**
 * Bright Steps – Streaks & Badges API
 * Manages daily login streaks, weekly/monthly activity streaks, and badge awarding.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Get all streak & badge data for a child ──────────────
    case 'get':
        $childId = $_GET['child_id'] ?? null;
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        // Get streaks (parent-level)
        $stmt = $connect->prepare(
            "SELECT streak_type, current_count, longest_count, last_activity_date FROM streaks WHERE parent_id = ?"
        );
        $stmt->execute([$userId]);
        $streaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $streakMap = [];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        foreach ($streaks as $s) {
            if ($s['streak_type'] === 'daily_login') {
                if ($s['last_activity_date'] !== $today && $s['last_activity_date'] !== $yesterday) {
                    $s['current_count'] = 0;
                }
            }
            $streakMap[$s['streak_type']] = $s;
        }

        // Get badges earned
        $stmt2 = $connect->prepare(
            "SELECT b.badge_id, b.name, b.description, b.icon, cb.redeemed_at
             FROM child_badge cb
             INNER JOIN badge b ON cb.badge_id = b.badge_id
             WHERE cb.child_id = ?
             ORDER BY cb.redeemed_at DESC"
        );
        $stmt2->execute([$childId]);
        $badges = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Get activity counts for this week and month
        $stmt3 = $connect->prepare(
            "SELECT 
                COUNT(CASE WHEN completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND is_completed = 1 THEN 1 END) AS weekly_completed,
                COUNT(CASE WHEN completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND is_completed = 1 THEN 1 END) AS monthly_completed
             FROM child_activities WHERE child_id = ?"
        );
        $stmt3->execute([$childId]);
        $counts = $stmt3->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'streaks' => $streakMap,
            'badges' => $badges,
            'badge_count' => count($badges),
            'weekly_activities' => (int)($counts['weekly_completed'] ?? 0),
            'monthly_activities' => (int)($counts['monthly_completed'] ?? 0)
        ]);
        break;

    // ── Daily check-in: update streak, check badge eligibility ──
    case 'check-in':
        $input = json_decode(file_get_contents('php://input'), true);
        $childId = $input['child_id'] ?? null;
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Get or create daily_login streak for PARENT
        $stmt = $connect->prepare(
            "SELECT streak_id, current_count, longest_count, last_activity_date
             FROM streaks WHERE parent_id = ? AND streak_type = 'daily_login'"
        );
        $stmt->execute([$userId]);
        $streak = $stmt->fetch(PDO::FETCH_ASSOC);

        $newCount = 1;
        $newLongest = 1;
        $newBadges = [];

        if ($streak) {
            if ($streak['last_activity_date'] === $today) {
                // Already checked in today, just keep current values for badge checks
                $newCount = (int)$streak['current_count'];
                $newLongest = (int)$streak['longest_count'];
            } else {
                if ($streak['last_activity_date'] === $yesterday) {
                    $newCount = $streak['current_count'] + 1;
                } else {
                    $newCount = 1; // streak broken
                }
                $newLongest = max($streak['longest_count'], $newCount);

                $stmt2 = $connect->prepare(
                    "UPDATE streaks SET current_count = ?, longest_count = ?, last_activity_date = ? 
                     WHERE streak_id = ?"
                );
                $stmt2->execute([$newCount, $newLongest, $today, $streak['streak_id']]);
            }
        } else {
            $stmt2 = $connect->prepare(
                "INSERT INTO streaks (parent_id, child_id, streak_type, current_count, longest_count, last_activity_date)
                 VALUES (?, ?, 'daily_login', 1, 1, ?)"
            );
            $stmt2->execute([$userId, $childId, $today]);
        }

        // Check badge eligibility based on streak count
        $badgeRules = [
            3 => 'Rising Star',
            7 => 'Consistency King',
            30 => 'Super Parent'
        ];

        // Helper to check and award badge
        $awardBadge = function($badgeName) use ($connect, $childId, $userId, &$newBadges) {
            $stmt = $connect->prepare("SELECT badge_id FROM badge WHERE name = ?");
            $stmt->execute([$badgeName]);
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($badge) {
                $stmt2 = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?");
                $stmt2->execute([$childId, $badge['badge_id']]);
                if ($stmt2->fetchColumn() == 0) {
                    $stmt3 = $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)");
                    $stmt3->execute([$childId, $badge['badge_id']]);
                    $newBadges[] = $badgeName;
                    $stmt4 = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)");
                    $stmt4->execute([$userId, "🏆 Badge Earned: $badgeName", "Congratulations! You earned the `$badgeName` badge!"]);
                }
            }
        };

        // Streak badges
        $badgeRules = [
            3 => 'Rising Star',
            7 => 'Consistency King',
            30 => 'Super Parent'
        ];
        foreach ($badgeRules as $threshold => $badgeName) {
            if ($newCount >= $threshold) {
                $awardBadge($badgeName);
            }
        }

        // Activity badges
        $stmt7 = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1");
        $stmt7->execute([$childId]);
        $totalActivities = (int)$stmt7->fetchColumn();

        $stmt7 = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt7->execute([$childId]);
        $weeklyCount = (int)$stmt7->fetchColumn();

        $stmt8 = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt8->execute([$childId]);
        $monthlyCount = (int)$stmt8->fetchColumn();

        if ($totalActivities >= 1) $awardBadge('First Steps');
        if ($weeklyCount >= 5) $awardBadge('Weekly Champion');
        if ($monthlyCount >= 20) $awardBadge('Monthly Master');

        // Growth badges
        $stmtGrowth = $connect->prepare("SELECT COUNT(*) FROM growth_record WHERE child_id = ?");
        $stmtGrowth->execute([$childId]);
        if ((int)$stmtGrowth->fetchColumn() >= 5) $awardBadge('Growth Tracker');

        echo json_encode([
            'success' => true,
            'current_streak' => $newCount,
            'longest_streak' => $newLongest,
            'new_badges' => $newBadges,
            'weekly_activities' => $weeklyCount,
            'monthly_activities' => $monthlyCount
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: get, check-in']);
        break;
}

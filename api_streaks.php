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

        // Get streaks
        $stmt = $connect->prepare(
            "SELECT streak_type, current_count, longest_count, last_activity_date FROM streaks WHERE child_id = ?"
        );
        $stmt->execute([$childId]);
        $streaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $streakMap = [];
        foreach ($streaks as $s) {
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

        // Get or create daily_login streak
        $stmt = $connect->prepare(
            "SELECT streak_id, current_count, longest_count, last_activity_date
             FROM streaks WHERE child_id = ? AND streak_type = 'daily_login'"
        );
        $stmt->execute([$childId]);
        $streak = $stmt->fetch(PDO::FETCH_ASSOC);

        $newCount = 1;
        $newLongest = 1;
        $newBadges = [];

        if ($streak) {
            if ($streak['last_activity_date'] === $today) {
                // Already checked in today
                echo json_encode([
                    'success' => true,
                    'message' => 'Already checked in today',
                    'current_streak' => (int)$streak['current_count'],
                    'longest_streak' => (int)$streak['longest_count'],
                    'new_badges' => []
                ]);
                exit();
            }

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
        } else {
            $stmt2 = $connect->prepare(
                "INSERT INTO streaks (child_id, streak_type, current_count, longest_count, last_activity_date)
                 VALUES (?, 'daily_login', 1, 1, ?)"
            );
            $stmt2->execute([$childId, $today]);
        }

        // Check badge eligibility based on streak count
        $badgeRules = [
            3 => 'Rising Star',
            7 => 'Consistency King',
            30 => 'Super Parent'
        ];

        foreach ($badgeRules as $threshold => $badgeName) {
            if ($newCount >= $threshold) {
                // Check if badge exists and not already awarded
                $stmt3 = $connect->prepare("SELECT badge_id FROM badge WHERE name = ?");
                $stmt3->execute([$badgeName]);
                $badge = $stmt3->fetch(PDO::FETCH_ASSOC);

                if ($badge) {
                    $stmt4 = $connect->prepare(
                        "SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?"
                    );
                    $stmt4->execute([$childId, $badge['badge_id']]);
                    if ($stmt4->fetchColumn() == 0) {
                        $stmt5 = $connect->prepare(
                            "INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)"
                        );
                        $stmt5->execute([$childId, $badge['badge_id']]);
                        $newBadges[] = $badgeName;

                        // Create notification for badge earned
                        $stmt6 = $connect->prepare(
                            "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)"
                        );
                        $stmt6->execute([
                            $userId,
                            "🏆 Badge Earned: $badgeName",
                            "Congratulations! You earned the \"$badgeName\" badge for maintaining a {$newCount}-day streak!"
                        ]);
                    }
                }
            }
        }

        // Check weekly activity badge
        $stmt7 = $connect->prepare(
            "SELECT COUNT(*) FROM child_activities 
             WHERE child_id = ? AND is_completed = 1 
             AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );
        $stmt7->execute([$childId]);
        $weeklyCount = (int)$stmt7->fetchColumn();

        if ($weeklyCount >= 5) {
            $stmt3 = $connect->prepare("SELECT badge_id FROM badge WHERE name = 'Weekly Champion'");
            $stmt3->execute();
            $badge = $stmt3->fetch(PDO::FETCH_ASSOC);
            if ($badge) {
                $stmt4 = $connect->prepare(
                    "SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?"
                );
                $stmt4->execute([$childId, $badge['badge_id']]);
                if ($stmt4->fetchColumn() == 0) {
                    $stmt5 = $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)");
                    $stmt5->execute([$childId, $badge['badge_id']]);
                    $newBadges[] = 'Weekly Champion';
                }
            }
        }

        // Check monthly activity badge
        $stmt8 = $connect->prepare(
            "SELECT COUNT(*) FROM child_activities 
             WHERE child_id = ? AND is_completed = 1 
             AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt8->execute([$childId]);
        $monthlyCount = (int)$stmt8->fetchColumn();

        if ($monthlyCount >= 20) {
            $stmt3 = $connect->prepare("SELECT badge_id FROM badge WHERE name = 'Monthly Master'");
            $stmt3->execute();
            $badge = $stmt3->fetch(PDO::FETCH_ASSOC);
            if ($badge) {
                $stmt4 = $connect->prepare(
                    "SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?"
                );
                $stmt4->execute([$childId, $badge['badge_id']]);
                if ($stmt4->fetchColumn() == 0) {
                    $stmt5 = $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)");
                    $stmt5->execute([$childId, $badge['badge_id']]);
                    $newBadges[] = 'Monthly Master';
                }
            }
        }

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

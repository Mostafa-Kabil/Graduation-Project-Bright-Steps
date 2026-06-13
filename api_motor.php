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

            // Award points for achieving a milestone
            $pointsMessage = "";
            $pointsToAward = 0;
            if ($isAchieved) {
                require_once 'api_points_engine.php';
                $awardResult = award_points_from_rule($connect, $childId, $parentId, 'log_milestone');
                
                if ($awardResult['success']) {
                    $pointsToAward = $awardResult['points_awarded'];
                    $pointsMessage = " +{$pointsToAward} points";
                } else if (isset($awardResult['error']) && $awardResult['error'] === 'cooldown') {
                    $pointsMessage = " (On cooldown: {$awardResult['minutes_remaining']}m remaining)";
                } else if (isset($awardResult['error'])) {
                    $pointsMessage = " (Points error: " . $awardResult['error'] . ")";
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
                        require_once 'api_points_engine.php';
                        awardPoints($connect, $parentId, $childId, 'Motor Checklist Complete', 20, "Completed all $cat milestones");
                    }
                }
            }

            echo json_encode([
                'success' => true, 
                'points_awarded' => $pointsToAward, 
                'new_badges' => $newBadges, 
                'message' => 'Status updated successfully' . $pointsMessage,
                'cooldown_active' => isset($awardResult['error']) && $awardResult['error'] === 'cooldown',
                'minutes_remaining' => $awardResult['minutes_remaining'] ?? 0
            ]);
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

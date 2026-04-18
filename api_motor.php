<?php
/**
 * Bright Steps – Motor Milestones API
 * Manages child motor skill milestone tracking.
 */
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
            if ($isAchieved) {
                $pointsToAward = 15;
                $walletStmt = $connect->prepare("SELECT wallet_id, total_points FROM points_wallet WHERE child_id = ?");
                $walletStmt->execute([$childId]);
                $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);

                if (!$wallet) {
                    $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (?, ?)")->execute([$childId, $pointsToAward]);
                } else {
                    $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE wallet_id = ?")->execute([$pointsToAward, $wallet['wallet_id']]);
                }
            }

            echo json_encode(['success' => true, 'points_awarded' => $isAchieved ? 15 : 0]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>

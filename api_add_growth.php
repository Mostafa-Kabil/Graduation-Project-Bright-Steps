<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$parentId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$childId = $_POST['child_id'] ?? '';
$recordId = $_POST['record_id'] ?? '';
$weight = (isset($_POST['weight']) && $_POST['weight'] !== '') ? (float) $_POST['weight'] : null;
$height = (isset($_POST['height']) && $_POST['height'] !== '') ? (float) $_POST['height'] : null;
$headCirc = (isset($_POST['head_circumference']) && $_POST['head_circumference'] !== '') ? (float) $_POST['head_circumference'] : null;

if (!$childId) {
    echo json_encode(['error' => 'Child ID is required']);
    exit();
}

if ($weight === null && $height === null && $headCirc === null) {
    echo json_encode(['error' => 'At least one measurement is required']);
    exit();
}

// Verify child belongs to parent
$stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Invalid child selected or access denied']);
    exit();
}

try {
    $connect->beginTransaction();

    // Weekly limit: only allow one new measurement per 7 days (edits are still allowed)
    if (!$recordId) {
        $stmtWeek = $connect->prepare(
            "SELECT recorded_at FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 1"
        );
        $stmtWeek->execute([$childId]);
        $lastRecord = $stmtWeek->fetch(PDO::FETCH_ASSOC);
        if ($lastRecord) {
            $lastDate = new DateTime($lastRecord['recorded_at']);
            $now = new DateTime();
            $diff = $now->diff($lastDate)->days;
            if ($diff < 7) {
                $daysLeft = 7 - $diff;
                $connect->rollBack();
                echo json_encode(['error' => "You can only log measurements once per week. Please wait $daysLeft more day" . ($daysLeft > 1 ? 's' : '') . "."]);
                exit();
            }
        }
    }

    if ($recordId) {
        $stmtPrev = $connect->prepare("SELECT * FROM growth_record WHERE record_id = :rid AND child_id = :cid");
        $stmtPrev->execute(['rid' => $recordId, 'cid' => $childId]);
        $curr = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        if (!$curr) { throw new Exception("Record not found."); }
        
        if ($height === null) $height = $curr['height'];
        if ($weight === null) $weight = $curr['weight'];
        if ($headCirc === null) $headCirc = $curr['head_circumference'];

        $stmt = $connect->prepare("UPDATE growth_record SET height = :h, weight = :w, head_circumference = :hc WHERE record_id = :rid");
        $stmt->execute(['rid' => $recordId, 'h' => $height, 'w' => $weight, 'hc' => $headCirc]);
        $pointsToAward = 0; // No points for editing
        $pointsMessage = "";
    } else {
        // Fetch latest known values to carry over if a field is omitted for new record
        $stmtPrev = $connect->prepare("
            SELECT 
                (SELECT height FROM growth_record WHERE child_id = :cid AND height IS NOT NULL ORDER BY recorded_at DESC LIMIT 1) as lh,
                (SELECT weight FROM growth_record WHERE child_id = :cid AND weight IS NOT NULL ORDER BY recorded_at DESC LIMIT 1) as lw,
                (SELECT head_circumference FROM growth_record WHERE child_id = :cid AND head_circumference IS NOT NULL ORDER BY recorded_at DESC LIMIT 1) as lhc
        ");
        $stmtPrev->execute(['cid' => $childId]);
        $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

        if ($prev) {
            if ($height === null) $height = $prev['lh'];
            if ($weight === null) $weight = $prev['lw'];
            if ($headCirc === null) $headCirc = $prev['lhc'];
        }

        // 1. Insert Growth Record
        $stmt = $connect->prepare("INSERT INTO growth_record (child_id, height, weight, head_circumference) VALUES (:cid, :h, :w, :hc)");
        $stmt->execute([
            'cid' => $childId,
            'h' => $height,
            'w' => $weight,
            'hc' => $headCirc
        ]);

        require_once 'api_points_engine.php';
        $awardResult = award_points_from_rule($connect, $childId, $parentId, 'log_growth');
        
        $pointsToAward = 0;
        $pointsMessage = "";
        
        if ($awardResult['success']) {
            $pointsToAward = $awardResult['points_awarded'];
            $pointsMessage = " You earned {$pointsToAward} points!";
        } else if (isset($awardResult['error']) && $awardResult['error'] === 'cooldown') {
            $connect->rollBack();
            echo json_encode(['error' => 'cooldown', 'minutes_remaining' => $awardResult['minutes_remaining']]);
            exit();
        } else if (isset($awardResult['error'])) {
            $pointsMessage = " (Points error: " . $awardResult['error'] . ")";
        }

    }

    // Build message
    $baseMessage = $recordId ? "Growth record updated successfully!" : "Growth recorded successfully!";
    if ($pointsMessage && !$recordId) {
        $baseMessage .= $pointsMessage;
    }

    $connect->commit();
    echo json_encode([
        'success' => true,
        'message' => $baseMessage,
        'points_awarded' => $pointsToAward
    ]);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
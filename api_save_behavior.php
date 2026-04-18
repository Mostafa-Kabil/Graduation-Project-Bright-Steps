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
$behaviorDetails = $_POST['behavior_details'] ?? [];
$frequencies = $_POST['frequency'] ?? [];
$severities = $_POST['severity'] ?? [];

if (!$childId) {
    echo json_encode(['error' => 'Child ID is required']);
    exit();
}

// Verify child belongs to parent
$stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Invalid child selected']);
    exit();
}

try {
    $connect->beginTransaction();

    // Ensure motor skills category exists
    $stmt = $connect->prepare("SELECT category_id FROM behavior_category WHERE category_name = 'Motor Skills'");
    $stmt->execute();
    $catId = $stmt->fetchColumn();
    if (!$catId) {
        $stmt = $connect->prepare("INSERT INTO behavior_category (category_name, category_type, category_description) VALUES ('Motor Skills', 'Developmental', 'Physical and motor development milestones')");
        $stmt->execute();
        $catId = $connect->lastInsertId();
    }

    for ($i = 0; $i < count($behaviorDetails); $i++) {
        $detail = $behaviorDetails[$i];
        $freqText = $frequencies[$i] ?? '';
        $severityText = $severities[$i] ?? '';

        if (!$freqText || !$severityText)
            continue;

        // Ensure behavior exists
        $stmt = $connect->prepare("SELECT behavior_id FROM behavior WHERE behavior_details = ? AND category_id = ?");
        $stmt->execute([$detail, $catId]);
        $behaviorId = $stmt->fetchColumn();

        if (!$behaviorId) {
            $stmt = $connect->prepare("INSERT INTO behavior (category_id, behavior_type, behavior_details, indicator) VALUES (?, 'Milestone', ?, 'Standard')");
            $stmt->execute([$catId, $detail]);
            $behaviorId = $connect->lastInsertId();
        }

        // We use frequency text strings (rarely, always). The schema says 'frequency' is INT. Let's map it.
        $freqMap = ['rarely' => 1, 'sometimes' => 2, 'often' => 3, 'always' => 4];
        $freqInt = $freqMap[$freqText] ?? 2;

        // Insert or update into child_exhibited_behavior
        $stmt = $connect->prepare("INSERT INTO child_exhibited_behavior (child_id, behavior_id, frequency, severity) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE frequency = VALUES(frequency), severity = VALUES(severity)");
        $stmt->execute([$childId, $behaviorId, $freqInt, $severityText]);
    }

    $connect->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
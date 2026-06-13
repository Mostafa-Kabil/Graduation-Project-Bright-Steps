<?php
// api_admin_points.php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

// Ensure admin is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $connect->query("SELECT * FROM points_rules ORDER BY rule_key ASC");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'rules' => $rules]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ruleKey = $input['rule_key'] ?? '';
    $points = isset($input['points']) ? (int)$input['points'] : null;
    $cooldown = isset($input['cooldown_minutes']) ? (int)$input['cooldown_minutes'] : 0;
    
    // We handle empty values gracefully (like setting them to NULL if they are empty strings)
    $dailyCap = (isset($input['daily_cap']) && $input['daily_cap'] !== '') ? (int)$input['daily_cap'] : null;
    $weeklyCap = (isset($input['weekly_cap']) && $input['weekly_cap'] !== '') ? (int)$input['weekly_cap'] : null;

    if (!$ruleKey || $points === null) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit();
    }

    $stmt = $connect->prepare("UPDATE points_rules SET points = ?, cooldown_minutes = ?, daily_cap = ?, weekly_cap = ? WHERE rule_key = ?");
    $result = $stmt->execute([$points, $cooldown, $dailyCap, $weeklyCap, $ruleKey]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update rule']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>

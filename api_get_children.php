<?php
session_start();
include "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$parentId = $_SESSION['id'];

// Fetch all children for this parent
$sql = "SELECT child_id, ssn, first_name, last_name, birth_day, birth_month, birth_year, gender
        FROM child
        WHERE parent_id = :parent_id
        ORDER BY child_id ASC";
$stmt = $connect->prepare($sql);
$stmt->execute(['parent_id' => $parentId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($children as &$child) {
    // Calculate age
    $birthDate = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
    $now = time();
    $ageMonths = floor(($now - $birthDate) / (30.44 * 24 * 60 * 60));
    $child['age_months'] = (int) $ageMonths;

    // Latest growth record
    $sql2 = "SELECT record_id, height, weight, head_circumference, recorded_at
             FROM growth_record
             WHERE child_id = :child_id
             ORDER BY recorded_at DESC
             LIMIT 1";
    $stmt2 = $connect->prepare($sql2);
    $stmt2->execute(['child_id' => $child['child_id']]);
    $child['growth'] = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
}
unset($child);

echo json_encode(['children' => $children], JSON_UNESCAPED_UNICODE);

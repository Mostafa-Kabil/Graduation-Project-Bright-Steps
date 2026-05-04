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
$data = [];

$data['parent'] = [
    'id'    => $parentId,
    'fname' => $_SESSION['fname'],
    'lname' => $_SESSION['lname'],
    'email' => $_SESSION['email']
];

// --- Streaks (Parent Level) ---
$sqlStreaks = "SELECT streak_type, current_count, longest_count FROM streaks WHERE parent_id = :parent_id";
$stmtStreaks = $connect->prepare($sqlStreaks);
$stmtStreaks->execute(['parent_id' => $parentId]);
$streaksData = $stmtStreaks->fetchAll(PDO::FETCH_ASSOC);

$streakMap = [];
foreach ($streaksData as $s) {
    // Basic assignment; api_streaks.php check-in logic handles actual date validation/reset 
    $streakMap[$s['streak_type']] = $s;
}
$data['streaks'] = $streakMap;

// --- Subscription Plan ---
$sql = "SELECT s.plan_name, s.price, s.plan_period
        FROM parent_subscription ps
        INNER JOIN subscription s ON ps.subscription_id = s.subscription_id
        WHERE ps.parent_id = :parent_id
        LIMIT 1";
$stmt = $connect->prepare($sql);
$stmt->execute(['parent_id' => $parentId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
$data['subscription'] = $plan ?: ['plan_name' => 'Free', 'price' => '0.00', 'plan_period' => ''];

// --- Children ---
$sql = "SELECT child_id, first_name, last_name, birth_day, birth_month, birth_year, gender, ssn
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
    $child['age_months'] = (int)$ageMonths;

    if ($ageMonths >= 24) {
        $child['age_display'] = floor($ageMonths / 12) . ' years old';
    } else {
        $child['age_display'] = $ageMonths . ' months old';
    }

    $child['birth_date_formatted'] = date('M d, Y', $birthDate);

    // Latest growth record
    $sql2 = "SELECT height, weight, head_circumference, recorded_at
             FROM growth_record
             WHERE child_id = :child_id
             ORDER BY recorded_at DESC
             LIMIT 1";
    $stmt2 = $connect->prepare($sql2);
    $stmt2->execute(['child_id' => $child['child_id']]);
    $growth = $stmt2->fetch(PDO::FETCH_ASSOC);
    $child['growth'] = $growth ?: null;

    // All growth records (for chart)
    $sql3 = "SELECT height, weight, head_circumference, recorded_at
             FROM growth_record
             WHERE child_id = :child_id
             ORDER BY recorded_at ASC";
    $stmt3 = $connect->prepare($sql3);
    $stmt3->execute(['child_id' => $child['child_id']]);
    $child['growth_history'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Badges array and count
    $sql4 = "SELECT b.name, b.icon, b.description, cb.redeemed_at FROM child_badge cb JOIN badge b ON cb.badge_id = b.badge_id WHERE cb.child_id = :child_id";
    $stmt4 = $connect->prepare($sql4);
    $stmt4->execute(['child_id' => $child['child_id']]);
    $badgesList = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    $child['badges'] = $badgesList;
    $child['badge_count'] = count($badgesList);

    // Total Activities completed
    $sqlAct = "SELECT COUNT(*) FROM child_activities WHERE child_id = :child_id AND is_completed = 1";
    $stmtAct = $connect->prepare($sqlAct);
    $stmtAct->execute(['child_id' => $child['child_id']]);
    $child['activities_completed'] = (int)$stmtAct->fetchColumn();

    // Points wallet
    $sql5 = "SELECT total_points FROM points_wallet WHERE child_id = :child_id LIMIT 1";
    $stmt5 = $connect->prepare($sql5);
    $stmt5->execute(['child_id' => $child['child_id']]);
    $points = $stmt5->fetchColumn();
    $child['total_points'] = $points !== false ? (int)$points : 0;
}
unset($child);
$data['children'] = $children;

// --- Appointments ---
$sql = "SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.report, a.comment,
               s.first_name AS doc_fname, s.last_name AS doc_lname, s.specialization,
               c.clinic_name, c.location AS clinic_location
        FROM appointment a
        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE a.parent_id = :parent_id
          AND a.scheduled_at >= NOW()
        ORDER BY a.scheduled_at ASC
        LIMIT 10";
$stmt = $connect->prepare($sql);
$stmt->execute(['parent_id' => $parentId]);
$data['appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Feedback given ---
$sql = "SELECT COUNT(*) FROM feedback WHERE parent_id = :parent_id";
$stmt = $connect->prepare($sql);
$stmt->execute(['parent_id' => $parentId]);
$data['feedback_count'] = (int)$stmt->fetchColumn();

echo json_encode($data, JSON_UNESCAPED_UNICODE);
